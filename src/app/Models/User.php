<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'pending_email',
        'password',
        'accepts_marketing',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'  => 'datetime',
            'email_confirmed_at' => 'datetime',
            'password'           => 'hashed',
            'accepts_marketing'  => 'boolean',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(Profile::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function premiumUsage(): HasMany
    {
        return $this->hasMany(PremiumUsage::class);
    }

    /** True if the user has an active subscription and premium is globally enabled. */
    public function isPremium(): bool
    {
        if (! config('premium.enabled')) {
            return false;
        }

        return $this->subscriptions()
            ->where('starts_at', '<=', now())
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }

    /** How many premium requests this user has used in the current calendar month. */
    public function premiumUsageThisMonth(): int
    {
        $row = $this->premiumUsage()
            ->where('year', now()->year)
            ->where('month', now()->month)
            ->first();

        return $row?->count ?? 0;
    }

    /** Remaining premium requests this month. */
    public function premiumRemaining(): int
    {
        return max(0, config('premium.monthly_limit') - $this->premiumUsageThisMonth());
    }

    /** True if the user can make another premium request right now. */
    public function canUsePremium(): bool
    {
        return $this->isPremium() && $this->premiumRemaining() > 0;
    }

    /** Increment the monthly premium usage counter. */
    public function incrementPremiumUsage(): void
    {
        $this->premiumUsage()
            ->firstOrCreate(
                ['year' => now()->year, 'month' => now()->month],
                ['count' => 0],
            )
            ->increment('count');
    }
}
