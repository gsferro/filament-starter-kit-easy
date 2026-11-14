# Casos de Teste — Aparência das telas de acesso

> Derivado do **requisito**, não do plano. Nenhuma implementação foi consultada.
>
> Cenários: 56 · Regras: 17 · Mutantes previstos: 57 · Sem matador: 0 *(os quatro `grep -cE` de `feature-test-design/references/template-04.md` §Contagem do cabeçalho, 2026-11-14, depois do Adendo 1; CT-21, CT-27 e CT-35 `@obsoleto`; as regras R18..R25 do Adendo 1 estão na tabela da seção dele, sem `## Regra` próprio)*
>
> Oráculo atual: `00-requisito.md`, RQ-01..RQ-24 (RQ-17..RQ-24 no Adendo 1), P-01..P-24 e decisões de Q2..Q11 *(alterado em 2026-11-14: QA-23)*. CT-36..CT-41 vieram do step 9; CT-42 e as linhas de fronteira do CT-40 do quality gate (ciclo 1). Os recortes 01/02 fornecem somente localização e superfície.
>
> Derivação sem execução; os testes foram escritos depois pelos executores (ver `03` › Testes). **Confirmada** preenchida pela sessão.
>
> Fechamento dos **23 achados ADV-01..ADV-23 de `adversario.md`**. Os achados históricos da derivação anterior não são renumerados nem confundidos com essa rodada.

## Perfil de Derivação

| Área | Probabilidade | Impacto | P×I | Perfil |
|---|---:|---:|---:|---|
| Editor, compatibilidade e tolerância | 3 | 2 | 6 | padrão |
| Segurança, escopo e isolamento | 3 | 3 | 9 | completo |
| Apresentação e interação visual | 2 | 2 | 4 | padrão |
| Documentação | 1 | 1 | 1 | mínimo |

Técnicas: EP, BVA, tabela de decisão, estado × evento, papel × ação, rastreio de efeitos, relações metamórficas e comparação de apresentação.

Identificadores preservados: CT-01..CT-28 e CT-B01..CT-B06. Acréscimos: CT-29..CT-35 e CT-B07..CT-B08. Os esquemas contam como um cenário, independentemente das linhas de exemplos.

Q1 e Q2 originais continuam abertas, com P-01 e P-02 vigentes. Isso não suspende seus cenários. Q3..Q11 já foram decididas e não são devolvidas como pendências.

Os acréscimos decorrentes da revisão adversarial justificam os estouros dos tetos por regra. A re-revisão independente dos cenários novos cabe à sessão responsável; não é apresentada aqui como realizada.

## Varredura SFDIPOT

| Dimensão | Risco examinado | Cenários |
|---|---|---|
| Structure | Configurações, criação/edição de organização, preview e layouts | CT-01–02, CT-14, CT-35, CT-B01–08 |
| Function | Salvar, herdar, copiar, descartar própria e pré-visualizar | CT-02, CT-10–16, CT-29–34 |
| Data | Vocabulário, tipos, cores completas, pontos de código, espaços e conteúdo hostil | CT-03–06, CT-18, CT-28–31, CT-B07–08 |
| Interfaces | HTTP, ações de salvar, hidratação pública e iframe | CT-08, CT-14–20, CT-25–29, CT-B05 |
| Platform | Kit/Tenancy, painéis existentes, temas e viewports determinados | CT-07, CT-22, CT-B01–08 |
| Operations | Quatro cenários de atualização, suítes existentes e documentação bilíngue | CT-21, CT-23–24, CT-27 |
| Time | Envio com erro, revogação, expiração e mudança posterior da padrão | CT-08, CT-11–13, CT-17–18, CT-25–26, CT-32 |

P-13 determina o resultado de rascunho expirado, mas não determina seu TTL. O teste força a expiração pela costura disponível, sem inventar duração.

## Mapa de Regras

| Regra | Área/perfil | Origem | Técnica | Cenários |
|---|---|---|---|---|
| R1 — Edição, recusa e normalização | editor/padrão | RQ-09, RQ-11, P-03, P-05, **P-10, P-11** | EP, BVA, rastreio de escrita | CT-01–03, CT-28–31, CT-35, CT-B07 |
| R2 — Fallback de dados gravados | tolerância/padrão | RQ-07, P-04, **P-11, P-14** | EP, metamórfica | CT-04–05 |
| R3 — Texto sem conteúdo executável | segurança/completo | P-05 | EP por campo e contexto de saída | CT-06, CT-B08 |
| R4 — Contexto visual não concede acesso | segurança/completo | RQ-13, P-02, P-06 | decisão, estado × evento | CT-07–09, CT-25–26 |
| R5 — Herança, cópia e descarte | isolamento/completo | RQ-13, RQ-14, P-07, **P-12** | estado × evento, ciclo de volta | CT-10–13, CT-32, CT-34 |
| R6 — Preview do rascunho e inércia | segurança/completo | RQ-10, P-08, **P-13** | EP × efeitos | CT-14–16 |
| R7 — Autorização, dono e escopo | segurança/completo | RQ-09–11, RQ-13, P-08, **P-13** | papel × ação, IDOR, tipos | CT-17–20 |
| R8 — Preservação da instalação existente | compatibilidade/padrão | RQ-07, RQ-08, P-01, **P-14**, Q10 | regressão e checklist de release | CT-21–22, CT-27 |
| R9 — Documentação detalhada bilíngue | documentação/mínimo | RQ-15, RQ-18, Q11 | EP por canal e idioma | CT-23 |
| R10 — Composição de computador | apresentação/padrão | RQ-01, P-03, **P-14** | EP, geometria | CT-B01 |
| R11 — Composição de celular | apresentação/padrão | RQ-01, P-09, **P-14** | EP, geometria | CT-B02 |
| R12 — Interação acessível | apresentação/padrão | RQ-01 | EP, teclado | CT-B03 |
| R13 — Opções visuais efetivas | apresentação/padrão | P-03, **P-11**, Q11 | EP, estilo e identidade de recurso | CT-B04 |
| R17 — Achados do step 9 e do gate | segurança/apresentação | **P-15, P-16, P-17, P-18**, P-12, P-07 | estado × evento, papel × ação, EP, BVA | CT-36–42 |
| R14 — Preview interativo | apresentação/padrão | RQ-10, P-08, **P-14** | JavaScript e iframe | CT-B05 |
| R15 — Clássico idêntico | compatibilidade/padrão | RQ-01, RQ-07, RQ-11, P-03, **P-14** | igualdade de configuração e regressão | CT-33, CT-B06 |
| R16 — Referências externas restritas | documentação/mínimo | RQ-12, Q9 | inspeção genérica de links | CT-24 |
| R18 — Vocabulário dos campos novos | tolerância/padrão | RQ-22, P-19, P-20, P-21, P-23, P-24 | EP, BVA | CT-44, CT-45, CT-46, CT-55 |
| R19 — Disposição | apresentação/padrão | RQ-23, P-19 | EP, BVA, geometria | CT-47, CT-54, CT-B11 |
| R20 — Destaque central | apresentação/padrão | RQ-20, P-20 | EP, falha de recurso, geometria | CT-48, CT-B12 |
| R21 — Ilustração | apresentação/padrão | RQ-21, RQ-22, P-21 | EP, estado × evento | CT-49, CT-B10, CT-B13, CT-B14, CT-B15 |
| R22 — Só o que a tela mostra | segurança/completo | P-06, P-16, P-20 | papel × ação | CT-50, CT-53, CT-56 |
| R23 — Editor, upload e preview | segurança/completo | RQ-19, RQ-20, P-08, P-20, P-24 | EP, rastreio de escrita | CT-43, CT-51, CT-52 |
| R24 — Celular só com a marca | apresentação/padrão | RQ-24, P-22 | EP, visibilidade | CT-B09, CT-B05 |
| R25 — Processo do Adendo 1 | processo/mínimo | RQ-17, RQ-18 | execução pela sessão | CT-21 (cenários, `03`), CT-23 (docs e capturas) |
| R26 — Uma aparência por tela | isolamento/completo | RQ-25, RQ-26, P-25, P-26 | estado × evento, decisão | CT-58, CT-59, CT-60, CT-61, CT-62, CT-63, CT-64, CT-73, CT-74, CT-75, CT-78 |
| R27 — Textos formatados | segurança/completo | RQ-27, P-27 | EP, BVA, conteúdo hostil | CT-57, CT-65, CT-66, CT-67 |
| R28 — Cor primária e gradiente | apresentação/padrão | RQ-28, RQ-29, P-28, P-29 | EP, valor hostil | CT-68, CT-69, CT-70, CT-76, CT-77 |
| R29 — Social e anti-robô no painel | regressão/padrão | RQ-31, RQ-32, RQ-33 | EP por painel e tela | CT-71, CT-72, CT-B17 |
| R30 — Faixa do celular com arte | apresentação/padrão | RQ-30, P-30 | BVA de largura | CT-B16 |

Tratamento das cláusulas:

- RQ-02..RQ-06, RQ-08 e RQ-16 são de processo. Sua evidência pertence aos registros de decisões, despachos, branch e execução.
- RQ-07 tem prova de atualização real em CT-21/CT-27, **executada pela sessão**, além dos testes de regressão.
- RQ-12 combina CT-24 genérico com busca privada pela sessão, registrada no `03` sem reproduzir nomes.
- RQ-14 usa P-07/P-12; Q11 confirma reutilização da identidade existente e ausência de upload novo.
- Todas as premissas P-01..P-14 estão rastreadas.
- **A escolha de painel está fora do escopo de aparência e de preview, conforme P-02.**
- Nenhuma cláusula do 00 está marcada como `aberta — Qn`.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 Editor | R1, R5, R7 | componente Livewire/Filament | existente: padrões de `ConfiguracoesDoKitTelaTest` e `IdentidadeVisualTenancyTest` | Exercita controles, erros e salvamento das telas || sessão, 2026-11-13 — convenção do kit (Kit/Tenancy + Browser) e autorização do pedido |
| G2 Fronteiras | R1–8, R15 | Pest feature HTTP | existente: arnês Kit/Tenancy e helpers de `Pest.php` | Prova HTTP, saída, persistência e chamada da ação de salvar sem montar a UI || sessão, 2026-11-13 — convenção do kit (Kit/Tenancy + Browser) e autorização do pedido |
| G3 Entrega documental | R9, R16 | Pest feature HTTP | existente: arnês Kit; destino de teste proposto | Inspeciona artefatos e respostas renderizadas || sessão, 2026-11-13 — convenção do kit (Kit/Tenancy + Browser) e autorização do pedido |
| G4 Visual | R1, R3, R10–15 | browser | existente: Browser/BrowserTenancy | Exige JavaScript, geometria, teclado ou entrada real do navegador || sessão, 2026-11-13 — convenção do kit (Kit/Tenancy + Browser) e autorização do pedido |

**CT-21 e CT-27 ficam fora das costuras automatizadas acima:** `execução pela sessão (cenários do checklist)`. Não são testes Pest nem geram arquivo de teste para atualização real.

A fronteira de CT-28 é a **ação de salvar das telas de configurações, criação e edição de organização**, chamada sem montar o componente. Não se exige validação universal em qualquer `Model::save()`.

## Fronteira com o Plano

| Item dos recortes | Uso permitido | Recusado como oráculo |
|---|---|---|
| Classes e paths | Localizar entradas | Nome de classe ou método não determina comportamento |
| Iframe e botão de preview | Acionar a superfície | Não exigir mecanismo específico de cache |
| Propriedades indicadas como protegidas | Identificar ataques públicos | Não exigir atributo específico como resultado |
| Regras de formulário sugeridas | Localizar validação | Recusa, tetos e normalização vêm de P-10/P-11 |
| Rotas de Resource | Localizar destinos a confirmar | O path proposto não cria regra de negócio |
| Layouts e medidas | Usar valores determinados pelo 00 | Nenhuma dimensão adicional ganha força de requisito |
| Quatro cenários de release | Referenciar a execução decidida em Q10 | Não substituí-los por simulação Pest de atualização |

As regras locais de browser prevalecem sobre recomendações genéricas: execução em série, preparação das views e tratamento documentado do ruído de ResizeObserver no editor.

## Setup Global

### Arnês e versões

Versões conferidas: Laravel 13.42.0, Filament 5.10.2, Livewire 4.4.7, Pest 5.1.1 e pest-plugin-browser 5.0.1.

- `tests/Kit`: tenancy desligada, `TestCase` e `RefreshDatabase`.
- `tests/Tenancy`: tenancy ligada antes das migrations, `TenancyTestCase` e `RefreshDatabase`.
- Não alternar `permission.teams` no `beforeEach`.
- Browser e BrowserTenancy usam seus respectivos arneses existentes.

### Personas

Pessoas distintas:

1. Administradora autorizada a salvar a padrão.
2. Editora autorizada somente à organização A.
3. Pessoa autorizada a criar organização.
4. Pessoa sem autorização ao escopo solicitado.
5. Visitante.
6. Pessoa com acesso somente a B.

Exercitar também os três pares de autorizações e o acúmulo triplo. Permissão adicional não pode retirar uma autorização existente. Não usar bypass global para representar autorização específica.

### Fixtures

- Organizações A e B distintas, inicialmente ativas.
- Organização inativa pela factory/state existente.
- **P:** painel, formulário à esquerda, cor `#123456`, destaque `PADRAO`, apoio `APOIO P`, logo e alternador ligados.
- **A:** painel, formulário à direita, cor `#654321`, destaque `ORG A`, apoio `APOIO A`, logo e alternador desligados.
- **P0:** painel, frases ausentes, logo e alternador desligados, cor primária configurada.
- **P2:** padrão posterior, diferente de P e A em todos os campos.
- Arte e logo distintas para a identidade padrão e a da organização.
- Configuração anterior entregue ao pacote de aparência, capturada na versão anterior com os mesmos dados e contexto.

“Integralmente” inclui todos os campos, flags e ausência semântica das frases. Não exige representação física específica de ausência.

### Normalização e textos

- Tetos literais: destaque **80** e apoio **160 pontos de código**.
- Medir **depois** de remover espaços das bordas e colapsar espaços repetidos.
- Vazio e somente espaços significam ausência.
- Os exemplos de espaços usam U+0020; não inventam política adicional para separadores Unicode.
- Exemplos no teto incluem ASCII, acento e emoji de um ponto de código.
- A sequência `👩‍👩‍👧‍👦` contém sete pontos de código. Onze repetições mais `abcd` contêm **81**, apesar de poucos grafemas.

### Helpers e isolamento

Reutilizar `chaveDoCampo`, `carregarAba`, `noPainelBootado`, `noPainelDa`, `fronteiraDeRequest`, `ofertaPara` e `ligarLoginUnificado`, conforme o arnês existente.

- Abrir a aba adiada antes de preencher seus campos.
- No painel app, efetuar o GET real necessário antes do componente.
- Separar requests e sessões de pessoas diferentes.
- Em CT-08/CT-25, conferir que o envio Livewire realmente não recebeu a query da montagem.
- Transições padrão/própria usam escolhas e salvamentos reais.
- Somente os cenários de corrupção gravada contornam deliberadamente a validação.

### Fotografias e efeitos

Antes das operações, fotografar os alvos reais: aparência padrão, aparência e demais campos de A/B, recado do login, organizações existentes e rascunhos.

No preview, criar conta real destinatária de recuperação, e-mail novo disponível para cadastro e interceptação específica do provedor. Fotografar usuários, vínculos, tokens e identidade da sessão.

Uma recusa de criação significa **zero nova organização**, além de nenhuma aparência ou campo alheio alterado.

Atualizações reais usam cópias descartáveis e são executadas pela sessão. Não alterar a árvore de trabalho para essa prova.

## Regra R1 — Edição válida, recusa e normalização

**Origem:** RQ-09, RQ-11, P-03, P-05, P-10, P-11 e Q11.  
**Perfil:** padrão. **Técnica:** EP e BVA; três valores usados onde distinguem os erros de limite.

```gherkin
# language: pt
Funcionalidade: Configurar a aparência
  Regra: O editor aceita o vocabulário e recusa valores inválidos sem salvar

    Esquema do Cenário: [CT-01] O editor está disponível no escopo autorizado
      Dado a pessoa autorizada ao escopo <escopo>
      Quando a pessoa abre <tela>
      Então o editor apresenta a escolha vigente e controles de layout, lado, fundo, cor, textos, logo e tema

      Exemplos:
        | escopo | tela | partição |
        | padrão | configurações na aba telas de acesso | padrão |
        | criar organização | criação | criação |
        | organização A | edição de A | edição |

    Esquema do Cenário: [CT-02] A escolha salva aparece em nova leitura
      Dado pessoa autorizada e formulário <entrada> com demais campos válidos
      Quando a pessoa salva pelo componente a aparência <aparência>
      Então a nova leitura contém integralmente <aparência> e preserva os campos alheios

      Exemplos:
        | entrada | aparência | partição |
        | configurações | P | padrão painel |
        | criar organização própria | A | criação |
        | editar organização própria | A | edição |
        | configurações | clássico | escolha clássico |

    Esquema do Cenário: [CT-03] Os textos válidos alcançam os tetos literais
      Dado pessoa autorizada a <entrada> e somente <campo> variando com <n> pontos de código após normalização
      Quando a pessoa salva a aparência pelo componente
      Então o texto relido contém exatamente os <n> pontos de código esperados sem truncamento

      Exemplos:
        | entrada | campo | n | partição |
        | configurações | destaque | 79 | borda menos um |
        | configurações | destaque | 80 | borda |
        | configurações | apoio | 159 | borda menos um |
        | configurações | apoio | 160 | borda |
        | criar organização | destaque | 80 | borda criação |
        | criar organização | apoio | 160 | borda criação |
        | editar organização | destaque | 80 | borda edição |
        | editar organização | apoio | 160 | borda edição |

    Esquema do Cenário: [CT-28] A ação de salvar recusa o excesso mesmo sem montar o componente
      Dado pessoa autorizada, alvo fotografado e somente <campo> com <n> pontos de código após normalização
      Quando a pessoa chama diretamente a ação de salvar de <entrada> sem montar a UI
      Então ocorre <resultado> e os campos alheios permanecem iguais à fotografia

      Exemplos:
        | entrada | campo | n | resultado | partição |
        | cada entrada de escrita | destaque | 80 | sucesso com os 80 pontos de código integrais persistidos | borda |
        | cada entrada de escrita | destaque | 81 | recusa com erro no destaque e nenhuma gravação | borda mais um |
        | cada entrada de escrita | apoio | 160 | sucesso com os 160 pontos de código integrais persistidos | borda |
        | cada entrada de escrita | apoio | 161 | recusa com erro no apoio e nenhuma gravação | borda mais um |

    Esquema do Cenário: [CT-29] Valor inválido no formulário impede o salvamento inteiro
      Dado pessoa autorizada, <entrada> válida fotografada e uma alteração válida em campo alheio à aparência
      E somente <campo> da aparência contém <valor> inválido
      Quando a pessoa salva pelo componente
      Então aparece erro no <campo> e aparência, demais campos, recado do login e organizações existentes permanecem iguais à fotografia
      E na criação nenhuma organização é criada

      Exemplos:
        | entrada | campo | valor | partição |
        | cada entrada de escrita | destaque | 81 pontos de código normalizados | excesso destaque |
        | cada entrada de escrita | apoio | 161 pontos de código normalizados | excesso apoio |
        | cada entrada de escrita | layout | desconhecido | enum layout |
        | cada entrada de escrita | lado | diagonal | enum lado |
        | cada entrada de escrita | fundo | vídeo | enum fundo |
        | cada entrada de escrita | cor | #12345G | caractere inválido |
        | cada entrada de escrita | cor | #1234567 | sufixo numérico |
        | cada entrada de escrita | cor | #123456;}body{display:none} | sufixo CSS |
        | cada entrada de escrita | cor | #123456" onload=alert(1) | sufixo atributo |
        | cada entrada de escrita | cor | #123456 seguido de LF | quebra final |
        | cada entrada de escrita | lado | array | tipo inválido isolado |
        | cada entrada de escrita | logo | valor fora do domínio booleano | flag inválida |

    Esquema do Cenário: [CT-30] A normalização precede a contagem em pontos de código
      Dado pessoa autorizada com somente <campo> contendo <entrada>
      Quando a pessoa salva a aparência
      Então ocorre <resultado>

      Exemplos:
        | campo | entrada | resultado | partição |
        | destaque | dois espaços + 80 letras a + dois espaços | sucesso e exatamente 80 letras a persistidas | trim antes do teto |
        | destaque | 39 letras a + três espaços + 40 letras b | sucesso e 39 letras a + um espaço + 40 letras b persistidos | colapso antes do teto |
        | apoio | dois espaços + 160 letras a + dois espaços | sucesso e exatamente 160 letras a persistidas | teto apoio após trim |
        | apoio | 79 letras a + três espaços + 80 letras b | sucesso e 79 letras a + um espaço + 80 letras b persistidos | colapso apoio |
        | destaque | vazio | frase ausente e nenhum bloco de destaque renderizado | vazio |
        | apoio | três espaços | frase ausente e nenhum bloco de apoio renderizado | somente espaços |
        | destaque | 80 emojis de um ponto de código | sucesso e os 80 emojis integrais persistidos | pontos de código |
        | destaque | onze famílias ZWJ + abcd | erro no destaque e nenhuma gravação | 81 pontos em poucos grafemas |
        | apoio | 23 famílias ZWJ | erro no apoio e nenhuma gravação | 161 pontos em poucos grafemas |

    Cenário: [CT-31] Campos omitidos do painel usam os padrões determinados
      Dado aparência painel com os demais campos omitidos e identidade com logo e cor primária #654321
      Quando a pessoa abre o login
      Então a configuração efetiva tem formulário à direita, fundo de cor primária #654321, frases ausentes, logo ligada e alternador ligado

    # Substituído pelo Adendo 1 (RQ-20 pede imagem própria no meio do painel): o upload agora existe;
    # o que este cenário protegia — nenhum arquivo arbitrário — passa ao CT-43 e ao CT-45.
    @obsoleto
    Esquema do Cenário: [CT-35] O editor reutiliza a identidade sem upload próprio
      Dado pessoa autorizada a <editor> e identidade com arte e logo conhecidas
      Quando a pessoa abre o editor da aparência
      Então não existe controle de upload da aparência e as opções de arte e logo usam a identidade existente

      Exemplos:
        | editor | partição |
        | configurações | padrão |
        | criação de organização | criação |
        | edição de organização | edição |
```

“Cada entrada de escrita” expande para configurações, criação e edição de organização, em execuções independentes. Em configurações, a alteração alheia inclui o **recado do login em outra aba**; na organização, inclui campo ordinário válido.

CT-29 repete as partições de tipo inválido isoladamente para os demais campos estruturados. Nenhuma execução combina dois campos inválidos. HTML em frase não é executado nem interpretado; sua saída é provada em R3, sem substituir o contrato por um erro de validação inventado.

CT-30 executa em cada entrada de escrita. As recusas conservam as fotografias e impedem a criação da organização. A aceitação dos emojis pela entrada real do navegador tem prova distinta em CT-B07.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | Aba adiada não disponibiliza o editor | CT-01 | Controles presentes no escopo autorizado |
| M2 | Salvamento ignora aparência em uma entrada | CT-02 | Nova leitura integral igual ao enviado |
| M3 | Teto 79/159 ou corte em bytes | CT-03 | 80/160 pontos de código preservados, inclusive emoji |
| M53 | A ação aceita excesso quando chamada sem montar a UI | CT-28 | 81/161 recusados com erro e nenhuma gravação |
| M55 | Formulário trunca o texto e salva sem avisar | CT-29 | Excesso produz erro e conserva a fotografia |
| M56 | Validação falha depois de salvar outra aba ou criar a organização | CT-29 | Recado/campos preservados e zero organização criada |
| M58 | Teto é medido antes do trim/colapso | CT-30 | Entrada maior antes da normalização salva o resultado de 80/160 |
| M59 | Teto conta grafemas | CT-30 | Sequências com 81/161 pontos de código são recusadas |
| M60 | Defaults usam esquerda, arte, frases ou flags desligadas | CT-31 | Configuração efetiva igual aos defaults de P-11 |
| M66 | Editor oferece upload novo da aparência — *invertido pelo Adendo 1 (RQ-20): o upload é pedido; o mutante agora é "upload aceita qualquer tipo/diretório", morto por CT-43* | CT-35 `@obsoleto` → CT-43 | Upload único, PNG/JPEG/WebP, `telas-de-acesso/`, teto do kit |

Os mutantes adicionais vêm da revisão adversarial. CT-28 não exige criar uma segunda política de escrita no model.

## Regra R2 — Valores gravados inválidos têm fallback seguro

**Origem:** RQ-07, P-04, P-11 e P-14.  
**Perfil:** padrão. **Técnica:** EP e relação metamórfica.

```gherkin
Funcionalidade: Ler aparência tolerante
  Regra: A invalidez gravada não derruba autenticação

    Esquema do Cenário: [CT-04] Layout inválido volta ao clássico anterior
      Dado aparência gravada com somente o layout <valor> inválido e configuração anterior conhecida
      Quando o visitante abre o login
      Então a configuração entregue ao pacote é exatamente a anterior e o formulário clássico é renderizado sem erro

      Exemplos:
        | valor | partição |
        | desconhecido | fora do vocabulário |
        | array | tipo errado |

    Esquema do Cenário: [CT-05] O fallback é local e usa o padrão literal do campo
      Dado aparência painel com sentinelas válidas nos demais campos e somente <campo> gravado como <valor>
      Quando o visitante abre uma tela abrangida
      Então somente esse campo assume <padrão>, as demais sentinelas permanecem e não sai conteúdo ativo originado do valor inválido

      Exemplos:
        | campo | valor | padrão | partição |
        | lado | diagonal | direita | enum |
        | lado | array | direita | tipo |
        | fundo | vídeo | cor | enum |
        | fundo | array | cor | tipo |
        | cor | #12345G | primária | caractere |
        | cor | #12345 | primária | comprimento curto |
        | cor | #1234567 | primária | sufixo numérico |
        | cor | #123456;}body{display:none} | primária | sufixo CSS |
        | cor | #123456" onload=alert(1) | primária | sufixo atributo |
        | cor | #123456 seguido de LF | primária | quebra final |
        | cor | array | primária | tipo |
        | destaque | 81 pontos de código normalizados | ausente | excesso |
        | destaque | array | ausente | tipo |
        | apoio | 161 pontos de código normalizados | ausente | excesso |
        | apoio | array | ausente | tipo |
        | logo | valor não booleano | ligada | domínio |
        | logo | array | ligada | tipo |
        | alternador | valor não booleano | ligado | domínio |
        | alternador | array | ligado | tipo |
```

Executar com aparência padrão e própria, respeitando os contextos de P-06. Na própria, a primária de fallback é a da identidade aplicável ao contexto.

Para a cor inválida, conferir a cor efetiva e a ausência de declaração CSS/atributo extra, não somente que a página respondeu.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M4 | Layout desconhecido chega ao renderizador | CT-04 | Configuração anterior exata e clássico renderizado |
| M5 | Estrutura de tipo errado não recebe fallback | CT-05 | Array produz default literal sem erro |
| M6 | Um campo inválido descarta a aparência inteira | CT-05 | Demais sentinelas permanecem |
| M57 | Regex de cor aceita prefixo válido com sufixo | CT-05, CT-29 | Gravado recebe primária; formulário recusa |

## Regra R3 — Textos não viram conteúdo ativo

**Origem:** P-05.  
**Perfil:** completo. **Técnica:** EP por campo e contexto de saída.

```gherkin
Funcionalidade: Exibir textos da aparência
  Regra: A saída preserva texto sem produzir HTML, CSS ou script

    Esquema do Cenário: [CT-06] Conteúdo hostil sai como texto
      Dado <campo> com <texto> dentro do teto no contexto de <saída>
      Quando a pessoa abre <saída>
      Então o texto aparece literalmente na posição de conteúdo sem nó, atributo de evento, estilo ou trecho executável originado dele

      Exemplos:
        | campo | texto | saída | partição |
        | destaque | <b>Marca</b> | login | HTML |
        | apoio | <img src=x onerror=alert(1)> | login | evento |
        | destaque | <script>alert(1)</script> | preview | script |
        | apoio | <style>body{display:none}</style> | preview | CSS |
        | destaque | ';alert(1);// | login | contexto JavaScript |
        | apoio | " autofocus onfocus="alert(1) | preview | quebra de atributo |
        | destaque | <b>Marca</b> | recuperar senha | outra tela |
        | apoio | <img src=x onerror=alert(1)> | cadastro | outra tela |
```

Cada payload é exercitado isoladamente nos dois campos, na aparência padrão e própria quando aplicável, em todas as telas de P-02 e no preview.

A inspeção é contextual: não basta buscar entidades HTML no documento inteiro. CT-B08 complementa a prova com JavaScript efetivamente executado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 | Destaque usa HTML cru | CT-06 | Texto literal e nenhum nó ativo correspondente |
| M8 | Apoio admite marcação por ser texto maior | CT-06 | `img` e `style` não viram elementos |
| M9 | Preview omite escape | CT-06 | Mesmo contrato no documento demonstrativo |
| M72 | Escape HTML é usado em interpolação JavaScript insegura | CT-B08 | Nenhuma execução, diálogo ou atributo ativo após renderização e foco |

## Regra R4 — Contexto visual não concede acesso

**Origem:** RQ-13, P-02 e P-06.  
**Perfil:** completo. **Técnica:** tabela de decisão e estado × evento.

```gherkin
Funcionalidade: Resolver contexto da aparência
  Regra: A organização só modifica os contextos determinados

    Esquema do Cenário: [CT-07] A decisão considera todas as condições
      Dado padrão P, organização A própria e contexto <contexto>
      Quando a pessoa abre <tela>
      Então a aparência integral é <esperada> e o formulário correspondente está presente

      Exemplos:
        | contexto | tela | esperada | partição |
        | tenancy ligada, A ativa identificada por org | login app | A | elegível |
        | tenancy ligada, A ativa identificada por org | /login | A | login único |
        | tenancy ligada, cadastro aberto identificado de A | cadastro app | A | cadastro aberto |
        | tenancy ligada, cadastro identificado de A | /cadastro | A | cadastro único |
        | tenancy ligada, convite válido de A | cadastro por convite | A | convite da organização |
        | tenancy ligada, convite sem contexto elegível de organização | cadastro por convite | P | convite padrão |
        | tenancy ligada, sessão dentro de A | bloqueio | A | bloqueio |
        | tenancy desligada, org de A informado | login | P | tenancy desligada |
        | tenancy ligada, org ausente | login | P | contexto nulo |
        | tenancy ligada, org inexistente | login | P | inexistente |
        | tenancy ligada, org de organização inativa | login | P | inativa |
        | tenancy ligada, A identificada | login admin | P | painel admin |
        | tenancy ligada, A identificada | login infra | P | painel infra |
        | tenancy ligada, A identificada | recuperar senha | P | exclusão explícita |
        | tenancy ligada, A identificada | redefinir senha | P | exclusão explícita |
        | tenancy ligada, A identificada | confirmação de e-mail | P | exclusão explícita |
        | tenancy ligada, A identificada | dois fatores | P | exclusão explícita |
        | tenancy ligada, A em sessão, sem org na tela | login | P | sessão residual |

    Esquema do Cenário: [CT-08] Erro de login conserva a organização da montagem
      Dado <entrada> montada com org válido de A, padrão P distinta e conta real com senha conhecida
      Quando o visitante envia senha incorreta pela atualização Livewire sem org na query do envio
      Então aparece erro com aparência A, sem autenticação e sem alteração das aparências persistidas

      Exemplos:
        | entrada | partição |
        | login do painel app | painel |
        | /login | página única |

    Cenário: [CT-09] Aparência identificada não concede vínculo
      Dado pessoa autenticada com acesso somente a B e aparência identificada de A
      Quando a pessoa solicita página protegida de A
      Então o conteúdo protegido não é entregue e o vínculo com A permanece ausente

    Esquema do Cenário: [CT-25] Erro de cadastro conserva a organização
      Dado cadastro identificado de A em <entrada> com um erro de validação isolado
      Quando o visitante envia pela atualização Livewire sem a identificação na query do envio
      Então o erro mantém aparência A sem criar conta, vínculo ou autenticação

      Exemplos:
        | entrada | partição |
        | cadastro da organização | painel |
        | /cadastro | página única |
        | cadastro por convite de A | convite |

    Cenário: [CT-26] Erro de desbloqueio conserva aparência e trava
      Dado pessoa real com sessão bloqueada dentro de A e aparência A distinta de P
      Quando a pessoa envia senha incorreta para desbloquear pela atualização Livewire
      Então o erro mantém aparência A e sessão bloqueada sem acrescentar permissão ou vínculo
```

A escolha de painel não integra o dataset. No cadastro por convite, usa-se convite real e o contexto efetivamente determinado por P-06; não se atribui aparência própria a todo convite indiscriminadamente.

CT-09 não inventa status de recusa. CT-22 prova separadamente o login padrão utilizável e a autenticação válida.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M10 | Tenancy ou identificação isolada habilita própria | CT-07 | Tenancy desligada resulta P |
| M11 | Organização inativa fornece própria ao login | CT-07 | Inativa resulta P |
| M12 | Contexto residual pinta outras telas/painéis | CT-07 | Exclusões, admin e infra resultam P |
| M13 | Login depende da query do envio | CT-08 | Erro conserva A sem query |
| M14 | Identificar organização autoriza entrada | CT-09 | Conteúdo protegido e vínculo continuam ausentes |
| M50 | Cadastro perde contexto após erro | CT-25 | A permanece nas entradas de cadastro |
| M51 | Desbloqueio perde contexto ou libera a sessão | CT-26 | A permanece e a sessão continua bloqueada |
| M63 | Registro por convite não recebe a aparência aplicável | CT-07 | Convite elegível recebe A inteira; demais recebem P |

## Regra R5 — Herança inteira, cópia independente e descarte

**Origem:** RQ-13, RQ-14, P-07 e P-12.  
**Perfil:** completo. **Técnica:** estado × evento e ciclo de volta.

A matriz usa **2 modos × 2 situações da organização × 5 operações = 20 células**. As linhas ativa/inativa são executadas separadamente. “Salvar própria” inclui escolher esse modo antes de salvar quando o estado inicial é padrão.

| Situação/modo inicial | Ler aparência no login identificado | Escolher própria e salvar | Escolher padrão e salvar | Salvar própria editada | Alterar padrão global |
|---|---|---|---|---|---|
| ativa/padrão | P, CT-07/11 | copia P, CT-10 | mantém padrão, CT-13 | salva A, CT-02 | acompanha P2, CT-11 |
| ativa/própria | A, CT-07 | conserva A, CT-12 | descarta própria, CT-13 | salva A, CT-02/12 | conserva A, CT-11 |
| inativa/padrão | P, CT-07 | copia P, CT-34 | mantém padrão, CT-34 | salva A, CT-34 | herda P2 no editor, CT-34 |
| inativa/própria | P, CT-07 | conserva própria, CT-34 | descarta própria, CT-34 | salva A, CT-34 | conserva própria no editor, CT-34 |

A organização inativa continua editável; sua aparência própria permanece inelegível para o login público. São operações diferentes.

```gherkin
Funcionalidade: Escolher origem da aparência
  Regra: Herança e cópia não misturam campos

    Esquema do Cenário: [CT-10] Escolher própria copia a padrão inteira
      Dado organização no modo padrão <origem> e pessoa autorizada a <entrada>
      Quando a pessoa escolhe própria e salva sem editar a cópia
      Então a aparência própria persistida equivale integralmente a <origem>

      Exemplos:
        | entrada | origem | partição |
        | criação | P | opcionais preenchidos |
        | edição | P | opcionais preenchidos |
        | criação | P0 | flags falsas e opcionais ausentes |
        | edição | P0 | flags falsas e opcionais ausentes |

    Cenário: [CT-11] Alterar a padrão afeta somente quem ainda a usa
      Dado A usando padrão e B com cópia própria anterior de P
      Quando a administradora salva P2 diferente em todos os campos
      Então A apresenta P2 inteira e B conserva a cópia anterior inteira

    Cenário: [CT-12] Repetir salvamento próprio não recopia a padrão
      Dado organização própria com valores A e padrão P diferente
      Quando a editora repete a mesma intenção válida de salvar A mantendo o modo próprio
      Então a aparência persistida continua A inteira e a padrão continua P inteira

    Esquema do Cenário: [CT-13] Escolher padrão restaura a herança e descarta a própria
      Dado organização no modo <modo> e padrão P2 distinta dos valores próprios anteriores
      Quando a editora escolhe padrão e salva
      Então a organização usa P2 inteira e não conserva aparência própria recuperável

      Exemplos:
        | modo | partição |
        | própria após padrão para própria | volta |
        | padrão | repetição |

    Cenário: [CT-32] Sair novamente da padrão cria uma cópia nova
      Dado organização que tinha A própria, voltou à padrão por salvamento real e depois recebeu padrão global P2
      Quando a editora escolhe própria novamente e salva sem editar
      Então a aparência própria persistida é P2 inteira e nenhum valor de A reaparece

    Esquema do Cenário: [CT-34] Organização inativa aceita as mesmas operações de aparência
      Dado organização inativa no modo <modo> e editora autorizada com padrão P2 distinta de A
      Quando a editora executa <operação>
      Então a nova leitura administrativa contém <resultado>, os demais campos são preservados e o login público continua usando a padrão

      Exemplos:
        | modo | operação | resultado | partição |
        | padrão | escolher própria e salvar sem editar | cópia integral de P2 | criar própria |
        | própria A | salvar própria alterada | nova aparência integral enviada | editar própria |
        | própria A | salvar própria sem alterar | A integral | repetição |
        | própria A | escolher padrão e salvar | padrão e própria descartada | descarte |
        | padrão | escolher padrão e salvar | padrão sem própria | manter padrão |
        | padrão | alterar a padrão global para P2 | herança integral de P2 | acompanha padrão |
        | própria A | alterar a padrão global para P2 | própria A preservada | independência |
```

Em CT-34, a alteração global é realizada por administradora autorizada, em sessão própria; a leitura administrativa seguinte é da editora da organização inativa. Nenhuma permissão é inferida da inatividade.

CT-32 discrimina o descarte: conservar A escondida por uma flag faria o resultado final divergir de P2.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | Própria continua referenciando a padrão | CT-11 | B conserva a cópia após P2 |
| M16 | Cópia esquece flags falsas/opcionais ausentes | CT-10 | Igualdade integral com P0 |
| M17 | Salvar própria reaplica a padrão | CT-12 | A persiste após repetição |
| M18 | Voltar à padrão mistura preferências próprias | CT-13 | P2 integral e própria descartada |
| M19 | Modo padrão congela valores anteriores | CT-11 | A acompanha P2 |
| M61 | Voltar à padrão apenas esconde A e depois a recupera | CT-32 | Nova própria igual a P2, nunca A |
| M70 | Inatividade bloqueia ou ignora a edição da aparência | CT-34 | Operação válida persiste integralmente como na ativa |

## Regra R6 — Preview representa rascunho e é inerte

**Origem:** RQ-10, P-08 e P-13.  
**Perfil:** completo. **Técnica:** EP × rastreio de efeitos.

```gherkin
Funcionalidade: Pré-visualizar sem aplicar
  Regra: Rascunho não grava aparência nem executa autenticação

    Esquema do Cenário: [CT-14] Preview apresenta valores ainda não salvos
      Dado pessoa autorizada, persistido P e rascunho próprio A válido não salvo
      Quando a pessoa abre o preview de <tela>
      Então o documento apresenta A e o formulário de <tela> enquanto as aparências persistidas permanecem P

      Exemplos:
        | tela | partição |
        | login | login |
        | recuperar senha | recuperação |
        | cadastro | cadastro |

    Esquema do Cenário: [CT-15] Tela desconhecida mostra o login com o rascunho
      Dado pessoa autorizada com rascunho próprio A válido e persistência fotografada
      Quando a pessoa solicita preview com tela <valor>
      Então a resposta 200 mostra o login demonstrativo com A e nenhuma aparência é gravada

      Exemplos:
        | valor | partição |
        | desconhecida | fora da lista |
        | ../../admin | tentativa de caminho |

    Esquema do Cenário: [CT-16] Ações demonstrativas não produzem efeitos reais
      Dado pessoa autorizada no preview, destinatário real, e-mail novo disponível e provedor interceptado
      Quando a pessoa tenta <ação>
      Então a identidade da sessão não muda e não surgem usuário, vínculo, token, e-mail de recuperação ou chamada ao provedor
      E as aparências persistidas permanecem iguais às fotografias

      Exemplos:
        | ação | partição |
        | autenticar com credenciais reais | login |
        | recuperar senha da conta real | recuperação |
        | cadastrar conta nova | cadastro |
        | abrir provedor social | social |
```

CT-16 inclui tentativa forjada à fronteira demonstrativa e repetição da intenção. A sessão autenticada continua sendo da mesma pessoa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M20 | Preview lê somente o salvo | CT-14 | A aparece com P ainda persistida |
| M21 | Atualizar preview salva aparência real | CT-14 | Persistência continua P |
| M22 | Tela vira caminho livre de view | CT-15 | Login demonstrativo aparece, nunca superfície administrativa |
| M23 | Demonstrativo reutiliza ações reais | CT-16 | Sessão, usuários, vínculos, tokens e envios sem alteração |
| M24 | Social continua ativo no preview | CT-16 | Zero chamada ao provedor |
| M67 | Tela desconhecida responde erro ou documento vazio | CT-15 | 200 com formulário de login e rascunho A |

## Regra R7 — Autorização, dono e escopo na operação

**Origem:** RQ-09..RQ-11, RQ-13, P-08 e P-13.  
**Perfil:** completo. **Técnica:** papel × ação, IDOR, mass assignment e tipos.

| Persona | Padrão | Editar A | Editar B | Criação própria |
|---|---|---|---|---|
| Visitante | recusa | recusa | recusa | recusa |
| Sem autorização | recusa | recusa | recusa | recusa |
| Autorizada só à padrão | permite | recusa | recusa | recusa |
| Autorizada só a editar A | recusa | permite | recusa | recusa |
| Autorizada só a criar | recusa | recusa | recusa | permite |
| Acúmulo | conforme concessão | conforme concessão | recusa sem concessão | conforme concessão |

Cada célula é exercitada independentemente. Rascunho alheio continua proibido mesmo para quem pode salvar o mesmo escopo.

```gherkin
Funcionalidade: Isolar editor e preview
  Regra: A operação confere autorização atual e dono

    Esquema do Cenário: [CT-17] A rota verifica autorização atual
      Dado pessoa <autorização> com rascunho próprio válido do <escopo>
      Quando a pessoa solicita diretamente o documento do preview
      Então ocorre <resultado> e nenhuma aparência persistida muda

      Exemplos:
        | autorização | escopo | resultado | partição |
        | visitante | padrão | conteúdo do rascunho ausente | sem sessão |
        | autenticada sem permissão | padrão | 403 sem conteúdo do rascunho | padrão negado |
        | pode editar A | B | 403 sem conteúdo do rascunho | horizontal |
        | pode editar A | A | 200 com rascunho próprio | edição permitida |
        | pode criar | criação própria | 200 com rascunho próprio | criação permitida |
        | pode salvar padrão | padrão | 200 com rascunho próprio | padrão permitido |
        | permissão revogada depois do rascunho | A | 403 sem conteúdo do rascunho | revogação |
        | padrão e edição A | cada escopo concedido | 200 com o respectivo rascunho | primeiro par |
        | padrão e criação | cada escopo concedido | 200 com o respectivo rascunho | segundo par |
        | edição A e criação | cada escopo concedido | 200 com o respectivo rascunho | terceiro par |
        | três autorizações | cada escopo concedido | 200 com o respectivo rascunho | acúmulo triplo |

    Esquema do Cenário: [CT-18] Rascunho inválido ou alheio recebe a resposta determinada
      Dado Ana e Bia autorizadas ao mesmo escopo com textos distintos e rascunhos fotografados
      Quando Ana solicita preview com <entrada>
      Então ocorre <resultado> sem entregar texto exclusivo de Bia nem alterar rascunhos ou aparências

      Exemplos:
        | entrada | resultado | partição |
        | UUID exclusivo de Bia | 404 | alheio |
        | UUID exclusivo de Bia com dono Bia forjado | 404 | dono forjado |
        | UUID comum aos namespaces sem dono informado | 200 com somente o rascunho de Ana | namespace próprio |
        | UUID ausente | 404 | ausente |
        | UUID nulo | 404 | nulo |
        | UUID inexistente | 404 | inexistente |
        | UUID próprio expirado | 404 | expirado |

    Esquema do Cenário: [CT-19] Forçar ação sem autorização não escreve o alvo
      Dado pessoa sem autorização para <alvo> e alvos reais e rascunhos fotografados
      Quando a pessoa força <ação> pelo componente com valores válidos
      Então padrão, organizações, campos alheios e rascunhos permanecem iguais às fotografias
      E nenhuma organização é criada pela tentativa de criação

      Exemplos:
        | alvo | ação | partição |
        | padrão | salvar configuração | salvamento negado |
        | padrão | atualizar preview | preview negado |
        | organização B | salvar edição | edição horizontal |
        | organização B | atualizar preview | preview horizontal |
        | criar organização | criar com aparência | criação negada |

    Esquema do Cenário: [CT-20] Hidratação hostil preserva dono e escopo
      Dado pessoa em <superfície> com P, A, B, permissões e rascunhos fotografados
      Quando o cliente envia <entrada> pela atualização pública
      Então não recebe rascunho alheio nem acesso adicional e os dados fora do alvo permanecem iguais às fotografias

      Exemplos:
        | superfície | entrada | partição |
        | TelaLogin.orgDaAparencia | id de B após montagem com A | fora do domínio |
        | TelaLogin.orgDaAparencia | array | tipo errado |
        | Preview.rascunho | UUID alheio | fora do domínio |
        | Preview.rascunho | array | tipo errado |
        | Preview.tela | rota admin | fora da lista |
        | Preview.tela | array | tipo errado |
        | Preview.data | ação real forjada | ação indevida |
        | Preview.data | owner_id alheio | dono indevido |
        | Preview.data | string no lugar da estrutura | tipo errado |
        | ConfiguracoesDoKit.data | chave extra de dono | mass assignment |
        | ConfiguracoesDoKit.data | chave extra de escopo | mass assignment |
        | ConfiguracoesDoKit.data | string no lugar da estrutura | tipo errado |
        | Atualizar preview | escopo alheio | fora do domínio |
        | Atualizar preview | argumento de tipo errado | tipo errado |
        | CreateTenant/EditTenant.data | id de B | alvo indevido |
        | CreateTenant/EditTenant.data | campo de privilégio | privilégio indevido |
        | CreateTenant/EditTenant.data | string no lugar da estrutura | tipo errado |
        | UUID do formulário | UUID de Bia | fora do domínio |
        | UUID do formulário | array | tipo errado |
```

CT-17 é a prova HTTP fora do componente. CT-19 dispara a operação; não testa apenas visibilidade ou `can()`.

CT-20 afirma também que adulterar `orgDaAparencia` não pinta a tela como B. Métodos e argumentos serão ajustados à superfície pública real na implementação dos testes; método inexistente não constitui prova.

Os status determinados de P-13 são exigidos nas requisições correspondentes de CT-15/17/18. Para payload estrutural de tipo errado, não se inventa status além do contrato de isolamento.

Após erro, CT-22 prova o login utilizável; pessoas que continuam autorizadas têm o editor de CT-01 como destino utilizável. Não se exige um botão de recuperação que o 00 não especificou.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M25 | Preview confia na origem da navegação | CT-17 | Chamada direta sem permissão não entrega rascunho |
| M26 | Rascunho é indexado somente por UUID | CT-18 | Namespace comum entrega somente o texto de Ana |
| M27 | Ação confere somente visibilidade | CT-19 | Persistência e rascunhos conservados |
| M28 | Hidratação aceita trocar organização visual | CT-20 | Aparência não vira B e acesso não se amplia |
| M29 | Payload aceita dono, privilégio ou id alheio | CT-20 | Dono, privilégios e B continuam iguais |
| M54 | Primeiro ramo bloqueia outra autorização acumulada | CT-17 | Todos os escopos concedidos continuam acessíveis |
| M68 | Escopo negado responde 200, 404 ou 500 | CT-17 | Autenticada sem permissão recebe exatamente 403 |
| M69 | Rascunho ausente, expirado ou alheio responde 403 ou 200 vazio | CT-18 | Cada partição correspondente recebe exatamente 404 |

## Regra R8 — Atualização preserva a instalação existente

**Origem:** RQ-07, P-01, P-14 e Q10.  
**Perfil:** padrão. **Técnica:** regressão e execução dos cenários de release.

**CT-21 e CT-27: execução pela sessão (cenários do checklist).** A prova de atualização real são os **quatro cenários do checklist de release**, todos executados ao final. Os nomes e os procedimentos são os do checklist; não se cria uma matriz substituta a partir do plano.

```gherkin
Funcionalidade: Atualizar projeto existente
  Regra: A entrega preserva a aparência e o funcionamento anteriores

    # Não é teste Pest: execução pela sessão nos quatro cenários do checklist de release, registrada no 03 (Q10).
    @obsoleto
    Esquema do Cenário: [CT-21] A atualização real preserva o clássico nos quatro cenários
      Dado cópia descartável anterior preparada para <cenário do checklist> com configuração clássica e dados autorais fotografados
      Quando a sessão executa integralmente esse cenário de atualização
      Então as telas de autenticação existentes recebem a configuração clássica anterior e os dados autorais permanecem preservados
      E no cenário com tenancy ligada e organizações existentes os logins dessas organizações abrem com formulário utilizável

      Exemplos:
        | cenário do checklist | partição |
        | primeiro cenário do checklist de release | release 1 |
        | segundo cenário do checklist de release | release 2 |
        | terceiro cenário do checklist de release | release 3 |
        | quarto cenário do checklist de release | release 4 |

    Esquema do Cenário: [CT-22] Credenciais mantêm seu resultado nos dois layouts
      Dado conta ativa autorizada ao painel com senha conhecida e layout <layout>
      Quando o visitante envia <senha> no login <entrada>
      Então ocorre <resultado> e nenhuma aparência persistida muda

      Exemplos:
        | layout | senha | entrada | resultado | partição |
        | clássico | correta | painel | sessão da conta e painel autorizado alcançável | sucesso clássico |
        | painel | correta | painel | sessão da conta e painel autorizado alcançável | sucesso painel |
        | clássico | incorreta | painel | erro e visitante sem sessão | erro clássico |
        | painel | incorreta | painel | erro e visitante sem sessão | erro painel |
        | painel | correta | único | sessão da conta e destino autorizado alcançável | unificado |

    # Não é teste Pest: execução pela sessão nos quatro cenários do checklist de release, registrada no 03 (Q10).
    @obsoleto
    Esquema do Cenário: [CT-27] A regressão existente passa depois da atualização real
      Dado projeto atualizado pela sessão em <cenário do checklist> com as fixtures e dados exigidos pelo checklist
      Quando a sessão conclui as verificações funcionais do cenário e executa as suítes Kit e Tenancy existentes
      Então as verificações prescritas passam, as duas suítes terminam sem falhas e a execução fica registrada com comandos e resultados

      Exemplos:
        | cenário do checklist | partição |
        | primeiro cenário do checklist de release | release 1 |
        | segundo cenário do checklist de release | release 2 |
        | terceiro cenário do checklist de release | release 3 |
        | quarto cenário do checklist de release | release 4 |
```

CT-21 e CT-27 são duas verificações da mesma rodada dos quatro cenários, não oito atualizações obrigatórias.

CT-22 expande painel para admin/app/infra, nas suítes e configurações em que cada rota existe. O GET de preparação exige formulário utilizável. Os casos positivos e negativos são independentes.

Não se propõe arquivo Pest para CT-21/CT-27. As suítes existentes continuam sendo executadas; elas não substituem os quatro cenários reais. A escolha de painel não ganha cenário de aparência.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M30 | Atualização ativa painel em instalação antiga | CT-21 | Configuração clássica anterior preservada nos quatro cenários |
| M31 | Formulário novo perde ligação com autenticação | CT-22 | Sessão correta alcança destino autorizado |
| M32 | Erro de senha é tratado como sucesso | CT-22 | Senha incorreta mantém visitante sem sessão |
| M52 | Atualização quebra migração ou outro fluxo existente | CT-27 | Checklist e suítes Kit/Tenancy passam no projeto realmente atualizado |

## Regra R9 — Documentação detalhada em pt e en

**Origem:** RQ-15 e Q11.  
**Perfil:** mínimo. **Técnica:** EP por canal e idioma.

```gherkin
Funcionalidade: Consultar documentação da aparência
  Regra: Página e READMEs explicam o uso nos dois idiomas

    Esquema do Cenário: [CT-23] Cada canal permite localizar o contrato completo
      Dado a entrega documental de <canal>
      Quando a pessoa responsável consulta seu conteúdo e os links locais oferecidos
      Então localiza layouts, campos e limites, preview, padrão versus própria, independência da cópia, descarte ao voltar à padrão e preservação da instalação existente

      Exemplos:
        | canal | partição |
        | página de documentação pt | documentação pt |
        | página de documentação en | documentação en |
        | README pt | README pt |
        | README en | README en |
```

Resumo correto com link navegável para o detalhe é suficiente nos READMEs. Links quebrados ou conteúdo disponível somente no outro idioma não satisfazem a linha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M33 | Orientação existe somente na página | CT-23 | Cada README localiza os tópicos |
| M34 | Documentação omite independência da cópia | CT-23 | Explica que própria deixa de acompanhar a padrão |
| M64 | Entrega existe somente em pt ou omite um README | CT-23 | Os quatro canais estão disponíveis no idioma correspondente |

## Regra R10 — Composição de computador

Origem: RQ-01, P-03 e P-14. Perfil padrão. Técnica EP/geometria.

CT-B01, no 05, cobre ambos os lados em **1440×900**, com destaque de 80 e apoio de 160 pontos de código. Mutantes M35, M36 e M65. Grupo G4.

## Regra R11 — Composição de celular

Origem: RQ-01, P-09 e P-14. Perfil padrão. Técnica EP/geometria.

CT-B02, no 05, usa **390×844**, inclusive textos nos tetos, marca acima do formulário e rodapé inteiro dentro da dobra. Mutantes M37–M38. Grupo G4.

## Regra R12 — Interação acessível

Origem: RQ-01. Perfil padrão. Técnica EP/teclado.

CT-B03 e mutantes M39–M40 no 05. Grupo G4. Não se declara conformidade com nível WCAG não solicitado.

## Regra R13 — Opções visuais efetivas

Origem: P-03, P-11 e Q11. Perfil padrão. Técnica EP/estilo e identidade de recurso.

CT-B04 e mutantes M41–M43 no 05. Grupo G4. CT-31 fixa os defaults literais; CT-B04 prova sua aplicação visual.

## Regra R14 — Preview interativo

Origem: RQ-10, P-08 e P-14. Perfil padrão. Técnica JavaScript/iframe.

CT-B05 e mutantes M44–M45 no 05. Grupo G4. Computador e celular correspondem a 1440×900 e 390×844 no documento demonstrativo.

## Regra R15 — Clássico entrega a configuração anterior exata

**Origem:** RQ-01, RQ-07, RQ-11, P-03 e P-14.  
**Perfil:** padrão. **Técnica:** igualdade de configuração e regressão.

```gherkin
Funcionalidade: Preservar a aparência clássica
  Regra: Escolher clássico conserva a entrada anterior do pacote

    Esquema do Cenário: [CT-33] O clássico ignora os valores exclusivos do painel
      Dado configuração anterior conhecida de <tela> e aparência clássica com lado, cor, destaque e apoio preenchidos com sentinelas
      Quando a pessoa abre <tela> no mesmo contexto anterior
      Então o pacote recebe exatamente o objeto de configuração anterior, sem chave adicional nem valor alterado
      E nenhum texto ou marcação do painel de aparência é acrescentado à tela

      Exemplos:
        | tela | partição |
        | login | login |
        | cadastro aberto | registro aberto |
        | cadastro por convite | convite |
        | recuperar senha | recuperação |
        | redefinir senha | redefinição |
        | confirmação de e-mail | confirmação |
        | dois fatores | segundo fator |
        | bloqueio de sessão | bloqueio |
```

A referência é capturada na versão anterior, com os mesmos dados e contexto. Não se calcula o esperado pelo ramo clássico novo.

CT-B06 preserva a execução dos testes de aparência existentes. Sua prova não depende de uma pergunta ainda aberta nem de aprovar uma captura da implementação nova.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M62 | Clássico acrescenta chave, hook ou valores da aparência à configuração | CT-33 | Objeto anterior exato e sentinelas ausentes da tela |

Os outros mutantes da regra, M46–M47, estão no 05. M47 também é discriminado por CT-33.

## Regra R16 — Links externos restritos ao próprio kit

**Origem:** RQ-12 e Q9.  
**Perfil:** mínimo. **Técnica:** inspeção genérica por canal.

```gherkin
Funcionalidade: Restringir referências externas
  Regra: O material novo usa somente links externos do próprio kit

    Esquema do Cenário: [CT-24] Links externos respeitam a lista permitida sem nomes privados
      Dado corpus novo ou alterado de <canal> e a lista pública permitida do próprio kit
      Quando a pessoa responsável extrai e resolve os destinos externos de links no corpus
      Então todo destino externo pertence a github.com/gsferro, raw.githubusercontent.com/gsferro ou gsferro.github.io e nenhum destino externo fica fora dessa lista

      Exemplos:
        | canal | partição |
        | documentação pt e en e READMEs pt e en | arquivos |
        | páginas novas e site renderizado afetado | HTML e metadados com links |
```

A verificação analisa URLs, sem procurar nomes privados:

- `github.com` e `raw.githubusercontent.com` (as imagens do próprio repositório, como no resto da documentação): somente o proprietário `gsferro`, no limite do segmento; `/gsferro-outro` não passa. *(alterado em 2026-11-13: capturas da doc ficam no repositório do kit)*
- `gsferro.github.io`: host exato; subdomínio ou sufixo parecido não passa.
- URLs relativas do próprio site são locais. Links externos relativos ao protocolo também são resolvidos e verificados.
- Credenciais, texto de âncora e query não podem disfarçar um host externo.
- Âncoras locais e links de contato não são referências a sites de projetos e não são convertidos em exceções para hosts externos.

**Complemento obrigatório pela sessão:** busca privada por nomes, URLs e variantes de referências vedadas, em documentação e site, com resultado registrado no `03` **sem reproduzir esses nomes**. CT-24 não afirma provar ausência de menções textuais privadas sozinho.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M48 | README inclui link para projeto externo | CT-24 | Zero destino externo fora da lista |
| M49 | Link externo permanece em metadados ou crédito do site | CT-24 | Mesma restrição em links do HTML e metadados afetados |

## Checklist de Taxonomia

| Item | Cenário ou delimitação | Grupo |
|---|---|---|
| IDOR horizontal | CT-17–20 | G1/G2 |
| Autorização exercida na operação | CT-19 | G1 |
| Autorização fora da UI | CT-17 por HTTP | G2 |
| Idempotência no agregado | CT-12/13; preview repetido em CT-16 | G1/G2 |
| Concorrência | Nenhuma política nova de resolução entre saves foi determinada; não se certifica corrida nem se inventa precedência | — |
| Fronteira de escrita | CT-28/29: ação de salvar, recusa e nenhuma gravação | G1/G2 |
| Domínio condicionado | CT-02/05/28/29/33; clássico não recebe configuração do painel | G1/G2 |
| Estado × operação | Matriz de 20 células; edição inativa CT-34 e leitura pública CT-07 | G1/G2 |
| Cardinalidade de destinatários | CT-16 usa conta destinatária real; zero envio autorizado pelo preview | G2 |
| Ausente, vazio e espaços | CT-30/31; UUID ausente/nulo CT-18 | G1/G2 |
| Paginação/ordenação | Não se aplica: nenhuma listagem nova no recorte | — |
| Tempo e expiração | CT-18 força rascunho expirado sem escolher TTL | G2 |
| Timezone/DST | Não se aplica aos resultados determinados: nenhum prazo civil foi especificado | — |
| Unicode/teto/espaços | CT-03/28–30 e CT-B07 | G1/G2/G4 |
| Unicidade e soft delete | Não há unicidade nova; organização inexistente/inativa em CT-07 | G2 |
| CRUD combinado | CT-01/02/10/13/29/32/34; não se cria contrato de retry de criação | G1 |
| Mass assignment — organização | CT-20 | G1 |
| Mass assignment — configuração padrão | CT-20 | G1 |
| Mass assignment — rascunho | CT-18/20 | G1/G2 |
| IDOR — organização | CT-17/19 | G1/G2 |
| IDOR — configuração padrão | CT-17/19 | G1/G2 |
| IDOR — rascunho | CT-18 | G2 |
| Upload | CT-35 `@obsoleto` (Adendo 1); CT-43 restringe o único upload; CT-51/CT-52 gravam e pré-visualizam; CT-B04 prova uso da identidade existente | G1/G4 |
| Precisão monetária | Não se aplica: nenhum valor monetário | — |
| Superfície Livewire | CT-20 cobre domínio e tipo nos pontos do 02 | G1 |
| Estado usado como caminho | CT-15/20 | G1/G2 |
| Método público/ação direta | CT-19/20/28 | G1/G2 |
| Discriminante nulo | CT-07/18; nulo não torna rascunho global | G2 |
| Saída do erro | Login utilizável CT-22 e editor autorizado CT-01 | G1/G2 |
| Saída escapada contextual | CT-06 e CT-B08 | G2/G4 |
| Atualização real | CT-21/27, quatro cenários do checklist | execução pela sessão |
| Documentação e idiomas | CT-23/24 | G3 |

Não se presume tabela física nova para rascunhos nem mecanismo de cache. A correspondência dos ataques com os pontos públicos finais deve ser conferida na implementação dos testes.

## Cogitado e cortado

| Candidato | Motivo |
|---|---|
| Simular `kit:update` em teste Pest como prova de atualização | A prova acordada são os quatro cenários reais executados pela sessão |
| Validar toda escrita arbitrária de model | P-10 fixa a fronteira nas ações de salvar das telas |
| Repetir toda validação no browser | CT-28/29 provam a recusa; CT-B07 fica apenas pelo risco de UTF-16 no navegador |
| Tratar escolha de painel como tela vestida | Fora por P-02 |
| Aceitar qualquer recusa para preview | P-13 já fixa 403, 404 e fallback login |
| Comparar clássico com baseline criada da implementação nova | P-14 exige a configuração anterior e os testes existentes |
| Gravar nomes privados em fixture de CT-24 | Q9 determina teste genérico e busca privada pela sessão |
| Criar política de saves concorrentes ou retry de criação | Não determinada pelo 00 e não necessária para fechar estes achados |
| Casos novos de log | Não há RQ de auditoria |

## Índice de Cenários

Destinos automatizados (implementados):

- **K:** `tests/Kit/AparenciaDasTelasDeAcessoTest.php`
- **T:** `tests/Tenancy/AparenciaDasTelasDeAcessoTenancyTest.php`
- **A:** `tests/Kit/AparenciaDasTelasAdendoTest.php` (Adendo 1)
- **P:** `tests/Kit/AparenciaDasTelasPorTelaTest.php` (Adendos 2 e 3)
- **E:** `tests/Kit/AparenciaDasTelasDeAcessoEntregaTest.php`
- **B:** `tests/Browser/AparenciaDasTelasDeAcessoTest.php` e correspondente em `tests/BrowserTenancy`

| ID | Cenário | Regra | Técnica | Grupo | Costura/execução | Destino | Mata |
|---|---|---|---|---|---|---|---|
| CT-01 | Editor autorizado | R1 | EP | G1 | componente Livewire/Filament | K/T | M1 |
| CT-02 | Salvamento integral | R1 | EP | G1 | componente Livewire/Filament | K/T | M2 |
| CT-03 | Tetos válidos | R1 | BVA | G1 | componente Livewire/Filament | K/T | M3 |
| CT-04 | Layout inválido | R2 | EP | G2 | Pest feature HTTP | K/T | M4 |
| CT-05 | Fallback por campo | R2 | EP/metamórfica | G2 | Pest feature HTTP | K/T | M5–6, M57 |
| CT-06 | Escape contextual | R3 | EP | G2 | Pest feature HTTP | K/T | M7–9 |
| CT-07 | Contexto e convite | R4 | decisão | G2 | Pest feature HTTP | K/T | M10–12, M63 |
| CT-08 | Erro de login | R4 | estado × evento | G2 | Pest feature HTTP | T | M13 |
| CT-09 | Aparência não autoriza | R4 | papel × ação | G2 | Pest feature HTTP | T | M14 |
| CT-10 | Cópia integral | R5 | estado × evento | G1 | componente Livewire/Filament | T | M16 |
| CT-11 | Herança e independência | R5 | estado × evento | G2 | Pest feature HTTP | T | M15, M19 |
| CT-12 | Repetição própria | R5 | idempotência | G1 | componente Livewire/Filament | T | M17 |
| CT-13 | Volta e descarte | R5 | transição | G1 | componente Livewire/Filament | T | M18 |
| CT-14 | Rascunho não salvo | R6 | efeitos | G2 | Pest feature HTTP | K/T | M20–21 |
| CT-15 | Tela desconhecida | R6 | EP | G2 | Pest feature HTTP | K/T | M22, M67 |
| CT-16 | Preview inerte | R6 | efeitos | G2 | Pest feature HTTP | K/T | M23–24 |
| CT-17 | Autorização e 403 | R7 | papel × ação | G2 | Pest feature HTTP | K/T | M25, M54, M68 |
| CT-18 | Dono, ausência e expiração | R7 | IDOR/EP | G2 | Pest feature HTTP | K/T | M26, M69 |
| CT-19 | Ação negada | R7 | autorização | G1 | componente Livewire/Filament | K/T | M27 |
| CT-20 | Hidratação hostil | R7 | EP/tipos | G1 | componente Livewire/Filament | K/T | M28–29 |
| CT-21 | Atualização preserva clássico | R8 | regressão | — | **execução pela sessão (cenários do checklist)** | checklist de release, quatro cenários | M30 |
| CT-22 | Login preservado | R8 | EP | G2 | Pest feature HTTP | K/T | M31–32 |
| CT-23 | Quatro canais documentais | R9 | EP | G3 | Pest feature HTTP | E | M33–34, M64 |
| CT-24 | Links externos permitidos | R16 | EP | G3 | Pest feature HTTP | E | M48–49 |
| CT-25 | Erro de cadastro | R4 | estado × evento | G2 | Pest feature HTTP | T | M50 |
| CT-26 | Erro de desbloqueio | R4 | estado × evento | G2 | Pest feature HTTP | T | M51 |
| CT-27 | Regressão após atualização real | R8 | regressão | — | **execução pela sessão (cenários do checklist)** | checklist e suítes Kit/Tenancy | M52 |
| CT-28 | Teto na ação de salvar | R1 | BVA | G2 | Pest feature HTTP | K/T | M53 |
| CT-29 | Recusa sem salvamento parcial | R1 | EP/BVA | G1 | componente Livewire/Filament | K/T | M55–57 |
| CT-30 | Normalização e pontos de código | R1 | BVA/EP | G1 | componente Livewire/Filament | K/T | M58–59 |
| CT-31 | Defaults literais | R1 | EP | G2 | Pest feature HTTP | K/T | M60 |
| CT-32 | Nova cópia após descarte | R5 | ciclo de volta | G1 | componente Livewire/Filament | T | M61 |
| CT-33 | Configuração clássica anterior | R15 | igualdade | G2 | Pest feature HTTP | K/T | M62, M47 |
| CT-34 | Edição de organização inativa | R5 | estado × evento | G1 | componente Livewire/Filament | T | M70 |
| ~~CT-35~~ | Sem upload novo — `@obsoleto` pelo Adendo 1 (RQ-20), substituído por CT-43 | R1 | EP | G1 | componente Livewire/Filament | — | M66 |
| CT-36 | Bloqueio usa a arte | R17 | estado × evento | G2 | componente Livewire/Filament | T | — |
| CT-37 | `$wire` sem cor/logo fora do painel | R17 | EP | G2 | componente Livewire/Filament | T | — |
| CT-38 | Preview exige o admin | R17 | papel × ação | G2 | Pest feature HTTP | K | — |
| CT-39 | Reautorização no update | R17 | papel × ação | G2 | componente Livewire/Filament | T | — |
| CT-40 | Texto escuro sobre cor clara | R17 | BVA | G2 | Pest feature HTTP | K | — |
| CT-41 | Toggle sem recarregar | R17 | estado × evento | G1 | componente Livewire/Filament | T | — |
| CT-42 | Clássico próprio × padrão em painel | R17 | estado × evento | G2 | Pest feature HTTP | T | — |
| CT-B01 | Computador com textos no teto | R10 | geometria | G4 | browser | B | M35–36, M65 |
| CT-B02 | Celular e dobra | R11 | geometria | G4 | browser | B | M37–38 |
| CT-B03 | Teclado e foco | R12 | teclado | G4 | browser | B | M39–40 |
| CT-B04 | Opções e defaults visuais | R13 | EP/estilo | G4 | browser | B | M41–43 |
| CT-B05 | Preview interativo | R14 | JavaScript | G4 | browser | B | M44–45 |
| CT-B06 | Aparência clássica existente | R15 | regressão | G4 | browser | B e testes existentes | M46–47 |
| CT-B07 | Entrada Unicode no navegador | R1 | BVA | G4 | browser | B | M71 |
| CT-B08 | Aspas sem execução | R3 | escape contextual | G4 | browser | B | M72 |
| CT-43 | Único upload restrito | R23 | EP | G1 | componente Livewire/Filament | K/T | — |
| CT-44 | Vocabulário dos campos novos | R18 | EP, BVA | G1 | Pest unitário | A | — |
| CT-45 | Caminho da imagem restrito | R18 | EP | G1 | Pest unitário | A | — |
| CT-46 | Aparência antiga igual | R18 | regressão | G2 | Pest feature HTTP | A | — |
| CT-47 | Largura vira mediaSize | R19 | EP, BVA | G2 | Pest feature HTTP | A | — |
| CT-48 | Destaque central | R20 | EP, falha de recurso | G2 | Pest feature HTTP | A | — |
| CT-49 | Rede, movimento e cor | R21 | EP | G2 | Pest feature HTTP | A | — |
| CT-50 | Destaque só no painel | R22 | papel × ação | G1 | componente Livewire | A | — |
| CT-51 | Editor grava campos novos e upload | R23 | rastreio de escrita | G1 | componente Livewire/Filament | A | — |
| CT-52 | Preview com imagem não salva | R23 | EP × efeitos | G2 | Pest feature HTTP | A | — |
| CT-B09 | Celular só com a marca | R24 | visibilidade | G4 | browser | B | — |
| CT-B10 | Movimento com pausa | R21 | estado × evento | G4 | browser | B | — |
| CT-B11 | Largura na tela | R19 | geometria | G4 | browser | B | — |
| CT-53 | Campos novos na aparência da organização | R22 | estado × evento | G2 | Pest feature HTTP | T | — |
| CT-54 | Campos novos nas outras telas | R19 | EP | G2 | Pest feature HTTP | A | — |
| CT-55 | Um aviso de imagem ausente por hora | R18 | estado × evento | G2 | Pest feature HTTP | A | — |
| CT-56 | Saída pública sem imagem/cor fora de uso | R22 | papel × ação | G1 | componente Livewire | A | — |
| CT-57 | Markdown hostil inerte | R27 | conteúdo hostil | G1 | Pest feature HTTP | P | — |
| CT-58 | Cada tela com a sua aparência | R26 | decisão | G2 | Pest feature HTTP | P | — |
| CT-59 | Tela sem própria segue o login | R26 | EP | G2 | Pest feature HTTP | P | — |
| CT-60 | Clássico explícito por tela | R26 | estado × evento | G2 | Pest feature HTTP | P | — |
| CT-61 | JSON sem `telas` igual nas três | R26 | regressão | G2 | Pest feature HTTP | P | — |
| CT-62 | Família pela classe da página | R26 | EP | G1 | Pest unitário | P | — |
| CT-63 | Editor: cópia do login e descarte | R26 | rastreio de escrita | G1 | componente Livewire | P | — |
| CT-64 | Preview por tela | R26 | EP | G2 | Pest feature HTTP | P | — |
| CT-65 | Formatação acordada chega | R27 | EP | G2 | Pest feature HTTP | P | — |
| CT-66 | Teto visível e bruto | R27 | BVA | G1 | componente Livewire | P | — |
| CT-67 | Texto legado igual antes e depois | R27 | regressão | G2 | Pest feature HTTP | P | — |
| CT-68 | Gradiente só com peças fechadas | R28 | valor hostil | G2 | Pest feature HTTP | P | — |
| CT-69 | Cor primária grava vazio | R28 | rastreio de escrita | G1 | componente Livewire | P | — |
| CT-70 | Gradiente não sai fora do painel | R28 | papel × ação | G1 | componente Livewire | P | — |
| CT-71 | Login social no painel | R29 | EP por painel | G2 | Pest feature HTTP | P | — |
| CT-72 | Anti-robô no painel | R29 | EP por tela | G2 | Pest feature HTTP | P | — |
| CT-73 | Organização com aparência por tela | R26 | estado × evento | G2 | Pest feature HTTP | T | — |
| CT-74 | Cópia do login com imagem e cor | R26 | rastreio de escrita | G1 | componente Livewire | P | — |
| CT-75 | Tela própria com layout inválido = clássico | R26 | EP | G2 | Pest feature HTTP | P | — |
| CT-76 | Saída pública sem cores do gradiente fora dele | R28 | papel × ação | G1 | componente Livewire | P | — |
| CT-77 | Gradiente vazio com primária hex | R28 | EP | G2 | Pest feature HTTP | P | — |
| CT-78 | Preview: imagem não salva herdada do login | R26 | estado × evento | G2 | componente Livewire | P | — |
| CT-B16 | Arte fora da faixa do celular | R30 | BVA | G4 | browser | B | — |
| CT-B17 | Social e anti-robô visíveis no painel | R29 | geometria | G4 | browser | B | — |
| CT-B12 | Destaque sem cobrir os textos | R20 | geometria | G4 | browser | B | — |
| CT-B13 | Reduzir movimento | R21 | estado × evento | G4 | browser | B | — |
| CT-B14 | Sem animação no celular | R21 | estado × evento | G4 | browser | B | — |
| CT-B15 | Botão de pausar inteiro | R21 | geometria | G4 | browser | B | — |

## Regra R17 — Achados do step 9 e do quality gate

### Achados do step 9 (revisão do diff) e do gate

Derivados pela sessão a partir das P-15..P-18 registradas no `00` (degradação: a `feature-test-design` não foi reinvocada para quatro cenários pontuais; ver `03`).

```gherkin
# language: pt
Funcionalidade: Achados da revisão do diff

  Regra: O fundo de arte é a arte da identidade em toda tela

    Cenário: [CT-36] Bloqueio com organização com logo usa a arte, não a logo, no fundo do painel
      Dado tenancy ligada, a organização A ativa com logo própria e a aparência padrão em painel com fundo "arte"
      E uma sessão bloqueada dentro de A
      Quando a tela de bloqueio é aberta
      Então a imagem de fundo do painel é a arte da identidade
      E a logo de A aparece só no selo do painel
      # Origem: P-15 · Mata: fundo do painel = logo ampliada

  Regra: Nada além do que a tela mostra sai para o navegador

    Esquema do Cenário: [CT-37] Chamada direta ao componente não revela cor nem logo que a tela não mostra
      Dado tenancy ligada, a organização A ativa com cor e logo próprias
      E a aparência efetiva <aparência>
      Quando um visitante abre o login com ?org=A e chama diretamente as consultas de cor e de logo do componente
      Então a cor devolvida é <cor> e a logo devolvida é <logo>

      Exemplos:
        | aparência | cor | logo |
        | clássico | vazia | vazia |
        | painel com logo desligada | a cor do painel | vazia |
        | painel com logo ligada | a cor do painel | a de A |
      # Origem: P-16 · Mata: métodos públicos sem gate

  Regra: O preview exige acesso ao admin e reautoriza a cada atualização

    Cenário: [CT-38] Conta sem acesso ao admin não abre o preview
      Dado uma conta que só entra no painel app, com um rascunho próprio no cache
      Quando ela abre o preview com o próprio rascunho
      Então a resposta é 403 (ou redirecionamento ao login do admin, sem o conteúdo do rascunho)
      # Origem: P-17

    Cenário: [CT-39] Permissão revogada com o preview aberto deixa de mostrar o escopo no próximo update
      Dado uma administradora com o preview aberto da organização A (rascunho com frase "SEGREDO A")
      Quando a permissão de editar organizações é revogada e o componente recebe um update
      Então a resposta do update não contém "SEGREDO A" e a aparência resolvida é vazia
      # Origem: P-17

  Regra: Texto legível sobre cor clara

    Esquema do Cenário: [CT-40] Cor clara deixa o texto do painel escuro
      Dado a aparência padrão em painel com fundo de cor <cor>
      Quando o login é aberto
      Então o painel <tem> a marca de fundo claro

      Exemplos:
        | cor | tem |
        | #ffffff | tem |
        | #f5f5dc | tem |
        | #123456 | não tem |
        | #000000 | não tem |
        | #00aaff | tem |
        | #d97706 | tem |
        | #1d4ed8 | não tem |
        | (vazia: primária default, âmbar 600) | tem |
        | (vazia: primária da instalação `#ff0`) | tem |
        | (vazia: primária Fuchsia 600) | não tem |
        | (vazia: primária Pink 600) | não tem |
        | (vazia: primária Sky 600) | tem |
      # Origem: P-18 · Mata: limiar invertido, cor clara com texto branco, corte fixo em cor média (QA-04), `#rgb` não entendido (QA-10), aproximação de luminância (QA-11)

  Regra: Voltar à padrão e sair dela sem recarregar a página

    Cenário: [CT-41] Desligar o toggle sem recarregar começa de cópia nova da padrão
      Dado a organização A com aparência própria A e a padrão P
      Quando a editora liga "usar a padrão", salva e, na mesma página, desliga e salva
      Então a aparência de A é igual a P
      # Origem: P-12 (CX-03)

  Regra: O clássico próprio não é "usa a padrão"

    Cenário: [CT-42] Organização com o clássico próprio não herda o painel da padrão
      Dado a aparência padrão em painel e a organização A ativa com aparência própria no clássico
      Quando o login é aberto sem organização e com ?org=A
      Então sem organização o painel aparece e com ?org=A não aparece
      # Origem: P-07 (QA-08 do gate) · Mata: `['layout' => 'classico']` da organização tratado como nulo
```

## Revisão adversarial e limites

A tabela abaixo fecha **os 23 IDs de `adversario.md` fornecido nesta revisão**.

| Achado | Fechamento | Asserção discriminante |
|---|---|---|
| ADV-01 | CT-29, CT-28 | 81/161 geram erro; não há truncamento salvo |
| ADV-02 | CT-29 | Erro no campo e fotografia integral preservada, inclusive outra aba; criação não cria organização |
| ADV-03 | CT-05, CT-29 | Cor com sufixo recebe fallback na leitura e recusa no formulário |
| ADV-04 | CT-06, CT-B08 | Texto literal sem atributo ativo nem execução JavaScript |
| ADV-05 | CT-30 | Trim/colapso antes do teto; frase só com espaços não produz bloco |
| ADV-06 | CT-30, CT-B07 | 81 pontos em poucos grafemas recusados; 80 emojis de um ponto aceitos pelo navegador |
| ADV-07 | CT-31, CT-B04 | Direita, cor primária, sem frases, logo e alternador ligados |
| ADV-08 | CT-13, CT-32 | Voltar à própria após descarte copia P2, sem recuperar A |
| ADV-09 | CT-33, CT-B06 | Objeto anterior exato; painel e sentinelas não aparecem no clássico |
| ADV-10 | CT-07, CT-25, CT-33 | Cadastro por convite recebe a aparência aplicável; escolha de painel fica fora |
| ADV-11 | CT-21, CT-27 | **Execução pela sessão dos quatro cenários do checklist**, incluindo tenancy com organizações; migração e logins utilizáveis |
| ADV-12 | CT-23 | Documentação pt/en e READMEs pt/en localizam o contrato |
| ADV-13 | CT-B01 | 1440×900 com 80/160, textos contidos e sem sobreposição ou rolagem horizontal |
| ADV-14 | CT-35, CT-B04 | Sem upload novo; arte/logo da identidade existente |
| ADV-15 | CT-15 | Tela desconhecida, inclusive caminho hostil, mostra login com A |
| ADV-16 | CT-17 | Escopo negado/revogado responde exatamente 403 sem rascunho |
| ADV-17 | CT-18 | Ausente, nulo, inexistente, expirado e alheio respondem exatamente 404 |
| ADV-18 | CT-28 | Ação de salvar recusa 81/161 com erro e nenhuma gravação |
| ADV-19 | CT-24 | Oráculo genérico de URLs; busca privada pela sessão no `03`, sem nomes |
| ADV-20 | CT-04, CT-33 | “Clássico” compara a configuração anterior exata |
| ADV-21 | Cabeçalho e mapa; CT-28–34 e CT-B01/02/06 | P-10..P-14 rastreadas; suspensões decididas removidas |
| ADV-22 | CT-21, CT-27 | Regressão não depende de Q?7; Q10 já decidiu a prova |
| ADV-23 | CT-34 | Organização inativa aceita a edição autorizada e persiste os valores |

**Fechado no desenho não significa executado.** Nenhum MSI, resultado verde, captura aprovada ou atualização bem-sucedida é alegado.

Como foram acrescentados cenários, a skill exige uma re-revisão independente do conjunto atualizado. Este retorno é o fechamento pelo derivador; a sessão deve encaminhá-lo ao revisor independente e respeitar o teto de duas rodadas do ciclo. A afirmação histórica de “nenhum achado novo” do 04 anterior não certifica este conjunto revisado.

Não restaram perguntas necessárias para fechar ADV-01..ADV-23. Não foram criadas políticas adicionais de concorrência, retry, TTL ou conformidade normativa.

References lidas da skill solicitada: `template-04.md` — passos 2/6/7 e formato; `template-05.md` e `pest-plugin-browser.md` — arquivo 05; `tecnicas-por-regra.md` — passo 3; `taxonomia-de-defeito.md` — passo 4; `gherkin.md` — passo 5; `armadilhas-de-api.md` — passos 5/7; `escolha-de-camada.md` — passos 2/7; `mutation-testing.md` — passo 6; `revisao-adversarial.md` — fechamento. A orientação de `testing-best-practices` para ler implementação foi afastada pela restrição explícita desta derivação cega.

## Adendo 1 — tamanho do painel, destaque central, ilustração e celular só com a marca

Derivados pela **sessão** a partir do Adendo 1 (RQ-17..RQ-24) e das P-19..P-24 do `00`. Degradação
declarada no `03`: a `feature-test-design` não derivou às cegas estes cenários; a revisão adversarial
independente (`fw-adversario-ct`) roda sobre eles no step 9 do adendo.

| Regra | Área/perfil | Origem | Técnica | Cenários |
|---|---|---|---|---|
| R18 — Vocabulário dos campos novos | tolerância/padrão | RQ-20..RQ-23, P-19..P-21, P-23, P-24 | EP, BVA | CT-44, CT-45, CT-46 |
| R19 — Disposição | apresentação/padrão | RQ-23, P-19 | EP, BVA, geometria | CT-47, CT-54, CT-B11 |
| R20 — Destaque central | apresentação/padrão | RQ-20, P-20 | EP, falha de recurso, geometria | CT-48, CT-B12 |
| R21 — Ilustração | apresentação/padrão | RQ-21, RQ-22, P-21 | EP, estado × evento | CT-49, CT-B10, CT-B13, CT-B14, CT-B15 |
| R22 — Só o que a tela mostra | segurança/completo | P-06, P-16, P-20 | papel × ação | CT-50, CT-53, CT-56 |
| R23 — Editor, upload e preview | segurança/completo | RQ-19, RQ-20, P-08, P-24 | EP, rastreio de escrita | CT-43, CT-51, CT-52 |
| R24 — Celular só com a marca | apresentação/padrão | RQ-24, P-22 | EP, visibilidade | CT-B09, CT-B05 (linhas de celular) |

```gherkin
# language: pt
Funcionalidade: Adendo 1 da aparência das telas de acesso

  Regra: R18 — Campo novo fora do vocabulário volta ao padrão do campo

    Esquema do Cenário: [CT-44] Valor inválido de campo novo vale o padrão; valor válido é preservado
      Dado a aparência P com <campo> = <valor>
      Quando a aparência é lida
      Então <campo> vale <lido> e os demais campos de P continuam iguais
      Exemplos:
        | campo | valor | lido |
        | tamanho_do_painel | 25, 75, 52, -50, "grande" | 50 |
        | tamanho_do_painel | 30, 70, "45" | 30, 70, 45 |
        | destaque | "video" | nenhum |
        | tamanho_do_destaque | "enorme" | medio |
        | ilustracao | "estrelas" | nenhuma |
        | movimento | "sim" / false | true / false |
        | cor_da_ilustracao | "red; background: url(x)" / "#ABCDEF" | vazia / #abcdef |
      # Origem: P-19..P-21, P-04 · Mata: limite aberto, passo ignorado, CSS na cor

    Esquema do Cenário: [CT-45] Caminho de imagem fora do diretório das telas de acesso é descartado
      Dado a aparência P com imagem_do_destaque = <caminho>
      Quando a aparência é lida
      Então a imagem vale <lido>
      Exemplos:
        | caminho | lido |
        | telas-de-acesso/abc.png | o mesmo |
        | estado do FileUpload {uuid: telas-de-acesso/abc.webp} | telas-de-acesso/abc.webp |
        | telas-de-acesso/../.env · kit/logo.png · telas-de-acesso/x.svg · /etc/passwd · URL · subpasta · quebra de linha no fim · dois arquivos | vazia |
      # Origem: P-24 · Mata: prefixo sem âncora, `..`, SVG, `$` que aceita `\n`

    Cenário: [CT-46] Aparência gravada antes do adendo continua igual
      Dado uma aparência painel gravada só com os campos de antes do adendo
      Quando o login é aberto
      Então o mediaSize é 50% e não há destaque, ilustração nem botão de pausar
      # Origem: P-23, RQ-07

  Regra: R19 — A largura escolhida divide a tela

    Esquema do Cenário: [CT-47] A largura vira o mediaSize do pacote, dos dois lados
      Dado a aparência P com largura <n> e formulário à <lado>
      Quando o login é aberto
      Então a configuração tem mediaSize "<n>%" e o HTML tem "--media-size: <n>%" e a classe do lado
      Exemplos:
        | n | lado |
        | 30 | direita |
        | 70 | esquerda |
        | 55 | direita |
      # E com o clássico, a configuração é a anterior exata, mesmo com largura gravada
      # Origem: P-19, P-14 · Mata: largura fixa, largura no clássico

  Regra: R20 — Destaque central

    Esquema do Cenário: [CT-48] O meio do painel mostra o destaque escolhido
      Dado a aparência P com destaque <tipo>
      Quando o login é aberto
      Então <resultado>
      Exemplos:
        | tipo | resultado |
        | imagem gravada, tamanhos pequeno/médio/grande | a imagem do disco, com a classe do tamanho |
        | imagem gravada que sumiu do disco | nada no meio, frase intacta e aviso no log |
        | logo, com logo | a logo da identidade |
        | logo, sem logo | o nome da aplicação |
        | nenhum, com imagem gravada | nada no meio |
      # Origem: P-20 · Mata: imagem ausente derruba a tela, destaque "nenhum" ignorado

  Regra: R21 — Ilustração

    Cenário: [CT-49] A rede desenha os pontos e as ligações, decorativa
      Dado a aparência P com ilustração "rede"
      Quando o login é aberto
      Então o SVG tem aria-hidden, um círculo por ponto e uma linha por ligação do desenho fixo
      # E com movimento ligado há o componente Alpine e o botão de pausar, nascido escondido; desligado, nenhum dos dois
      # E a cor própria vai para o SVG; em branco, nenhuma cor própria; com "nenhuma", não há rede
      # Origem: P-21 · Mata: botão visível sem JavaScript, movimento sem pausa, cor sem validação

  Regra: R22 — O componente não entrega o que a tela não mostra

    Esquema do Cenário: [CT-50] Chamada direta ao destaque devolve só o que a tela mostra
      Dado a aparência <caso> com imagem gravada
      Quando o componente de login é chamado diretamente
      Então o destaque devolvido é <tipo>
      Exemplos:
        | caso | tipo |
        | clássico | nenhum |
        | painel sem destaque | nenhum |
        | painel com imagem | imagem |
        | painel com logo | logo |
      # Origem: P-16 estendida ao destaque

  Regra: R23 — Editor, upload e preview

    Esquema do Cenário: [CT-43] O único upload da aparência da organização é restrito
      Dado pessoa autorizada a <criar ou editar> organização, com aparência própria
      Quando o formulário é aberto
      Então há exatamente um upload na aparência, o da imagem do destaque, aceitando só PNG, JPEG e WebP no disco público, em telas-de-acesso
      # Origem: P-20, P-24 · substitui o CT-35

    Cenário: [CT-51] O editor grava os campos novos e o upload no diretório certo
      Dado o editor da instalação com largura 35, destaque imagem (PNG enviado), tamanho grande, rede parada e cor #00ff88
      Quando a pessoa salva
      Então a aparência gravada tem esses valores e a imagem existe em telas-de-acesso/ no disco público
      # E um SVG enviado é recusado com erro no campo e nada é gravado
      # Origem: RQ-19..RQ-22, P-20

    Cenário: [CT-52] O preview mostra a imagem enviada e ainda não salva
      Dado o editor com destaque imagem e um PNG enviado e não salvo
      Quando o preview é aberto
      Então a imagem do destaque vem da URL temporária assinada do Livewire, nada foi gravado nem copiado para o disco público
      # E um SVG não salvo não vira imagem temporária
      # Origem: P-08, P-20
```

### Acréscimos da revisão do diff do adendo (step 9)

Derivados pela sessão a partir dos achados confirmados (ver `03` › Revisão do Diff — Adendo 1).

```gherkin
# language: pt
Funcionalidade: Achados da revisão do adendo

  Regra: R22 — Campos novos da organização e saída pública

    Cenário: [CT-53] A aparência própria da organização leva largura, destaque e rede ao login dela
      Dado tenancy ligada, a padrão P (largura 60, sem rede) e a organização A ativa, com logo própria e aparência própria de largura 70, destaque logo e rede
      Quando o login é aberto com ?org=A e sem organização
      Então com ?org=A o mediaSize é 70%, há rede e o destaque mostra a logo de A; sem organização, 60% e sem rede
      # Origem: P-06 × P-19..P-21 (ADV-A4, ADV-A11)

    Cenário: [CT-56] A aparência devolvida pelo componente não traz imagem nem cor que a tela não usa
      Dado destaque "nada" com imagem gravada e ilustração "nenhuma" com cor gravada
      Quando o componente de login é chamado diretamente
      Então a imagem e a cor devolvidas são vazias, e o gravado continua inteiro
      # Origem: P-16 (CX-A4)

  Regra: R19 — As outras telas

    Esquema do Cenário: [CT-54] A largura, o destaque e a rede valem nas outras telas
      Dado largura 70, destaque imagem e rede
      Quando <tela> é aberta
      Então o HTML tem "--media-size: 70%", a imagem e a rede
      Exemplos:
        | tela |
        | recuperar senha |
        | cadastro |
      # Origem: P-02 (ADV-A3)

  Regra: R18 — Aviso sem volume controlado pelo visitante

    Cenário: [CT-55] Imagem ausente gera um aviso, não um por visita
      Dado destaque imagem com arquivo ausente
      Quando o login é aberto duas vezes
      Então o aviso "Imagem do destaque ausente" é registrado uma vez
      # Origem: SEC-A1
```

## Adendos 2 e 3 — por tela, textos formatados, cor/gradiente, celular com arte, social e anti-robô

Derivados pela sessão a partir dos Adendos 2 e 3 (RQ-25..RQ-33) e das P-25..P-30; o desenho foi
julgado às cegas pelo Codex antes (ver `03` › Despachos). Mudaram oráculos antigos, cada um pela
premissa que o substitui: CT-06 passa a fixar o formato `texto` (o legado) e o `markdown` vai para o
CT-57; CT-30 (colapso) mede o texto visível e grava o Markdown como foi escrito (P-27); CT-43 conta
um upload por tela (P-25); CT-B05/07/08 escrevem no editor Markdown.

```gherkin
# language: pt
Funcionalidade: Adendos 2 e 3

  Regra: R26 — Uma aparência por tela

    Cenário: [CT-58] Login, cadastro e esqueci a senha usam cada um a sua aparência
      Dado login com formulário à direita e cadastro e recuperação próprios à esquerda
      Quando as três telas são abertas
      Então cada uma mostra a sua frase e o seu lado

    Esquema do Cenário: [CT-59] Tela sem aparência própria segue o login
      Dado a recuperação <estado>
      Quando a tela de recuperação é aberta
      Então ela mostra a frase e o lado do login
      Exemplos:
        | estado |
        | sem a chave |
        | nula |
        | com "igual ao login" |
        | com valor inválido |

    Cenário: [CT-60] Clássico escolhido numa tela vale com o login em painel, e vice-versa
      Dado a recuperação no clássico e o login no painel (depois o contrário)
      Então cada tela segue o seu layout

    Cenário: [CT-61] Aparência gravada sem "telas" vale igual nas três telas

    Esquema do Cenário: [CT-62] A família de cada página vem da classe
      Exemplos:
        | página | família |
        | login, dois fatores, bloqueio, confirmação de e-mail | login |
        | cadastro (aberto e convite) | cadastro |
        | recuperar e redefinir senha | recuperacao |

    Cenário: [CT-63] O editor grava uma tela própria a partir da cópia do login
      Quando "Igual ao login" é desligado na aba Esqueci a senha
      Então os campos mostram a cópia do login; o lado alterado é gravado só na tela própria
      E religar "Igual ao login" e salvar remove a tela própria

    Esquema do Cenário: [CT-64] O preview mostra a aparência da tela escolhida
      Exemplos: login, cadastro e recuperar senha, cada um com a sua frase do rascunho

    Cenário: [CT-73] Organização com aparência própria por tela
      Então login e cadastro dela mostram cada um a sua; sem organização, a padrão sem mistura

  Regra: R27 — Textos formatados (Markdown seguro)

    Esquema do Cenário: [CT-57] Markdown hostil não deixa elemento executável, estilo nem imagem
      Exemplos: HTML cru com evento, imagem Markdown, link `javascript:`, título, `<style>`, `<iframe>`

    Esquema do Cenário: [CT-65] A formatação acordada chega à tela
      Exemplos: negrito → strong, itálico → em, tachado → del, link → a[href][rel]

    Esquema do Cenário: [CT-66] O teto mede o texto visível e há um teto bruto de 4 KiB
      Exemplos: 80 visíveis em negrito (aceita), 81 (recusa), link longo com 5 visíveis (aceita), bruto > 4 KiB (recusa)

    Cenário: [CT-67] Texto gravado antes aparece igual, também depois de salvo pelo editor

  Regra: R28 — Cor primária e gradiente

    Esquema do Cenário: [CT-68] O gradiente só usa cores validadas e um ângulo do vocabulário
      Exemplos: vertical, horizontal, direção inválida (diagonal), cor com CSS (primária), vazias (600 e 900)

    Cenário: [CT-69] "Cor primária" grava a cor vazia, e a tela usa a primária
    Cenário: [CT-70] O gradiente não sai pelo componente fora do painel

  Regra: R29 — Login social e anti-robô no painel (Adendo 3)

    Esquema do Cenário: [CT-71] O login social aparece no layout painel nos três painéis
    Esquema do Cenário: [CT-72] O anti-robô aparece no layout painel em login, cadastro e esqueci a senha
      E a chave secreta nunca sai
```

### Acréscimos da revisão do diff dos Adendos 2 e 3

```gherkin
# language: pt
Funcionalidade: Achados da revisão dos Adendos 2 e 3

  Regra: R26 — Cópia e herança

    Cenário: [CT-74] Desligar "igual ao login" com imagem gravada copia a imagem e a cor, e salva
    Esquema do Cenário: [CT-75] Tela própria com layout inválido cai no clássico
      Exemplos: layout desconhecido, layout ausente
    Cenário: [CT-78] No preview, a tela que segue o login usa a imagem ainda não salva do login

  Regra: R28 — Saída pública e paleta

    Cenário: [CT-76] O componente não devolve as cores do gradiente fora do gradiente
    Cenário: [CT-77] Com primária hex, o gradiente vazio vai do tom 600 ao 900 da mesma paleta
```
