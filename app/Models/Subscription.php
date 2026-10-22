<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $guarded = [];

    public const STATUSES = [
        'active' => 'Activa',
        'trial' => 'Experimental',
        'pending' => 'Pendente',
        'expired' => 'Expirada',
        'cancelled' => 'Cancelada',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'expires_at' => 'date',
            'event_limit' => 'integer',
            'participant_limit' => 'integer',
        ];
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function payments()
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /** Event limit for this subscription: admin override first, then the plan. */
    public function effectiveEventLimit(): int
    {
        return (int) ($this->event_limit ?? $this->plan?->event_limit ?? 0);
    }

    /** Participants allowed per event. Null means unlimited. */
    public function effectiveParticipantLimit(): ?int
    {
        return $this->participant_limit ?? $this->plan?->participant_limit;
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->endOfDay()->isPast();
    }

    public function isUsable(): bool
    {
        return in_array($this->status, ['active', 'trial'], true) && ! $this->isExpired();
    }

    public function statusLabel(): string
    {
        if ($this->isExpired() && in_array($this->status, ['active', 'trial'], true)) {
            return self::STATUSES['expired'];
        }

        return self::STATUSES[$this->status] ?? $this->status;
    }
}
