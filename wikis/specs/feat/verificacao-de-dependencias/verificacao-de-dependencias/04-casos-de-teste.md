# Casos de Teste — Roadmap 8.2: `composer-require-checker` + `composer-unused`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só os paths de `## Estrutura de Implementação`)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação.
> Revisão adversarial: não exigida — perfil mínimo em todas as áreas e nenhum Impacto 3.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Ferramentas no `require-dev` e comando do composer | 1 | 1 | 1 | mínimo |
| Relatório no CI (roda, publica, não reprova) | 1 | 2 | 2 | mínimo |
| Configuração nas duas rotas de entrega | 1 | 2 | 2 | mínimo |
| Triagem registrada na documentação de qualidade | 1 | 1 | 1 | mínimo |

- Impacto 2 no CI e na configuração: um passo que reprova deixa todo PR vermelho, e uma configuração que não viaja faz o relatório do projeto derivado acusar o que é falso (retrabalho manual, reversível). Nenhuma área toca dinheiro, dado de terceiro, autorização ou algo irreversível.
- Passos rodados: 1, 2 (com as costuras), 5, 6; o 3 só com EP e o 7 só para alocar e conferir costuras. Sem BVA, sem tabela de decisão, sem matriz de estado (não há entidade com ciclo de vida).
- Técnicas aplicadas: EP; rastreio de efeito na R4 (o relatório publicado é o efeito; ver a nota da R4).
- Cenários: 8 · Regras: 8 · Mutantes previstos: 25 · Sem matador: 0
<!-- derivado do arquivo por grep -c (comandos da reference template-04, "Contagem do cabeçalho") -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | `composer.json`/`composer.lock` (`require-dev`, scripts), arquivo de configuração das ferramentas na raiz, passo no workflow de CI, lista de caminhos do `kit:update`, documentação de qualidade | CT-01, CT-02, CT-03, CT-05 |
| F | rodar as duas ferramentas (local e CI), publicar o relatório, não reprovar, excluir falso positivo com motivo, registrar a triagem e as recomendações | CT-02, CT-03, CT-04, CT-06, CT-07, CT-08 |
| D | entra: o `composer.json` do projeto; sai: lista de dependências não usadas e de símbolos de pacote não declarado; já existe: as 58 dependências do kit. Dado de outro tenant: não se aplica (ferramental, sem banco) | CT-04, CT-06, CT-07 |
| I | `composer deps:check` (dev), passo do workflow de CI, `kit:update` e `create-project` (as duas rotas de entrega). Sem rota HTTP, sem UI | CT-02, CT-03, CT-05 |
| P | PHP 8.4, `vendor/bin` instalado pelo `composer install`, shell `bash` do runner do GitHub (`-eo pipefail` por padrão), Windows do desenvolvedor | CT-03, CT-04 |
| O | o mantenedor roda sob demanda; o CI roda em todo push/PR; o projeto derivado recebe a configuração e roda o mesmo comando. Uso indevido plausível: excluir pacote sem uso só para calar o relatório | CT-04, CT-06 |
| T | ordem dos passos do job (a ferramenta precisa do `vendor/` já instalado); com `set -e`, a primeira ferramenta que acha algo interrompe a segunda | CT-03, CT-04 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — As duas ferramentas são dependência de desenvolvimento, declaradas e travadas no lock | require-dev (mínimo) | RQ-01, RQ-02, RQ-03 | EP (seção do `composer.json`/`composer.lock`) | CT-01 |
| R2 — Um comando só do composer, `deps:check`, roda as duas ferramentas | require-dev (mínimo) | P-01, RQ-01, RQ-02, P-07 | EP (ferramenta alcançada pelo comando) | CT-02, CT-09 |
| R3 — O CI roda as duas ferramentas pelo `vendor/bin`, depois de instalar as dependências | CI (mínimo) | RQ-04 | EP (origem do binário) | CT-03 |
| R4 — O CI publica o relatório das duas no resumo do job e o job continua verde com achados | CI (mínimo) | RQ-05, P-02 | rastreio de efeito (publicado / sem reprovar) | CT-04 |
| R5 — A configuração das ferramentas viaja nas duas rotas de entrega | configuração (mínimo) | P-06 | EP (rota de entrega) | CT-05 |
| R6 — A lista de exclusão só tem pacote usado por outro caminho, cada um com motivo | configuração × triagem (mínimo) | P-03 | EP (veredito da triagem) | CT-06 |
| R7 — A triagem registra cada pacote acusado com veredito do domínio e evidência | triagem (mínimo) | RQ-06, P-04 | EP (veredito: usado por outro caminho / sem uso / uso direto de dependência só transitiva) | CT-07 |
| R8 — Achado que não é falso positivo fica registrado como recomendação | triagem (mínimo) | P-05 | EP (veredito ≠ usado por outro caminho) | CT-08 |

- RQ-01 — coberta por CT-01, CT-02, CT-04 (a ferramenta existe, roda local e roda no CI)
- RQ-02 — coberta por CT-01, CT-02, CT-04
- RQ-03 — coberta por CT-01
- RQ-04 — coberta por CT-03
- RQ-05 — coberta por CT-04
- RQ-06 — coberta por CT-07 (e CT-06, CT-08 sobre a mesma triagem)
- P-01 — coberta por CT-02; recorte "o `deps:check` entra no `composer test`?" resolvido pela P-07 (fora)
- P-02 — coberta por CT-04
- P-03 — coberta por CT-06
- P-04 — cobertura parcial: CT-07 cobre forma e domínio; **completude** (todo pacote acusado pela ferramenta está na triagem) é lacuna declarada — exige rodar a ferramenta e casar a saída dela com a tabela; ver Checklist de Taxonomia
- P-05 — cobertura parcial: CT-08 cobre "registrado como recomendação"; "esta entrega não remove nem acrescenta dependência de produção" fica fora de CT permanente (um teste que trave o `require` expira no dia em que o mantenedor acatar a recomendação) — conferência do diff no quality gate
- P-06 — coberta por CT-05

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Arquivos de ferramental do kit | R1, R2, R3, R5 | Pest feature HTTP | existente — `tests/Kit/QualidadeDeCodigoTest.php` (lê `composer.json` e o config de ferramenta no `beforeEach`) e `tests/Kit/CandidatasAPacoteTest.php` CT-32 (`caminhosDoKit()` + `.gitattributes`) | o `Então` afirma o conteúdo de arquivos versionados; a aplicação de pé só fornece `base_path()` e o `ReflectionClass` de `KitUpdate` || sessão, 2026-11-18 (irmãos citados; autorização "prossiga ate finalizar") |
| Passo do CI executado | R4 | Pest feature HTTP | existente — `tests/Kit/QualidadeDeCodigoTest.php` (`configuracaoEfetivaDoPhpstan()` roda o binário de verdade por `Process`) | "não reprova" e "publica" são comportamento de shell (`-eo pipefail`, redirecionamento): lido estaticamente, o YAML deixa passar o `set -e` que corta a segunda ferramenta. Só executando o `run` do passo isso falsifica || sessão, 2026-11-18 (irmãos citados; autorização "prossiga ate finalizar") |
| Triagem na documentação de qualidade | R6, R7, R8 | Pest feature HTTP | existente — `tests/Kit/CandidatasAPacoteTest.php` (`secoesDoMarkdown()`) | o `Então` afirma linhas de uma tabela do markdown e o cruzamento com o arquivo de configuração || sessão, 2026-11-18 (irmãos citados; autorização "prossiga ate finalizar") |

**Divergência com a skill (a rule vence)**: a skill define `Pest feature HTTP` como teste em `tests/Feature`; aqui os casos moram em `tests/Kit`, porque `.ai/rules/kit.md`/`testes.md` e os irmãos (`QualidadeDeCodigoTest`, `CandidatasAPacoteTest`) põem o ferramental do kit lá. A camada é a mesma (aplicação de pé, sem componente).

**Divergência de skip (a rule vence)**: `.ai/rules/testes.md` exige `->skip(fn (): bool => ! naArvoreDoKit(), '...')` em caso que lê `docs/`, README, `site/` ou `wikis/specs/`. Além disso, `.github/` e `.gitattributes` também são `export-ignore`; por isso CT-03, CT-04 e CT-05 (leem o workflow ou o `.gitattributes`) e CT-06..CT-08 (leem `wikis/`, por instrução da sessão) levam o mesmo `skip`. CT-01 e CT-02 leem só `composer.json`/`composer.lock`, que viajam, e rodam em qualquer árvore.

## Fronteira com o Plano

| Item do PRD (ou visto na árvore) | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes `deps:unused` e `deps:require` | escolha de implementação; o `00` só nomeia `deps:check` (P-01) | detalhe; o CT-02 resolve as referências `@script` sem exigir esses nomes |
| flag `--ignore-exit-code` do `composer-unused` | um dos mecanismos possíveis de "não reprovar" (RQ-05) | detalhe; CT-04 é agnóstico ao mecanismo |
| `continue-on-error` × `\|\| true` × flag da ferramenta | mecanismo; o `00` fixa o resultado (job verde com achado), não o meio | Q?1 (raia desenho); CT-04 aceita qualquer mecanismo que produza o resultado |
| os cinco pacotes excluídos (`anselmokossa/filament-sentinel`, `laravel/pulse`, …) | só o PRD determina a lista; o `00` fixa o critério (P-03) | o CT-06 cruza a lista com a triagem, sem fixar nomes |
| título do passo e nome do job `qualidade` | escolha de implementação | o CT-03/CT-04 acham o passo pela ferramenta que ele roda |
| "181 símbolos" | medição do momento; muda com qualquer `composer update` | não vira asserção |
| "roadmap 8.2 marcado FEITO" | gestão do roadmap, não comportamento pedido | não vira CT |
| `docs/{pt,en}/referencia/qualidade-de-codigo.md` | o `00` pede "documentação de qualidade", sem fixar quantas cópias | os CT leem `wikis/qualidade-de-codigo.md` (path do plano, como nome); as traduções ficam para a rede de documentação existente |
| `composer-unused.php` como nome do arquivo | nome default da ferramenta (fato do vendor), usado só para localizar | detalhe do CT-05/CT-06 |

**Perguntas geradas pela derivação**: fechadas pela sessão em 2026-11-18 — Q?1 (desenho): o CT-04 executa o `run` do passo do CI, como recomendado; Q?2: virou a premissa P-07 do `00` (o `deps:check` fica fora do `composer test`), sem cenário próprio além do que o `composer.json` já mostra.

## Setup Global

### Personas
- Nenhuma: ferramental, sem usuário nem painel.

### Fixtures
- `composer.json` e `composer.lock` lidos com `json_decode(file_get_contents(base_path(...)), true)`, como no `beforeEach` de `QualidadeDeCodigoTest`.
- Workflow de CI lido com `Symfony\Component\Yaml\Yaml::parse()` (já usado em `tests/Kit/RoteiroDeAtualizacaoTest.php`), varrendo **todos** os arquivos de `.github/workflows/`.
- "O passo que roda as ferramentas" = o passo cujo `run` cita `composer-unused`, `composer-require-checker` ou `deps:check`. Achado por conteúdo, nunca por título.
- `caminhosDoKit()` de `tests/Pest.php` para a rota `kit:update`; para a rota `create-project`, as linhas `export-ignore` do `.gitattributes` sem comentário (padrão do CT-32 de `CandidatasAPacoteTest`).
- Asserção de **ausência** sobre arquivo comentado (`composer-unused.php`, workflow) filtra comentário antes (`.ai/rules/testes.md`): `#` no YAML, `/* */` e `//` no PHP. A de presença roda no texto cru.
- Triagem: a tabela da documentação de qualidade (`wikis/qualidade-de-codigo.md`) cujas linhas têm uma coluna de pacote e uma de veredito; lida pelo helper `secoesDoMarkdown()` de `tests/Pest.php`.
- Nenhuma situação de partida com ciclo de vida.

### Fakes
- Nenhum. O CT-04 roda processo real (`Illuminate\Support\Facades\Process`), como `configuracaoEfetivaDoPhpstan()`; sem `Process::fake()`, que esvaziaria o oráculo.

### Estratégia de DB
- Não se aplica: nenhum caso toca o banco.

---

## Regra R1 — As duas ferramentas são dependência de desenvolvimento, declaradas e travadas no lock

> `RQ-01`, `RQ-02`, `RQ-03` · perfil **mínimo** · técnica: **EP** (seção onde o pacote aparece: `require-dev` ✓ · `require` ✗ · ausente ✗; no lock, `packages-dev` ✓ · `packages` ✗ · ausente ✗)

```gherkin
# language: pt
Funcionalidade: Verificação de dependências declaradas × usadas

  Regra: composer-unused e composer-require-checker entram só como dependência de desenvolvimento

    Esquema do Cenário: [CT-01] a ferramenta <ferramenta> é dependência de desenvolvimento travada no lock
      Dado o composer.json e o composer.lock do kit
      Quando o mantenedor confere onde o pacote <pacote> está declarado
      Então ele está no "require-dev" do composer.json e não no "require"
      E ele está em "packages-dev" do composer.lock e não em "packages"

      Exemplos:
        | ferramenta               | pacote                           |
        | composer-unused          | icanhazstring/composer-unused    |
        | composer-require-checker | maglnet/composer-require-checker |
```

O nome Packagist de cada ferramenta é fato do registro, não oráculo do plano; o oráculo é a seção.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | pacote declarado no `require` (viaja para produção) | CT-01 | "não está no `require`": o mutante tem a chave em `require` |
| M2 | só uma das duas ferramentas instalada | CT-01 (linha da ausente) | "está no `require-dev`": a linha da ferramenta esquecida não acha a chave |
| M3 | `composer.json` editado à mão sem `composer update` (lock defasado: `composer install` do CI não instala) | CT-01 | "está em `packages-dev` do lock": o mutante não tem o pacote no lock |

---

## Regra R2 — Um comando só do composer, `deps:check`, roda as duas ferramentas

> `P-01`, `RQ-01`, `RQ-02` · perfil **mínimo** · técnica: **EP** (ferramenta alcançada pelo comando: as duas ✓ · só uma ✗ · referência a script inexistente ✗)

```gherkin
  Regra: o comando composer deps:check roda as duas ferramentas

    Cenário: [CT-02] deps:check alcança composer-unused e composer-require-checker
      Dado os scripts do composer.json do kit
      Quando o mantenedor expande o script "deps:check", seguindo cada referência "@script" até o comando final
      Então toda referência "@script" aponta para um script que existe
      E os comandos finais incluem uma chamada a "composer-unused"
      E os comandos finais incluem uma chamada a "composer-require-checker"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M4 | `deps:check` roda só o `composer-unused` (o `require-checker` ficou num script à parte) | CT-02 | "inclui `composer-require-checker`": a expansão do mutante não o tem |
| M5 | `deps:check` aponta `@deps:require` mas o script se chama outro nome (referência quebrada; `composer deps:check` falha) | CT-02 | "toda referência aponta para script que existe": o mutante tem referência sem destino |
| M6 | o comando existe com outro nome (`check:deps`, `deps`) | CT-02 | `scripts['deps:check']` ausente: a expansão começa vazia e as duas inclusões falham |

---

## Regra R3 — O CI roda as duas ferramentas pelo `vendor/bin`, depois de instalar as dependências

> `RQ-04` · perfil **mínimo** · técnica: **EP** (origem do binário: `vendor/bin` direto ✓ · `composer <script>` que resolve no `vendor/bin` ✓ · action de terceiro, phar baixado, `composer global` ✗)

```gherkin
  Regra: o CI usa as ferramentas instaladas pelo require-dev

    Cenário: [CT-03] o passo do CI roda as duas ferramentas do vendor/bin, depois do composer install
      Dado os workflows de CI do kit
      Quando o mantenedor localiza o passo que roda as ferramentas de dependência
      Então o passo chama "composer-unused" e "composer-require-checker" por "vendor/bin/" ou por um script do composer.json que as chama
      E o passo não usa "uses:", nem baixa ".phar", nem roda "composer global"
      E no mesmo job há um passo anterior que roda "composer install"
```

`@premissa` (mecanismo, raia desenho): as duas formas aceitas de "pelo `vendor/bin`" são o caminho literal e o script do composer, que põe `vendor/bin` no `PATH`. O cenário não escolhe entre elas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 | ferramenta obtida por action de terceiro ou phar baixado (versão diverge da do `require-dev`) | CT-03 | "não usa `uses:`/`.phar`/`composer global`": o mutante tem um dos três no passo |
| M8 | passo colocado antes do `composer install` (sem `vendor/`; com o "não reprovar", some calado) | CT-03 | "há passo anterior com `composer install`": no mutante o índice do passo de install é maior |
| M9 | CI roda só uma das ferramentas | CT-03 | "chama `composer-require-checker`": o passo do mutante não a cita (nem pelo script) |

---

## Regra R4 — O CI publica o relatório das duas no resumo do job e o job continua verde com achados

> `RQ-05`, `P-02` · perfil **mínimo** · técnica: **rastreio de efeito** — o QUE: o relatório de cada ferramenta, no canal `GITHUB_STEP_SUMMARY` (P-02); direções: publicado **com** achado e **sem** reprovar. A direção "uma só vez" não se aplica (append num arquivo por execução); com o teto de 1 cenário do perfil mínimo, as duas direções vivem no mesmo cenário porque o mesmo `Quando` as produz.

```gherkin
  Regra: o CI relata e não reprova

    Cenário: [CT-04] com achado, o passo publica os dois relatórios no resumo e não reprova o job
      Dado que ao menos uma das duas ferramentas, rodada sozinha no kit, sai com código diferente de 0
      E um arquivo vazio no lugar de GITHUB_STEP_SUMMARY
      Quando o passo do CI que roda as ferramentas é executado como o runner faz, em "bash -eo pipefail"
      Então o passo termina com código 0, ou o passo declara "continue-on-error: true"
      E o arquivo do resumo contém a saída do composer-unused
      E o arquivo do resumo contém a saída do composer-require-checker
```

- O primeiro `Dado` é **precondição medida**, não suposta: sem achado, "não reprova" seria asserção em mundo vazio (gate, item 7). Se um dia o kit zerar os achados, o CT fica vermelho na precondição e precisa de uma fixture com achado — vermelho correto, não flake. O `Dado` não exige achado nas **duas**: o `composer-unused` pode legitimamente sair 0 depois da lista de exclusão (P-03) se nenhum pacote for `sem uso`. Consequência declarada: M11 e M12 só morrem enquanto a ferramenta que roda **primeiro** no passo tiver achado; se ela sair 0, a segunda roda de qualquer jeito e os dois mutantes ficam latentes (vivos sem efeito hoje). O CT registra, na mensagem da precondição, qual ferramenta saiu ≠ 0.
- "Contém a saída de X": um trecho fixo que a ferramenta imprime no relatório, confirmado no vendor (`arquivo:símbolo:linha`) na implementação — `@premissa` de mecanismo. Um título escrito pelo passo não basta: o mutante M11 escreve o título e perde a saída.
- `@premissa`: o `run` é executado com `bash -eo pipefail` porque é o shell padrão do `run` no runner Linux do GitHub; `${{ }}` no `run`, se houver, é resolvido para o valor fixo do teste.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | passo sem nenhuma guarda (achado reprova o job) | CT-04 | "código 0 ou `continue-on-error`": o mutante sai ≠ 0 e não declara `continue-on-error` |
| M11 | guarda só na primeira ferramenta; com `-e`, a segunda nunca roda | CT-04 | "resumo contém a saída do `composer-require-checker`": no mutante o arquivo só tem a do `composer-unused` |
| M12 | `continue-on-error: true` com as duas no mesmo `run`: a primeira sai ≠ 0 e a segunda nunca roda | CT-04 | "resumo contém a saída da segunda": ausente no mutante, embora o código de saída seja aceito |
| M13 | saída só no log do passo (`echo` sem redirecionar ao `GITHUB_STEP_SUMMARY`) | CT-04 | "resumo contém a saída do `composer-unused`": o arquivo do mutante fica vazio |
| M14 | `vendor/bin/x \| tee -a "$GITHUB_STEP_SUMMARY"`: com `pipefail`, o código ≠ 0 da ferramenta reprova o passo | CT-04 | "código 0 ou `continue-on-error`": o mutante sai ≠ 0 |

---

## Regra R5 — A configuração das ferramentas viaja nas duas rotas de entrega

> `P-06` · perfil **mínimo** · técnica: **EP** (rota de entrega, conforme o glossário: `create-project` por exclusão no `.gitattributes` · `kit:update` por inclusão em `CAMINHOS_DO_KIT`)

```gherkin
  Regra: a configuração versionada das ferramentas chega ao projeto pelas duas rotas de entrega

    Cenário: [CT-05] o arquivo de configuração do composer-unused está nas duas rotas de entrega
      Dado o arquivo de configuração do composer-unused na raiz do kit
      Quando o mantenedor confere as duas rotas de entrega
      Então o caminho está na lista do kit:update
      E nenhuma linha "export-ignore" do .gitattributes, fora de comentário, cobre o caminho
```

Se a implementação versionar também um arquivo de configuração do `composer-require-checker`, ele entra como segunda linha deste cenário (`Esquema do Cenário`), sem CT novo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | arquivo criado e esquecido em `CAMINHOS_DO_KIT` (projeto antigo atualiza sem a exclusão e o relatório acusa falso positivo) | CT-05 | "está na lista do `kit:update`": `caminhosDoKit()` do mutante não contém o caminho |
| M16 | arquivo marcado `export-ignore` junto com o ferramental do repositório (`create-project` nasce sem ele) | CT-05 | "nenhum `export-ignore` cobre": o mutante tem a linha |
| M17 | configuração embutida num lugar que não viaja (só no `run` do workflow, por argumento de linha de comando) | CT-05 | "o arquivo existe na raiz": o `Dado` falha no mutante |

---

## Regra R6 — A lista de exclusão só tem pacote usado por outro caminho, cada um com motivo

> `P-03` · perfil **mínimo** · técnica: **EP** sobre o veredito da triagem (`usado por outro caminho` ✓ pode ser excluído · `sem uso` ✗ · `uso direto de dependência só transitiva` ✗ · pacote ausente da triagem ✗)

```gherkin
  Regra: excluir do relatório é só para o falso positivo

    Cenário: [CT-06] todo pacote excluído do composer-unused está na triagem como usado por outro caminho, com motivo
      Dado a lista de pacotes excluídos no arquivo de configuração do composer-unused, fora de comentário
      E a tabela de triagem da documentação de qualidade
      Quando o mantenedor cruza cada pacote excluído com a triagem
      Então cada pacote excluído tem linha na triagem com o veredito "usado por outro caminho" e evidência não vazia
      E nenhum pacote com veredito "sem uso" está na lista de exclusão
```

`@premissa`: "pacote excluído" = string `vendor/pacote` entre aspas no código do arquivo de configuração, depois de tirar comentário. O motivo de cada exclusão é a evidência da linha da triagem (o `00` não fixa se o motivo também mora no comentário do arquivo).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M18 | pacote realmente sem uso excluído para o relatório sair limpo | CT-06 | "nenhum `sem uso` excluído": o pacote do mutante tem veredito `sem uso` e está na lista |
| M19 | pacote excluído sem entrada nem motivo na triagem | CT-06 | "tem linha na triagem com evidência": o mutante não acha a linha |
| M20 | pacote de uso direto de dependência só transitiva excluído como se fosse falso positivo | CT-06 | "veredito `usado por outro caminho`": a linha do mutante tem outro veredito |

---

## Regra R7 — A triagem registra cada pacote acusado com veredito do domínio e evidência

> `RQ-06`, `P-04` · perfil **mínimo** · técnica: **EP** (veredito ∈ {usado por outro caminho, sem uso, uso direto de dependência só transitiva}; fora do domínio ✗; evidência vazia ✗)

```gherkin
  Regra: a pergunta "quais dependências são de fato usadas?" tem resposta registrada

    Cenário: [CT-07] a documentação de qualidade traz a triagem com veredito e evidência em cada linha
      Dado a documentação de qualidade do kit
      Quando o mantenedor lê a tabela de triagem das dependências
      Então a tabela tem ao menos uma linha
      E o veredito de cada linha é "usado por outro caminho", "sem uso" ou "uso direto de dependência só transitiva"
      E a evidência de cada linha não está vazia
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M21 | triagem não registrada (a resposta continua "não sabemos") | CT-07 | "ao menos uma linha": a tabela não existe no mutante |
| M22 | linha com veredito fora do domínio ("a verificar", "talvez") — a resposta volta a ser "não sabemos" por linha | CT-07 | "veredito ∈ domínio": a linha do mutante tem outro texto |
| M23 | veredito sem evidência (afirmação sem prova, o contrário de "mecanicamente") | CT-07 | "evidência não vazia": a célula do mutante está vazia |

---

## Regra R8 — Achado que não é falso positivo fica registrado como recomendação

> `P-05` · perfil **mínimo** · técnica: **EP** (veredito `sem uso` ou `uso direto de dependência só transitiva` → recomendação obrigatória · `usado por outro caminho` → não exige)

```gherkin
  Regra: o que a triagem acha vira recomendação ao mantenedor, não mudança de dependência

    Cenário: [CT-08] cada linha da triagem com veredito diferente de "usado por outro caminho" tem recomendação registrada
      Dado a tabela de triagem da documentação de qualidade
      Quando o mantenedor filtra as linhas com veredito "sem uso" ou "uso direto de dependência só transitiva"
      Então cada uma dessas linhas tem uma recomendação não vazia para decisão do mantenedor
```

Se a triagem tiver zero linhas desses dois vereditos, o cenário passa vácuo: o CT afirma, como precondição, que a filtragem devolve ao menos uma linha; se a resposta real for "nenhum achado", o CT vira vermelho e volta à sessão como achado de requisito, não é afrouxado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M24 | pacote `sem uso` listado sem recomendação (fica registrado e ninguém sabe o que fazer) | CT-08 | "recomendação não vazia": a célula da linha `sem uso` do mutante está vazia |
| M25 | recomendação só para os `sem uso`, esquecendo os de dependência só transitiva | CT-08 | linha `uso direto de dependência só transitiva` sem recomendação no mutante |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: sem rota, sem `{id}`, sem usuário | — |
| Autorização exercida na ação | não se aplica: sem policy nem permission; `02` §Superfície Livewire declara zero superfície | — |
| Idempotência | não se aplica: nenhuma escrita persistida; o append no resumo é por execução de job | — |
| Concorrência | não se aplica: sem contador, saldo ou limite | — |
| Fronteira no ponto de entrada | não se aplica: sem campo nem gravação | — |
| Domínio condicionado | não se aplica: sem campo dependente | — |
| Estado × operação de escrita | não se aplica: sem entidade com ciclo de vida | — |
| Ausente ≠ null ≠ vazio | CT-07 (evidência vazia), CT-08 (recomendação vazia) | Triagem na documentação de qualidade |
| Paginação / ordenação | não se aplica: sem listagem | — |
| Timezone / DST | não se aplica: nada depende de data | — |
| Unicode / limite de varchar | não se aplica: sem campo de texto gravado | — |
| Unicidade + soft delete | não se aplica: sem model | — |
| CRUD combinado | não se aplica: sem model | — |
| Mass assignment | não se aplica: sem payload | — |
| Upload | não se aplica: sem upload | — |
| Precisão monetária | não se aplica: sem dinheiro | — |
| Superfície Livewire | não se aplica: `02` §Superfície Livewire — nenhum `public function` nem `public $` novo | — |
| Estado do framework usado sem validar | não se aplica: sem componente | — |
| IDOR por entidade | não se aplica: nenhuma tabela persistida | — |
| Escopo com discriminante nulo | não se aplica: sem filtro de escopo | — |
| Saída do estado de erro | não se aplica: sem 4xx/5xx/redirect | — |
| Efeito com destinatário variável (0/1/N) | CT-04 — o `Dado` mede que há achado (código ≠ 0 em ao menos uma ferramenta), nunca mundo vazio; os dois relatórios (N = 2) são afirmados | Passo do CI executado |
| Completude da triagem (todo pacote acusado pela ferramenta tem linha) | lacuna declarada: exige casar a saída do `composer-unused` com a tabela, e o formato da saída é do vendor; tentado reaproveitar a execução do CT-04, recusado para não somar um segundo oráculo a um CT do perfil mínimo. Fica para a conferência manual do quality gate | Triagem na documentação de qualidade |
| Dependência de produção alterada nesta entrega (P-05) | lacuna declarada: CT permanente sobre o `require` expira quando o mantenedor acatar a recomendação; conferência do diff (`composer.json` `require` antes × depois) no quality gate | — |

## Revisão Adversarial

Rodada 1 (`fw-adversario-ct`, 2026-11-18): 15 achados. Fechados pela sessão acrescentando `Então` aos cenários existentes e um cenário novo (CT-09); o perfil é baixo risco, sem rodada 2.

| ADV | Destino |
|---|---|
| ADV-01 (alta) | CT-03 ganha: "o `composer install` que precede o passo, no mesmo job, não traz `--no-dev`" |
| ADV-02 (alta) | CT-04 ganha: "o resumo contém o nome de ao menos um pacote que a triagem dá como sem uso" (prova conteúdo, não banner) |
| ADV-03 (média) | CT-06 ganha: "nem o `deps:check` nem o `run` do passo do CI trazem `--excludePackage`" |
| ADV-04 (média) | CT-06 ganha: "a configuração só exclui por nome literal `vendor/pacote` (nenhum padrão `/…/`)" |
| ADV-05, ADV-06 (média) | CT-05 ganha: "nenhum `--config-file` aponta para arquivo fora da lista do `kit:update`", e o `composer-unused.php` tem o nome que a ferramenta lê sozinha (sem `--config-file`) |
| ADV-07, ADV-08 (média) | CT-08 ganha: "a triagem registra os achados do `composer-require-checker` — a contagem de símbolos, os pacotes só transitivos e as extensões `ext-*` — com recomendação" |
| ADV-09 (baixa) | CT-03 ganha: "o passo está no job `qualidade` do workflow de CI, sem `if:` no passo" |
| ADV-10 (baixa) | CT-06/CT-07: a evidência não é só marcador (`—`, `-`, `N/A`, `?`) |
| ADV-11 (baixa) | M11/M12 condicionais: o `Dado` do CT-04 já tem achado nas duas ferramentas no estado atual da árvore (3 sem uso e 181 símbolos); registrado |
| ADV-12 (baixa) | M18 atribuído ao 1º `Então` do CT-06 |
| ADV-13 (alta) | CT-09 novo (P-07) |
| ADV-14 (média) | mantido o corte: a completude da triagem fica no CT-08 pelos três pacotes sem uso e pelos grupos do require-checker; casar a saída inteira acoplaria o CT ao formato da ferramenta |
| ADV-15 (baixa) | mantido: P-05 conferida no diff do lock (step 9 e gate) |

```gherkin
Cenário: [CT-09] o deps:check fica fora do gate local
  Dado o composer.json do kit
  Quando se expande o script "test" pelos scripts que ele chama
  Então nenhum deles é "deps:check", "deps:unused", "deps:require", nem chama composer-unused ou composer-require-checker
```

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | a ferramenta é dependência de desenvolvimento travada no lock | R1 | EP | Arquivos de ferramental do kit | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M1, M2, M3 |
| CT-02 | `deps:check` alcança as duas ferramentas | R2 | EP | Arquivos de ferramental do kit | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M4, M5, M6 |
| CT-03 | o passo do CI roda as duas do `vendor/bin`, depois do `composer install` | R3 | EP | Arquivos de ferramental do kit | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M7, M8, M9 |
| CT-04 | com achado, o passo publica os dois relatórios e não reprova | R4 | rastreio de efeito | Passo do CI executado | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M10…M14 |
| CT-05 | a configuração está nas duas rotas de entrega | R5 | EP | Arquivos de ferramental do kit | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M15, M16, M17 |
| CT-06 | todo excluído é "usado por outro caminho" na triagem | R6 | EP | Triagem na documentação de qualidade | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M18, M19, M20 |
| CT-07 | a triagem tem veredito do domínio e evidência | R7 | EP | Triagem na documentação de qualidade | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M21, M22, M23 |
| CT-08 | achado real tem recomendação registrada | R8 | EP | Triagem na documentação de qualidade | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M24, M25 |
| CT-09 | o deps:check fica fora do gate local | R2 | EP | Arquivos de ferramental do kit | Pest feature HTTP | `tests/Kit/VerificacaoDeDependenciasTest.php` | M26 (`composer test` chama `@deps:check`) |

O arquivo é proposta (nome do domínio do `00`, padrão `{Assunto}Test.php` dos irmãos); `->group('kit')` como em `QualidadeDeCodigoTest`.

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| CT estático: o passo do CI tem `continue-on-error: true` | prova o mecanismo, não o resultado; deixa vivos M11, M12 e M13; substituído pelo CT-04 (Q?1) |
| CT: `composer deps:check` não está no `composer test` | o `00` não decide; virou Q?2 |
| CT: o `require` do `composer.json` não mudou nesta entrega | expira quando o mantenedor agir sobre a recomendação; conferência do diff no quality gate |
| CT: rodar `composer deps:check` localmente e afirmar a saída | mata os mesmos M4–M6 que o CT-02 estático, a um custo de dezenas de segundos |
| CT: as traduções `docs/{pt,en}` trazem a mesma triagem | o `00` pede a resposta registrada, não N cópias; paridade de tradução é da rede de documentação existente |
| CT: o passo do CI roda em `push` e `pull_request` | os gatilhos são do workflow inteiro, não desta feature; nenhum mutante plausível desta entrega os muda |

## Sem CT-B

- Motivo: nenhuma linha de `## Costuras de Teste` tem costura `browser`; a feature não tem `## Superfície de UI` nem superfície Livewire (`02`). O `05` não é criado.
