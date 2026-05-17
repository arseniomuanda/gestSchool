<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\SmsCreditRequest;
use App\Services\Notifications\OmbalaClient;
use App\Services\Notifications\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class NotificacaoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('role:director_geral')];
    }

    public function index(): View
    {
        $stats = [
            'total' => Notification::count(),
            'sent' => Notification::where('status', 'sent')->count(),
            'failed' => Notification::where('status', 'failed')->count(),
            'last24h' => Notification::where('created_at', '>=', now()->subDay())->count(),
        ];
        return view('notificacoes.index', compact('stats'));
    }

    // ---------- Configurações (read-only — vêm do .env) ----------

    public function settings(): View
    {
        $email = [
            'mailer' => config('mail.default'),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
        ];
        $client = app(OmbalaClient::class);
        $sms = [
            'api_url' => Settings::get('sms.api_url') ?: OmbalaClient::DEFAULT_BASE_URL,
            'sender_id' => Settings::get('sms.sender_id'),
            'enabled' => filter_var(Settings::get('sms.enabled'), FILTER_VALIDATE_BOOL),
            'has_api_key' => $client->isConfigured(),
            'saldo' => $client->credits(),
            'senders_aprovados' => $client->approvedSenders(),
        ];
        return view('notificacoes.settings', compact('email', 'sms'));
    }

    public function sendTestEmail(Request $request): RedirectResponse
    {
        $request->validate(['email' => 'required|email']);
        try {
            Mail::raw(
                "Olá!\n\nEste é um e-mail de teste enviado a partir do gestSchool em ".now()->format('Y-m-d H:i').".\n\nSe estás a ler isto, a configuração SMTP do .env está correcta.",
                fn ($m) => $m->to($request->input('email'))->subject('Teste de configuração — gestSchool')
            );
            return back()->with('status', __('E-mail de teste enviado para :email.', ['email' => $request->input('email')]));
        } catch (\Throwable $e) {
            return back()->with('error', __('Falha ao enviar: :err', ['err' => $e->getMessage()]));
        }
    }

    // ---------- Histórico ----------

    public function historico(Request $request): View
    {
        $q = Notification::query()->latest('created_at');
        if ($status = $request->input('status')) {
            $q->where('status', $status);
        }
        if ($channel = $request->input('channel')) {
            $q->where('channel', $channel);
        }
        if ($event = $request->input('event')) {
            $q->where('event_key', $event);
        }
        $notifications = $q->with('recipient')->paginate(25)->withQueryString();
        return view('notificacoes.historico', compact('notifications'));
    }

    // ---------- Templates ----------

    public function templatesIndex(): View
    {
        $templates = NotificationTemplate::query()->orderBy('event_key')->orderBy('channel')->get();
        return view('notificacoes.templates.index', compact('templates'));
    }

    public function templatesEdit(NotificationTemplate $template): View
    {
        return view('notificacoes.templates.edit', compact('template'));
    }

    public function templatesUpdate(Request $request, NotificationTemplate $template): RedirectResponse
    {
        $data = $request->validate([
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'active' => 'nullable|boolean',
        ]);
        $data['active'] = (bool) ($data['active'] ?? false);
        $template->update($data);
        return redirect()->route('notificacoes.templates.index')->with('status', __('Template actualizado.'));
    }

    // ---------- Pedidos de crédito SMS ----------

    public function smsCreditos(): View
    {
        $requests = SmsCreditRequest::query()
            ->with(['requestedBy', 'processedBy'])
            ->latest()
            ->paginate(20);
        $saldoAtual = app(OmbalaClient::class)->credits();
        return view('notificacoes.sms-creditos', compact('requests', 'saldoAtual'));
    }

    public function smsCreditosStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'quantity_requested' => 'required|integer|min:1|max:1000000',
            'notes' => 'nullable|string|max:1000',
        ]);
        SmsCreditRequest::create([
            'requested_by_user_id' => $request->user()->id,
            'quantity_requested' => $data['quantity_requested'],
            'notes' => $data['notes'] ?? null,
        ]);
        return back()->with('status', __('Pedido registado. Será processado pela Ombala.'));
    }
}
