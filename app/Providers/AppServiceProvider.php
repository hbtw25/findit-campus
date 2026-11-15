<?php

namespace App\Providers;

use Carbon\Carbon;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Format waktu bahasa Indonesia, contoh "2 jam yang lalu"
        Carbon::setLocale('id');

        // Paginasi memakai tampilan Bootstrap 5
        Paginator::useBootstrapFive();

        // Jumlah notifikasi belum dibaca untuk ikon lonceng di navbar
        View::composer(['layouts.*', 'partials.navbar'], function ($view) {
            $view->with('unreadAlerts', Auth::check()
                ? Auth::user()->alerts()->whereNull('read_at')->count()
                : 0);
        });
    }
}
