<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'mobile',
        'password',
        'must_change_password',
        'coin_balance',
        'is_admin',
        'is_active',
        'fcm_token',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password'             => 'hashed',
        'must_change_password' => 'boolean',
        'is_admin'             => 'boolean',
        'is_active'            => 'boolean',
        'coin_balance'         => 'integer',
    ];

    // ─── Relationships ────────────────────────────────────────

    public function polls()
    {
        return $this->hasMany(Poll::class);
    }

    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }

    // ─── Coin Helpers ─────────────────────────────────────────

    public function creditCoins(int $amount, string $type, string $description, $reference = null): CoinTransaction
    {
        $locked = User::lockForUpdate()->find($this->id);
        $locked->increment('coin_balance', $amount);
        $this->coin_balance = $locked->fresh()->coin_balance;
        return $this->logTransaction($amount, $type, $description, $reference);
    }

    public function debitCoins(int $amount, string $type, string $description, $reference = null): CoinTransaction
    {
        $locked = User::lockForUpdate()->find($this->id);
        if ($locked->coin_balance < $amount) {
            throw new \Exception('Insufficient coin balance.');
        }
        $locked->decrement('coin_balance', $amount);
        $this->coin_balance = $locked->fresh()->coin_balance;
        return $this->logTransaction(-$amount, $type, $description, $reference);
    }

    private function logTransaction(int $amount, string $type, string $description, $reference): CoinTransaction
    {
        $txn = new CoinTransaction([
            'user_id'       => $this->id,
            'type'          => $type,
            'amount'        => $amount,
            'balance_after' => $this->coin_balance,
            'description'   => $description,
        ]);
        if ($reference) {
            $txn->reference()->associate($reference);
        }
        $txn->save();
        return $txn;
    }
}
