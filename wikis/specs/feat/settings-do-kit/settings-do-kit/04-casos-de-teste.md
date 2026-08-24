# Casos de Teste — Settings do kit em `/admin`

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md`
> Derivado do **requisito**. Nenhum cenário foi escrito olhando a implementação da feature — ela não existe ainda.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A1 — fonte da verdade / alinhamento de config no boot | 3 | 3 | **9** | **completo** |
| A2 — cor primária (precedência e tolerância) | 2 | 3 | 6 | padrão |
| A3 — autorização da tela | 2 | 3 | 6 | padrão |
| A4 — trilha de auditoria | 3 | 2 | 6 | padrão |
| A5 — segredo de e-mail (cifra e máscara) | 2 | 3 | 6 | padrão |
| A6 — gravação pelo formulário | 2 | 2 | 4 | padrão |
| A7 — defaults de tabela (atinge os 3 painéis, inclusive telas de vendor) | 2 | 2 | 4 | padrão |
| A8 — `kit:install --custom` concordando com o banco | 2 | 2 | 4 | padrão |
| A9 — identidade visual (upload e fallback) | 2 | 1 | 2 | mínimo |
| A10 — documentação e inventário | 1 | 1 | 1 | mínimo |

- **Técnicas aplicadas**: EP, BVA 3-valores (paginação e hexadecimal), tabela de decisão (precedência de cor; fonte da verdade), matriz papel × ação, rastreio de efeito (config alinhada, trilha de auditoria, log), normalização (hexadecimal).
- **Cenários**: 34 (`CT-01`…`CT-32`, `CT-B01`, `CT-B02`) · **Regras**: 17 · **Mutantes previstos**: 51 · **Sem matador**: 2 (declarados)
- **Revisão adversarial**: obrigatória (há área `completo`) — resultado registrado em `## Revisão Adversarial`.

### Divergência declarada entre skill e Project Rule

A skill sugere `pest --parallel --tia` como padrão. **A rule do projeto vence**: `.ai/rules/testes-browser.md` mediu que `--parallel` derruba 4 de 11 cenários de navegador e que, sem PCOV, o `--tia` em série não termina (abortado após 35 min). Os comandos desta wiki são, portanto, dois: `php artisan test --testsuite=Unit,Feature,Kit,Tenancy` e `composer test:browser`.

---

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| **S**tructure | classe de settings (`App\Settings\ConfiguracoesDoKit`), Page do painel `admin`, migration de settings em `database/settings/`, listener, classe de apoio de identidade, channel de log, chaves novas em `config/kit.php`, registro em `config/settings.php`, tabela `settings` (já existe), tabela `audits` (já existe) | CT-01, CT-11, CT-30 |
| **F**unction | gravar configuração; sobrepor a config do processo; resolver a cor; resolver caminho de arquivo; autorizar acesso e gravação; registrar trilha; propagar do `--custom`; ligar/desligar defaults de tabela | CT-01…CT-29 |
| **D**ata | 21 propriedades tipadas em 4 tipos (`string`, `?string`, `?int`, `bool`); hexadecimal (formato); caminho de arquivo (pode não existir no disco); segredo (cifrado); nome de cor (lista fechada); paginação (faixa); **dado de outra organização** — a cor do `Tenant` NÃO é dado desta feature e precisa continuar vencendo | CT-05…CT-10, CT-16, CT-22…CT-25, CT-28 |
| **I**nterfaces | a tela (`/admin/configuracoes-do-kit`); o `.env` (semente); `php artisan migrate` (semeia); `php artisan kit:install --custom` (propaga); `db:seed` dos dois seeders (permissão); leitura por `config()` de todo consumidor | CT-01, CT-02, CT-11, CT-17…CT-21, CT-26, CT-27 |
| **P**latform | banco (a tabela pode **não existir**, e é o estado normal antes do primeiro `migrate`); disco `public` + `storage:link`; `APP_KEY` (a cifra depende dela); `LOG_KIT_DRIVER` | CT-03, CT-04, CT-25, CT-30 |
| **O**perations | quem opera é `admin` e `master_global`; roda em **todo** request e **todo** comando artisan; instalação nova, instalação já feita, `--force`, `--custom`; e a suíte de testes, onde o `phpunit.xml` fixa os valores | CT-02, CT-03, CT-04, CT-17…CT-20, CT-26 |
| **T**ime | **não se aplica**: nenhuma propriedade é temporal, não há expiração, agendamento nem concorrência de contador. A única ordem que importa é de **boot** (alinhamento antes da configuração global de tabela), e ela é estrutural, não temporal — coberta por CT-23 |

---

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o banco vence a config do processo em tempo de execução | A1 (completo) | RQ-03, RQ-05, RQ-09 | rastreio de efeito + EP | CT-01, CT-02 |
| R2 — sem banco legível vale o `.env`, e a aplicação não cai | A1 (completo) | RQ-03 (⚠️ ver `## Fronteira com o Plano`) | EP (3 partições de falha) | CT-03, CT-04 |
| R3 — a migration semeia o banco a partir da config vigente | A1 (completo) | RQ-03 | rastreio de efeito | CT-11, CT-12 |
| R4 — a cor livre vence a seleção, e a seleção vence o padrão | A2 (padrão) | RQ-06, RQ-07 | tabela de decisão | CT-05 |
| R5 — valor de cor inválido é ignorado e nunca derruba o painel | A2 (padrão) | RQ-06, RQ-07 | EP + BVA de formato | CT-06, CT-07 |
| R6 — só quem tem `View:ConfiguracoesDoKit` abre a tela e salva | A3 (padrão) | RQ-14 | matriz papel × ação | CT-13, CT-14, CT-15 |
| R7 — a permissão nasce semeada no papel `admin` e não vaza | A3 (padrão) | RQ-15 | matriz papel × ação | CT-16 |
| R8 — a tela grava as propriedades no grupo `kit` | A6 (padrão) | RQ-01, RQ-04…RQ-09, RQ-12, RQ-13 | rastreio de efeito + EP | CT-08, CT-09, CT-10 |
| R9 — alteração pela tela deixa uma linha em `audits` por propriedade alterada | A4 (padrão) | RQ-17 | rastreio de efeito | CT-22, CT-23, CT-24 |
| R10 — a trilha não registra o segredo em claro | A5 (padrão) | RQ-08, RQ-17 | rastreio de efeito | CT-25 |
| R11 — a senha de SMTP é cifrada na tabela `settings` | A5 (padrão) | RQ-08 | rastreio de efeito | CT-21 |
| R12 — os defaults de tabela vêm da config e valem para toda tabela | A7 (padrão) | RQ-11 | BVA 3-valores + tabela de decisão | CT-17, CT-18, CT-19, CT-20 |
| R13 — favicon, logo e arte resolvem para URL pública e caem no padrão quando ausentes | A9 (mínimo) | RQ-04, RQ-12, RQ-13 | EP | CT-26 |
| R14 — `kit:install --custom` deixa `.env` e banco concordando | A8 (padrão) | RQ-03 | rastreio de efeito | CT-27 |
| R15 — a tela existe no painel `admin` e é alcançável por URL | A10 (mínimo) | RQ-01, RQ-02 | EP | CT-28 |
| R16 — isto não é settings de organização: a cor do tenant continua vencendo em `/app/{slug}` | A2 (padrão) | RQ-18 | rastreio de efeito | CT-29 |
| R17 — os READMEs documentam a feature e os TODOs foram substituídos | A10 (mínimo) | RQ-10, RQ-11, RQ-19 | EP | CT-31, CT-32 |

**Técnica escalada acima do perfil da área**: R5 está em área `padrão` e recebe **BVA de formato** (3 e 6 dígitos, com e sem `#`), porque partição simples não distingue "valida o formato" de "aceita qualquer string começando com `#`" — e o mutante que sobra é o que derruba toda página do projeto.

---

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| nome da classe `App\Settings\ConfiguracoesDoKit` e da Page homônima | escolha de implementação | detalhe do cenário (aparece no `Dado`, nunca no `Então`) |
| nome do método `aplicarNaConfig()` | escolha de implementação | detalhe |
| nome do grupo de settings (`kit`) | **o requisito também determina**: "settings do starter-kit" — o grupo é a identidade do conjunto no banco, e sem afirmá-lo o cenário não distingue gravar no lugar certo de gravar em qualquer lugar | oráculo válido (CT-08) |
| a chave de config de cada propriedade (`app.name`, `kit.cor_primaria`, …) | **o requisito também determina** o efeito: "ficam disponíveis para alteração" só é verdade se o consumidor passar a ler o valor novo. A chave é o observável | oráculo válido (CT-01) |
| `event = 'settings-updated'` na tabela `audits` | escolha de implementação (o requisito só pede trilha) | detalhe — mas CT-24 **afirma** que o botão de restauração não aparece, que é o comportamento visível que motivou a escolha |
| nome do channel de log `configuracoes` | escolha de implementação | detalhe |
| `View:ConfiguracoesDoKit` como nome da permission | escolha de implementação (deriva de `config/filament-shield.php`) | detalhe — o oráculo é "o papel `admin` abre e o `panel_user` não" |
| rótulo das quatro abas ("Identidade", "E-mail", "Tabelas", "Kit") | **só o PRD determina, e é visível ao usuário** | **pergunta** (abaixo) |
| a máscara `'••••••'` na trilha | só o PRD determina, e é visível | detalhe — o oráculo de CT-25 é a **ausência** do segredo, não a presença da máscara |
| paginação default `10` | **o requisito não determina o número**; `10` é o valor que `ConfiguraFilamentGlobal.php:222` já usa hoje | oráculo de **regressão**, não de requisito — CT-17 afirma o valor efetivo lido antes de comparar |

**Perguntas em aberto** (replicadas em `00-requisito.md` → `## Ambiguidades`):

- Os rótulos das quatro abas são premissa desta wiki — o requisito não os nomeia. Nenhum cenário do `04` asserta rótulo de aba; CT-B01 navega pelas abas por **posição e conteúdo**, não por texto de aba, exatamente para não fixar a premissa. Se o usuário renomear, nada aqui fica vermelho.
- As quatro ambiguidades de `00-requisito.md` (recorte de RQ-03, escopo de "dados de email", densidade de tabela, uma ou duas permissões) estão registradas lá com o par **Assumido / Se negado**. Os cenários que dependem delas estão marcados `@premissa`.

---

## Setup Global

### Personas

Todas por helpers que **já existem** em `tests/Pest.php` — nenhum helper novo, o que evita a armadilha de `.ai/rules/testes.md` (helper cruzado estoura em `--tia` e em arquivo isolado):

- `usuarioDoKit('admin')` — quem opera a tela
- `usuarioDoKit('master_global')` — entra pelo `Gate::before`, sem permission
- `usuarioDoKit('panel_user')` — não deve alcançar a tela
- `usuarioCom(null)` — sem papel nenhum
- `usuarioComPapel('panel_user', $organizacao)` + `duasOrganizacoes()` — só em `tests/Tenancy` (CT-29)

### Fixtures

- Nenhuma factory nova. As propriedades de settings são semeadas pela **migration de settings**, que roda dentro do `RefreshDatabase`.
- `Storage::fake('public')` nos cenários de upload e de resolução de arquivo (CT-10, CT-26).

### Fakes

- `Log::partialMock()` no padrão que `espiarAutenticacao()` já usa em `tests/Pest.php:397-404`, adaptado ao channel `configuracoes` **dentro do arquivo de teste** (um arquivo só o usa — a rule manda deixar helper de um arquivo no arquivo).
- Nenhum `Queue::fake` / `Mail::fake`: a feature não enfileira nem envia.

### Estratégia de DB

`RefreshDatabase` global, aplicado por `tests/Pest.php:45-48` (`Kit`) e `:69-72` (`Tenancy`). `DB_DATABASE=:memory:` fixado no `phpunit.xml`.

### Arquivos de teste

| Arquivo | Suíte | Cenários |
|---|---|---|
| `tests/Kit/ConfiguracoesDoKitTest.php` | Kit | CT-01…CT-04, CT-11, CT-12, CT-21…CT-25, CT-28, CT-30 |
| `tests/Kit/CorPrimariaTest.php` (existente, acrescentar) | Kit | CT-05, CT-06, CT-07 |
| `tests/Kit/ConfiguracoesDoKitTelaTest.php` | Kit | CT-08, CT-09, CT-10, CT-13, CT-14, CT-15, CT-16 |
| `tests/Kit/DefaultsDeTabelaTest.php` | Kit | CT-17…CT-20 |
| `tests/Kit/IdentidadeDoKitTest.php` | Kit | CT-26 |
| `tests/Kit/CustomizadorDaInstalacaoTest.php` (existente, acrescentar) | Kit | CT-27 |
| `tests/Tenancy/IdentidadeVisualTest.php` (existente, acrescentar) | Tenancy | CT-29 |
| `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | Kit | CT-31, CT-32 |
| `tests/Browser/ConfiguracoesDoKitTest.php` | Browser | CT-B01, CT-B02 |

---

## Regra R1 — o banco vence a config do processo em tempo de execução

> `RQ-03`, `RQ-05`, `RQ-09` · perfil **completo** · técnica: **rastreio de efeito** (o QUE: a chave de config; as direções: aconteceu / não aconteceu quando não devia) + **EP**

```gherkin
# language: pt

Funcionalidade: Configurações do kit gravadas no banco

  Regra: o valor gravado no banco substitui o do .env na configuração do processo

    Esquema do Cenário: [CT-01] a propriedade gravada no banco chega ao consumidor por config()
      Dado que a propriedade "<propriedade>" do grupo "kit" está gravada com "<gravado>"
      Quando o kit alinha a configuração do processo
      Então a chave de configuração "<chave>" vale "<gravado>"

      Exemplos:
        | propriedade             | gravado          | chave                  | # partição            |
        | nome_da_aplicacao       | Meu Projeto      | app.name               | string obrigatória    |
        | cor_primaria            | Emerald          | kit.cor_primaria       | string opcional       |
        | mail_from_address       | eu@exemplo.test  | mail.from.address      | string aninhada       |
        | paginacao_padrao        | 25               | kit.tabelas.paginacao  | inteiro               |
        | hub_de_navegacao        | true             | kit.hub                | booleano              |
        | rotulo_das_organizacoes | Empresas         | kit.tenancy.label_plural | vocabulário         |

    Cenário: [CT-02] valor do .env que não foi gravado no banco continua valendo
      Dado que a configuração do processo tem "kit.cor_primaria" igual a "Blue"
      E que a propriedade "cor_primaria" do grupo "kit" não tem linha na tabela
      Quando o kit alinha a configuração do processo
      Então a chave de configuração "kit.cor_primaria" continua valendo "Blue"
      E um aviso é registrado no canal de log da feature
```

**Nota sobre discriminância dos valores de CT-01**: `25` na paginação e não `10` — `10` é o default de hoje (`ConfiguraFilamentGlobal.php:222`) e um alinhamento que não faz nada passaria com ele. `true` no hub e não `false` — `false` é o default do `phpunit.xml`. `Empresas` e não `Organizações`. Cada linha escolhe o valor que **difere** do que o ambiente já entrega.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M1 | o mapa propriedade → chave existe, mas `aplicarNaConfig()` nunca é chamado no boot | CT-01 (toda linha) |
| M2 | o mapa esquece uma família de chaves (típico: as aninhadas de `mail.*`, escritas como `mail_from_address` em vez de `mail.from.address`) | CT-01 (linha `mail_from_address`) |
| M3 | `config()` chamado com o valor **antigo** (lê a config e regrava, em vez de ler o banco) | CT-01 (toda linha, pelos valores discriminantes) |
| M4 | propriedade ausente sobrepõe a config com `null`, apagando o valor do `.env` | CT-02 |
| M5 | inteiro gravado como string chega como `"25"` e `defaultPaginationPageOption()` recebe string | CT-01 (linha `paginacao_padrao`, com asserção de tipo) |
| M6 | booleano gravado como `"true"` (string) chega verdadeiro para `if`, mas falso para `===` | CT-01 (linha `hub_de_navegacao`, com asserção de tipo) |

---

## Regra R2 — sem banco legível vale o `.env`, e a aplicação não cai

> `RQ-03` · perfil **completo** · técnica: **EP** (três partições de falha, isoladas uma por cenário)

⚠️ **Fronteira**: o requisito **não** diz "a aplicação não deve cair". A regra é derivada de RQ-01 e RQ-03 pelo argumento de que uma tela que derruba o processo não é uma tela que existe. Está registrada aqui como regra derivada, não como cláusula — e é a única do conjunto nessa condição.

```gherkin
# language: pt

  Regra: falha ao ler o banco não altera a configuração nem interrompe o processo

    Cenário: [CT-03] instalação antes do primeiro migrate não é afetada
      Dado que a tabela de settings não existe
      Quando o kit alinha a configuração do processo
      Então nenhuma exceção é lançada
      E a chave de configuração "app.name" continua com o valor do .env
      E nenhum aviso é registrado no canal de log da feature

    Cenário: [CT-04] falha na leitura do banco cai para o .env com aviso
      Dado que a leitura das propriedades do grupo "kit" lança uma exceção
      Quando o kit alinha a configuração do processo
      Então nenhuma exceção é lançada
      E a chave de configuração "app.name" continua com o valor do .env
      E um aviso é registrado no canal de log da feature com o motivo da falha
```

**Por que CT-03 afirma a ausência de aviso e CT-04 a presença**: as duas partições têm o mesmo resultado de configuração e resultados **opostos** de observabilidade. Tabela ausente é o estado normal de uma instalação nova — avisar ali produziria um `warning` em todo `migrate` de todo mundo, e um canal que grita no caminho feliz é um canal que ninguém lê. Banco quebrado é anomalia e precisa aparecer. Sem os dois cenários, um `catch` que engole tudo em silêncio e um que grita sempre são indistinguíveis.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M7 | `Schema::hasTable()` **fora** do `try` — num banco inexistente ele lança antes de responder, e todo `artisan` morre | CT-03 |
| M8 | `catch (Exception)` em vez de `catch (Throwable)` — `Error` e `TypeError` escapam | CT-04 (o mock lança `Error`) |
| M9 | o `catch` sobrepõe a config com valores vazios antes de sair | CT-03, CT-04 |
| M10 | `warning` emitido também quando a tabela não existe | CT-03 |
| M11 | nenhum log no `catch` — a falha fica invisível | CT-04 |

---

## Regra R3 — a migration semeia o banco a partir da config vigente

> `RQ-03` · perfil **completo** · técnica: **rastreio de efeito** (aconteceu / uma só vez / reversível)

```gherkin
# language: pt

  Regra: a primeira migração leva as respostas da instalação para o banco

    Cenário: [CT-11] as propriedades nascem com o valor que a configuração tinha
      Dado um banco recém-migrado
      Quando as propriedades do grupo "kit" são lidas
      Então existem 21 propriedades
      E a propriedade "nome_da_aplicacao" vale o mesmo que a chave "app.name" do .env
      E a propriedade "paginacao_padrao" vale o mesmo que a chave "kit.tabelas.paginacao"

    Cenário: [CT-12] a migração é reversível e o desfazer devolve o .env como única fonte
      Dado um banco recém-migrado com "nome_da_aplicacao" gravado como "Gravado no banco"
      Quando a migração de settings é desfeita
      Então nenhuma propriedade do grupo "kit" existe na tabela
      E o alinhamento da configuração não altera a chave "app.name"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M12 | a migration semeia com literais em vez de `config(...)` — a resposta do `kit:install` nunca chega ao banco | CT-11 |
| M13 | uma propriedade fica de fora da migration; a tela salva mas o boot avisa toda vez | CT-11 (contagem de 21) |
| M14 | `down()` vazio — o rollback não desfaz e o desligamento de emergência não existe | CT-12 |
| M15 | `add()` sem `deleteIfExists` no `down()` — o segundo `migrate` estoura `SettingAlreadyExists` | CT-12 (seguido de novo `migrate` no mesmo caso) |

---

## Regra R4 — a cor livre vence a seleção, e a seleção vence o padrão

> `RQ-06`, `RQ-07` · perfil **padrão** · técnica: **tabela de decisão** (2 condições × 3 estados cada, colapsada onde a ação comprovadamente não depende)

| # | `cor_primaria_hex` | `cor_primaria` | Paleta resultante |
|---|---|---|---|
| 1 | válido | válido | a do hexadecimal |
| 2 | válido | vazio | a do hexadecimal |
| 3 | inválido | válido | a do nome |
| 4 | vazio | válido | a do nome |
| 5 | inválido | inválido | vazia (padrão do Filament) |
| 6 | vazio | vazio | vazia (padrão do Filament) |

```gherkin
# language: pt

  Regra: a cor livre em hexadecimal vence a seleção pelo enum de cores

    Esquema do Cenário: [CT-05] a paleta sai da fonte de maior precedência disponível
      Dado que a cor livre está definida como "<hex>"
      E que a cor selecionada está definida como "<nome>"
      Quando a paleta primária do kit é resolvida
      Então a paleta é "<resultado>"

      Exemplos:
        | hex      | nome     | resultado          | # linha da tabela |
        | #7c3aed  | Blue     | hexadecimal #7c3aed | 1                |
        | #7c3aed  |          | hexadecimal #7c3aed | 2                |
        | azul     | Blue     | Color::Blue         | 3                |
        |          | Blue     | Color::Blue         | 4                |
        | azul     | Roxo     | vazia               | 5                |
        |          |          | vazia               | 6                |
```

**Discriminância**: `#7c3aed` com `Blue` presente é a linha que separa "hex vence" de "nome vence" — com o nome vazio (linha 2) as duas implementações concordam. A linha 3 é a que separa "hex inválido cai para o nome" de "hex inválido zera tudo".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M16 | a seleção vence o hexadecimal (ordem invertida no `??`) | CT-05 linha 1 |
| M17 | hexadecimal inválido zera a paleta em vez de cair para o nome | CT-05 linha 3 |
| M18 | hexadecimal vazio (string `''`) tratado como definido — a paleta vira `['primary' => '']` | CT-05 linha 4 |
| M19 | a paleta do hexadecimal é devolvida como array em vez de string, e o `ColorManager` deixa de gerar a escala | CT-05 linhas 1 e 2 (asserção sobre o valor exato devolvido) |

---

## Regra R5 — valor de cor inválido é ignorado e nunca derruba o painel

> `RQ-06`, `RQ-07` · perfil **padrão**, técnica **escalada** para **BVA de formato** · motivo: partição simples não distingue "valida o formato" de "aceita qualquer coisa que comece com `#`", e o mutante que sobra derruba toda página do projeto

```gherkin
# language: pt

  Regra: nome ou hexadecimal fora do formato é ignorado, e o painel volta ao padrão

    Esquema do Cenário: [CT-06] hexadecimal fora do formato é recusado sem lançar
      Dado que a cor livre está definida como "<hex>"
      E que nenhuma cor está selecionada
      Quando a paleta primária do kit é resolvida
      Então a paleta é "<resultado>"
      E nenhuma exceção é lançada

      Exemplos:
        | hex       | resultado           | # borda de formato        |
        | #abc      | hexadecimal #abc    | 3 dígitos — válido        |
        | #aabbcc   | hexadecimal #aabbcc | 6 dígitos — válido        |
        | #ab       | vazia               | 2 dígitos — curto demais  |
        | #abcd     | vazia               | 4 dígitos — entre os dois |
        | #aabbccd  | vazia               | 7 dígitos — longo demais  |
        | aabbcc    | vazia               | sem o "#"                 |
        | #gggggg   | vazia               | fora do alfabeto hexa     |
        | #7C3AED   | hexadecimal #7C3AED | maiúsculas — válido       |

    Cenário: [CT-07] o painel sobe com uma cor inválida em vez de morrer em toda página
      Dado que a cor selecionada está definida como "Roxo", que não existe no enum de cores
      E que a cor livre está definida como "vermelho"
      Quando o painel de administração é bootado
      Então a cor primária registrada é a do padrão do Filament
      E a página inicial do painel responde com sucesso
```

**Por que `#abcd` está na tabela**: é o valor que separa `/^#[0-9a-fA-F]{3,6}$/` (frouxo, aceita 4 e 5) de `/^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/` (correto). Sem essa linha, as duas expressões passam.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M20 | sem validação: o valor vai direto para a paleta e `convertToOklch()` recebe lixo | CT-06 (linhas `aabbcc`, `#gggggg`), CT-07 |
| M21 | regex `{3,6}` em vez de `{3}|{6}` | CT-06 linha `#abcd` |
| M22 | regex com `[0-9a-f]` sem `A-F` — hexadecimal em maiúsculas é recusado | CT-06 linha `#7C3AED` |
| M23 | `str_starts_with($hex, '#')` no lugar da validação de formato | CT-06 linhas `#ab`, `#gggggg` |
| M24 | a guarda do nome é removida junto com a refatoração e `constant()` volta a lançar | CT-07 |

---

## Regra R6 — só quem tem `View:ConfiguracoesDoKit` abre a tela e salva

> `RQ-14` · perfil **padrão** · técnica: **matriz papel × ação**

| Persona | abrir a tela | salvar |
|---|---|---|
| `master_global` (sem permission, entra pelo `Gate::before`) | permitido — CT-13 | permitido — CT-13 |
| `admin` (com a permission) | permitido — CT-14 | permitido — CT-08 |
| `panel_user` (papel do painel `app`) | recusado — CT-15 | recusado — CT-15 |
| sem papel nenhum | recusado — CT-15 | recusado — CT-15 |
| `admin` com a permission **revogada** | recusado — CT-15 | recusado — CT-15 |

**Toda coluna tem célula válida exercitada** (linhas 1 e 2) e célula inválida (linhas 3 a 5). A coluna "salvar" não é herdada da coluna "abrir": ela é exercitada por chamada própria, porque `SettingsPage::save()` consulta `canEdit()` e não `canAccess()` (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:64`) — uma implementação que devolvesse `true` fixo em `canEdit()` passaria em todo cenário de abertura.

```gherkin
# language: pt

  Regra: a tela de configurações exige a permissão de acesso para abrir e para gravar

    Cenário: [CT-13] o administrador geral abre e grava sem nenhuma permissão atribuída
      Dado o administrador geral autenticado no painel de administração
      Quando ele abre a tela de configurações do kit e grava o nome "Pelo geral"
      Então a resposta é bem-sucedida
      E a propriedade "nome_da_aplicacao" do grupo "kit" vale "Pelo geral"

    Cenário: [CT-14] quem tem a permissão vê o item no menu do painel
      Dado o administrador da aplicação autenticado no painel de administração
      Quando o menu de navegação do painel é montado
      Então a tela de configurações do kit está entre os destinos

    Esquema do Cenário: [CT-15] quem não tem a permissão não abre nem grava
      Dado "<persona>" autenticado no painel de administração
      Quando ele tenta abrir a tela de configurações do kit e gravar o nome "Invadido"
      Então o acesso é recusado com o código 403
      E a propriedade "nome_da_aplicacao" do grupo "kit" não vale "Invadido"

      Exemplos:
        | persona                          | # partição            |
        | o usuário comum do negócio       | papel de outro painel |
        | um usuário sem papel nenhum      | sem papel             |
        | o administrador sem a permissão  | permissão revogada    |
```

**Discriminância da persona**: a linha "administrador sem a permissão" é a que separa "confere a permission" de "confere o papel" — as outras duas passariam numa implementação que só olhasse o papel. E CT-15 afirma o **não-efeito** (a propriedade não mudou), não só o 403: uma implementação que gravasse e depois abortasse passaria no 403.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M25 | `HasPageShield` ausente — `Page::canAccess()` devolve `true` para qualquer autenticado | CT-15 (linhas 1 e 2) |
| M26 | `canAccess()` confere o **papel** (`hasRole('admin')`) em vez da permission | CT-15 linha 3 |
| M27 | `canEdit()` devolve `true` fixo — a tela é somente leitura para ninguém | CT-15 (asserção de não-efeito) |
| M28 | `canAccess()` nega para o `master_global` porque ele não tem a permission na tabela | CT-13 |
| M29 | a Page fica fora do menu para quem tem a permissão (`shouldRegisterNavigation()` sobrescrito errado) | CT-14 |

---

## Regra R7 — a permissão nasce semeada no papel `admin` e não vaza

> `RQ-15` · perfil **padrão** · técnica: **matriz papel × ação**

```gherkin
# language: pt

  Regra: a permissão da tela de configurações é padrão do kit, sem passo manual

    Cenário: [CT-16] os seeders do kit entregam a permissão ao administrador e a ninguém mais
      Dado um banco semeado com os seeders de permissões e de papéis do kit
      Quando as permissões de cada papel são lidas
      Então o papel "admin" tem a permissão de ver a tela de configurações do kit
      E o papel "infra" não a tem
      E o papel "panel_user" não a tem
      E o papel "master_global" não tem permissão nenhuma
```

**Nota**: `master_global` sem permission nenhuma é invariante já coberta por `tests/Kit/PaineisTest.php:127`. Ela é repetida aqui porque é a asserção que impede o conserto errado ("acrescentar a permission ao `master_global`") de passar.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M30 | a Page é criada fora de `app/Filament/Admin/Pages/`, não é descoberta e nenhuma permission é gerada | CT-16 |
| M31 | a Page entra em `config('filament-shield.pages.exclude')` "para não poluir" | CT-16 |
| M32 | a permission é acrescentada à mão numa lista, e a lista fica desatualizada | CT-16 (é um cenário de resultado, não de mecanismo — passa nos dois; **mata só se combinado com CT-30**) |
| M33 | a Page é movida para o painel `app` e todo `panel_user` herda a configuração da instalação | CT-16 (linha do `panel_user`) |

---

## Regra R8 — a tela grava as propriedades no grupo `kit`

> `RQ-01`, `RQ-04`…`RQ-09`, `RQ-12`, `RQ-13` · perfil **padrão** · técnica: **rastreio de efeito** + **EP** por tipo de campo

**Gate de tela de escrita**: esta é a gravação por componente exigida pela regra do par (*uma tela aberta não é uma tela que grava*).

```gherkin
# language: pt

  Regra: o formulário grava cada propriedade no grupo de settings do kit

    Cenário: [CT-08] o formulário grava as quatro famílias de campo de uma vez
      Dado o administrador da aplicação autenticado no painel de administração
      Quando ele preenche o nome "Projeto Novo", a cor "Emerald", a cor livre "#7c3aed",
        o remetente "contato@exemplo.test", a paginação 25 e o hub ligado, e grava
      Então a gravação não devolve erro de formulário
      E o grupo "kit" tem "nome_da_aplicacao" igual a "Projeto Novo"
      E o grupo "kit" tem "cor_primaria" igual a "Emerald"
      E o grupo "kit" tem "cor_primaria_hex" igual a "#7c3aed"
      E o grupo "kit" tem "mail_from_address" igual a "contato@exemplo.test"
      E o grupo "kit" tem "paginacao_padrao" igual ao inteiro 25
      E o grupo "kit" tem "hub_de_navegacao" igual ao booleano verdadeiro

    Esquema do Cenário: [CT-09] campo fora do domínio é recusado e nada é gravado
      Dado o administrador da aplicação autenticado no painel de administração
      E que o nome da aplicação gravado é "Antes"
      Quando ele preenche "<campo>" com "<valor>" e grava
      Então o formulário acusa erro em "<campo>"
      E o grupo "kit" continua com "nome_da_aplicacao" igual a "Antes"

      Exemplos:
        | campo             | valor              | # partição inválida        |
        | nome_da_aplicacao |                    | obrigatório ausente        |
        | mail_from_address | nao-e-email        | formato de e-mail          |
        | paginacao_padrao  | 0                  | abaixo do mínimo (borda−1) |
        | paginacao_padrao  | 101                | acima do máximo (borda+1)  |

    Cenário: [CT-10] o arquivo enviado fica no disco público e visível sem sessão
      Dado o administrador da aplicação autenticado no painel de administração
      E um disco público falso
      Quando ele anexa uma imagem ao favicon e grava
      Então o arquivo existe no disco público
      E a visibilidade do arquivo no disco é pública
      E o grupo "kit" tem "favicon" apontando para o arquivo gravado
```

**Discriminância de CT-09**: `0` e `101` são borda−1 e borda+1 de um domínio `[1, 100]`; a borda em si (`1` e `100`) é exercitada por CT-17. Cada partição inválida está **isolada** em uma linha — combinar duas faria a primeira validação mascarar a segunda. E o `Então` afirma o **não-efeito** no banco, não só o erro de formulário: uma implementação que validasse depois de gravar passaria só com o erro.

**Discriminância de CT-10**: o oráculo é a **visibilidade no disco**, não a existência do arquivo. O default do Filament é `private` (doc do Filament 5, "files are uploaded with `private` visibility"), e um favicon privado existe no disco e responde 403 no `<head>` de toda página — exatamente o defeito que "o arquivo existe" não pega.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M34 | o `statePath` não é `data` e o formulário grava em lugar nenhum | CT-08 |
| M35 | o grupo da classe de settings é outro (`general`, `app`) e a leitura do boot não acha | CT-08 (asserção sobre o grupo `kit`) |
| M36 | o campo de paginação sem `numeric()` grava a string `"25"` | CT-08 (asserção de tipo inteiro) |
| M37 | `->dehydrated(false)` num campo, que desaparece do estado antes do `save()` | CT-08 |
| M38 | `required()` ausente no nome — grava vazio e o cabeçalho dos painéis fica sem título | CT-09 linha 1 |
| M39 | `minValue`/`maxValue` ausentes na paginação | CT-09 linhas 3 e 4 |
| M40 | `FileUpload` sem `->visibility('public')` | CT-10 |
| M41 | `FileUpload` sem `->disk('public')` — grava no disco default | CT-10 |

---

## Regra R9 — alteração pela tela deixa uma linha em `audits` por propriedade alterada

> `RQ-17` · perfil **padrão** · técnica: **rastreio de efeito** (aconteceu / não aconteceu quando não devia / uma só vez)

```gherkin
# language: pt

  Regra: cada propriedade alterada gera um registro de auditoria com valor antigo e novo

    Cenário: [CT-22] a alteração registra quem mudou, o que mudou e os dois valores
      Dado o administrador da aplicação autenticado no painel de administração
      E que o nome da aplicação gravado é "Antes"
      Quando ele grava o nome "Depois"
      Então existe um registro de auditoria do usuário autenticado
      E o valor antigo do registro contém "nome_da_aplicacao" igual a "Antes"
      E o valor novo do registro contém "nome_da_aplicacao" igual a "Depois"
      E o registro aponta para a linha da tabela de settings daquela propriedade

    Cenário: [CT-23] gravar sem alterar nada não gera registro de auditoria
      Dado o administrador da aplicação autenticado no painel de administração
      E que o nome da aplicação gravado é "Igual"
      Quando ele grava o nome "Igual"
      Então nenhum registro de auditoria é criado

    Cenário: [CT-24] o registro não oferece restauração, que corromperia a linha de settings
      Dado um registro de auditoria de alteração de configuração do kit
      Quando a trilha de alterações é listada no painel de infraestrutura
      Então a listagem responde com sucesso
      E o registro não é do tipo de evento que habilita a restauração
```

**Por que CT-24 existe e é da camada de tela**: a listagem de `/infra/audits` faz `->with(['user','auditable'])`; um `auditable_type` que não resolva derruba a tela **inteira**, e a `RestoreAuditAction` restauraria `['nome_da_aplicacao' => …]` numa linha cujas colunas são `group/name/payload`. As duas consequências são de integração com o pacote de UI e nenhuma aparece asserindo só a tabela `audits`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M42 | o listener não é registrado — nenhuma trilha é gravada, e a tela fica verde | CT-22 |
| M43 | ouve `SettingsSaved` em vez de `SavingSettings`, e sem os valores antigos grava tudo a cada salvamento | CT-22 (valor antigo) e CT-23 |
| M44 | sem diff: grava uma linha por propriedade a cada salvamento (21 linhas por clique) | CT-23 |
| M45 | `auditable_type` recebe a classe de settings, que não é model — a listagem de `/infra/audits` derruba | CT-24 |
| M46 | `event = 'updated'` — o botão de restauração aparece e corrompe a linha de settings | CT-24 |
| M47 | `old_values`/`new_values` gravados como `['payload' => …]`, e a listagem não diz qual propriedade mudou | CT-22 |

---

## Regra R10 — a trilha não registra o segredo em claro

> `RQ-08`, `RQ-17` · perfil **padrão** · técnica: **rastreio de efeito** (o QUE: o conteúdo do registro)

```gherkin
# language: pt

  Regra: a senha de e-mail aparece na trilha como alterada, nunca como valor

    Cenário: [CT-25] a troca da senha de e-mail é registrada sem o valor
      Dado o administrador da aplicação autenticado no painel de administração
      E que a senha de e-mail gravada é "segredo-antigo"
      Quando ele grava a senha "segredo-novo"
      Então existe um registro de auditoria referente a "mail_password"
      E nenhum registro de auditoria contém o texto "segredo-novo"
      E nenhum registro de auditoria contém o texto "segredo-antigo"
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M48 | a máscara é aplicada só no valor novo, e o antigo vaza | CT-25 (asserção sobre "segredo-antigo") |
| M49 | a máscara é aplicada por comparação de nome frouxa (`str_contains($nome, 'pass')`), que erraria um campo futuro — **⚠️ sem matador nesta entrega**: só há um campo de segredo, então nenhum cenário distingue a lista explícita da heurística. **Lacuna declarada**: tentado derivar um segundo campo de segredo fictício, e criar propriedade só para o teste seria testar a implementação. Fica registrado para a wiki de login social, que traz `client_secret` | ⚠️ sem matador |
| M50 | a propriedade não entra em `encrypted()` e a senha fica em claro no `payload` | CT-21 |

---

## Regra R11 — a senha de SMTP é cifrada na tabela `settings`

> `RQ-08` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
# language: pt

  Regra: a senha de e-mail é cifrada no repositório de settings e decifrada na leitura

    Cenário: [CT-21] a senha vai cifrada para a tabela e volta legível
      Dado que a senha de e-mail é gravada como "senha-do-smtp"
      Quando a linha da tabela de settings daquela propriedade é lida diretamente
      Então o conteúdo bruto não contém "senha-do-smtp"
      E a leitura pela classe de settings devolve "senha-do-smtp"
      E a chave de configuração do transporte de e-mail vale "senha-do-smtp" depois do alinhamento
```

**Discriminância**: as três asserções juntas separam três implementações — sem cifra (a primeira falha), cifra sem decifra (a segunda falha) e decifra que não chega ao consumidor (a terceira falha). Nenhuma delas sozinha distingue as três.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M50 | `encrypted()` não devolve `mail_password` | CT-21 (primeira asserção) |
| M51 | o alinhamento grava o valor cifrado na config, e o mailer tenta autenticar com o criptograma | CT-21 (terceira asserção) |

---

## Regra R12 — os defaults de tabela vêm da config e valem para toda tabela

> `RQ-11` · perfil **padrão** · técnica: **BVA 3-valores** (paginação) + **tabela de decisão** (três interruptores independentes)

```gherkin
# language: pt

  Regra: paginação, listras, persistência de recorte e colunas arrastáveis obedecem à configuração

    Esquema do Cenário: [CT-17] a paginação default é a configurada, inclusive nas bordas
      Dado que a paginação configurada é <paginacao>
      Quando uma tabela do kit é configurada
      Então a opção de paginação default da tabela é <paginacao>

      Exemplos:
        | paginacao | # borda                 |
        | 1         | mínimo                  |
        | 10        | valor de fábrica de hoje |
        | 25        | valor diferente do de hoje |
        | 100       | máximo                  |

    Esquema do Cenário: [CT-18] cada interruptor de tabela liga e desliga o seu efeito
      Dado que "<interruptor>" está "<estado>" na configuração
      Quando uma tabela do kit é configurada
      Então "<efeito>" está "<estado>"

      Exemplos:
        | interruptor              | estado    | efeito                              |
        | tabela_listrada          | ligado    | linhas listradas                    |
        | tabela_listrada          | desligado | linhas listradas                    |
        | persistir_filtros        | ligado    | persistência de filtro em sessão    |
        | persistir_filtros        | desligado | persistência de filtro em sessão    |
        | colunas_redimensionaveis | ligado    | colunas arrastáveis                 |
        | colunas_redimensionaveis | desligado | colunas arrastáveis                 |

    Cenário: [CT-19] desligar um interruptor não desliga os outros
      Dado que apenas "tabela_listrada" está desligado na configuração
      Quando uma tabela do kit é configurada
      Então as linhas não são listradas
      E a persistência de filtro em sessão continua ligada
      E a paginação default continua sendo a configurada

    Cenário: [CT-20] as telas dos painéis continuam de pé com os defaults desligados
      Dado que os três interruptores de tabela estão desligados
      Quando as telas com tabela do painel de infraestrutura são visitadas
      Então todas respondem com sucesso
```

**Por que CT-19 existe**: `CT-18` isola um interruptor por linha, e uma implementação que ligasse os três juntos (um `if` só governando o bloco inteiro) passaria em todas as linhas de `CT-18`, porque cada linha só afirma sobre o seu efeito. `CT-19` é a linha da tabela de decisão que separa "três condições" de "uma".

**Por que CT-20 existe**: o comentário de `ConfiguraFilamentGlobal.php:204-214` registra oito telas de `/infra` caindo em 500 por uma configuração global aplicada a tabelas de vendor. Uma configuração condicional é exatamente a classe de mudança que reintroduz aquilo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M52 | a paginação continua literal `10` e a config é ignorada | CT-17 (linha 25) |
| M53 | um `if` só governa os três interruptores | CT-19 |
| M54 | a condição de um interruptor está negada | CT-18 (as duas linhas daquele interruptor) |
| M55 | `aplicaMacrosDeColuna()` é chamado sem a guarda de existência da macro e a tabela morre onde o pacote não está | CT-20 |
| M56 | `persistir_filtros` desliga só um dos quatro `persist*` | CT-18 (linha desligada, asserindo os quatro) |

---

## Regra R13 — favicon, logo e arte resolvem para URL pública e caem no padrão quando ausentes

> `RQ-04`, `RQ-12`, `RQ-13` · perfil **mínimo** · técnica: **EP** (3 partições)

```gherkin
# language: pt

  Regra: caminho de arquivo vira URL pública, e caminho quebrado cai no padrão do kit

    Esquema do Cenário: [CT-26] a resolução do arquivo de identidade cobre as três partições
      Dado que "<chave>" está configurado como "<valor>"
      E que o arquivo "<existe_no_disco>" existe no disco público
      Quando o caminho de identidade é resolvido
      Então o resultado é "<resultado>"

      Exemplos:
        | chave          | valor           | existe_no_disco | resultado                    | # partição      |
        | favicon        | kit/fav.png     | kit/fav.png     | URL pública de kit/fav.png   | presente        |
        | favicon        | kit/fav.png     |                 | nulo                         | ausente no disco|
        | favicon        |                 |                 | nulo                         | não configurado |
        | arte_do_login  | kit/arte.svg    |                 | URL de images/auth/login.svg | fallback do kit |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M57 | sem a guarda de existência: devolve URL de arquivo inexistente e o `<head>` de toda página pede um 404 | CT-26 linha 2 |
| M58 | a arte de login perde o fallback e as telas de autenticação ficam sem imagem | CT-26 linha 4 |
| M59 | usa `asset()` em vez da URL do disco e aponta para `public/kit/...`, que não existe | CT-26 linha 1 |

---

## Regra R14 — `kit:install --custom` deixa `.env` e banco concordando

> `RQ-03` · perfil **padrão** · técnica: **rastreio de efeito**

```gherkin
# language: pt

  Regra: refazer nome e cor pelo instalador vale também para a configuração gravada no banco

    Cenário: [CT-27] o instalador propaga o nome e a cor para o settings
      Dado um projeto com a tabela de settings semeada
      Quando o instalador aplica o nome "Refeito" e a cor "Teal" sem tocar o banco de dados
      Então o arquivo de ambiente tem o nome "Refeito" e a cor "Teal"
      E o grupo "kit" tem "nome_da_aplicacao" igual a "Refeito"
      E o grupo "kit" tem "cor_primaria" igual a "Teal"
```

**Por que as duas metades no mesmo `Então`**: é exatamente o cenário em que as duas fontes discordam. Afirmar só o `.env` (que é o que `tests/Kit/CustomizadorDaInstalacaoTest.php` já faz hoje) deixa passar a implementação que reescreve o arquivo e não toca o banco — em que a resposta do usuário não tem efeito nenhum.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M60 | o instalador só reescreve o `.env` e o banco continua vencendo | CT-27 |
| M61 | a gravação no settings não é condicional à existência da tabela e o instalador quebra num projeto antes do `migrate` | CT-27 (variante sem a tabela — asserida no mesmo arquivo) |

---

## Regra R15 — a tela existe no painel `admin` e é alcançável por URL

> `RQ-01`, `RQ-02` · perfil **mínimo** · técnica: **EP**

```gherkin
# language: pt

  Regra: a tela de configurações é uma página do painel de administração

    Cenário: [CT-28] a tela está registrada no painel de administração e em nenhum outro
      Quando as páginas registradas em cada painel são lidas
      Então a tela de configurações do kit está entre as do painel de administração
      E não está entre as do painel de negócio
      E não está entre as do painel de infraestrutura
```

`tests/Kit/InventarioDeTelasTest.php` já reprova se a URL não entrar em `telasDoKit()`; CT-28 é a metade que aquele arquivo não cobre — que a tela está **só** no `admin`. Estar no `app` é o defeito de M33.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M62 | a Page é registrada nos três painéis "para conveniência" e o `panel_user` herda a permission | CT-28, CT-16 |
| M63 | a Page fica fora da descoberta e a rota não existe | CT-28 |

---

## Regra R16 — isto não é settings de organização

> `RQ-18` · perfil **padrão** · técnica: **rastreio de efeito** (regressão da precedência)

```gherkin
# language: pt

  Regra: a cor da organização continua vencendo a do kit dentro do painel dela

    Cenário: [CT-29] a cor da organização vence a cor livre do kit
      Dado duas organizações com identidade visual diferente
      E que a cor livre do kit está definida como "#7c3aed"
      Quando o painel de negócio da primeira organização é renderizado
      Então a cor primária registrada é a da organização, não a do kit
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M64 | a cor do kit passa a ser registrada no `bootUsing()` e vence a da organização | CT-29 |
| M65 | o alinhamento de config escreve na chave da organização | CT-29 |

---

## Regra R17 — os READMEs documentam a feature e os TODOs foram substituídos

> `RQ-10`, `RQ-11`, `RQ-19` · perfil **mínimo** · técnica: **EP**

O kit já tem esse padrão de teste (`tests/Kit/AnexosPrivadosDocumentacaoTest.php`), e ele existe porque documentação prometida e não escrita é indistinguível de documentação escrita, para todo mundo menos o leitor.

```gherkin
# language: pt

  Regra: a tela de configurações está documentada nos dois idiomas

    Esquema do Cenário: [CT-31] cada README cita a tela e a fonte da verdade
      Quando o arquivo "<readme>" é lido
      Então ele cita a URL da tela de configurações do kit
      E ele cita a regra de precedência entre o banco e o arquivo de ambiente

      Exemplos:
        | readme       |
        | README.md    |
        | README.en.md |

    Esquema do Cenário: [CT-32] o TODO de virada para settings não sobrevive nos READMEs
      Quando o arquivo "<readme>" é lido, ignorando as linhas de citação
      Então ele não promete transformar os defaults de tabela num settings futuro

      Exemplos:
        | readme       |
        | README.md    |
        | README.en.md |
```

⚠️ **Armadilha aplicável** (`.ai/rules/testes.md`): asserção de **ausência** sobre arquivo documentado precisa filtrar comentário e citação — o README novo vai **citar** o TODO antigo para explicar o que mudou. CT-32 filtra as linhas de citação (`>`) antes de afirmar a ausência; a asserção de **presença** de CT-31 roda sobre o texto cru.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M66 | só o `README.md` é atualizado, e o `README.en.md` fica prometendo o TODO | CT-31 e CT-32 (linha `README.en.md`) |
| M67 | o TODO é apagado sem nada no lugar, e ninguém encontra a tela pela documentação | CT-31 |

---

## Regra transversal — o mecanismo, não só o resultado

> perfil **completo** (área A1) · técnica: **rastreio de efeito** sobre a ordem de boot

```gherkin
# language: pt

  Regra: o alinhamento acontece antes de a configuração global de tabela ser montada

    Cenário: [CT-30] a paginação gravada no banco chega à tabela sem ninguém alinhar à mão
      Dado que a propriedade "paginacao_padrao" do grupo "kit" está gravada como 25
      Quando uma tela com tabela do painel de administração é visitada
      Então a resposta é bem-sucedida
      E a tabela apresenta a paginação 25
```

**Por que este cenário é separado de CT-17**: CT-17 alinha a config no arranjo e prova que `configuraTable()` a lê. CT-30 **não** alinha nada — ele prova que o boot faz isso sozinho, na ordem certa. É o único cenário do conjunto que falsifica M1 e M32 pelo caminho de ponta a ponta, e é o que separa "o mapa existe" de "o mapa é usado".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M68 | `configureSettingsDoKit()` é chamado **depois** de `configuraFilamentGlobal()` no `boot()` | CT-30 |
| M69 | o alinhamento roda num `register()` e a leitura de banco falha silenciosamente | CT-30 |
| M70 | ⚠️ o alinhamento roda **duas vezes** por request (provider e middleware), dobrando a query — **sem matador**. **Lacuna declarada**: tentado `DB::listen()` para contar queries no boot, mas o boot acontece antes do arranjo do caso, e contar query de boot exigiria um `TestCase` próprio. Custo alto para um defeito de performance sem efeito funcional | ⚠️ sem matador |

---

## Checklist de Taxonomia

| Item | Cenário que mata |
|---|---|
| IDOR / autorização horizontal | **não se aplica**: a feature é um singleton de instalação, sem `{id}` de recurso e sem dono. O análogo — dado de outra organização — é CT-29 |
| Autorização exercida na ação (não só `can()`) | CT-15 (chama `save()` e afirma o não-efeito no banco) |
| Idempotência (ancorada no agregado) | CT-23 — gravar o mesmo valor duas vezes, com o oráculo em "nenhum registro de auditoria criado", que é o agregado persistido afetado |
| Concorrência | **não se aplica**: sem contador, saldo, estoque nem limite de uso. Dois administradores salvando ao mesmo tempo é último-a-escrever-vence, que é o comportamento pretendido de configuração |
| **Fronteira no ponto de entrada** (gravação) | CT-09 (paginação em 0 e 101), CT-06 (formato do hexadecimal) |
| **Criação ≠ edição ≠ uso** | é um singleton: não há criação pelo usuário. **Semeadura** (CT-11) ≠ **edição** (CT-08, CT-09) ≠ **uso** (CT-01, CT-17, CT-30). As três estão cobertas, e CT-30 é a que fecha o uso de ponta a ponta |
| **Unicidade contra si mesmo na edição** | CT-23 — gravar sem alterar deve passar sem erro e sem trilha; é a variante desta armadilha num singleton |
| **Domínio condicionado** (campo × campo) | CT-05 — o domínio válido de `cor_primaria` depende de `cor_primaria_hex` estar preenchido |
| **Estado × operação de escrita** | **não se aplica**: nenhuma entidade com ciclo de vida. Não há `status`, transição nem soft delete nesta feature |
| Ausente ≠ `null` ≠ `""` | CT-02 (linha sem registro no banco), CT-05 linha 4 e CT-06 linha 3 (string vazia), CT-26 linha 3 (não configurado). Semântica declarada: **ausente = usa o `.env`**; **`null`/vazio = a propriedade não tem valor e o consumidor cai no padrão** |
| Paginação / ordenação | CT-17 é sobre o **default de paginação** das tabelas do kit, que é o que a feature controla. Paginar a listagem da própria feature **não se aplica**: a tela é um formulário, não uma listagem |
| Timezone / DST | **não se aplica**: nenhuma propriedade é temporal e nenhum cenário depende de instante. Declarado na varredura SFDIPOT, dimensão T |
| Unicode / limite de varchar | **lacuna declarada**: tentado derivar cenário com nome de aplicação em emoji e com 300 caracteres. O `payload` é `json` (sem limite prático) e o nome vai para o `<title>` e o cabeçalho — não há regra no requisito sobre limite, e inventar um seria inventar cláusula. O que **entrou** foi normalização de caixa no hexadecimal (CT-06, linha `#7C3AED`), que tem regra |
| Unicidade + soft delete | **não se aplica**: a chave `unique(group,name)` é da tabela do vendor e não há soft delete |
| CRUD combinado | CT-12 (migrar → gravar → desfazer → migrar de novo, no mesmo cenário) |
| Mass assignment | **lacuna declarada**: `Settings::fill()` (`vendor/spatie/laravel-settings/src/Settings.php:178-185`) atribui por `__set`, que só alcança propriedade **declarada** — enviar `is_admin` no payload do Livewire não cria propriedade nem grava linha. Tentado derivar um cenário enviando chave não declarada; ele passaria por construção da linguagem, o que o torna tautológico pelo critério da própria skill |
| Upload | CT-10 (disco e visibilidade). Byte zero, extensão que mente e acima do limite: **não se aplica** — `->image()` do Filament valida o MIME e o kit não declara limite de tamanho para favicon; inventar um seria inventar cláusula |
| Precisão monetária | **não se aplica**: nenhum valor monetário |
| **Efeito colateral entregue pelo canal certo** | CT-02 e CT-04 afirmam o **canal** `configuracoes`, não "algum log" — um `Log::warning()` sem channel passaria numa asserção genérica |

---

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Camada | Arquivo | Mata |
|----|---------|-------|---------|--------|---------|------|
| CT-01 | propriedade do banco chega ao consumidor | R1 | rastreio + EP | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` | M1, M2, M3, M5, M6 |
| CT-02 | propriedade sem linha mantém o `.env` | R1 | EP | Feature | idem | M4 |
| CT-03 | tabela ausente não afeta e não avisa | R2 | EP | Feature | idem | M7, M9, M10 |
| CT-04 | falha de leitura cai para o `.env` com aviso | R2 | EP | Feature | idem | M8, M9, M11 |
| CT-05 | precedência hex → nome → padrão | R4 | tabela de decisão | Unit-em-Kit | `tests/Kit/CorPrimariaTest.php` | M16, M17, M18, M19 |
| CT-06 | formato do hexadecimal | R5 | BVA de formato | Unit-em-Kit | idem | M20, M21, M22, M23 |
| CT-07 | painel sobe com cor inválida | R5 | EP | Feature | idem | M20, M24 |
| CT-08 | gravação pelo formulário | R8 | rastreio + EP | componente | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M34, M35, M36, M37 |
| CT-09 | campo fora do domínio é recusado | R8 | EP + BVA | componente | idem | M38, M39 |
| CT-10 | upload público | R8 | EP | componente | idem | M40, M41 |
| CT-11 | migration semeia da config | R3 | rastreio | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` | M12, M13 |
| CT-12 | migration reversível | R3 | rastreio | Feature | idem | M14, M15 |
| CT-13 | `master_global` abre e grava | R6 | matriz | componente | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M28 |
| CT-14 | quem tem a permissão vê no menu | R6 | matriz | Feature | idem | M29 |
| CT-15 | quem não tem não abre nem grava | R6 | matriz | componente | idem | M25, M26, M27 |
| CT-16 | seeders entregam a permissão ao `admin` | R7 | matriz | Feature | idem | M30, M31, M33 |
| CT-17 | paginação nas bordas | R12 | BVA 3-valores | Feature | `tests/Kit/DefaultsDeTabelaTest.php` | M52 |
| CT-18 | cada interruptor liga e desliga | R12 | tabela de decisão | Feature | idem | M54, M56 |
| CT-19 | interruptores independentes | R12 | tabela de decisão | Feature | idem | M53 |
| CT-20 | telas de pé com tudo desligado | R12 | EP | Feature | idem | M55 |
| CT-21 | senha cifrada e legível | R11 | rastreio | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` | M50, M51 |
| CT-22 | trilha com valor antigo e novo | R9 | rastreio | componente | idem | M42, M43, M47 |
| CT-23 | gravar igual não gera trilha | R9 | rastreio | componente | idem | M44 |
| CT-24 | trilha sem restauração | R9 | rastreio | Feature | idem | M45, M46 |
| CT-25 | segredo fora da trilha | R10 | rastreio | componente | idem | M48 |
| CT-26 | resolução de arquivo de identidade | R13 | EP | Unit-em-Kit | `tests/Kit/IdentidadeDoKitTest.php` | M57, M58, M59 |
| CT-27 | `--custom` propaga para o settings | R14 | rastreio | Feature | `tests/Kit/CustomizadorDaInstalacaoTest.php` | M60, M61 |
| CT-28 | tela só no painel `admin` | R15 | EP | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` | M62, M63 |
| CT-29 | cor da organização vence | R16 | rastreio | Feature | `tests/Tenancy/IdentidadeVisualTest.php` | M64, M65 |
| CT-30 | boot alinha antes da config de tabela | transversal | rastreio | Feature | `tests/Kit/ConfiguracoesDoKitTest.php` | M1, M32, M68, M69 |
| CT-31 | READMEs citam a tela e a precedência | R17 | EP | Feature | `tests/Kit/ConfiguracoesDoKitDocumentacaoTest.php` | M67 |
| CT-32 | TODO substituído nos dois READMEs | R17 | EP | Feature | idem | M66 |
| CT-B01 | abas e seletor de cor funcionam no navegador | R8 | — | Browser | `tests/Browser/ConfiguracoesDoKitTest.php` | ver `05` |
| CT-B02 | erro de validação aparece na aba do campo | R8 | — | Browser | idem | ver `05` |

**Mutantes sem matador**: M49 (heurística de máscara de segredo — só há um campo de segredo nesta entrega) e M70 (alinhamento executado duas vezes por request — defeito de performance sem efeito funcional). Os dois estão declarados no corpo das regras com o que foi tentado.

---

## Revisão Adversarial

Delegada a sub-agente independente, sem acesso ao PRD, ao ADR nem a código. **Rodada 1: 5 implementações erradas que passavam em tudo, 10 oráculos fracos, 3 cenários com `Quando` múltiplo, 10 cláusulas `RQ` com cobertura insuficiente.** Todos fechados abaixo.

O achado estrutural foi um só, e ele explica quatro dos cinco: **CT-01 particionava por tipo de PHP (string, aninhada, inteiro, booleano) quando a unidade de falha é a chave de config**. Cada chave é uma linha de código independente — não existe classe de equivalência entre `mail.from.address` e `mail.mailers.smtp.host`. Com 6 das 21 propriedades exercitadas, um mapa de alinhamento que cobrisse só aquelas seis passava no conjunto inteiro, e a tela prometia 21 configurações entregando 6.

### O que mudou

| # | Achado | Fechamento |
|---|---|---|
| I-1 | mapa erra as chaves aninhadas de `mail.mailers.smtp.*` e o kit segue com `MAIL_MAILER=log` | **CT-01 passa a ter uma linha por propriedade — 21, não 6** |
| I-2 | 14 propriedades semeadas, salvas e nunca alinhadas; os três interruptores de tabela entre elas | idem CT-01; e CT-17/CT-18/CT-19 continuam arranjando a config direto **de propósito** (eles provam que `configuraTable()` lê a config), com CT-01 e CT-30 fechando o laço banco → config |
| I-3 | identidade resolvida corretamente e **nunca consumida** pelos painéis; `brandLogo` e a arte do Auth Designer continuam literais | **CT-35, novo** — renderiza os três painéis e afirma as URLs no HTML |
| I-4 | as opções da seleção de cor podem ser um array escrito à mão em vez da lista do kit | **CT-36, novo** |
| I-5 | uma linha de auditoria por salvamento com o diff inteiro dentro passa, porque nenhum cenário altera **duas** propriedades | **CT-34, novo** — a cardinalidade tinha só 0 e 1; o limite que separa as duas implementações é 2 |
| CT-11 | `expect(...)->toHaveCount(21)` é asserção de cardinalidade, e o `21` vem do PRD, não do requisito | passa a iterar as propriedades **declaradas na classe** por reflexão, afirmando que cada uma tem linha e valor igual ao da config. Sem número mágico e sem confiar no mapa |
| CT-12 | o segundo `Então` era tautológico e o matador de M15 (`add()` sem `deleteIfExists()`) estava só na prosa | ganha o `Quando` de **remigrar** |
| CT-13, CT-15 | "abre **e** grava" num só `Quando` funde as duas colunas da matriz: barrado no `abrir`, o `save()` nunca é chamado e a coluna "salvar" fica sem cenário nas três personas negativas | **CT-15 fica só com `abrir`; CT-33, novo, chama `save()` direto.** É a regra do verbo irmão: evidência de um verbo não cobre o outro |
| CT-16 | invoca os seeders à mão, e RQ-15 pede "sem passo manual" | passa pelo `DatabaseSeeder`, que é o ponto de entrada de `db:seed` e do `kit:install` |
| CT-20 | `assertOk` sozinho | ganha asserção de que os três interruptores estavam de fato desligados na tabela configurada |
| CT-24 | metade `assertOk`, metade afirmando o valor da coluna `event` — que a própria `## Fronteira com o Plano` declara detalhe de implementação | o oráculo passa a ser o **renderizado**: a ação de restauração não aparece na listagem |
| CT-26 | só `favicon` e `arte_do_login` na tabela; RQ-12 (logo) sem nenhuma linha | ganha as linhas de `logo` |
| CT-28 | registro de Page é indistinguível de implementação artesanal, e RQ-02 exige o pacote | afirma que a Page **estende** `Filament\Pages\SettingsPage` e que `getSettings()` devolve a classe de settings do kit |
| CT-08, CT-B02 | `Quando` múltiplo sem justificativa declarada | justificativa escrita (um salvamento único; e a sequência é o comportamento sob teste) |
| RQ-16 | **não aparecia em nenhuma linha do Mapa de Regras** — zero cobertura, nem nominal | entra em R1, exercitada pelas linhas de CT-01 que são os "pontos adicionais": hub, rótulos da organização e os quatro defaults de tabela |
| RQ-05 | ninguém afirmava que o nome gravado chega ao `brandName` e ao `<title>` | CT-35 |
| RQ-13 | só a partição de **fallback** era exercitada | CT-35 (arte presente e servida) |

### Achados recusados, com o motivo

| Achado | Por que não virou cenário |
|---|---|
| CT-05 e CT-06 têm oráculo em função pura | é o oráculo **certo** aqui: `CorPrimaria::paleta()` é literalmente o valor que os três painéis passam a `->colors()`, então a paleta **é** o observável, não um intermediário. CT-07 fecha o caminho até o painel no caso inválido, que é o que derruba página |
| CT-14 é quase-tautologia de framework | verdade, e por isso ele foi **rebaixado a asserção de apoio** no índice em vez de removido: continua sendo o único que mata M29, e custa um `assertSee` |
| CT-27 cobre só nome e cor de RQ-03 | não é lacuna: `aplicarSemBanco()` do `kit:install --custom` **oferece** só nome e cor, e o recorte está argumentado em `CustomizadorDaInstalacao.php:170-192`. Os outros itens de RQ-03 chegam à config por CT-01, não pelo instalador |

### Rodada 2 — a lacuna de segunda ordem

O fechamento criou cinco cenários, o que disparou a re-revisão prevista (teto: 2 rodadas). Ela achou **três implementações erradas que ainda passavam, cinco oráculos novos fracos ou vacuosos, seis redundâncias/contradições introduzidas pelo próprio fechamento e três cláusulas ainda sem falsificador**. Esta é a última rodada: o que sobra vai para lacuna declarada, não para uma rodada 3.

Duas observações que mudam como ler a lista:

1. **Os três "ainda passava" são lacunas de TESTE, não de código.** O código já estava escrito quando a rodada 2 rodou, e nos três casos ele faz o certo — o alinhamento está no `boot()` do `KitServiceProvider` (alcança comando artisan), o mapa grava a chave **sem** condicionar a valor não-nulo, e a trilha sai do listener de `SavingSettings` (alcança gravação fora da tela). O que faltava era o cenário capaz de reprovar a alternativa. É exatamente o valor de uma revisão cega: ela não sabia o que o código faz e apontou onde o conjunto não olhava.
2. **Um achado da rodada 1 foi um erro meu, e a rodada 2 o desfez.** CT-33 foi criado para separar "abrir" de "gravar" pela regra do verbo irmão. Sob a decisão de RQ-14 (**uma** permissão governa as duas), `mount()` aborta em `canAccess()` antes de `save()` existir — as duas personas de CT-33 são as mesmas que CT-15 já barra, e `canEdit()` fixo em `true` é **inobservável por qualquer cenário possível**. A regra do verbo irmão é válida em geral e foi aplicada a um par de verbos que a própria decisão de escopo fundiu.

#### Fechamento da rodada 2

| Achado | Destino |
|---|---|
| **E-1** — o alinhamento poderia estar pendurado no `bootUsing()` de um painel ou num middleware `web`, e nenhum cenário roda comando artisan. Consequência: convite e lembrete sairiam da fila/CLI com o `MAIL_MAILER` do `.env`, e a aba E-mail ficaria decorativa fora do navegador | **CT-37, novo** — afirma **onde** o alinhamento está ligado, por varredura dos providers. É uma asserção estrutural, e está declarada como tal: o falsificador comportamental exigiria um processo separado (`Process::run('php artisan …')`), lento e frágil na suíte. O kit já usa esse padrão de teste em `CacheDeViewsNoDockerTest` e `QualidadeDeCodigoTest` |
| **E-2** — `if (! is_null($valor))` no alinhamento é a guarda que qualquer dev escreve depois de pensar em M4, e as 21 linhas de CT-01 usam valor **não-nulo**. Consequência: escolher "padrão" na cor, limpar a logo ou apagar o usuário de SMTP grava, gera trilha, e **não tem efeito** — não há caminho de volta ao default pela tela | **CT-38, novo** — três linhas de propriedade limpada (cor, logo, usuário de SMTP), afirmando que a chave de config vira nula |
| **E-3** — a trilha poderia estar dentro do `save()` da Page: CT-22, CT-23, CT-24, CT-25 e CT-34 gravam **todos pela tela**, e o `Então` de CT-27 não menciona auditoria | **CT-39, novo** — grava pela API do settings, fora de qualquer tela, e afirma a trilha |
| **CT-33** não mata mutante nenhum sob a decisão de uma permissão | **removido.** M27 e M73 passam a **lacuna declarada**: `canEdit()` só é observável separadamente se um dia houver duas permissões, e aí o cenário nasce com a decisão |
| **CT-34** conta registros em termos absolutos, sem declarar que a tabela começa vazia | o `Dado` passa a arranjar pelos valores **semeados pela migration** (o migrator escreve direto no repositório, não por `Settings::save()`, logo não dispara o listener), e o `Então` conta apenas os registros com a etiqueta `configuracoes-do-kit` |
| **CT-35** não separa `brandName` de `<title>`: com a marca literal no provider e o alinhamento funcionando, o HTML contém **as duas** coisas | o `Dado` passa a exigir que o nome gravado **difira** do `APP_NAME` do ambiente, e o `Então` ganha `assertDontSee` do valor do ambiente. É o mesmo par discriminante que CT-35b já usava, e cuja ausência aqui a própria revisão apontou como contradição interna do arquivo |
| **CT-35b** não tem `Dado` — Gherkin só compartilha arranjo por `Contexto:` | ganha `Contexto:` explícito, compartilhado com CT-35 |
| **CT-36** afirma o conjunto de opções: derivar a lista esperada da constante é tautologia, fixar `16` é número mágico — e o defeito real é o **formato da chave** (uma opção com chave em slug grava `emerald` e `CorPrimaria` cai em paleta vazia, em silêncio) | **oráculo reescrito para comportamento**: escolher uma cor pelo formulário, salvar, e afirmar que `CorPrimaria::paleta()` devolve a paleta daquela cor. Mata M79 e o mutante de formato de chave, sem contagem |
| **CT-11** reescrito passou a medir o ambiente: comparar o gravado com `config()` é auto-referencial, e para paginação (10), os três booleanos (`true`) e o hub (`false`) uma migration com literais produz exatamente o valor que a config já tem | o cenário passa a **arranjar valores discriminantes na config, apagar o grupo e rodar o `up()` da migration**, afirmando o que foi semeado. Sem isso M12 não tinha matador — a reescrita da rodada 1 tirou o número mágico e, com ele, a única asserção que podia falhar |
| **CT-20** perdeu o que o tornava único quando ganhou a asserção dos interruptores | asserção revertida. CT-20 volta a ser só a fumaça das telas de vendor (M55); o oráculo dos interruptores é de CT-18 e CT-19 |
| **RQ-11** — CT-32 afirma a **ausência** da promessa, e um README que apaga a linha da densidade em silêncio passa. É o mutante M67 sem matador | **CT-32 ganha uma asserção de presença**: os dois READMEs precisam **dizer** que densidade de tabela não existe no Filament 5. Presença é falsificável; ausência não distinguia apagar de explicar |
| **RQ-12** — a tabela "O que mudou" prometeu linhas de `logo` em CT-26 e elas não foram escritas; e CT-26 tem dois comportamentos opostos para "ausente no disco" (favicon → nulo, arte → padrão do kit) | linhas escritas abaixo, com a regra explícita: **logo e favicon devolvem nulo** (o Filament cai no brand em texto e no ícone dele); **só a arte tem fallback**, porque `->media()` do Auth Designer com nulo deixaria a tela de login sem imagem |
| **RQ-16** — "pontos adicionais de valor identificados na análise" foi marcado como fechado apontando linhas de CT-01 que existem por RQ-03/RQ-09; nenhuma delas pode ficar vermelha *por causa* de RQ-16 | **passa a lacuna declarada.** A cláusula não é falsificável por teste: ela julga o *julgamento* de quem analisou o kit. O que dá para verificar — que hub, rótulos e defaults de tabela existem e chegam à config — já está em CT-01, e está registrado ali. Marcar como fechado era relabelagem de cobertura, e a revisão está certa em recusar |
| CT-01 absorveu asserções de CT-21 (`mail_password`) e de CT-18 (os três interruptores) | redundância aceita e registrada: é o preço de a matriz ser completa. M51 e M56 continuam atribuídos aos cenários originais, que os matam por outro caminho |
| CT-34 e CT-35 dependiam de estado inicial do arnês sem declarar | declarado nos dois `Dado` |

#### Cenários da rodada 2

```gherkin
# language: pt

  Regra: o alinhamento vale para todo processo, não só para o request HTTP

    Cenário: [CT-37] o alinhamento está ligado no provider da aplicação, não num painel
      Quando o código dos provedores do projeto é lido
      Então o provider da aplicação chama o alinhamento das configurações
      E nenhum provedor de painel o chama
      E nenhum middleware do projeto o chama

  Regra: limpar uma propriedade devolve o consumidor ao padrão

    Esquema do Cenário: [CT-38] propriedade limpada zera a chave de configuração
      Dado que a chave de configuração "<chave>" vale "<antes>"
      E que a propriedade "<propriedade>" do grupo "kit" está gravada como vazia
      Quando o kit alinha a configuração do processo
      Então a chave de configuração "<chave>" está vazia

      Exemplos:
        | propriedade    | chave                      | antes        | # o que o usuário fez |
        | cor_primaria   | kit.cor_primaria           | Blue         | escolheu "padrão"     |
        | logo           | kit.identidade.logo        | kit/logo.png | removeu a logo        |
        | mail_username  | mail.mailers.smtp.username | usuario      | apagou o usuário      |

  Regra: a trilha registra a alteração, não o clique

    Cenário: [CT-39] gravação fora de qualquer tela também deixa trilha
      Dado que o nome da aplicação gravado é "Antes"
      Quando o nome "Depois" é gravado pela API de configurações, sem passar pela tela
      Então existe um registro de auditoria com "nome_da_aplicacao" de "Antes" para "Depois"
```

#### Linhas de `logo` acrescentadas a CT-26

```gherkin
      Exemplos:
        | chave   | valor         | existe_no_disco | resultado                    | # partição       |
        | logo    | kit/logo.png  | kit/logo.png    | URL pública de kit/logo.png  | presente         |
        | logo    | kit/logo.png  |                 | nulo                         | ausente no disco |
```

**A regra que a revisão cobrou por escrito**: `logo` e `favicon` devolvem **nulo** quando não há arquivo utilizável — o Filament cai no brand em texto e no ícone próprio. **Só `arte_do_login` tem fallback**, e o motivo é assimétrico de propósito: `->media()` do Auth Designer recebendo nulo deixaria a tela de autenticação sem imagem, que é regressão visível, não default.

#### Mutantes da rodada 2

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M80 | o alinhamento é registrado no `bootUsing()` de um painel ou num middleware `web`, e comando/fila/scheduler seguem com o `.env` — **origem: revisão adversarial rodada 2** | CT-37 |
| M81 | `if (! is_null($valor))` no alinhamento: não há caminho de volta ao default pela tela — **origem: revisão adversarial rodada 2** | CT-38 |
| M82 | a trilha é escrita no `save()` da Page e toda gravação fora da tela fica sem rastro — **origem: revisão adversarial rodada 2** | CT-39 |
| M83 | as opções do campo de cor usam chave em slug (`emerald`) e `CorPrimaria` cai em paleta vazia, em silêncio — **origem: revisão adversarial rodada 2** | CT-36 (oráculo reescrito) |
| M84 | a migration semeia com literais que **coincidem** com o valor de fábrica da config, e o defeito fica invisível — **origem: revisão adversarial rodada 2** | CT-11 (arranjo discriminante) |
| M85 | o README apaga a linha da densidade em silêncio, como se tivesse sido entregue — **origem: revisão adversarial rodada 2** | CT-32 (asserção de presença) |

**Totais finais**: **41 cenários** (CT-33 removido; CT-37, CT-38, CT-39 acrescentados) · 17 regras · **85 mutantes previstos** · **5 sem matador, todos declarados**: M27 e M73 (`canEdit()` inobservável com uma permissão só), M49 (heurística de máscara de segredo — só há um campo de segredo), M70 (alinhamento duplicado, defeito de performance sem efeito funcional) e a cláusula **RQ-16**, que não é falsificável por teste.

---

## Cenários acrescentados e reescritos no fechamento

### CT-01 (reescrito) — uma linha por propriedade

```gherkin
# language: pt

    Esquema do Cenário: [CT-01] cada propriedade gravada chega à sua chave de configuração
      Dado que a propriedade "<propriedade>" do grupo "kit" está gravada com "<gravado>"
      Quando o kit alinha a configuração do processo
      Então a chave de configuração "<chave>" vale "<gravado>", com o mesmo tipo

      Exemplos:
        | propriedade              | gravado                | chave                             |
        | nome_da_aplicacao        | Meu Projeto            | app.name                          |
        | cor_primaria             | Emerald                | kit.cor_primaria                  |
        | cor_primaria_hex         | #7c3aed                | kit.cor_primaria_hex              |
        | logo                     | kit/logo.png           | kit.identidade.logo               |
        | favicon                  | kit/favicon.png        | kit.identidade.favicon            |
        | arte_do_login            | kit/arte.svg           | kit.identidade.arte_do_login      |
        | mail_mailer              | smtp                   | mail.default                      |
        | mail_host               | smtp.exemplo.test      | mail.mailers.smtp.host            |
        | mail_port                | 587                    | mail.mailers.smtp.port            |
        | mail_scheme              | tls                    | mail.mailers.smtp.scheme          |
        | mail_username            | usuario@exemplo.test   | mail.mailers.smtp.username        |
        | mail_password            | senha-do-smtp          | mail.mailers.smtp.password        |
        | mail_from_address        | contato@exemplo.test   | mail.from.address                 |
        | mail_from_name           | Remetente              | mail.from.name                    |
        | paginacao_padrao         | 25                     | kit.tabelas.paginacao             |
        | tabela_listrada          | false                  | kit.tabelas.listrada              |
        | persistir_filtros        | false                  | kit.tabelas.persistir_filtros     |
        | colunas_redimensionaveis | false                  | kit.tabelas.colunas_redimensionaveis |
        | hub_de_navegacao         | true                   | kit.hub                           |
        | rotulo_da_organizacao    | Empresa                | kit.tenancy.label                 |
        | rotulo_das_organizacoes  | Empresas               | kit.tenancy.label_plural          |
```

**Toda linha usa valor que difere do que o ambiente já entrega**: `mail.default` é `log` no `phpunit.xml`, então `smtp` discrimina; os três booleanos de tabela nascem `true` na config, então `false` discrimina; `hub_de_navegacao` nasce `false`, então `true` discrimina; `paginacao_padrao` é `10` hoje, então `25` discrimina. Um alinhamento que não faça nada reprova em **todas** as 21 linhas.

**"com o mesmo tipo"** no `Então` é o que mata M5 e M6: `"25"` e `"true"` como string satisfazem uma comparação frouxa e quebram `defaultPaginationPageOption()` e todo `===`.

> Um `Esquema do Cenário` conta como **1 cenário**, não 21 — é a forma canônica de expressar a matriz dentro do teto do perfil.

#### Mutante acrescentado

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M71 | o mapa escreve `mail.host`/`mail.port`/`mail.username` (chaves que não existem) em vez de `mail.mailers.smtp.*`, e o kit segue com o mailer `log` sem erro nenhum — **origem: revisão adversarial** | CT-01 (linhas de `mail_*`) |
| M72 | o mapa cobre só as propriedades de identidade e ignora as de tabela e de kit — **origem: revisão adversarial** | CT-01 (linhas de `kit.tabelas.*` e `kit.hub`) |

### CT-33 (novo) — a coluna "salvar" da matriz, por chamada própria

> R6 · fecha a fusão de `Quando` apontada na revisão

```gherkin
# language: pt

    Esquema do Cenário: [CT-33] quem não tem a permissão não grava, mesmo chamando a gravação direto
      Dado "<persona>" autenticado no painel de administração
      E que o nome da aplicação gravado é "Antes"
      Quando a gravação da tela é chamada com o nome "Invadido"
      Então a propriedade "nome_da_aplicacao" do grupo "kit" continua valendo "Antes"

      Exemplos:
        | persona                          |
        | o usuário comum do negócio       |
        | o administrador sem a permissão  |
```

CT-15 fica com **abrir** (403); este fica com **gravar**. `SettingsPage::save()` consulta `canEdit()` (`vendor/filament/spatie-laravel-settings-plugin/src/Pages/SettingsPage.php:64`), não `canAccess()` — são dois métodos e duas barreiras, e evidência de um não cobre o outro.

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M73 | `canEdit()` devolve `true` fixo — **origem: revisão adversarial** | CT-33 |

### CT-34 (novo) — a cardinalidade da trilha

> R9 · fecha I-5

```gherkin
# language: pt

    Cenário: [CT-34] duas propriedades alteradas geram duas linhas, cada uma com a sua
      Dado o administrador da aplicação autenticado no painel de administração
      E que o nome gravado é "Antes" e a paginação gravada é 10
      Quando ele grava o nome "Depois" e a paginação 25 no mesmo salvamento
      Então existem exatamente dois registros de auditoria
      E o registro do nome não menciona a paginação
      E o registro da paginação não menciona o nome
```

A dimensão "quantas propriedades mudaram" tinha só **0** (CT-23) e **1** (CT-22) — e com cardinalidade 1 as duas implementações são indistinguíveis. **2 é o limite** que as separa.

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M74 | uma linha por salvamento, com o diff inteiro dentro — **origem: revisão adversarial** | CT-34 |
| M75 | uma linha por propriedade, mas incluindo as **não** alteradas nos valores — **origem: revisão adversarial** | CT-34 |

### CT-35 (novo) — a identidade gravada chega ao HTML dos três painéis

> R13, e também RQ-04, RQ-05, RQ-12, RQ-13 · fecha I-3

```gherkin
# language: pt

  Regra: o nome e os arquivos de identidade gravados aparecem nas telas dos painéis

    Esquema do Cenário: [CT-35] o painel serve o nome, a logo, o favicon e a arte gravados
      Dado que o nome, a logo, o favicon e a arte do login estão gravados
      E que os três arquivos existem no disco público
      Quando a tela inicial do painel "<painel>" é visitada
      Então a resposta contém o nome gravado
      E a resposta contém a URL pública da logo
      E a resposta contém a URL pública do favicon

      Exemplos:
        | painel |
        | admin  |
        | app    |
        | infra  |

    Esquema do Cenário: [CT-35b] a tela de login serve a arte gravada
      Quando a tela de login do painel "<painel>" é visitada
      Então a resposta contém a URL pública da arte gravada
      E a resposta não contém o caminho da arte padrão do kit

      Exemplos:
        | painel |
        | admin  |
        | app    |
        | infra  |
```

Este é o cenário que a revisão adversarial cobrava e que não existia: CT-26 tem o oráculo no **retorno** do resolvedor, e uma implementação em que a classe de resolução está perfeita e **nenhum painel a consome** passava no conjunto inteiro. O `assertDontSee` do caminho padrão é a asserção discriminante — sem ele, uma resposta que sirva as duas coisas passaria.

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M76 | `IdentidadeDoKit` correta e nenhum painel a consome (`brandLogo` ausente, arte literal) — **origem: revisão adversarial** | CT-35, CT-35b |
| M77 | só o favicon é ligado; logo e arte continuam literais — **origem: revisão adversarial** | CT-35 (logo), CT-35b (arte) |
| M78 | `brandName` continua escalar e o nome gravado não aparece — **origem: revisão adversarial** | CT-35 |

### CT-36 (novo) — a seleção de cor oferece a lista do kit

> R4 / R8 · fecha I-4 e RQ-06

```gherkin
# language: pt

    Cenário: [CT-36] o campo de seleção de cor oferece exatamente as cores do kit
      Dado o administrador da aplicação autenticado no painel de administração
      Quando o formulário de configurações é montado
      Então as opções do campo de cor são as 16 cores da lista do kit, mais a opção de padrão
```

A lista do kit é `App\Support\CustomizadorDaInstalacao::CORES`, e `tests/Kit/CorPrimariaTest.php` já afirma que **toda** entrada dela existe em `Filament\Support\Colors\Color`. As duas asserções em cadeia é o que entrega RQ-06 ("use o Enum Color como opção de seleção") sem oferecer as constantes que não são cor (`WCAG_AA_TEXT` e os neutros), que é a razão da lista ser fechada.

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| M79 | as opções são um array escrito à mão com as cinco cores "que alguém usa" — **origem: revisão adversarial** | CT-36 |

### Índice dos acrescentados

| ID | Cenário | Regra | Camada | Arquivo | Mata |
|----|---------|-------|--------|---------|------|
| CT-33 | gravação recusada por chamada própria | R6 | componente | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M73 |
| CT-34 | duas propriedades, duas linhas de trilha | R9 | componente | `tests/Kit/ConfiguracoesDoKitTest.php` | M74, M75 |
| CT-35 | identidade no HTML dos três painéis | R13 | Feature | `tests/Kit/IdentidadeDoKitTest.php` | M76, M77, M78 |
| CT-35b | arte gravada nas telas de login | R13 | Feature | idem | M76, M77 |
| CT-36 | opções do campo de cor | R4 | componente | `tests/Kit/ConfiguracoesDoKitTelaTest.php` | M79 |

**Totais após o fechamento**: 39 cenários · 17 regras · 79 mutantes previstos · 2 sem matador (M49, M70 — declarados).

---

## Comandos

```bash
# backend — a rule do projeto proíbe --parallel junto com browser, e sem PCOV o --tia não termina
php artisan test --testsuite=Unit,Feature,Kit,Tenancy

# só esta feature, durante a implementação
php artisan test --compact --filter=ConfiguracoesDoKit
php artisan test --compact --filter='CorPrimaria|DefaultsDeTabela|IdentidadeDoKit'

# navegador — em série, com build e cache de view embutidos
composer test:browser

# fechamento do ciclo (exige PCOV ou XDEBUG_MODE=coverage)
vendor/bin/pest --mutate --path=app/Settings --path=app/Support/CorPrimaria.php --path=app/Listeners
```
