# Configurações de notificações

> Estado das credenciais de envio. Os valores vêm do ficheiro `.env` no servidor — para alterar tens de editar o ficheiro e reiniciar o php-fpm. Esta página é apenas leitura.

## Email (SMTP)

A app usa a configuração padrão do Laravel (`config/mail.php`), que por sua vez lê do `.env`. As variáveis relevantes:

| Variável | Para que serve |
|---|---|
| `MAIL_MAILER` | Driver: `smtp` em produção, `log` em dev (escreve no `storage/logs/laravel.log` em vez de enviar) |
| `MAIL_HOST` | Servidor SMTP — ex: `smtp.gmail.com`, `smtp-mail.outlook.com` |
| `MAIL_PORT` | `587` para TLS, `465` para SSL |
| `MAIL_USERNAME` | Conta SMTP — normalmente o email completo |
| `MAIL_PASSWORD` | Password / app password do provedor |
| `MAIL_FROM_ADDRESS` | Remetente visível ao destinatário |
| `MAIL_FROM_NAME` | Nome do remetente (cabeçalho From) |

### Testar
Botão **Enviar email de teste** — envia um email com a tua conta como destinatário e mostra na sessão `status` se foi aceite ou `error` com a causa.

> Para Gmail, gera uma [app password](https://support.google.com/accounts/answer/185833) — a password normal não funciona com 2FA.

## SMS (Ombala)

> **Estado actual:** o canal SMS está em **modo stub**. Podes preencher já as variáveis no `.env`; envios reais só funcionam quando a integração com a API Ombala estiver concluída (aguarda `ombalaOpenAPI.json`).

| Variável | Notas |
|---|---|
| `OMBALA_ENABLED` | `true` para tentar enviar, `false` para saltar SMS silenciosamente |
| `OMBALA_API_URL` | URL base da API (a confirmar quando o spec chegar) |
| `OMBALA_API_KEY` | Chave secreta — fica apenas no `.env`, nunca na DB |
| `OMBALA_SENDER_ID` | Texto curto que aparece como remetente no telemóvel |

## Porquê ler do `.env` em vez da UI?

- **Mais simples** — uma única fonte da verdade
- **Mais seguro** — passwords e API keys não saem do servidor, não passam pela DB nem aparecem em backups da BD
- **Mais consistente** com o resto do Laravel: a config viva do framework já é lida do `.env` para todos os outros serviços

Se precisares de override por ambiente (staging vs produção), basta ter `.env` diferente em cada máquina.

## Páginas relacionadas

- [Visão geral de Notificações](/help/notificacoes.index)
- [Política de Privacidade](/privacidade)
