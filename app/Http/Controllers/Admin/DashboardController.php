<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Claim;
use App\Models\Report;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'active' => Report::whereIn('status', ['open', 'in_claim'])->count(),
            'pending' => Claim::where('status', 'pending')->count(),
            'resolved_month' => Report::where('status', 'resolved')->where('updated_at', '>=', now()->startOfMonth())->count(),
            'users' => User::where('role', 'user')->count(),
        ];

        $byCategory = Category::withCount(['reports' => fn ($q) => $q->whereIn('status', ['open', 'in_claim'])])
            ->orderByDesc('reports_count')
            ->get();

        $pendingClaims = Claim::with('report', 'user')->where('status', 'pending')->oldest()->take(6)->get();
        $latestReports = Report::with('category', 'user')->latest()->take(6)->get();

        return view('admin.dashboard', compact('stats', 'byCategory', 'pendingClaims', 'latestReports'));
    }
}
