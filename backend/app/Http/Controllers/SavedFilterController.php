<?php

namespace App\Http\Controllers;

use App\Models\SavedFilter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SavedFilterController extends Controller
{
    public function index(): JsonResponse
    {
        $filters = SavedFilter::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($filters);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'filters' => 'required|array',
        ]);

        $filter = SavedFilter::create([
            'user_id' => Auth::id(),
            'name' => $validated['name'],
            'filters' => $validated['filters'],
        ]);

        return response()->json([
            'message' => 'Filter saved successfully',
            'filter' => $filter,
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
        $filter = SavedFilter::where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($filter);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $filter = SavedFilter::where('user_id', Auth::id())
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'filters' => 'sometimes|array',
        ]);

        $filter->update($validated);

        return response()->json([
            'message' => 'Filter updated successfully',
            'filter' => $filter,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $filter = SavedFilter::where('user_id', Auth::id())
            ->findOrFail($id);

        $filter->delete();

        return response()->json([
            'message' => 'Filter deleted successfully',
        ]);
    }
}
