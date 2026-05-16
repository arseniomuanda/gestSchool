<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Professor extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'professores';

    protected $fillable = [
        'user_id',
        'numero_professor',
        'bi',
        'data_nascimento',
        'sexo',
        'habilitacoes',
        'especialidade',
        'disciplinas',
        'data_admissao',
        'assistente',
        'morada',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'data_admissao' => 'date',
            'assistente' => 'boolean',
            'bi' => 'encrypted',
            'habilitacoes' => 'encrypted',
            'especialidade' => 'encrypted',
            'morada' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function atribuicoes(): HasMany
    {
        return $this->hasMany(Atribuicao::class);
    }
}
