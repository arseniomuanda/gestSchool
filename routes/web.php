<?php

use App\Http\Controllers\AlunoController;
use App\Http\Controllers\AnoLectivoController;
use App\Http\Controllers\AtribuicaoController;
use App\Http\Controllers\AulaController;
use App\Http\Controllers\AvaliacaoController;
use App\Http\Controllers\BoletimController;
use App\Http\Controllers\ClasseController;
use App\Http\Controllers\ComunicadoController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DisciplinaController;
use App\Http\Controllers\EncarregadoController;
use App\Http\Controllers\EventoController;
use App\Http\Controllers\FuncionarioController;
use App\Http\Controllers\HorarioController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MatriculaController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\PautaController;
use App\Http\Controllers\PresencaController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\TrimestreController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

// Política de Privacidade — pública (Lei 22/11)
Route::get('/privacidade', function () {
    return view('legal.privacidade', [
        'versao' => config('legal.lpd_versao'),
        'dataAtualizacao' => now()->format('Y-m-d'),
        'instituicao' => config('legal.instituicao'),
        'dpo' => config('legal.dpo'),
        'apd' => config('legal.apd'),
        'retencaoAnos' => config('legal.retencao_anos'),
    ]);
})->name('legal.privacidade');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/search', [SearchController::class, 'index'])->name('search.index');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('role:director_geral|director_pedagogico|secretario')->group(function () {
        Route::resource('users', UserController::class);
        Route::resource('funcionarios', FuncionarioController::class)->parameters(['funcionarios' => 'funcionario']);
        Route::resource('professores', ProfessorController::class)->parameters(['professores' => 'professor']);
        Route::resource('alunos', AlunoController::class)->parameters(['alunos' => 'aluno']);
        Route::get('encarregados/search', [EncarregadoController::class, 'search'])->name('encarregados.search');
        Route::resource('encarregados', EncarregadoController::class)->parameters(['encarregados' => 'encarregado']);

        Route::resource('anos', AnoLectivoController::class)->parameters(['anos' => 'ano']);
        Route::resource('classes', ClasseController::class)->parameters(['classes' => 'classe']);
        Route::resource('cursos', CursoController::class)->parameters(['cursos' => 'curso']);
        Route::resource('turmas', TurmaController::class)->parameters(['turmas' => 'turma']);
        Route::resource('disciplinas', DisciplinaController::class)->parameters(['disciplinas' => 'disciplina']);
        Route::resource('matriculas', MatriculaController::class)->parameters(['matriculas' => 'matricula']);
        Route::resource('atribuicoes', AtribuicaoController::class)->parameters(['atribuicoes' => 'atribuicao']);
        Route::resource('trimestres', TrimestreController::class)->parameters(['trimestres' => 'trimestre']);
    });

    // Acesso para direcção + professores
    Route::middleware('role:director_geral|director_pedagogico|secretario|professor|professor_assistente')->group(function () {
        Route::resource('aulas', AulaController::class)->parameters(['aulas' => 'aula']);
        Route::get('/aulas/{aula}/presencas', [PresencaController::class, 'folha'])->name('presencas.folha');
        Route::post('/aulas/{aula}/presencas', [PresencaController::class, 'gravar'])->name('presencas.gravar');
        Route::get('/presencas', [PresencaController::class, 'index'])->name('presencas.index');

        Route::resource('avaliacoes', AvaliacaoController::class)->parameters(['avaliacoes' => 'avaliacao']);

        Route::get('/notas/{avaliacao}/folha', [NotaController::class, 'folha'])->name('notas.folha');
        Route::post('/notas/{avaliacao}/gravar', [NotaController::class, 'gravar'])->name('notas.gravar');

        Route::get('/pautas', [PautaController::class, 'index'])->name('pautas.index');
        Route::get('/pautas/disciplina/{atribuicao}/{trimestre}', [PautaController::class, 'disciplina'])->name('pautas.disciplina');
        Route::get('/pautas/turma/{turma}/trimestre/{trimestre}', [PautaController::class, 'turmaTrimestre'])->name('pautas.turma-trimestre');
        Route::get('/pautas/turma/{turma}/anual', [PautaController::class, 'turmaAnual'])->name('pautas.turma-anual');
        Route::get('/pautas/turma/{turma}/situacao', [PautaController::class, 'situacao'])->name('pautas.situacao');

        // PDFs
        Route::get('/pautas/disciplina/{atribuicao}/{trimestre}/pdf', [PautaController::class, 'disciplinaPdf'])->name('pautas.disciplina.pdf');
        Route::get('/pautas/turma/{turma}/trimestre/{trimestre}/pdf', [PautaController::class, 'turmaTrimestrePdf'])->name('pautas.turma-trimestre.pdf');
        Route::get('/pautas/turma/{turma}/anual/pdf', [PautaController::class, 'turmaAnualPdf'])->name('pautas.turma-anual.pdf');
        Route::get('/pautas/turma/{turma}/situacao/pdf', [PautaController::class, 'situacaoPdf'])->name('pautas.situacao.pdf');

        Route::get('/pautas/{atribuicao}/{trimestre}', [PautaController::class, 'disciplina'])->name('pautas.show');

        // Horários — consulta para todos os roles de operação, CRUD restrito a direcção
        Route::get('/horarios', [HorarioController::class, 'index'])->name('horarios.index');
        Route::get('/horarios/turma/{turma}', [HorarioController::class, 'turma'])->name('horarios.turma');
        Route::get('/horarios/turma/{turma}/pdf', [HorarioController::class, 'turmaPdf'])->name('horarios.turma.pdf');
        Route::get('/horarios/professor/{professor}', [HorarioController::class, 'professor'])->name('horarios.professor');
        Route::get('/horarios/professor/{professor}/pdf', [HorarioController::class, 'professorPdf'])->name('horarios.professor.pdf');
    });

    // CRUD horários só para direcção
    Route::middleware('role:director_geral|director_pedagogico|secretario')->group(function () {
        Route::get('/horarios/create', [HorarioController::class, 'create'])->name('horarios.create');
        Route::post('/horarios', [HorarioController::class, 'store'])->name('horarios.store');
        Route::get('/horarios/turma/{turma}/bulk', [HorarioController::class, 'bulkTurma'])->name('horarios.bulk-turma');
        Route::post('/horarios/turma/{turma}/bulk', [HorarioController::class, 'bulkTurmaStore'])->name('horarios.bulk-turma.store');
        Route::post('/horarios/turma/{turma}/auto-generate', [HorarioController::class, 'autoGenerate'])->name('horarios.auto-generate');
        Route::post('/horarios/turma/{turma}/auto-generate-ai', [HorarioController::class, 'autoGenerateAi'])->name('horarios.auto-generate-ai');
        Route::get('/horarios/professor/{professor}/bulk', [HorarioController::class, 'bulkProfessor'])->name('horarios.bulk-professor');
        Route::post('/horarios/professor/{professor}/bulk', [HorarioController::class, 'bulkProfessorStore'])->name('horarios.bulk-professor.store');
        Route::get('/horarios/{horario}/edit', [HorarioController::class, 'edit'])->name('horarios.edit');
        Route::put('/horarios/{horario}', [HorarioController::class, 'update'])->name('horarios.update');
        Route::delete('/horarios/{horario}', [HorarioController::class, 'destroy'])->name('horarios.destroy');
    });

    // Calendário escolar — todos os autenticados visualizam
    Route::get('/eventos', [EventoController::class, 'index'])->name('eventos.index');
    Route::get('/eventos/pdf', [EventoController::class, 'pdf'])->name('eventos.pdf');
    Route::get('/eventos/{evento}', [EventoController::class, 'show'])->name('eventos.show');

    // Gestão de eventos só para direcção
    Route::middleware('role:director_geral|director_pedagogico|secretario')->group(function () {
        Route::get('/eventos/create/new', [EventoController::class, 'create'])->name('eventos.create');
        Route::post('/eventos', [EventoController::class, 'store'])->name('eventos.store');
        Route::get('/eventos/{evento}/edit', [EventoController::class, 'edit'])->name('eventos.edit');
        Route::put('/eventos/{evento}', [EventoController::class, 'update'])->name('eventos.update');
        Route::delete('/eventos/{evento}', [EventoController::class, 'destroy'])->name('eventos.destroy');
    });

    // Boletim acessível a direcção, professores e encarregado do aluno
    Route::get('/boletim/{matricula}', [BoletimController::class, 'show'])->name('boletim.show');
    Route::get('/boletim/{matricula}/pdf', [BoletimController::class, 'pdf'])->name('boletim.pdf');

    Route::resource('comunicados', ComunicadoController::class)->parameters(['comunicados' => 'comunicado']);

    Route::middleware('role:professor|professor_assistente')->group(function () {
        Route::get('/meus-alunos', [AlunoController::class, 'index'])->name('meus-alunos.index');
    });

    Route::middleware('role:encarregado')->group(function () {
        Route::get('/meus-educandos', function (\App\Services\BoletimService $boletimService) {
            $encarregado = auth()->user()->encarregado()
                ->with([
                    'alunos.user',
                    'alunos.matriculas.turma.classe',
                    'alunos.matriculas.turma.curso',
                    'alunos.matriculas.anoLectivo',
                ])
                ->first();

            $alunos = $encarregado?->alunos ?? collect();

            $resumosActivos = [];
            foreach ($alunos as $aluno) {
                $activa = $aluno->matriculas->firstWhere('estado', 'activa');
                if ($activa) {
                    $resumosActivos[$aluno->id] = [
                        'matricula' => $activa,
                        'summary' => $boletimService->quickSummary($activa),
                    ];
                }
            }

            return view('encarregado.meus-educandos', compact('alunos', 'resumosActivos'));
        })->name('meus-educandos.index');

        Route::get('/meus-educandos/{aluno}', function (\App\Models\Aluno $aluno, \App\Services\BoletimService $boletimService) {
            $encarregado = auth()->user()->encarregado;
            abort_unless($encarregado && $encarregado->alunos()->whereKey($aluno->id)->exists(), 403);

            $aluno->load([
                'user',
                'encarregados.user',
                'matriculas' => fn ($q) => $q->orderByDesc('ano_lectivo_id'),
                'matriculas.turma.classe',
                'matriculas.turma.curso',
                'matriculas.anoLectivo',
            ]);

            $resumos = [];
            foreach ($aluno->matriculas as $m) {
                $resumos[$m->id] = $boletimService->quickSummary($m);
            }

            $matriculaActiva = $aluno->matriculas->firstWhere('estado', 'activa');

            return view('encarregado.aluno-perfil', compact('aluno', 'resumos', 'matriculaActiva'));
        })->name('meus-educandos.show');
    });
});

require __DIR__.'/auth.php';
