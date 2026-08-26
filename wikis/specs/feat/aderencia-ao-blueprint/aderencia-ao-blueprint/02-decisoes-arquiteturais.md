# Decisões Arquiteturais — Aderência ao Blueprint

## ADR-01: Norma de planejamento traduzida em norma de auditoria

**Status**: Aceita · **Data**: 2026-08-26

### Contexto

O Blueprint escreve para quem vai *planejar* uma feature. Suas 23 referências dizem "o plano deve
conter X" — não "o código deve ter X". Aplicá-las literalmente a um kit existente daria zero
achados (nenhum plano a avaliar) ou achados absurdos ("o código não inclui a URL da doc").

### Decisão

Extrair de cada referência as **perguntas com resposta sim/não sobre código** (43 normas em
`05-norma-blueprint.md`), com a origem citada, e declarar `N/A` com motivo o que é só forma de
plano. O comparativo mede contra a extração, não contra o texto bruto.

### Alternativas

1. **Auditar só com a skill `filament-security-audit`** — descartada: já feita na v0.20.0, e cobre
   segurança; o pedido é aderência total (arquitetura, qualidade, docs).
2. **Aplicar as referências literalmente** — descartada pelo motivo do contexto.

### Consequências

- Positiva: cada veredito do comparativo aponta para uma linha do Blueprint. Discordar do achado é
  discordar da tradução, e a tradução está escrita.
- Negativa: a extração é minha leitura. Onde ela erra, o achado erra junto. Mitigado citando o
  trecho de origem em toda norma.

---

## ADR-02: `PermissaoDaTela::permite()` passa a falhar fechado

**Status**: Aceita · **Data**: 2026-08-26

### Contexto

`permite()` devolve `true` quando a chave da página não está no mapa do Shield
(`PermissaoDaTela.php:70-72`). O código declara isso como falha aberta aceitável, e o
`InfraPanelProvider.php:251` repete a declaração. Durante a auditoria, uma sonda em processo único
fez o `once()` do Shield congelar o mapa no painel errado, e **todas** as páginas do `/infra`
passaram a abrir sem permissão. Em request HTTP real isso não acontece (medido: 403 via Playwright).

### Decisão

Fechar: sem chave resolvida **e** com usuário autenticado, `permite()` devolve `false`. Sem usuário
continua delegando ao `parent::canAccess()` — a página de painel já exige `auth`, e não é papel deste
predicado decidir sobre anônimo.

### Alternativas

1. **Manter aberto, documentado** — é o estado atual. Descartado porque a condição que torna a falha
   alcançável (mapa tocado antes do `SetUpPanel`) não está sob controle do kit: qualquer plugin ou
   provider novo pode disparar `FilamentShield::getPages()` no boot.
2. **Remover o `once()` do Shield** — não é nosso; e o memo é correto para request.
3. **Ligar `discover_all_pages`** — resolve o mapa, mas muda a matriz de permissões gerada (páginas
   de todos os painéis aparecem em toda tela de papel) e é decisão de produto, não de segurança.

### Consequências

- Positiva: a classe inteira de "página com o trait mas fora do mapa" deixa de existir como buraco.
- Negativa: página nova com o trait cuja permissão não foi gerada (ex.: esquecida no
  `shield:generate`) passa a **negar** em vez de abrir. É o comportamento certo — e o sweep de
  `PermissoesDeTelasTest` já pega página sem permissão.
- Risco: `DescobreCardsDoPainel` fora de request passa a esconder mais cartões. Desejado.

---

## ADR-03: `preventFilePathTampering` global NÃO entra nesta rodada

**Status**: Aceita · **Data**: 2026-08-26

### Contexto

A dica §5 da v0.20.0 continua válida (N-36): três `FileUpload` não-Spatie, `FILESYSTEM_DISK=local`.
Um campo novo que esqueça `->disk()` cai no disco dos anexos privados.

### Decisão

Não aplicar agora. Registrar como dívida com condição de disparo.

### Alternativas

1. **Aplicar o default global** — a skill do Blueprint é explícita: **o passo 2 é obrigatório** —
   enumerar toda fonte de preenchimento (`default()`, `mutateFormDataBeforeFill`, `$set`) e
   excluí-la com `allowFilePathUsing`. O kit tem `mutateFormDataBeforeFill` em `ConfiguracoesDoKit`
   (zera segredos) e `mutateRecordDataUsing` em ações. Fazer isso sem medir cada fonte quebraria
   upload de logo em produção com a suíte verde. É trabalho de wiki própria.

### Consequências

- Negativa: a janela continua aberta para o **próximo** campo de upload mal configurado.
- Mitigação: rule nova exige `->disk()` explícito em todo `FileUpload` (uma linha, verificável por
  grep), o que fecha a condição de disparo sem tocar no tampering.

---

## ADR-04: Enforço automático antes de prosa, em três lugares

**Status**: Aceita · **Data**: 2026-08-26

### Contexto

A auditoria achou que **100% das pages e widgets** têm permissão consultada — e que **5 de 9
resources** nunca tiveram a permissão revogada num teste. A diferença não é cuidado: é que existe um
sweep de teste para pages e widgets, e nenhum para resources. Onde há enforço, o kit é perfeito; onde
há só rule em prosa, ele falha.

### Decisão

Três sweeps novos, um por classe de achado:

1. `PermissoesDeResourcesTest` — autorização negativa sobre `getResources()` dos 3 painéis.
2. `AderenciaAoBlueprintTest` — varredura de fonte por `ignoreRecord: true`, `->reactive(`,
   `Filament\Forms\Get`, helpers depreciados.
3. `EscopoFailClosedTest` — todo resource do `/app` devolve vazio sem tenant.

As rules novas apontam para os testes em vez de repetir a regra.

### Alternativas

1. **Só rules** — é o que existia para resources, e não funcionou.
2. **Só testes, sem rules** — o agente que lê a rule antes de escrever poupa o ciclo vermelho.

### Consequências

- Positiva: resource novo sem policy, `ignoreRecord` reintroduzido, ou resource do `/app` sem
  fail-closed quebram o CI. A rule vira lembrete, não barreira.
- Negativa: três arquivos de teste a mais, com âncora de população cada — o sweep vazio passa calado
  e este projeto já registrou isso como rule.

---

## ADR-05: Sub-agentes medem; eu decido e corrijo

**Status**: Aceita · **Data**: 2026-08-26

### Contexto

Três sub-agentes de auditoria devolveram 61 itens. Dois deles estavam **errados** (`general.md` no
índice; `@laravel/multiplex` no `package.json`) e eu marquei um como confirmado antes de checar.

### Decisão

Todo veredito de agente que muda gravidade ou vira correção é re-verificado por mim com uma segunda
medição antes de entrar no comparativo. Agentes de **correção** recebem lista fechada e proibição de
reescrever fora do item.

### Consequências

- Positiva: o comparativo tem a coluna "re-verificado ✓" e uma seção de refutadas.
- Negativa: custo — mas menor que corrigir um README para um "erro" que não existe.
