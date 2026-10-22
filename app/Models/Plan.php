<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $guarded = [];

    public const FEATURES = [
        'attendance' => 'Lista de presença',
        'signatures' => 'Assinaturas digitais',
        'pdf' => 'Exportação PDF',
        'excel' => 'Exportação Excel',
        'payments' => 'Pagamentos/subsídios',
        'check_out' => 'Check-in e check-out',
        'multi_day' => 'Múltiplos dias',
        'qr_code' => 'QR Code',
        'imports' => 'Importação Excel/CSV',
        'advanced_reports' => 'Relatórios avançados',
        'projects' => 'Gestão de projectos',
        'donors' => 'Gestão de financiadores',
        'audit' => 'Histórico de auditoria',
        'custom_documents' => 'Documentos personalizados',
        'priority_support' => 'Suporte prioritário',
    ];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'monthly_event_limit' => 'boolean',
            'active' => 'boolean',
            'is_public' => 'boolean',
            'participant_limit' => 'integer',
            'duration_days' => 'integer',
        ];
    }

    public function subscriptions()
    {
        return $this->hasMany(Subscription::class);
    }

    public function isFree(): bool
    {
        return (int) $this->price_mzn === 0;
    }

    public function scopePurchasable($query)
    {
        return $query->where('active', true)->where('is_public', true)->orderBy('sort_order')->orderBy('price_mzn');
    }

    public function durationLabel(): string
    {
        if (! $this->duration_days) {
            return 'sem expiração';
        }

        return match ($this->duration_days) {
            30, 31 => '1 mês',
            90 => '3 meses',
            180 => '6 meses',
            365 => '1 ano',
            default => $this->duration_days.' dias',
        };
    }
}
