# Casos de Teste de Browser — Aparência das telas de acesso

> Derivado do `00-requisito.md`: RQ-01..RQ-16, premissas vigentes P-01..P-14 e decisões de Q3..Q11. Adendo 1 (seções no fim): RQ-17..RQ-24 e P-19..P-24, derivados pela sessão *(alterado em 2026-11-14: QA-23)*. Adendos 2 e 3 (seção no fim): RQ-25..RQ-33 e P-25..P-31, derivados pela sessão. Oráculo atual: RQ-01..RQ-33 e P-01..P-31 *(alterado em 2026-11-14: QA-30)*.
>
> Cenários: 19 · Regras: 8 · Mutantes previstos: 23 · Sem matador: 0 *(2026-11-14, depois do CT-B19 (Adendo 4); os `grep -cE` de `feature-test-design/references/template-04.md` §Contagem do cabeçalho aplicados a este arquivo, com `\[CT-B[0-9]+\]` no padrão de cenário, 2026-11-14; as regras dos Adendos estão no `Regra:` do Gherkin, sem `## Regra` próprio; o sem matador é o M108, lacuna declarada do CT-B16)*
>
> Revisão adversarial dos Adendos 2 e 3: feita — ver o `04`
>
> Runtime proposto: Pest 5 com pest-plugin-browser. Costura **browser**, grupo **G4** do 04.
>
> CT-B01..CT-B06 preservados; CT-B07 e CT-B08 acrescentados pela revisão adversarial.
>
> Cenários propostos, sem execução, captura aprovada ou confirmação de seletores.
>
> **Escolha de painel fora do escopo, conforme P-02.**

## Pré-requisitos

- Executar `npm run build` e preparar as views com `view:cache`; `composer test:browser` incorpora esses preparativos.
- Executar em série, conforme a regra local.
- Para arquivo isolado, aquecer pelo kernel a mesma tela/painel antes do cronômetro do navegador.
- Autenticar editores antes de visitar; telas públicas usam visitante e sessão apropriada.
- Arranjar o painel dentro do cenário, imediatamente antes da visita.
- Isolar sessão, identidade e tema entre exemplos.
- Conferir o path antes do conteúdo.
- Esperar o estado observável; não usar espera fixa.
- Confirmar o ignore de `tests/Browser/Screenshots`; não publicar capturas automaticamente.
- Viewports determinados: **1440×900** e **390×844**.
- A referência do clássico é a configuração anterior entregue ao pacote e os testes de aparência existentes, conforme P-14. Não há suspensão por Q?6.

Comandos direcionados propostos:

```text
vendor/bin/pest tests/Browser/AparenciaDasTelasDeAcessoTest.php
vendor/bin/pest tests/BrowserTenancy/AparenciaDasTelasDeAcessoTest.php
```

## Seletores e medidas

| Elemento | Contrato de arnês proposto | Confirmado |
|---|---|---|
| Campos do editor | Chaves reais do formulário e labels acessíveis | |
| Aba de configurações | Aba adiada correspondente a telas de acesso | |
| Atualização do preview | Ação indicada no recorte de superfície | |
| Iframe | Título acessível e documento interno | |
| Marca e frases | Âncoras semânticas a confirmar | |
| Formulário | Formulário correspondente à tela visitada | |
| Rodapé | Rodapé real da tela, incluindo sua assinatura quando presente | |
| Alternador | Nome acessível traduzido e controle efetivamente operável | |

Seletores são dívida de arnês, não perguntas de comportamento.

Medir caixas por `getBoundingClientRect`, estilos por `getComputedStyle` e o documento interno do iframe. Presença no HTML não prova visibilidade, posição nem ausência de overflow.

Console limpo é apoio. No editor com ColorPicker dentro de Tabs, respeitar a exceção local documentada para ResizeObserver, preservando todos os oráculos funcionais.

## Regra R10 — Composição de computador

**Origem:** RQ-01, P-03 e P-14.  
**Perfil:** padrão. **Técnica:** EP e geometria.  
**Por que browser:** CSS aplicado determina posição, contenção e sobreposição.

```gherkin
# language: pt
Funcionalidade: Apresentar telas de acesso no computador
  Regra: Marca e formulário ocupam colunas utilizáveis

    Esquema do Cenário: [CT-B01] A composição respeita o lado e comporta os textos no teto
      Dado layout painel com destaque de 80 e apoio de 160 pontos de código, viewport 1440 por 900 e formulário do lado <lado>
      Quando o visitante abre o login
      Então formulário e marca ficam em colunas na ordem escolhida, sem sobreposição e com cantos arredondados na marca
      E os dois textos permanecem visíveis e contidos na marca sem invadir o formulário nem gerar rolagem horizontal

      Exemplos:
        | lado | partição |
        | esquerda | primeiro lado |
        | direita | segundo lado |
```

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Preparar os textos nos tetos e viewport | 80/160 após normalização; 1440×900 |
| 2 | Visitar e conferir path | Formulário de login correto |
| 3 | Medir marca e formulário | Ordem horizontal conforme escolha; interseção vazia |
| 4 | Medir texto e conteúdo rolável | Texto não ultrapassa a marca nem fica oculto por corte |
| 5 | Conferir documento | Largura rolável não excede a viewport |

Usar textos discriminantes com palavras e quebra natural. A região do texto deve conter o conteúdo inteiro, sem corte, sobreposição ou ocultação para fazer a medição passar.

Não impor igualdade exata de 50% das colunas sem contrato adicional.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M35 | Formulário fica sempre à esquerda | CT-B01 | Exemplo direita exige ordem inversa |
| M36 | Estilos de colunas não são compilados | CT-B01 | Colunas efetivas, sem sobreposição, marca arredondada |
| M65 | Textos nos tetos estouram o painel no computador | CT-B01 | Conteúdo inteiro contido, sem invadir formulário ou causar rolagem horizontal |

## Regra R11 — Marca e rodapé permanecem no celular

**Origem:** RQ-01, P-09 e P-14.  
**Perfil:** padrão. **Técnica:** EP e geometria.  
**Por que browser:** o DOM não demonstra posição dentro da dobra.

```gherkin
Funcionalidade: Usar autenticação no celular
  Regra: A marca vira faixa acima do formulário

    Esquema do Cenário: [CT-B02] A composição estreita preserva a marca e o rodapé
      Dado layout painel com <conteúdo>, viewport 390 por 844 e formulário do lado <lado>
      Quando o visitante abre <tela>
      Então a marca visível forma uma faixa acima do formulário sem rolagem horizontal
      E o rodapé real inteiro permanece dentro da viewport

      Exemplos:
        | conteúdo | lado | tela | partição |
        | frases ausentes | direita | login | compacto |
        | destaque 80 e apoio 160 | direita | login | teto login |
        | destaque 80 e apoio 160 | esquerda | login | outro lado |
        | destaque 80 e apoio 160 | direita | recuperar senha | recuperação |
        | destaque 80 e apoio 160 | esquerda | cadastro | cadastro |
```

Os valores 80/160 são pontos de código após normalização. Executar as telas com ambos os lados em exemplos independentes.

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Preparar conteúdo e viewport | 390×844 |
| 2 | Visitar e conferir path | Tela correta |
| 3 | Medir marca e formulário | Marca com área positiva e `marca.bottom <= formulário.top` |
| 4 | Medir rodapé | `rodapé.top >= 0` e `rodapé.bottom <= innerHeight` |
| 5 | Medir documento | Sem rolagem horizontal |

A marca não pode ser ocultada. Medir o rodapé real, sem cópia fixa criada para satisfazer o teste. A inspeção visual confere sua apresentação como faixa curta; não se inventa um limite numérico de altura.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M37 | Marca é escondida no breakpoint móvel | CT-B02 | Faixa visível com área positiva acima do formulário |
| M38 | Altura de computador empurra o rodapé | CT-B02 | Rodapé inteiro dentro de 844 pixels, inclusive com textos nos tetos |

## Regra R12 — Interação acessível

**Origem:** RQ-01.  
**Perfil:** padrão. **Técnica:** EP e teclado.  
**Por que browser:** exige foco e ativação reais.

```gherkin
Funcionalidade: Usar autenticação por teclado
  Regra: Controles essenciais são identificáveis e operáveis

    Cenário: [CT-B03] A pessoa percorre o formulário sem armadilha de foco
      Dado visitante no login painel com alternador de tema exibido
      Quando a pessoa percorre os controles essenciais por teclado
      Então e-mail, senha, envio e alternador recebem foco visível, possuem nome acessível e são operáveis sem foco preso na marca
```

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Visitar e conferir path | Login correto |
| 2 | Percorrer Tab e Shift+Tab | Todos os alvos alcançáveis |
| 3 | Conferir foco e nomes | Elemento ativo correto, indicação visível e nome associado |
| 4 | Ativar alternador por teclado | Tema muda efetivamente |

Executar claro/escuro e computador/celular de forma independente. Auditoria automática pode apoiar, mas não substitui a interação nem certifica nível WCAG não acordado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M39 | Campos ficam somente com placeholders | CT-B03 | Cada campo possui nome acessível associado |
| M40 | Marca prende foco ou bloqueia alternador | CT-B03 | Controles alcançáveis e tema alterado por teclado |

## Regra R13 — Opções e defaults produzem efeito visual

**Origem:** P-03, P-11 e Q11.  
**Perfil:** padrão. **Técnica:** EP, estilo computado e identidade de recurso.  
**Por que browser:** persistir opção não prova sua aplicação visual.

```gherkin
Funcionalidade: Personalizar a marca
  Regra: Cor, arte, logo e tema refletem a escolha e os padrões

    Esquema do Cenário: [CT-B04] O navegador obedece à opção visual
      Dado layout painel com <opção>, identidade conhecida e restante válido
      Quando o visitante abre o login no tema <tema>
      Então o navegador apresenta <resultado>

      Exemplos:
        | opção | tema | resultado | partição |
        | fundo cor #123456 | claro | marca com fundo rgb(18, 52, 86) | hex claro |
        | fundo cor #123456 | escuro | marca com fundo rgb(18, 52, 86) | hex escuro |
        | fundo primária #654321 | claro | marca com fundo rgb(101, 67, 33) | primária |
        | fundo arte da identidade | claro | arte exata da identidade carregada e visível | arte |
        | logo ligada | claro | logo exata da identidade visível | logo ligada |
        | logo desligada | claro | logo do painel de marca ausente | logo desligada |
        | alternador ligado | claro | alternador visível e operável | tema ligado |
        | alternador desligado | escuro | alternador ausente | tema desligado |
        | somente layout painel informado, primária #654321 | claro | formulário à direita, fundo rgb(101, 67, 33), sem frases, logo e alternador visíveis | defaults |
```

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Preparar uma partição | Identidade conhecida; primária distinta do hex escolhido |
| 2 | Visitar com tema explícito e conferir path | Tema realmente ativo |
| 3 | Inspecionar estilos, caixas e recursos | Resultado literal da linha |
| 4 | Acionar alternador quando ligado | Mudança real de tema |

Para arte/logo, conferir conteúdo ou fingerprint do recurso efetivamente exibido, além de dimensões positivas. Imagem genérica válida ou logo da outra organização deve falhar.

O recurso deve carregar sob o host do servidor de teste. Não exigir mecanismo de armazenamento específico nem upload novo.

Na linha de defaults, conferir também ausência dos blocos de frase, não apenas ausência de texto visível.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M41 | Sempre usa primária apesar do hex | CT-B04 | Hex escolhido resulta em `rgb(18, 52, 86)` |
| M42 | Arte/logo não carrega ou pertence a outra identidade | CT-B04 | Recurso exato, visível e com dimensões positivas |
| M43 | Flags/defaults não controlam os elementos reais | CT-B04 | Ligado aparece, desligado some e defaults são aplicados |

## Regra R14 — Preview acompanha o rascunho

**Origem:** RQ-10, P-08 e P-14.  
**Perfil:** padrão. **Técnica:** JavaScript e iframe.  
**Por que browser:** a ação de servidor pode funcionar enquanto o iframe fica desatualizado.

```gherkin
Funcionalidade: Conferir aparência antes de salvar
  Regra: O documento demonstrativo apresenta o rascunho na tela e dimensão escolhidas

    Esquema do Cenário: [CT-B05] Atualizar preview mostra o que ainda não foi salvo
      Dado pessoa autorizada no editor de <escopo>, persistido P e campos alterados para A sem salvar
      Quando a pessoa atualiza o preview para <tela> em <dimensão>
      Então o iframe visível apresenta A, o formulário de <tela> e a dimensão interna escolhida
      E a aparência persistida continua P e a criação demonstrativa não cria organização

      Exemplos:
        | escopo | tela | dimensão | partição |
        | padrão | login | 1440 por 900 | padrão computador |
        | padrão | recuperar senha | 390 por 844 | recuperação celular |
        | padrão | cadastro | 1440 por 900 | cadastro computador |
        | edição A | login | 390 por 844 | edição celular |
        | criação organização | cadastro | 390 por 844 | criação celular |
```

Dataset obrigatório: **três telas × duas dimensões**, em cada editor disponível. Cada combinação é independente; a tabela acima identifica as partições, sem reduzir o produto exigido.

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Autenticar e visitar editor | Path correto |
| 2 | Abrir seção e alterar valores | Rascunho distinto do persistido |
| 3 | Selecionar tela/dimensão e atualizar | Documento interno recebe a sentinela nova |
| 4 | Medir documento e conferir controles | Tela e viewport interna correspondentes |
| 5 | Conferir persistência | Continua P; organização de criação ainda inexistente |

Medir o documento interno, mesmo se o preview de computador for apresentado com escala. A caixa externa do iframe sozinha não prova a dimensão demonstrativa.

Conferir que controles demonstrativos não navegam iframe ou topo para autenticação/provedor. CT-16 prova separadamente os efeitos persistidos e tentativas forjadas.

No editor com ColorPicker/Tabs, a exceção local de ResizeObserver não dispensa a prova de atualização do iframe.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M44 | Rascunho muda no servidor e iframe continua antigo | CT-B05 | Documento visível recebe A e sentinela nova |
| M45 | Seletores não controlam tela ou dimensão interna | CT-B05 | Formulário e viewport interna correspondem à escolha |

## Regra R15 — Clássico preserva a aparência anterior

**Origem:** RQ-01, RQ-07, RQ-11, P-03 e P-14.  
**Perfil:** padrão. **Técnica:** regressão dos testes de aparência existentes.  
**Por que browser:** a igualdade da configuração, isoladamente, não detecta CSS novo vazando para o clássico.

```gherkin
Funcionalidade: Preservar a aparência clássica
  Regra: A configuração anterior continua produzindo a aparência já verificada

    Esquema do Cenário: [CT-B06] O clássico conserva os resultados de aparência existentes
      Dado <tela> com os dados e referências dos testes de aparência existentes e campos exclusivos do painel preenchidos com sentinelas
      Quando a pessoa abre <tela> com clássico escolhido
      Então os oráculos existentes de aparência continuam satisfeitos e nenhuma faixa, painel ou frase da aparência nova é acrescentada

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

Usar as referências e assertions anteriores disponíveis para cada superfície; CT-33 cobre todas as telas pela igualdade do objeto entregue ao pacote. Executar os testes de aparência existentes aplicáveis sem regenerar automaticamente seus esperados.

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Fixar dados e contexto da referência anterior | Identidade, conteúdo, tema e relógio comparáveis |
| 2 | Visitar com viewport e tema explícitos | Path e estado de autenticação apropriados |
| 3 | Executar oráculos de aparência existentes | Resultados preservados |
| 4 | Inspecionar regiões da composição | Nenhum painel/faixa novo ou sentinela exclusiva do painel |

Quando houver comparação por captura, reutilizar a baseline anterior. Não mascarar marca, formulário ou rodapé e não criar baseline do código novo para afirmar preservação.

O bloqueio de sessão e as demais telas autenticadas usam a persona e o estado reais necessários. Não são visitadas todas como visitante.

Não há pergunta pendente sobre qual referência usar: P-14 já decidiu. Disponibilizar e executar os artefatos anteriores é trabalho da sessão de implementação.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M46 | CSS do painel altera a composição clássica | CT-B06 | Oráculo anterior de aparência deixa de passar |
| M47 | Clássico passa a mostrar painel/faixa ou frases novas | CT-B06, CT-33 | Elementos e sentinelas ausentes; configuração anterior exata |

## Regra R1 — Entrada Unicode no navegador

**Origem:** P-03, P-10 e P-11.  
**Perfil:** padrão. **Técnica:** BVA.  
**Por que browser:** `maxlength` nativo pode contar unidades UTF-16 e impedir o envio de texto válido antes da validação do servidor.

```gherkin
Funcionalidade: Digitar frases Unicode
  Regra: O navegador permite os tetos medidos em pontos de código

    Esquema do Cenário: [CT-B07] Emojis válidos não são cortados pelo controle de entrada
      Dado pessoa autorizada no editor <editor> com <campo> contendo <n> emojis de um ponto de código digitados pelo controle real
      Quando a pessoa salva o formulário pelo navegador
      Então o campo não apresenta erro, a nova leitura contém os <n> emojis integrais e o controle não os truncou

      Exemplos:
        | editor | campo | n | partição |
        | configurações | destaque | 80 | teto destaque |
        | configurações | apoio | 160 | teto apoio |
        | criação de organização | destaque | 80 | teto criação |
        | edição de organização | apoio | 160 | teto edição |
```

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Autenticar, visitar e abrir seção | Editor correto |
| 2 | Inserir emojis pela interação real com o controle | Valor do DOM contém todos os pontos de código |
| 3 | Salvar pelo controle de envio | Sem erro do campo |
| 4 | Efetuar nova leitura | Texto integral persistido |

Não atribuir o valor diretamente ao estado Livewire ou remover `maxlength` pelo teste. Isso contornaria o defeito que o cenário deve detectar.

Executar os dois campos nos três editores; os exemplos destacam as bordas discriminantes. A recusa de poucos grafemas com excesso de pontos de código permanece em CT-30.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M71 | Controle limita 80/160 unidades UTF-16 | CT-B07 | 80/160 emojis integrais entram e persistem; cada emoji ocupa duas unidades UTF-16 |

## Regra R3 — Aspas não produzem execução

**Origem:** P-05.  
**Perfil:** completo. **Técnica:** EP por contexto de escape.  
**Por que browser:** é preciso observar o JavaScript executado, além da estrutura do documento.

```gherkin
Funcionalidade: Renderizar frases com aspas
  Regra: Conteúdo textual não vira atributo nem código

    Esquema do Cenário: [CT-B08] Payload de aspas permanece texto inerte
      Dado <saída> com <campo> contendo <texto> dentro do teto
      Quando a pessoa abre a tela e percorre os elementos por teclado
      Então o texto permanece literal sem atributo de evento ou código executável originado dele
      E nenhum diálogo ou execução do payload ocorre

      Exemplos:
        | saída | campo | texto | partição |
        | login | destaque | ';alert(1);// | quebra de JavaScript no login |
        | login | apoio | " autofocus onfocus="alert(1) | quebra de atributo no login |
        | preview | destaque | ';alert(1);// | quebra de JavaScript no preview |
        | preview | apoio | " autofocus onfocus="alert(1) | quebra de atributo no preview |
```

### Roteiro executável

| Passo | Ação | Resultado exigido |
|---|---|---|
| 1 | Preparar valor salvo ou rascunho próprio | Payload integral dentro do teto |
| 2 | Observar diálogos antes da navegação | Execução inicial também detectável |
| 3 | Visitar e conferir path/documento interno | Saída correta |
| 4 | Conferir texto, atributos e percorrer foco | Texto literal; nenhum evento ou autofocus injetado |
| 5 | Conferir observadores de execução | Zero execução do payload e zero diálogo |

No preview, inspecionar o documento interno. Não substituir a renderização do produto por um fragmento construído pelo teste.

A ausência de diálogo sozinha não basta: conferir também o texto literal e a ausência de atributos/nós ativos originados da entrada.

#### Mutantes previstos

O mutante **M72**, registrado em R3 do 04, é morto por CT-B08: interpolação insegura em JavaScript ou atributo produz execução ou altera o DOM; o cenário exige texto inerte nos dois destinos.

## Índice de Cenários

| ID | Regra | Técnica | Grupo | Costura | Destino proposto | Mata |
|---|---|---|---|---|---|---|
| CT-B01 | R10 | EP/geometria | G4 | browser | Browser e BrowserTenancy | M35–36, M65 |
| CT-B02 | R11 | EP/geometria | G4 | browser | Browser e BrowserTenancy | M37–38 |
| CT-B03 | R12 | teclado | G4 | browser | Browser e BrowserTenancy | M39–40 |
| CT-B04 | R13 | EP/estilo | G4 | browser | Browser e BrowserTenancy | M41–43 |
| CT-B05 | R14 | JavaScript/iframe | G4 | browser | Browser e BrowserTenancy | M44–45 |
| CT-B06 | R15 | regressão | G4 | browser | Testes existentes e arquivo da feature | M46–47 |
| CT-B07 | R1 | BVA Unicode | G4 | browser | Browser e BrowserTenancy | M71 |
| CT-B08 | R3 | escape contextual | G4 | browser | Browser e BrowserTenancy | M72 |
| CT-B09 | R24 | visibilidade | G4 | browser | Browser | — |
| CT-B10 | R21 | estado × evento | G4 | browser | Browser | — |
| CT-B11 | R19 | geometria | G4 | browser | Browser | — |
| CT-B12 | R20 | geometria | G4 | browser | Browser | — |
| CT-B13 | R21 | estado × evento | G4 | browser | Browser | — |
| CT-B14 | R21 | estado × evento | G4 | browser | Browser | — |
| CT-B15 | R21 | geometria | G4 | browser | Browser | — |
| CT-B16 | R30 | BVA de largura | G4 | browser | Browser | M104–M108 |
| CT-B18 | R28 | estilo computado | G4 | browser | Browser | — |
| CT-B19 | R31 | rastreio de efeito (navegação real) | G4 | browser | Browser | M139 |
| CT-B17 | R29 | geometria | G4 | browser | Browser | M102–M103 |

CT-B07 acrescenta a prova do limite efetivo do controle do navegador. CT-B08 acrescenta a prova de execução. Essas observações não são duplicadas pelas assertions de persistência de CT-30 ou pela inspeção de resposta de CT-06.

## Roteiro de Validação: Desenhado × Implementado

| # | Superfície desenhada | Implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | Editor padrão e preview | não consultado | | |
| 2 | Criação/edição de organização e preview | não consultado | | |
| 3 | Telas de P-02, incluindo cadastro por convite | não consultado | | |
| 4 | Computador 1440×900 com textos nos tetos | não consultado | | |
| 5 | Celular 390×844 com marca e rodapé dentro da dobra | não consultado | | |
| 6 | Configuração clássica anterior e testes existentes | não consultado | | |
| 7 | Entrada Unicode sem limitação indevida por UTF-16 | não consultado | | |
| 8 | Aspas inertes no login e no preview | não consultado | | |
| 9 | Arte e logo da identidade, sem upload novo | não consultado | | |

## Revisão adversarial e limites

| Achado | Cenários de browser envolvidos |
|---|---|
| ADV-04 | CT-B08, complementando CT-06 |
| ADV-06 | CT-B07, complementando CT-30 |
| ADV-07 | CT-B04, complementando CT-31 |
| ADV-09 | CT-B06, complementando CT-33 |
| ADV-13 | CT-B01 |
| ADV-14 | CT-B04, complementando CT-35 |
| ADV-20 | CT-B06, com comparação exata em CT-04/33 |
| ADV-21 | Origens e dimensões atualizadas em CT-B01/02/04/05/06 |

O fechamento integral ADV-01..ADV-23 está no 04. Os cenários novos requerem re-revisão independente pela sessão, conforme a skill. Nenhuma execução, aprovação visual, conformidade normativa ou confirmação de costura é alegada.

## Adendo 1 — celular só com a marca, movimento e largura

Derivados pela sessão (ver `04` › Adendo 1). CT-B05 mudou nas linhas de celular: a frase do
rascunho chega ao documento do preview, mas não fica visível na faixa (P-22).

```gherkin
# language: pt
Funcionalidade: Adendo 1 no navegador

  Regra: R24 — No celular, só a marca

    Esquema do Cenário: [CT-B09] A faixa do celular mostra só a logo ou o nome
      Dado a aparência com frase, apoio, destaque logo e rede em movimento, <com ou sem> logo na identidade
      Quando o login é aberto em 390×844
      Então o texto visível da faixa é <vazio | o nome da aplicação>, e meio, base e rede não aparecem
      E em 1440×900 a frase e a rede aparecem, sem erro de JavaScript
      # Origem: RQ-24, P-22

  Regra: R21 — Movimento com pausa

    Cenário: [CT-B10] A rede se move, o botão pausa e retoma
      Dado a rede em movimento
      Quando o login é aberto em 1440×900
      Então o botão de pausar está visível e um ponto muda de posição
      E depois de "Pausar o movimento" o ponto fica parado e o rótulo passa a "Retomar o movimento", sem aria-pressed (QA-25)
      E depois de "Retomar o movimento" o ponto volta a se mover
      # E com movimento desligado, o ponto não muda e não há botão
      # Origem: P-21 (WCAG 2.2.2)

  Regra: R19 — Largura

    Esquema do Cenário: [CT-B11] A largura escolhida divide a tela
      Dado largura <n> e formulário à <lado>
      Quando o login é aberto em 1440×900
      Então a seção do painel mede <n>% de 1440 (±2 px) e o formulário fica do lado escolhido
      Exemplos:
        | n | lado |
        | 30 | direita |
        | 70 | esquerda |
```

### Acréscimos da revisão do diff do adendo (step 9)

```gherkin
# language: pt
Funcionalidade: Achados da revisão do adendo no navegador

  Regra: R21 — Movimento só quando permitido e visível

    Cenário: [CT-B13] Com "reduzir movimento" no sistema a rede fica parada e sem botão
      Dado a rede em movimento e o navegador emulando prefers-reduced-motion: reduce
      Quando o login é aberto em 1440×900
      Então um ponto não muda de posição e o botão de pausar continua escondido
      # Origem: P-21 (ADV-A1)

    Cenário: [CT-B14] No celular o laço de animação não roda
      Dado a rede em movimento
      Quando o login é aberto em 390×844
      Então um ponto não muda de posição; em 1440×900 volta a mudar
      # Origem: P-21, P-22 (RD-A1)

  Regra: R20 — Nada é cortado ou sobreposto

    Cenário: [CT-B12] A imagem grande do destaque não cobre os textos numa tela baixa
      Dado largura 30, destaque imagem alta em tamanho grande e textos no teto
      Quando o login é aberto em 1366×768
      Então a imagem termina acima da base com os textos
      # Origem: P-20 (RD-A2, CX-A2)

    Cenário: [CT-B15] Num painel estreito o botão de pausar continua inteiro
      Dado largura 30, rede em movimento e textos no teto
      Quando o login é aberto em 1024×768
      Então o botão de pausar está inteiro dentro do painel
      # Origem: P-21 (CX-A1)
```

## Adendos 2 e 3 no navegador

Derivados pela sessão (ver `04` › Adendos 2 e 3). *(alterado em 2026-11-14: QA-30 — Gherkin
completo, tabelas `Exemplos` e mutantes previstos de R29 e R30)*

```gherkin
# language: pt
Funcionalidade: Adendos 2 e 3 no navegador

  Regra: R30 — Faixa do celular com arte

    Esquema do Cenário: [CT-B16] Com fundo de arte, a faixa do celular não mostra a imagem; no computador, mostra
      Dado a aparência padrão no painel com fundo de arte e destaque "nenhum"
      Quando o visitante abre o login do admin com largura <largura> px e altura 900 px
      Então a imagem da arte do painel de marca <arte>
      E o painel de marca continua exibido

      Exemplos:
        | caso            | largura | arte                                  |
        | celular         | 390     | não é exibida (display none)          |
        | limite          | 1023    | não é exibida (display none)          |
        | computador      | 1024    | é exibida                             |
      # Origem: RQ-30, P-30 (no celular a faixa não mostra a imagem da arte), P-22

  Regra: R29 — Social e anti-robô no painel

    Esquema do Cenário: [CT-B17] O botão social e o desafio anti-robô ficam visíveis dentro do formulário
      Dado o provedor Google configurado e o anti-robô ligado (reCAPTCHA v2, modo local)
      E a aparência padrão no painel com frase, apoio, destaque logo e rede em movimento
      Quando o visitante abre o login do admin em <tela>
      Então o botão do Google é exibido e fica entre as bordas esquerda e direita do formulário (±0,5 px)
      E o desafio anti-robô é exibido — sem ancestral escondido, sem visibility hidden e sem opacidade zero —, dentro do formulário e entre as mesmas bordas

      Exemplos:
        | tela        | largura × altura |
        | computador  | 1440 × 900       |
        | celular     | 390 × 900        |
      # Origem: RQ-31, RQ-32, RQ-33
```

#### Mutantes previstos — R30 (faixa do celular com arte)

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M104 | Arte visível na faixa do celular: a correção só tira a logo do topo e deixa a imagem da arte | CT-B16 | linha 390: arte com `display: none`; o mutante a exibe |
| M105 | Ponto de troca errado: a arte some só até 768 px (ou o limite é `<= 1024`) | CT-B16 | linhas 1023 (não exibida) e 1024 (exibida); o mutante erra uma das duas |
| M106 | A arte some também no computador (regra sem media query) | CT-B16 | linha 1024: arte exibida; o mutante a esconde |
| M107 | Ao esconder a arte, a faixa inteira some no celular | CT-B16 | painel de marca com caixa (`getClientRects` > 0) nas três linhas; o mutante o esconde |
| M108 | Sem a arte, a faixa fica sem a cor do painel (fundo transparente ou outra cor) | CT-B16 — sem a arte, o fundo do painel não é transparente e o véu está à vista *(alterado em 2026-11-14: lacuna fechada pela sessão)* | CT-B16 |

#### Mutantes previstos — R29 (login social e anti-robô no painel, navegador)

Complementam M98..M101 do `04`, que provam a presença no HTML; estes provam a visibilidade.

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M102 | O CSS do painel esconde o botão social (ou o empurra para fora do formulário) | CT-B17 | botão do Google com caixa e entre as bordas do formulário, nas duas linhas; o mutante o deixa sem caixa ou fora |
| M103 | O desafio anti-robô fica no HTML, mas escondido por ancestral, `visibility` ou opacidade, ou fora do formulário | CT-B17 | `checkVisibility` com opacidade e `visibility` verdadeiro e o desafio dentro do formulário, nas duas linhas; o mutante falha uma delas |

### Acréscimo da revisão adversarial (ADV-C19)

```gherkin
# language: pt
Funcionalidade: Gradiente claro

  Regra: R28 — Superfície escura sobre o gradiente

    Cenário: [CT-B18] Sobre um gradiente claro, os textos ficam numa superfície escura, em branco, com links sublinhados
      Dado o fundo em gradiente de #eeeeee a #ffffff, com frase e apoio com link
      Quando o login é aberto em 1440×900
      Então o bloco dos textos tem fundo rgba(17, 24, 39, 0.9), texto branco e o link sublinhado
```

## Adendo 4 no navegador

```gherkin
# language: pt
Funcionalidade: Transição entre as telas no navegador

  Regra: R31 — Transição entre as telas

    Esquema do Cenário: [CT-B19] Do login para o "esqueci a senha", a troca de tela tem transição só com a opção ligada
      Dado o login no painel com o formulário à direita, a recuperação no painel com o formulário à esquerda e a transição <opção>
      E o login aberto em 1440×900
      Quando o visitante clica em "Esqueceu sua senha?"
      Então o wrapper da mídia e o contêiner do formulário do login se chamam <nomes>
      E o evento "pageswap" da página de login traz uma transição <transição>
      E o "esqueci a senha" abre com o painel do outro lado
      E, em 390×844, o wrapper da mídia não tem nome de transição

      Exemplos:
        | opção     | nomes                         | transição |
        | ligada    | "kit-painel" e "kit-formulario" | sim       |
        | desligada | "none" e "none"               | não       |
      # Origem: RQ-34, RQ-35, P-32, P-33
```

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M139 | A regra e os nomes existem, mas só numa das pontas (a de destino), e o navegador não faz a transição | CT-B19 | linha "ligada": `pageswap` com `viewTransition`; o mutante dá "nao" |
