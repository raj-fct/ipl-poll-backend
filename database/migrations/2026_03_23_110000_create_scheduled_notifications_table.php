<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title', 200);
            $table->string('body', 500);
            $table->foreignId('match_id')->nullable()->constrained('matches')->nullOnDelete();
            $table->dateTime('scheduled_at');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->integer('success_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->dateTime('sent_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
