---
title: Registro aberto e aprovação
parent: Autenticação
grand_parent: Português
nav_order: 2
---

# Registro aberto e aprovação

O convite é a porta padrão do kit. A segunda porta — **cadastro aberto**, sem convite — existe,
e **nasce desligada**:

```dotenv
KIT_REGISTRO=false                      # a porta pública
KIT_REGISTRO_APROVACAO_MANUAL=false     # nasce pendente até alguém aprovar?
KIT_REGISTRO_VERIFICAR_EMAIL=false      # exige e-mail validado no /app? (tambem editavel na tela)
```

Com `KIT_REGISTRO=false`, `/app/register` responde **só** a quem traz um token de convite
válido — sem token, recusa e manda para o login. É o comportamento que o kit sempre teve, e
nada nesta seção acontece até você ligar a chave.

## As duas portas convivem na mesma tela

`/app/register` decide o caminho pela **ausência** do parâmetro `token`:

| URL | Com `KIT_REGISTRO=false` | Com `KIT_REGISTRO=true` |
|---|---|---|
| `?token=` válido | aceite de convite | aceite de convite (igual) |
| `?token=` inválido, expirado ou usado | recusa → login | **recusa → login** (igual) |
| sem `token` | recusa → login | formulário de cadastro |

A segunda linha é deliberada: token inválido **nunca** cai no cadastro aberto. Se caísse,
`?token=qualquer-coisa` seria uma segunda entrada para a porta pública — justamente a que não
passa pelo limite de tentativas da recusa nem pela mensagem genérica que não revela se o token
não existe, venceu ou já foi usado.

## O que ligar `KIT_REGISTRO=true` faz refletir

| Onde | O que muda |
|---|---|
| `/app/register` sem token | passa a exibir o formulário em vez de recusar |
| `/app/login` | passa a oferecer o link "Criar conta" (antes escondido, porque levaria a uma tela que recusa) |
| papel de quem se cadastra | **só** `panel_user` — nenhum outro perfil, e 403 em `/admin` e `/infra` |
| `/admin/organizacoes` (com tenancy) | aparece o campo *"Aceita cadastro público"* em cada organização |
| tela de usuários (`/admin` e `/app`) | ganha a coluna **Situação**, o filtro *"Somente pendentes"* e a ação **Aprovar** |
| channel de log `autenticacao` | passa a registrar cada cadastro e cada aprovação, com o e-mail mascarado |

## O papel de quem se cadastra, e nada além dele

Quem entra por essa porta recebe **um único** papel: `panel_user`, o perfil básico do painel de
negócio. Não recebe `admin_app`, não alcança `/admin` nem `/infra` — os dois respondem **403**.
Quem administra ajusta os papéis depois, na tela de usuários, que é onde essa decisão vive.

A atribuição é feita em um lugar só (`App\Support\RegistroAberto::papel()`), e vale também para
quem chamar o registro de fora da tela — um comando, um job, um seeder.

## Aprovação manual: pendente não entra em painel nenhum

Com `KIT_REGISTRO_APROVACAO_MANUAL=true`, o cadastro nasce **pendente**:

- não recebe papel nenhum;
- a sessão que o cadastro abriu é encerrada na hora, e a pessoa volta ao login com a mensagem
  de que a conta aguarda liberação — em vez de levar um 403 depois de um cadastro que funcionou;
- `/app`, `/admin` e `/infra` respondem **403** enquanto ele estiver pendente.

Quem libera abre a tela de usuários, filtra por *"Somente pendentes"* e usa a ação **Aprovar**.
Aprovar dá o papel do painel de negócio (na organização corrente, quando há tenancy) e é
idempotente — clicar duas vezes não duplica papel. A ação exige permissão de **editar usuário**,
então o usuário comum do `/app` não a vê.

> Enquanto o cadastro está pendente ele **não tem papel**, e o formulário de edição sabe disso:
> o campo *Papéis*, normalmente obrigatório, deixa de ser exigido nesse caso. Sem essa exceção a
> edição de um pendente seria impossível de salvar, e a única saída seria dar um papel à mão —
> o que concede acesso sem passar pela aprovação.

## Cadastro por organização (multi-tenancy)

Com a multi-organização ligada, **duas** condições valem juntas: a instalação libera o cadastro
(`KIT_REGISTRO=true`) **e** cada organização opta, no campo *"Aceita cadastro público"* da tela
dela. O default de cada organização é **não** — ligar a chave global não abre cadastro em
nenhuma organização existente sem alguém decidir.

O endereço carrega o slug:

```text
/app/register?org=acme
```

Sem `?org`, com slug desconhecido, com a organização **inativa** ou com o cadastro **desligado**
nela, a tela devolve **a mesma** recusa — quem visita não descobre qual das condições falhou. Ou
seja: numa instalação multi-organização, divulgar `/app/register` sem o `?org=` leva as pessoas
à recusa; divulgue o link da organização.

Isto não se confunde com *criar* organização: registrar-se **numa** organização não é criá-la, e
quem cria organização continua sendo quem administra a instalação, pelo `/admin`.

## Validação de e-mail (opcional)

Exige e-mail confirmado para entrar no `/app`. **Editável em `/admin/configuracoes-do-kit` → aba
Registro**, e o valor gravado vale no **request seguinte** — sem deploy. `KIT_REGISTRO_VERIFICAR_EMAIL`
continua existindo: ele semeia a instalação nova e é o plano B, como as demais configurações da
tela.

> Até a v0.19.3 esta chave valia **só** pelo `.env`, e a tela dizia isso. O motivo era real: o
> Filament fixa o middleware de e-mail verificado no array da rota no momento do registro, então a
> decisão era tomada no boot e um toggle na tela gravava sem fazer efeito. O conserto foi tirar a
> *decisão* do array da rota e pôr lá um *decisor* — `App\Http\Middleware\ExigirEmailVerificado`,
> que pergunta a cada request. A tela de confirmação, por consequência, passou a existir sempre.

**Leia esta parte antes de ligar em ambiente com gente dentro — agora um clique basta.** A
exigência vale para **todo** usuário do `/app`, não só para os recém-cadastrados, e **não** depende
de o cadastro aberto estar ligado: quem estiver sem `email_verified_at` é levado à tela de
confirmação. Numa instalação limpa isso não atinge ninguém, porque os caminhos que o kit usa para
criar usuário já gravam a coluna — o seeder do administrador, a factory, o seeder da demo, o
aceite de convite e o `kit:admin`. Quem **não** grava é a criação manual pela tela de usuários.

Para marcar como validada a base que já existe, antes de ligar:

```bash
php artisan tinker --execute 'App\Models\User::whereNull("email_verified_at")->update(["email_verified_at" => now()]);'
```

**Quem vem de convite nunca é afetado**, e vale saber por quê: `Convite::aceitar()` grava
`email_verified_at` de propósito — o token chegou no endereço da pessoa, então ele já provou a
posse, e pedir a mesma prova duas vezes é atrito sem ganho. O Filament só envia o pedido de
confirmação para quem ainda não validou, então o convidado nunca recebe esse e-mail.

## O limite de tentativas

O envio do formulário usa o limite do próprio Filament: **2 tentativas por IP** e 2 por
endereço de e-mail, por janela — o mesmo que o aceite de convite já usava. A recusa por falta de
convite tem limite próprio (5 por 10 minutos por IP), que protege o **arquivo de log** contra um
laço anônimo, sem mudar o que a pessoa vê.

## Onde isso vive no código

| O quê | Onde |
|---|---|
| as três opções, lidas em um lugar só | `app/Support/RegistroAberto.php` |
| a tela, com os dois modos | `app/Filament/Pages/Auth/RegistroPorConvite.php` |
| a barreira do pendente | `App\Models\User::canAccessPanel()` — primeira instrução |
| a liberação | `App\Models\User::aprovar()` |
| coluna, filtro e ação de aprovar | `app/Filament/Concerns/AprovacaoDeCadastro.php` |
| o campo por organização | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php` |

> **Para quem for ligar as opções numa tela de Settings**: `App\Support\RegistroAberto` é o ponto
> único de leitura. Trocar `config()` pelo Settings é reescrever o corpo de três métodos naquele
> arquivo — nenhum outro lugar do projeto lê essas chaves, e há um teste que reprova se alguém
> passar a ler.

