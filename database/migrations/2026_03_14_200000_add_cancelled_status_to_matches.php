<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE matches MODIFY COLUMN status ENUM('upcoming', 'live', 'completed', 'cancelled') DEFAULT 'upcoming'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE matches MODIFY COLUMN status ENUM('upcoming', 'live', 'completed') DEFAULT 'upcoming'");
    }
};
