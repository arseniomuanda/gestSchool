<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplatesSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            // Comunicado publicado para uma turma
            [
                'event_key' => 'comunicado_publicado',
                'channel' => 'email',
                'locale' => 'pt',
                'subject' => '[gestSchool] Novo comunicado: {{titulo}}',
                'body' => "Caro(a) {{nome_destinatario}},\n\nA escola publicou um novo comunicado:\n\n{{titulo}}\n\n{{mensagem}}\n\nPode consultar todos os comunicados no portal do encarregado.\n\nCumprimentos,\ngestSchool",
            ],
            [
                'event_key' => 'comunicado_publicado',
                'channel' => 'sms',
                'locale' => 'pt',
                'subject' => null,
                'body' => 'gestSchool: novo comunicado "{{titulo}}". Consulte no portal.',
            ],
            // Boletim trimestral fechado
            [
                'event_key' => 'boletim_fechado',
                'channel' => 'email',
                'locale' => 'pt',
                'subject' => '[gestSchool] Boletim trimestral disponível — {{aluno}}',
                'body' => "Caro(a) {{nome_destinatario}},\n\nO boletim do {{trimestre}} trimestre do(a) seu(sua) educando(a) {{aluno}} já está disponível para consulta no portal.\n\nCumprimentos,\ngestSchool",
            ],
            [
                'event_key' => 'boletim_fechado',
                'channel' => 'sms',
                'locale' => 'pt',
                'subject' => null,
                'body' => 'gestSchool: boletim do {{trimestre}}º trim. de {{aluno}} disponível no portal.',
            ],
            // Faltas excessivas
            [
                'event_key' => 'faltas_excessivas',
                'channel' => 'email',
                'locale' => 'pt',
                'subject' => '[gestSchool] Faltas elevadas — {{aluno}}',
                'body' => "Caro(a) {{nome_destinatario}},\n\nInformamos que o(a) seu(sua) educando(a) {{aluno}} acumulou {{faltas}} faltas, o que ultrapassa o limite recomendado.\n\nSolicitamos que entre em contacto com a escola.\n\nCumprimentos,\ngestSchool",
            ],
            [
                'event_key' => 'faltas_excessivas',
                'channel' => 'sms',
                'locale' => 'pt',
                'subject' => null,
                'body' => 'gestSchool: {{aluno}} acumula {{faltas}} faltas. Contacte a escola.',
            ],
        ];

        foreach ($templates as $t) {
            NotificationTemplate::updateOrCreate(
                ['event_key' => $t['event_key'], 'channel' => $t['channel'], 'locale' => $t['locale']],
                array_merge($t, ['active' => true]),
            );
        }
    }
}
