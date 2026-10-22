<?php

namespace App\Support;

use App\Models\Participant;

/** Participant columns an organization can choose to show on its PDF reports. */
class ReportColumns
{
    public const OPTIONS = [
        'sex' => 'Género',
        'company' => 'Instituição',
        'position' => 'Cargo',
        'phone' => 'Contacto',
        'email' => 'E-mail',
        'province' => 'Província',
        'district' => 'Distrito',
        'document' => 'Documento',
        'birth_date' => 'Data de nascimento',
    ];

    public const REPORTS = [
        'attendance' => 'Lista de presença',
        'payment' => 'Listas de pagamento',
    ];

    /** Same columns the PDFs showed before this became configurable. */
    public const DEFAULTS = [
        'attendance' => ['sex', 'company', 'phone'],
        'payment' => ['company', 'phone'],
    ];

    private const SENSITIVE = ['phone', 'email', 'document', 'birth_date'];

    /** @return array<string,string> selected key => label, in the canonical order */
    public static function for(array $settings, string $report): array
    {
        $selected = $settings['report_columns'][$report] ?? self::DEFAULTS[$report];

        return array_intersect_key(self::OPTIONS, array_flip($selected));
    }

    public static function value(Participant $participant, string $column, bool $mask = false): string
    {
        $value = match ($column) {
            'sex' => ['female' => 'Feminino', 'male' => 'Masculino', 'other' => 'Outro'][$participant->sex] ?? null,
            'document' => trim(($participant->document_type ? $participant->document_type.' ' : '').$participant->document_number) ?: null,
            'birth_date' => $participant->birth_date?->format('d/m/Y'),
            default => $participant->{$column},
        };

        if ($value !== null && $mask && in_array($column, self::SENSITIVE, true)) {
            $value = match ($column) {
                'phone' => Mask::phone($value),
                'email' => Mask::email($value),
                default => Mask::text($value),
            };
        }

        return ($value === null || $value === '') ? '—' : (string) $value;
    }
}
