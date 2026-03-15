<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->string('score_a')->nullable()->after('team_b_logo');
            $table->string('score_b')->nullable()->after('score_a');
            $table->string('toss_winner')->nullable()->after('winning_team');
            $table->string('toss_decision')->nullable()->after('toss_winner');
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropColumn(['score_a', 'score_b', 'toss_winner', 'toss_decision']);
        });
    }
};
