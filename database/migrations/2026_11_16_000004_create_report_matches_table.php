<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lost_report_id')->constrained('reports')->cascadeOnDelete();
            $table->foreignId('found_report_id')->constrained('reports')->cascadeOnDelete();
            $table->unsignedTinyInteger('rule_score');
            $table->unsignedTinyInteger('ai_score')->nullable();
            $table->string('ai_reason', 255)->nullable();
            $table->unsignedTinyInteger('score');
            $table->unsignedInteger('distance_m');
            $table->enum('status', ['suggested', 'dismissed', 'claimed'])->default('suggested');
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['lost_report_id', 'found_report_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_matches');
    }
};
