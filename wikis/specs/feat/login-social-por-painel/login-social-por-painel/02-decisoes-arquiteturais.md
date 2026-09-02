# Decisões Arquiteturais — Login social por painel

## ADR-01: A entrega inclui o destino, não só a permissão

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A segunda linha do requisito pede a permissão: *"é apenas se tiver ativo, ver se o painel em si
também podera usar esse tipo de login"*. O exemplo da primeira linha pede o efeito: *"liberar o
login do empresarial do google para acessar o admin"*.

E o levantamento achou o que torna as duas leituras incompatíveis: `Filament::getPanel('app')` está
escrito em **seis** pontos do `LoginSocialController`, incluindo `urlDoPainel()` (`:645-648`), que é
o destino de quem entra com sucesso. O login social do kit termina sempre no `/app`.

### Decisão

Implementar as duas metades: a permissão **e** o destino. Quem entra pelo botão na tela do `/admin`
termina no `/admin`.

### Alternativas Consideradas

1. **Só a permissão** — descartada pelo mantenedor, e com razão técnica: entregaria uma
   configuração que não produz o efeito que a justifica. O administrador marcaria "Google só no
   `/admin`", o botão apareceria lá, e quem clicasse cairia no `/app` — provavelmente sem papel
   daquele painel, batendo em `canAccessPanel()`. A tela diria uma coisa e o sistema faria outra.
2. **Só o destino** (o painel de origem vira o destino, sem escolha de painéis) — descartada:
   é metade do requisito, e a metade que ele **não** enfatiza.

### Consequências

- **Positivas**: o caso de uso do requisito funciona de ponta a ponta.
- **Negativas**: a entrega cresce de "uma condição em `disponivel()`" para "o fluxo passa a
  conhecer o painel de origem" — seis pontos de redirecionamento parametrizados, sessão estendida,
  e uma barreira nova contra entrada do usuário (ADR-03).
- **Riscos**: mudança de comportamento em update — quem hoje entra no `/app` por um botão na tela
  do `/admin` passa a chegar no `/admin`. Vai no `CHANGELOG` como *Alterado*, não como *Adicionado*.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:434,466,590,645-648,666,694`
- `00-requisito.md` → **A1**, respondida pelo mantenedor em 2026-09-02

---

## ADR-02: A lista de painéis sai de `Paineis::opcoes()`, nunca de uma constante

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A feature precisa de uma lista de painéis em três lugares: as opções do campo na tela, a lista
branca da validação da query, e a resolução do painel de destino.

### Decisão

Usar `App\Support\Paineis::opcoes()` (`app/Support/Paineis.php:62-70`) nos três. Ela devolve
`['admin' => '/admin', 'app' => '/app', 'infra' => '/infra']` derivado de `Filament::getPanels()`.

### Alternativas Consideradas

1. **Uma constante `PAINEIS = ['admin', 'app', 'infra']`** na feature — descartada: seria a segunda
   fonte da verdade sobre quais painéis o kit tem, e divergiria no dia em que alguém acrescentar um
   painel. É o defeito que `.ai/rules/config.md` chama de *"uma pergunta, uma dona"*, e que o kit já
   pagou uma vez no login social (`kit.registro.aberto` × `kit.registro.habilitado`).
2. **Um enum `PainelDoKit`** — descartada por YAGNI e por acoplamento invertido: o kit já deixa os
   painéis serem descobertos, e um enum exigiria editá-lo a cada painel novo. Vale a mesma régua do
   docblock de `ProvedorSocial`: *"enum de um caso é abstração sem segundo caso"* — aqui seria enum
   que duplica o registro do Filament.
3. **`Filament::getPanels()` direto** nos três pontos — descartada: `Paineis::opcoes()` já é esse
   wrapper, com o rótulo pronto para `Select::options()`.

### Consequências

- **Positivas**: painel novo no kit entra na escolha, na validação e no destino sozinho.
- **Negativas**: `Paineis::opcoes()` era uma classe de apoio à matriz de permissões e passa a ter um
  segundo consumidor com outro propósito. É reuso legítimo — a pergunta é a mesma ("quais painéis
  existem?").

### Referências

- `app/Support/Paineis.php:62-70`
- `.ai/rules/config.md` — "uma pergunta, uma dona"

---

## ADR-03: O painel vem da query, e a barreira é no servidor

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O botão precisa dizer ao `redirecionar()` de qual painel a pessoa veio. O callback do OAuth volta
numa URL **fixa** por provedor (`config/services.php:68,74,80,86`), fora do painel, então não há
painel na URL de volta — a informação tem de sair da ida.

O kit já tem o padrão: o blade monta o link com `route('auth.social.redirect', [...])` carregando
`org` e `token` da query corrente (`botoes-sociais.blade.php:52`), e o `redirecionar()` os guarda na
sessão (`:85`).

### Decisão

O painel viaja pelo **mesmo caminho**: query string na ida (`?painel=admin`), guardado no
`login_social.contexto` da sessão, lido no callback.

E porque query string é **entrada do usuário**, o `redirecionar()` valida duas coisas antes de
seguir, em ordem — e **as duas falhas não têm o mesmo destino**:

| Falha | O que acontece | Por quê |
|---|---|---|
| O valor **não é um painel que existe** (`?painel=marketing`) | tratado como **ausente**: o fluxo segue no painel default | é indistinguível de um link antigo sem `painel`, de um painel renomeado e de um erro de digitação. Nenhum dos três é ataque, e nenhum deve virar 404 |
| O valor é um painel real, mas o provedor **não está autorizado** nele | **404**, com `warning` no canal `autenticacao` | é a barreira. É o caso que a feature existe para fechar, e o único em que alguém pediu algo que a configuração nega |

> **Correção de uma contradição desta wiki, achada pela derivação dos casos de teste
> (pergunta A6).** A primeira versão desta ADR dizia *"as duas falhas respondem 404"*, e o código
> do passo 5b do PRD fazia o oposto para a primeira: `painelDaRequisicao()` devolve `null` para
> painel inexistente, e o `abort` é condicionado a `$painel !== null`. O código estava certo e a
> prosa errada. Vale registrar **por que** o código estava certo: 404 em painel inexistente
> transformaria um link antigo — legítimo, gerado antes desta feature, sem `painel` na query — na
> mesma resposta de uma tentativa negada, e quebraria a compatibilidade que a ADR-04 protege.

### Alternativas Consideradas

1. **Confiar no `Referer`** — descartada: cabeçalho opcional, forjável, e ausente em navegador com
   política restritiva. Seria uma barreira que às vezes não existe.
2. **`Filament::getCurrentPanel()` dentro do `redirecionar()`** — descartada porque **não
   funciona**: a rota é global (`routes/web.php:61-70`), fora de qualquer painel, então o middleware
   `SetUpPanel` não rodou e o painel corrente não está resolvido. `getCurrentOrDefaultPanel()` cairia
   no **default** — e devolver sempre o mesmo painel é exatamente o defeito que a feature corrige.
3. **Uma rota por painel** (`/admin/auth/{provedor}/redirect`) — descartada: multiplicaria as três
   rotas por três painéis, e o `redirect` do OAuth registrado no provedor é **um** por aplicação.
   Mudaria o contrato com o Google, o GitHub, o LinkedIn e o X.
4. **Assinar a query** (`URL::signedRoute`) — descartada: resolve forja, mas a validação de
   autorização no servidor é necessária de qualquer forma (o link assinado poderia ter sido gerado
   quando o painel *era* autorizado), então a assinatura seria uma camada a mais sem barreira nova.

### Consequências

- **Positivas**: reusa o mecanismo de sessão que já existe; nenhuma rota nova; nenhuma mudança no
  OAuth registrado nos provedores.
- **Negativas**: a barreira **tem** de estar no `redirecionar()`. Um `->visible()` no botão não
  basta, e é exatamente o que `.ai/rules/filament.md:19-29` avisa: *"A query é filtro de UI; a
  barreira é uma asserção no método"*. O caso de teste que forja `?painel=` é o que prova isso.
- **Riscos**: sessão perdida entre a ida e a volta (cookie, expiração, aba nova) deixa o callback
  sem painel. Tratado na ADR-06.

### Referências

- `routes/web.php:61-70`; `config/services.php:68,74,80,86`
- `resources/views/filament/auth/botoes-sociais.blade.php:52`
- `.ai/rules/filament.md:19-29`

---

## ADR-04: Lista vazia significa TODOS os painéis

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A propriedade nova é uma lista. Uma lista vazia pode significar duas coisas opostas: "nenhum
painel" (o provedor não vale em lugar nenhum) ou "todos" (sem restrição declarada).

E há um estado em que o vazio é inevitável: a instalação que atualiza o kit e recebe a propriedade
recém-semeada, sem ninguém ter escolhido nada.

### Decisão

**Vazio = todos.** Em `painelAutorizado()`: `$paineis === [] || in_array($painel, $paineis, true)`.

### Alternativas Consideradas

1. **Vazio = nenhum** — descartada, e é a alternativa perigosa: num update, toda instalação que usa
   login social hoje veria a propriedade nascer vazia e o login social **desligar sozinho** nos três
   painéis. Um interruptor de acesso que se fecha em silêncio num update é o oposto do que o kit
   pratica — e note que a régua de *falhar fechado* de `.ai/rules/config.md` vale para chave que
   **abre** superfície pública, não para uma que já estava aberta por decisão de quem instalou.
2. **Semear com os três painéis explícitos** — descartada como forma primária, mas **é o que a
   migration faz** de fato (semeia de `config`, que a `.env` vazia traduz para `[]`, e a leitura o
   trata como todos). O ponto é que o **código** não pode depender de a semeadura ter acontecido:
   `migrate` sem a migration nova, `.env` com a chave apagada e settings sem linha precisam todos
   cair no comportamento anterior.
3. **Um valor sentinela** (`['*']`) — descartada: um valor mágico a mais para documentar, quando o
   vazio já é inequívoco por decisão.

### Consequências

- **Positivas**: a feature nasce inerte; update não muda comportamento; apagar o valor no `.env`
  não tranca ninguém fora.
- **Negativas**: "não quero este provedor em painel nenhum" não se expressa pela lista — expressa-se
  desligando o provedor, que é o interruptor que já existe para isso. É a decisão certa, mas precisa
  estar escrita na tela: o campo leva `helperText('Vazio = todos os painéis.')`.

### Referências

- `00-requisito.md` → **A2**, respondida pelo mantenedor
- `.ai/rules/config.md` — a régua de falhar fechado e seu escopo

---

## ADR-05: O callback não reconfere a autorização do painel

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

`disponivel()` é chamada três vezes no controller: no `redirecionar()` (`:80`), no `retorno()`
(`:108`) e no `confirmarVinculo()` (`:477`). A condição nova é por painel. Reconferi-la no callback
é possível — o painel está na sessão.

### Decisão

**Não reconferir por painel no callback.** As chamadas de `:108` e `:477` seguem sem painel; a
autorização é decidida na **ida**.

### Alternativas Consideradas

1. **Reconferir no callback** — descartada por UX, com o mesmo nível de segurança: a autorização
   já foi verificada na ida, e o único cenário que a reconferência pega é a configuração ter mudado
   **entre** o clique e a volta — segundos. Nesse caso, a pessoa já autenticou no provedor e
   receberia um 404 seco, sem entender por quê. E não há ganho de barreira: quem forjou a query foi
   barrado na ida.
2. **Reconferir e redirecionar com mensagem** — descartada por escopo: seria UI nova para uma
   janela de segundos, e o requisito não a pede.

### Consequências

- **Positivas**: nenhuma mudança nos dois caminhos de callback, que são os mais delicados do
  controller.
- **Negativas**: uma configuração alterada no meio do fluxo permite **uma** entrada pelo painel que
  acabou de ser desautorizado. Aceito e declarado.
- **Riscos**: se algum dia a lista de painéis passar a ser usada como barreira de segurança forte
  (e não como escolha de conveniência), esta ADR precisa ser revista.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:80,108,477`

---

## ADR-06: Sem painel na sessão, o destino é o painel default

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O painel viaja pela sessão (ADR-03), e sessão se perde: cookie bloqueado, expiração entre a ida e a
volta, link do callback aberto em outro navegador. O callback precisa de um destino de qualquer
forma.

### Decisão

`painel(?string $id)` devolve `Filament::getPanel($id)` quando o id está na lista branca, e
`Filament::getDefaultPanel()` quando não. Nunca lança.

### Alternativas Consideradas

1. **`getPanel('app')` como fallback** — descartada: reintroduz a constante que a ADR-02 elimina.
   O `app` **é** o painel default do kit hoje, então o comportamento é idêntico — mas por
   configuração, não por string escrita no controller.
2. **Abortar com 404** — descartada: sessão perdida não é ataque, é navegador. Mandar a pessoa para
   o painel default é o comportamento anterior a esta wiki, e é o menos surpreendente.
3. **Revalidar e abortar se o painel da sessão não existir mais** — parcialmente adotada: o
   `array_key_exists` **está** lá, mas cai no default em vez de abortar, pelo motivo acima.

> **Correção de fato desta ADR** (achada pela derivação dos casos de teste, que abriu o vendor
> conforme `.ai/rules/specs.md`). A primeira versão justificava o `array_key_exists` dizendo que
> *"`getPanel()` com id inexistente lança"*. **Não lança nesta versão**:
> `PanelRegistry::get()` devolve `null` no modo estrito
> (`vendor/filament/filament/src/PanelRegistry.php:36-44`, `return $this->panels[$id] ?? null`).
>
> A decisão continua a mesma, e a guarda continua necessária — só o mecanismo da falha muda: sem
> ela, `$this->painel($id)` devolveria `null` e o `->getUrl()` seguinte estouraria
> `Error: Call to a member function getUrl() on null`. O observável é 500 em vez de exceção
> nomeada, e é isso que o cenário de sessão corrompida afirma. **A justificativa estava errada e a
> conclusão certa** — exatamente o padrão que a rule do projeto existe para pegar.

### Consequências

- **Positivas**: o fluxo nunca quebra por sessão; o comportamento sem painel é o de hoje.
- **Negativas**: uma sessão corrompida leva a pessoa ao painel default em silêncio, sem aviso. É o
  que já acontece hoje, para todo mundo.

### Referências

- `app/Http/Controllers/Auth/LoginSocialController.php:645-648`

---

## ADR-07: A propriedade é do settings do kit, e não é lida no boot do painel

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O kit tem duas fontes de configuração, com precedência declarada: **o banco vence em tempo de
execução; o `.env` semeia e é o plano B** (`app/Settings/ConfiguracoesDoKit.php:22-24`).

E tem uma armadilha medida: `registro_verificar_email` era lida no **boot do painel**, e por isso o
toggle na tela **gravava e não fazia nada** — o painel já havia fixado a decisão no array da rota
(`ConfiguracoesDoKit.php:318-330`).

### Decisão

A propriedade vive no settings do kit, com os três lugares do contrato da classe (propriedade, linha
no mapa, migration nova). **E ela é lida por request**, em dois pontos: o render hook do blade e o
`redirecionar()` do controller.

### Alternativas Consideradas

1. **Só no `.env`/`config`** — descartada: o requisito diz *"ao habilitar o login social, ter as
   opções"*, e habilitar login social é ato de tela (`/admin/configuracoes-do-kit`). Uma escolha que
   só existe em arquivo seria a única do bloco de login social fora da tela.
2. **Registrar o provedor condicionalmente no boot do painel** — descartada, e é a alternativa que
   repetiria o defeito do `registro_verificar_email`: o botão vem de render hook global, e um
   registro condicional por painel no boot congelaria a decisão. A leitura por request é o que faz a
   tela governar de verdade (`.ai/rules/settings.md`).

### Consequências

- **Positivas**: a tela governa; o `.env` semeia; nenhum consumidor sabe que o settings existe (o
  mapa é a ligação).
- **Negativas**: mais uma propriedade por provedor — quatro propriedades novas, quatro linhas no
  mapa, uma migration. O contrato da classe cobra os três lugares, e esquecer a migration estoura
  `MissingSettings` no boot de **todo** request numa instalação de terceiro.
- **Riscos**: o caso de teste que assere que toda propriedade declarada é semeada
  (`tests/Kit/ConfiguracoesDoKitTest.php:305`) é a guarda desse esquecimento. Ele precisa continuar
  verde.

### Referências

- `app/Settings/ConfiguracoesDoKit.php:22-24`, `:60-68`, `:318-330`
- `.ai/rules/settings.md`
