<?php

use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\ClaimController as AdminClaimController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\MatchController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ShareController;
use Illuminate\Support\Facades\Route;

// ---------- Publik ----------
Route::get('/', [MapController::class, 'index'])->name('map');
Route::get('/ajax/map/reports', [MapController::class, 'reports'])->name('ajax.map.reports');
Route::get('/ajax/campus-locations', [MapController::class, 'locations'])->name('ajax.locations');
Route::get('/r/{code}', [ShareController::class, 'short'])->name('share.short');
Route::get('/reports/{report}/poster', [ShareController::class, 'poster'])->name('reports.poster');

// ---------- Autentikasi (Modul 13: Auth::attempt) ----------
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.attempt');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// ---------- Laporan (resource controller seperti Modul 12) ----------
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::middleware('auth')->group(function () {
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->name('reports.store');
    Route::get('/reports/{report}/edit', [ReportController::class, 'edit'])->name('reports.edit');
    Route::put('/reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    Route::delete('/reports/{report}', [ReportController::class, 'destroy'])->name('reports.destroy');
    Route::post('/reports/{report}/close', [ReportController::class, 'close'])->name('reports.close');
    Route::post('/reports/{report}/resolve', [ReportController::class, 'resolve'])->name('reports.resolve');
    Route::get('/my/reports', [ReportController::class, 'mine'])->name('reports.mine');

    // Kandidat auto-match
    Route::post('/matches/{match}/dismiss', [MatchController::class, 'dismiss'])->name('matches.dismiss');

    // Notifikasi
    Route::get('/alerts', [AlertController::class, 'index'])->name('alerts.index');
    Route::post('/alerts/read-all', [AlertController::class, 'readAll'])->name('alerts.readAll');
    Route::get('/alerts/{alert}/open', [AlertController::class, 'open'])->name('alerts.open');
    Route::get('/ajax/alerts', [AlertController::class, 'feed'])->name('ajax.alerts');

    // Klaim kepemilikan
    Route::get('/claims', [ClaimController::class, 'index'])->name('claims.index');
    Route::get('/reports/{report}/claims/create', [ClaimController::class, 'create'])->name('claims.create');
    Route::post('/reports/{report}/claims', [ClaimController::class, 'store'])->name('claims.store');
    Route::get('/claims/{claim}', [ClaimController::class, 'show'])->name('claims.show');
    Route::post('/claims/{claim}/cancel', [ClaimController::class, 'cancel'])->name('claims.cancel');
    Route::post('/claims/{claim}/returned', [ClaimController::class, 'returned'])->name('claims.returned');

    // Chat serah terima (AJAX)
    Route::get('/ajax/claims/{claim}/messages', [MessageController::class, 'index'])->name('ajax.messages.index');
    Route::post('/ajax/claims/{claim}/messages', [MessageController::class, 'store'])->name('ajax.messages.store');
});
Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');

// ---------- Admin ----------
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/ajax/reports', [AdminReportController::class, 'data'])->name('reports.data');
    Route::post('/reports/{report}/toggle-hidden', [AdminReportController::class, 'toggleHidden'])->name('reports.toggleHidden');
    Route::post('/reports/{report}/close', [AdminReportController::class, 'close'])->name('reports.close');

    Route::get('/claims', [AdminClaimController::class, 'index'])->name('claims.index');
    Route::get('/ajax/claims', [AdminClaimController::class, 'data'])->name('claims.data');
    Route::get('/claims/{claim}', [AdminClaimController::class, 'show'])->name('claims.show');
    Route::post('/claims/{claim}/approve', [AdminClaimController::class, 'approve'])->name('claims.approve');
    Route::post('/claims/{claim}/reject', [AdminClaimController::class, 'reject'])->name('claims.reject');

    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/ajax/users', [AdminUserController::class, 'data'])->name('users.data');
    Route::post('/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive'])->name('users.toggleActive');

    Route::resource('categories', AdminCategoryController::class)->except('show');
});
