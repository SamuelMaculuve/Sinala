<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Organization;
use App\Models\Participant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CiesNvdaEventSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::where('name', 'CIES - Centro Informazione e Educazione allo Sviluppo')->firstOrFail();
        $participantNames = [
            'Vino Justino Tune', 'Adérito Bonifácio Chauque', 'Salvador Roda',
            'Quinídio Gune', 'Marina Glória Queixa', 'Percília Delfina Pelembe',
            'Saugineta José Nhantende', 'Chabane Afonso Saide', 'José Chicuamba',
            'Alina Joyce Júlio Pondja', 'Amélia Gabriel', 'Paulo Bernardo Guambe',
            'Dércia Emílio Matsimbe',
        ];

        DB::transaction(function () use ($organization, $participantNames): void {
            $event = Event::firstOrNew([
                'organization_id' => $organization->id,
                'name' => 'Formação em Software Assistivo NVDA - Curso Introdutório',
            ]);

            if (! $event->exists) {
                $event->uuid = (string) Str::uuid();
                $event->public_code = Str::upper(Str::random(10));
            }

            $event->fill([
                'type' => 'training',
                'status' => 'ongoing',
                'location' => 'MDITecHub - CIUEM',
                'starts_on' => '2026-08-17',
                'ends_on' => '2026-08-18',
                'expected_participants' => 13,
                'description' => 'Curso introdutório presencial e essencialmente prático de utilização do computador com o leitor de ecrã NVDA, dirigido exclusivamente a pessoas cegas e pessoas com baixa visão.',
                'notes' => implode("\n\n", [
                    'Duração: 12 horas, distribuídas por 2 dias, com 6 horas de formação por dia.',
                    'Público-alvo e critérios: exclusivamente pessoas cegas e pessoas com baixa visão; interesse em aprender a utilizar o computador com o NVDA; disponibilidade integral nos dois dias; literacia básica suficiente para compreender instruções verbais; prioridade, quando aplicável, a pessoas com necessidade concreta do computador para educação, emprego, comunicação ou acesso a serviços; procurar equilíbrio de género sempre que as candidaturas o permitam.',
                    'Nível: introdutório. Modalidade: presencial, essencialmente prática.',
                    'Indicadores de sucesso: 10 participantes seleccionados; 100% com deficiência visual ou baixa visão; pelo menos 90% de assiduidade; 100% dos participantes elegíveis recebem certificado; pelo menos 85% avaliam positivamente a formação (Net Promoter Score).',
                    'Equipa recomendada: 1 formador e pelo menos 1 assistente.',
                    'Requisitos logísticos garantidos pelo CIES: 1 computador e auscultadores por participante; NVDA instalado; internet estável; Microsoft Word, LibreOffice Writer ou equivalente; extensões e tomadas eléctricas protegidas.',
                    'Espaço físico garantido pelo CIES: sala sem barreiras arquitectónicas; baixo nível de ruído; corredores livres de obstáculos; mesas organizadas de forma previsível; casas de banho acessíveis.',
                    'Materiais acessíveis por participante: guia introdutório do NVDA; lista dos principais comandos; ficheiros de exercícios.',
                ]),
                'requires_check_in' => true,
                'requires_check_out' => false,
                'public_registration_enabled' => false,
            ]);
            $event->save();

            $event->days()->whereNotIn('date', ['2026-08-17', '2026-08-18'])->delete();
            foreach (['2026-08-17', '2026-08-18'] as $date) {
                $event->days()->firstOrCreate(['date' => $date]);
            }

            foreach ($participantNames as $name) {
                $participant = Participant::withTrashed()->firstOrNew([
                    'organization_id' => $organization->id,
                    'full_name' => $name,
                ]);

                if (! $participant->exists) {
                    $participant->uuid = (string) Str::uuid();
                }

                $participant->deleted_at = null;
                $participant->save();
                $event->participants()->syncWithoutDetaching([$participant->id => ['status' => 'pending']]);
            }
        });
    }
}
