> Referência da requirement-to-rule 1.4.0. Lida em: passo 2 (prever o arquivo que o Boost vai
> escolher), passo 6 (dois globs, atualizar rule existente), passo 7 (conferir o índice), Poda e
> Fallback. Fonte única de: o que uma chamada do `record-rule` faz com o arquivo e com o índice, a
> tabela glob → arquivo, os procedimentos de atualizar, remover e mudar glob, a alternativa do
> `writeIndex()` pelo `tinker`, e o formato do índice escrito à mão.

# `record-rule` e o índice — como o Boost faz de verdade

As obrigações (gravar sempre por `record-rule`, não editar o índice à mão fora dos dois casos
previstos, conferir depois da última chamada) ficam no corpo do `SKILL.md`: §6, §7, §Índice de
Rules, §Poda e §Fallback.

Fonte: `laravel/boost` v2.10.0 — `src/Rules/RuleRepository.php` e `src/Mcp/Tools/RecordRule.php`,
lidos em 2026-09-26. As saídas desta página vêm de um harness que executou esse `RuleRepository`
fora do Laravel em 2026-09-27 (Illuminate 13.33, Symfony Yaml 8.1).

## O que uma chamada faz

`record-rule` recebe `glob`, `title` e `note` e chama `RuleRepository::write()` (:116-135):

1. Normaliza o glob para relativo à raiz do projeto.
2. Escolhe o arquivo (`resolveTargetFile`, :179-204): o primeiro arquivo de `.ai/rules/` cujo
   `paths:` já tem esse glob **ou** tem um glob da mesma **área**. Área = os segmentos de diretório
   do glob, descartados os que têm `*` ou `.` (`meaningfulSegments`, :257-267). `app/Models/**` e
   `app/Models/Invoice.php` são a mesma área (`app/Models`); `app/Services/Billing/**` é outra.
3. Nenhum arquivo da área: cria um com o nome do último segmento, em minúsculas e com hífen; se o
   nome já estiver ocupado, usa os dois últimos (`api-controllers.md`), e assim por diante
   (`uniqueFilePath`, :209-247). O arquivo nasce com o frontmatter `paths:` e o título `# {Nome}`.
4. Arquivo existente: acrescenta o glob ao `paths:` se ainda não estiver lá (`ensureGlobApplied`,
   :292-313).
5. Acrescenta `## {title}` e, na linha seguinte, o `note`, no fim do arquivo (`appendEntry`,
   :315-320). **Sempre**: não procura seção com o mesmo título.
6. Regenera o `.ai/rules/index.md` inteiro a partir dos arquivos (`writeIndex`, :137-161).

A resposta diz onde a rule foi parar — `Recorded rule in .ai/rules/models.md: {title}.`
(`RecordRule.php`:94-96). É esse arquivo que o passo 7 confere.

## Glob → arquivo (primeira gravação da área)

Saída do harness: um `record-rule` por linha, na ordem, em `.ai/rules/` vazio. A coluna da direita
é o conteúdo típico de cada área.

| Glob | Arquivo que o Boost cria | O que vive nele |
|---|---|---|
| `app/Models/**` | `.ai/rules/models.md` | invariantes de dados, scopes globais, casts obrigatórios |
| `app/Models/Invoice.php` | `.ai/rules/models.md` (mesma área: entra no mesmo arquivo) | — |
| `app/Services/Billing/**` | `.ai/rules/billing.md` | — |
| `app/Http/Controllers/**` | `.ai/rules/controllers.md` | base class, autorização, formato de resposta |
| `app/Http/Controllers/Api/**` | `.ai/rules/api.md` (não `controllers.md`: a área é outra) | — |
| `app/Http/Requests/**` | `.ai/rules/requests.md` | padrão de validação, mensagens |
| `app/Jobs/**` | `.ai/rules/jobs.md` | idempotência, retries, channel de log |
| `database/migrations/**` | `.ai/rules/migrations.md` | convenções de nome, FK, seeder-em-migration, guard de environment |
| `tests/**` | `.ai/rules/tests.md` (não `testing.md`) | estratégia de DB, factories obrigatórias, o que precisa CT-B |
| `app/Livewire/**` | `.ai/rules/livewire.md` | padrão de `fail()`, log de validação |
| `app/Filament/**` | `.ai/rules/filament.md` | resources, policies, `data-test` obrigatório |
| `**` | `.ai/rules/general.md` | nada: glob `**` é anti-padrão |

Dois globs de áreas diferentes com o mesmo último segmento viram dois arquivos: o teste do próprio
Boost grava `app/Admin/Controllers/**` em `controllers.md` e `app/Api/Controllers/**` em
`api-controllers.md` (`tests/Feature/Mcp/Tools/RuleToolsTest.php`:98-117).

## O arquivo de rule que o Boost escreve

Harness, depois de `record-rule` com o mesmo `title` em `app/Models/**` e em `app/Models/Invoice.php`:

```markdown
---
paths:
  - 'app/Models/**'
  - app/Models/Invoice.php
---

# Models

## Valores monetários são inteiros em centavos
Nota A.

## Valores monetários são inteiros em centavos
Nota A.
```

A seção saiu **duas vezes**: é o passo 5 da chamada. O mesmo `title` em `app/Services/Billing/**`
criou `billing.md` com uma terceira cópia.

## O índice que o Boost escreve

Harness, mesmo estado (trecho):

```markdown
# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

| Applies to | Rule file |
| --- | --- |
| app/Http/Controllers/Api/** | .ai/rules/api.md |
| app/Services/Billing/** | .ai/rules/billing.md |
| app/Http/Controllers/** | .ai/rules/controllers.md |
| app/Models/**, app/Models/Invoice.php | .ai/rules/models.md |
```

- **Uma linha por arquivo de rule**, não por glob. Os globs do `paths:` vão na mesma célula,
  separados por `, `, na ordem do frontmatter.
- Linhas em ordem de path do arquivo, não de especificidade.
- Arquivo sem `paths:` no frontmatter, ou com frontmatter que não parseia, fica **fora** do índice
  (`writeIndex`, :141) — invisível para os agentes.
- Sem nenhum arquivo com `paths:`, a tabela vira a frase `No rules recorded yet.`
- Com `BOOST_RULES_SCOPED_GUIDELINES=true`, o índice também lista `.ai/rules/boost/*.md`, que o
  Boost gera e apaga sozinho a cada `boost:install`/`boost:update`. Não editar esses arquivos.

## Chamadas que duplicam ou espalham

| Situação | O que o Boost faz | Procedimento |
|---|---|---|
| mesmo `title` em dois globs da **mesma área** | duas seções `## {title}` iguais no mesmo arquivo | chamar `record-rule` com o segundo glob (o `paths:` e o índice ganham o glob) e apagar à mão a segunda seção — apagar seção não mexe no índice |
| mesmo `title` em globs de **áreas diferentes** | um arquivo por área, cada um com uma cópia da seção | antes, conferir se um glob só não basta (gate 2). Se não basta, aceitar as cópias e listar os dois arquivos no commit: a próxima atualização muda as duas |
| `record-rule` para "atualizar" rule existente | acrescenta outra seção `## {title}`; a antiga continua valendo | não chamar: ver abaixo |

## Atualizar uma rule existente

- **Texto novo, mesmo glob**: editar a seção `## {title}` no arquivo da área. O índice não muda: ele
  só mapeia `paths:` → arquivo.
- **Glob novo para rule existente**: primeira linha da tabela acima.

## Remover ou mudar glob (Poda)

| Mudança | Índice | Procedimento |
|---|---|---|
| apagar uma seção `## {título}` de `.ai/rules/{área}.md` | não muda | apagar a seção; commit |
| apagar o arquivo inteiro (a última seção saiu) | defasado: a linha aponta para arquivo que não existe | apagar o arquivo e editar a linha do índice à mão |
| mudar o `paths:` (trocar ou tirar glob) | defasado: a célula mostra o glob antigo | editar o frontmatter e a linha do índice à mão |

Por que à mão: fora de uma chamada direta ao `writeIndex()` ([alternativa](#alternativa-writeindex-pelo-tinker)),
o índice só é regenerado por `record-rule`, e por `boost:install`/`boost:update` quando
`BOOST_RULES_SCOPED_GUIDELINES=true`. Com a configuração padrão, o `boost:update` chama
`InstallCommand::syncRuleFiles()` (`InstallCommand.php`:436-443), que só executa `clearManaged()` —
e ela só reescreve o índice se `.ai/rules/boost/` existia (`RuleRepository.php`:64-76). Esperar o
próximo `record-rule` deixa os agentes lendo uma linha errada até lá.

A edição manual da linha do índice:

1. Escrever a linha como o `writeIndex()` escreveria: `| {globs do paths:, separados por ", "} | .ai/rules/{área}.md |`.
   Arquivo removido = linha removida. Linhas em ordem de path do arquivo. Nenhum arquivo com
   `paths:` restante = `No rules recorded yet.` no lugar da tabela.
2. Conferir `ls .ai/rules/` contra as linhas: todo arquivo com `paths:` tem uma linha, e toda linha
   aponta para um arquivo que existe.
3. Commit com o motivo: `:fire: rules: remove {título}` (ou `:memo: rules: glob de {título}`), e no
   corpo o gatilho da poda (as três features, ou "glob sem arquivo").

### Alternativa: `writeIndex()` pelo `tinker`

> **API interna do Boost, não documentada — pode mudar.** A edição manual acima continua o
> procedimento padrão. Esta é a alternativa para quem prefere que a linha saia do código do Boost.

```bash
php artisan tinker --execute='app(\Laravel\Boost\Rules\RuleRepository::class)->writeIndex();'
```

- É o que o `record-rule` chama no fim de cada gravação (`RuleRepository.php`:132): relê os arquivos
  de `.ai/rules/` e reescreve o `index.md` inteiro. Na v2.10.0 é `public function writeIndex(): string`
  (:137). Não aparece na doc do Boost, e nada impede uma versão futura de renomeá-lo ou fechá-lo.
- Precisa do `laravel/tinker` (a opção `--execute` é do `TinkerCommand` dele, 2.x) e do Boost ativo
  naquele ambiente. O `BoostServiceProvider` só registra o `RuleRepository` quando `shouldRun()` é
  verdadeiro: `boost.enabled` ligado, fora de teste, e `APP_ENV=local` ou `APP_DEBUG=true`
  (`BoostServiceProvider.php`:38-48, :193-209). Sem o registro, o container tenta montar a classe
  sozinho e falha, porque o construtor pede o diretório:
  `BindingResolutionException: Unresolvable dependency resolving [Parameter #0 [ <required> string $directory ]]`
  (reproduzido com `illuminate/container` 13.33 em 2026-09-27). Nesse caso, voltar à edição manual.
- Conferir como na edição manual: `git diff -- .ai/rules/index.md` mostra só a linha esperada, e
  `ls .ai/rules/` bate com as linhas. No corpo do commit, além do gatilho: "índice regenerado por
  `writeIndex()` (tinker)".

Harness de 2026-09-27, com o mesmo `RuleRepository` v2.10.0 fora do Laravel: três `record-rule`
(`app/Models/**`, `app/Jobs/**`, `app/Services/Billing/**`); depois, à mão, `jobs.md` apagado e o glob
de `billing.md` trocado para `app/Billing/**`. O índice continuou com as três linhas antigas até a
chamada. Depois de `writeIndex()`:

```markdown
| Applies to | Rule file |
| --- | --- |
| app/Billing/** | .ai/rules/billing.md |
| app/Models/** | .ai/rules/models.md |
```

Não foi executado num projeto Laravel com o `tinker`: a opção `--execute` foi lida no código do
`laravel/tinker` (branch 2.x, 2026-09-27).

## Gatilho da poda — como conferir

Rule `n.a.` ou `violada` em `## Conformidade com Rules`. A coluna `Rule` do `03` começa pelo arquivo
da rule (`` `auth.md` — {título} ``):

```bash
grep -rn --include=03-progresso.md -F 'models.md' wikis/specs/
```

Ler as linhas que citam o título e ordenar as features pela data do `## Quality Gate` de cada `03`.
Três seguidas com `n.a.` ou `violada` disparam o gatilho.

Glob que não casa mais nenhum arquivo versionado:

```bash
git ls-files -- ':(glob)app/Services/Billing/**' | head -n 1    # saída vazia = gatilho
```

## Índice escrito à mão (Fallback)

Sem `record-rule`, o arquivo e o índice são escritos à mão **no formato do Boost**, para que a
primeira regeneração, quando o Boost voltar, não mude nada além do necessário:

- Nome do arquivo: o que o Boost escolheria (tabela glob → arquivo acima).
- Arquivo: frontmatter `paths:`, `# {Nome}`, e uma seção `## {title}` com o `note` embaixo.
- Índice: cabeçalho e frase de instrução do modelo oficial, uma linha por arquivo, globs separados
  por `, `, linhas em ordem de path do arquivo.
