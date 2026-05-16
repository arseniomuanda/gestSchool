<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aluno extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'alunos';

    protected $fillable = [
        'user_id',
        'numero_processo',
        'bi',
        'data_nascimento',
        'sexo',
        'classe',
        'turma',
        'ano_lectivo',
        'nacionalidade',
        'naturalidade',
        'morada',
        'observacoes',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            // PII encriptada em repouso (Lei 22/11). Trade-off: estes campos
            // deixam de suportar WHERE/ORDER BY/LIKE — escolhidos só os que
            // nunca são alvo de queries directas.
            'bi' => 'encrypted',
            'naturalidade' => 'encrypted',
            'morada' => 'encrypted',
            'observacoes' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function encarregados(): BelongsToMany
    {
        return $this->belongsToMany(Encarregado::class, 'aluno_encarregado')
            ->withPivot(['parentesco', 'principal'])
            ->withTimestamps();
    }

    public function matriculas(): HasMany
    {
        return $this->hasMany(Matricula::class);
    }

    public function matriculaActiva()
    {
        $anoActivo = AnoLectivo::activo();
        return $this->matriculas()
            ->when($anoActivo, fn ($q) => $q->where('ano_lectivo_id', $anoActivo->id))
            ->where('estado', 'activa')
            ->first();
    }
}
