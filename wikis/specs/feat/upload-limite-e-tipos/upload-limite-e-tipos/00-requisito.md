# Requisito — Limite e tipos de upload

## Fonte

- **Origem**: `.claude/requisitos/w5-upload-limite-e-tipos.txt` (texto do usuário, colado no arquivo)
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> no upload, pode subir arquivos de ate 10mb e não aceitam SVG. O restante pode ser qualquer tipo de image. O tamanho maximo de upload deve ficar na config no kit e documentado para caso o usuário queria aumentar ou diminuir de forma facil ao instalar o nosso kit

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Todo upload do kit aceita arquivo de até 10 MB | "no upload, pode subir arquivos de ate 10mb" | funcional |
| RQ-02 | Arquivo acima do teto é recusado | "pode subir arquivos de ate 10mb" (o "até" é a fronteira; sem recusa acima dela a cláusula não é verificável) | funcional |
| RQ-03 | Upload recusa SVG | "não aceitam SVG" | restrição (segurança) |
| RQ-04 | Nos campos de imagem, qualquer outro tipo de imagem é aceito | "O restante pode ser qualquer tipo de image" | funcional |
| RQ-05 | O tamanho máximo vive na config do kit | "O tamanho maximo de upload deve ficar na config no kit" | restrição |
| RQ-06 | O tamanho máximo é documentado para quem instala aumentar ou diminuir com facilidade | "e documentado para caso o usuário queria aumentar ou diminuir de forma facil ao instalar o nosso kit" | não-funcional |

RQ-01 e RQ-02 são separadas de propósito: são as duas metades do valor limite, e a matriz de
rastreabilidade marcaria ✅ com só a primeira implementada — um campo sem `maxSize()` já atende
"aceita 10 MB" e falha em tudo o mais.

RQ-03 e RQ-04 também: recusar SVG e aceitar o resto podem falhar separadamente. Uma allow-list
curta demais (`['image/png']`) atende RQ-03 e viola RQ-04; o `->image()` de fábrica atende RQ-04
e viola RQ-03, que é exatamente o estado de hoje.

## Superfícies de upload do kit (varredura, não suposição)

"no upload" não nomeia campo. A varredura (`grep -rn 'FileUpload' app/`) achou **cinco campos em
três arquivos**, e o estado de cada um antes desta feature:

| # | Campo | Arquivo | Teto hoje | SVG hoje |
|---|---|---|---|---|
| 1 | `logo` | `app/Filament/Admin/Pages/ConfiguracoesDoKit.php:235` (helper `arquivo()`) | **nenhum** | **aceito** (`->image()` → `mimetypes:image/*`) |
| 2 | `favicon` | idem, `:238` | **nenhum** | **aceito** |
| 3 | `arte_do_login` | idem, `:241` | **nenhum** | **aceito** |
| 4 | `logo` da organização | `app/Filament/Admin/Resources/Tenants/Schemas/TenantForm.php:119` | `1024` **fixo no código** (1 MB) | já recusado (`acceptedFileTypes(['image/png','image/jpeg','image/webp'])`) |
| 5 | `anexos` do Projeto | `app/Filament/App/Resources/Projetos/ProjetoResource.php:126` | `10 * 1024` **fixo no código** | **aceito** (sem `acceptedFileTypes`) |

Os campos 4 e 5 são o que mostra por que a varredura importa: os dois **já têm** teto, cravado no
código — 1 MB num, 10 MB no outro, nenhum dos dois explicado por comentário nenhum. É exatamente o
que RQ-05 proíbe. Corrigir só os três campos da tela de configurações deixaria três números em
três arquivos, e só um deles documentado.

> **Correção da revisão pós-escrita (step 5).** A primeira versão desta tabela dizia "nenhum" no
> campo 4. Errado: `->maxSize(1024)` estava lá, na linha 144, fora do bloco de comentários que
> explicava os outros encadeamentos — e por isso não apareceu no `grep` por `maxSize` que abriu a
> varredura. A conclusão ("aquele campo precisa de trabalho") continuou certa **pelo motivo
> errado**, que é o padrão que `.ai/rules/specs.md` manda desconfiar.

## Ambiguidades e Perguntas Abertas

- **RQ-03 no campo `anexos`** — "não aceitam SVG" está numa frase cuja continuação fala de
  imagem ("o restante pode ser qualquer tipo de image"). O `anexos` é campo de **documento**
  (PDF, planilha), não de imagem.
  - **Assumido**: RQ-03 vale ali também. A recusa é de **um formato**, não de uma família:
    bloquear SVG num campo de documento não impede nenhum documento, e o SVG é servido pela
    própria aplicação (mesmo origin), que é a razão da cláusula existir.
  - **Se negado**: remover a regra de recusa do `anexos` (passo 4 do PRD) e o par CT-07/CT-08.
    O teto de RQ-01/RQ-02/RQ-05 ali continua valendo em qualquer hipótese.

- **RQ-04 no campo `logo` da organização** — aquele campo já tem allow-list de três tipos
  (`png`, `jpeg`, `webp`), mais estreita que "qualquer tipo de image".
  - **Assumido**: não alargar. A allow-list de lá é decisão registrada num comentário do próprio
    arquivo, com justificativa de segurança, e RQ-04 não pede para afrouxar nada — pede para não
    apertar além do SVG. Estreitar é o que a cláusula proíbe; **já estar** estreito por decisão
    anterior é outra questão.
  - **Se negado**: trocar aquele `acceptedFileTypes()` pelo mesmo par `->image()` + `rule('image')`
    dos campos 1-3.

- **RQ-01 no campo `logo` da organização** — aquele campo tinha `->maxSize(1024)`, 1 MB, **sem
  nenhum comentário justificando o número** (todos os comentários daquele bloco falam de tipo,
  disco e visibilidade).
  - **Assumido**: alargar para o teto da config. RQ-01 diz que se "pode subir arquivos de ate
    10mb", e um campo travado em 1 MB não permite; RQ-05 diz que o "tamanho maximo de upload",
    no singular, vive na config. Um teto por campo são vários donos da mesma pergunta.
  - **Se negado**: devolver `->maxSize(1024)` naquele campo e marcar RQ-01 como atendida com
    exceção declarada. Nesse caso o número volta a estar cravado, e RQ-05 deixa de valer ali.

- **RQ-01, unidade** — "10mb" é MB. O `->maxSize()` do Filament recebe **kilobytes**
  (`vendor/filament/forms/src/Components/BaseFileUpload.php:413-421`, que monta `max:{$size}`, e
  `vendor/laravel/framework/src/Illuminate/Validation/Concerns/ValidatesAttributes.php:2822`, que
  divide o tamanho por 1024). Sem ambiguidade real, mas é onde a feature erra se ninguém escrever.

- **RQ-04 × `.ico` e `.tiff`** (devolvido pela `feature-test-design`, **resolvido pelo quality
  gate**) — a regra `image` do Laravel é uma allow-list de nove formatos
  (`ValidatesAttributes.php:1533`), e `.ico` e `.tiff` não estão nela.
  - **RESOLVIDO, não assumido.** A ambiguidade deixou de existir quando o quality gate olhou o
    disco: **o kit serve `public/favicon.ico`**. A tela de favicon do kit recusava o formato de
    favicon que o kit distribui, e isso não é trade-off, é RQ-04 violada. A barreira passou a ser
    `mimes:` com os nove formatos **mais `ico` e `tiff`**. Ver a correção em ADR-02.

- **Arquivo de 0 byte** (devolvido pela `feature-test-design`) — o requisito define teto e **não
  define piso**. Medido: um arquivo de 0 KB com nome `.png` passa `max`, passa `image` (o MIME vem
  do nome), grava no disco `public`, e um favicon de 0 byte quebra o `<head>` de toda página sem
  erro em lugar nenhum.
  - **Assumido**: fora desta entrega. Não há cláusula, e inventar `min:1` seria chutar valor.
  - **Consequência declarada**: fica como **lacuna** no checklist de taxonomia do `04`, com o
    mutante correspondente sem matador. É dívida registrada, não defeito escondido.
  - **Se confirmado como defeito**: um cenário por família de campo, na fronteira 0 KB / 1 KB.

- **O piso de 1 MB quando a env é inválida** (devolvido pela `feature-test-design`) — o requisito
  diz "pode subir arquivos de ate 10mb" e não diz o que fazer com valor **inválido**.
  `NumeroDoEnv::positivo()`, que `.ai/rules/config.md` obriga a usar, manda negativo e texto para
  **1 MB**, não para o default. Um typo plausível (`KIT_UPLOAD_MAXIMO_MB=10 MB`, com a unidade
  escrita junto) derrubaria o teto da instalação inteira, em silêncio.
  - **Assumido**: é o comportamento documentado da classe, e o argumento de ADR-01 vale — o pior
    caso passa a ser um teto curto e **visível**, que faz alguém corrigir o `.env`.
  - **Se negado**: valor inválido cai no default (10240) em vez do piso, o que exige uma regra nova
    em `NumeroDoEnv` — infra compartilhada, decisão do dono do kit e não desta wiki.

- **Texto das mensagens de erro e do `helperText`** — o requisito não determina nenhuma frase.
  - **Assumido**: detalhe de implementação. As asserções sobre texto existem para provar que a
    mensagem em MB (e não em kilobytes) chega ao formulário, não para congelar a redação.

## Fora de Escopo (declarado)

- Anti-vírus, sanitização de SVG (DOMPurify e afins) ou reescrita de imagem no upload — o
  requisito pede recusa, não saneamento.
- Teto **por campo** diferente por campo. O requisito fala de um "tamanho maximo de upload", no
  singular.
- Dimensões de imagem (largura/altura). Não pedidas.
- Teto de upload de CSV do importador do Filament (`ImportAction`), que não é `FileUpload` do kit
  e tem teto próprio do pacote.
