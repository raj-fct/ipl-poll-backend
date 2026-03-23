<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('match_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('match_id')->constrained('matches')->cascadeOnDelete();
            $table->string('type'); // match_day, 2hr_before, 1hr_before, 30min_before
            $table->timestamp('sent_at');
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamps();

            $table->unique(['match_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('match_notifications');
    }
};
