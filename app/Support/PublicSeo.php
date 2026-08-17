<?php

namespace App\Support;

final class PublicSeo
{
    public static function structuredData(): array
    {
        $home = url('/');
        $organizationId = $home.'#organization';

        return [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'Organization',
                    '@id' => $organizationId,
                    'name' => 'Sinala',
                    'url' => $home,
                    'logo' => asset('brand/sinala-logo-transparent.png'),
                    'description' => 'Plataforma moçambicana de gestão de presenças, assinaturas digitais e pagamentos para eventos e formações.',
                ],
                [
                    '@type' => 'WebSite',
                    '@id' => $home.'#website',
                    'url' => $home,
                    'name' => 'Sinala',
                    'alternateName' => 'Sinala Moçambique',
                    'inLanguage' => 'pt-MZ',
                    'publisher' => ['@id' => $organizationId],
                ],
                [
                    '@type' => 'WebApplication',
                    '@id' => $home.'#software',
                    'name' => 'Sinala',
                    'url' => $home,
                    'applicationCategory' => 'BusinessApplication',
                    'operatingSystem' => 'Qualquer dispositivo com navegador moderno',
                    'inLanguage' => 'pt-MZ',
                    'description' => 'Aplicação SaaS para criar eventos, gerir participantes, recolher assinaturas digitais e confirmar pagamentos e subsídios.',
                    'audience' => [
                        '@type' => 'Audience',
                        'audienceType' => 'ONGs, associações, instituições, projectos sociais e empresas de consultoria',
                    ],
                    'offers' => [
                        ['@type' => 'Offer', 'name' => 'Plano Free', 'price' => '0', 'priceCurrency' => 'MZN'],
                        ['@type' => 'Offer', 'name' => 'Plano Profissional', 'price' => '3500', 'priceCurrency' => 'MZN'],
                        ['@type' => 'Offer', 'name' => 'Plano Organização', 'price' => '7500', 'priceCurrency' => 'MZN'],
                    ],
                ],
                [
                    '@type' => 'FAQPage',
                    '@id' => $home.'#faq-schema',
                    'mainEntity' => [
                        self::question('Preciso de instalar alguma aplicação?', 'Não. O Sinala funciona directamente no navegador do tablet ou computador.'),
                        self::question('O que acontece se a internet falhar?', 'Os registos podem permanecer pendentes no dispositivo e ser sincronizados quando a ligação regressar.'),
                        self::question('As assinaturas ficam seguras?', 'Cada assinatura fica associada ao participante, evento, data e hora, com controlo de acesso e integridade.'),
                        self::question('Posso usar o logótipo da minha organização?', 'Sim. O logótipo e os dados da organização podem aparecer nos relatórios exportados.'),
                    ],
                ],
            ],
        ];
    }

    private static function question(string $question, string $answer): array
    {
        return [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $answer],
        ];
    }
}
