<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Funcionario extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'funcionarios';

    protected $fillable = [
        'user_id',
        'numero_funcionario',
        'categoria',
        'funcao',
        'bi',
        'data_nascimento',
        'sexo',
        'cargo',
        'departamento',
        'data_admissao',
        'morada',
    ];

    /** Lista dos valores possíveis (mantém sincronizado com a migration). */
    public const CATEGORIAS = ['administrativo', 'auxiliar'];
    public const FUNCOES_AUXILIAR = ['limpeza', 'seguranca', 'motorista', 'jardinagem', 'cantina', 'outro'];

    public function scopeAdministrativos($query)
    {
        return $query->where('categoria', 'administrativo');
    }

    public function scopeAuxiliares($query)
    {
        return $query->where('categoria', 'auxiliar');
    }

    public function getIsAuxiliarAttribute(): bool
    {
        return $this->categoria === 'auxiliar';
    }

    public function getIsAdministrativoAttribute(): bool
    {
        return $this->categoria === 'administrativo';
    }

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'data_admissao' => 'date',
            'bi' => 'encrypted',
            'cargo' => 'encrypted',
            'departamento' => 'encrypted',
            'morada' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
