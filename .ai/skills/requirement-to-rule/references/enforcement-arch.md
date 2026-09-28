> Referência da requirement-to-rule 1.4.0. Lida em: passo 4 (decidir se a restrição é mecânica e
> montar o teste que entra na apresentação) e passo 6 (gravar o teste e rodar a prova). Fonte única
> de: a tabela restrição → expectation do `arch()`, o template do teste, os modelos de violação
> controlada, o uso de `scripts/prova-arch.sh` e o que fazer com PHPStan, Rector e Pint.

# Enforcement que roda — `arch()` gerado e provado

As obrigações (gerar o teste quando a restrição é mecânica, provar antes de a rule dizer
"enforçado", nada gravado sem aprovação) ficam no corpo do `SKILL.md`, §Enforcement que roda.

Sintaxe conferida em https://pestphp.com/docs/arch-testing (lida em 2026-09-26; o seletor de
versão da página mostrava 5.x). Os templates vivem aqui como bloco de código porque o
`boost:add-skill` descarta arquivos `.php` da skill.

## Restrição mecânica — quando o `arch()` alcança

`arch()` confere a estrutura do código por namespace: herança, interface, uso de classe ou função,
sufixo de nome, `final`, trait, atributo. A doc: *"The expectations are determined by either
relative namespaces, fully qualified namespaces, or function names."* Ele não enxerga tipo de
coluna, conteúdo de migration, valor em runtime nem fluxo dentro do método. Restrição que não cabe
numa linha da tabela abaixo é prosa.

| Restrição da rule | Expectation — exemplo da doc |
|---|---|
| todo X estende Y | `arch()->expect('App\Models')->toExtend('Illuminate\Database\Eloquent\Model');` |
| todo X implementa I | `arch()->expect('App\Jobs')->toImplement('Illuminate\Contracts\Queue\ShouldQueue');` |
| X nunca usa Y | `arch()->expect('App\Domain')->not->toUse('request');` |
| Y nunca é usado em X | `arch()->expect('request')->not->toBeUsedIn('App\Domain');` |
| Y só é usado em X | `arch()->expect('App\Models')->toOnlyBeUsedIn('App\Repositories');` |
| X só depende de Y | `arch('models')->expect('App\Models')->toOnlyUse('Illuminate\Database');` |
| nome termina em S | `arch()->expect('App\Http\Controllers')->toHaveSuffix('Controller');` |
| classes são `final` | `arch()->expect('App')->classes()->toBeFinal();` |
| X usa a trait T | `arch('models')->expect('App\Models')->toUseTrait('Illuminate\Database\Eloquent\SoftDeletes');` |
| X tem o atributo A | `arch()->expect('App\Console\Commands')->toHaveAttribute('Symfony\Component\Console\Attribute\AsCommand');` |
| X é invocável | `arch()->expect('App\Actions')->toBeInvokable();` |
| X só tem enums | `arch()->expect('App\Enums')->toBeEnums();` |
| exceção declarada na rule (escape hatch) | `->ignoring(...)` no fim da cadeia: `arch()->expect('Illuminate\Support\Facades')->not->toBeUsed()->ignoring('App\Providers');` |

## Template do teste — `tests/Arch/{Área}Test.php`

`{Área}` é o nome do arquivo de rule que o Boost vai escolher (tabela glob → arquivo em
`references/indice-e-record-rule.md`), em StudlyCase: `models.md` → `ModelsTest.php`,
`api-controllers.md` → `ApiControllersTest.php`. Se o arquivo já existe, acrescentar o bloco
`arch(...)` no fim; nunca sobrescrever.

```php
<?php

// Rule "{título da rule}" — .ai/rules/{área}.md
// Gerado pela requirement-to-rule. A rule aponta para este teste: mudar a rule antes de mudar o teste.

arch('{título da rule}')
    ->expect('{Namespace}')
    ->{expectation}('{alvo}');
```

A descrição do `arch()` é o título da rule: a falha no CI já diz qual rule foi quebrada.

Exemplo — "Controllers estendem `BaseController`":

```php
<?php

// Rule "Controllers estendem BaseController" — .ai/rules/controllers.md
// Gerado pela requirement-to-rule. A rule aponta para este teste: mudar a rule antes de mudar o teste.

arch('Controllers estendem BaseController')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\BaseController')
    ->ignoring('App\Http\Controllers\BaseController');
```

O `ignoring` deixa explícito que a base fica fora da conferência. No projeto-fixture (Pest 5.2.1,
2026-09-27, `BaseController` abstrata) o teste passou com e sem ele. Quem decide no projeto real é
a prova (a).

## A suíte roda `tests/Arch`?

`vendor/bin/pest` sem argumento roda só as suítes do `phpunit.xml`. Se nenhuma inclui `tests/Arch`
nem `tests/`, o teste passa no terminal e nunca roda no CI. Nesse caso a apresentação traz, junto
do teste, a mudança mínima:

```xml
<testsuite name="Arch">
    <directory>tests/Arch</directory>
</testsuite>
```

O script confere isso (checagem 0) e acusa `phpunit.xml:{linha}: nenhuma suíte inclui tests/Arch nem tests/`.
Ele lê as suítes pelo DOM do PHP, não por grep: um `<testsuite>` dentro de `<!-- … -->` não conta.

## Violação controlada

Um arquivo temporário, num diretório que o teste varre, com nome `ViolacaoControlada{Algo}.php` (se
sobrar no disco, `grep -rl ViolacaoControlada app/` acha). O conteúdo é **PHP válido**: no
projeto-fixture (Pest 5.2.1, 2026-09-26), um arquivo com erro de sintaxe foi ignorado pelo `arch()`,
o teste passou, e a prova acusou "não derrubou".

| Expectation | O que a violação faz |
|---|---|
| `toExtend('Y')` | classe no namespace que não estende Y |
| `toImplement('I')` | classe no namespace sem `implements I` |
| `not->toUse('F')` | classe no namespace que chama `F` ou importa a classe proibida |
| `toOnlyBeUsedIn('X')` | classe **fora** de X que usa a classe protegida |
| `toBeFinal()` | classe no namespace sem `final` |
| `toHaveSuffix('S')` | classe no namespace cujo nome não termina em S (`ViolacaoControlada…` já não termina) |

Modelo para `toExtend`, usado nas saídas do cabeçalho de `scripts/prova-arch.sh`:

```php
<?php

namespace App\Models;

class ViolacaoControladaArch
{
}
```

Modelo para `not->toUse('request')` em `App\Domain`:

```php
<?php

namespace App\Domain;

class ViolacaoControladaArch
{
    public function handle(): mixed
    {
        return request('x');
    }
}
```

## A prova

Na raiz do projeto, com o teste já gravado:

```bash
TMP=$(mktemp -d)                       # fora do repositório
cat > "$TMP/violacao.php" <<'PHP'
<?php

namespace App\Models;

class ViolacaoControladaArch
{
}
PHP
bash {skills}/requirement-to-rule/scripts/prova-arch.sh tests/Arch/ModelsTest.php \
  app/Models/ViolacaoControladaArch.php "$TMP/prova" < "$TMP/violacao.php"
echo "exit=$?"
```

O script roda `php vendor/bin/pest tests/Arch/ModelsTest.php` duas vezes: (a) no código atual;
(b) com a violação no lugar. Ele cria e remove a violação sozinho e compara
`git status --porcelain --untracked-files=all` de antes e de depois de (b). Em interrupção
(`INT`/`TERM`) o `trap` remove a violação e sai com `exit=2`, mas só quando o `pest` em curso termina:
bash espera o processo filho (testado com `TERM` no Git Bash do Windows em 2026-09-27).
Nunca sobrescreve arquivo existente e recusa diretório de evidência dentro do repositório. Quem
decide é o `git rev-parse --show-toplevel`, não o texto do path: no Git Bash, a caixa digitada no
`cd` e o `/tmp` fazem dois paths do mesmo diretório parecerem diferentes.

Colar na conversa, antes do `record-rule`: `exit=0` e o conteúdo de `$TMP/prova/a-atual.txt`
(passou), `b-violacao.txt` (falhou citando a violação), `status-antes.txt` e `status-depois.txt`
(idênticos).

| Saída do script | Significado | O que fazer |
|---|---|---|
| silêncio, `exit=0` | provado | `record-rule` com a linha "Enforçado em `tests/Arch/{Área}Test.php` — não contornar." |
| `phpunit.xml:{n}: nenhuma suíte inclui tests/Arch…` | o CI não roda o teste | mudança de suíte aprovada e ainda não aplicada: aplicá-la e rodar de novo. Mudança fora da aprovação: desfazer o teste, não gravar o candidato em forma nenhuma (nem como prosa) e relatar |
| `{arquivo}:{linha}: viola … no código atual` | violação existente | achado, não rule nova: desfazer o teste, não gravar o candidato, relatar com a linha |
| `… não derrubou …` | o teste não prova a restrição | conferir namespace e PHP válido da violação; persistindo, desfazer o teste e não gravar o candidato |
| `… falhou … sem citar …` | a falha veio de outro lugar | ler `b-violacao.txt`; corrigir só o conteúdo da violação controlada e rodar de novo. Se a correção exige mudar o teste aprovado: desfazer o teste, não gravar o candidato e relatar |
| `exit=2` | erro de uso ou de ambiente | ler a mensagem, corrigir a chamada ou o ambiente e rodar de novo — nunca o teste aprovado |

"Desfazer o teste" = apagar o arquivo que a skill criou, ou tirar o bloco que ela acrescentou.
A violação controlada pode ser corrigida porque é um arquivo temporário e não faz parte da
aprovação. O teste e a mudança de suíte fazem: mexer neles depois do "sim" seria gravar o que o
usuário não aprovou, e o corpo proíbe uma segunda pergunta na mesma invocação (`SKILL.md` §5 e §6).

## PHPStan / Larastan, Rector, Pint

A skill não gera configuração dessas ferramentas. Quando a restrição cabe numa delas, a
apresentação sugere a configuração mínima como texto. A rule só diz "enforçado por {ferramenta}"
se o usuário aplicar a configuração e colar uma execução em que a ferramenta acusa uma violação
controlada, com a árvore intacta — o mesmo critério da prova acima. Formatação (Pint) nunca vira
rule: ver *Quando NÃO Invocar* no `SKILL.md`.
