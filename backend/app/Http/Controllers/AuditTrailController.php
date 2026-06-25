<?php

namespace App\Http\Controllers;

use App\Models\DataHistory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuditTrailController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = DataHistory::with(['changedBy:id,name,npp', 'data:id,title,category'])
            ->orderBy('created_at', 'desc');

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter by user who made the change
        if ($request->has('user_id')) {
            $query->where('changed_by', $request->user_id);
        }

        // Filter by action type
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by data record
        if ($request->has('data_id')) {
            $query->where('data_id', $request->data_id);
        }

        // Search by title or category
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('data', function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $perPage = $request->get('per_page', 15);
        $histories = $query->paginate($perPage);

        return response()->json($histories);
    }

    public function show(int $id): JsonResponse
    {
        $history = DataHistory::with(['changedBy:id,name,npp', 'data'])
            ->findOrFail($id);

        return response()->json($history);
    }
}
