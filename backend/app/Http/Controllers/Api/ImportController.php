<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    public function import(Request $request)
    {
        // Increase execution time for large files
        set_time_limit(300);
        ini_set('memory_limit', '256M');

        try {
            $request->validate([
                'file' => 'required|file|max:10240',
            ]);

            $file = $request->file('file');

            // Validate file extension manually (more reliable than mimes for CSV)
            $extension = strtolower($file->getClientOriginalExtension());
            if (!in_array($extension, ['csv', 'xlsx', 'xls'])) {
                return response()->json([
                    'message' => 'File harus berformat CSV, XLSX, atau XLS',
                    'error' => "Received file with extension: .{$extension}",
                ], 422);
            }

            // Use native PHP for CSV (much faster than Maatwebsite\Excel for CSV)
            if ($extension === 'csv') {
                $content = file_get_contents($file->getRealPath());

                // Strip BOM if present
                $content = preg_replace('/^\x{FEFF}/u', '', $content);

                // Normalize line endings to \n
                $content = str_replace(["\r\n", "\r"], "\n", $content);

                // Parse CSV line by line
                $rows = [];
                $lines = explode("\n", $content);
                unset($content); // Free memory

                foreach ($lines as $line) {
                    $trimmed = trim($line);
                    if ($trimmed === '') {
                        continue;
                    }

                    // Detect lines fully wrapped in outer quotes:
                    //   "val1,val2,""quoted,value"",val3"
                    // These are exported by some tools (e.g. Google Sheets) and need
                    // to be unwrapped before parsing.
                    if (strlen($trimmed) >= 2
                        && $trimmed[0] === '"'
                        && $trimmed[-1] === '"'
                    ) {
                        // Strip outer quotes
                        $inner = substr($trimmed, 1, -1);
                        // Unescape doubled quotes back to single quotes
                        $inner = str_replace('""', '"', $inner);
                        $rows[] = str_getcsv($inner, ',', '"', '');
                    } else {
                        $rows[] = str_getcsv($trimmed, ',', '"', '');
                    }
                }
            } else {
                // Use Excel library only for xlsx/xls files
                $rows = Excel::toArray([], $file)[0];
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to read file',
                'error' => $e->getMessage(),
            ], 400);
        }


        if (count($rows) < 1) {
            return response()->json([
                'message' => 'File is empty',
            ], 400);
        }

        // Detect file format: BNI format vs Standard format
        $bniFormat = $this->detectBNIFormat($rows);

        $filename = $file->getClientOriginalName();


        if ($bniFormat === 'header') {
            return $this->importBNIFormat($rows, $filename, hasHeader: true);
        } elseif ($bniFormat === 'no_header') {
            return $this->importBNIFormat($rows, $filename, hasHeader: false);
        } else {
            return $this->importStandardFormat($rows, $filename);
        }
    }

    /**
     * Known BNI column headers for files that have a header row.
     */
    private const BNI_HEADER_COLUMNS = ['computer_name', 'operating_system', 'manufacturer', 'product_name'];

    /**
     * Default header for BNI files that lack a header row (13-column format).
     */
    private const BNI_DEFAULT_HEADER_13 = [
        'id', 'online', 'group_id', 'domain_workgroup', 'computer_name',
        'operating_system', 'os_version', 'manufacturer', 'product_name',
        'ram_size', 'chasis_type', 'status_instalasi_edr', 'system_serial_number',
    ];

    /**
     * Default header for BNI files that have a header row (15-column format).
     */
    private const BNI_DEFAULT_HEADER_15 = [
        'id', 'online', 'group_id', 'domain_workgroup', 'computer_name',
        'operating_system', 'os_version', 'manufacturer', 'product_name',
        'ram_size', 'chasis_type', 'status_instalasi_edr', 'system_serial_number',
        'last_checkin_time', 'agentguid',
    ];

    /**
     * Detect whether the file is BNI format.
     * Returns: 'header' if BNI with header, 'no_header' if BNI without header, false if standard.
     */
    private function detectBNIFormat($rows)
    {
        if (count($rows) < 1) {
            return false;
        }

        // 1. Check if first row looks like a BNI header (has named BNI columns)
        $firstRow = array_map('trim', array_map('strtolower', $rows[0]));

        $matchCount = 0;
        foreach (self::BNI_HEADER_COLUMNS as $col) {
            if (in_array($col, $firstRow)) {
                $matchCount++;
            }
        }

        if ($matchCount >= 3) {
            return 'header'; // BNI format with header row
        }

        // 2. Check if first row looks like BNI data (no header):
        //    - 13 columns (old format) or 15 columns (new format with last_checkin_time + agentguid)
        //    - First column is a long numeric ID (>= 10 digits)
        //    - Column at index 4 looks like a hostname (contains letters/dashes)
        //    - Column at index 7 looks like a manufacturer name (contains letters)
        $colCount = count($rows[0]);
        if (in_array($colCount, [13, 15])) {
            $firstCol = trim($rows[0][0] ?? '');
            $fifthCol = trim($rows[0][4] ?? '');
            $eighthCol = trim($rows[0][7] ?? '');

            // First col should be a long numeric ID
            $isLongNumericId = is_numeric($firstCol) && strlen($firstCol) >= 10;
            // Fifth col (computer_name) should contain letters
            $hasHostname = preg_match('/[a-zA-Z]/', $fifthCol);
            // Eighth col (manufacturer) should contain letters
            $hasManufacturer = preg_match('/[a-zA-Z]/', $eighthCol);

            if ($isLongNumericId && $hasHostname && $hasManufacturer) {
                return 'no_header'; // BNI format without header row
            }
        }

        return false;
    }

    private function importBNIFormat($rows, $filename, bool $hasHeader = true)
    {
        $validRows = [];
        $errorRows = [];
        $warningRows = [];
        $currentUserId = Auth::id();

        if ($hasHeader) {
            // Parse header row (trim whitespace from column names)
            $header = array_map('trim', array_map('strtolower', $rows[0]));
            $dataRows = array_slice($rows, 1);
        } else {
            // No header row — assign default header based on column count
            $colCount = count($rows[0]);
            $header = ($colCount >= 15) ? self::BNI_DEFAULT_HEADER_15 : self::BNI_DEFAULT_HEADER_13;
            $dataRows = $rows; // All rows are data
        }

        foreach ($dataRows as $index => $row) {
            $rowNumber = $hasHeader ? $index + 2 : $index + 1; // Adjust for presence/absence of header

            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            // Pad or truncate row to match header length
            while (count($row) < count($header)) {
                $row[] = '';
            }
            if (count($row) > count($header)) {
                $row = array_slice($row, 0, count($header));
            }

            $rowData = @array_combine($header, array_map('trim', $row));
            if ($rowData === false) {
                $errorRows[] = [
                    'row' => $rowNumber,
                    'message' => 'Column count mismatch',
                ];
                continue;
            }

            try {
                $deviceId = $rowData['id'] ?? $rowData['agentguid'] ?? '';
                $location = $rowData['group_id'] ?? '';
                $hostname = $rowData['computer_name'] ?? '';
                $os = $rowData['operating_system'] ?? '';
                $osVersion = $rowData['os_version'] ?? '';
                $manufacturer = $rowData['manufacturer'] ?? '';
                $model = $rowData['product_name'] ?? '';
                $memory = $rowData['ram_size'] ?? '0';
                $statusBNI = $rowData['status_instalasi_edr'] ?? '';
                $serialNumber = $rowData['system_serial_number'] ?? '';
                $lastCheckin = $rowData['last_checkin_time'] ?? '';
                $onlineStatus = $rowData['online'] ?? '';
                $domainWorkgroup = $rowData['domain_workgroup'] ?? '';
                $chasisType = $rowData['chasis_type'] ?? '';
                $agentGuid = $rowData['agentguid'] ?? '';

                // Track missing fields for warnings
                $missingFields = [];
                if (strlen($hostname) === 0) {
                    $missingFields[] = 'computer_name';
                }
                if (strlen($deviceId) === 0) {
                    $missingFields[] = 'id';
                }

                // Record warning if any fields were missing
                if (!empty($missingFields)) {
                    $warningRows[] = [
                        'row' => $rowNumber,
                        'message' => 'Data tidak lengkap: ' . implode(', ', $missingFields) . ' kosong',
                        'missing_fields' => $missingFields,
                    ];
                }

                // Map to Data model attributes
                $validRows[] = [
                    'category' => $location ?: 'Unknown',
                    'value' => floatval($memory),
                    'date' => !empty($lastCheckin) ? date('Y-m-d', strtotime($lastCheckin)) : now()->format('Y-m-d'),
                    'title' => $hostname,
                    'description' => trim("$os $osVersion - $manufacturer $model"),
                    'status' => (stripos($statusBNI, 'terinstall') !== false) ? 'active' : 'inactive',
                    'metadata' => [
                        'device_id' => $deviceId,
                        'manufacturer' => $manufacturer,
                        'model' => $model,
                        'os' => $os,
                        'os_version' => $osVersion,
                        'serial_number' => $serialNumber,
                        'last_checkin' => $lastCheckin,
                        'online_status' => $onlineStatus,
                        'domain_workgroup' => $domainWorkgroup,
                        'chasis_type' => $chasisType,
                        'agentguid' => $agentGuid,
                        'import_type' => 'bni_device',
                        'has_warnings' => !empty($missingFields),
                    ],
                    'created_by' => $currentUserId,
                    'updated_by' => $currentUserId,
                ];
            } catch (\Exception $e) {
                $errorRows[] = [
                    'row' => $rowNumber,
                    'message' => 'Error processing row: ' . $e->getMessage(),
                ];
            }
        }

        return $this->saveImportedData($validRows, $errorRows, count($dataRows), $filename, $warningRows);
    }

    private function importStandardFormat($rows, $filename)
    {
        if (count($rows) < 2) {
            return response()->json([
                'message' => 'File has no data rows',
            ], 400);
        }

        $header = array_map('strtolower', array_map('trim', $rows[0]));
        $dataRows = array_slice($rows, 1);

        $requiredColumns = ['category', 'value', 'date', 'title'];
        $missingColumns = array_diff($requiredColumns, $header);

        if (!empty($missingColumns)) {
            return response()->json([
                'message' => 'Missing required columns',
                'missing_columns' => array_values($missingColumns),
                'found_columns' => $header,
                'column_count' => count($header),
                'first_row_sample' => array_slice($rows[0], 0, 5),
            ], 400);
        }

        $validRows = [];
        $errorRows = [];
        $currentUserId = Auth::id();

        foreach ($dataRows as $index => $row) {
            $rowNumber = $index + 2;

            if (empty(array_filter($row))) {
                continue;
            }

            // Pad or truncate row to match header length
            $rowCount = count($row);
            $headerCount = count($header);
            if ($rowCount < $headerCount) {
                $row = array_pad($row, $headerCount, '');
            } elseif ($rowCount > $headerCount) {
                $row = array_slice($row, 0, $headerCount);
            }

            $rowData = array_combine($header, $row);

            $validator = Validator::make($rowData, [
                'category' => 'required|string|max:255',
                'value' => 'required|numeric',
                'date' => 'required|date_format:Y-m-d',
                'title' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'nullable|in:active,inactive',
            ]);

            if ($validator->fails()) {
                $errorRows[] = [
                    'row' => $rowNumber,
                    'data' => $rowData,
                    'errors' => $validator->errors()->all(),
                ];
                continue;
            }

            $validRows[] = [
                'category' => $rowData['category'],
                'value' => $rowData['value'],
                'date' => $rowData['date'],
                'title' => $rowData['title'],
                'description' => $rowData['description'] ?? null,
                'status' => $rowData['status'] ?? 'active',
                'metadata' => isset($rowData['metadata']) ? json_decode($rowData['metadata'], true) : null,
                'created_by' => $currentUserId,
                'updated_by' => $currentUserId,
            ];
        }

        return $this->saveImportedData($validRows, $errorRows, count($dataRows), $filename);
    }

    private function saveImportedData($validRows, $errorRows, $totalRows, $filename, $warningRows = [])
    {
        $successCount = 0;

        if (!empty($validRows)) {
            try {
                // Use Eloquent model's create() inside a transaction via the Model
                Data::getConnectionResolver()
                    ->connection()
                    ->transaction(function () use ($validRows, &$successCount) {
                        foreach ($validRows as $rowData) {
                            Data::create($rowData);
                            $successCount++;
                        }
                    });
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to import data',
                    'error' => $e->getMessage(),
                ], 500);
            }
        }

        // Determine status
        $errorCount = count($errorRows);
        $warningCount = count($warningRows);
        if ($successCount > 0 && $errorCount === 0) {
            $status = 'success';
        } elseif ($successCount > 0 && $errorCount > 0) {
            $status = 'partial';
        } else {
            $status = 'failed';
        }

        // Save import history using Eloquent
        ImportHistory::create([
            'filename' => $filename,
            'status' => $status,
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'warning_count' => $warningCount,
            'total_rows' => $totalRows,
            'errors' => $errorRows,
            'warnings' => $warningRows,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Import completed',
            'success_count' => $successCount,
            'error_count' => count($errorRows),
            'warning_count' => $warningCount,
            'errors' => $errorRows,
            'warnings' => $warningRows,
        ], $successCount > 0 ? 200 : 400);
    }

    public function history(Request $request)
    {
        $query = ImportHistory::with('user')->recent();

        // If user is not admin, only show their own imports
        if (!Auth::user()->isAdmin()) {
            $query->byUser(Auth::id());
        }

        $perPage = $request->input('per_page', 20);
        $histories = $query->paginate($perPage);

        return response()->json($histories);
    }
}
