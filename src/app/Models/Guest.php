<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

/**
 * @property int             $id
 * @property string          $uuid
 * @property \Carbon\Carbon  $last_seen_at
 * @property \Carbon\Carbon  $created_at
 */
class Guest extends Model
{
    public $timestamps = false;

    protected $table = 'guests';

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
        return $this->hasOne(Profile::class);
    }

    public static function findOrCreateFromCookie(): static
    {
        $uuid = request()->cookie('guest_uuid');

        if ($uuid) {
            $guest = static::where('uuid', $uuid)->first();

            if ($guest) {
                $guest->last_seen_at = now();
                $guest->save();

                return $guest;
            }
        }

        return static::create([
            'uuid'         => Str::uuid()->toString(),
            'last_seen_at' => now(),
            'created_at'   => now(),
        ]);
    }
}
