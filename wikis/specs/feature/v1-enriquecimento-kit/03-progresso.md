# Progresso — Enriquecimento do kit para a versão 1.0

**Branch**: `feature/v1-enriquecimento-kit` (a partir de `main` em `198ccc9`)
**Concluído em**: 2026-08-18
**Merge**: ✅ **feito** — `main` em `22f1d93` (v0.18.0). RQ-18 pediu para não commitar dentro da sessão; o merge veio depois. `git branch --no-merged main` não lista mais esta branch.

## 0. Preparação

- [x] Branch `feature/v1-enriquecimento-kit` criada
- [x] Recon do `mini-pff` por sub-agente (4 painéis, plugins, views de user menu, plataforma)
- [x] Recon do starter-kit por sub-agente (3 painéis, `app/`, views, testes, wikis, rules, config)

## 1. Revisão de saúde do projeto (RQ-06)

Executada **antes** de qualquer alteração, para que o resultado seja linha de base e não consequência.

- [x] `php artisan test --testsuite=Kit --parallel` → **278 casos, 739 asserções, verde**
- [x] `php artisan test --testsuite=Tenancy --parallel` → **77 casos, 308 asserções, verde**
- [x] `vendor/bin/pint --test --parallel` → **verde**
- [x] `vendor/bin/phpstan analyse` → **0 erros**

> **Veredito de RQ-06: o projeto está saudável.** Nenhuma falha, nenhum aviso de estilo, nenhum erro
> de tipo. Os achados da revisão não são defeitos — são lacunas e faxina, e estão na seção 5 do
> `08-comparativo-mini-pff.md`.

## 2. `App\Models\User::papelDoPainel()`

- [x] Método escrito ao lado de `temPapelDoPainel()`, no bloco "Acesso aos painéis"
- [x] `master_global` resolvido antes da consulta
- [x] Consulta por `papeisEmQualquerContexto()` (ADR-03), não por `roles()`
- [x] Retorno por `getAttribute('name')` — PHPStan limpo
- [x] Docblock diz explicitamente que é **exibição, não autorização**

## 3. As duas views

- [x] `resources/views/filament/perfil-indicator.blade.php` — badge do papel
- [x] `resources/views/filament/user-menu-header.blade.php` — avatar, nome, e-mail, badge
- [x] Rótulo por `App\Support\Papeis::rotulo()`, nunca escrito na view
- [x] Guarda de usuário nulo
- [x] Guarda de papel ausente (não renderiza badge vazio)
- [x] Par claro/escuro explícito nas classes
- [x] `data-user-menu-header` como âncora de teste (ADR-06)

## 4. Registro do render hook nos três painéis

- [x] `AdminPanelProvider`
- [x] `AppPanelProvider`
- [x] `InfraPanelProvider`
- [x] Comentário em cada um explicando por que `USER_MENU_PROFILE_BEFORE` serve aqui e
      `USER_MENU_BEFORE` não serviu no hook irmão

## 5. Testes de componente

- [x] `tests/Kit/CabecalhoDoMenuDoUsuarioTest.php` — CT-01 a CT-11
- [x] `tests/Tenancy/CabecalhoDoMenuDoUsuarioTenancyTest.php` — CT-12 a CT-16

## 6. Teste de browser

- [x] `tests/Browser/CabecalhoDoMenuDoUsuarioTest.php` — CT-B01

## 7. Varredura dos plugins (RQ-08 a RQ-14)

- [x] 7 sub-agentes, páginas **1 a 61**
- [x] **547 plugins** catalogados — bate com o contador do site
- [x] Fim da paginação confirmado (62 e 63 vazias)
- [x] Saída bruta em `varredura/lote-{1..7}-*.md`

## 8. Comparativo `mini-pff` × kit (RQ-04, RQ-05, RQ-07)

- [x] `08-comparativo-mini-pff.md` — o que portar, o que não portar, onde o kit está à frente,
      melhorias próprias e ordem sugerida para a 1.0

## 9. Relatório de pacotes (RQ-10, RQ-11, RQ-16, RQ-20)

- [x] `wikis/pacotes-candidatos.md` — método e limites, instalados, **top 10 com prós e contras**,
      segunda linha, descartados por motivo
- [x] Linkado a partir de `wikis/README.md` e `wikis/pacotes.md`

## 10. Fechamento

- [x] `wikis/specs/main/lembretes-de-convite/getLoginUrl())` apagado (nome corrompido por shell)
- [x] `03-progresso.md` fechado
- [x] `06-relatorio-qa.md` escrito

## Verificação Final

- [x] `/ponytail:ponytail-review` no diff
- [x] `vendor/bin/pint --dirty` → verde
- [x] `vendor/bin/phpstan analyse` → 0 erros
- [x] `php artisan test --testsuite=Kit,Tenancy --parallel` → **388 casos, 1099 asserções, verde**
- [x] `npm run build` + suíte `Browser` → **27 casos: 25 passados, 2 pulados, 0 falhas**, 145 asserções
      (rodada em 4 blocos de arquivos — ver *Notas de Implementação*)
- [x] Roteiro "Desenhado × Implementado" preenchido no `05-casos-de-teste-browser.md`
- [x] `git commit`

---

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O código real diz | Correção aplicada |
|---|---|---|
| "o kit precisa de `Date::use(CarbonImmutable)`, `prohibitDestructiveCommands`, `Password::defaults` do `mini-pff`" | **já existem**, em `KitServiceProvider.php:57-61` | Item removido da lista de portes; movido para "não portar" no `08` |
| "o kit precisa do `configureProcessEnvNoWindows()`" | **já existe** no `KitServiceProvider` | idem |
| "o kit precisa do `isolarScriptDoPulse()`" | **já existe** como `isolarScriptsConflitantes()` | idem |
| "falta wiring do Impersonate" | **já existe** em `UserResource.php:167` | idem |
| "`admin_app` está disponível na suíte `Kit`" | o `PapeisSeeder` só o cria **com tenancy** | dataset movido para a suíte `Tenancy` (CT-12) |
| "o badge pode ler `Filament::getTenant()` como no `mini-pff`" | o eixo do kit é `roles.painel`, não o tenant | ADR-03 reescrita antes do código |

### Auditoria Ponytail (step 6)

| # | Sugestão | Aplicada? | Onde |
|---|---|---|---|
| 1 | Não criar kill-switch de config para 35 linhas de Blade | sim | ADR-04 |
| 2 | Não criar channel de log para leitura de identidade | sim | ADR-05 |
| 3 | Usar `x-filament-panels::avatar.user` em vez de montar `<img>` com fallback | sim | `user-menu-header.blade.php` |
| 4 | Devolver `string` do hook, como o hook irmão, em vez de `View` + import novo | sim | os 3 providers |
| 5 | Não instalar pacote de terceiro sem aprovação | sim | ADR-07 |
| 6 | Não portar `StatsRecursoWidget` (refatoração de 21 arquivos sem ganho visível) | sim | `08`, seção 2.7 |

---

## Blockers

Nenhum.

## Desvios do Plano

- **Passo 3 — o compilador do Blade e o comentário.** A primeira versão do `perfil-indicator`
  mencionava diretivas do Blade **dentro** do comentário `{{-- --}}`, entre crases, como texto
  explicativo. O compilador processa diretivas **antes** de remover o comentário: as menções viraram
  código no arquivo compilado e as três telas de dashboard morreram com
  `ParseError: syntax error, unexpected token ","`, apontando para
  `storage/framework/views/<hash>.php` — longe da causa.
  **Corrigido**: nenhuma diretiva escrita no comentário, e o próprio comentário agora registra a
  armadilha. Candidato a rule de projeto.

- **Passo 3 — expressão de várias linhas em atributo de componente.** A escolha do ícone estava como
  ternário quebrado em linhas dentro de `:icon="..."`. Movida para o bloco PHP no topo da view.
  (Suspeita inicial da falha acima; a causa real era o comentário, mas a mudança ficou porque é mais
  legível.)

- **Passo 5 — `admin_app` na suíte errada.** Detectado pela primeira execução:
  `There is no role named 'admin_app' for guard 'web'`. O papel só existe com tenancy. O dataset
  saiu da suíte `Kit` e virou CT-12 na `Tenancy`.

- **Passo 9 — a paginação não terminava onde se esperava.** O lote 6 foi lançado como "46 até o fim,
  teto na 60" e a 60 ainda tinha 9 itens. Um lote 7 fechou em 61. O aritmético fecha:
  540 + 7 = 547, igual ao contador do site.

## Notas de Implementação

- **`filamentphp.com/plugins` não expõe o nome Composer no card.** Todos os `vendor/pacote` do
  relatório vêm do slug da URL ou de conhecimento do ecossistema. Está declarado como limite na
  seção "Método" do `pacotes-candidatos.md` — ignorar isso levaria a um `composer require` de nome
  inventado.

- **O hook `USER_MENU_PROFILE_BEFORE` é emitido em quatro ramos** do
  `vendor/filament/filament/resources/views/components/user-menu.blade.php` (linhas 92, 105, 128,
  143), conforme a configuração do painel. Só um renderiza por request — se um dia dois renderizarem,
  o CT-B quebra com violação de modo estrito do Playwright, e isso é o comportamento desejado.

- **A classe do gatilho do dropdown é `.fi-user-menu-trigger`** (`user-menu.blade.php:53,61`). É de
  framework, não do kit — se o Filament renomear num major, o CT-B01 é o que avisa.

- **`assertMissing` no plugin de browser significa "não visível"**, não "ausente do DOM" — quem quer
  ausência usa `assertNotPresent`. O par presente-e-invisível do CT-B01 depende dessa distinção.

- **Suíte `Kit,Tenancy` foi de 355 para 388 casos.** Nenhum caso existente precisou de ajuste: o
  cabeçalho é aditivo e não mexe em seletor de teste algum.

- **A suíte `Browser` inteira num comando só foi morta duas vezes** pelo ambiente de execução, sem
  emitir uma linha de resultado (o formato de saída do Pest só escreve no fim). Rodada em 4 blocos
  de arquivos, fecha em ~2,5 min no total e passa:

  | Bloco | Casos | Resultado |
  |---|---|---|
  | Cabeçalho + Gráficos + Hub + Identidade | 4 | 4 ✅ · 19 asserções |
  | Lightbox + Perfis + Tema escuro | 8 | 7 ✅ · 1 pulado · 27 asserções |
  | Telas do kit + Roteiro | 10 | 9 ✅ · 1 pulado · 73 asserções |
  | `BrowserTenancy` | 5 | 5 ✅ · 26 asserções |
  | **Total** | **27** | **25 ✅ · 2 pulados · 0 falhas · 145 asserções** |

  Os 2 pulados são pré-existentes, não desta feature. A primeira versão deste arquivo afirmava
  "23 casos, verde" — número **não verificado**, corrigido aqui.

## Retrospectiva

**Funcionou bem**

- Capturar o requisito verbatim **antes** de nomear a feature evitou o erro óbvio: o pedido tinha 21
  cláusulas, e a leitura apressada teria entregado só a primeira.
- A revisão profunda (step 5) matou **quatro** portes que pareciam necessários e já existiam no kit.
  Sem ela, o `08-comparativo` teria recomendado copiar código duplicado.
- Sete sub-agentes em paralelo cobriram 547 plugins com a saída bruta em disco e só o resumo voltando
  ao contexto — é o formato certo para varredura grande.
- A regra de `CLAUDE.md` sobre dependências transformou o que seria uma decisão minha numa entrega
  melhor: o relatório sobrevive à feature; a instalação não sobreviveria à revisão.

**Faltou no plano**

- O PRD não previu **nenhum** risco de compilação de Blade. A armadilha do comentário custou dois
  ciclos e um erro que aponta para o arquivo errado. Uma linha em `.ai/rules/filament.md` resolve
  para sempre.
- O plano assumiu que a paginação do diretório terminaria por volta da página 46, com base no
  arredondamento de 547/12. Eram 9 cards por página, não 12. Contar antes de dividir teria evitado o
  lote extra.
- Os helpers de teste do kit são fortes, mas **não há documentação de qual papel existe em qual
  suíte**. `admin_app` só com tenancy é o tipo de fato que se descobre falhando.

## Candidatos a Rule de Projeto

Apresentados ao usuário no fim da sessão. **Nenhum gravado sem aprovação.**

1. **Diretiva do Blade dentro de comentário `{{-- --}}` é compilada**
   - Glob: `resources/views/**`
   - Evidência: `03-progresso.md` → Desvios do Plano; a falha real desta feature
   - Gates: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅

2. **`User::papelDoPainel()` é exibição, não autorização**
   - Glob: `app/Models/User.php`, `resources/views/filament/**`
   - Evidência: ADR-02; o docblock do método
   - Gates: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅

3. **Papel do kit disponível por suíte de teste** (`admin_app` só com tenancy)
   - Glob: `tests/**`
   - Evidência: Desvios do Plano, passo 5
   - Gates: durável ✅ · escopável ✅ · não-inferível ✅ · não-redundante ✅
