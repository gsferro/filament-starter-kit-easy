# Decisões Arquiteturais — Rodapé coerente

## ADR-01: "Coerente" é a mesma linha nos dois, com visibilidades diferentes

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-06, RQ-07

### Contexto

O pedido diz *"precisamos que seja coerente ambos os cenarios"*. A leitura óbvia — **mesmo
conteúdo nos dois** — colide com uma decisão anterior e registrada: o rodapé dos painéis esconde a
versão de quem não autenticou, porque mostrá-la *"é entregar o mapa de CVEs aplicáveis a quem ainda
não autenticou"* (`resources/views/filament/versao-do-kit.blade.php`).

Uma superfície é pública, a outra não. Sem resolver isso, "coerência" desfaria a decisão de
segurança sem que ninguém a tivesse revisto.

### Decisão

**Coerência é de composição, não de conteúdo.** Um ponto único monta a linha; a **audiência**
decide o que entra nela:

| | Visitante | Autenticado |
|---|---|---|
| `© {Nome}` | ✅ | ✅ |
| `v{versão}` | ❌ | ✅ |
| `kit {versão}` | ❌ | ✅, se o toggle estiver ligado |

Na tela de login, o texto livre do admin vira uma linha **adicional, abaixo** da automática.

### Alternativas Consideradas

1. **O login herda quando o textarea está vazio** — descartada. Coerente **só enquanto ninguém
   preenche**: o admin que digitasse qualquer coisa perderia o `© Nome` sem perceber, e os dois
   voltariam a divergir em silêncio. Foi levada ao usuário e recusada
2. **Só acrescentar `©` + nome ao painel** — descartada: atende RQ-01 e RQ-02 e **deixa RQ-06 sem
   resposta**. Foi levada ao usuário e recusada
3. **Mostrar a versão também no login** — descartada **pelo usuário**, explicitamente. Reverteria
   a decisão de segurança
4. **Toggle `exibir_versao_publica`** — oferecida e recusada: mais um campo na tela e mais um
   caminho a testar, para uma escolha que a decisão de segurança já resolve

### Consequências

- **Positivas**: a regra vive em um lugar; a decisão de segurança sobrevive intacta
- **Negativas**: os dois rodapés não são idênticos, e alguém pode ler isso como incoerência. Está
  escrito aqui e na blade **por que** não são
- **Riscos**: a guarda deixa de envolver o bloco e passa a envolver a parte — é mais fácil errar.
  Mitigação: caso dedicado sobre a tela de login **anônima**

---

## ADR-02: A composição não é dona da visibilidade

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-05

### Contexto

`AssinaturaDoRodape` poderia receber zero argumentos e decidir sozinha se inclui a versão, chamando
`filament()->auth()->check()` internamente. É o desenho mais curto.

### Decisão

**`partes(bool $comVersao): array`.** A classe compõe; **a blade decide a audiência.**

### Por quê

Três razões, e a terceira é a que pesa:

1. **Testabilidade** — a classe fica testável sem painel montado, sem request e sem sessão. O
   argumento é o eixo do caso de teste
2. **Uma responsabilidade** — compor uma linha e decidir quem pode ver são coisas diferentes
3. **A regra de segurança fica onde alguém vai procurá-la.** Enterrada num `App\Support\`, a
   frase que explica por que a versão não aparece para visitante ficaria a dois saltos da tela.
   Na blade, ela está **no ponto em que a audiência é conhecida** — e é lá que o próximo leitor
   vai perguntar *"quem vê isto?"*

### Alternativas Consideradas

1. **A classe decide sozinha** — descartada pelos três motivos acima
2. **Dois métodos, `paraVisitante()` e `paraAutenticado()`** — descartada: duplica a composição e
   convida as duas a divergirem, que é o defeito que esta wiki inteira existe para fechar

### Consequências

- **Positivas**: o argumento booleano é o eixo de partição do caso de teste, e cobre os dois ramos
  sem montar painel
- **Negativas**: quem chamar a classe precisa saber o que passar. Mitigação: o único chamador é a
  blade, e o parâmetro é nomeado (`comVersao:`)

---

## ADR-03: A blade é renomeada, e o nome antigo já era impreciso

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-05

### Contexto

`resources/views/filament/versao-do-kit.blade.php` passa a renderizar `© Nome` além das versões.

O nome **já era impreciso antes desta entrega**: a blade mostra `config('app.version')`, a versão
do **sistema**, e a do kit só aparece sob toggle. O próprio docblock dela registra que confundir as
duas foi um defeito real, corrigido por um adendo da wiki ancestral.

### Decisão

`git mv` para **`assinatura-do-rodape.blade.php`**, com a referência atualizada em
`ConfiguraFilamentGlobal:configuraVersaoNoRodape:129`.

### Alternativas Consideradas

1. **Manter o nome e corrigir só o docblock** — descartada. Mais barata, e deixa um nome
   ativamente errado num kit que é distribuído como material de referência. Nome errado é a
   mesma classe de defeito que o `[CT-10]` desta base já produziu: o rótulo dizia uma coisa e o
   alcance era outro
2. **Renomear também `tests/Kit/VersaoNoRodapeTest.php`** — descartada **por ora**: o arquivo
   passa a cobrir a assinatura inteira, mas renomeá-lo desloca IDs de CT de outra wiki sem
   benefício proporcional. Registrado como dívida

### Consequências

- **Positivas**: o nome passa a dizer o que o arquivo faz
- **Negativas**: churn em referências. São poucas e o `grep` as encontra todas
- **Riscos**: referência esquecida quebra o render **em silêncio** — o hook simplesmente não
  encontra a view e estoura. Mitigação: a suíte tem caso que renderiza o rodapé

---

## ADR-04: O `©` leva o **ano corrente**, calculado no render

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-02

> **Esta ADR foi revertida antes de virar código.** A primeira versão decidia *"sem ano"*, com o
> argumento de que o usuário pediu só o `©` e de que acrescentar o ano seria regra tomada no lugar
> dele. O argumento continua válido — e por isso a pergunta foi **levada a ele**, com as três
> formas e o custo de cada uma. Ele escolheu o ano corrente.

### Contexto

`©` sem ano é incomum. As três formas possíveis têm consequências diferentes, e nenhuma é óbvia
o bastante para ser assumida: ano fixo envelhece, ano corrente muda sozinho, intervalo exige saber
o ano inicial e vira mais um campo.

### Decisão

**`© {ano corrente} {Nome}`**, com o ano calculado **no render**, sem campo novo e sem persistência.

### Alternativas Consideradas

1. **Sem ano** — era a decisão anterior. **Recusada pelo usuário** em 2026-09-22
2. **Campo novo nas configurações** (ano ou intervalo `2024–2026`) — oferecida e recusada: mais um
   campo, mais uma settings e mais um caminho a testar por um detalhe de convenção
3. **Ano da instalação, gravado uma vez** — não chegou a ser oferecida, e vale registrar por que
   não: exigiria migration e um valor que ninguém sabe conferir depois

### Consequências

- **Positivas**: a convenção fica completa, sem campo novo e sem manutenção
- **Negativas, e é a que importa**: **o texto muda sozinho na virada do ano**, sem ninguém
  decidir. Ano de copyright deveria refletir a data da obra, e este reflete a data em que a
  página foi aberta. Aceito explicitamente pelo usuário, com a alternativa à vista
- **Consequência dura no teste**: **nenhum caso pode afirmar o ano literal sem congelar o tempo.**
  Um `assertStringContainsString('© 2026 …')` fica verde hoje e vermelho em 1º de janeiro — a
  suíte quebraria sozinha, de madrugada, sem commit. Os casos usam `travelTo()` e **pelo menos um
  deles atravessa a virada**, provando que o ano acompanha em vez de estar cravado

---

## ADR-05: A assinatura sai escapada; o recado sai por Markdown

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-06

### Contexto

Depois desta entrega, **duas vias de render convivem no mesmo bloco visual** da tela de login:

| Bloco | Origem | Via |
|---|---|---|
| assinatura | `config('app.name')`, de campo editável | escapada |
| recado | `login_rodape`, de campo editável | `Str::markdown(…, html_input: strip)` dentro de HTML cru |

A tela de login é **pública e não autenticada** — é a tela por onde todo mundo entra.

### Decisão

**A assinatura nunca passa pelo caminho do recado.** Ela sai escapada, sempre.

### Por quê

O recado tem um motivo próprio para aceitar Markdown, documentado na blade dele: o solicitante
precisava de negrito e link, e o `html_input: strip` descarta tag e `javascript:`. Esse motivo **não
se estende** ao nome da aplicação, que é texto puro e não tem por que formatar.

Unificar as duas vias por simetria estética abriria a superfície do nome — e o nome passa a
renderizar em tela pública nesta mesma entrega. A simetria que importa é a de **composição**
(ADR-01), não a de mecanismo de render.

### Alternativas Consideradas

1. **Passar a assinatura pelo mesmo `Str::markdown`** — descartada pelo motivo acima. O
   `html_input: strip` mitiga, mas o padrão certo para texto que não precisa formatar é não abrir
   a porta
2. **Levar o recado para saída escapada** — descartada: removeria a formatação que o solicitante
   pediu e que a wiki ancestral registrou

### Consequências

- **Positivas**: a superfície nova não herda a via permissiva
- **Negativas**: dois mecanismos lado a lado, e alguém pode "uniformizar" sem ler. Mitigação: o
  motivo está nas duas blades, e o caso de teste afirma sobre a assinatura escapada

---

## ADR-06: O prefixo `v` é de exibição, e não migra dado

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-03, RQ-04

### Contexto

O usuário pediu *"um preffix do `v` que é adicionado ao numero"*, citando a doc do afixo. A blade
**já** antepõe `v` ao compor o rodapé.

### Decisão

`->prefix('v')` no campo. O valor **gravado continua sem `v`**; quem compõe o `v` no rodapé
continua sendo a blade.

### Por quê

Verificado na fonte instalada (`vendor/filament/forms/src/Components/Concerns/HasAffixes.php:prefix:56`): o método recebe um rótulo e **não muta
o estado**. É exibição.

Isso é o que mantém os dois coerentes: **um só lugar acrescenta o `v`**. Se o prefixo gravasse, o
rodapé mostraria `vv1.2.3`, e a correção seria tirar o `v` da blade — trocando um ponto único por
dois.

### O efeito colateral, declarado

Quem **já digitou** `v1.2.3` passa a ver `vv1.2.3` **no campo**. O prefixo **torna o dado sujo
visível**; não o limpa.

**Sem migração**, e a decisão é deliberada: seria migração de dado de settings para um caso que a
tela agora evidencia e que o admin corrige em dois segundos. O risco de uma migração que remove
`v` do início de uma string é maior que o incômodo — uma versão legítima `v2` viraria `2`.

### Alternativas Consideradas

1. **Gravar com o `v`** — descartada: duplica o prefixo no rodapé ou obriga a tirá-lo da blade
2. **`mutateDehydratedStateUsing()` para limpar `v` ao salvar** — descartada: resolve o dado novo
   e não o antigo, e acrescenta transformação escondida num campo que o usuário digitou
3. **Migração dos valores já gravados** — descartada pelo risco acima; registrada como dívida

### Consequências

- **Positivas**: um só ponto acrescenta o `v`; zero risco a dado existente
- **Negativas**: base que já tem `v` gravado exibe `vv` até alguém corrigir. É visível, que é o
  oposto de silencioso

---

## ADR-07: O rodapé cabe na dobra por CSS do kit, e não mexendo no hook

**Status**: Aceita · **Data**: 2026-09-23 · **Atende**: RQ-06 · **Origem**: Blocker RD-01 do step 6.5

### Contexto

O layout do `filament-auth-designer` — usado por **8 das 10** páginas de `app/Filament/Pages/Auth/`
— fixa `.fi-auth-layout` em `min-height: 100vh` e emite o hook `FOOTER` **depois** de fechar a
própria div. O contêiner sozinho consome a dobra inteira.

Medido, viewport de 1117px: assinatura em `y=1122`, recado em `y=1186`. Os dois invisíveis sem
rolar — e o recado **regredindo**, porque antes vivia dentro do cartão do formulário.

### Decisão

Uma regra de CSS do kit:

```css
body.fi-body:has(> .fi-auth-layout)                     { display: flex; flex-direction: column; }
body.fi-body:has(> .fi-auth-layout) > .fi-auth-layout   { min-height: 0; flex: 1 1 auto; }
```

Os três elementos são **irmãos diretos** de `body.fi-body`, que já tem `100vh` — medido. Basta o
body ser a coluna e o layout parar de exigir a altura toda.

### Alternativas Consideradas

1. **Mover o rodapé para um hook interno** (`AuthDesignerRenderHook::CardAfter`) — descartada: põe
   o rodapé **dentro do cartão**, o que ele não é, e acopla o kit a um hook proprietário do pacote
2. **Voltar o recado para `AUTH_LOGIN_FORM_AFTER`** — descartada: quebra a ordem que o usuário
   escolheu (assinatura acima), porque aquele hook sai **dentro** do cartão e o `FOOTER` fora
3. **`min-height: calc(100vh - {altura})`** — descartada: número mágico que envelhece com o
   conteúdo do rodapé

### Consequências

- **Positivas**: o hook fica intacto; a regra é de três linhas e escopada
- **Negativas**: o kit passa a sobrescrever o dimensionamento de um layout de vendor. As variantes
  `no-media` e `media-left` foram medidas; `media-top`, `media-bottom`, `media-right` e `cover`,
  **não** — registrado como suspeita não confirmada
- **Degradação declarada**: navegador sem `:has()` descarta a regra inteira e volta ao
  comportamento anterior — **para o estado de hoje, não para um pior**
- **Riscos**: a regra depende de dois fatos do vendor (`min-height: 100vh` e a posição do hook), e
  `composer update` move âncoras em silêncio. Mitigação: `[CT-B01]`, provado por mutação

---

## ADR-08: As skills do Blueprint não são versionadas

**Status**: Aceita · **Data**: 2026-09-22 · **Atende**: RQ-08 · **Origem**: QA-19

### Contexto

`composer bp:on` instala `filament/blueprint`, que traz três skills de agente
(`planning-filament`, `reviewing-filament-plans`, `filament-security-audit`). Para o Claude Code as
enxergar, elas precisam ser copiadas para `.claude/skills/` — diretório que **é versionado** neste
repositório, com 18 skills de pacotes abertos.

### Decisão

Exclusão **cirúrgica** no `.gitignore`: as três do Blueprint, e só elas.

### Por quê

`filament/blueprint` vem de `packages.filamentphp.com`, um repositório **pago**, e este kit é
distribuído publicamente por `composer create-project`. Versionar as skills seria **redistribuir
material licenciado** a quem não tem licença.

### Alternativas Consideradas

1. **Ignorar `.claude/skills/` inteiro** — descartada: tiraria do versionamento as 18 skills
   abertas que o kit entrega de propósito
2. **Não copiar as skills** — descartada: sem a cópia o `subagent_type` falha, e a RQ-08 pede o
   Blueprint

### Consequências

- **Positivas**: nenhum material licenciado no repositório público
- **Negativas**: quem tiver licença refaz a cópia. O comando está escrito no próprio `.gitignore`

