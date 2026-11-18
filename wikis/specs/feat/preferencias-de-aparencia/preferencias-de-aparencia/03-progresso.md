# Progresso — Roadmap item 1: preferências de aparência por usuário

**Estado**: em planejamento

**Base do PR**: `main`

## 1. Colunas na pessoa e na organização
- [ ] `users.aparencia` (JSON, fora do `$fillable`, em `$hidden`) e `tenants.aparencia_por_usuario` (default `false`)

## 2. Vocabulário e padrões da instalação
- [ ] `TamanhoDaFonte` e `ModoDoMenu`
- [ ] `aparencia_por_usuario`, `tamanho_da_fonte`, `modo_do_menu` nos três lugares do settings, `config/kit.php` e `.env.example`

## 3. O decisor
- [ ] `AparenciaDoUsuario`

## 4. Os quatro consumidores
- [ ] render hook (densidade + fonte), largura do menu, modo do menu e cor nos três painéis

## 5. A tela de configurações
- [ ] seção "Aparência dos painéis" e coerção no save

## 6. A seção "Aparência" do Meu perfil
- [ ] `PreferenciasDeAparencia` registrado nos três painéis

## 7. A chave da organização
- [ ] `Toggle aparencia_por_usuario` no `TenantForm`

## 8. O documento de candidatas a pacote
- [ ] `wikis/candidatas-a-pacote.md` e links

## Testes

## Tickets

## Verificação Final
- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/phpstan analyse` (level 8)
- [ ] testes da feature (Kit e Tenancy)
- [ ] `vendor/bin/pest --parallel --tia` contra a baseline
- [ ] `/code-review high main...HEAD` + passe de eixos (step 9), antes da reconciliação
- [ ] Playwright nos três painéis, sem e com tenancy
- [ ] Desvios propagados ao `01`/`02`/`04` de origem
- [ ] `rastreabilidade.sh {wiki}` silencioso
- [ ] `checkbox-sem-evidencia.sh {wiki}` silencioso
- [ ] `citacoes.sh {wiki}` silencioso
- [ ] `ids-ct.sh {wiki}` silencioso
- [ ] `conformidade-rules.sh {wiki} main` silencioso
- [ ] Docs pt/en, CHANGELOG, roadmap e roteiro de atualização reconciliados
- [ ] `git commit`

## Auditoria Pré-Implementação

- Entendimento confirmado: 2026-11-18 — mantenedor, pelas respostas escritas a Q1–Q4 e "prossiga ate finalizar" — 1 rodada, 4 perguntas na raia requisito (Q1–Q4), 0 na raia desenho (decisões rotineiras sob a autorização), 0 de fato
- Roteiro manual: não — 2026-11-18 — instrução explícita do desenvolvedor ("use o playwrite para navegar no site e validar as correções") — a validação manual é a navegação Playwright da sessão, registrada na Verificação Final, sem `08`

### Revisão profunda (step 5)

| Premissa do plano | O que o código diz | Correção |
|---|---|---|
| sidebar recolhível por Closure no render | `HasSidebar::isSidebarFullyCollapsibleOnDesktop()` faz `evaluate()`; lido em `layout/index.blade.php:12` no render | nenhuma |
| `/app/meu-perfil` sem organização no endereço | `route:list`: `app/meu-perfil` — rodado **sem** tenancy; com `KIT_TENANCY=true` a rota é `/app/{tenant}/meu-perfil` (descoberto pelo CT-12 na implementação) | P-13 escrita a partir disto e corrigida em 2026-11-18 |
| Breezy filtra por `canView()` estático | `HasMyProfile::getRegisteredMyProfileComponents():96` | nenhuma |
| cor da organização no `bootUsing`, registrada depois da do painel | `AppPanelProvider.php:196`; a da pessoa precisa vir depois desta | D5 escrita a partir disto |
| contagem de propriedades do settings travada num teste | `tests/Kit/KitInfoTest.php` âncora `toHaveCount(73)` | o passo 2 sobe para 76 (registrado em Impacto) |
| `TenantPolicy` restringe o Resource | `app/Policies/TenantPolicy.php` existe | nenhuma |

### Varredura da classe irmã

- `App\Livewire\DefinirSenhaPorEmail` (irmã de `PreferenciasDeAparencia`): citada nos três providers (`->myProfileComponents`) e em `tests/Kit/DefinirSenhaPorEmailTest.php`. O componente novo entra nos três providers; nenhuma outra lista
- `App\Support\DensidadeDoLayout` (irmã de `TamanhoDaFonte`/`ModoDoMenu`): `config/kit.php`, migration de settings, `ConfiguracoesDoKit` (classe e tela), três providers, `KitServiceProvider`, `.env.example`, `tests/Kit/DensidadeDoLayoutTest.php`, `tests/Kit/KitInfoTest.php` (âncora). Os dois enums novos entram em `config/kit.php`, settings (três lugares), tela e `.env.example`; a âncora sobe 73 → 76
- `tenants.registro_habilitado` (irmã de `aparencia_por_usuario`): `Tenant::$fillable`/cast, `TenantForm`, `TenantHeader`, `TenantInfolist`. A chave nova entra em `$fillable`, cast e `TenantForm`; `TenantHeader`/`TenantInfolist` não (corte do Ponytail abaixo)

### Auditoria Ponytail (step 6)

Ponytail indisponível como plugin com namespace — passe manual pela skill `ponytail-review` do projeto sobre `01` e `02`:

- `01` passo 7: `delete:` entrada no `TenantInfolist` — o requisito não pede exibir a chave; o formulário basta. Aplicado: passo 7 só no `TenantForm`
- `01` D2: um decisor para quatro consumidores — mantido; quatro cópias da regra das duas chaves seriam o defeito da `.ai/rules/models.md` (fonte única)
- `01` D6: mover a densidade de seção — mantido; é uma linha, e deixar a densidade longe de fonte e menu espalharia a mesma pergunta em duas seções
- `02`: nada a cortar

## Desvios do Plano

- 2026-11-18 — **P-13 corrigida pelo CT-12**: com tenancy, o Meu perfil do `/app` está dentro de uma organização (`/app/{tenant}/meu-perfil`); as linhas D5 e D6 do CT-12 (que abriam `/app/meu-perfil` com tenancy) viraram `@obsoleto` no `04` e saíram do teste; a fronteira "sem organização corrente" continua coberta pela linha D7/D10 (`/admin`) e pelo CT-09 (Kit, `/app` sem tenancy). O código não muda: `AparenciaDoUsuario::liberada()` já decide pela organização corrente
- 2026-11-18 — **defeito achado pelo CT-25 (M49)**: fonte e menu de tipo errado forjados no estado da tela de configurações (`["grande"]`) passavam pelo `in()` do Select, chegavam nulos ao `fill()` do spatie e davam `TypeError` (500) ao salvar. Corrigido coagindo densidade, fonte e menu também em `mutateFormDataBeforeSave()`

- 2026-11-18 — **ordem**: a implementação dos passos 1 a 8 começou em paralelo com a derivação do `04` (step 7), e não depois dela. O derivador recebeu só o `00`, as rotas e a Superfície de UI do `01`, a Superfície Livewire do `02` e o glossário, com a ordem de ler `app/` só para nomes. Para conter o risco de o `04` confirmar o código em vez do requisito: o `04` é auditado por comportamento que só viria de `app/` (nomes de método, textos de log ou de tela que não estão no `01`), passa pelo `fw-adversario-ct`, e a falsificabilidade é provada revertendo o patch de `app/` (`git apply -R`) e vendo os CT ficarem vermelhos
- 2026-11-18 — **erro de leitura do step 3**: a coerção da densidade na tela de configurações mora em `mutateFormDataBeforeFill()`, não em `mutateFormDataBeforeSave()`; corrigido no `01` (Análise e passo 5), no `02` e no comentário do código
- 2026-11-18 — **consequências do documento novo em `wikis/`**: `wikis/candidatas-a-pacote.md` entrou em `KitUpdate::CAMINHOS_DO_KIT` (a varredura de `wikis/*.md` do `KitUpdateTest`), na contagem de documentos de referência dos dois READMEs (12 → 13) e na tabela de `wikis/README.md`

## Revisão do Diff (step 9)

## Conformidade com Rules

## Despachos

| # | Step | Rota | Modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `Explore` (revisão do código para RQ-14) | não exposto pelo host | contexto novo, só o objetivo e as pastas | tabela de 20 candidatas com arquivos, acoplamento e esforço | 113k tokens · 2,5 min | amostragem: 8 arquivos citados conferidos por `ls` (todos existem); 2 chaves de config conferidas por grep e corrigidas no documento (`kit.exibir_versao`, `kit.tabelas.visoes_salvas`) |
| 2 | 7 | `feature-test-design` (derivação do `04`) | `opus` | contexto novo: `00`, rotas e Superfície de UI do `01`, Superfície Livewire do `02`, glossário | — | — | — |

## Candidatos a Rule
