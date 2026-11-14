# Decisões Arquiteturais — feat: tela de login personalizável

## ADR-01 — Camada dentro do kit, não pacote novo

**Status**: aceita · 2026-11-13

**Portões**: difícil de reverter ✅ (pacote publicado vira contrato de versão para terceiros) ·
surpreendente ✅ (o pedido sugere pacote) · trade-off ✅ (reuso externo × distribuição pelo `kit:update`)

**Contexto**: o pedido manda refletir se a personalização deveria ser um pacote novo baseado no
auth designer ou uma camada no kit.

**Decisão**: camada no kit, sobre o `caresome/filament-auth-designer` sem fork: um decisor em
`app/Support`, um trait nas páginas de autenticação, o partial `media` que o kit já sobrescreve e o
CSS do kit.

**Por quê**:

- O `kit:update` entrega arquivos do kit e **não** aplica `composer.json` (só relata). Um pacote novo
  exigiria `composer require` manual em todo projeto que já usa o kit — exatamente o tipo de passo
  que quebra a atualização
- A personalização depende de peças que são do kit e não fazem sentido fora dele: as configurações
  (spatie settings do kit), a organização (`Tenant`), a identidade (`IdentidadeDoKit`) e as
  permissões. Num pacote, tudo isso viraria contrato de adaptador
- Não há consumidor externo conhecido

**Alternativas**: pacote composer próprio (descartado pelos três motivos); fork do auth-designer
(descartado: os pontos de extensão do pacote bastam — `getAuthDesignerConfig()` público e o partial
`media` sobrescrevível).

**Revisão**: extrair um pacote quando houver um segundo produto querendo só o painel de marca —
nesse momento o pacote leva a apresentação e deixa settings/tenancy no kit.

## ADR-02 — Aparência como objeto inteiro em JSON: um na instalação, um opcional por organização

**Status**: aceita · 2026-11-13 · **substitui o gatilho** da ADR-01 de
`wikis/specs/feature/identidade-visual-da-organizacao/` ("ao terceiro campo, reavaliar JSON")

**Portões**: difícil de reverter ✅ (formato gravado em settings e em `tenants`) · surpreendente ✅
(a identidade da organização é por coluna) · trade-off ✅ (herança campo a campo × simplicidade)

**Decisão**: a instalação guarda `aparencia_das_telas` (array ou nulo) nas configurações do kit; a
organização guarda uma coluna JSON nula com o mesmo formato. Nulo na instalação = layout clássico,
intocado. Nulo na organização = usa a da instalação. Não há herança por campo: a organização usa a
padrão **inteira** ou a sua **inteira**, que nasce como cópia da padrão.

**Por quê**: o pedido é binário ("usar padrão ou customizar por tenant"). Herança campo a campo
(herdar / nenhum / personalizado) triplica o espaço de estados, a interface e os testes sem cláusula
que a peça (veredito do juiz cego, item 1). A cor e a logo da organização continuam nas colunas
próprias e continuam valendo: a aparência só decide layout e textos, e consulta a cor/logo resolvidas.

**Trade-off aceito**: a aparência própria não acompanha mudanças posteriores da padrão. A tela diz isso.

**Leitura tolerante**: o valor gravado passa por um vocabulário fechado a cada leitura; valor
desconhecido nunca derruba a tela de login — cai no clássico com `warning`.

## ADR-03 — O painel de marca nasce no partial `media`, sem publicar o layout do pacote

**Status**: aceita · 2026-11-13

**Portões**: difícil de reverter ✅ (publicar o layout transfere a manutenção do layout inteiro para o
kit, para sempre) · surpreendente ✅ (o "painel" é renderizado onde o pacote põe uma imagem) ·
trade-off ✅ (controle total do HTML × compatibilidade com o pacote e com o CSS existente)

**Decisão**: o layout `painel` é o modo lateral do próprio pacote (`media-left`/`media-right`,
`mediaSize 50%`) com o conteúdo da mídia trocado pelo painel de marca, no partial `media` que o kit
já sobrescreve. O layout `auth.blade.php` do pacote continua sendo o do vendor.

**Por quê**: o `.fi-auth-layout`, as classes `media-*`, o hook `FOOTER` e a regra
`body:has(> .fi-auth-layout)` do kit (rodapé na dobra) continuam iguais, e com eles a detecção de
"tela de autenticação" (dock/chat) e os testes existentes. Publicar o layout traria o arquivo inteiro
para a varredura de views de vendor do `kit:update` e para cada atualização do pacote.

**Consequência**: o partial só é incluído com mídia; por isso o layout `painel` sempre leva uma mídia
(a da base, ou a arte), mesmo quando o fundo é cor — o partial ignora a imagem nesse caso.

## ADR-04 — Preview: página do painel admin, rascunho em cache por usuário e instância de edição

**Status**: aceita · 2026-11-13

**Portões**: difícil de reverter ✅ (rota e contrato de rascunho) · surpreendente ✅ (o preview não
abre a tela de login real) · trade-off ✅ (fidelidade × efeitos colaterais)

**Decisão**: o editor grava o rascunho normalizado no cache, com chave
`aparencia-preview:{usuário}:{uuid da instância}` e o escopo dentro do valor; um iframe abre uma
página do painel `admin`, fora da navegação, que usa o MESMO layout das telas de autenticação, com
formulários demonstrativos desabilitados. A página só lê rascunho do próprio usuário, exige acesso ao
painel admin e autoriza o escopo a cada request. O iframe recarrega quando o rascunho muda e pelo botão
**Atualizar preview**. *(alterado em 2026-11-13: P-17)*

**Por quê**: abrir a rota real de login no iframe dispara redirect de autenticado, escrita de sessão e
rate limit; e a tela não renderiza valores ainda não salvos. A chave por usuário dispensa assinatura
de URL: outra pessoa não alcança o rascunho, e o uuid separa duas abas do mesmo usuário (juiz cego,
item 5).

**Limites aceitos**: o documento do preview roda no contexto do painel `admin` — botões com a cor do
admin, sem os botões sociais e sem o anti-robô. O alternador de tema fica desligado no preview para
não trocar a preferência do próprio admin.

## Superfície Livewire

Greps (`grep -n "public function\|public ?\|public [a-z]" …`) a refazer sobre o código final antes do step 9.

| Ponto | Origem | Quem escreve | Fronteira |
|---|---|---|---|
| `TelaLogin::$orgDaAparencia` | código (novo) | `mount()` pela query | `#[Locked]`; resolvido só com tenancy + painel `app` + organização ativa; só decide aparência, nunca acesso |
| `aparenciaDaTela()`, `corDoPainelDeMarca()`, `logosDoPainelDeMarca()` nas páginas de auth | código (novo, métodos públicos) | cliente chama por `$wire` | cor e logos vazias fora do `painel` (P-16); devolvem só o que a tela mostra |
| `PreviewDasTelasDeAcesso::$rascunho`, `$tela` | código (novo) | `mount()` pela query | `#[Locked]`; `tela` em lista fechada; rascunho só do usuário autenticado |
| `PreviewDasTelasDeAcesso::$data` (formulário demonstrativo) | framework | cliente | campos desabilitados, nenhuma ação; nada é gravado |
| `ConfiguracoesDoKit::$data` — chaves da aba nova | framework | cliente | validação por campo (`in`, `regex`, `maxLength`) no save + `normalizar()` na leitura |
| `ConfiguracoesDoKit` — ação **Atualizar preview** | código (novo, Action de schema) | cliente aciona | autorização da página; grava só no cache, na chave do próprio usuário |
| `EditTenant`/`CreateTenant::$data` — chaves novas | framework | cliente | `TenantPolicy`; mesma validação; toggle não desidratado |
| uuid do rascunho no formulário | código (novo) | cliente pode trocar | chave inclui o id do usuário: trocar o uuid só alcança rascunho próprio |
