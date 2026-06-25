<?php

namespace App\Http\Controllers;

use App\Models\DashboardPreference;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DashboardPreferenceController extends Controller
{
    public function show(): JsonResponse
    {
        $preference = DashboardPreference::where('user_id', Auth::id())->first();

        if (!$preference) {
            return response()->json([
                'widget_visibility' => null,
                'widget_layout' => null,
                'date_range_preferences' => null,
            ]);
        }

        return response()->json($preference);
    }

    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widget_visibility' => 'nullable|array',
            'widget_layout' => 'nullable|array',
            'date_range_preferences' => 'nullable|array',
        ]);

        $preference = DashboardPreference::updateOrCreate(
            ['user_id' => Auth::id()],
            $validated
        );

        return response()->json([
            'message' => 'Dashboard preferences updated successfully',
            'preference' => $preference,
        ]);
    }

    public function destroy(): JsonResponse
    {
        $deleted = DashboardPreference::where('user_id', Auth::id())->delete();

        return response()->json([
            'message' => 'Dashboard preferences reset successfully',
            'deleted' => $deleted > 0,
        ]);
    }
}
