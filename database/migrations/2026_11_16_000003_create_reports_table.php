<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->foreignId('campus_location_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('type', ['lost', 'found']);
            $table->string('title', 80);
            $table->text('description');
            $table->string('color', 30)->nullable();
            $table->string('brand', 40)->nullable();
            $table->string('secret_detail')->nullable();
            $table->dateTime('event_at');
            $table->string('location_note', 120)->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->string('photo')->nullable();
            $table->string('photo_thumb')->nullable();
            $table->enum('status', ['open', 'in_claim', 'resolved', 'closed'])->default('open');
            $table->boolean('is_hidden')->default(false);
            $table->string('share_code', 10)->unique();
            $table->timestamps();

            $table->index(['type', 'status', 'is_hidden']);
            $table->index('event_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};
