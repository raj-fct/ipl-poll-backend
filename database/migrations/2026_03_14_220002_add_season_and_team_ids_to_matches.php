<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->foreignId('season_id')->nullable()->after('espn_id')->constrained('seasons')->nullOnDelete();
            $table->foreignId('team_a_id')->nullable()->after('season_id')->constrained('teams')->nullOnDelete();
            $table->foreignId('team_b_id')->nullable()->after('team_a_id')->constrained('teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('matches', function (Blueprint $table) {
            $table->dropForeign(['season_id']);
            $table->dropForeign(['team_a_id']);
            $table->dropForeign(['team_b_id']);
            $table->dropColumn(['season_id', 'team_a_id', 'team_b_id']);
        });
    }
};
