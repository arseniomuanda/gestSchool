# Confirmar palavra-passe

> Estás a entrar numa **zona segura** do sistema. Para continuar precisas de digitar a tua palavra-passe novamente, mesmo já tendo sessão iniciada.

## Porquê isto

Algumas operações são sensíveis e não basta a sessão activa:

- Eliminar a tua própria conta
- Alterar o e-mail principal
- Aceder a relatórios de auditoria sensíveis (futuro)
- Operações financeiras (futuro)

Esta etapa protege contra alguém que **se senta no teu computador** enquanto estás afastado — sem a palavra-passe não pode executar a acção que o sistema considera crítica.

## Como funciona

1. Digita a tua palavra-passe actual
2. Carrega em **Confirmar**
3. Voltas automaticamente para a página onde estavas, com a acção sensível agora permitida durante alguns minutos

## Notas

- A confirmação dura ~3 horas (configurável em `config/auth.php` → `password_timeout`). Após isto, qualquer nova acção sensível pede confirmação outra vez.
- Se esqueceste a palavra-passe e precisas de a recuperar, sai e usa [Recuperar palavra-passe](/forgot-password).

## Páginas relacionadas

- [Perfil](/profile) — onde estão as operações sensíveis principais
- [Política de Privacidade](/privacidade) — sobre protecção de dados
