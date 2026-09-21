# Decisões Arquiteturais — Pacotes Laravel para o ecossistema de IA

## ADR-01: `laravel/pao` — **já instalado**; a entrega é documentação, não instalação

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-05, RQ-01, RQ-03

### Contexto

O requisito pede "instalar". **Ele já está instalado**, e desde o começo do projeto:

- `composer.json:88` → `"laravel/pao": "^1.0.6"` em `require-dev`
- `composer.lock` → **v1.1.4**
- `git log -S'laravel/pao' -- composer.json` → **um único commit**: `1eded2b` (o esqueleto do kit)

A documentação do kit o descreve como *"ferramentas de desenvolvimento do Laravel"*
(`docs/pt/referencia/pacotes-instalados.md:124` e o espelho em `en`) — descrição genérica que não
diz o que ele faz e, pior, **não prepara ninguém para ver a suíte cuspir uma linha de JSON**.

### O que ele faz, medido (não lido do README)

O README diz só *"hooks … automatically through Composer's autoloader"*. O mecanismo está no código:

- Gancho: `autoload.files` → `vendor/laravel/pao/src/Autoload.php`, em **todo** processo PHP
- Detecção: `vendor/laravel/agent-detector/src/AgentDetector.php` — `AI_AGENT`, mais a **presença**
  (não o valor) de 19 variáveis (`CLAUDECODE`, `CURSOR_AGENT`, `GEMINI_CLI`, `CODEX_*`, …), mais
  `file_exists('/opt/.devin')`
- Sob agente: Pest/PHPUnit/Paratest/PHPStan/Rector emitem **uma linha de JSON**; o Artisan perde
  ANSI e decoração

Duas variáveis **não documentadas no README**, confirmadas no código e por execução:
`PAO_DISABLE=1` (desliga tudo) e `PAO_FORCE=1` (liga sem agente). Lidas de **`$_SERVER`**, não de
`getenv()` — um `.env` do Laravel **não** serve.

### Decisão

**Manter**, e entregar o que falta: **documentação honesta** e o alinhamento da constraint que o
pacote já impõe na prática (ADR-04).

### Alternativas Consideradas

1. **Remover** — descartada. O ganho é real e mensurável: 1 linha de JSON estruturado contra ~25
   linhas de ANSI por execução, com `file`/`line`/`message` já extraídos. Numa suíte de 2.614
   testes, é a diferença entre um agente **ler** o resultado e um agente afogar o contexto.
2. **Deixar como está** — descartada: é exatamente o estado que fez esta wiki nascer com a
   premissa errada de que o pacote não existia no kit.

### Consequências

- **Positivas**: quem instalar o kit entende o que o pacote faz e como desligá-lo
- **Negativas**: nenhuma — a entrega é texto
- **Riscos**: nenhum medido. Ver ADR-02, que é onde o risco **poderia** estar e não está

### Referências

- `composer.json:'laravel/pao':88`
- `docs/pt/referencia/pacotes-instalados.md:'laravel/pao':124`
- `vendor/laravel/pao/src/Laravel/ServiceProvider.php`

---

## ADR-02: O `pao` **deve** viajar no `create-project` — é o caso oposto ao do Blueprint

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-02, RQ-05

### Contexto

`composer create-project` instala `require-dev` **por default**, então tudo que está lá viaja para
quem instala o kit. `.ai/rules/general.md` registra o caso em que isso foi desastroso
(`filament/blueprint`), com guarda em `tests/Kit/BlueprintForaDoPacoteTest.php`. A pergunta
legítima é se o `pao` cai na mesma armadilha.

### Decisão

**Não cai, e deve viajar.** Permanece em `require-dev`, sem guarda.

### A distinção que importa

O veto do `general.md` é sobre **repositório privado**, não sobre `require-dev`:

| | `filament/blueprint` | `laravel/pao` |
|---|---|---|
| Repositório | privado, com licença | Packagist **público** |
| Resolução sem licença | **403 → kit não-instalável** | sempre resolve |
| Licença | comercial | **MIT** |
| Peso transitivo | — | 1 pacote (`agent-detector`), **que o Pint já traz** |
| Produção | — | nenhum impacto |

E é **coerente com o posicionamento do kit**: o `require-dev` já carrega `laravel/boost`, e o
repositório tem `.ai/rules`, `CLAUDE.md` e skills. Este kit é explicitamente desenhado para ser
trabalhado por agente; o `pao` entrega ao projeto do usuário exatamente o que entrega aqui.

### Por que os testes do kit não quebram — e a causa é estrutural

Havia risco real: `grep -rn "expectsOutput"` acha **15 asserções sobre texto de saída**, em 4
arquivos, e uma delas (`tests/Kit/KitInfoTest.php`) casa substring na saída do `kit:info` —
justamente o comando cujos pontinhos o `pao` colapsa.

Não quebram por um portão no provider:

```php
if ($this->app->runningUnitTests()) { return; }
```

`runningUnitTests()` é `env === 'testing'`, e `phpunit.xml` fixa `APP_ENV=testing`. O `pao`
transforma a saída do **runner**, nunca a saída que uma asserção interna inspeciona.

**Medido com o agente ativo**: os 4 arquivos, **88/88 verdes**, 436 asserções. E `--parallel`
também.

### Por que o CI não muda

`.github/workflows/ci.yml` **não define nenhuma** das 19 variáveis. GitHub Actions define `CI=true`,
que não está na lista. Nenhum passo faz parse de saída de teste — o único parse em todos os
workflows é `curl -w '%{http_code}'`, que o `pao` não toca. E os exit codes são preservados.

### Consequências

- **Positivas**: o agente de quem usa o kit ganha saída estruturada, sem configuração
- **Negativas**: mais um pacote no `vendor/` de quem instala — público, MIT, 1 transitivo já presente
- **Riscos**: o portão `runningUnitTests()` é do **vendor**. Se um upgrade do `pao` o remover, as 15
  asserções de saída podem quebrar **só sob agente**. Registrado como débito no `03`; não vale
  guarda hoje, porque a guarda teria de rodar sob agente para significar algo

---

## ADR-03: `laravel/vet` — **adiado até a 1.0**

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-04, RQ-01

### Contexto

O `vet` mostra o diff do código das dependências **antes** do `composer update` gravar no vendor.
Para um kit com ~60 dependências diretas de fornecedores pequenos de Filament, o valor é alto.

Mas ele é um **plugin do Composer** (`extra.class`), e o que ele faz quando acha pacote não
confiado é, por desenho declarado, **abortar o build**:

```php
throw new ScriptExecutionException('Vet found packages that your trust file does not cover.', …);
```

### A armadilha específica deste kit

O kit é distribuído por `composer create-project`. O plugin é **inerte sem `vet.json`**
(`Gate::arguments()` devolve `[]` quando `! hasTrustFile()`). Mas **se o `vet.json` viajar no
dist**, a sequência num `create-project` de terceiro é:

1. `PRE_OPERATIONS_EXEC` → gate pulado (projeto zerado)
2. install roda
3. `POST_INSTALL_CMD` → `vet` roda de verdade, contra a lista de confiança **do mantenedor**
4. qualquer divergência de versão **ou de bytes** → saída não-zero → **`create-project` morre**
5. `post-create-project-cmd` → `kit:install` **nunca roda**

`export-ignore` defende — mas `composer create-project --prefer-source` **fura a defesa**.

### Decisão

**Não instalar agora.** Registrar a análise e reavaliar na 1.0.

### Alternativas Consideradas

1. **`require-dev` + `allow-plugins` + `/vet.json export-ignore` + guarda de teste** — tecnicamente
   seguro pelo mecanismo lido, mas ver "Consequências"
2. **Padrão `vet:on` / `vet:off`**, como o `bp:on`/`bp:off` do Blueprint — zero exposição para quem
   instala. É a alternativa a retomar quando a 1.0 sair, se o adiamento incomodar antes disso

### O que decidiu o adiamento

| Fato | Medida |
|---|---|
| Maturidade | **v0.1.2**, e **3 releases em 3 dias** (v0.1.0 e v0.1.1 em 2026-09-11 e 14) |
| Estabilidade de API | o `extra.class` **mudou** de `App\Composer\Plugin` para `Laravel\Vet\Composer\Plugin` entre v0.1.0 e v0.1.1 — o namespace do plugin quebrou em 3 dias |
| Declaração do autor | README: *"Laravel Vet is in beta. The behaviour can change before the first stable release."* |
| Adoção | ~1.033 instalações |
| Peso | binário opaco de **2,78 MB** no `vendor/` de todos |
| Custo de fluxo | com `vet.json` commitado, **toda PR do Dependabot fica vermelha** até um humano rodar o vet |
| `allow-plugins` | o kit tem mapa **explícito**; sem a entrada, o CI (`--no-interaction`) **pula o plugin em silêncio** — dependência sem proteção |

**O argumento decisivo**: o custo de esperar é **zero**; o custo de um `create-project` quebrado é
o kit inteiro. E `composer audit` (nativo) já cobre parte do valor hoje.

### Consequências

- **Positivas**: nenhuma exposição nova; nenhuma regressão possível
- **Negativas**: o valor de auditoria fica na mesa até a 1.0
- **Riscos**: esquecer de reavaliar. Mitigação: débito registrado no `03` com o gatilho explícito
  (saída da 1.0)

---

## ADR-04: `nunomaduro/collision` — a constraint declarada não descreve o instalável

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-02

### Contexto

`composer.json:92` declara `"nunomaduro/collision": "^8.6"`, que sozinha admitiria a 8.6.0. Mas o
`pao` declara `conflict: "nunomaduro/collision": "<8.9.3"`, e o resolvedor **subiu o piso em
silêncio**: o lock fixa **v8.9.5**.

### Decisão

Alinhar para `^8.9.3` — a constraint passa a declarar o que já é verdade.

### Alternativas Consideradas

1. **Deixar como está** — descartada: `composer.json` que não descreve o instalável é documentação
   falsa, e quem ler `^8.6` conclui que 8.6 funciona

### Consequências

- **Positivas**: o manifesto volta a dizer a verdade
- **Negativas**: nenhuma — não muda o que é instalado
- **Riscos**: nenhum

---

## ADR-05: `laravel/moat` — documentado como ferramenta do mantenedor, não como dependência

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: RQ-06

### Contexto

O requisito agrupa o `moat` com os outros dois sob "instalar novos pacotes do laravel". Ele **não é
pacote PHP**: é uma CLI escrita em **Rust**, instalada por **Homebrew**, que faz auditoria
**read-only** de configuração de segurança do GitHub (2FA, branch protection, secret scanning,
permissões de workflow). Não entra em `composer.json`, e **não tem relação com IA**.

### Decisão

Documentar como **ferramenta opcional do mantenedor**, na documentação do kit. Não é dependência e
não entra no CI. **Decidido com o usuário em 2026-09-21.**

### Alternativas Consideradas

1. **Integrar ao CI** — descartada pelo usuário: exigiria `GITHUB_TOKEN` com escopo de organização,
   toolchain Rust no CI e um job que pode falhar por permissão. Superfície nova que serve ao
   **repositório do kit**, não a quem instala o kit
2. **Tirar de escopo** — descartada pelo usuário: a análise tem valor e o custo de registrá-la é
   uma seção de documentação

### Consequências

- **Positivas**: RQ-06 atendido com custo quase zero; a análise fica registrada
- **Negativas**: quem quiser usar precisa instalar por fora, e o Homebrew não é padrão no Windows —
  a documentação precisa dizer isso
- **Riscos**: nenhum para o kit

---

## ADR-06: A constraint de PHP do kit já está desatualizada

**Status**: Aceita · **Data**: 2026-09-21 · **Atende**: — (achado lateral, aprovado pelo usuário)

### Contexto

`composer.json` declara `"php": "^8.3"`. O `composer.lock` publicado **já** trava **29 pacotes de
produção** exigindo PHP ≥ 8.4 (`symfony/console`, `symfony/clock`, `spatie/laravel-activitylog`, …).
O piso real do kit é **8.4** desde antes desta wiki.

Quem tentar instalar em PHP 8.3 falha na **resolução**, com a mensagem confusa de conflito de
dependência transitiva — não com "este kit exige 8.4".

### Decisão

Corrigir para `^8.4`, em **commit separado** e com a evidência. **Aprovado pelo usuário em
2026-09-21.**

### Alternativas Consideradas

1. **Abrir issue à parte** — descartada pelo usuário: o defeito já está no ar e a correção é de uma
   linha
2. **Tratar como pré-requisito do `vet`** — descartada, e a distinção importa: o `vet` foi **adiado**
   (ADR-03). Se a correção dependesse dele, a constraint continuaria mentindo

### Consequências

- **Positivas**: erro de instalação passa a ser legível
- **Negativas**: nenhuma — não exclui ninguém que hoje consiga instalar
- **Riscos**: nenhum medido. Um `composer validate` confirma
