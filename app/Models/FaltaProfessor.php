<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FaltaProfessor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'faltas_professores';

    protected $fillable = [
        'professor_id',
        'data',
        'tempo_inicio',
        'tempo_fim',
        'tipo',
        'motivo',
        'substituto_id',
        'registado_por_id',
        'justificacao_em',
        'documento_url',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'date',
            'justificacao_em' => 'datetime',
            // PII potencial (pode mencionar saúde, situação familiar)
            'motivo' => 'encrypted',
        ];
    }

    public function professor(): BelongsTo
    {
        return $this->belongsTo(Professor::class);
    }

    public function substituto(): BelongsTo
    {
        return $this->belongsTo(Professor::class, 'substituto_id');
    }

    public function registadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registado_por_id');
    }

    /** Nº de tempos abrangidos pela falta (inclusivo). */
    public function getDuracaoTemposAttribute(): int
    {
        return max(0, ((int) $this->tempo_fim) - ((int) $this->tempo_inicio) + 1);
    }

    /** true se a falta tem registo de justificação aceite. */
    public function getJustificadaAttribute(): bool
    {
        return $this->tipo === 'justificada' && $this->justificacao_em !== null;
    }
}
