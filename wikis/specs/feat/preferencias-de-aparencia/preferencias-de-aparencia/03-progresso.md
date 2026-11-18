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
| `/app/meu-perfil` sem organização no endereço | `route:list`: `app/meu-perfil` | P-13 escrita a partir disto |
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

## Revisão do Diff (step 9)

## Conformidade com Rules

## Despachos

| # | Step | Rota | Modelo | Contexto | Resultado | Custo | Auditoria |
|---|---|---|---|---|---|---|---|
| 1 | 3 | `Explore` (revisão do código para RQ-14) | não exposto pelo host | contexto novo, só o objetivo e as pastas | — | — | — |

## Candidatos a Rule
