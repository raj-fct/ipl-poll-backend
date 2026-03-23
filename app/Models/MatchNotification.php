<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatchNotification extends Model
{
    protected $fillable = ['match_id', 'type', 'sent_at', 'success_count', 'failure_count'];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(IplMatch::class, 'match_id');
    }
}