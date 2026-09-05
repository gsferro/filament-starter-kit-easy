# Decisões Arquiteturais — `kit:info`

## ADR-01: Comando próprio, e não uma seção no `php artisan about`

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

O Laravel 13 permite que um provider acrescente uma seção ao `php artisan about` com uma linha —
`AboutCommand::add('Starter Kit', fn () => [...])` (doc oficial, seção *"About" Artisan Command*,
`search-docs` de 2026-09-02). Seria o caminho de menor código: nenhum comando novo, saída em
`--json` de graça, filtro por `--only=starter_kit`.

### Decisão

Criar `kit:info` como comando próprio. O `about` fica **fora de escopo** (registrado no `00`).

### Alternativas Consideradas

1. **Só a seção no `about`** — descartada por três motivos concretos:
   - o `about` imprime `chave => valor` plano; não tem título de seção secundário, não tem lista com
     marcadores (`itensManuais()`), e não tem seção **condicional** (a de divergências só aparece se
     houver alguma). O conteúdo do `kit:info` não cabe no formato sem virar uma parede de 60 linhas
     misturada a *Environment*, *Cache*, *Drivers*;
   - o requisito diz literalmente "crie um command do kit" — o vocabulário do projeto é `kit:*`, e é
     onde `php artisan list kit` e a documentação já mandam procurar;
   - o `about` roda os resolvers de **todas** as seções; uma consulta ao banco lá dentro
     (`AdministradorDaInstalacao::todos()`) tornaria o `about` dependente de banco migrado, num
     comando que hoje é seguro em qualquer estado da instalação.
2. **Comando próprio E seção no `about`** — descartada por YAGNI: dois lugares para manter, ninguém
   pediu o segundo. Se surgir necessidade de saída para script, a seção no `about` com `--json` é o
   caminho, e é uma linha.

### Consequências

- **Positivas**: formato livre, seção condicional, reuso do visual do `kit:install`.
- **Negativas**: mais uma classe (~150 linhas) em `app/Console/Commands`.
- **Riscos**: nenhum novo.

### Referências

- `app/Console/Commands/KitInstall.php:488-505` — o visual reutilizado
- Laravel 13, `packages.md` → *"About" Artisan Command*

---

## ADR-02: A lista de configurações é `mapaDeConfiguracao()`, iterado — não uma lista escrita à mão

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

São 44 propriedades em `ConfiguracoesDoKit`. O comando poderia ter uma tabela própria com rótulos em
português bem escritos por grupo ("Identidade", "E-mail", "Login social"…). Ficaria mais bonito e
seria a **quarta cópia** da lista de propriedades — a classe já exige três lugares ao acrescentar uma
(`ConfiguracoesDoKit.php:65-68`: a propriedade, a linha do mapa e a migration).

### Decisão

Iterar `ConfiguracoesDoKit::mapaDeConfiguracao()` na ordem em que está e rotular com
`Str::headline($propriedade)`. Propriedade nova aparece no comando **sem edição**. A ordem do mapa já
está agrupada por tema, então a leitura preserva os blocos.

### Alternativas Consideradas

1. **Tabela própria com rótulos e grupos** — descartada: quarta cópia; esquecer a linha aqui é o
   defeito silencioso que o docblock da classe já combate. A perda estética (`Login Linkedin Openid
   Client Id` em vez de "LinkedIn — client id") é aceita.
2. **Subtítulos por prefixo** (`mail_*`, `login_*`, `registro_*`) — descartada: heurística de nome
   que quebra na primeira propriedade fora do padrão (`hub_de_navegacao`, `rotulo_da_organizacao`).
   Se a leitura ficar ruim na prática, a solução é um atributo de grupo na classe, não um `if` no
   comando.

### Consequências

- **Positivas**: zero manutenção ao evoluir o settings; o comando é sempre completo.
- **Negativas**: rótulos mecânicos.
- **Riscos**: o mapa expõe **chave de config**, não rótulo de tela; se a tela renomear um campo, o
  comando não acompanha — aceitável, o comando fala a língua do `config()`.

### Referências

- `app/Settings/ConfiguracoesDoKit.php:269-374`

---

## ADR-03: A cor é lida pelo **formato** de `CorPrimaria::paleta()`, não por uma cópia da precedência

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

Hex vence nome; hex inválido cai para o nome; nome inexistente cai para o padrão. A regra está em
`CorPrimaria::resolver()` (`app/Support/CorPrimaria.php:80-97`), e o docblock dela (`:71-78`) avisa
que *"uma segunda cópia da precedência é a forma de ela divergir no primeiro ajuste"* — lição da
feature em andamento na branch.

### Decisão

O comando não reimplementa nada: chama `CorPrimaria::paleta()` e decide o rótulo pelo **tipo do
retorno** — `[]` é o padrão do Filament; `primary` string é o hex (a regra só devolve string quando
o hex venceu); `primary` array é o nome (o array de tons vem de `constant(Color::X)`).

### Alternativas Consideradas

1. **Repetir os `if`s no comando** — descartada pelo motivo do docblock.
2. **Fazer `resolver()` devolver também a origem** (`['primary' => ..., 'origem' => 'hex']`) —
   descartada: muda a assinatura consumida pelos três painéis e pelo `bootUsing()` do `/app`, por
   causa de um rótulo num comando. Se um segundo consumidor precisar da origem, aí sim.

### Consequências

- **Positivas**: uma regra, uma dona.
- **Negativas**: o comando depende de um detalhe de formato (string × array). É documentado no
  próprio `resolver()` (`@return`), e há caso de teste que quebra se mudar.

### Referências

- `app/Support/CorPrimaria.php:64-97`

---

## ADR-04: O comando não revela mais do que o `.env` já revela a quem tem o terminal

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

Quem roda `php artisan` tem o `.env`. Mas a **saída** de um comando de resumo tem outra vida: vai
para chamado de suporte, captura de tela, log de CI. O `kit:admin` já decidiu isso para o e-mail do
administrador — mascara no terminal e no log (`KitAdmin.php:59`, `:171`, `:211-218`).

### Decisão

- Segredos — os seis de `ConfiguracoesDoKit::encrypted()` — saem como **`definida` / `vazia`**,
  nunca o valor. Na seção de divergências, segredo divergente sai como `diverge (valores não exibidos)`.
- E-mail do administrador sai **mascarado** com a mesma expressão do `kit:admin`.
- Senha do administrador **não sai** — nem a do `.env` (é a do seeder, não necessariamente a atual;
  mostrar "padrão do kit / alterada" comparando `kit.admin.password` mentiria depois de uma troca
  pela tela de perfil). A linha existe para manter a paridade com as cinco perguntas e aponta para
  `kit:admin`.
- O log do comando registra **as chaves** divergentes, nunca valores.
- `client_id` de provedor social e `chave_do_site` do anti-robô são **públicos por natureza** (vão
  para o HTML da página) e saem em claro.

### Alternativas Consideradas

1. **Tudo em claro, "quem tem terminal tem o `.env`"** — descartada: ignora a vida da saída.
2. **Uma flag `--segredos`** — descartada: YAGNI, e é a porta que a decisão 1 fecha.

### Consequências

- **Positivas**: saída colável em chamado de suporte.
- **Negativas**: para ver o valor de um segredo, a pessoa abre o `.env` ou a tela — que é o certo.

### Referências

- `app/Console/Commands/KitAdmin.php:171`, `:211-218`
- `app/Settings/ConfiguracoesDoKit.php:248-258`

---

## ADR-05: `.env` × banco comparam pelo **texto normalizado**, e a seção só existe quando diverge

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

A confusão que o settings documenta (`ConfiguracoesDoKit.php:14-23`) é *"alguém edita o .env, nada
muda"*. A informação útil é **onde** o `.env` diz uma coisa e o banco outra. Mas os tipos divergem por
construção: `mail.mailers.smtp.port` vem `string` do `env()` no arquivo e `int` do settings;
booleanos vêm `bool` dos dois lados mas `null` × `false` aparecem em campos opcionais.

### Decisão

`normalizar()` reduz ambos os lados a texto (`bool` → `1`/`0`, `null` → vazio, array → JSON, escalar →
`(string)`) e compara com `!==`. A seção *"Onde o .env diz diferente do banco"* **só é impressa se a
lista não estiver vazia**, e só quando o banco está valendo — sem tabela de settings não há segundo
lado para comparar.

A leitura dos arquivos vem de `ConfiguracoesDoKit::valoresDosArquivos()`, extraída de
`devolverConfigAoEnv()` justamente para **ler sem escrever** (RQ-04).

### Alternativas Consideradas

1. **Comparar com `==`** — descartada: `0 == ''` é `false` no PHP 8, `null == false` é `true`;
   comportamento por tabela de coerção que ninguém lembra.
2. **Mostrar sempre a coluna `.env` ao lado de cada valor** — descartada: 44 linhas viram 44 pares,
   quase todos iguais; a divergência, que é a informação, some no meio.
3. **Não comparar (A2 negada)** — fica como saída de emergência: remover o passo 4 do PRD e o método
   `divergencias()`; o resto do comando não muda.

### Consequências

- **Positivas**: a saída fica curta no caminho feliz e fala só quando há algo a dizer.
- **Negativas**: `valoresDosArquivos()` faz `require` dos arquivos de `config/` de novo — o mesmo
  custo que `devolverConfigAoEnv()` já tinha, agora num comando de consulta. Irrelevante.
- **Riscos**: falso positivo por tipo não coberto pela normalização (ex.: `float` `0.5` × `"0.5"`
  — `(string) 0.5` é `"0.5"`, coberto; mas `"0.50"` no `.env` divergiria de `0.5`). Aceito e
  documentado no `04` como caso de fronteira.

### Referências

- `app/Settings/ConfiguracoesDoKit.php:408-419`
- `tests/Kit/ConfiguracoesDoKitTest.php:637-659` — guarda do refactor

---

## ADR-06: Banco indisponível não derruba o comando

**Status**: Aceita
**Data**: 2026-09-02

### Contexto

Duas linhas tocam o banco: a lista de administradores e a contagem de organizações. Também
`gravadoNoBanco()` conecta antes de responder (`KitServiceProvider.php:150-160` documenta que
`hasTable()` lança sem banco). Um comando de **consulta** que morre com `SQLSTATE` num projeto recém
clonado, antes do `migrate`, é pior do que não existir — é exatamente quando alguém quer saber "como
isto está configurado?".

### Decisão

Um helper `noBanco(callable): mixed` que devolve `null` em `Throwable`. A linha imprime
`indisponível (banco não acessível)` e o comando segue, com exit `SUCCESS`. Sem log de `warning`: o
provider já logou a mesma condição no boot (`configureSettingsDoKit`, `:186-189`), e duas linhas
para o mesmo fato é ruído.

### Alternativas Consideradas

1. **Falhar com `FAILURE` e mensagem** — descartada: 90% do conteúdo (tudo que é `config()`) está
   disponível sem banco. Jogar fora por causa de duas linhas é perder a informação que existe.
2. **`Schema::hasTable('users')` antes de cada consulta** — descartada: `hasTable()` também lança
   sem conexão; não resolve o caso que importa.

### Consequências

- **Positivas**: funciona em qualquer estado da instalação, inclusive em CI sem banco.
- **Negativas**: `catch (Throwable)` engole erro de programação junto. Mitigado pelo escopo: o
  callable tem uma linha.

### Referências

- `app/Providers/KitServiceProvider.php:150-191`
