<x-mail::message>
# Você foi convidado!

Olá, **{{ $tenantUser->name }}**!

Você foi convidado para acessar o portal da empresa **{{ $tenant->name }}** na plataforma **Payproxy** com o perfil de **{{ match($tenantUser->role) { 'admin' => 'Administrador', 'operator' => 'Operador', default => 'Visualizador' } }}**.

Para ativar sua conta e definir sua senha, clique no botão abaixo:

<x-mail::button :url="route('portal.invite.show', $rawToken)" color="primary">
Aceitar Convite
</x-mail::button>

**Este link é válido por 48 horas.**

Após aceitar, você criará sua senha e poderá acessar o portal imediatamente.

---

Se você não esperava este convite, pode ignorar este e-mail com segurança. Nenhuma ação será tomada.

Atenciosamente,
**Equipe Payproxy — Ciberian**
</x-mail::message>
