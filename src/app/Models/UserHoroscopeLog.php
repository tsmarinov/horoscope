<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserHoroscopeLog extends Model
{
    protected $table = 'user_horoscope_logs';

    protected $fillable = [
        'user_id',
        'user_uuid',
        'user_email',
        'profile_uuid',
        'profile_snapshot',
        'type',
        'premium_content',
        'premium_requested_at',
    ];

    protected function casts(): array
    {
        return [
            'profile_snapshot'     => 'array',
            'premium_content'      => 'boolean',
            'premium_requested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Build a snapshot of the profile at the time of generation.
     */
    public static function snapshotProfile(Profile $profile): array
    {
        return [
            'name'       => $profile->name,
            'gender'     => $profile->gender instanceof \App\Enums\Gender ? $profile->gender->value : $profile->gender,
            'birth_date' => $profile->birth_date?->format('Y-m-d'),
            'birth_time' => $profile->birth_time ? substr($profile->birth_time, 0, 5) : null,
            'birth_city' => $profile->birthCity?->name . ($profile->birthCity?->country_code ? ' (' . $profile->birthCity->country_code . ')' : ''),
        ];
    }
}
