<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->id();
            $table->string('team_a');
            $table->string('team_b');
            $table->string('team_a_short', 5);
            $table->string('team_b_short', 5);
            $table->string('team_a_logo')->nullable();
            $table->string('team_b_logo')->nullable();
            $table->dateTime('match_date');
            $table->string('venue')->nullable();
            $table->unsignedSmallInteger('match_number');
            $table->string('season')->default('IPL 2025');
            $table->enum('status', ['upcoming', 'live', 'completed'])->default('upcoming');
            $table->string('winning_team')->nullable();
            $table->decimal('win_multiplier', 4, 2)->default(1.90);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};
