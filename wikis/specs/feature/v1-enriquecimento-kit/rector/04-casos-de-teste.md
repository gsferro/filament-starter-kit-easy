# Casos de Teste — Rector no pipeline de qualidade

> Derivados do `00-requisito.md`. Arquivo: `tests/Kit/QualidadeDeCodigoTest.php`.

## Sem CT-B

Não há superfície de UI: a entrega é dependência de desenvolvimento, um arquivo de configuração e
documentação. Nenhum cenário afirma sobre algo que só o navegador prova.

## Perfil de risco

O que esta feature entrega é, quase todo, **uma decisão** — e decisão não tem comportamento para
testar. O que **tem** é o inverso: o risco de a decisão ser desfeita por engano, meses depois, por
alguém que acha que "faltou o Rector no lint".

Esse é um risco específico e cruel: **acrescentar o Rector ao `composer test` deixa o build verde na
primeira rodada.** A briga com o PHPStan só aparece quando alguém tocar num dos 7 arquivos do
`CarbonToDateFacadeRector` — e aí o sintoma é "o PHPStan quebrou", num commit que não mexeu em tipo
nenhum.

| Dimensão | Risco | Coberto por |
|---|---|---|
| Rector entra no gate | **alto** — silencioso até quebrar | CT-01 |
| Gate perde uma das três ferramentas | alto | CT-02 |
| Set de qualidade ligado em definitivo | **alto** — mesmo efeito do CT-01 | CT-04 |
| Comando some do `composer.json` | médio | CT-03 |
| Cache do Rector sujando a raiz | baixo | CT-05 |

## Mapa de regras

| Regra | Enunciado | Técnica |
|---|---|---|
| R1 | Nenhum comando de Rector roda em `composer test` | asserção sobre config |
| R2 | O gate tem exatamente os três: pint, phpstan, filacheck | composição |
| R3 | `refactor:preview` e `refactor:apply` existem como comando | partição por script |
| R4 | Nenhum set de qualidade está ligado no `rector.php` | partição por set |
| R5 | O cache do Rector fica fora da raiz | asserção sobre config |

## Casos

| CT | Regra | Cenário | Oráculo |
|----|-------|---------|---------|
| CT-01 | R1 | Ler `scripts.test` do `composer.json` | não contém `rector` nem `refactor` |
| CT-02 | R2 | idem | contém `@lint:check`, `@types:check`, `@filament:check` |
| CT-03 | R3 | Ler `scripts` do `composer.json`, por dataset | `refactor:preview` e `refactor:apply` existem |
| CT-04 | R4 | Ler `rector.php` **sem os comentários**, por dataset de set | nenhum dos 6 sets/métodos aparece |
| CT-05 | R5 | Ler `rector.php` | contém `withCache` e `storage/framework/cache/rector` |

**11 casos** ao todo (CT-03 e CT-04 são datasets).

### CT-04 é o caso que exige cuidado, e por quê

O `rector.php` **cita** os sets de qualidade de propósito, no bloco de instruções do topo — é lá que
está escrito qual ligar em cada tipo de upgrade. Uma asserção ingênua sobre o texto do arquivo
falharia por causa da documentação.

Por isso o caso remove os comentários antes de afirmar:

```php
$codigo = preg_replace('~/\*.*?\*/~s', '', $this->rector) ?? '';
expect($codigo)->not->toContain($set);
```

**Citar não é ligar.** Sem essa distinção, o teste puniria exatamente a documentação que torna a
decisão utilizável.

## Mutantes previstos e o cenário que mata cada um

| # | Mutante | Morto por |
|---|---------|-----------|
| M1 | Acrescentar `"@refactor:preview"` ao `scripts.test` | **CT-01** |
| M2 | Remover `@filament:check` do gate | CT-02 |
| M3 | Ligar `LARAVEL_CODE_QUALITY` no `withSets()` | **CT-04** |
| M4 | Ligar `withPhpSets(php84: true)` | CT-04 (o dataset inclui `withPhpSets`) |
| M5 | Remover o `withCache()` — cache volta para `.rector.cache` na raiz | CT-05 |
| M6 | Apagar o script `refactor:apply` | CT-03 |
| M7 | Ligar um set de qualidade **dentro de um comentário** | nenhum — **e é o comportamento correto** |

### Lacuna de derivação assumida

**Nenhum caso verifica que o Rector de fato funciona** — que `refactor:preview` roda e não estoura.
Fazê-lo exigiria invocar o binário dentro da suíte, o que custa segundos e depende de o `vendor/`
estar completo.

**Assumido**: a verificação é manual, está na `## Verificação Final` do PRD, e foi executada
(`exit code 0`, nenhuma mudança proposta com os sets desligados). Se o Rector quebrar num upgrade do
próprio pacote, o sintoma aparece na primeira vez que alguém rodar o comando — que é justamente
quando ele importa.

## Cobertura do requisito

| RQ | Coberto por |
|----|-------------|
| RQ-03 (Rector no lint — resposta **não**) | CT-01, CT-02, CT-04 |
| RQ-07 (instalar se agregar valor) | CT-03 |

RQ-01, RQ-02, RQ-04, RQ-05, RQ-06, RQ-08 e RQ-09 são de análise e documentação — não têm
comportamento a testar. A prova deles é o `02-decisoes-arquiteturais.md` e os documentos entregues.

## Execução

```bash
php artisan test tests/Kit/QualidadeDeCodigoTest.php
```

**Resultado em 2026-08-18**: 11 casos, 15 asserções, verdes.
