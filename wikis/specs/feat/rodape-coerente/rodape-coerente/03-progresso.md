# Progresso — Rodapé coerente

## 1. `App\Support\AssinaturaDoRodape` — o ponto único

- [ ] Classe criada com `partes(bool $comVersao): array`
- [ ] Composição na ordem: `© {ano} {Nome}` · `v{versão}` · `kit {versão}`
- [ ] Cada parte entra só quando `filled()`
- [ ] A classe **não** decide a audiência (ADR-02)

## 2. A blade da assinatura

- [ ] `git mv versao-do-kit.blade.php` → `assinatura-do-rodape.blade.php`
- [ ] Referência atualizada em `ConfiguraFilamentGlobal`
- [ ] A guarda se estreita: envolve só a versão, não o bloco
- [ ] Saída **escapada** (ADR-05)
- [ ] Docblock reescrito: o que mudou e por quê

## 3. O recado do login muda de hook

- [ ] Sai de `AUTH_LOGIN_FORM_AFTER`, entra em `FOOTER` com `scopes:`
- [ ] O escopo lista **as duas** classes de login (V6/V7)
- [ ] Os botões sociais **continuam** em `AUTH_LOGIN_FORM_AFTER`

## 4. O prefixo `v` no campo

- [ ] `->prefix('v')` em `versao_do_sistema`
- [ ] `helperText` de `nome_da_aplicacao` corrigido — ele **subdeclara** a exposição, e o nome
      passa a ser publicado em página anônima (achado da derivação do `04`)

## 5. Documentação e CHANGELOG

- [ ] `docs/pt/` e `docs/en/`
- [ ] `CHANGELOG.md` em `[Unreleased]` — **sem tag** (RQ-09)

## Testes

- [ ] `04-casos-de-teste.md` derivado
- [ ] Casos escritos conforme o `04`

## Verificação Final

- [ ] `vendor/bin/pint --dirty --format agent`
- [ ] `vendor/bin/filacheck --fix`
- [ ] `vendor/bin/pest tests/Kit/VersaoNoRodapeTest.php --compact`
- [ ] `php artisan test --testsuite=Kit,Tenancy --parallel` — regressão obrigatória
- [ ] `vendor/bin/phpstan analyse`
- [ ] Captura de arte reconferida
- [ ] `/code-review high main...HEAD` + passe de eixos (step 6.5)
- [ ] `feature-quality-gate` (step 8)
- [ ] `composer bp:off`

## Conformidade com Rules

| Rule | Glob que casou | Aplicada / n.a. / violada | Evidência |
|---|---|---|---|
| `app.md` | `app/**` | — | a preencher |
| `filament.md` | `app/Filament/**` | — | a preencher |
| `views.md` | `resources/views/**` | — | a preencher |
| `css-filament.md` | `resources/css/filament/**` | — | a preencher |
| `testes.md` | `tests/**` | — | a preencher |
| `specs.md` | `wikis/specs/**` | — | a preencher |

## Quality Gate

<!-- Enquanto vazio, a feature NÃO está concluída e o PR não abre. -->

- **Ciclo**: — · **Veredito**: — · **Data**: —

## Despachos

| # | Step | Agente / tarefa | Modelo | Não recebeu | Resultado | Auditoria do retorno |
|---|---|---|---|---|---|---|
| 1 | 3 | `planning-filament` (Blueprint) — APIs do Filament | o da sessão | — | V1–V7, todas verificadas na fonte instalada | as 7 conferidas por `grep`/`sed` direto |
| 2 | 4 | derivação do `04` — lê e segue `feature-test-design` | opus | o `01` inteiro (só Superfície de UI, Verificações e paths) | 21 cenários, 7 regras, 57 mutantes, 4 sem matador | estrutura conferida (nenhuma seção duplicada); contagem reconciliada por `grep -cE`; `[CT-22]` lido |
| 3 | 4 | revisão adversarial (dentro do #2) | opus | só `00` + `04` | 3 rodadas; derrubou 3 dos 4 eixos que a sessão pediu | ver `## Notas de Implementação` |

## Auditoria Pré-Implementação

### Revisão profunda (step 5) — premissas do plano contra o código real

| Premissa do plano | O que o código diz | Correção |
|---|---|---|
| `TextInput` aceita `prefix()` | `HasAffixes.php:prefix:56`, e é **só exibição** | confirmada |
| `FOOTER` é emitido por dois layouts | `index.blade.php:126` e `simple.blade.php:61`, e **só** eles | confirmada |
| hook sem escopo vem antes do escopado | `ViewManager.php:renderHook:94` | confirmada — é o que resolve a ordem |
| escopar na `TelaLogin` basta | `getRenderHookScopes()` devolve a classe **concreta**, e a unificada **estende** a outra | **corrigida**: o escopo lista as duas |
| o `©` vai sem ano | — | **revertida pelo usuário**: ano corrente. ADR-04 reescrita |

## Notas de Implementação

### O que a revisão adversarial derrubou

Dos quatro eixos que a sessão mandou atacar, **três foram falseados**:

| Eixo | O que passava nos cenários originais |
|---|---|
| Guarda | a guarda escrita **por rota** (`routeIs('*login*')`) — verde porque toda superfície pública testada era tela de login, e **vazando de verdade** em recuperação de senha e registro |
| Escopo | só havia "escopar de menos"; faltava o espelho — escopar na mãe **e** na filha **duplica** tudo em `/login` |
| Ordem | assinatura e recado concatenados no **mesmo nó** passariam em "antes"; agora exige dois blocos irmãos |

E três defeitos do próprio conjunto: um cenário **autocontraditório** (ficaria vermelho contra a
implementação correta), um **vacuamente verdadeiro**, e **dez** cenários medindo o `rodapeDe()`,
que inclui o **snapshot do Livewire** — onde `app.name` aparece serializado, satisfazendo
"presença" com a faixa inexistente.

### `[CT-22]` é o antídoto da regra que a própria feature criou

A decisão do ano corrente obriga todo cenário a **congelar o tempo**, senão a suíte quebra sozinha
em 1º de janeiro. Mas congelar em 2026 é exatamente o que **cega** o conjunto para um `'© 2026 '`
cravado na blade — esse literal passaria em todos os outros.

`[CT-22]` atravessa a virada, e ganhou uma quarta linha que ninguém pediu e que é a mais
discriminante: com o app em `America/Sao_Paulo`, o instante `2027-01-01 02:30 UTC` **ainda é
31/12/2026** lá. É a janela de três horas em que ler o ano em UTC e no fuso da aplicação dão
respostas diferentes.

### Achados fora do escopo pedido

- **Dois casos existentes ficam vermelhos**, e não é defeito da entrega. Um deles vira
  **restrição de desenho**: o elemento do recado tem de continuar condicional ao recado
- `segmentoDaVersao()` (`tests/Kit/VersaoNoRodapeTest.php:102`) casa `<div class="kit-versao">` por
  regex e **falha aberto** — não casando, devolve string vazia em silêncio, e o comparador fica
  verde sobre nada. O docblock dele já narra que *"a quarta redação deste oráculo nasceu inerte"*
- O `helperText` de `nome_da_aplicacao` **subdeclara** a exposição

## Retrospectiva

<!-- Preenchido no fim. -->
