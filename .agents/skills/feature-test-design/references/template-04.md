> Referência da feature-test-design 1.16.0. Lida em: passo 2 (formato ❓/➡️ das perguntas; costuras de
> teste), passo 6 (formato da tabela de mutantes), passo 7 (tabela de cogitado e cortado), escrita
> do `04` (o template inteiro) e pós-implementação (contagem por `grep -c`; teste de arquitetura de
> sincronia de IDs). Fonte única de: o template do arquivo `04-casos-de-teste.md` e o formato das
> perguntas que a derivação gera.

# Template do arquivo 04 — Casos de Teste

**Path**: `wikis/specs/{branch}/{feature}/04-casos-de-teste.md`. As regras que o template carrega
estão no `SKILL.md` §Arquivo 04 e valem mesmo que o template seja adaptado.

## Template

```markdown
# Casos de Teste — {Card}: {Título}

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| {cálculo do desconto} | 3 | 3 | 9 | completo |
| {listagem} | 1 | 1 | 1 | mínimo |

- Técnicas aplicadas: {EP, BVA 3-valores, tabela de decisão, tabela estado × evento}
- Cenários: {n} · Regras: {n} · Mutantes previstos: {n} · Sem matador: {n}
<!-- derivado do arquivo por grep -c (comandos na reference, seção "Contagem do cabeçalho"); recalcular a cada cenário novo, nunca escrever à mão. Contagem manual defasada é a mentira mais barata de produzir -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | {…} | — |
| F | {…} | CT-01, CT-02 |
| D | {…} | CT-03…CT-08 |
| I | {…} | CT-09 |
| P | {não se aplica: sem dependência de plataforma além do banco} | — |
| O | {…} | CT-10 |
| T | {…} | CT-11, CT-12 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — {…} | cálculo (completo) | RQ-01, RQ-04 | BVA 3-valores | CT-01…CT-04 |
| R2 — {…} | listagem (mínimo) | RQ-02 | tabela de decisão | CT-05, CT-06 |
| R3 — {…} | aprovação (padrão) | P-01 | matriz papel × ação | CT-07 |

<!-- Técnica escalada acima do perfil da área: declarar aqui, em uma linha, com o motivo. -->

<!-- RQ com Estado `aberta — Qn` no 00, ou citada em `afeta:` de pergunta de requisito desta
     derivação: uma linha, sem regra e sem cenário. Nenhum passo do 01 a implementa.
     Pergunta nova desta derivação em sub-agente: `(Q?n)` provisório, até a sessão renumerar. -->
- RQ-05 — aberta (Q3), sem cenário até a resposta
- RQ-06 — aberta (Q?1), sem cenário até a resposta

## Costuras de Teste

<!-- Onde cada grupo de CT se prende ao sistema. Uma linha por grupo. Existente > nova.
     Proposta pela derivação, confirmada pelo desenvolvedor (raia desenho) antes dos cenários.
     O 05 existe se e só se alguma linha tem costura `browser`. -->

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Autorização da aprovação | R1, R2 | Pest feature HTTP | existente — {arquivo de teste irmão} | {…} | {quem}, {data} |
| {grupo} | R3 | componente Livewire/Filament | nova — {por que nenhuma costura existente serve} | {…} | {vazio quando proposta de sub-agente} |

<!-- Costura: unit de regra · Pest feature HTTP · componente Livewire/Filament · browser.
     Pest feature HTTP = teste em tests/Feature com a aplicação de pé — por HTTP ou chamando
     action/service/model direto ("por fora do componente"). -->

## Fronteira com o Plano

<!-- O que veio do 01-plano-acao.md e foi RECUSADO como oráculo, para o cenário não virar
     teste do PRD. Item que só o PRD determina e é visível ao usuário vira pergunta. -->

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| {nome do método `aplicarEm()`} | escolha de implementação | detalhe do cenário |
| {texto do erro na tela} | comportamento visível que o requisito não determina | pergunta ao solicitante (raia requisito) |

**Perguntas geradas pela derivação** (a sessão leva as de raia requisito a `00-requisito.md` →
`## Perguntas ao Solicitante`; as de raia desenho, ao desenvolvedor. Em sub-agente, `Q?1, Q?2…`
provisórios; a sessão renumera ao gravar):

❓ Q?1 · raia: requisito · afeta: RQ-06 · depende de: —
{pergunta}
➡️ Recomendação: {resposta recomendada} — {por quê; premissa de comportamento: falha fechado}

## Setup Global

### Personas
- `{papel}` — {como criar, com o helper real do projeto}

### Fixtures
- `{Model}::factory()->{state}()` — {estado}
- **Situação de partida com ciclo de vida: por transições reais.** Helper `{entidade}Em('{situacao}', [...])`
  em `tests/Pest.php` que chama a máquina de estados do domínio (`enviar()`, `aprovar()`…) em vez de
  gravar `situacao` à força. Reimplementar a transição no teste esconde o defeito que o teste existe
  para pegar

### Fakes
- `Queue::fake()` / `Mail::fake()` / `Notification::fake()` / `Http::fake()` + `Http::preventStrayRequests()`

### Estratégia de DB
- {`RefreshDatabase` global no `tests/Pest.php`, ou o que o projeto usa}

---

## Regra R1 — {enunciado da regra}

> `RQ-01`, `RQ-04` · perfil **completo** · técnica: **BVA 3-valores** (fronteira: {campo}, granularidade {tipo})

```gherkin
# language: pt
Funcionalidade: {…}

  Regra: {…}

    Cenário: [CT-01] {comportamento}
      Dado {…}
      Quando {…}
      Então {…}
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | {…} | CT-01 | {a asserção ou o valor do CT que diverge sob o mutante} |
| M2 | {…} | ⚠️ **sem matador** — {motivo} | — (lacuna declarada) |

<!-- Asserção que mata é obrigatória em todo perfil. Vazia = gate não passou. -->

---

## Checklist de Taxonomia

<!-- Resposta válida: um ID de cenário, "não se aplica: {motivo}", ou
     "lacuna declarada: {o que foi tentado}". NUNCA "sim".
     Grupo: a linha (coluna Grupo) de ## Costuras de Teste onde vive o CT (ou viveria o cenário da
     lacuna) — e, por ela, a costura; "não se aplica" leva —. -->

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-09 | {grupo} |
| Autorização exercida na ação (não só `can()`) | CT-19 | {grupo} |
| Idempotência (ancorada no agregado) | CT-07 | {grupo} |
| Concorrência | CT-08 | {grupo} |
| **Fronteira no ponto de entrada** (gravação) | CT-30, CT-31 | {grupo} |
| **Domínio condicionado** (tipo × valor) | CT-08 | {grupo} |
| **Estado × operação de escrita** (excluído ainda funciona?) | CT-21 | {grupo} |
| Ausente ≠ null ≠ vazio | não se aplica: sem campo opcional | — |
| Paginação / ordenação | … | … |
| Timezone / DST | lacuna declarada: tentado `config(['app.timezone'])` divergente; {resultado} | {grupo} |
| Unicode / limite de varchar | lacuna declarada: RQ-05 aberta (Q3) | {grupo} |
| Unicidade + soft delete | … | … |
| CRUD combinado | … | … |
| Mass assignment | … | … |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | CT-02 | {grupo} |
| **Superfície Livewire** (método público, prop pública, estado do framework) | CT-31, CT-32 | {grupo} |
| **Estado do framework usado sem validar** (índice de array, `parse`, coluna) | CT-33 | {grupo} |
| **IDOR por entidade** (uma linha por tabela persistida) | `dashboards`: CT-11 · `dashboard_widgets`: CT-31 | {grupo} |
| **Escopo com discriminante nulo** (fecha ou abre?) | CT-33 | {grupo} |
| **Saída do estado de erro** (4xx/redirect tem destino) | CT-30 | {grupo} |

## Índice de Cenários

<!-- Grupo: a linha de ## Costuras de Teste a que o cenário pertence — escrita, não inferida: a
     mesma regra pode estar em dois grupos. Costura: o valor do enum, o mesmo da linha do grupo. -->

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | {…} | R1 | BVA | Autorização da aprovação | Pest feature HTTP | `tests/Feature/...` | M1 |
| CT-09 | {…} | R3 | matriz papel×ação | {grupo} | componente Livewire/Filament | `tests/Feature/...` | M8, M9 |

## Sem CT-B

<!-- só quando nenhuma linha de ## Costuras de Teste tem costura `browser` -->
- Motivo: {…}
```

## Tabela de mutantes preenchida (passo 6)

Formato da tabela que cada `Regra:` recebe, com um exemplo preenchido (o `CT-04` é o `Esquema do
Cenário` de `references/gherkin.md`: limite 3, `borda` = 3 usos já feitos → recusado):

```markdown
#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|--------------------------------|------------------|-------------------|
| M1 | `<=` no lugar de `<` no limite de usos | CT-04 (linha "borda") | linha `borda` (3, 3): resultado "recusado"; o mutante aceita |
| M2 | contador incrementado antes de validar | CT-06 | depois da recusa, o contador continua no valor do `Dado`; o mutante soma 1 |
| M3 | contador não incrementado no sucesso | CT-05 | depois do uso aceito, o contador é o do `Dado` + 1; o mutante deixa igual |
| M4 | limite lido do cupom mas não comparado | CT-04 | linha `borda+1` (3, 4): "recusado"; o mutante aceita qualquer contagem |
```

A coluna `Asserção que mata` é o que torna o gate falsificável: quem lê confere, sem rodar nada, se
o valor citado diverge mesmo sob o mutante. "CT-04" sozinho é afirmação de quem derivou os dois.

## Perguntas geradas pela derivação (passo 2, princípio 5)

Formato ❓/➡️ — o mesmo da entrevista da `feature-wiki` (step 4), para a sessão levar a pergunta sem
reescrever:

```text
❓ Q?2 · raia: requisito · afeta: RQ-07 · depende de: Q?1
{pergunta}
➡️ Recomendação: {resposta recomendada} — {por quê}
```

- **Raia**: `requisito` (o que o sistema deve fazer quando o texto não diz — só o solicitante
  responde; é a raia da maioria das perguntas da derivação) ou `desenho` (como implementar dado o
  requisito — o desenvolvedor responde). Pergunta de fato (código, schema, config) não se faz:
  descobre-se
- **`afeta:`** — só entra pergunta que toca uma `RQ` ou `P-nn`. A `RQ` citada numa pergunta de
  requisito é tratada como aberta: sem cenário até a resposta
- **Número**: `Qn` é **uma sequência só por feature**, nas três raias. A derivação em sub-agente
  (step 7) não sabe o próximo `Qn` e numera `Q?1, Q?2…` — provisório. A sessão renumera ao gravar,
  continuando do maior `Qn` da wiki: as de requisito vão para `## Perguntas ao Solicitante` do `00`;
  as de desenho aterrissam, com o mesmo `Qn`, em `## Decisões de Desenho` do `01` ou na ADR. A troca
  vale para todo `Q?n` do `04` (linha `RQ-nn — aberta (Q?n)`, `depende de:`, checklist de taxonomia):
  `grep -nE 'Q\?[0-9]+' 04-casos-de-teste.md` vazio depois de gravar. Derivação em linha, na própria
  sessão, já usa o `Qn` final
- **Recomendação** de premissa de **comportamento** (o que o sistema faz quando o texto não diz:
  aceitar ou recusar): falha fechado (`references/tecnicas-por-regra.md` §Premissa),
  igual na entrevista do step 4 da `feature-wiki`

**`00` somente leitura** (`SKILL.md` §Quando o `00-requisito.md` é somente leitura): a seção
`## Perguntas para o 00-requisito.md` do `04` traz as de requisito já no formato da tabela de destino:

```markdown
| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q{n} | {pergunta} | RQ-05 | {resposta recomendada — por quê} | aberta |
```

## Contagem do cabeçalho (`grep -c`)

A linha `Cenários: {n} · Regras: {n} · Mutantes previstos: {n} · Sem matador: {n}` do `## Perfil de
Derivação` sai destes comandos, rodados na pasta da feature — nunca de contagem à mão:

```bash
grep -cE '^[[:space:]]*(Cenário|Esquema do Cenário): \[CT-[0-9]+\]' 04-casos-de-teste.md   # Cenários
grep -cE '^## Regra R[0-9]+' 04-casos-de-teste.md                                          # Regras
grep -cE '^\| M-?[0-9]+' 04-casos-de-teste.md                                              # Mutantes previstos
grep -cE '^\| M-?[0-9]+.*sem matador' 04-casos-de-teste.md                                 # Sem matador
```

`Esquema do Cenário` conta 1, não uma por linha de `Exemplos` (`references/escolha-de-camada.md`
§Passo 7). Cenário `@obsoleto` continua contado enquanto estiver no arquivo.

## Teste de arquitetura de sincronia de IDs (pós-implementação)

**Teste de arquitetura sugerido** — barato, um por projeto e não por feature: lê os `[CT-nn]` dos
testes e dos `04`/`05` e falha com o ID que existe num lado só — nas **duas** direções: ID do teste
sem cenário na wiki, e ID do `04`/`05` sem `it('[CT-nn]…')`. O dataset é a lista declarada de
pares (arquivo de teste, pasta da wiki); declará-la à mão é o custo, e é também o que impede um
teste novo de nascer sem wiki.

```php
it('todo [CT-nn] existe nos dois lados: teste e 04/05 da wiki', function (string $teste, string $wiki): void {
    $ids = fn (string $arquivo): array => preg_match_all('/\[(CT-B?\d{2,})\]/', file_get_contents($arquivo), $m) ? array_unique($m[1]) : [];
    $noTeste   = $ids($teste);
    $naWiki    = array_merge([], ...array_map($ids, glob("$wiki/0[45]-*.md")));
    $soNoTeste = array_diff($noTeste, $naWiki);
    $soNaWiki  = array_diff($naWiki, $noTeste);

    expect($soNoTeste)->toBeEmpty('IDs só no teste: '.implode(', ', $soNoTeste))
        ->and($soNaWiki)->toBeEmpty('IDs só na wiki (sem it(\'[CT-nn]…\')): '.implode(', ', $soNaWiki));
})->with('pares teste ↔ wiki');
```

## Tabela de cogitado e cortado (passo 7, item 4)

| Cenário cogitado | Por que foi cortado |
|---|---|
| {…} | já provado por CT-07, mais barato |
| {…} | mata o mesmo mutante que CT-B01 |
| {…} | não mata nenhum mutante previsto |

Sem essa tabela, "só há 2 CT-B" é indistinguível de "só pensamos em 2", e a próxima pessoa
refaz a análise do zero.
