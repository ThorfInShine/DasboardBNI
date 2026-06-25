<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Data;
use App\Models\ImportHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalDevices = Data::count();
        $totalEdrInstalled = Data::where('status', 'active')->count();
        $totalInactive = Data::where('status', 'inactive')->count();

        // Count per lokasi (category)
        $byLocation = Data::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Count per manufacturer from metadata JSON
        $byManufacturer = Data::select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.manufacturer')) as manufacturer"),
                DB::raw('count(*) as count')
            )
            ->whereNotNull('metadata')
            ->groupBy('manufacturer')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->filter(fn($item) => $item->manufacturer && $item->manufacturer !== 'null' && $item->manufacturer !== '');

        // Recent imports
        $recentImports = ImportHistory::latest()->limit(5)->get(['id', 'filename', 'status', 'success_count', 'error_count', 'warning_count', 'created_at']);

        // Devices added in last 7 days
        $recentDevices = Data::where('created_at', '>=', now()->subDays(7))->count();

        return response()->json([
            'total_devices' => $totalDevices,
            'total_edr_installed' => $totalEdrInstalled,
            'total_inactive' => $totalInactive,
            'recent_devices' => $recentDevices,
            'by_location' => $byLocation,
            'by_manufacturer' => $byManufacturer->values(),
            'recent_imports' => $recentImports,
        ]);
    }

    public function lineChart(Request $request)
    {
        $days = $request->input('days', 30);

        // Group imports by date
        $imports = ImportHistory::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(success_count) as total_imported'),
                DB::raw('SUM(error_count) as total_errors'),
                DB::raw('COUNT(*) as import_count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'labels' => $imports->pluck('date'),
            'total_imported' => $imports->pluck('total_imported'),
            'total_errors' => $imports->pluck('total_errors'),
            'import_count' => $imports->pluck('import_count'),
        ]);
    }

    public function barChart()
    {
        // Top 10 locations by device count
        $byLocation = Data::select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return response()->json([
            'labels' => $byLocation->pluck('category'),
            'counts' => $byLocation->pluck('count'),
        ]);
    }

    public function pieChart()
    {
        // Manufacturer distribution
        $byManufacturer = Data::select(
                DB::raw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.manufacturer')) as manufacturer"),
                DB::raw('count(*) as count')
            )
            ->whereNotNull('metadata')
            ->groupBy('manufacturer')
            ->orderByDesc('count')
            ->limit(8)
            ->get()
            ->filter(fn($item) => $item->manufacturer && $item->manufacturer !== 'null' && $item->manufacturer !== '');

        return response()->json([
            'labels' => $byManufacturer->pluck('manufacturer')->values(),
            'counts' => $byManufacturer->pluck('count')->values(),
        ]);
    }
}
