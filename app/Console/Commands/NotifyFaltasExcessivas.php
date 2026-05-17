<?php

namespace App\Console\Commands;

use App\Models\AnoLectivo;
use App\Models\Encarregado;
use App\Models\Matricula;
use App\Models\Notification;
use App\Models\Presenca;
use App\Services\Notifications\NotificationSender;
use Illuminate\Console\Command;

/**
 * Detecta alunos com faltas acumuladas acima do limite e notifica
 * o(s) encarregado(s) por email. Respeita um cooldown para não
 * bombardear o mesmo encarregado todos os dias.
 *
 * Correr periodicamente — recomendado: diariamente às 19h.
 *   php artisan notifications:faltas-excessivas
 */
class NotifyFaltasExcessivas extends Command
{
    protected $signature = 'notifications:faltas-excessivas
                            {--dry : Não envia, apenas lista os candidatos}
                            {--limite= : Sobrepõe o limite definido em config(escola.faltas_excessivas_limite)}';

    protected $description = 'Notifica encarregados de alunos com faltas excessivas';

    public function handle(NotificationSender $sender): int
    {
        $limite = (int) ($this->option('limite') ?? config('escola.faltas_excessivas_limite', 10));
        $cooldown = (int) config('escola.faltas_excessivas_cooldown_dias', 14);
        $dry = (bool) $this->option('dry');

        $ano = AnoLectivo::query()->where('activo', true)->first();
        if (! $ano) {
            $this->error('Sem ano lectivo activo.');
            return self::FAILURE;
        }

        // Conta faltas por matrícula activa
        $faltasPorMatricula = Presenca::query()
            ->whereIn('estado', ['falta', 'falta_justificada'])
            ->whereHas('matricula', fn ($q) => $q->where('ano_lectivo_id', $ano->id)->where('estado', 'activa'))
            ->selectRaw('matricula_id, COUNT(*) AS total')
            ->groupBy('matricula_id')
            ->havingRaw('COUNT(*) >= ?', [$limite])
            ->pluck('total', 'matricula_id');

        $this->info("Matrículas acima do limite ({$limite}): {$faltasPorMatricula->count()}");

        $enviadas = 0;
        $saltadas = 0;

        foreach ($faltasPorMatricula as $matriculaId => $totalFaltas) {
            $matricula = Matricula::with('aluno.user')->find($matriculaId);
            if (! $matricula || ! $matricula->aluno) continue;

            $encarregadoUsers = Encarregado::query()
                ->whereHas('alunos', fn ($q) => $q->whereKey($matricula->aluno_id))
                ->with('user')
                ->get()
                ->pluck('user')
                ->filter();
            if ($encarregadoUsers->isEmpty()) {
                $saltadas++;
                continue;
            }

            // Cooldown: já notificámos este aluno recentemente?
            $jaNotificadoRecentemente = Notification::query()
                ->where('event_key', 'faltas_excessivas')
                ->where('status', 'sent')
                ->whereIn('recipient_user_id', $encarregadoUsers->pluck('id'))
                ->whereJsonContains('payload->matricula_id', (int) $matriculaId)
                ->where('sent_at', '>=', now()->subDays($cooldown))
                ->exists();

            if ($jaNotificadoRecentemente) {
                $saltadas++;
                continue;
            }

            $payload = [
                'aluno' => $matricula->aluno->user->name ?? '—',
                'faltas' => (int) $totalFaltas,
                'matricula_id' => (int) $matriculaId,
                'limite' => $limite,
            ];

            if ($dry) {
                $this->line("[DRY] {$payload['aluno']} — {$totalFaltas} faltas → ".$encarregadoUsers->count().' encarregado(s)');
                continue;
            }

            $result = $sender->dispatch(
                eventKey: 'faltas_excessivas',
                recipients: $encarregadoUsers,
                channels: ['email'],
                payload: $payload,
            );
            $enviadas += $result['sent'] ?? 0;
        }

        $this->info("Notificações enviadas: {$enviadas}");
        $this->info("Saltadas (cooldown ou sem encarregado): {$saltadas}");
        return self::SUCCESS;
    }
}
