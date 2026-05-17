# Notificações

> Vista geral do módulo de notificações. Mostra o número de envios totais, bem-sucedidos, falhados e nas últimas 24 horas, com atalhos para Configurações, Templates, Histórico e Créditos SMS.

## O que vês aqui

### Cartões de estatística

- **Total enviado** — todas as notificações com qualquer status (enviada, em fila, falhou) desde o arranque do módulo
- **Bem-sucedidas** — notificações com status `sent` (entregues ao servidor SMTP/SMS)
- **Falharam** — notificações com status `failed` (erro de SMTP, credenciais inválidas, destinatário sem email)
- **Últimas 24h** — envios feitos nas últimas 24 horas (para detectar picos)

### Cartões de atalho

- **Configurações** — credenciais SMTP, remetente, API Ombala e edição dos templates
- **Créditos SMS** — saldo (quando ligado à Ombala) e pedidos de mais créditos

## O que podes fazer

### Configurar email
**Configurações → Email** → preenche `SMTP host`, `porta`, `utilizador`, `password`, `encriptação`, `email do remetente`, `nome do remetente`. Em seguida usa **Enviar email de teste** para confirmar que chega.

### Configurar SMS (futuramente)
**Configurações → SMS** → preenche `URL da API`, `chave API`, `Sender ID` e activa o canal. O canal só funciona quando a especificação ombalaOpenAPI.json estiver integrada na Fase B.

### Editar templates
**Templates** → cada template tem `evento × canal × idioma`. Os placeholders disponíveis são:
- `{{nome_destinatario}}` — nome do User receptor
- `{{titulo}}` — título do comunicado
- `{{mensagem}}` — corpo do comunicado
- `{{aluno}}` — nome do aluno (para boletim e faltas)
- `{{trimestre}}` — número do trimestre
- `{{faltas}}` — número de faltas acumuladas

### Ver histórico
**Histórico** → todas as notificações enviadas, com filtros por canal, status e chave de evento. Útil para auditoria (Lei 22/11).

### Pedir mais créditos SMS
**Créditos SMS → Pedir mais créditos** → indica a quantidade. O pedido é registado e fica `pendente` até ser processado pela Ombala.

## Eventos que disparam notificações

Hoje o sistema dispara automaticamente:

| Evento | Quando | Destinatário |
|---|---|---|
| `comunicado_publicado` | Quando um comunicado é publicado para uma turma/classe/encarregados | Encarregados dos alunos da turma |
| `boletim_fechado` | Quando o Director Pedagógico carrega "Notificar encarregados" na pauta de trimestre | Encarregados dos alunos da turma |
| `faltas_excessivas` | Job diário às 19h detecta alunos com faltas ≥ limite | Encarregados do aluno |

O limite de faltas excessivas é configurável em `config/escola.php` (`faltas_excessivas_limite`, default 10). Há cooldown de 14 dias para não bombardear o mesmo encarregado.

## Conformidade (Lei 22/11)

- Apenas o **Director Geral** acede a este módulo
- Passwords SMTP e chave da API SMS são guardadas **encriptadas em repouso** (Crypt::encryptString)
- O histórico guarda apenas a primeira linha do corpo (`body_preview`, 200 caracteres) — não a mensagem completa
- O destinatário é referenciado por `user_id` quando disponível, com o endereço (email/telefone) também guardado para auditoria

## Páginas relacionadas

- [Comunicados](/comunicados)
- [Pautas](/pautas)
- [Política de Privacidade](/privacidade)
