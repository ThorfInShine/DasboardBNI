<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Data;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DataController extends Controller
{
    public function index(Request $request)
    {
        $query = Data::query()->with(['creator', 'updater']);

        // Use the search scope from Data model
        if ($request->has('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $status === 'active' ? $query->active() : $query->inactive();
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange(
                $request->input('start_date'),
                $request->input('end_date')
            );
        }

        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = $request->input('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50]) ? $perPage : 10;

        $data = $query->paginate($perPage);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'category' => 'required|string|max:255',
            'value' => 'required|numeric',
            'date' => 'required|date',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['status'] = $validated['status'] ?? 'active';

        $data = Data::create($validated);
        $data->load(['creator', 'updater']);

        return response()->json([
            'message' => 'Data created successfully',
            'data' => $data,
        ], 201);
    }

    public function show(string $id)
    {
        $data = Data::with(['creator', 'updater', 'histories.changedBy'])
            ->findOrFail($id);

        return response()->json([
            'data' => $data,
        ]);
    }

    public function update(Request $request, string $id)
    {
        $data = Data::findOrFail($id);

        $validated = $request->validate([
            'category' => 'sometimes|string|max:255',
            'value' => 'sometimes|numeric',
            'date' => 'sometimes|date',
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'status' => 'sometimes|in:active,inactive',
            'metadata' => 'nullable|array',
        ]);

        $validated['updated_by'] = Auth::id();
        $data->update($validated);
        $data->load(['creator', 'updater']);

        return response()->json([
            'message' => 'Data updated successfully',
            'data' => $data,
        ]);
    }

    public function destroy(string $id)
    {
        $data = Data::findOrFail($id);
        $data->delete();

        return response()->json([
            'message' => 'Data berhasil dihapus',
        ]);
    }

    public function batchDestroy(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:data,id',
        ]);

        $count = Data::whereIn('id', $validated['ids'])->delete();

        return response()->json([
            'message' => "$count data berhasil dihapus",
            'deleted_count' => $count,
        ]);
    }

    public function batchUpdate(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:data,id',
            'updates' => 'required|array',
            'updates.category' => 'sometimes|string|max:255',
            'updates.status' => 'sometimes|in:active,inactive',
            'updates.value' => 'sometimes|numeric',
            'updates.description' => 'nullable|string',
        ]);

        $updates = $validated['updates'];
        $updates['updated_by'] = Auth::id();

        $count = Data::whereIn('id', $validated['ids'])->update($updates);

        return response()->json([
            'message' => "$count data berhasil diupdate",
            'updated_count' => $count,
        ]);
    }

    public function export(Request $request)
    {
        $format = $request->input('format', 'csv'); // csv or xlsx
        $columns = $request->input('columns', []); // Array of columns to export

        $query = Data::query();

        // Apply filters
        if ($request->has('search') && $request->input('search')) {
            $query->search($request->input('search'));
        }

        if ($request->has('category')) {
            $query->byCategory($request->input('category'));
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            $status === 'active' ? $query->active() : $query->inactive();
        }

        if ($request->has('start_date') && $request->has('end_date')) {
            $query->byDateRange(
                $request->input('start_date'),
                $request->input('end_date')
            );
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        // Define all available columns
        $availableColumns = [
            'id' => 'ID',
            'device_id' => 'Device ID',
            'online_status' => 'Online',
            'category' => 'Group ID',
            'domain_workgroup' => 'Domain/Workgroup',
            'title' => 'Computer Name',
            'os' => 'Operating System',
            'os_version' => 'OS Version',
            'manufacturer' => 'Manufacturer',
            'model' => 'Product Name',
            'value' => 'RAM (MB)',
            'chasis_type' => 'Chasis Type',
            'status' => 'Status EDR',
            'serial_number' => 'System Serial Number',
            'last_checkin' => 'Last Checkin Time',
            'agentguid' => 'Agent GUID',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];

        // Use selected columns or default to all
        $selectedColumns = !empty($columns) ? $columns : array_keys($availableColumns);
        $headers = array_intersect_key($availableColumns, array_flip($selectedColumns));

        if ($format === 'xlsx') {
            return $this->exportXlsx($data, $headers);
        }

        return $this->exportCsv($data, $headers);
    }

    private function exportCsv($data, $headers)
    {
        $httpHeaders = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $callback = function () use ($data, $headers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
            fputcsv($file, array_values($headers));

            foreach ($data as $row) {
                $metadata = $row->metadata ?? [];
                $rowData = [];

                foreach (array_keys($headers) as $column) {
                    $rowData[] = $this->getColumnValue($row, $column, $metadata);
                }

                fputcsv($file, $rowData);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $httpHeaders);
    }

    private function exportXlsx($data, $headers)
    {
        $exportData = [];
        $exportData[] = array_values($headers); // Header row

        foreach ($data as $row) {
            $metadata = $row->metadata ?? [];
            $rowData = [];

            foreach (array_keys($headers) as $column) {
                $rowData[] = $this->getColumnValue($row, $column, $metadata);
            }

            $exportData[] = $rowData;
        }

        $filename = 'data_export_' . date('Y-m-d_His') . '.xlsx';

        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\DataExport($exportData),
            $filename
        );
    }

    private function getColumnValue($row, $column, $metadata)
    {
        return match ($column) {
            'id' => (string) $row->id,
            'device_id' => (string) ($metadata['device_id'] ?? $row->id),
            'online_status' => (string) ($metadata['online_status'] ?? ''),
            'category' => $row->category,
            'domain_workgroup' => $metadata['domain_workgroup'] ?? '',
            'title' => $row->title,
            'os' => $metadata['os'] ?? '',
            'os_version' => $metadata['os_version'] ?? '',
            'manufacturer' => $metadata['manufacturer'] ?? '',
            'model' => $metadata['model'] ?? '',
            'value' => (string) $row->value,
            'chasis_type' => $metadata['chasis_type'] ?? '',
            'status' => $row->status === 'active' ? 'Terinstall' : 'Tidak Aktif',
            'serial_number' => (string) ($metadata['serial_number'] ?? ''),
            'last_checkin' => $metadata['last_checkin'] ?? '',
            'agentguid' => (string) ($metadata['agentguid'] ?? ''),
            'created_at' => (string) $row->created_at,
            'updated_at' => (string) $row->updated_at,
            default => '',
        };
    }
}
