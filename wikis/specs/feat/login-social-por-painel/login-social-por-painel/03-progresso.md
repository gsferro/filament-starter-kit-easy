# Progresso — Login social por painel

> Wiki criada em 2026-09-02, na branch `feat/login-social-por-painel`.
> **Fidelidade do requisito: média.** As três ambiguidades materiais foram **respondidas pelo
> mantenedor** antes de o plano ser escrito: A1 (permissão **e** destino), A2 (default todos os
> painéis), A4 (os três painéis). A2 e A5 seguem como premissas assumidas, com o "Se negado" no `00`.

## 0. Antes de implementar

- [x] Requisito capturado verbatim e decomposto em RQ-01..RQ-06
- [x] Levantamento do código: `ConfiguracaoDoLogin`, o render hook global, as rotas fora do painel,
      os seis `getPanel('app')` e o mecanismo de sessão `login_social.contexto`
- [x] **A1 respondida**: a entrega inclui o destino, não só a permissão
- [x] **A2 respondida**: default é todos os painéis (feature nasce inerte)
- [x] **A4 respondida**: os três painéis entram na escolha
- [ ] Confirmar **A3** (settings do kit como fonte — assumido, ADR-07) e **A5** (quem entra sem
      papel do painel cai no comportamento atual do painel)
- [x] `04-casos-de-teste.md` recebido da `feature-test-design` — 26 cenários, 8 regras, 39
      mutantes, 2 sem matador (declarados), com revisão adversarial fechada
- [x] **Seis perguntas devolvidas pela derivação incorporadas ao `00`** (A6..A11), e uma delas
      corrigiu um **defeito da própria wiki** — ver a tabela da revisão profunda

## 1. `config/kit.php` — a chave `paineis` por provedor

- [ ] Quatro chaves, no padrão de lista do `.env` que o kit já usa (`kit.convites.lembretes_dias`)
- [ ] Vazio devolve `[]`; a tradução "vazio = todos" é de quem lê, não da config (ADR-04)
- [ ] `.env.example` com as quatro chaves vazias, ao lado das que já existem por provedor

## 2. `ConfiguracoesDoKit` — a quarta propriedade por provedor

- [ ] A propriedade `array` por provedor, nome de `ProvedorSocial::propriedadeDeSettings('paineis')`
- [ ] A linha no `mapaDeConfiguracao()` por provedor
- [ ] Migration de settings **NOVA** (nunca editar a de 2026-08-25 que já rodou)
- [ ] Fora de `encrypted()` — não é segredo
- [ ] `tests/Kit/ConfiguracoesDoKitTest.php` continua verde (há caso que assere que toda
      propriedade declarada é semeada)

## 3. `ConfiguracaoDoLogin` — a terceira condição

- [ ] `disponivel(ProvedorSocial $provedor, ?string $painel = null)` — default nulo preserva os
      chamadores anteriores
- [ ] `painelAutorizado()` público — o controller precisa distinguir as duas recusas no log
- [ ] Lista vazia = todos (ADR-04); `in_array` **estrito**
- [ ] Docblock reescrito: a pergunta passou a ser por provedor **e** painel

## 4. O blade dos botões passa o painel corrente

- [ ] `filament()->getCurrentOrDefaultPanel()?->getId()` — o padrão do kit em tela de auth
- [ ] `disponiveis($painel)` e `'painel' => $painel` no link, dentro do `array_filter` existente
- [ ] **Nenhum nome de diretiva com arroba nos comentários** do blade (`.ai/rules/views.md`)

## 5. `LoginSocialController` — valida, carrega na sessão, usa como destino

- [ ] `painelDaRequisicao()` — lista branca de `Paineis::opcoes()`
- [ ] A barreira no `redirecionar()`: painel não autorizado → `warning` + 404 (RQ-05)
- [ ] O painel entra no `login_social.contexto`, junto de `org`/`token`
- [ ] `painel(?string $id)` — o da sessão quando válido, o **default** quando não (ADR-06)
- [ ] Os seis `getPanel('app')` parametrizados **um por um** (a tabela do PRD diz o porquê de cada)
- [ ] `retorno()` e `confirmarVinculo()` **sem** reconferência por painel (ADR-05)
- [ ] O painel de destino no contexto dos logs de sucesso que já existem

## 6. A tela de settings ganha o campo por provedor

- [ ] `Select::multiple()` com `Paineis::opcoes()`, um por provedor
- [ ] `helperText('Vazio = todos os painéis.')` — a decisão da ADR-04 precisa estar na tela
- [ ] `->visible()` casado com o toggle do provedor, como os campos de credencial já fazem

## 7. Documentação e changelog

- [ ] `docs/pt/autenticacao/login-social.md` — a escolha de painéis e o destino
- [ ] `docs/en/autenticacao/login-social.md` — a mesma, em inglês
- [ ] `CHANGELOG.md` → `### Adicionado` (a escolha) **e** `### Alterado` (o destino mudou)
- [ ] Conferir antes se outra branch editou os arquivos de doc nesta rodada

## Testes

- [ ] `04-casos-de-teste.md` derivado do `00` pela `feature-test-design`
- [ ] Cenários nos vizinhos (`tests/Kit/LoginSocial*`) ou arquivo próprio, conforme o `04`
- [ ] Suíte `tests/Tenancy` para o `/app` com tenancy (o destino resolve organização default)
- [ ] Helper novo usado por mais de um arquivo vai para `tests/Pest.php` (`.ai/rules/testes.md`)

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/phpstan analyse`
- [ ] `php artisan test --compact tests/Kit/LoginSocial*Test.php tests/Kit/VinculoDeProvedorSocialTest.php`
- [ ] `php artisan test --compact tests/Kit/ConfiguracoesDoKitTest.php tests/Kit/ConfiguracoesDoKitTelaTest.php`
- [ ] `php artisan test --compact --testsuite=Tenancy`
- [ ] `php artisan test --compact tests/Kit/SiteDeDocumentacaoTest.php`
- [ ] `vendor/bin/pest --parallel --tia`
- [ ] À mão: Google só no `/admin` → botão fora do `/app/login`; `?painel=app` forjado → 404;
      entrada pelo `/admin` termina no `/admin`
- [ ] `git commit`

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada na wiki |
|---|---|---|
| "o painel corrente resolve no `redirecionar()`" | **falso** — a rota é global (`routes/web.php:61-70`), fora do painel; `getCurrentOrDefaultPanel()` cairia no **default**, que é o defeito que a feature corrige | ADR-03 descartou essa alternativa explicitamente; o painel passou a vir da query |
| "basta filtrar o botão" | o `redirecionar()` é rota pública e a query é entrada do usuário — filtro de UI não é barreira (`.ai/rules/filament.md:19-29`) | RQ-05 e o passo 5a; a barreira é no servidor |
| "o destino é configurável" | **falso** — `Filament::getPanel('app')` está escrito em seis pontos, e um deles é o destino de sucesso (`:645-648`) | o achado que mudou o escopo; A1 foi levada ao mantenedor |
| "os seis pontos são equivalentes" | não são: quatro são "volta ao login", um é destino de sucesso, um é o perfil (e consulta `hasTenancy()`) | o passo 5d trata um por um, com tabela |
| "preciso de uma constante de painéis" | `App\Support\Paineis::opcoes()` (`:62-70`) já devolve exatamente a lista, de `Filament::getPanels()` | ADR-02: reuso, nenhuma constante |
| "monto o nome da propriedade por concatenação" | `ProvedorSocial::propriedadeDeSettings()` (`:120`) já faz, e trata o hífen do LinkedIn | passos 2 e 6 a reusam |
| "propriedade de settings pode ser lida no boot" | armadilha medida: `registro_verificar_email` gravava e não fazia nada (`ConfiguracoesDoKit.php:318-330`) | ADR-07: a leitura é por request, nos dois pontos |
| "vazio pode significar nenhum painel" | seria desligar o login social de toda instalação existente num update | ADR-04: vazio = todos |
| "posso editar a migration de settings existente" | instalação de terceiro que só roda `migrate` ficaria sem a linha e estouraria `MissingSettings` no boot de todo request (`:66-68`) | passo 2: migration **nova**, obrigatoriamente |
| **"`getPanel()` com id inexistente lança"** (ADR-06, primeira versão) | **não lança nesta versão**: `PanelRegistry::get()` devolve `null` no modo estrito (`vendor/filament/filament/src/PanelRegistry.php:36-44`) | **achado da derivação**, que abriu o vendor como `.ai/rules/specs.md` manda. Justificativa errada, conclusão certa: sem a guarda o `->getUrl()` estoura `Error ... on null`, e o observável é 500. ADR-06 corrigida com o `file:line` |
| **"painel inexistente na query responde 404"** (ADR-03, primeira versão) | **contradizia o próprio PRD**: o código do passo 5b faz `painelDaRequisicao()` devolver `null` para painel inexistente, e o `abort` é condicionado a `$painel !== null` — ou seja, segue no default | **achado da derivação dos casos de teste (A6)**, não da minha revisão. O código estava certo e a prosa errada: 404 ali transformaria um link antigo sem `painel` na mesma resposta de uma tentativa negada. ADR-03 e `01` corrigidos, com o motivo escrito |

### Auditoria Ponytail (step 6)

| # | Sugestão de corte | Aplicada? | Onde |
|---|---|---|---|
| — | **Nenhuma.** Revisor independente auditou os quatro arquivos e devolveu `Lean already. Ship.` | — | — |

Auditado explicitamente, a pedido: os sete passos do PRD, as sete ADRs, os três métodos novos
(`painelAutorizado()`, `painelDaRequisicao()`, `painel()`), as quatro propriedades de settings — uma
por provedor — e a tabela dos seis pontos de `getPanel('app')` do passo 5d. Nenhum foi apontado
como enxerto, cerimônia ou reinvenção do que o kit já entrega.

## Blockers

- [ ] **`04-casos-de-teste.md` pendente** — derivação delegada à `feature-test-design`; a
      implementação não começa antes dele (a barreira de RQ-05 é o cenário que mais importa)
- [ ] **A3 e A5 não confirmadas** — as duas seguem como premissa assumida, com o "Se negado"
      registrado. Nenhuma das duas bloqueia os passos 1-5.

## Desvios do Plano

<!-- Pós-implementação -->

## Notas de Implementação

<!-- Pós-implementação -->

## Retrospectiva

- **Funcionou bem**: levantar o código **antes** de escrever o plano. O achado dos seis
  `getPanel('app')` mudou a feature de "uma condição a mais" para "o fluxo conhece o painel", e foi
  levado ao mantenedor como decisão em vez de assumido em silêncio — que era o erro mais provável
  aqui, porque a segunda linha do requisito autoriza a leitura estreita.
- **Faltou no plano**: nada ainda.
