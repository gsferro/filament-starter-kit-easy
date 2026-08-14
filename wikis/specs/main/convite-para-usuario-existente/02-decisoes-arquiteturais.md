# Decisões Arquiteturais — Convite para quem já tem conta

## ADR-01: Uma tabela, duas vias de consumo

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Convidar quem não tem conta e convidar quem já tem são fluxos com finais diferentes: o
primeiro **cria** um usuário, o segundo **vincula** um que existe. A tentação é modelar dois
registros — `convites` para o primeiro, algo como `ofertas_de_acesso` para o segundo.

Mas os dados são os mesmos: e-mail, papel, organização, quem convidou, prazo, e o carimbo de
consumo. E o ciclo de vida é o mesmo: nasce pendente, é enviado, expira, é aceito, recusado
ou revogado. A diferença é só o que acontece **no aceite**.

O `jeffersongoncalves/filament-teams` tem uma tabela só (`team_invitations`) e o
`offload-project/laravel-invite-only` também (`invitations`, polimórfica). Nenhum dos dois
separou.

### Decisão

Uma tabela: a `convites` que já existe. O que decide a via é uma pergunta ao banco no momento
do aceite — `Convite::usuarioExistente(): ?User`. `Convite::aceitar()` desvia para
`aceitarComoUsuarioExistente()` quando há conta.

A pergunta é feita **no aceite**, não na criação. Entre criar o convite e alguém clicar podem
passar dias, e a pessoa pode ter criado conta nesse meio-tempo por outro caminho. Decidir a
via na criação congelaria uma resposta que envelhece.

### Alternativas Consideradas

1. **Segunda tabela.** Descartada: seis colunas iguais, o mesmo Resource, a mesma
   notificação, o mesmo comando de lembrete. Duas tabelas para um domínio é duas migrations
   para toda evolução futura.
2. **Coluna `tipo` (`registro` | `oferta`) decidida na criação.** Descartada: é um terceiro
   estado a manter em sincronia com um fato que o banco já sabe, e envelhece — exatamente o
   argumento que nos fez não ter coluna de status.
3. **Flag no formulário, escolhida por quem convida.** Descartada: quem convida não sabe (nem
   deveria precisar saber) se o endereço já tem conta. Perguntar transfere ao usuário uma
   consulta que o sistema faz melhor.

### Consequências

- **Positivas**: um Resource, uma notificação, um comando de lembrete, uma tabela para
  auditar. As wikis irmãs (`convite-em-massa`, `lembretes-de-convite`) herdam as duas vias de
  graça.
- **Negativas**: `aceitar()` passa a ter dois finais, e o método fica mais longo. Mitigado
  extraindo `atribuirPapel()`, que os dois compartilham.
- **Riscos**: a consulta por e-mail no aceite é um ponto a mais que pode divergir da
  normalização usada na asserção. Mitigação: as duas usam `mb_strtolower(trim(...))`, e
  CT-08 cobre o caso de maiúsculas.

### Referências

- `app/Models/Convite.php:186-197` (a guarda que deixa de lançar)
- Refina: ADR-02, ADR-03

---

## ADR-02: O token continua nas duas vias — mas com poder diferente

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

O token existe para o link. Na via de oferta de acesso, a resolução **poderia** ser só pelo
e-mail do usuário autenticado — é assim que o teamkit faz, e o resultado é elegante: sem
token, sem link, sem rota pública, a superfície de ataque clássica de convite simplesmente
não existe.

A tentação, então, é não gerar token quando o e-mail já tem conta: credencial que não existe
não vaza.

**Mas isso fecha a porta para quem mais precisa dela.** A caixa de entrada é uma página do
painel `app`; com tenancy ela vive sob `/app/{tenant}`, e o Filament precisa de um tenant
para renderizá-la. Duas categorias de pessoa não conseguem chegar lá:

- quem tem conta e **zero** organizações;
- quem tem conta e papel só de `/admin` ou `/infra` — `canAccessPanel('app')` nega, porque o
  papel do `/app` só é atribuído **no** aceite.

O segundo caso é um impasse circular: precisa aceitar para ganhar o papel, precisa do papel
para alcançar a tela de aceitar. O teamkit escapa disso porque times pessoais são obrigatórios
(`config/filament-teams.php:41`) — todo usuário sempre tem um tenant. Não temos isso, e não
vamos criar organizações fantasma só para destravar uma tela.

### Decisão

O token é gerado nas duas vias. O que muda é o **poder** dele:

| Via | O token é | Porque |
| --- | --- | --- |
| conta nova | **suficiente** | a conta ainda não existe; quem tem o link define a senha |
| oferta de acesso | **necessário, não suficiente** | exige também `$user->email === $convite->email`, conferido no model |

Na via de oferta, interceptar o link não dá nada a quem não tem a senha do endereço
convidado. É estritamente mais forte que a via de conta nova — e é o que torna o link
utilizável por quem a caixa de entrada não alcança.

### Alternativas Consideradas

1. **Sem token na via de oferta** (o desenho do teamkit). Descartada: deixa quem tem zero
   organizações e quem tem papel só de `/admin`/`/infra` sem nenhum caminho de aceite.
2. **Times pessoais obrigatórios**, para garantir que todo usuário tenha tenant. Descartada:
   inventa uma organização por usuário — poluição de dado, de menu e de relatório, para
   resolver um problema de roteamento de tela.
3. **Página de aceite fora do segmento de tenant.** Descartada por ora: exigiria rota e
   controller próprios fora do Filament, e o link já resolve com a página que existe.

### Consequências

- **Positivas**: um único link no e-mail atende os dois casos; a caixa de entrada fica sendo
  conveniência e não pré-requisito.
- **Negativas**: existe um token para uma via que, em teoria, não precisaria dele. O
  hash em repouso e o prazo já valem para ele.
- **Riscos**: alguém pode ler "token" e supor que ele basta, replicando a via de conta nova.
  Mitigação: a asserção é no model (ADR-03) e CT-04 a exercita direto.

### Referências

- `app/Providers/Filament/AppPanelProvider.php:192-196` (o `->tenant()` que prefixa as rotas)
- `app/Models/User.php:75-104` (`canAccessPanel`, que nega `/app` sem papel do painel)
- Refina: ADR-01. Refinada por: ADR-05

---

## ADR-03: A asserção de identidade vive no model, não na query da tela

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Na caixa de entrada, a lista já é escopada pelo e-mail do usuário autenticado. Com o Filament
resolvendo o `$record` de uma ação através da query da tabela, um convite de outro endereço
não é sequer resolvível — a tela **parece** suficientemente segura, e escrever a verificação
de novo dentro do método parece redundância.

É exatamente esse raciocínio que produziu o furo do
`jeffersongoncalves/filament-teams`. Lá, `TeamInvitation::accept(Authenticatable $user)`
(`src/Models/TeamInvitation.php:34-38`) faz:

```php
$this->team->users()->attach($user->getAuthIdentifier());
$this->delete();
```

Nenhuma comparação entre `$user->email` e `$this->email`. A única barreira é o
`->where('email', $email)` da query da página
(`src/Pages/TeamInvitationAccept.php:45`). O método aceita **qualquer** `Authenticatable` e
anexa. Enquanto a página for o único chamador, funciona; o primeiro job, comando, endpoint de
API ou ação em massa que chamar `accept()` passa por cima da barreira **sem que nada acuse** —
e o resultado é entrada indevida numa organização, com o papel do convite.

### Decisão

`aceitarComoUsuarioExistente(User $user)` e `recusar(User $user)` **começam** conferindo a
identidade, por um `exigirDono(User $user): void` privado que lança quando o e-mail não
corresponde. A query da caixa de entrada continua escopada — mas como filtro de UI, não como
controle de acesso.

Comparação normalizada (`mb_strtolower(trim(...))`) nos dois lados: e-mail não é
case-sensitive na prática, e o convite pode ter sido digitado com maiúsculas.

### Alternativas Consideradas

1. **Confiar na query escopada** (o desenho do teamkit). Descartada pelo argumento acima:
   barreira que depende de o chamador ser um só não é barreira, é coincidência.
2. **Policy no `Convite`** (`can('aceitar', $convite)`). Descartada: policy é autorização de
   ação por perfil; isto é asserção de **identidade do dono do registro**. E policy não é
   consultada por job nem por comando, que é justamente o chamador que se quer cobrir.
3. **Middleware.** Descartada: não há request na maioria dos chamadores futuros.

### Consequências

- **Positivas**: qualquer chamador futuro herda a barreira. A regra fica em
  `.ai/rules/filament.md`, onde os agentes a leem antes de editar.
- **Negativas**: uma query a mais por aceite (ler o e-mail do usuário). Irrelevante — é uma
  ação por vez, feita por gente.
- **Riscos**: alguém remover a asserção por parecer redundante com a query da tela.
  Mitigação: CT-04 chama o método **direto**, com o usuário errado, e cobra a exceção. É o
  teste que existe exatamente para essa "simplificação".

### Referências

- `vendor` do teamkit: `src/Models/TeamInvitation.php:34-38` e `src/Pages/TeamInvitationAccept.php:45`
- Refina: ADR-02

---

## ADR-04: Consumo por `update` condicional, não por check-then-act

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Na via de conta nova, o uso único do convite é garantido de graça: `aceitar()` roda dentro da
transação que `Register::register()` abre
(`vendor/filament/filament/src/Auth/Pages/Register.php:84-102`), e o segundo aceite
concorrente falha no `unique` de `users.email` — a transação inteira volta atrás. Não é
elegante, mas é o banco garantindo.

Na via de oferta **não existe esse unique para nos salvar**: o aceite só faz
`syncWithoutDetaching` e `assignRole`, as duas operações idempotentes. Duas requisições
simultâneas (o clique duplo no botão, ou duas abas) passariam as duas pelo `whereNull`,
gravariam `aceito_em` as duas, e o papel seria atribuído duas vezes.

É precisamente o defeito do `laravel-invite-only`: `InviteOnly::accept()` é check-then-act
puro, sem transação, sem `lockForUpdate()` e sem constraint — e ele dispara
`event(new InvitationAccepted(...))` nas duas vezes, o que num listener de membership
significa duas linhas e duplo grant de papel.

### Decisão

O consumo é um `update` condicional, e o vínculo só acontece se ele afetou **uma** linha:

```php
$consumido = static::query()
    ->whereKey($this->getKey())
    ->whereNull('aceito_em')
    ->whereNull('recusado_em')
    ->update(['aceito_em' => now()]);

if ($consumido !== 1) {
    throw new RuntimeException('Este convite já foi usado.');
}
```

O `UPDATE ... WHERE aceito_em IS NULL` é atômico no banco: a segunda requisição recebe `0` e
para antes de vincular. Sem `lockForUpdate()`, sem transação explícita, sem `SELECT` de
verificação.

### Alternativas Consideradas

1. **`lockForUpdate()` dentro de `DB::transaction()`.** Funciona, e é mais linhas para a mesma
   garantia. O `update` condicional é a versão de uma expressão.
2. **Check-then-act** (o desenho do `invite-only`). Descartada: é o bug.
3. **Unique parcial no banco** (`unique(convite_id) where aceito_em is not null`). Descartada:
   índice parcial não é portável entre SQLite e MySQL, e o kit roda nos dois.

### Consequências

- **Positivas**: uma expressão, atômica, portável. Vale igual para `recusar()`.
- **Negativas**: `$this` fica com o atributo velho depois do `update` — daí o `refresh()`
  logo em seguida. Esquecê-lo faria o log sair com `aceito_em` nulo.
- **Riscos**: `update()` não dispara eventos de model, então `AuditsFillables` não registra
  este aceite na trilha. Aceitável: quem registra é o log de `autenticacao`, com contexto
  mais rico do que a auditoria teria.

### Referências

- `vendor` do invite-only: `src/InviteOnly.php`, método `accept()`
- CT-06 cobre o aceite concorrente

---

## ADR-05: A caixa de entrada é conveniência; o link é a via canônica

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

A caixa de entrada (`ConvitesRecebidos`) é uma página do painel `app`. Com a tenancy ligada
ela vive sob `/app/{tenant}`, e o Filament precisa de um tenant corrente para renderizar.
Logo, ela **não alcança** quem tem zero organizações nem quem só tem papel de `/admin` ou
`/infra` (ver ADR-02).

Isso poderia ser lido como defeito da caixa de entrada. Não é — é a consequência de ela viver
dentro do painel, que é justamente o que lhe dá zero superfície pública.

### Decisão

A caixa de entrada é **conveniência para quem já está dentro**: quem já pertence a pelo menos
uma organização e foi convidado para outra. Esse é o caso de uso que motivou a feature (a
consultora com dois clientes), e para ele a página é o caminho mais confortável — não depende
de achar o e-mail.

E ela não é só conveniência: **a recusa só existe nela**. O link tem um destino só (aceitar);
dizer "não, obrigada" precisa de uma tela com duas ações. Sem a caixa de entrada, `recusar()`
não teria de onde ser chamado, e a recusa registrada — a parte que o teamkit tem e o nosso
convite não tinha — não existiria.

A **via canônica é o link**, que funciona sempre: para conta nova, para quem tem conta e
nenhuma organização, e para quem tem conta e papel de outro painel.

Consequência de desenho: o aceite pós-login **não** é automático. Quem chega pelo link sem
estar autenticado é mandado ao login com uma notificação; depois de entrar, encontra a oferta
pelo item de menu com contagem. Um redirecionamento que vincula alguém a uma organização no
primeiro login seria exatamente o que `requiresConfirmation()` existe para evitar.

### Alternativas Consideradas

1. **Times/organizações pessoais obrigatórias**, como o teamkit. Descartada em ADR-02.
2. **Rota + controller próprios fora do Filament** para a página de aceite. Descartada por
   ora: o link já resolve com a página de registro que existe, e uma rota nova traz de volta
   a superfície pública que a caixa de entrada evita. Fica anotado como a saída, se um dia
   alguém precisar de uma tela de aceite acessível sem painel.
3. **Consumir o token automaticamente depois do login.** Descartada: vincula sem confirmação
   explícita.

### Consequências

- **Positivas**: a página é simples (uma tabela, duas ações) e não precisa resolver
  roteamento fora de tenancy.
- **Negativas**: existem dois caminhos para a mesma coisa, e a documentação tem de explicar
  qual serve quando.
- **Riscos**: alguém reportar "não vejo meus convites" estando sem organização. Mitigação:
  a linha em `wikis/receitas.md#problemas-comuns` e o texto do e-mail, que sempre traz o
  link.

### Referências

- ADR-02 (o impasse circular do papel)
- `app/Filament/Pages/Auth/TelaBloqueio.php:105-114` (o padrão de item de menu reusado)

---

## ADR-06: O token prova o e-mail, então o usuário nasce verificado

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

`Convite::aceitar()` cria o usuário sem `email_verified_at`
(`app/Models/Convite.php:200`). Hoje é inócuo: nenhum dos três painéis chama
`->emailVerification()`.

Mas a via de conta nova só é alcançável por quem **recebeu o token no endereço convidado** —
o token existe em claro apenas no e-mail enviado para aquele endereço. Isso é precisamente a
prova que a verificação de e-mail existe para obter.

Vale registrar o contraste com o teamkit, porque ali o mesmo detalhe é uma vulnerabilidade
latente: `User` implementa `MustVerifyEmailContract`
(`app/Models/User.php:82` do teamkitv4) e o aceite só compara strings de e-mail, sem checar
`hasVerifiedEmail()`. No dia em que aquele kit ligar `->registration()`, alguém registra
`ceo@vitima.com` sem confirmar o endereço, vê o convite pendente daquele e-mail na própria
tela e aceita. Nosso desenho fecha isso por outra via: o registro **só** existe com token
válido, e o e-mail vem do convite — não do formulário.

### Decisão

`aceitar()` grava `email_verified_at = now()` no usuário que cria.

`email_verified_at` **não** está no `$fillable` de `User`
(`app/Models/User.php:42-46`), então mass assignment o descarta em silêncio: usar
`forceFill(['email_verified_at' => now()])->save()` depois do `create()`, ou incluir
explicitamente. Conferir na implementação qual dos dois — a diferença é uma query.

Nada muda para a via de oferta: lá o usuário já existe, com o estado de verificação que
tiver.

### Alternativas Consideradas

1. **Deixar nulo e confiar no fluxo de verificação.** Descartada: pede a mesma prova duas
   vezes, e no dia em que a verificação for ligada todo usuário nascido de convite é barrado
   na porta sem motivo.
2. **Ligar `->emailVerification()` nos painéis agora.** Fora de escopo — é decisão de produto,
   não desta feature.

### Consequências

- **Positivas**: uma linha; o fluxo de convite fica correto para o dia em que a verificação
  for ligada.
- **Negativas**: se alguém considerar que só a confirmação explícita conta, este default
  discorda. O comentário no código diz por quê.
- **Riscos**: nenhum hoje — a coluna não é lida por nada no kit.

### Referências

- `app/Models/Convite.php:200`
- `vendor/filament/filament/src/Auth/Pages/Register.php:106` (`sendEmailVerificationNotification`)

---

## ADR-07: Nenhum dos dois pacotes é instalado

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Os dois pacotes analisados resolvem partes deste problema, e a pergunta honesta antes de
escrever código é se algum deles substitui a feature.

### Decisão

Nenhum é instalado. Copiam-se **ideias**, não a dependência.

**`offload-project/laravel-invite-only`** — três impedimentos, cada um bastante sozinho:

1. **`final class Invitation` e `final class InviteOnly`, sem config de model.** Não há como
   acrescentar `$hidden`, escopo global de tenant, ou trocar o model. Num kit com tenancy
   isso é bloqueante: convites não seriam escopáveis por organização sem fork.
2. **O fluxo de aceite não funciona.** `getAcceptUrl()` resolve
   `invite-only.invitations.accept`, que é rota **POST**, e essa URL é usada no `->action()`
   do e-mail (um `<a href>`, logo GET) e num `redirect()->to()` do controller — **405** nos
   dois. Não há view nem form no pacote para intermediar. Os ~66 testes não pegam porque
   **nenhum toca HTTP**.
3. **`unique(invitable_type, invitable_id, email)` sobre todos os status.** Depois de uma
   recusa nunca mais se convida aquele e-mail para aquela organização — e as próprias
   mensagens de erro do pacote mandam "create a new invitation instead", que o schema
   proíbe. Pior: `inviteMany()` só captura `InvalidArgumentException`, então um duplicado
   não-pendente **derruba o lote inteiro**.

Somado: token em claro no banco, `token` no `$fillable` e sem `$hidden`, rota de accept sem
`auth` e sem casar e-mail (qualquer autenticado com o token aceita convite de terceiro e ganha
o `role`), e `resend()` que não estende o prazo. A lógica de negócio inteira do pacote é
~250 linhas; o que aproveitamos dela são quatro ideias.

**`jeffersongoncalves/filament-teams`** — não é candidato a substituir nada aqui: não tem
notificação (o convidado nunca é avisado), não tem papel por organização, não tem expiração,
o `ApplyTenantScopes` é literalmente `return $next($request)` — um kit de multi-tenancy que
não escopa dados — e não há um único teste de convite, tenancy ou policy nos dois repositórios.
Ele contribui com o **formato** (oferta que se aceita ou recusa dentro do painel) e com um
erro instrutivo (ADR-03).

### Alternativas Consideradas

1. **Instalar `laravel-invite-only` e sobrescrever as rotas/views.** Descartada: começa com
   três correções no dia um, e o model `final` continua não escopável.
2. **Instalar `filament-teams`.** Descartada: substituiria nossa tenancy inteira por uma sem
   escopo de dados e sem papéis.
3. **Fork de um dos dois.** Descartada: manter fork custa mais que as ~200 linhas desta
   feature.

### Consequências

- **Positivas**: nenhuma dependência nova; o `Convite` continua nosso, escopável e testado.
- **Negativas**: reimplementamos bulk e lembretes (as wikis irmãs), que os pacotes já têm.
- **Riscos**: nenhum imediato. Se algum dos dois evoluir, o comparativo está aqui para
  reavaliar.

### Referências

- Análise de código-fonte dos dois pacotes, 2026-08-14
- `wikis/pacotes.md` (a regra do kit: não reimplementar vendor — e por que aqui é exceção)
