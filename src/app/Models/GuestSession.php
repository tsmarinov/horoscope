<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class GuestSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'created_at'   => 'datetime',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(GuestProfile::class);
    }

    public function natalChart(): MorphOne
    {
        return $this->morphOne(NatalChart::class, 'subject');
    }

    public static function findOrCreateFromCookie(): static
    {
        $uuid = request()->cookie('guest_uuid');

        if ($uuid) {
            $session = static::where('uuid', $uuid)->first();

            if ($session) {
                $session->last_seen_at = now();
                $session->save();

                return $session;
            }
        }

        $session = static::create([
            'uuid'         => Str::uuid()->toString(),
            'last_seen_at' => now(),
            'created_at'   => now(),
        ]);

        return $session;
    }
}
