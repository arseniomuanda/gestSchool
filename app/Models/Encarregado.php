<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Encarregado extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'encarregados';

    protected $fillable = [
        'user_id',
        'bi',
        'data_nascimento',
        'sexo',
        'profissao',
        'local_trabalho',
        'morada',
    ];

    protected function casts(): array
    {
        return [
            'data_nascimento' => 'date',
            'bi' => 'encrypted',
            'profissao' => 'encrypted',
            'local_trabalho' => 'encrypted',
            'morada' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function alunos(): BelongsToMany
    {
        return $this->belongsToMany(Aluno::class, 'aluno_encarregado')
            ->withPivot(['parentesco', 'principal'])
            ->withTimestamps();
    }
}
