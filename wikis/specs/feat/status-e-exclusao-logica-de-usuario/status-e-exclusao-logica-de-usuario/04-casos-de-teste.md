# Casos de Teste — Status de ativo/inativo e exclusão lógica de usuário

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação — ela
> ainda não existe. O plano entrou só para paths (`TelaLogin`, `LoginSocialController`,
> `UserResource` do `/admin`, a rota `/conta-indisponivel`, a Lixeira do `/infra`) e para a tabela
> `## Superfície de UI`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — negação no login (senha e social) e o aviso | 3 | 3 | 9 | completo |
| A2 — desativar/reativar e as permissões | 2 | 3 | 6 | padrão |
| A3 — exclusão lógica e restauração | 2 | 3 | 6 | padrão |
| A4 — exibição (coluna, filtro) | 1 | 1 | 1 | mínimo |
| A5 — documentação | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: tabela estado × operação, tabela de decisão, matriz papel × ação, rastreio
  de efeito (log, trilha, sessão, vínculo), EP (motivo, senha certa/errada), normalização
  (e-mail), 2-switch (excluir → restaurar → entrar).
- Cenários: 34 · Regras: 11 · Mutantes previstos: 37 · Sem matador: 0 · Lacunas declaradas: 1

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | coluna `users.ativo`, `users.deleted_at`, linha em `recycle_bin_items`, duas permissões, uma rota, uma view, uma trait de tela | CT-25, CT-29 |
| F | negar no login por senha; negar no login social (dois ramos + confirmação por link); explicar na tela; desativar; reativar; excluir logicamente; restaurar (`/admin` e `/infra`); registrar | CT-01…CT-28 |
| D | `ativo` × `deleted_at` × `aprovacao_pendente` (oito combinações, quatro estados úteis); `deleted_at` formatado `d/m/Y`; e-mail com caixa diferente; e-mail de conta na lixeira; `sub` do provedor com vínculo | CT-01, CT-10, CT-14, CT-30, CT-32 |
| I | formulário de login (Livewire) nos três painéis; callback OAuth; link assinado de confirmação; ações de tabela; Lixeira; middleware do painel numa sessão já aberta | CT-04, CT-13, CT-16, CT-18, CT-28, CT-34 |
| P | SQLite em memória na suíte — `lower(email)` é o que torna a comparação case-insensitive nos três bancos; `Hash::check` com `BCRYPT_ROUNDS=4` | CT-14 (caixa do e-mail) |
| O | quem desativa é `admin`; `panel_user` não pode; a própria pessoa não pode se desativar; a instalação não pode ficar sem `master_global` ativo; senha errada em conta indisponível é o uso indevido esperado (enumeração) | CT-05, CT-11, CT-19, CT-22, CT-23, CT-24 |
| T | a data da exclusão aparece no aviso; restaurar depois de excluir (2-switch); desativar duas vezes (idempotência); sessão aberta no momento da desativação | CT-10, CT-21, CT-26, CT-34 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — conta inativa ou excluída não abre painel nenhum, em qualquer caminho | A1 (completo) | RQ-01, RQ-02, RQ-08 | tabela estado × painel; estado × operação (sessão aberta) | CT-01, CT-02, CT-03, CT-34 |
| R2 — inativo que digita a senha certa cai no aviso "desativada" sem sessão; senha errada recebe o genérico | A1 (completo) | RQ-02, RQ-03, RQ-04 | tabela de decisão (estado × senha) | CT-04, CT-05, CT-06, CT-07 |
| R3 — toda tentativa de inativo fica registrada — no log e na trilha, uma vez | A1 (completo) | RQ-05 | rastreio de efeito | CT-08, CT-09 |
| R4 — excluído que digita a senha certa vê a data da exclusão; senha errada recebe o genérico; a tentativa fica registrada | A1 (completo) | RQ-08, RQ-09, RQ-10 | tabela de decisão; rastreio de efeito | CT-10, CT-11, CT-12 |
| R5 — o login social recusa inativo e excluído com o mesmo aviso, nos dois ramos e na confirmação, sem efeito colateral | A1 (completo) | RQ-06, RQ-09, RQ-10 | partição (ramo do e-mail, ramo do vínculo, link de confirmação) × estado; rastreio de efeito | CT-13, CT-14, CT-15, CT-16, CT-17 |
| R6 — desativar e reativar são ações do `/admin`, cada uma com permissão própria, idempotentes, e desativar recusa a própria conta e o último `master_global` | A2 (padrão) | RQ-07 | matriz papel × ação; estado × evento; guardas | CT-18, CT-19, CT-20, CT-21, CT-22, CT-23, CT-24 |
| R7 — excluir é lógico: a linha fica, some das consultas, e entra na lixeira | A3 (padrão) | RQ-08 | rastreio de efeito | CT-25 |
| R8 — o excluído é restaurável pelo `/admin` e pelo `/infra`, com permissão, e volta a entrar | A3 (padrão) | RQ-11 | 2-switch; matriz papel × ação; guarda arquitetural | CT-26, CT-27, CT-28, CT-29 |
| R9 — a tela mostra o estado e filtra por ele | A4 (mínimo) | RQ-01, RQ-07 | partição exaustiva do estado exibido | CT-30, CT-31 |
| R10 — e-mail de conta na lixeira fica reservado | A3 (padrão) | RQ-08 (consequência, ADR-05) | unicidade + soft delete (taxonomia) | CT-32 |
| R11 — o README documenta os três estados, o aviso, a reativação e a restauração, em PT e EN | A5 (mínimo) | RQ-12 | presença de seção | CT-33 |

Técnica escalada acima do perfil: em R6 (área `padrão`) a idempotência e as duas guardas pedem
estado × evento completo — o custo de errar é uma instalação sem administrador.

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `motivoDeIndisponibilidade()`, `desativar()`, `reativar()`, `comEmail()` | escolha de implementação | detalhe do cenário (só aparecem no "Como" do índice) |
| valores de `motivo` no log (`conta_inativa`, `conta_excluida`) | o requisito pede "registrar", não nomeia o campo | **premissa** `@premissa`: o `Então` afirma que o log distingue os dois casos; os literais são os do plano |
| texto exato do aviso | o requisito determina o **conteúdo** ("desativado", "contato com o admin", "quando foi excluído"), não a frase | o `Então` afirma as palavras-chave e a data, não a frase inteira |
| código HTTP 403 da página do aviso | escolha de implementação | assertion de apoio, nunca única |
| ações só no `/admin` | premissa registrada no `00` | cenários de R6 rodam no `/admin`; nenhum afirma ausência no `/app` (suíte `Kit` não tem tenancy — o resource do `/app` se esconde) |
| coluna com três estados e filtro "Somente inativos" | comportamento visível que o requisito só implica ("o usuário tem status") | R9, perfil mínimo, sem afirmar rótulo exato além de "Inativo" |
| `Failed` do Filament × `Failed` à mão | mecanismo | o `Então` afirma **uma** linha na trilha, que é o observável |

**Perguntas em aberto** (já em `00-requisito.md` → `## Ambiguidades`): tela própria no layout do
Sentinel; aviso só com senha certa; ações só no `/admin`; guardas de própria conta e último
master; `/admin` **e** `/infra`; e-mail reservado. Todas com premissa adotada — os cenários que
dependem delas estão marcados `@premissa`.

## Setup Global

### Personas

- `admin` — `usuarioDoKit('admin', 'admin@example.com')` (helper de `tests/Pest.php`); autenticado
  com `$this->actingAs()`; `noPainelDoShield('admin')` antes de montar tela do `/admin`.
- `infra` — `usuarioDoKit('infra', 'infra@example.com')` para a Lixeira.
- `panel_user` — `usuarioDoKit('panel_user', ...)`: a persona **discriminante** de permissão (tem
  papel de painel, não tem `Desativar:User`).
- `master_global` — `usuarioDoKit('master_global', ...)`: só como **controle** (vence tudo por
  `Gate::before`) e como alvo das guardas de R6.
- alvo — `usuario('alvo@example.com')` (senha `password`), com `assignRole()` do painel que o
  cenário exige.

### Fixtures

- inativo: `$user->forceFill(['ativo' => false])->save()` — direto na coluna, para o arranjo não
  depender do método sob teste (que é o que R6 prova).
- excluído: `$user->delete()` (o próprio soft delete é o sob teste em R7; nos demais é arranjo).
- `Carbon::setTestNow('2026-08-12 10:00:00')` antes do `delete()` quando a data importa (CT-10,
  CT-15).

### Fakes

- `Notification::fake()` nos cenários sociais (prova o não-envio de `PrimeiroAcessoSocial` e
  `ConfirmarVinculoSocial`).
- `Socialite::fake('google', usuarioSocialFalso(ProvedorSocial::Google, [], [...]))` +
  `ligarProvedor(ProvedorSocial::Google)` — helpers de `tests/Pest.php`.
- `espiarAutenticacao()` — spy do channel `autenticacao` (`tests/Pest.php`).

### Estratégia de DB

- `RefreshDatabase` global (`tests/Pest.php`), SQLite `:memory:`. `beforeEach` semeia
  `ShieldPermissionsSeeder` + `PapeisSeeder` nos arquivos que dependem de permissão.
- Filament: `Filament::setCurrentPanel('admin')` antes de `Livewire::test(TelaLogin::class)` — o
  login lê o painel corrente para `canAccessPanel()`.

### Nota sobre o flash entre o Livewire e o GET do aviso

O aviso vive um request na sessão. Nos cenários de R2/R4 a asserção do **redirect** é feita no
componente (`assertRedirect(route('auth.conta-indisponivel'))`) e a do **conteúdo** num
`$this->get()` seguinte. Se o flash não sobreviver entre `Livewire::test()` e `$this->get()` no
arnês, o conteúdo é provado com `$this->withSession([...])` explícito — a chave e o formato são
detalhe de implementação, o **texto** é o oráculo.

---

## Regra R1 — Conta inativa ou excluída não abre painel nenhum, em qualquer caminho

> `RQ-01`, `RQ-02`, `RQ-08` · perfil **completo** · técnica: **tabela estado × painel** (inativo,
> excluído, ativo × `app`, `admin`, `infra`) e **estado × operação** (sessão já aberta)

```gherkin
# language: pt

Funcionalidade: Status de ativo/inativo e exclusão lógica de usuário

  Regra: Conta inativa ou excluída não abre painel nenhum, em qualquer caminho

    Esquema do Cenário: [CT-01] o usuário inativo é negado no painel do próprio papel
      Dado um usuário com o papel "<papel>" e "ativo" igual a falso
      Quando o painel "<painel>" pergunta se ele pode acessar
      Então a resposta é "não"

      Exemplos:
        | papel      | painel |
        | panel_user | app    |
        | admin      | admin  |
        | infra      | infra  |

    Cenário: [CT-02] o usuário ativo com o papel do painel entra (controle)
      Dado um usuário com o papel "admin" e "ativo" igual a verdadeiro
      Quando o painel "admin" pergunta se ele pode acessar
      Então a resposta é "sim"

    Cenário: [CT-03] o usuário excluído é negado mesmo quando reconstruído com trashed
      Dado um usuário com o papel "admin" que foi excluído logicamente
      Quando o painel "admin" pergunta, à instância carregada com excluídos, se ele pode acessar
      Então a resposta é "não"

    Cenário: [CT-34] quem já estava dentro é barrado no request seguinte à desativação
      Dado um administrador autenticado no painel "admin"
      E ele é marcado como inativo enquanto a sessão está aberta
      Quando ele abre "/admin"
      Então a resposta é 403
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | guarda de `ativo` colocada **depois** do atalho do `master_global` (só nega quem não é master) | CT-01 com dataset extra: `master_global` inativo → negado. **Acrescentado ao esquema** (quarta linha: `master_global`, `admin`) |
| M2 | guarda escrita como `if ($this->ativo)` (invertida) | CT-02 |
| M3 | só `ativo` checado, `trashed()` esquecido | CT-03 |
| M4 | negação feita só na tela de login, não em `canAccessPanel()` | CT-34 (o middleware do painel consulta `canAccessPanel()`) |

---

## Regra R2 — Inativo que digita a senha certa cai no aviso; senha errada recebe o genérico

> `RQ-02`, `RQ-03`, `RQ-04` · perfil **completo** · técnica: **tabela de decisão** (estado da
> conta × senha)

| Conta | Senha | Resultado |
|---|---|---|
| inativa | certa | sem sessão, redirect para o aviso "desativada" |
| inativa | errada | sem sessão, erro genérico no campo, **sem** redirect |
| ativa | certa | sessão aberta |
| inexistente | qualquer | erro genérico (comportamento de hoje; coberto por CT-05 na linha "inexistente") |

```gherkin
  Regra: Inativo que digita a senha certa cai no aviso; senha errada recebe o genérico

    Cenário: [CT-04] @premissa inativo com a senha certa é levado ao aviso e não ganha sessão
      Dado um usuário "admin" inativo com a senha "password"
      Quando ele envia o formulário de login do painel "admin" com e-mail e senha corretos
      Então nenhuma sessão é aberta
      E a resposta é um redirecionamento para a página de aviso
      E a página de aviso diz que a conta está "desativada" e pede contato com o "administrador"

    Esquema do Cenário: [CT-05] senha errada em conta inativa, ou conta inexistente, recebe o erro genérico
      Dado <conta>
      Quando o visitante envia o formulário de login do painel "admin" com a senha "errada"
      Então nenhuma sessão é aberta
      E o campo de e-mail exibe o erro genérico de credenciais
      E não há redirecionamento

      Exemplos:
        | conta                                   |
        | um usuário "admin" inativo              |
        | nenhum usuário com aquele e-mail        |

    Cenário: [CT-06] usuário ativo com a senha certa continua entrando (regressão)
      Dado um usuário "admin" ativo com a senha "password"
      Quando ele envia o formulário de login do painel "admin" com e-mail e senha corretos
      Então a sessão é dele

    Cenário: [CT-07] a página de aviso visitada sem aviso pendente não mostra nada
      Dado um visitante sem aviso na sessão
      Quando ele abre "/conta-indisponivel" diretamente
      Então é redirecionado para "/"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M5 | interceptor mostra o aviso **sem** conferir a senha (enumeração) | CT-05, linha "inativo" — receberia redirect em vez do erro |
| M6 | interceptor procura o usuário sem `withTrashed()` e ignora `ativo` (só relança) | CT-04 — sem redirect |
| M7 | interceptor abre a sessão antes de redirecionar (`Auth::login` esquecido de desfazer) | CT-04 (`assertGuest`) |
| M8 | `TelaLogin` só registrada no `/app`; `/admin` continua com a tela crua | CT-04 roda no painel `admin` |
| M9 | página do aviso renderiza mesmo sem flash (texto fixo "desativada" para qualquer visitante) | CT-07 |
| M10 | `try/catch` engole a exceção e devolve `null` sem redirect nem erro | CT-05 (erro no campo ausente) e CT-04 (sem redirect) |

---

## Regra R3 — Toda tentativa de inativo fica registrada, no log e na trilha, uma vez

> `RQ-05` · perfil **completo** · técnica: **rastreio de efeito** — o QUE: `Log::channel('autenticacao')`
> nível `warning` e uma linha `login_successful = false` em `authentication_log` do usuário; as
> direções: aconteceu / não aconteceu quando não devia / uma só vez

```gherkin
  Regra: Toda tentativa de inativo fica registrada, no log e na trilha, uma vez

    Cenário: [CT-08] @premissa a tentativa do inativo vai ao channel de autenticação com o motivo
      Dado um usuário "admin" inativo com a senha "password"
      Quando ele envia o formulário de login com e-mail e senha corretos
      Então o channel "autenticacao" recebe um "warning" cujo contexto tem o id do usuário
      E o contexto distingue o caso como conta inativa

    Cenário: [CT-09] a tentativa do inativo vira exatamente uma linha de falha na trilha de acessos
      Dado um usuário "admin" inativo com a senha "password" e nenhuma linha na trilha
      Quando ele envia o formulário de login com e-mail e senha corretos
      Então a trilha de acessos dele tem exatamente 1 linha
      E essa linha é de login malsucedido
      E o usuário ativo do CT-06, depois de entrar, tem 0 linhas malsucedidas
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M11 | log emitido só pelo interceptor, sem `motivo` (ou com `motivo` fixo `conta_excluida`) | CT-08 (contexto distingue) |
| M12 | evento `Failed` disparado à mão **também** para o inativo (o Filament já disparou) → 2 linhas | CT-09 ("exatamente 1") |
| M13 | evento `Failed` nunca disparado para o caso do inativo (por exemplo, ao filtrar `ativo` nas credenciais sem repor o evento) | CT-09 ("1 linha") |
| M14 | linha da trilha gravada como sucesso (`Login` disparado e depois `logout`) | CT-09 ("malsucedido") |

---

## Regra R4 — Excluído que digita a senha certa vê a data; senha errada recebe o genérico; a tentativa fica registrada

> `RQ-08`, `RQ-09`, `RQ-10` · perfil **completo** · técnica: **tabela de decisão** (excluído ×
> senha) e **rastreio de efeito**

```gherkin
  Regra: Excluído que digita a senha certa vê a data; senha errada recebe o genérico; a tentativa fica registrada

    Cenário: [CT-10] @premissa excluído com a senha certa vê quando foi excluído e não ganha sessão
      Dado um usuário "admin" excluído logicamente em "12/08/2026"
      Quando ele envia o formulário de login do painel "admin" com e-mail e senha corretos
      Então nenhuma sessão é aberta
      E a resposta é um redirecionamento para a página de aviso
      E a página de aviso diz que a conta foi "excluída", mostra "12/08/2026" e pede contato com o "administrador"

    Cenário: [CT-11] @premissa excluído com a senha errada recebe o erro genérico
      Dado um usuário "admin" excluído logicamente
      Quando alguém envia o formulário de login com o e-mail dele e a senha "errada"
      Então nenhuma sessão é aberta
      E o campo de e-mail exibe o erro genérico de credenciais
      E não há redirecionamento

    Cenário: [CT-12] @premissa a tentativa do excluído fica no log com o motivo e vira uma linha de falha na trilha
      Dado um usuário "admin" excluído logicamente com nenhuma linha na trilha
      Quando ele envia o formulário de login com e-mail e senha corretos
      Então o channel "autenticacao" recebe um "warning" cujo contexto distingue o caso como conta excluída
      E a trilha de acessos dele tem exatamente 1 linha, malsucedida
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M15 | busca do interceptor sem `withTrashed()` — excluído nunca é achado, recebe o genérico | CT-10 |
| M16 | data omitida (aviso do excluído igual ao do inativo) | CT-10 ("12/08/2026") |
| M17 | aviso do excluído sem `Hash::check` | CT-11 |
| M18 | `Failed` não disparado para o excluído (o Filament não o acha, `$user = null`) → 0 linhas | CT-12 |
| M19 | `motivo` do excluído gravado como `conta_inativa` (o `if (! $ativo)` avaliado antes de `trashed()` num excluído que também está inativo) | CT-12 com o alvo **também inativo** (excluído vence — dataset de uma linha) |

---

## Regra R5 — O login social recusa inativo e excluído com o mesmo aviso, nos dois ramos e na confirmação, sem efeito colateral

> `RQ-06`, `RQ-09`, `RQ-10` · perfil **completo** · técnica: **partição** (ramo do e-mail, ramo do
> vínculo, link de confirmação) × **estado** (inativo, excluído); **rastreio de efeito** (sessão,
> vínculo, notificação, `ultimo_acesso_em`, trilha)

```gherkin
  Regra: O login social recusa inativo e excluído com o mesmo aviso, nos dois ramos e na confirmação, sem efeito colateral

    Cenário: [CT-13] inativo sem vínculo volta do provedor e cai no aviso, sem vínculo criado e sem e-mail
      Dado um usuário inativo com e-mail "ja.tem@example.com" e nenhum vínculo social
      E o Google devolve esse e-mail verificado com o identificador "sub-1"
      Quando o callback do Google é aberto
      Então nenhuma sessão é aberta
      E a resposta redireciona para a página de aviso, que diz "desativada"
      E não existe vínculo para "sub-1"
      E nenhuma notificação foi enviada

    Cenário: [CT-14] inativo já vinculado é recusado pelo ramo do vínculo, sem registrar acesso
      Dado um usuário inativo com e-mail "Ja.Tem@Example.com" já vinculado ao Google como "sub-1"
      E o Google devolve "sub-1" com o e-mail em minúsculas
      Quando o callback do Google é aberto
      Então nenhuma sessão é aberta
      E a resposta redireciona para a página de aviso
      E o "ultimo_acesso_em" do vínculo continua nulo

    Cenário: [CT-15] excluído volta do provedor e vê a data da exclusão
      Dado um usuário com e-mail "ja.tem@example.com" excluído logicamente em "12/08/2026"
      E o Google devolve esse e-mail verificado com o identificador "sub-1"
      Quando o callback do Google é aberto
      Então nenhuma sessão é aberta
      E a página de aviso diz "excluída" e mostra "12/08/2026"
      E não existe vínculo para "sub-1"

    Cenário: [CT-16] o link de confirmação do modo estrito não entra numa conta inativa
      Dado o modo estrito de vínculo ligado e um usuário inativo sem vínculo
      E um link assinado de confirmação para esse usuário e o identificador "sub-1"
      Quando o link é aberto
      Então nenhuma sessão é aberta
      E não existe vínculo para "sub-1"
      E a resposta redireciona para a página de aviso

    Cenário: [CT-17] a recusa social fica no log com o provedor e vira uma linha de falha na trilha
      Dado um usuário inativo com e-mail "ja.tem@example.com" e nenhuma linha na trilha
      E o Google devolve esse e-mail verificado
      Quando o callback do Google é aberto
      Então o channel "autenticacao" recebe um "warning" cujo contexto tem o provedor "google" e distingue conta inativa
      E a trilha de acessos dele tem exatamente 1 linha, malsucedida
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | checagem só no ramo do e-mail; o ramo do vínculo faz `Auth::login()` | CT-14 |
| M21 | checagem depois de `registrarAcesso()` | CT-14 (`ultimo_acesso_em` nulo) |
| M22 | checagem depois de `VinculoSocial::vincular()` / do `notify()` | CT-13 (sem vínculo, sem notificação) |
| M23 | `contaCom()` sem `withTrashed()` — excluído cai em "não há conta" e volta ao login com notificação | CT-15 (redirect para o aviso, com a data) |
| M24 | `confirmarVinculo()` sem a checagem | CT-16 |
| M25 | recusa social sem disparar `Failed` (o Socialite não passa pelo guard) | CT-17 |
| M26 | comparação de e-mail sem `lower()` no ramo do vínculo (o vínculo é por `sub`, mas o excluído cai no ramo do e-mail) | CT-14 com e-mail em caixa mista |

---

## Regra R6 — Desativar e reativar são ações do `/admin`, cada uma com permissão própria, idempotentes, e desativar recusa a própria conta e o último `master_global`

> `RQ-07` · perfil **padrão**, técnica **escalada** para estado × evento completo · **matriz
> papel × ação** (`admin` com/sem permissão) · **guardas** exercidas **direto no model**, não só na
> tela

| Estado \ evento | desativar | reativar |
|---|---|---|
| ativo | → inativo (CT-18) | no-op (CT-21) |
| inativo | no-op (CT-21) | → ativo (CT-20) |
| a própria conta (ativo) | **recusado** (CT-22) | — |
| último master_global ativo | **recusado** (CT-23) | — |

```gherkin
  Regra: Desativar e reativar são ações do /admin, cada uma com permissão própria e idempotentes

    Cenário: [CT-18] @premissa o administrador com a permissão desativa um usuário pela lista
      Dado um administrador com a permissão "Desativar:User" e um usuário ativo "alvo@example.com"
      Quando o administrador executa a ação "desativar" na linha do alvo
      Então o alvo tem "ativo" igual a falso
      E o channel "autenticacao" recebe um "info" com o id do alvo e o id do executor

    Esquema do Cenário: [CT-19] sem a permissão, a ação não aparece e o alvo continua como estava
      Dado um administrador cujo papel perdeu "<permissao>" e um usuário "<estado>" "alvo@example.com"
      Quando o administrador abre a lista de usuários
      Então a ação "<acao>" existe na definição da tela e está oculta na linha do alvo
      E o alvo continua "<estado>"
      E a ação "edit" da mesma linha continua visível

      Exemplos:
        | permissao       | acao      | estado  |
        | Desativar:User  | desativar | ativo   |
        | Reativar:User   | reativar  | inativo |

    Cenário: [CT-20] @premissa o administrador com a permissão reativa um usuário inativo
      Dado um administrador com a permissão "Reativar:User" e um usuário inativo
      Quando o administrador executa a ação "reativar" na linha dele
      Então o usuário tem "ativo" igual a verdadeiro
      E ele volta a poder acessar o painel do próprio papel

    Esquema do Cenário: [CT-21] a transição repetida não faz nada, e não registra nada
      Dado um usuário "<estado>"
      Quando o método "<metodo>" é chamado duas vezes seguidas
      Então o usuário continua "<estado_final>"
      E o channel "autenticacao" recebeu exatamente 1 "info" dessa transição

      Exemplos:
        | estado  | metodo    | estado_final |
        | ativo   | desativar | inativo      |
        | inativo | reativar  | ativo        |

    Cenário: [CT-22] @premissa ninguém desativa a própria conta — nem pela tela, nem direto
      Dado um administrador autenticado com a permissão "Desativar:User"
      Quando ele chama "desativar" na própria conta
      Então uma exceção é lançada com mensagem legível
      E a conta dele continua ativa
      E na lista de usuários a ação "desativar" está oculta na própria linha, e visível na linha de outro usuário ativo

    Esquema do Cenário: [CT-23] @premissa o último master_global ativo não pode ser desativado; com dois, o primeiro pode
      Dado <masters> ativos e um administrador com a permissão "Desativar:User"
      Quando o administrador chama "desativar" no primeiro master_global
      Então o resultado é "<resultado>"
      E o primeiro master_global tem "ativo" igual a <ativo_depois>

      Exemplos:
        | masters                        | resultado          | ativo_depois |
        | 1 master_global                | exceção legível    | verdadeiro   |
        | 2 master_global                | desativado         | falso        |
        | 2 master_global, 1 já inativo  | exceção legível    | verdadeiro   |

    Cenário: [CT-24] as duas permissões existem e nascem no papel certo
      Dado os dois seeders de permissões executados
      Quando a matriz é consultada
      Então "Desativar:User" e "Reativar:User" existem no banco
      E "admin" tem as duas
      E "panel_user" e "infra" não têm nenhuma das duas
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M27 | `->authorize()` esquecido na Action (aberta para quem abre a lista) | CT-19 (ação visível sem a permissão) |
| M28 | `->authorize('update')` copiado do "aprovar" em vez de permissão própria | CT-19 (admin com `Update:User` e sem `Desativar:User` veria a ação); CT-24 |
| M29 | `desativar()` sem a saída antecipada — segundo `save()` e segundo log | CT-21 ("exatamente 1") |
| M30 | guarda da própria conta só em `->visible()`, não no model | CT-22 (chamada direta) |
| M31 | contagem de masters ignora `ativo` (conta o inativo como reserva) | CT-23, terceira linha |
| M32 | contagem de masters inclui o próprio (`whereKeyNot` esquecido) — nunca é "o último" | CT-23, primeira linha |
| M33 | permissão nova registrada em `custom_permissions` sem painel → não chega a papel nenhum | CT-24 ("admin tem") |

---

## Regra R7 — Excluir é lógico: a linha fica, some das consultas, e entra na lixeira

> `RQ-08` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
  Regra: Excluir é lógico

    Cenário: [CT-25] excluir pela lista do /admin marca a data, esconde das consultas e cria o item da lixeira
      Dado um administrador autenticado com "Delete:User" e um usuário "alvo@example.com"
      Quando o administrador executa a ação "delete" na linha do alvo
      Então a linha do alvo continua na tabela de usuários, com "deleted_at" preenchido
      E uma consulta comum por e-mail não o encontra
      E existe um item de lixeira do tipo usuário apontando para o alvo, com "deleted_by" igual ao administrador
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | `SoftDeletes` ausente (exclusão física) | CT-25 ("linha continua") |
| M35 | `Recyclable` ausente — soft delete sem item de lixeira | CT-25 ("existe um item") |

---

## Regra R8 — O excluído é restaurável pelo `/admin` e pelo `/infra`, com permissão, e volta a entrar

> `RQ-11` · perfil **padrão** · técnica: **2-switch** (excluir → restaurar → entrar), **matriz
> papel × ação**, **guarda arquitetural**

```gherkin
  Regra: O excluído é restaurável pelo /admin e pelo /infra, e volta a entrar

    Cenário: [CT-26] @premissa restaurar pela lista do /admin devolve a conta, que volta a autenticar
      Dado um administrador com "Restore:User" e um usuário "admin" excluído logicamente
      Quando o administrador filtra a lista por "só excluídos" e executa a ação "restore" na linha dele
      Então o usuário tem "deleted_at" nulo
      E o item de lixeira dele não existe mais
      E ele consegue entrar no painel "admin" com a senha "password"

    Cenário: [CT-27] sem "Restore:User" a restauração não aparece
      Dado um administrador cujo papel perdeu "Restore:User" e um usuário excluído logicamente
      Quando o administrador filtra a lista por "só excluídos"
      Então a ação "restore" está oculta na linha do excluído
      E o usuário continua excluído

    Cenário: [CT-28] @premissa a Lixeira do /infra lista o usuário excluído e o restaura
      Dado um operador "infra" autenticado e um usuário excluído logicamente
      Quando ele abre a Lixeira e executa a restauração no item do usuário
      Então o usuário tem "deleted_at" nulo
      E ele volta a poder acessar o painel do próprio papel

    Cenário: [CT-29] toda model com exclusão lógica está na Lixeira e cria o item dela
      Dado as models de "app/Models" que usam SoftDeletes
      Quando a lista de models da Lixeira do painel "infra" é lida
      Então cada uma dessas models está na lista
      E cada uma usa a trait que grava o item da lixeira
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M36 | `TrashedFilter` ausente — o excluído não aparece para ser restaurado | CT-26 (filtro) |
| M37 | `RestoreAction` sem passar pela policy (autorização default `null`) | CT-27 |
| M38 | `User::class` fora de `->models()` da Lixeira | CT-28, CT-29 |
| M39 | `Projeto` continua sem `Recyclable` (a dívida) | CT-29 |
| M40 | restauração não limpa `ativo`/não devolve acesso (por exemplo, restaurar também marcando inativo) | CT-26 ("consegue entrar") |

---

## Regra R9 — A tela mostra o estado e filtra por ele

> `RQ-01`, `RQ-07` · perfil **mínimo** · técnica: **partição exaustiva do estado exibido**

```gherkin
  Regra: A tela mostra o estado e filtra por ele

    Esquema do Cenário: [CT-30] a coluna Situação distingue os três estados
      Dado um usuário <estado>
      Quando o administrador abre a lista de usuários do /admin
      Então a coluna de situação da linha dele mostra "<rotulo>"

      Exemplos:
        | estado                | rotulo   |
        | pendente de aprovação | Pendente |
        | inativo               | Inativo  |
        | ativo e aprovado      | Ativo    |

    Cenário: [CT-31] o filtro de inativos mostra só os inativos
      Dado um usuário ativo e um usuário inativo
      Quando o administrador aplica o filtro "Somente inativos"
      Então vê o inativo e não vê o ativo
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M41 | coluna continua binária (Pendente/Ativo) — inativo aparece "Ativo" | CT-30 |
| M42 | filtro com `where('ativo', true)` | CT-31 |

---

## Regra R10 — E-mail de conta na lixeira fica reservado

> `RQ-08` (consequência; ADR-05) · perfil **padrão** · técnica: **unicidade + soft delete**
> (checklist de taxonomia)

```gherkin
  Regra: E-mail de conta na lixeira fica reservado

    Cenário: [CT-32] @premissa criar usuário com o e-mail de uma conta excluída é recusado pela validação
      Dado uma conta "alvo@example.com" excluída logicamente e um administrador com "Create:User"
      Quando ele tenta criar um usuário com o e-mail "alvo@example.com" pela tela do /admin
      Então o formulário exibe erro no campo de e-mail
      E continua existindo uma única linha com esse e-mail, a excluída
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M43 | `->unique()` trocado por `Rule::unique()->withoutTrashed()` — o `create` estoura na constraint (500) ou cria duplicado | CT-32 |

---

## Regra R11 — O README documenta os três estados, o aviso, a reativação e a restauração, em PT e EN

> `RQ-12` · perfil **mínimo**

```gherkin
  Regra: O README documenta a feature em PT e EN

    Esquema do Cenário: [CT-33] o README tem a seção e cita cada mecanismo
      Dado o arquivo "<arquivo>"
      Quando ele é lido
      Então contém um título de seção sobre usuário "<estado>"
      E cita "<reativar>", "<lixeira>" e "<aviso>"

      Exemplos:
        | arquivo      | estado                    | reativar   | lixeira  | aviso      |
        | README.md    | ativo, inativo e excluído | Reativar   | Lixeira  | senha      |
        | README.en.md | active, inactive and deleted | Reactivate | Recycle bin | password |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M44 | só o README PT atualizado | CT-33, segunda linha |
| M45 | seção sem explicar a decisão de segurança (senha) | CT-33 ("senha") |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | não se aplica: as ações recebem o registro da própria tabela do `/admin`, que lista todos; a horizontalidade (organização) é do `/app`, onde não há ação |
| Autorização exercida na ação (não só `can()`) | CT-19, CT-27 |
| Idempotência (ancorada no agregado) | CT-21 (`ativo` e contagem de log) |
| Concorrência | lacuna declarada: duas abas desativando o último master ao mesmo tempo — a guarda lê e grava sem lock; SQLite em memória não permite arranjar a corrida. Ceiling registrado no model com `ponytail:` |
| Fronteira no ponto de entrada (gravação) | não se aplica: `ativo` não é campo de formulário; só transição por método |
| Domínio condicionado | CT-19 (estado × permissão), CT-23 (contagem × estado) |
| Estado × operação de escrita (excluído ainda funciona?) | CT-03, CT-10, CT-15, CT-32 |
| Ausente ≠ null ≠ vazio | não se aplica: sem campo opcional novo |
| Paginação / ordenação | não se aplica: nenhuma coluna ordenável nova |
| Timezone / DST | CT-10 e CT-15 fixam `Carbon::setTestNow` às 10:00 — a data exibida é a do fuso do app; deslocamento de fuso na data de exclusão fica como **lacuna declarada** (a data vem de `deleted_at` já no fuso do app; um caso à meia-noite não distinguiria porque o kit grava e lê no mesmo fuso) |
| Unicode / limite de varchar | não se aplica: nenhum texto livre novo |
| Unicidade + soft delete | CT-32 |
| CRUD combinado (excluir duas vezes, restaurar não excluído) | CT-21 (transição repetida); restaurar quem não está excluído é impedido pela própria `RestoreAction` (só visível em trashed) — CT-27 prova a ocultação no eixo da permissão |
| Mass assignment | `ativo` fora do `$fillable` — coberto por construção; CT-18/CT-20 provam que a transição é por método. Um caso explícito (`User::create([... 'ativo' => false])` ignora a chave) entra como linha extra de CT-21? **Não**: entra como CT-35 abaixo |
| Upload | não se aplica |
| Precisão monetária | não se aplica |

```gherkin
    Cenário: [CT-35] "ativo" não se escreve por atribuição em massa
      Dado os dados de criação de um usuário contendo "ativo" igual a falso
      Quando o usuário é criado por atribuição em massa
      Então ele nasce ativo
```

Mutante: M46 — `ativo` posto no `$fillable` "para o formulário gravar" → CT-35.

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | inativo negado no painel do papel (4 linhas) | R1 | estado × painel | Feature (model) | `tests/Kit/SituacaoDaContaTest.php` | M1 |
| CT-02 | ativo entra | R1 | controle | Feature | idem | M2 |
| CT-03 | excluído negado (instância trashed) | R1 | estado | Feature | idem | M3 |
| CT-34 | sessão aberta barrada no request seguinte | R1 | estado × operação | Feature (HTTP) | idem | M4 |
| CT-04 | inativo + senha certa → aviso, sem sessão | R2 | decisão | Livewire + HTTP | idem | M6, M7, M8 |
| CT-05 | senha errada / inexistente → genérico (2 linhas) | R2 | decisão | Livewire | idem | M5, M10 |
| CT-06 | ativo entra pelo formulário | R2 | controle | Livewire | idem | M10 |
| CT-07 | aviso sem flash → `/` | R2 | partição | HTTP | idem | M9 |
| CT-08 | log com motivo (inativo) | R3 | efeito | Livewire + spy | idem | M11 |
| CT-09 | trilha: exatamente 1 falha | R3 | efeito | Livewire + DB | idem | M12, M13, M14 |
| CT-10 | excluído + senha certa → aviso com data | R4 | decisão | Livewire + HTTP | idem | M15, M16 |
| CT-11 | excluído + senha errada → genérico | R4 | decisão | Livewire | idem | M17 |
| CT-12 | log + trilha do excluído (alvo também inativo) | R4 | efeito | Livewire + spy + DB | idem | M18, M19 |
| CT-13 | social: inativo pelo e-mail | R5 | partição × estado | HTTP | `tests/Kit/LoginSocialContaIndisponivelTest.php` | M22 |
| CT-14 | social: inativo pelo vínculo, caixa mista | R5 | partição × estado | HTTP | idem | M20, M21, M26 |
| CT-15 | social: excluído com data | R5 | partição × estado | HTTP | idem | M23 |
| CT-16 | social: link de confirmação de inativo | R5 | partição | HTTP | idem | M24 |
| CT-17 | social: log + trilha | R5 | efeito | HTTP + spy + DB | idem | M25 |
| CT-18 | desativar pela ação | R6 | papel × ação | Livewire | `tests/Kit/SituacaoDaContaTest.php` | — (controle positivo de M27) |
| CT-19 | sem permissão: oculta, sem efeito (2 linhas) | R6 | papel × ação | Livewire | idem | M27, M28 |
| CT-20 | reativar pela ação | R6 | estado × evento | Livewire | idem | — (controle) |
| CT-21 | transição repetida (2 linhas) | R6 | idempotência | Feature (model) + spy | idem | M29 |
| CT-22 | própria conta | R6 | guarda | Feature (model) + Livewire | idem | M30 |
| CT-23 | último master (3 linhas) | R6 | guarda | Feature (model) | idem | M31, M32 |
| CT-24 | permissões na matriz | R6 | papel × ação | Feature | idem | M28, M33 |
| CT-25 | excluir é lógico + item da lixeira | R7 | efeito | Livewire + DB | `tests/Kit/LixeiraTest.php` | M34, M35 |
| CT-26 | restaurar no /admin e voltar a entrar | R8 | 2-switch | Livewire + Livewire | idem | M36, M40 |
| CT-27 | restaurar sem permissão | R8 | papel × ação | Livewire | idem | M37 |
| CT-28 | restaurar pela Lixeira do /infra | R8 | 2-switch | Livewire | idem | M38 |
| CT-29 | guarda arquitetural SoftDeletes ⇒ Recyclable + lista | R8 | arquitetura | Feature | idem | M38, M39 |
| CT-30 | coluna Situação (3 linhas) | R9 | partição exaustiva | Livewire | `tests/Kit/SituacaoDaContaTest.php` | M41 |
| CT-31 | filtro de inativos | R9 | partição | Livewire | idem | M42 |
| CT-32 | e-mail reservado | R10 | unicidade + soft delete | Livewire | `tests/Kit/LixeiraTest.php` | M43 |
| CT-33 | README PT e EN (2 linhas) | R11 | presença | Feature (arquivo) | `tests/Kit/SituacaoDaContaDocumentacaoTest.php` | M44, M45 |
| CT-35 | `ativo` fora do mass assignment | taxonomia | mass assignment | Feature (model) | `tests/Kit/SituacaoDaContaTest.php` | M46 |

Estouro de teto declarado: R6 tem 7 cenários (teto `padrão` = 3) porque a técnica foi escalada
para estado × evento com duas guardas e a matriz de permissão em dois verbos — "verbo irmão não
herda evidência". R5 tem 5 (teto `completo` = 5). R2 e R4 têm 4 e 3.

## Sem CT-B

- Motivo: nenhum cenário afirma sobre JavaScript executado, console, acessibilidade, cor ou
  layout. O redirect do Livewire é asserido no componente; o aviso é HTML servido por Blade e é
  provado por HTTP; as ações e filtros são teste de componente Livewire. A regra do par (tela de
  escrita) não se aplica: nenhuma tela `create`/`edit` ganhou campo.

## Revisão Adversarial

Executada por sub-agente independente (perfil `completo` em A1), com entrada `00` + `04` apenas.
Resultado registrado abaixo, após a rodada.

### Rodada 1

_(preenchido após a revisão)_
