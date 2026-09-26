# Roadmap — o que ainda não entrou no kit, e por quê

> **Este documento é sobre o futuro do KIT, não sobre o do seu projeto.**
>
> Ele viaja junto com o `composer create-project` de propósito, pela mesma razão que os outros
> `wikis/*.md`: é material de trabalho de quem instala. Serve para você saber **o que o kit já
> olhou e decidiu adiar** — e assim não gastar uma tarde reavaliando do zero algo que já tem
> medição registrada aqui.
>
> Nada nesta página é compromisso de data. Depois do `create-project`, o projeto é seu: se um item
> daqui for urgente para você, implementá-lo no seu projeto é legítimo e não conflita com nada.

Companheiro de [`pacotes-candidatos.md`](pacotes-candidatos.md), que responde à mesma pergunta para
**pacotes**. Este responde para **capacidades**.

---

## 1. Preferências de usuário — densidade, fonte, cor e modo do menu

**Estado**: analisado, não implementado · **Origem**: `wikis/specs/feat/layout-compact/`

Hoje a densidade do layout é uma configuração **da instalação**: quem administra escolhe em
`/admin` → Configurações → Kit, e vale para todo mundo. O passo seguinte natural é a escolha ser
**de cada pessoa**, guardada no perfil:

| Preferência | Situação hoje | O que falta |
|---|---|---|
| **Densidade do layout** | existe, por instalação | mover a leitura para o usuário logado, com a da instalação como padrão |
| **Tamanho da fonte** | não existe | mesma técnica da densidade — o Tailwind 4 do Filament deriva tudo de `--text-*` |
| **Cor primária** | existe por instalação e por organização | falta o nível do usuário |
| **Modo de ocultação do menu** (esconder tudo × manter o ícone, que é o padrão hoje) | não existe | é a única das quatro que **não** sai de variável CSS |

**Por que não entrou junto com a densidade.** A densidade custou uma declaração CSS porque já havia
onde guardá-la (o settings do kit) e onde lê-la (um render hook avaliado por request). Preferência
por usuário precisa de **tabela, tela de perfil e leitura por sessão** — é uma feature inteira, não
o mesmo trabalho com outro sujeito.

**A hipótese que vale registrar**: isto é candidato natural a **pacote Filament externo**. Ele
nasceria no kit, mas nada nele depende do kit — e um pacote de "preferências de aparência por
usuário" é útil para qualquer painel Filament, podendo ser evoluído por terceiros. Se for por esse
caminho, o kit passa a consumir o pacote em vez de manter o código.

---

## 2. O tema compacto oficial e pago do Filament

**Estado**: estudado e **recusado para o kit** — mas pode ser a escolha certa para o **seu** projeto

[`filament/compact-theme`](https://filamentphp.com/plugins/filament-compact-theme), US$ 29, da
própria Filament. Medido por diff dos bundles da demo oficial:

| | tema pago | o que o kit faz |
|---|---|---|
| Ganho na altura da tabela | **−22,5%** | −16,8% no nível `compacto`, **−22,9%** no `denso` |
| Como | 548 blocos de regra sobre 370 classes `fi-*` | **uma** declaração de `--spacing` |
| Distorce ícone e fonte? | **não** — não mexe em `--spacing` nem `--text-*` | **sim** — ícones e inputs encolhem junto |
| Liga/desliga em runtime? | **não** — é build-time, exige `viteTheme()` + `npm run build` | **sim** |

**O que o desqualifica para o kit não é o preço, é a licença**: ela é de projeto único. Um starter
kit é distribuído, então embarcá-lo obrigaria **cada instalação** a comprar — a mesma família do
veto que `.ai/rules/general.md` já registra para o `filament/blueprint`.

**Para um projeto único, a conta muda.** Se a distorção de proporção do nível `denso` incomoda e
você quer o ganho sem ela, o tema pago é a melhor compra disponível, e a análise acima existe para
você decidir com número em vez de intuição. Ele exige migrar o painel para `viteTheme()` — ver o
item 4.

---

## 3. Densidade nativa do Filament — o gatilho que aposenta o item

**Estado**: aguardando o upstream · **É desejável que aconteça**

O Filament 5 **não** tem densidade global. Ele tem `compact()` **por componente**
(`Section`, `EmptyState`, `Repeater`), e `Table` não tem nem isso. Foi por isso que o kit resolveu
em CSS.

Se o Filament ganhar densidade de painel — algo como um `Panel::densely()` —, a implementação atual
do kit vira dívida: CSS mantido à mão onde existiria API. **O gatilho de reavaliação é esse**, e a
ação é trocar a declaração de `--spacing` pela API nativa, mantendo os mesmos três níveis na tela
de configurações para não quebrar quem já escolheu.

Vale dizer o lado bom: como o kit **não escreve nenhuma classe `fi-*`**, essa troca é local. Fosse
uma lista congelada de seletores de vendor, seria uma migração.

---

## 4. Armadilha conhecida: migrar para `viteTheme()` desliga o botão em silêncio

**Estado**: não é tarefa, é aviso para quem for mexer

O interruptor de densidade funciona porque é um **render hook**, avaliado a cada request. Se alguém
migrar o kit (ou o seu projeto) para um tema Vite customizado e mover a densidade para lá, o
`viteTheme()` é resolvido no **registro do painel** e **não aceita `Closure`**: a configuração
passa a ser lida uma vez, no boot.

O sintoma é o pior possível — a tela **grava** a escolha, não dá erro, e **nada muda** até o
próximo deploy. É exatamente o modo de falha que `.ai/rules/settings.md` já registra por outro
caso do kit.

Quem migrar para `viteTheme()` precisa reintroduzir a densidade por render hook, ou aceitar que ela
vira decisão de build.

---

## 5. Superfícies que a densidade não alcança

**Estado**: medido, documentado, sem plano

Estes três não encolhem, e não é defeito — é onde o valor não sai de `--spacing`:

| Superfície | Por que não responde | O que exigiria |
|---|---|---|
| **Altura da topbar** (64 px nos três níveis) | altura fixa, não derivada de variável | uma variável nova no layout base do Filament — não há API hoje |
| **Rail do menu colapsado** (`--collapsed-sidebar-width`, 72 px nos três) | **decisão, não limitação**: `collapsedSidebarWidth()` aceita `Closure` e daria para governar igual à largura normal | nada — foi deixado de fora de propósito, ver abaixo |
| **Cartões do Pulse** (`/infra/pulse`, 128 px nos três) | o Pulse carrega CSS própria | escrever regra mirando o Pulse — o tipo de acoplamento a vendor que o kit evita. As **tabelas** do Pulse apertam normalmente |

> **O rail colapsado é o único item desta página que está fora por escolha, e não por limitação.**
> Decidido com o usuário em 2026-09-21: o rail é *icon-only*, então a largura dele é ditada pelo
> **alvo de clique**, não por densidade de conteúdo. Encolher os 72 px espremeria o alvo sem ganhar
> área útil — e o ícone dentro dele **já** encolhe sozinho (24 → 19,2 → 16,8 px), porque sai de
> `--spacing`. Fica registrado com o motivo para não ser reaberto como se fosse esquecimento.
>
> **A largura do menu saiu desta lista.** Ela estava aqui como "candidata mais provável a entrar
> primeiro", e entrou: o quality gate apontou que deixá-la de fora violava o invariante do
> requisito — o menu é uma das quatro superfícies do escopo, e apertar só a altura é a "meia tela
> compacta" que ele proíbe. Hoje a largura acompanha os três níveis (320 → 272 → 264 px), por
> `Panel::sidebarWidth()` com `Closure`, avaliado por request.

---

## 6. Testar captcha e login social contra o serviço de verdade

**Estado**: analisado, não implementado · **Origem**: pedido do mantenedor em 2026-09-22, durante
a validação da `v0.38.1`

O kit tem **dez arquivos de teste** de login social e cobertura do captcha — todos com o provedor
**dublado**. Isso prova o nosso lado do contrato: que o callback monta o DTO certo, que conta
indisponível é recusada, que o vínculo de provedor respeita o painel. **Não prova a ponta**: que
a chave real, o domínio registrado e a URL de redirecionamento fecham contra o serviço.

### Os dois casos não são o mesmo problema

| | Captcha (reCAPTCHA, Turnstile, hCaptcha) | Login social (Google, GitHub) |
|---|---|---|
| Existe credencial de **teste oficial**? | **Sim** — os provedores publicam pares de chaves públicas feitas para isto: um sempre aprova, outro sempre reprova, outro força o desafio interativo | **Não** |
| Precisa de segredo? | **Não.** As chaves de teste são públicas por desenho e podem ser **versionadas**, inclusive no CI | **Sim** — `CLIENT_ID` e `CLIENT_SECRET` de um app OAuth real |
| O que a passada prova | que o widget renderiza, que o token viaja e que a verificação server-side interpreta aprovação **e** recusa | que o fluxo completo fecha contra o provedor |

**A metade do captcha é a fácil, e deveria vir primeiro** — ela não tem nada a resolver sobre
segredo, e o par "sempre reprova" é o que hoje **ninguém testa**: o caminho de recusa.

### O desenho proposto para a metade que precisa de segredo

- `.env.testing.local`, **no `.gitignore`**, com as credenciais reais de quem mantém o kit
- `.env.testing.local.example`, **versionado**, listando as chaves exigidas e onde obtê-las
- um helper `credencialDe('google')` em `tests/Pest.php`, e os casos guardados por
  `->skip(fn () => ! temCredencial('google'), '[credencial] …')`

### O risco, e ele é exatamente o desta wiki

**Teste que pula por falta de credencial fica verde, e a suíte parece completa.** É a mesma forma
do defeito que originou o `checklist-de-release.md` — ausência que não falha sozinha. Três
amarras, e nenhuma é opcional:

1. **O motivo do `skip` leva o prefixo `[credencial]`**, distinto do
   `→ não se aplica fora da árvore do kit`. Sem isso os dois viram o mesmo número na saída, e o
   teto de pulados do [checklist de release](checklist-de-release.md) deixa de discriminar —
   pular por falta de chave e pular por não se aplicar são coisas **opostas**
2. **Um caso exige que toda chave lida pelos testes esteja no `.example`**, para a lista não
   apodrecer em silêncio
3. **Um caso exige que `.env.testing.local` esteja no `.gitignore`** — guarda contra o vazamento,
   e é barata

### O que isso muda no roteiro de release

Os quatro cenários ganham uma **passada com credenciais**, que registra **quantos casos de
credencial rodaram** — não quantos pularam. Número de pulados é o que a passada existe para levar
a zero.

### Por que não entrou agora

Decisão do mantenedor: fechar as features desta release primeiro. Fica para a próxima.

---

## 7. ~~O kit registra o próprio middleware, em vez de depender do `bootstrap/app.php`~~ — FEITO

> Aberto em 2026-09-24 pela guarda `tests/Kit/DuasRotasDeEntregaTest.php`; **fechado em
> 2026-09-26**, em `wikis/specs/feat/plumb-e-dividas-tecnicas/`.
>
> **A saída não foi a que este item propunha.** Ele previa *mover* o registro para o
> `KitServiceProvider`, e mover trocaria um defeito silencioso por outro: a posição no stack global
> é requisito (depois do `TrustProxies`), e o `append()` do `bootstrap/app.php` a garante.
>
> O que foi feito é **rede de segurança**: `garantirRaizDeUrlSemPublic()` chama `pushMiddleware()`,
> que é idempotente (`Kernel.php:364`). Instalação nova — o método é no-op e a posição original
> fica intacta. Instalação atualizada sem o registro — o middleware entra no fim do stack, que
> continua sendo depois do `TrustProxies`, o único requisito de ordem que a classe declara.
>
> Coberto por `tests/Kit/RaizDeUrlRegistradaTest.php`, 4 casos. O quarto nasceu de um **mutante
> sobrevivente**: apagar a chamada de dentro do `boot()` não reprovava, porque o caso que provava a
> correção invocava o método privado por reflexão.

<details>
<summary>O texto original do item, para quem quiser o histórico</summary>

### O defeito, e ele está em produção agora

`bootstrap/app.php` registra o middleware `RaizDeUrlSemPublic` do kit no stack global, e
`bootstrap/providers.php` registra o `KitServiceProvider`. **Nenhum dos dois é entregue pelo
`kit:update`** — `bootstrap/` não está em `KitUpdate::CAMINHOS_DO_KIT`, e não pode estar: é onde
quem instala registra os **próprios** middlewares e providers, e sobrescrever apagaria isso.

Consequência medida: `RaizDeUrlSemPublic` entrou na **v0.36.1**. Quem instalou entre a v0.16.0 e a
v0.36.0 e vem rodando `kit:update`:

- **tem** a classe do middleware — `app/Http/Middleware` está coberto;
- **não tem** o registro — `bootstrap/app.php` não viaja.

A correção de URL **não funciona** para essas instalações, e o sintoma é silencioso: nada quebra,
o `/public` simplesmente continua aparecendo antes do painel.

### A saída

O kit registra o próprio middleware a partir do `KitServiceProvider`, que **viaja pelas duas
rotas** de entrega. O `bootstrap/app.php` de quem instala deixa de ser o lugar onde o kit escreve.

```php
// em KitServiceProvider::boot(), em vez de bootstrap/app.php
$this->app->make(\Illuminate\Contracts\Http\Kernel::class)
    ->pushMiddleware(RaizDeUrlSemPublic::class);
```

**A posição importa** e é requisito, não estilo: o comentário em `bootstrap/app.php` registra que
ele precisa rodar **depois** de outro middleware do stack global. Mover sem preservar a ordem
troca um defeito silencioso por outro.

### Por que não entrou junto dos cinco urgentes

Porque não é de uma linha, e porque mexer na ordem do stack global de middleware é mudança de
comportamento que merece wiki própria — com CT que prove a ordem, e com os quatro cenários de
validação rodados contra uma instalação **antiga** atualizada, que é onde o defeito vive.

O `bootstrap/` está declarado em `FORA_DA_ENTREGA_POR_DECISAO` com este texto inteiro, então a
guarda não deixa a decisão virar esquecimento de novo.

### O mesmo mecanismo já falhou três vezes

| Ocorrência | O que não chegava a quem atualiza | Descoberto em |
|---|---|---|
| 1 | `resources/views/svg` | v0.23.0, por erro em produção (`View not found`) |
| 2 | `tests/Browser`, `tests/BrowserTenancy` | validação dos quatro cenários da v0.39.0 |
| 3 | `lang/pt_BR.json` — /infra em inglês | a guarda nova, em 2026-09-24 |
| **4** | **`bootstrap/` — middleware sem registro** | **a mesma guarda, aberta aqui** |

As três primeiras estão corrigidas. Esta é a única em que a correção exige decisão de desenho.


</details>

---

## 8. Os níveis de qualidade que já foram medidos e ficaram para depois

Levantados em 2026-09-24 ao atender o pedido *"analise e pesquise se temos mais níveis de
qualidade para implementarmos"*. Cada candidato foi **rodado contra esta árvore** antes de entrar
aqui — a tabela completa, com os que entraram e os que foram recusados, está em
`wikis/specs/feat/cobertura-de-testes/cobertura-de-testes/02-decisoes-arquiteturais.md` → ADR-05.

| # | Item | O que a medição mostrou | Por que não entrou agora |
|---|---|---|---|
| 8.1 | ~~**PHPStan level 8**~~ — **FEITO** em 2026-09-26 | **48 erros**, remedido e zerado pela causa | `wikis/specs/feat/phpstan-nivel-8/`. Level 9 (**474**) e `max` (**594**) continuam precipício, não degrau |
| 8.2 | **`composer-require-checker` + `composer-unused`** | não instalados | respondem mecanicamente a *"das 58 dependências, quais são de fato usadas?"* — pergunta que a crítica externa fez e que hoje só tem a resposta *"não sabemos"*. Melhor razão valor/esforço da lista |
| 8.3 | **`declare(strict_types=1)`** | **98 de 240** arquivos de `app/` | não é falta de rigor, é **inconsistência**: metade roda com coerção estrita e metade não, e nada no CI diz de que lado um arquivo novo nasce. Exige decidir o lado, não rodar um `sed` |
| 8.4 | **Branch coverage** (Xdebug) | Xdebug 3.5.3 já instalado localmente | é a única razão de o Xdebug existir nesta máquina. Mais lento que o PCOV; depende de o job de cobertura já estar estabilizado |
| 8.5 | **`pest-plugin-type-coverage`** | não instalado | dependência nova; mede outra coisa que o PHPStan level 8 já cobre em parte |
| 8.6 | **Meta de cobertura por diretório** | `app/Policies` a **23 %**, `app/Console` a **27 %** | hoje reprovaria `app/Console`, que é o código **mais** exercitado do kit e aparece baixo por ser medido de outro processo. Só faz sentido depois que essa distorção tiver saída |

### O que **entrou** junto com o levantamento

Os dois presets de arquitetura do Pest que já passavam — `php` e `security`, em
`tests/Kit/ArquiteturaDoCodigoTest.php`. O plugin `pest-plugin-arch` estava no `composer.json`
desde sempre com **zero** uso.

### Por que não entrou o resto agora

Decisão registrada: a entrega da cobertura já mexe em CI, README, docs e `composer.json`. Empilhar
48 correções de PHPStan em cima disso tornaria o diff irrevisável. Cada item acima tem número
medido, então a estimativa não precisa ser refeita do zero — mas **precisa ser remedida**: `48` é
de 2026-09-24 e muda a cada release.

---

## 9. Excluir pela tela um papel que tem convite pendente responde 500

> Medido em 2026-09-26 pela `feature-test-design` da wiki `phpstan-nivel-8` (premissa P-07).

A FK `convites.role_id` **sem cascade** (`database/migrations/2026_08_13_000002_create_convites_table.php`)
é deliberada: apagar o papel de um convite pendente tem de doer na hora, em vez de deixar o convite
aceitar com papel nulo. A recusa está certa. O que está errado é **como** ela chega: a ação de
excluir do `RoleResource` não trata a violação, e o administrador vê o 500 do `QueryException`.

**Fica para depois porque** não é nulidade, não é o que a wiki `phpstan-nivel-8` pediu, e a saída
certa é decisão de produto: recusar com aviso nomeando os convites pendentes, ou oferecer
cancelá-los junto. O invariante já está travado por `[CT-12]` da wiki `phpstan-nivel-8`; falta a UX.

---

## Como este documento é mantido

Item entra aqui quando uma decisão **registrada** o empurrou para depois — com o motivo e, quando
houver, o número que sustentou a decisão. Item sai daqui quando é implementado (e vira linha do
`CHANGELOG.md`) ou quando é recusado em definitivo (e vira linha de
[`pacotes-candidatos.md`](pacotes-candidatos.md), se for pacote).

Item sem motivo registrado não é roadmap, é lista de desejos — e lista de desejos envelhece sem
que ninguém perceba.
