# Checklist de release — os quatro cenários

> **Este documento é do processo de quem MANTÉM o kit, não de quem o instala.**
>
> Ele viaja junto com o `composer create-project` pela mesma razão que os outros `wikis/*.md`, mas
> se você derivou um projeto do kit, isto aqui não é tarefa sua — pode apagar. O que interessa a
> você é o [roadmap](roadmap.md) e as [convenções](convencoes.md).

Companheiro do [roadmap](roadmap.md). Aquele responde *"o que ainda não entrou"*; este responde
**"o que conferir antes de dizer que entrou"**.

---

## A regra

**A cada nova tag, rodar os quatro cenários.** Suíte verde na árvore do kit **não** é suficiente, e
a seção [O que já quebrou aqui](#o-que-já-quebrou-aqui) explica por quê com um caso real.

| # | Cenário | Como nasce | O que ele cobre que os outros não |
|---|---|---|---|
| 1 | **Instalação limpa, sem tenancy** | `composer create-project` na tag nova | o caminho de quem começa hoje — o mais comum |
| 2 | **Instalação limpa, com tenancy** | idem + `php artisan kit:tenancy --force` | o `migrate:fresh` com `permission.teams` ligado **antes** das migrations |
| 3 | **`kit:update`, sem tenancy** | `create-project` na tag **anterior** + `kit:update --all` | a lista `CAMINHOS_DO_KIT` — arquivo novo que não está nela **não chega a quem já instalou** |
| 4 | **`kit:update`, com tenancy** | idem, com `kit:tenancy` **antes** do update | se a tenancy sobrevive ao update |

**Por que os quatro, e não dois**: instalação limpa e `kit:update` são caminhos de entrega
**diferentes** — o primeiro é governado pelo `.gitattributes`, o segundo por
`KitUpdate::CAMINHOS_DO_KIT`. Um arquivo pode viajar por um e não pelo outro, e isso já aconteceu
(ver o caso do roadmap abaixo). Tenancy dobra porque ela muda o schema, não a configuração.

---

## O roteiro

Tudo fora da árvore do kit, num diretório descartável.

```bash
mkdir validacao-vX.Y.Z && cd validacao-vX.Y.Z

# 1 e 2 — instalações limpas na tag nova
composer create-project gsferro/starter-kit-easy novo-sem-tenant "vX.Y.Z" --no-interaction
composer create-project gsferro/starter-kit-easy novo-com-tenant "vX.Y.Z" --no-interaction

# 3 e 4 — a tag ANTERIOR, para depois atualizar
composer create-project gsferro/starter-kit-easy velho-sem-tenant "vX.Y.(Z-1)" --no-interaction
composer create-project gsferro/starter-kit-easy velho-com-tenant "vX.Y.(Z-1)" --no-interaction
```

> **O Packagist leva alguns minutos para indexar a tag nova.** Conferir antes de começar:
> `curl -s https://repo.packagist.org/p2/gsferro/starter-kit-easy.json | grep -o '"version":"vX.Y.Z"'`
> — o Packagist devolve a versão **com** o `v`, como a tag.
>
> **Pinar a versão no `create-project` é o que torna isso seguro**: sem ela indexada, o comando
> **falha** (`Could not find package … with version`), alto e claro. É esperar e repetir. Sem pinar,
> o composer pegaria a versão anterior **em silêncio** e os quatro cenários mediriam a release
> errada sem que nada avisasse.

### Ligar a tenancy (cenários 2 e 4)

```bash
cd novo-com-tenant
git init && git add -A && git commit -m "estado antes da tenancy"   # o comando EXIGE git
php artisan kit:tenancy --force --no-interaction                     # --force: recria o banco
```

O `git init` não é formalidade: o `kit:tenancy` recusa rodar sem repositório, porque é isso que
torna a mudança reversível. O `--force` é necessário fora de terminal interativo — ele confirma o
`migrate:fresh`, que é **destrutivo** e por isso só é inócuo num projeto recém-criado.

### Atualizar (cenários 3 e 4)

```bash
cd velho-sem-tenant
git init && git add -A && git commit -m "vX.Y.(Z-1) recem instalada"
php artisan kit:update --all --no-interaction
php artisan migrate --force
```

> **No cenário 4 há um commit A MAIS, e ele não é zelo.** O `kit:tenancy` deixa a árvore suja
> (medido: 6 arquivos — `.env`, `config/permission.php`, `config/filament-shield.php`, o banco e
> dois logs), e o `kit:update` **recusa** árvore suja, de propósito: é isso que permite distinguir
> o que é seu do que o kit trouxe. Sem o commit no meio, o cenário 4 falha com
> `ERROR Há alterações não commitadas na árvore de trabalho`:
>
> ```bash
> cd velho-com-tenant
> git init && git add -A && git commit -m "vX.Y.(Z-1) recem instalada"
> php artisan kit:tenancy --force --no-interaction
> git add -A && git commit -m "depois da tenancy, antes do update"   # <- ESTE
> php artisan kit:update --all --no-interaction
> php artisan migrate --force
> ```
>
> A redação anterior dizia apenas *"idem, com `kit:tenancy` antes do update"*, e o *"idem"* não
> carregava o commit extra. Achado da validação da `v0.39.0`, onde o cenário 4 ficou parado na
> versão antiga sem que a falha fosse óbvia: o `artisan` saiu com código 0 e a mensagem de recusa
> rolou para fora do `tail`. **O sinal confiável é a versão**, conferida logo após o update.

> **O `kit:update` nunca apaga arquivo, e isso é desenho.** Um arquivo **renomeado** no kit chega
> pelo nome novo e o nome velho **fica** no projeto atualizado — órfão, mas presente. O comando
> avisa (`KitUpdate.php:798`: *"foi REMOVIDO do kit. Nada é apagado automaticamente — decida
> você"*). Então **não** trate a presença do nome antigo como falha do cenário 3 ou 4;
> a v0.39.0 renomeou `versao-do-kit.blade.php` e as duas blades convivem em projeto atualizado.

`--all` aplica tudo sem revisar arquivo a arquivo. Num projeto de validação é o que se quer; num
projeto real, **não** — ali o modo interativo existe para você escolher.

### Conferir, em cada um dos quatro

```bash
php artisan test --testsuite=Kit,Tenancy --parallel --compact
```

| O que conferir | Como | Esperado |
|---|---|---|
| **Zero erro e zero falha** | a saída do comando acima | é o critério de "liso"; **pulado declarado não conta contra** |
| Contagem de **pulados** | mesma saída | **não pode passar do teto** registrado na release anterior. Subiu? **justificar por escrito, caso a caso**, antes da tag — ver a nota abaixo |
| Versão | `php artisan tinker --execute "echo config('kit.version');"` | a tag nova **sem o `v`** (`0.38.1`, não `v0.38.1`), nos quatro. **É o único sinal confiável de que o `kit:update` de fato aplicou** — ele pode recusar e sair com código 0 |
| Tenancy (2 e 4) | `… echo config('kit.tenancy.enabled') ? 'SIM' : 'NAO';` | `SIM` |
| Arquivo novo da release | `ls` no caminho dele | presente nos quatro — se faltar só nos de update, o problema é `CAMINHOS_DO_KIT` |
| Migration nova | saída do `migrate --force` | rodou nos cenários 3 e 4 |

> ### Sobre os pulados — e por que aqui há **teto**, não só registro
>
> **Teto corrente: 190**, medido na `v0.40.1` (cenários 3 e 4). A decomposição por causa está na
> seção `Validacao dos quatro cenarios — v0.40.0 e v0.40.1` do `CHANGELOG.md`, e ela foi feita com
> `--log-junit` **depois** de uma estimativa por `grep` ter errado por 2 — a chamada de
> `naArvoreDoKit()` também aparece dentro de corpo de caso, e dataset de N linhas conta N pulados.
> **Não estime: meça.**
>
> Na `v0.38.0` foram **145** por cenário, e eles são deliberados: os casos que não se aplicam fora
> da árvore do kit (site de documentação, fluxos do GitHub Actions, histórico de planejamento). O
> número **envelhece** a cada release, e é medição histórica: vale para a `v0.38.0`, não para a
> próxima.
>
> **A regra**: a contagem de uma release **não pode passar do teto da anterior**. Se passar, o
> aumento é **justificado por escrito** antes da tag sair — **por causa, não por unidade**: *"+26,
> arquivo de testes novo do roteiro, que só vale na árvore do kit"* é uma justificativa completa
> para 26 pulados. O teto novo vira o da próxima release.
>
> **Não tente prever o número.** A primeira versão desta seção previa `145 → 147`, contando só os
> dois casos que ela tinha em mente, e errou por ~25: **todo** caso guardado por `naArvoreDoKit()`
> pula lá fora, e a própria entrega que escreveu este parágrafo acrescentou um arquivo inteiro
> deles. Previsão de contagem envelhece no commit seguinte. **Meça no passo 6 e registre**; o que
> não envelhece é o comando ao lado do número.
>
> **Por que teto e não só "anotar e comparar"**, que era a regra original: *"pulado declarado não
> conta contra"* é **anti-conservador justamente nesta classe de defeito**, porque a correção
> canônica dela **é acrescentar um `skip`**. O critério isenta exatamente a métrica que toda
> correção futura infla. Um caso que deixe de rodar **indevidamente** e uma correção **legítima**
> produzem a mesma linha — *+1 pulado, zero falha* — e só a justificação os separa.
>
> Registrar deixa o sinal visível e não obriga ninguém a olhar. Teto **reprova** na release em que
> o número sobe sem explicação.

### Limpar

```bash
cd .. && rm -rf validacao-vX.Y.Z
```

---

## O que já quebrou aqui

Esta seção é o motivo de o checklist existir. São dois casos da `v0.38.0`, e eles são
**diferentes um do outro** — o primeiro nenhum gate podia pegar, o segundo um gate pegou. Os dois
justificam o roteiro, por razões opostas.

### `v0.38.0` — teste que viaja lendo arquivo que não viaja

> **Este passou por todos os gates**, e é o caso que o roteiro existe para achar.

**Sintoma**: os quatro cenários deram `2773 / 2627 verdes / 145 pulados / 1 erro`, **idêntico byte
a byte**.

```
File does not exist at path …/docs/pt/comecar/dominio-local.md
```

**Causa**: `tests/Kit/HostLocalTest.php` `[CT-12]` usa uma página de `docs/` como oráculo
documental — ele confronta o comando que o código emite com o comando que a página ensina. `docs/`
está no `export-ignore`: **o teste viaja e o arquivo que ele lê não**.

**Por que nenhum gate pegou**: rodada **na árvore do kit**, a suíte tem o `docs/` presente. O
defeito só é observável **de dentro de um projeto instalado**. Ele atravessou três ciclos de
quality gate, um `/code-review`, um `fw-revisor-diff` e dois `fw-qa-gate` — nenhum deles roda fora
da árvore do kit, e **nenhum poderia**. A causa é estrutural, não falta de rigor.

**Correção**: `->skip(fn () => ! naArvoreDoKit(), …)`, que é o **mesmo padrão que outro caso do
próprio arquivo já usava**.

**A lição**: foram escritas **três varreduras estáticas** para achar outros casos iguais, e as
três erraram — 8 arquivos, 20 casos, 17 casos, contra **1** real. O kit tem três mecanismos de
guarda diferentes (`->skip()` por caso, `markTestSkipped()` no `beforeEach`, e não-leitura), e cada
varredura deixou de enxergar um deles.

Isso levou, na primeira versão deste documento, à conclusão de que **não havia** guarda
automática possível para a classe. **Estava errado**, e a revisão de diff da correção mostrou por
quê: uma guarda já existia (`RedeDeDocumentacaoTest` `[CT-10]`, verde o tempo todo porque olha o
arquivo e não o caso), e uma quarta varredura, escrita por quem não tinha lido as três primeiras,
acertou. Hoje o `[CT-11]` cobre a fatia decidível — caminho **literal** lido no corpo do caso — e
foi provado por mutação contra este defeito. Ver
`wikis/specs/fix/validacao-de-release/validacao-de-release/02-decisoes-arquiteturais.md`, ADR-02.

**O que continua sem guarda automática possível** é o resto: tudo o que só acontece **fora da
árvore do kit**. Para essa classe, **instalar e rodar é a única medição confiável** — não uma
entre várias, e nenhuma varredura estática a substitui, porque toda varredura roda **dentro** da
árvore, onde o defeito não existe. O `[CT-11]` cobre **uma** classe vizinha e não dispensa nada
daqui.

E a classe a reconhecer no próximo caso é esta: **teste que viaja lendo arquivo que não viaja.**
Quem for procurá-la precisa saber que o kit tem **três** mecanismos de guarda, e que nenhum é
sinônimo dos outros — `->skip()` **por caso**, `markTestSkipped()` no `beforeEach` **por arquivo**,
e **não-leitura** (o caso só cita o caminho como string). Cada uma das três varreduras que erraram
deixou de enxergar exatamente um deles.

### `v0.38.0` — arquivo novo que não chegava a quem já instalou

> **Este NÃO passou pelos gates — um teste automatizado o pegou.** Está aqui porque a
> **forma** dele é a que os cenários 3 e 4 cobrem, e porque o que ficou verde ensina mais que o
> que ficou vermelho.

**Sintoma**: nenhum, na instalação limpa. O `wikis/roadmap.md` chegava por `create-project` e
**não** por `kit:update`.

**Causa**: o arquivo entrou no repositório e não em `KitUpdate::CAMINHOS_DO_KIT`.

**Quem pegou**: o `KitUpdateTest` ficou vermelho — *a única falha da suíte `Kit` inteira*. A
guarda existia desde 14/08/2026, cinco semanas antes de o roadmap nascer, e funcionou como
desenhada. O `/code-review` reportou o achado; o **detector** foi o teste.

**O que ensina, e é o motivo de estar num checklist de release**: na mesma branch havia um caso
escrito **exatamente** para garantir que o roadmap *"viaja com o projeto"* — e ele estava
**verde**. Viajar tem dois caminhos, e o caso afirmava um só:

| Caminho | Governado por | Entrega a |
|---|---|---|
| `composer create-project` | `.gitattributes` | quem instala **agora** |
| `php artisan kit:update` | `KitUpdate::CAMINHOS_DO_KIT` | quem **já** instalou |

O README prometia nas duas línguas que o roadmap vem junto com o projeto. A promessa estava no
texto, um mecanismo estava certo, o outro não existia, e o caso desenhado para cobrir a promessa
não tocava nele. **Teste verde sobre meia promessa é pior que teste ausente**, porque compra
confiança.

É essa a assimetria que os cenários **3 e 4** existem para medir, e que os cenários 1 e 2 **não
podem** medir. E ela reincidiu durante a própria escrita deste documento: o `KitUpdateTest`
reprovou assim que `wikis/checklist-de-release.md` foi criado, pelo mesmo motivo.

---

## Depois dos quatro — o registro, e ele vem **antes** da tag

**Nada disto é opcional, e a ordem importa**: o registro da evidência existe **antes de a tag de
correção ser publicada**, não depois. Tag publicada é irreversível na prática — o Packagist já
indexou, e alguém já pode ter rodado `create-project`. Registro feito depois é justificativa, não
validação.

O que fica registrado, no `CHANGELOG` ou na própria release do GitHub:

| O que | Por que este item, e não só "deu verde" |
|---|---|
| **A versão validada**, exata | "rodei os quatro" sem a versão não diz **contra o quê**. Já houve caso de o Packagist ainda não ter indexado e a medição ser de outra release |
| **Os quatro diretórios**, nomeados um a um | é o que distingue *"rodei os quatro"* de *"rodei dois e presumi os outros dois porque os números foram idênticos"*. Os números **foram** idênticos na `v0.38.0`, nos quatro |
| **A saída colada** de cada execução | número sem o comando que o produziu é alegação. Colar a saída é o que torna a contagem de pulados conferi­vel por outra pessoa |
| **A contagem de pulados** de cada cenário, e a justificativa de todo aumento | é o teto da seção anterior. Sem o número registrado, a próxima release não tem contra o que comparar |

- Se **tudo liso**: com o registro acima feito, a release está validada. **Aí** sai a tag
- Se **algo quebrou**: corrigir e lançar **versão de correção**, depois **reexecutar os quatro**
  contra ela — e registrar de novo, com a mesma exigência, antes da tag nova. Suíte verde na
  árvore do kit não fecha o caso: o defeito que originou este documento era invisível ali
