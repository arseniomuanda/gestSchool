<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matricula extends Model
{
    use HasFactory;

    protected $fillable = [
        'aluno_id', 'turma_id', 'ano_lectivo_id', 'numero_matricula',
        'data_matricula', 'estado', 'observacoes',
        'consentimento_lpd_em', 'consentimento_lpd_versao',
    ];

    protected function casts(): array
    {
        return [
            'data_matricula' => 'date',
            'consentimento_lpd_em' => 'datetime',
            'observacoes' => 'encrypted',
        ];
    }

    public function aluno(): BelongsTo
    {
        return $this->belongsTo(Aluno::class);
    }

    public function turma(): BelongsTo
    {
        return $this->belongsTo(Turma::class);
    }

    public function anoLectivo(): BelongsTo
    {
        return $this->belongsTo(AnoLectivo::class);
    }

    public function presencas(): HasMany
    {
        return $this->hasMany(Presenca::class);
    }

    public function notas(): HasMany
    {
        return $this->hasMany(Nota::class);
    }
}
