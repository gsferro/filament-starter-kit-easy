# Decisões Arquiteturais — Convite em massa

## ADR-01: Resultado parcial em duas chaves, e o lote nunca aborta por um endereço

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Convidar trinta endereços de uma vez levanta uma pergunta antes de qualquer outra: o que acontece
quando o décimo segundo tem problema? Há duas respostas, e elas são features diferentes:

- **tudo-ou-nada**: valida o conjunto, e se algo estiver errado não manda nada. É o que uma
  transação em volta do laço produz, e é o que a validação de formulário produz de graça;
- **resultado parcial**: manda o que dá, relata o que não deu.

O caso real decide. Alguém cola uma coluna de planilha com quarenta endereços: um está com
`@gmial.com`, três já foram convidados na semana passada, dois já são membros. Em tudo-ou-nada
essa pessoa recebe "erro" até limpar a lista à mão, sem saber o que o sistema já sabe. Em
resultado parcial ela recebe trinta e quatro convites enviados e uma lista de seis linhas com o
motivo de cada.

O `inviteMany()` do `laravel-invite-only` **tenta** ser parcial e falha por dois detalhes, que o
Contexto do plano detalha: captura só `InvalidArgumentException` (então um duplicado não-pendente
derruba o lote inteiro) e o `BulkInvitationResult::count()` conta apenas os `successful`.

### Decisão

**1. Laço tolerante por endereço**, com `catch` de `Throwable` e não de uma exceção específica:

```php
try {
    $convite = static::create([...]);
    $convite->enviar();
    $enviados[] = $email;
} catch (Throwable $e) {
    Log::channel('autenticacao')->warning('[Convite@convidarEmMassa] …', ['exception' => $e, …]);
    $falhas[] = ['email' => $email, 'motivo' => 'erro_no_envio'];
}
```

Capturar `Throwable` normalmente é cheiro. Aqui é o **contrato**: uma falha de driver de e-mail,
de fila ou de banco é motivo para aquele endereço falhar, nunca para os outros trinta e nove não
serem convidados. E nada é engolido — o `warning` leva `'exception' => $e`, que o Laravel
serializa com stack trace.

**2. A forma é um array com duas chaves**, com array shape no PHPDoc (a convenção de PHP deste
projeto): `array{enviados: list<string>, falhas: list<array{email: string, motivo: string}>}`.
**Sem chave `total` e sem `count()`** — `recebidos` vai para o log, e `enviados` e `falhas` são
duas contagens explícitas que o chamador soma se quiser. Um total calculado seria a versão nova do
`BulkInvitationResult::count()`: um número que parece o total e não é.

**3. Sem transação em volta do lote.** Transação faria tudo-ou-nada, que é a decisão oposta.

**4. Consequência aceita**: se a exceção acontecer **dentro** de `enviar()` depois do
`forceFill(...)->save()` (`app/Models/Convite.php:128-132`) e antes do `Notification::route(...)`
(`:134`), sobra um convite com token válido e sem e-mail entregue. É o failure mode desejado: a
linha aparece como **Pendente** e o `Reenviar` por linha
(`app/Filament/Admin/Resources/Convites/Tables/ConvitesTable.php:66-73`) resolve. Apagar a linha
no `catch` destruiria a única pista de que aquele endereço foi tentado.

### Alternativas Consideradas

1. **Tudo-ou-nada com validação no formulário** (`->nestedRecursiveRules(['email'])`, que o
   `TagsInput` suporta). Descartada: é a negação da feature — um endereço torto reprovaria a modal
   inteira. Está registrado porque é o que alguém vai propor como "validar direito", e CT-02
   existe para ficar vermelho nesse dia.
2. **`catch` de exceção específica** (`QueryException`, `TransportException`). Descartada: é
   literalmente o defeito do `invite-only`. Toda lista de exceções esperadas está incompleta na
   primeira mudança de driver.
3. **DTO `ResultadoDoLote` com `total()`.** Descartada: uma classe para transportar dois arrays, e
   o `total()` é exatamente a superfície onde o `invite-only` errou. Vale no dia em que o resultado
   tiver **comportamento**, não só dados.
4. **Coletar as exceções e relançar no fim.** Descartada: relançar depois de enviar trinta e
   quatro convites transforma sucesso parcial em erro 500, e o operador perde o resumo.

### Consequências

- **Positivas**: o defeito que trava o lote do `invite-only` é impossível aqui; o retorno não tem
  API mentirosa; o operador termina com informação acionável.
- **Negativas**: `catch (Throwable)` exige justificativa a cada leitura — daí o comentário longo
  no código.
- **Riscos**: um bug de programação (`TypeError`, método inexistente) aparece como `erro_no_envio`
  em vez de estourar. Mitigação: o `warning` leva a exception inteira, e CT-10 prova que ela chega
  ao log.

### Referências

- `app/Models/Convite.php:124-152` (`enviar()`, o que o laço chama)
- `wikis/specs/main/convite-para-usuario-existente/02-decisoes-arquiteturais.md:381-442`
- CT-01, CT-02, CT-10
- Refinada por: ADR-03, ADR-04

---

## ADR-02: A tela é uma `Action` de header nos dois Resources, não uma `Page`

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

A tela precisa existir nos dois painéis. Duas formas: **A** — `Action` de header na listagem de
convites, com modal de formulário; **B** — `Page` própria nos dois painéis, com item de menu.

Pela escada do Ponytail A ganha por tamanho: zero rota, zero item de menu, dois arquivos a menos,
e o modal já é a confirmação. Mas o argumento decisivo é de **autorização**: uma `Page` nova no
painel `app` gera uma permission que entra na matriz do `panel_user` e que a lista de subtração
**não consegue alcançar**, porque ela filtra por `resourceFqcn`. O resultado é todo usuário comum
do negócio com acesso à tela de convidar em massa a organização dele — sem migration, sem 403, sem
log. O mecanismo inteiro, com os números medidos, está em ADR-06; foi ao ler isto que este ADR
escolheu a forma A, e foi por ter medido que o passo 7 existe.

Consertar pela forma B exigiria subtrair Pages no `PapeisSeeder` — um segundo mecanismo de
subtração, com um segundo lugar para esquecer.

### Decisão

Forma A: `Filament\Actions\Action` no `getHeaderActions()` das duas `ListConvites`
(`app/Filament/Admin/Resources/Convites/Pages/ListConvites.php:13-18` e
`app/Filament/App/Resources/Convites/Pages/ListConvites.php:16-21`).

A ação declara a própria autorização:

```php
->authorize('create', Convite::class)
```

`CanBeAuthorized::authorize()` guarda a checagem, e
`isAuthorizedOrNotHiddenWhenUnauthorized()`
(`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:224`) faz `CanBeHidden` devolver
`true` para escondido quando ela nega
(`vendor/filament/actions/src/Concerns/CanBeHidden.php:94`): o botão desaparece e a ação não é
montável. **Esta linha não é opcional** — `CreateAction::make()` consulta `canCreate()` sozinho
(`vendor/filament/filament/src/Resources/Resource/Concerns/HasAuthorization.php:144`), mas um
`Action::make()` cru não consulta nada e apareceria para quem só tem `ViewAny:Convite`.
Affordance sem permissão é bug (`wikis/convencoes.md:84`).

O corpo compartilhado vive num trait, `App\Filament\Concerns\ConvidaEmMassa`, no padrão de
`BadgeContagemNavegacao` (`app/Filament/Concerns/BadgeContagemNavegacao.php:20-38`). O trait
recebe o `Select` de papel de cada painel e um booleano de organização, porque o campo de papel é
**legitimamente diferente** nos dois (`/app` filtra `painel = 'app'` e trava por `Rule::exists`,
`app/Filament/App/Resources/Convites/ConviteResource.php:110, 121-122`) — e o resto (parser,
limite, chamada do lote, resumo) é o que **não pode** divergir. Duas cópias do resumo é como um
painel passa a esconder as falhas que o outro mostra.

### Alternativas Consideradas

1. **`Page` própria nos dois painéis**, com ou sem subtração de Pages no `PapeisSeeder`.
   Descartada pelo argumento de autorização acima, e pelo custo: duas rotas, dois arquivos, dois
   itens de menu, duas permissions.
2. **Uma classe de `Action` compartilhada** (`app/Filament/Actions/ConvidarEmMassa.php`) em vez do
   trait. Descartada por pouco: a classe receberia o `Select` por parâmetro de qualquer forma, e
   não há precedente de `app/Filament/Actions` no kit — há de `app/Filament/Concerns`. Se as duas
   telas convergirem para o mesmo formulário, o trait vira uma classe de Action.
3. **Ação em massa da tabela** (`bulkActions`), sobre registros selecionados. Descartada: os
   endereços a convidar **não existem** como registro para selecionar.
4. **Só no `/admin`.** Descartada: o `admin_app` é a persona que mais precisa disso, e ele
   não entra no `/admin`.

### Consequências

- **Positivas**: zero rota, zero permission nova, zero edição na lista do `PapeisSeeder`; o
  `panel_user` continua fora por herança do recorte que já existe.
- **Negativas**: uma modal com `Textarea` de oito linhas é mais apertada que uma página. Se o
  formulário crescer (pré-visualização, CSV), a `Page` volta a ser a resposta certa — e então a
  subtração de Pages, que o passo 7 entrega, passa a ser pré-requisito dela.
- **Riscos**: alguém "promover" a modal para Page por conforto. Mitigação: a contagem de
  permissions na Verificação Final, este ADR e `wikis/convencoes.md`.

### Referências

- `database/seeders/PapeisSeeder.php:87-93, 111-124`
- `app/Support/Paineis.php:74-77, 85-88`
- `vendor/filament/actions/src/Concerns/CanBeAuthorized.php:224`
- `vendor/filament/actions/src/Concerns/CanBeHidden.php:94`
- `.ai/rules/filament.md:30-38`
- CT-09, CT-12, CT-14
- Refinada por: ADR-06

---

## ADR-03: O que conta como falha — e "já tem conta" não conta mais

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

No `invite-only`, "e-mail já cadastrado" é motivo de recusa. No kit também era, em duas pontas: o
`->unique('users', 'email')` nos formulários e uma guarda em `Convite::aceitar()` que lançava.

A wiki `convite-para-usuario-existente`, **já implementada**, remove as duas: convite para
endereço que já tem conta passa a ser uma **oferta de acesso**. Sobram quatro perguntas por
endereço, e uma delas tem resposta menos óbvia do que parece.

### Decisão

Cinco motivos de falha, nesta precedência:

| Motivo | Quando | Por que é falha |
| --- | --- | --- |
| `formato_invalido` | `Validator` do Laravel reprova o endereço | não há a quem enviar |
| `convite_pendente` | já existe convite pendente para esse endereço **e** essa organização | um segundo token válido para o mesmo par é ruído; quem quer renovar usa o `Reenviar` da linha |
| `recusou_antes` | existe convite com `recusado_em` para esse par | consentimento: quem disse não não é reconvidado porque alguém colou a planilha antiga de novo |
| `ja_e_membro` | o endereço já pertence à organização do lote | não há acesso novo a conceder |
| `erro_no_envio` | qualquer `Throwable` no `create()` ou no `enviar()` | ADR-01 |

E **"já tem conta" não é falha**: é sucesso comum do lote. É a maior diferença de comportamento em
relação ao `invite-only`, e o motivo de o lote não precisar consultar `users.email` para decidir se
manda.

**"Já é membro" é falha, não sucesso silencioso.** Mandar o e-mail assim mesmo é escrever "você
foi convidado para a Acme" para quem trabalha na Acme há um ano — parece bug, ou parece phishing.
E sucesso silencioso mentiria no resumo, que é justamente a informação que o operador não tinha
quando colou a planilha. O rótulo na tela diz "já faz parte desta organização", que se lê como
informação e não como erro.

**Contradição aparente com a wiki irmã, resolvida:** o CT-07 dela estabelece que reconvidar quem
já é membro com papel diferente é legítimo e idempotente. Aquilo continua valendo **para o convite
individual**, que é o caminho de mudar o papel de uma pessoa específica. O lote é broadcast, e
broadcast não é a ferramenta para promover alguém — nem para insistir com quem recusou. O model
permite as duas coisas (`valido()` já ignora recusados,
`app/Models/Convite.php:162-174`); o lote é que não as faz automaticamente.

**Sem organização (`tenant_id` nulo), `ja_e_membro` não existe** — não há do que ser membro.
Sobram três motivos.

**O motivo é uma string, não um Enum.** Ele nasce no laço, vira chave de `countBy` no log e é
traduzido por um `match` num lugar só (`ConvidaEmMassa::motivoLegivel()`), com `default` cobrindo
o que ainda não existe. Um Enum acrescentaria arquivo, `label()` e `import` para render o mesmo
`match`.

### Alternativas Consideradas

1. **"Já é membro" como sucesso silencioso** (envia o convite; o aceite é idempotente).
   Descartada pelos argumentos acima. É defensável e custa uma query menos.
2. **"Já é membro" só quando o papel também coincide.** Descartada: exigiria consultar
   `model_has_roles` com `team_id` no pré-carregamento para servir uma intenção que o convite
   individual já atende melhor.
3. **`recusou_antes` não sendo motivo.** Descartada por consentimento — e é a alternativa mais
   próxima de ser aceita. O dado para reverter está no `countBy` do log: se `recusou_antes`
   aparecer sempre com contagem alta, a regra está atrapalhando.
4. **Manter "já tem conta" como falha**, por segurança. Descartada: devolveria a parede que a wiki
   irmã derrubou.
5. **Um Enum `MotivoDeFalhaDoLote`.** Descartada por ora. Vale no dia em que o motivo for
   **persistido** ou filtrado numa tela.

### Consequências

- **Positivas**: o conjunto é pequeno e cada motivo tem uma pergunta clara por trás; duas queries
  respondem por todos os que dependem do banco.
- **Negativas**: `ja_e_membro` e `recusou_antes` aparecem como "falha" numa lista onde não são
  erro. Mitigado pelo texto e pelo título da notificação, que separa enviados de não-enviados.
- **Riscos**: a definição de "pendente" do pré-carregamento sair de sincronia com
  `Convite::valido()` — duas noções que discordam produzem convite duplicado. Mitigação: o passo 3
  repete as mesmas três condições, e CT-03 as exercita.

### Referências

- `app/Models/Convite.php:162-174` (`valido()`)
- `wikis/specs/main/convite-para-usuario-existente/04-casos-de-teste.md` (o CT-07 da irmã)
- CT-03, CT-04, CT-05, CT-11, CT-13
- Refina: ADR-01

---

## ADR-04: Limite de 100 endereços por lote, em config e não no model

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Quem manda no teto é o modo de envio:

| `QUEUE_CONNECTION` | O que cada endereço custa no request | Cem endereços |
| --- | --- | --- |
| `database` (default do kit) | um `INSERT` em `convites`, um `UPDATE` do token, um `INSERT` em `jobs` | três centenas de escritas locais, sem rede — bem abaixo de um segundo |
| `sync` (o `phpunit.xml`, e um deploy mal configurado) | um **handshake SMTP dentro do request** | a 200-400 ms por endereço, 20 a 40 s: acima do `max_execution_time` default de 30 s |

O limite não protege o banco, protege o **request** — e o cenário é real: `sync` em produção é uma
linha de `.env` errada, e o sintoma é 504 com metade do lote enviada. Cem é o tamanho de uma turma
ou de um departamento, que é o caso de uso; quinhentos é migração de base, não operação de tela.

### Decisão

**1. Limite de 100 em `kit.convites.limite_do_lote`, via `KIT_CONVITE_LIMITE_LOTE`.** É config e
não constante por dois motivos: só o projeto sabe se tem worker de fila e qual é o
`max_execution_time` do deploy (mesmo argumento de `validade_em_dias`, `config/kit.php:72-91`), e
`app/Models/Convite.php` e `app/Filament` estão em `KitUpdate::CAMINHOS_DO_KIT` — uma constante
ajustada no projeto instalado seria **sobrescrita no próximo `kit:update`**, enquanto uma linha de
`.env` sobrevive.

**2. O limite ABORTA o lote.** Nada é enviado, nenhum convite é criado. Não é incoerência com
ADR-01: um endereço com problema é dado ruim dentro de uma entrada válida; um lote acima do limite
é a **entrada** inválida, e a resposta correta é não começar.

**3. A modal fica aberta**, por `$action->halt()`
(`vendor/filament/actions/src/Action.php:693-696`) — quem acabou de colar cento e vinte linhas não
pode perdê-las porque a modal fechou com uma notificação de erro. É também por isso que a checagem
fica na ação e não numa closure de validação do campo: o `halt()` entrega o comportamento sem
parsear o texto duas vezes.

**4. O limite NÃO vive no model.** `Convite::convidarEmMassa()` aceita qualquer quantidade. Não
contradiz a ADR-03 da wiki irmã ("a asserção de identidade vive no model"): aquela é barreira de
**segurança**, que vale para todo chamador futuro; esta é teto de **vazão do request HTTP**, e um
job futuro que precise convidar mil endereços tem o direito de fazê-lo. Barreira de identidade
pertence ao dado; teto de vazão pertence à superfície que sofre o timeout.

### Alternativas Consideradas

1. **Sem limite.** Descartada: colar dez mil linhas derruba o request, e com `sync` derruba
   parcialmente — o pior dos estados.
2. **Truncar em 100 e enviar os primeiros.** Descartada: decide pelo operador quais cem endereços
   importam, na ordem em que a planilha estava.
3. **Limite validado no campo** (`->rule()` com closure). Descartada: parsearia o texto duas vezes
   para entregar o mesmo que `halt()` entrega.
4. **Lote grande vira Job.** Descartada por ora — é a resposta certa quando o teto real passar de
   alguns milhares, e está na tabela de cortes do plano.

### Consequências

- **Positivas**: uma chave de config e uma comparação; o operador nunca perde o texto colado; o
  kit não promete um lote que o deploy dele não aguenta.
- **Negativas**: quem convida 120 pessoas divide em dois lotes ou sobe a chave.
- **Riscos**: subir a chave para 5000 sem worker e descobrir o timeout em produção. Mitigação: o
  comentário do `config/kit.php` diz o número **e** a condição, e o README repete.

### Referências

- `config/kit.php:72-91`
- `vendor/filament/actions/src/Action.php:693-696`
- `wikis/specs/main/convite-para-usuario-existente/02-decisoes-arquiteturais.md:130-193`
- CT-06

---

## ADR-05: `Textarea` com um `preg_split`, não `TagsInput` nem `Repeater`

**Status**: Aceita
**Data**: 2026-08-14

### Contexto

Como cem endereços entram na tela? O `TagsInput` deveria ganhar pela escada: é nativo, dedupe é
dele, `->separator()` e `->splitKeys()` existem
(`vendor/filament/forms/src/Components/TagsInput.php:124, 134`) e o estado já chega como array.
Duas leituras o desqualificam.

**A de JS.** O `paste` dele divide o conteúdo colado por um regex montado a partir dos
`splitKeys` (`vendor/filament/forms/resources/js/components/tags-input.js:83-105`), e `splitKeys`
são **nomes de tecla** — `'Enter'`, `'Tab'`, `','`. Quebra de linha não é um split key, e o campo
por baixo é um `<input>` de uma linha. O jeito real de usar esta feature é **colar uma coluna de
planilha** separada por `\n`: exatamente o caminho que ficaria à mercê do navegador, com o sintoma
de uma única tag gigante.

**A de ADR-01.** `->nestedRecursiveRules(['email'])`
(`vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php:27`) valida
cada item **no formulário**, e formulário que reprova reprova a modal inteira. O recurso mais
atraente do `TagsInput` é exatamente o que destrói o resultado parcial.

O `Repeater` cai por ergonomia: cem itens é cem cliques em "adicionar", e não há como colar.

### Decisão

`Textarea` (`->rows(8)`), **sem `->email()` e sem `nestedRecursiveRules()`**, e o parsing numa
expressão em `Convite::separarEmails()` (passo 2 do plano). Um separador de classe única
(`/[\s,;]+/`) cobre quebra de linha, tab, espaço, vírgula e ponto-e-vírgula — o conjunto do que
vem de planilha, de campo "Para:" e de gente digitando. `unique()` dá a deduplicação que o
`TagsInput` daria, e `mb_strtolower` torna o pré-carregamento comparável.

A validação de formato acontece **no laço**, com a mesma regra `email` do Laravel que o campo do
convite individual usa (`ConviteForm.php:32`), para que o lote não aceite o que o formulário
individual recusaria.

### Alternativas Consideradas

1. **`TagsInput`** — pelas duas leituras acima. Volta a ser candidato se o input passar a ser
   digitado item por item, e nunca com `nestedRecursiveRules`.
2. **`Repeater`** — cem cliques, e não aceita colar.
3. **Upload de CSV** — acrescenta storage, encoding, cabeçalho e pré-visualização, e nesse ponto a
   modal já não serve: é o gatilho para reabrir ADR-02.
4. **`filter_var($email, FILTER_VALIDATE_EMAIL)`** — mais curto, e divergiria em casos de borda da
   validação que o resto do kit usa. Um endereço que o lote aceita e o formulário individual
   recusa é uma inconsistência que ninguém entende no suporte.
5. **Um `Validator` só, com regra `emails.*`** — exigiria mapear `emails.3` de volta ao índice.
   Mais código para o mesmo resultado; o `Validator` por endereço está marcado com `ponytail:`.

### Consequências

- **Positivas**: colar de qualquer fonte funciona; uma expressão de parsing; deduplicação de
  graça; a validação de formato fica onde ela pode virar falha parcial.
- **Negativas**: um `Textarea` não mostra chips, então erro de digitação só aparece no resumo
  depois do envio. É o preço do resultado parcial.
- **Riscos**: alguém acrescentar `->email()` "para validar antes". Mitigação: CT-02 fica vermelho.

### Referências

- `vendor/filament/forms/resources/js/components/tags-input.js:83-105`
- `vendor/filament/forms/src/Components/TagsInput.php:124, 134`
- `vendor/filament/forms/src/Components/Concerns/HasNestedRecursiveValidationRules.php:27`
- `app/Filament/Admin/Resources/Convites/Schemas/ConviteForm.php:32`
- CT-02, CT-11, CT-15
- Refina: ADR-01

---

## ADR-06: A subtração do `panel_user` tem de cobrir Resource, Page e Widget

**Status**: Aceita
**Data**: 2026-08-14

> **Esta ADR não é sobre a feature de convite em massa.** É a correção de um buraco em código já
> entregue, que ADR-02 encontrou ao decidir onde a tela do lote vive. Fica registrada aqui porque
> foi aqui que apareceu, e o passo 7 do plano é independente do resto — commit próprio.
> (Numerada **ADR-08** antes da auditoria da wiki; mesmo conteúdo.)

### Contexto

`PapeisSeeder` dá ao `panel_user` a matriz do painel `app` **menos** as permissões dos Resources
de administração (`database/seeders/PapeisSeeder.php:87-93`). Sem essa subtração, registrar
`UserResource` e `ConviteResource` no painel `app` promoveria todo usuário comum do negócio a
administrador da organização — o motivo pelo qual ADR-06 da wiki `admin-da-organizacao` existe.

O problema é que os dois lados da conta leem fontes diferentes:

| Lado | Método | O que enxerga |
| --- | --- | --- |
| a matriz (o que é **dado**) | `Paineis::permissoes()` (`app/Support/Paineis.php:74-77`) → `FilamentShield::getEntitiesPermissions()` | Resources **+ Pages + Widgets + custom** (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:115-125`) |
| a subtração (o que é **tirado**) | `permissoesDeAdministracaoDoApp()` (`PapeisSeeder.php:111-124`) → `Paineis::resources()` (`Paineis.php:85-88`) | **só Resources** |

A subtração cobre metade do espaço que a matriz preenche. Medido no repositório:

```
permissoes(app) total: 37
vindas de Resource:   36
NÃO cobertas pela subtração (Pages/Widgets/custom): 1 -> View:MyProfilePage
```

A única fora de alcance hoje é a página de perfil do Breezy
(`vendor/jeffgreco13/filament-breezy/src/Pages/MyProfilePage.php:10`), que **deve** mesmo ser
visível a todos. Ou seja: o furo é inofensivo hoje e **mecanismo aberto amanhã**. A próxima Page
de administração registrada no painel `app` entra na matriz do `panel_user` e a subtração não tem
como removê-la — com o sintoma que a regra do kit já descreve como o mais caro desta parte:
**nenhum erro, nenhum 403, nenhuma migration, e o cliente editando os próprios colegas**
(`.ai/rules/filament.md:30-38`). Só aparece quando alguém repara.

Foi esse mecanismo que fez ADR-02 recusar a `Page`. Desviar do buraco não é motivo para deixá-lo
de pé.

### Decisão

**1. `App\Support\Paineis` passa a mapear as três famílias.** A varredura de `mapa()`
(`Paineis.php:95-128`) hoje pede `getEntitiesPermissions()` e `getResources()` (`:112-113`), e
passa a pedir também `getPages()` e `getWidgets()`
(`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:66-79`), na **mesma volta do laço**,
com a mesma instância limpa por painel que `shieldNovo()` (`:135-141`) já garante.

**2. Um método, não três.** A pergunta que o seeder faz é "quais as permissões destas entidades
neste painel":

```php
public static function permissoesDe(string $painel, array $fqcns): Collection
{
    return collect(self::mapa()['entidades'][$painel] ?? [])
        ->only($fqcns)
        ->flatten()
        ->unique()
        ->values();
}
```

`->only()` casa por **FQCN exato**, nunca por substring — o PHPDoc de
`permissoesDeAdministracaoDoApp()` já registra que `str_contains($p, 'User')` foi removido de lá
porque um `UserPreferenceResource` futuro cairia nele, e numa **subtração** o erro é o espelhado:
tirar permissão de quem deveria tê-la.

**3. `resources()` fica como está.** A tela de papéis do Shield consome aquele formato
(`resourceFqcn`, `model`, `modelFqcn`, `permissions`) e continua consumindo; `entidades` é um mapa
novo ao lado. Mexer em `resources()` some com o agrupamento por painel da tela, que
`tests/Kit/PaineisTest.php:155-162` cobre.

**4. O seeder vira uma lista de FQCN**, delegando:

```php
return Paineis::permissoesDe('app', [UserResource::class, ConviteResource::class])->all();
```

**5. A extração da chave de permission é diferente por família, e é a armadilha do passo.**
Resource guarda `permissions` como `[affix => ['key' => …, 'label' => …]]`; Page e Widget guardam
`[chave => rótulo]` — `getDefaultPermissionKeys()` ramifica por `is_array($affixes)`
(`FilamentShield.php:91-113`). Aplicar `array_column($e['permissions'], 'key')` numa Page devolve
`[]` **sem erro, sem exception e sem aviso**, e a subtração volta a não subtrair nada: o buraco de
novo, agora com cara de correção. E é o que quem reimplementar isto vai tentar primeiro, porque é
o que está escrito no código atual do seeder (`PapeisSeeder.php:120`). O caminho certo para Page e
Widget é `array_keys()`, que é o que o próprio Shield faz em `getEntityPermissionKeys()`
(`:140-145`).

**6. A chave do mapa vem do campo `*Fqcn` de dentro da entidade**, não da chave externa do array:
em `transformWidgets()`
(`vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:56-70`) a chave
externa pode ser um `WidgetConfiguration`, e só `widgetFqcn` é garantidamente string.

**7. A regra de IA passa a dizer as três.** `.ai/rules/filament.md:30-38` diz "Resource de
administração"; passa a dizer Resource, Page **ou** Widget, com os números medidos. É a única
parte deste ADR que protege contra a repetição, porque é o que o próximo agente lê antes de
registrar uma Page.

**8. A matriz de todos os papéis é idêntica antes e depois.** Isto é consequência, não objetivo: a
única permission de Page descoberta não está na lista de FQCN. Um `panel_user` que **perca** algo
com este passo é sinal de que ele fez mais do que devia — daí a conferência do passo 7d.

### Alternativas Consideradas

1. **Não corrigir**, já que hoje é inofensivo e a feature de lote desviou do problema. Descartada:
   é uma armadilha que espera. O custo de fechar agora é um método e uma delegação; o de descobrir
   depois é um cliente com acesso indevido e nenhum sinal no caminho.
2. **`pages()` e `widgets()` públicos em `Paineis`**, e o seeder somando as três listas.
   Descartada: três métodos e a soma escrita no seeder, que é onde ela pode ser escrita errada (o
   `array_column` na lista errada). `permissoesDe()` responde a pergunta real de uma vez, e a
   extração por família fica num lugar só.
3. **Subtrair Pages dentro do `PapeisSeeder`**, sem tocar em `Paineis`. Descartada: segundo
   mecanismo de recorte ao lado do de Resources, com um segundo lugar para esquecer.
4. **Inverter para lista de permissão** (`panel_user` recebe uma lista explícita em vez da matriz
   menos a administração). Tem mérito real — lista explícita não tem furo por omissão — e foi
   descartada por escopo: mudaria a matriz de todos os papéis do painel `app` e é decisão de
   produto. Fica anotada como a saída se a subtração voltar a falhar.
5. **Casar por substring do nome da permission.** Descartada: já foi removida do kit uma vez, e o
   PHPDoc do método diz por quê.

### Consequências

- **Positivas**: a subtração passa a cobrir **as três famílias de entidade** que a matriz
  preenche; o seeder fica com uma lista de FQCN e nenhuma lógica de extração; a regra de IA passa
  a descrever o mecanismo inteiro.
- **Negativas**: `Paineis` ganha uma chave no mapa e um método — cresce para cobrir um caso que
  ainda não existe em produção. É a exceção justificada ao YAGNI: o caso não existe hoje, mas o
  **furo** existe hoje.
- **Consequência conhecida, e a razão dela estar escrita: `custom_permissions` continua fora do
  alcance.** `getEntitiesPermissions()` mescla **quatro** fontes, não três — Resources, Pages,
  Widgets e as permissions custom de `config('filament-shield.custom_permissions')`
  (`FilamentShield.php:120`). O `permissoesDe()` mapeia por **FQCN de entidade**, e permission
  custom **não tem entidade**: é uma string declarada em config. Então ela entra na matriz do
  painel e a subtração não tem por onde pegá-la.
  - **Hoje é inerte**: a lista está vazia (`config/filament-shield.php:255`), e é por isso que as
    37 permissions do painel `app` fecham com 36 de Resource + 1 de Page.
  - **O sintoma, quando deixar de ser inerte**: uma permission custom de administração declarada
    para o painel `app` cai na matriz do `panel_user` — sem erro, sem 403, sem migration. O mesmo
    sintoma do furo de Page que esta ADR fecha.
  - **Por que registrar em vez de resolver**: mecanismo para uma lista vazia é especulação, e
    `permissoesDe()` não poderia atendê-lo sem mudar de eixo (de FQCN para string). Mas **não
    registrar é pior que o furo original**: o de Page tinha um sintoma que alguém acabaria
    notando, enquanto este reabre depois de alguém ler esta ADR e concluir que o mecanismo está
    fechado. É a conclusão errada que esta consequência existe para impedir.
  - **Gatilho para agir**: a **primeira** entrada em `custom_permissions` que não seja para todo
    mundo. Aí a subtração ganha uma **segunda lista, de strings** ao lado da de FQCN, e as duas são
    somadas antes do `reject()` de `PapeisSeeder.php:87-93`. Não antes: uma lista de strings vazia
    é um lugar a mais para esquecer de preencher.
- **Riscos**: o `array_column` aplicado à família errada (decisão 5) — a correção óbvia que não
  funciona e não avisa. Mitigação: **CT-16**, que assere a chave de uma **Page** saindo de
  `permissoesDe()`; com a extração errada a coleção volta vazia e o caso fica vermelho. É a única
  coisa que acusa.

### Referências

- `app/Support/Paineis.php:74-77, 85-88, 95-128, 135-141`
- `database/seeders/PapeisSeeder.php:87-93, 111-124`
- `vendor/bezhansalleh/filament-shield/src/FilamentShield.php:66-79, 91-113, 115-125, 120, 140-145`
- `vendor/bezhansalleh/filament-shield/src/Concerns/HasEntityTransformers.php:14-70`
- `config/filament-shield.php:255` (`custom_permissions` vazia — a quarta fonte, hoje inerte)
- `vendor/jeffgreco13/filament-breezy/src/Pages/MyProfilePage.php:10`
- `.ai/rules/filament.md:30-38`
- `tests/Kit/PaineisTest.php:135-144, 155-162`
- `wikis/specs/main/admin-da-organizacao/02-decisoes-arquiteturais.md` — ADR-06 (a subtração, que
  cobria metade do espaço)
- CT-16
- Refina: ADR-02
