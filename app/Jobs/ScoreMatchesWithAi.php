<?php

namespace App\Jobs;

use App\Models\Report;
use App\Services\AiMatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// Dijalankan oleh antrian (php artisan queue:work) agar pengguna tidak menunggu respons AI
class ScoreMatchesWithAi implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $lostReportId) {}

    public function handle(AiMatcher $matcher): void
    {
        $report = Report::find($this->lostReportId);

        if ($report && $report->isOpen()) {
            $matcher->scoreLostReport($report);
        }
    }
}
