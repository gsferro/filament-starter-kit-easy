# Requisito — Auth Designer nas telas que ficaram de fora

## Fonte

- **Origem**: `.claude/requisitos/w4-auth-designer-telas.txt` (texto do usuário, colado pelo agente coordenador)
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> Quando o usuário ativa o 2FA no perfil dele, a tela de exibição para inserir o codigo, não esta usando o pacote de auth designer
> - adicione o auth design na tela de register do lado inverso ao de login (como esta o esqueci a senha)
> - adicione também na tela de confirmação do email (não é usada como default, mas caso use, já esta implemetado o auth designer nela também)

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A tela onde se digita o código do 2FA (o desafio pós-login) precisa usar o layout do Auth Designer, em vez do layout simples do Filament. | "a tela de exibição para inserir o codigo, não esta usando o pacote de auth designer" | funcional |
| RQ-02 | A tela de `register` precisa exibir a mídia do Auth Designer no lado **inverso** ao do login. | "adicione o auth design na tela de register do lado inverso ao de login" | funcional |
| RQ-03 | O "inverso" é o mesmo eixo que a tela de recuperação de senha já usa — ela é a referência declarada. | "(como esta o esqueci a senha)" | restrição |
| RQ-04 | A tela de confirmação de e-mail precisa ter o Auth Designer aplicado. | "adicione também na tela de confirmação do email" | funcional |
| RQ-05 | A confirmação de e-mail **não** passa a ser usada como default; a entrega deixa a tela pronta para quando alguém ligar o recurso. | "(não é usada como default, mas caso use, já esta implemetado o auth designer nela também)" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-04 × RQ-05 — como a tela pode estar vestida sem estar em uso?**
  Para a rota da tela existir, o painel precisa de `Panel::emailVerification()`, e a assinatura
  do Filament é `emailVerification(string|Closure|array|null $promptAction = EmailVerificationPrompt::class, bool|Closure $isRequired = true)`
  (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:110`). O `AuthDesignerPlugin`
  chama esse método com **um argumento só**
  (`vendor/caresome/filament-auth-designer/src/AuthDesignerPlugin.php:45-47`), então passar
  pelo plugin liga `isRequired = true` de tabela.
  - **Assumido** (revisado durante a implementação, por medição): registrar a **configuração**
    do Auth Designer para a chave `email-verification` pelo plugin, e **apagar a ação da rota**
    com `->emailVerification(null, isRequired: false)` depois do `->plugins([...])`. A tela fica
    vestida — mídia, eixo espelhado e alternador de tema no repositório de config — e nenhuma
    rota entra no ar, que é literalmente o "não é usada como default" do RQ-05.
    A premissa original era deixar a rota no ar com `isRequired: false`. Foi implementada e
    **reprovou**: `EmailVerificationPrompt::getVerifiable()` declara retorno `MustVerifyEmail`
    (`vendor/filament/filament/src/Auth/Pages/EmailVerification/EmailVerificationPrompt.php:36-43`),
    é chamada no `mount()` (`:31`), e `App\Models\User` não implementa a interface — a tela
    responde **500**. Publicar uma rota que sempre erra é pior que não a publicar. Ver ADR-03.
  - **Se negado** (o usuário quiser a verificação de e-mail ligada de fato): o passo 4 do PRD
    muda de uma linha para uma feature própria — `App\Models\User` teria de implementar
    `Illuminate\Contracts\Auth\MustVerifyEmail`, e aí duas coisas mudam para todo mundo: todo
    aceite de convite passa a disparar e-mail de verificação
    (`vendor/filament/filament/src/Auth/Pages/Register.php:106,161-164`) e, com
    `isRequired: true`, todo usuário sem `email_verified_at` — inclusive o administrador que o
    `UsuarioAdminSeeder` cria — é barrado no painel
    (`vendor/laravel/framework/src/Illuminate/Auth/Middleware/EnsureEmailIsVerified.php:32-40`).
    CT-07 e CT-08 são refeitos. **Isso está fora deste requisito** e não foi entregue.

- **RQ-01 — qual chave de configuração o 2FA usa?**
  O Auth Designer só tem cinco chaves (`login`, `registration`, `password-reset`,
  `email-verification`, `profile` — `AuthDesignerPlugin.php:88-108`). Não existe chave "2fa".
  - **Assumido**: a chave `login`, porque o desafio de 2FA é a **segunda etapa do mesmo
    login** — mesma barreira, mesma mídia, mesmo alternador de tema. É a mesma escolha que
    `TelaBloqueio::getAuthDesignerPageKey()` já faz pelo mesmo motivo
    (`app/Filament/Pages/Auth/TelaBloqueio.php:52-55`).
  - **Se negado**: bastaria trocar o retorno de `getAuthDesignerPageKey()` e configurar a
    chave escolhida nos três painéis. Uma linha por painel.

- **RQ-04 — em quais painéis?**
  O requisito é singular ("a tela de confirmação do email") e o kit tem três painéis.
  - **Assumido**: os três, porque login e recuperação de senha já são configurados nos três
    e deixar dois painéis com a tela crua reintroduz exatamente a inconsistência que o
    requisito reclama.
  - **Se negado**: remover o bloco dos providers de `admin` e `infra`.

## Fora de Escopo (declarado)

- **Ligar a verificação de e-mail de verdade** — ver a ambiguidade de RQ-04/RQ-05. O pedido é
  que a tela ESTEJA vestida, não que o fluxo de login mude.
- **A tela de `profile`** (`->profile()` do Auth Designer). O perfil do kit é o `myProfile` do
  Breezy (slug `meu-perfil`), o `->profile()` do plugin nunca foi chamado, e o requisito não
  fala dele.
- **A aba de 2FA dentro do perfil** (`Jeffgreco13\FilamentBreezy\Livewire\TwoFactorAuthentication`).
  Ela é um componente **dentro** de uma página de painel autenticado, não uma tela de auth: não
  tem layout próprio para vestir. O requisito fala da "tela de exibição para inserir o codigo",
  que é a `TwoFactorPage` — o desafio pós-login.
