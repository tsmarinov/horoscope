<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PremiumUsage extends Model
{
    protected $table = 'premium_usage';

    protected $fillable = ['user_id', 'year', 'month', 'count'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
