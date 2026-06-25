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

    public function export(Request $request)
    {
        $query = Data::query();

        if ($request->has('search') && $request->input('search')) {
            $query->search($request->input('search'));
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="data_management_export_' . date('Y-m-d_His') . '.csv"',
        ];

        $columns = [
            'ID', 'Online', 'Group ID', 'Domain/Workgroup', 'Computer Name',
            'Operating System', 'OS Version', 'Manufacturer', 'Product Name',
            'RAM (MB)', 'Chasis Type', 'Status EDR', 'System Serial Number',
            'Last Checkin Time', 'Agent GUID', 'Created At', 'Updated At'
        ];

        $callback = function () use ($data, $columns) {
            $file = fopen('php://output', 'w');
            // BOM for UTF-8 Excel compatibility
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $columns);

            foreach ($data as $row) {
                $metadata = $row->metadata ?? [];
                fputcsv($file, [
                    (string) ($metadata['device_id'] ?? $row->id),
                    (string) ($metadata['online_status'] ?? ''),
                    $row->category,
                    $metadata['domain_workgroup'] ?? '',
                    $row->title,
                    $metadata['os'] ?? '',
                    $metadata['os_version'] ?? '',
                    $metadata['manufacturer'] ?? '',
                    $metadata['model'] ?? '',
                    (string) $row->value,
                    $metadata['chasis_type'] ?? '',
                    $row->status === 'active' ? 'Terinstall' : 'Tidak Aktif',
                    (string) ($metadata['serial_number'] ?? ''),
                    $metadata['last_checkin'] ?? '',
                    (string) ($metadata['agentguid'] ?? ''),
                    (string) $row->created_at,
                    (string) $row->updated_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
