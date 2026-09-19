# Plano de Ação — `/public` nunca aparece na URL antes do painel

> Requisito: `00-requisito.md`

## Natureza da Wiki

- **Tipo**: correção
- **Wiki ancestral**: — (não há wiki anterior sobre geração de URL; a correção nasce da
  investigação registrada no `00`)
- **Motivo**: instalação em hospedagem cujo `DocumentRoot` aponta para a raiz do projeto produz
  `/public` no início de toda URL gerada durante aquele request
- **Toca infra compartilhada?**: **sim** — geração de URL vale para os três painéis e para toda
  rota da aplicação. A regressão é obrigatória mesmo sendo "correção".

## Cobertura do Requisito

| RQ | Cláusula | Passo(s) | Observação |
|----|----------|----------|------------|
| RQ-01 | `/public` nunca precede o painel | 1, 2 | — |
| RQ-02 | vale para `/admin`, `/infra`, `/app` | 2 | coberto por dataset nos CTs |
| RQ-03 | vale para painel novo do usuário do kit | 1 | a correção é na **raiz da URL**, não numa lista de painéis — é isto que a satisfaz |
| RQ-04 | investigação e atribuição da causa | — | **já entregue** no `00`, seção "Achado da investigação" |
| RQ-05 | kit, branch própria, PR para a `main` | 4 | branch `fix/url-sem-prefixo-public` |
| RQ-06 | só remove com evidência positiva | 1 | *(Adendo 1)* `deveRemover()`; CT-09, CT-10 |
| RQ-07 | sem evidência, não age | 1 | *(Adendo 1)* falha segura; CT-09, CT-10 |
| RQ-08 | o operador declara por configuração | 1 | *(Adendo 1)* `kit.url.remover_sufixo_public`; CT-11, CT-12 |

## Objetivo

Tornar o kit imune a uma hospedagem cujo `DocumentRoot` aponta para a raiz do projeto em vez de
`public/`. Hoje, nessa configuração, basta um request chegar por `/public/...` para que **todas** as
URLs daquela página nasçam prefixadas.

A correção não tenta consertar o servidor: ela recusa honrar uma base de URL que termine em
`/public`, que é a assinatura exata dessa configuração.

## Contexto

Ver `00-requisito.md` → "Achado da investigação". Em uma linha: o Symfony deriva a base do request
comparando `SCRIPT_NAME` com `REQUEST_URI`; depois da reescrita interna do `.htaccess` os dois
divergem, e a base vira `/public` quando o endereço pedido também traz o prefixo.

## Análise dos Arquivos Existentes

### `bootstrap/app.php` *(alterado em 2026-09-18: ADR-05)*
O registro do middleware global. `append()` o coloca **depois** do `TrustProxies`, e a posição é
requisito — ver ADR-05 e CT-13.

### ~~`app/Providers/KitServiceProvider.php`~~ — **descartado**
O plano previa um método `configure*()` no provider "cola" do kit. **Não é mais lá.** O `boot()`
de provider roda **antes** do `TrustProxies`, e ler `getSchemeAndHttpHost()` ali congelaria host e
porta sem os cabeçalhos `X-Forwarded-*` — atrás de proxy entregando em 8080, a raiz forçada
apontaria para uma porta que o navegador não alcança. Achado 2 do `/code-review`; ADR-05.

### `app/Support/BooleanoDoEnv.php` *(acrescentado em 2026-09-18: QA-02)*
Ganha `ouNulo()`, irmão tri-estado do `comPadrao()` que o kit já tinha. A chave nova precisa
distinguir "ausente" de "desligado", e o `comPadrao()` sempre devolve booleano.

## Autorização

Sem impacto — a correção não lê usuário, papel nem tenant.

## Rotas

Nenhuma rota nova. A correção muda a **raiz** com que as rotas existentes são renderizadas.

## Superfície de UI

**Sem superfície de UI própria.** A correção não desenha tela; ela muda o endereço com que toda
tela é linkada. O efeito é observável em qualquer painel, e é medido por teste de componente/HTTP.

## Superfície Livewire

Sem página, widget ou componente novo. Nenhum método público novo exposto ao cliente, nenhuma
propriedade pública nova. A tabela do `02` registra isso explicitamente em vez de omitir.

## Variáveis de Ambiente

*(alterado em 2026-09-18: o Adendo 1 criou RQ-08, e com ele a chave.)*

| Key | Default | Descrição |
|-----|---------|-----------|
| `KIT_URL_REMOVER_SUFIXO_PUBLIC` | *(ausente)* = detecta | `true` encurta sempre (nginx), `false` nunca. Ausente, vazia ou ilegível caem na detecção — `BooleanoDoEnv::ouNulo()` |

A correção **não** usa `APP_URL` — decisão da ADR-02.

## Eventos / Listeners / Observers

Nenhum.

## Jobs / Queues

Nenhum. Em fila não existe request HTTP, o método sai cedo e a geração de URL segue usando
`APP_URL`, como já era.

## Modelo de Execução

| Pergunta | Resposta |
|---|---|
| Quantos requests a tela custa? | não altera — a correção roda **uma vez por request**, no boot do provider |
| O que é adiado, e por qual gatilho? | nada |
| O que é memoizado por request? | nada *(alterado em 2026-09-18: o memo estático foi cortado pela auditoria Ponytail — a leitura do `.htaccess` só acontece quando a base já veio com o sufixo, ou seja nunca em instalação correta)* |
| O que é cacheado entre requests? | nada |
| Custo do caminho principal | **zero query**. Em instalação correta a base é vazia e o middleware sai na primeira condição, sem tocar disco |

## Impacto em Features Existentes

- **Todos os painéis**: a raiz de URL passa a ser forçada **somente** quando a base termina em
  `/public`. Em instalação correta o comportamento é idêntico ao de hoje — é o que CT-02 afirma.
- **Testes do kit**: `Request::create()` em teste produz base vazia, então nenhum teste existente
  passa a cair no ramo novo.

## Rollback

Remover o `append(RaizDeUrlSemPublic::class)` de `bootstrap/app.php` *(alterado em 2026-09-18)*.
Sem migration, sem dado, sem estado — e CT-13 fica vermelho, que é o desejado: o rollback é
visível, não silencioso.

## Dependências

Nenhuma nova.

## Riscos

- ~~**Aplicação que vive legitimamente sob um caminho terminado em `public`** seria o caso
  quebrado visto de outro ângulo~~ — *(alterado em 2026-09-18: **a premissa era falsa**. Um
  servidor com `DocumentRoot` na raiz e **sem** reescrita entrega a mesma base `/public` e
  funciona; encurtar ali quebra tudo. Ver Adendo 1 do `00` e ADR-05. A mitigação passa a ser a
  exigência de evidência positiva, coberta por CT-09 e CT-10.)*
- **Sufixo parecido não é sufixo**: `/meupublic` e `/publicacoes` não podem ser tocados —
  CT-05.
- **Proxy reverso que termina o TLS**: `getSchemeAndHttpHost()` depende de `TrustProxies` estar
  configurado. O kit já depende disso em qualquer geração de URL absoluta — a correção não piora
  nem melhora esse ponto. Declarado na ADR-03.

## Channel de Log da Feature

**Nenhum channel novo, e nenhum log.** A decisão está na ADR-04: o método roda em **todo request**
e o ramo quebrado dispara em **todos** eles numa instalação mal configurada — logar produziria uma
linha por request, para sempre, sem acrescentar informação depois da primeira.

## Estrutura de Implementação

### 1. O middleware `RaizDeUrlSemPublic` *(alterado em 2026-09-18: era `configureRaizDeUrl()` no `KitServiceProvider` — ver ADR-05)*

> Skills: `laravel-best-practices`, `ponytail`

- **Path**: `app/Http/Middleware/RaizDeUrlSemPublic.php` *(alterado em 2026-09-18: ADR-05 —
  era um método no `KitServiceProvider`, e `boot()` roda antes do `TrustProxies`)*
- Anexado ao stack **global** em `bootstrap/app.php`, o que garante a execução depois do
  `TrustProxies`
- Lógica *(alterado em 2026-09-18: o guard de console caiu — ver abaixo)*:
  1. ler `$request->getBaseUrl()`
  2. se **não** terminar em `/public`, sair (é o caminho comum; custo zero)
  3. senão, `URL::forceRootUrl($request->getSchemeAndHttpHost().<base sem o sufixo>)`

> **O guard `runningInConsole()` foi cortado.** Ele estava previsto aqui como primeira linha.
> Medido em `artisan`: `SCRIPT_NAME` é `artisan`, a base sai **vazia** e o teste de sufixo já
> devolve falso — o guard não protegia nada, custava uma condição e tornava a etapa impossível de
> exercitar, porque a suíte roda em console. É a escada do Ponytail aplicada durante a execução.
- Sem log (ADR-04)

### 2. Cobertura por teste

> Skills: `pest-testing`

- **Path**: `tests/Kit/UrlSemPrefixoPublicTest.php`
- Cenários em `04-casos-de-teste.md`

### 3. Documentação

- `docs/pt/recursos/configuracao-global-filament.md` e o par em `en`: seção curta dizendo qual é a
  configuração **certa** de `DocumentRoot` e que o kit se defende da errada
- `CHANGELOG.md`: entrada em *Corrigido*

### 4. Entrega

- Branch `fix/url-sem-prefixo-public`, criada a partir de `origin/main` **em worktree próprio**,
  porque outra sessão trabalha no checkout principal
- PR para a `main` com o link da wiki e o veredito do quality gate

## Verificação Final

- [ ] `/ponytail:ponytail-review` no diff
- [ ] `vendor/bin/pint --dirty`
- [ ] `php artisan test tests/Kit/UrlSemPrefixoPublicTest.php`
- [ ] `composer test:kit` — regressão obrigatória (toca infra compartilhada)
- [ ] **Custo medido** — zero query no caminho comum
- [ ] **`/code-review` no diff (step 7.5)**
- [ ] Falsificabilidade provada por mutante
- [ ] Docs pt/en e CHANGELOG reconciliados

## Commits

- `:bug: fix(url): nunca exibir /public antes do painel`
- `:memo: docs(url): configuracao certa de DocumentRoot`
