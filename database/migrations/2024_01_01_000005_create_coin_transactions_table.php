<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'bonus',
                'bid_debit',
                'win_credit',
                'admin_credit',
                'admin_debit',
                'refund',
            ]);
            $table->bigInteger('amount');
            $table->unsignedBigInteger('balance_after');
            $table->nullableMorphs('reference');
            $table->string('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
