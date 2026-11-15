<?php

namespace App\Http\Controllers;

use App\Models\CampusLocation;
use App\Models\Category;
use App\Models\Report;
use Illuminate\Http\Request;

// Peta Global: halaman dirender Blade, marker dirender di browser dari JSON (client-side rendering)
class MapController extends Controller
{
    public function index()
    {
        return view('map.index', [
            'categories' => Category::orderBy('name')->get(),
            'campus' => config('findit.campus'),
            'totals' => [
                'lost' => Report::visible()->where('type', 'lost')->count(),
                'found' => Report::visible()->where('type', 'found')->count(),
            ],
        ]);
    }

    public function reports(Request $request)
    {
        $filters = $request->validate([
            'type' => 'nullable|in:lost,found',
            'category' => 'nullable|integer',
            'days' => 'nullable|integer|in:7,30,90,365',
            'q' => 'nullable|string|max:60',
        ]);

        $reports = Report::with('category', 'campusLocation')
            ->visible()
            ->filter($filters)
            ->latest('event_at')
            ->limit(500)
            ->get();

        return response()->json([
            'count' => $reports->count(),
            'data' => $reports->map->toMapArray(),
        ], 200);
    }

    public function locations()
    {
        return response()->json([
            'data' => CampusLocation::orderBy('name')->get(['id', 'name', 'short_name', 'lat', 'lng']),
        ], 200);
    }
}
