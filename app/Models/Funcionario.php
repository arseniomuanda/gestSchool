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
        'bi',
        'data_nascimento',
        'sexo',
        'cargo',
        'departamento',
        'data_admissao',
        'morada',
    ];

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
