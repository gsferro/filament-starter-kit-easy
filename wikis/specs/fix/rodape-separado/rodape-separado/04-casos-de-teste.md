# Casos de Teste — Rodapé separado: o recado volta ao cartão do login e a assinatura fica

> Requisito: `00-requisito.md` · Plano: `01-plano-acao.md` (só paths, rotas, stack e superfície)
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando a implementação (a correção não existe na branch no momento da derivação).
> Wiki ancestral: `wikis/specs/feat/rodape-coerente/rodape-coerente/` (v0.39.0). Esta wiki **substitui** parte dos oráculos dela e não a edita.

## Como os IDs desta wiki convivem com os da ancestral

O código de teste mora **no mesmo arquivo** dos casos da ancestral (`tests/Kit/RodapeCoerenteTest.php` e `tests/Browser/RodapeNaDobraTest.php`), e dois `[CT-10]` no mesmo arquivo seriam dois casos com o mesmo nome. Por isso, **sem renumerar**: o ID de um caso que esta wiki substitui ou mantém é o do teste que existe, e o `04` diz, caso a caso, uma de duas coisas:

- **substitui o `[CT-nn]` de `rodape-coerente`** — o oráculo muda, e a linha diz qual asserção muda;
- **regressão** — o caso continua como está; entra no `## Índice de Cenários` porque é o matador de algum mutante desta feature.

Nenhum ID novo foi necessário: a tela de registro entrou como linha do CT-12 (ver `## Cogitado e cortado`). Os demais IDs da ancestral (CT-03, CT-05, CT-13…CT-25) **não** são tocados por esta entrega e **não** aparecem aqui; o `ids-ct.sh` sobre o arquivo inteiro os acusa como "no teste, sem cenário nesta wiki" — é ruído esperado de dividir o arquivo com outra wiki, e não omissão (saída colada em `## Saída dos scripts`).

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — posição e presença do recado no cartão (R1, R3) | 2 | 2 | 4 | padrão |
| B — ausência do recado fora da tela de login (R2) | 2 | 2 | 4 | padrão |
| C — assinatura intocada (R4) | 1 | 2 | 2 | mínimo |
| D — dobra, ordem visual e acessibilidade (R5, browser) | 3 | 2 | 6 | padrão |

- **Probabilidade**: A e B integram com um ponto de extensão do Filament e com o layout do pacote `caresome/filament-auth-designer` (2). C é regressão sobre código que o diff nem toca (1). D depende de CSS do kit, de geometria do navegador e do axe sobre vendor (3: o plano registra os riscos R1 e R2 como não medidos).
- **Impacto 2 em toda área**: tela pública de login, texto de rodapé, reversível por `git revert`; nenhuma autorização, dinheiro, dado de terceiro, irreversibilidade nem LGPD é tocada (o escape do recado é o da blade, que o passo 2 do plano não altera: só comentário). **Nenhuma área tem Impacto 3 nem perfil completo; a revisão adversarial não é exigida pelo critério da skill.** A sessão pode despachá-la por decisão própria (precedente: `fix/kit-update-views-vendor`).
- Técnicas aplicadas: EP (tela de login mãe/filha × provedor social; recado ausente ≠ nulo ≠ vazio ≠ só espaços), valor limite **ordinal** sobre a posição do recado no documento (limite inferior = fim do formulário e do bloco dos botões; limite superior = fechamento do layout), tabela de decisão classe de página × recado, invariante sobre conjunto (axe), medição geométrica (browser).
- Escalada acima do perfil: nenhuma. Estouro do teto de CT-B (padrão: 1): R5 tem 2 (CT-B01 e CT-B03), justificado em `## Cogitado e cortado`.
- Cenários: 6 · Regras: 5 · Mutantes previstos: 23 · Sem matador: 1
<!-- derivado por grep -c (template-04 §Contagem do cabeçalho); recalcular a cada cenário novo. Os cenários CT-B01 e CT-B03 não entram na contagem por construção da regex (`[CT-B01]` não casa `\[CT-[0-9]+\]`): são 2 a mais, em `05-casos-de-teste-browser.md`. -->

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | o registro do recado no hook do formulário de login (`configureTelaDeLogin()` do `KitServiceProvider`), três comentários, quatro arquivos de docs, os dois arquivos de teste da ancestral | CT-10, CT-11, CT-12 |
| F | exibir o recado dentro do cartão, depois do formulário e dos botões; manter a assinatura fora do cartão; não exibir o recado em tela alguma que não seja de login; não exibir faixa quando o texto é vazio | CT-09, CT-10, CT-11, CT-12, CT-01 |
| D | o texto do recado (preenchido, ausente, vazio, só espaços); o nome e a versão da assinatura; **dado de outro tenant**: não se aplica, o recado é global e não pertence a organização | CT-09, CT-08, CT-07 |
| I | quatro telas de login (`/admin/login`, `/app/login`, `/infra/login`, `/login`); a recuperação de senha; a tela de registro (`/app/register`, só com registro aberto); as três telas autenticadas (`/admin`, `/app`, `/infra`) | CT-01, CT-10, CT-11, CT-12 |
| P | o layout do `caresome/filament-auth-designer`, que emite o `FOOTER` **fora** do `.fi-auth-layout` e não tem `<main>`; o cartão é `.fi-auth-card` ou `.fi-auth-form-container` conforme a mídia; viewport do Playwright (dobra); axe | CT-10, CT-B01, CT-B03 |
| O | o admin edita o recado numa aba da tela de configurações (não muda, P-04); o visitante o lê na tela de login; quem atualiza o kit recebe o arquivo pelo `kit:update` (não é desta feature) | CT-11 |
| T | a assinatura traz o **ano corrente**: todo cenário que a afirma congela o relógio (`emJunhoDe2026()`); nenhuma outra dependência de tempo | CT-01, CT-08, CT-10, CT-12 |

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — o recado fica dentro do cartão, depois do formulário e dos botões sociais, e a assinatura fica fora do cartão, depois dele | A (padrão) | RQ-01, RQ-02, P-01 | EP (tela × provedor social) + valor limite ordinal sobre a posição | CT-10 (substitui `[CT-10]`), CT-B01 (geometria, ver R5) |
| R2 — o recado só existe na tela de login e só quando há texto: nenhuma outra tela o emite, e texto ausente, vazio ou só de espaços não deixa elemento nem texto no documento | B (padrão) | RQ-02, P-01, P-04 | tabela de decisão (classe de página × recado) + EP (ausente ≠ nulo ≠ vazio ≠ espaços), com controle positivo | CT-01 (substitui `[CT-01]`), CT-09 (substitui `[CT-09]`), CT-12 (substitui `[CT-12]`); CT-04 em parte (ver Índice) |
| R3 — o recado aparece, com o texto configurado, em toda tela de login | A (padrão) | RQ-02, P-04 | EP (tela de login mãe, outra mãe, terceiro painel, filha) | CT-11 (regressão de `[CT-11]`) |
| R4 — a assinatura fica como está (composição, lugar, visibilidade para o visitante) e o recado de volta não a substitui | C (mínimo) | RQ-01, RQ-03, P-02, P-03 | EP (1 partição) + regressão | CT-08 (regressão de `[CT-08]`), CT-02, CT-04, CT-07, CT-18 (regressões da ancestral) |
| R5 — o recado dentro do cartão não empurra a assinatura para fora da dobra, está dentro do cartão e acima da assinatura na tela, e não acrescenta problema de acessibilidade | D (padrão, browser) | RQ-02, RQ-03, P-03, P-05 | invariante sobre conjunto + geometria, com controle positivo | CT-B01 (substitui `[CT-B01]`), CT-B03 (substitui `[CT-B03]`) |

- RQ-04 — restrição de processo, sem regra e sem cenário: cumprida pela branch `fix/rodape-separado` e pela worktree (o `01` a marca com passo "—" e justificativa).
- P-01 e P-02 são premissas de comportamento retiradas como pergunta no `00` (Q1 e Q2) com confirmação no PR. Os cenários que dependem delas levam `# @premissa` e **re-derivam** se o solicitante discordar: CT-10 e CT-11 (P-01, posição), CT-10, CT-08 e CT-12 (P-02, assinatura visível ao visitante na tela de login). A recomendação de ambas já é por **falha fechado**: manter o que existe.
- Nenhuma `RQ` está `aberta — Qn`.

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Posição e presença do recado | R1, R3 | Pest feature HTTP | existente — `tests/Kit/RodapeCoerenteTest.php` (suíte `kit`, `TestCase` da aplicação, `$this->get()`) | o `Então` é posição relativa de marcadores no HTML e texto recortado; nada exige JavaScript | (vazio — a sessão confirma) |
| Ausência do recado fora do login | R2 | Pest feature HTTP | existente — o mesmo arquivo | o `Então` é ausência no HTML inteiro; sem JavaScript | (vazio — a sessão confirma) |
| Assinatura intocada | R4 | Pest feature HTTP | existente — o mesmo arquivo | regressão do que já é provado por HTTP | (vazio — a sessão confirma) |
| Dobra, ordem visual e acessibilidade | R5 | browser | existente — `tests/Browser/RodapeNaDobraTest.php` (grupo `browser-kit`) | geometria (`getBoundingClientRect` contra `innerHeight`) e axe só o navegador prova | (vazio — a sessão confirma) |

`tests/Kit` é ligado ao `TestCase` da aplicação com `RefreshDatabase` (`tests/Pest.php`, ligação por pasta): "Pest feature HTTP" aqui roda com a aplicação de pé. Helper usado por um arquivo só fica nele (`.ai/rules/testes.md`, "Helper de teste usado por mais de um arquivo vive em tests/Pest.php").

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| os nomes dos hooks (`AUTH_LOGIN_FORM_AFTER`, `FOOTER`, `AUTH_LOGIN_FORM_BEFORE`) e o `scopes:` | mecanismo: o requisito pede o **lugar visível** (dentro do cartão, abaixo dos botões), não a chave do hook | detalhe dos mutantes; o oráculo é a posição no documento |
| a tag `<aside>` do recado (D1, P-05) | mecanismo e premissa; afirmar a tag congela a implementação, como o CT-B03 da ancestral já registrou | o oráculo do CT-B03 é a **propriedade** (nenhum elemento da feature entre os acusados pelo axe; ou, na bifurcação, contido num landmark); a tag é detalhe |
| `semRecadoEmLugarNenhum()` | nome de helper local; escolha de implementação | detalhe do `## Setup Global` |
| os seletores `.fi-auth-card` e `.fi-auth-form-container` | escolha de implementação do pacote; o requisito diz "cartão" | detalhe do CT-B01, com o nome medido no step 10 |
| o número de linha de qualquer citação do plano | envelhece (`.ai/rules/specs.md`) | citado aqui por símbolo e conferido por `citacoes.sh` |
| D4: "não há CT separado de ordem" | decisão de desenho do plano, **aceita**: o limite inferior do CT-10 (linha com provedor habilitado) mata o recado antes dos botões (M2) | linha do Esquema do CT-10, não cenário novo |
| "Controle positivo no mesmo caso, com um GET /admin/login" (CT-01, CT-09) | o Gherkin admite **um** `Quando` por cenário | o controle vira **linha irmã** do mesmo Esquema (como o CT-12 da ancestral já fazia); o oráculo é o mesmo |

**Perguntas geradas pela derivação.** Nenhuma de raia requisito. Três divergências de **desenho** em relação ao `01`, decididas pela sessão em 2026-10-07 (D6, D7 e D8 do `01`) (a derivação fecha a lista de CT, como o próprio `01` diz no passo 3):

❓ Q3 · raia: desenho · afeta: R2 (RQ-02) · depende de: — · **respondida — D6 do `01`**
O `01` (passo 3) não lista o `[CT-04]` entre os que mudam, mas ele usa `rodapeDe()` para afirmar a **ausência do recado** na linha `/admin/password-reset/request`: a mesma fraqueza que o D3 corrige nos CT-01, CT-09 e CT-12.
➡️ Recomendação: o bloco de ausência do recado dessa linha do CT-04 troca `rodapeDe()` por `semRecadoEmLugarNenhum()` (uma linha). O resto do CT-04 não muda. Aplicar no passo 3.

❓ Q4 · raia: desenho · afeta: R5 (RQ-02, P-01) · depende de: — · **respondida — D7 do `01`**
O `01` mede só a dobra e a ordem no CT-B01. Nenhuma asserção prova que o recado está **dentro do cartão** (um recado fora do cartão e dentro do `.fi-auth-layout`, por exemplo no hook `CardAfter` do designer, passa pelo CT-10 e pelas ordens).
➡️ Recomendação: o CT-B01 ganha uma asserção de contenção (`recado.closest('.fi-auth-card, .fi-auth-form-container')` não nulo), com o seletor medido no step 10. Aplicar no passo 4.

❓ Q5 · raia: desenho · afeta: R1 (RQ-01, RQ-02) · depende de: — · **respondida — D8 do `01`**
O CT-10 com provedor social habilitado precisa do arranjo `ligarLoginComGoogleDoKit()`, hoje declarado em `tests/Kit/LoginSocialGoogleTest.php` (uso de um arquivo só). Usá-lo em dois arquivos o torna helper cruzado.
➡️ Recomendação: o executor move o helper para `tests/Pest.php` (regra de `.ai/rules/testes.md`) **ou** grava as chaves de configuração direto no `Dado` do CT-10; não cria clone com outro nome.

## Setup Global

### Suíte e camada

- `tests/Kit/RodapeCoerenteTest.php`, suíte `Kit`, grupo `kit`; `beforeEach` já existente: `$this->seed([ShieldPermissionsSeeder::class, PapeisSeeder::class])`.
- `tests/Browser/RodapeNaDobraTest.php`, grupo `browser-kit` (detalhe no `05`).

### Personas

- `usuarioDoKit('admin' | 'panel_user' | 'infra', "{papel}@example.com")` — autenticado, como o CT-12 da ancestral já usa. `panel_user` existe nas duas suítes (`.ai/rules/testes.md`, tabela de papéis); `admin_app` não é usado aqui.
- **visitante** — nenhuma chamada a `actingAs()`. É persona, não ausência de arranjo.

### Helpers (já existentes em `tests/Pest.php`, nenhum muda)

| Helper | Papel nesta feature | Onde |
|---|---|---|
| `emJunhoDe2026()` | congela o relógio (a assinatura traz o ano) | `tests/Pest.php:emJunhoDe2026():2124` |
| `comIdentidade($nome, $versao, $exibirKit, $recado)` | fixa as **quatro** chaves da composição: nome, versão, toggle do kit e recado | `tests/Pest.php:comIdentidade():2137` |
| `ligarLoginUnificado(bool)` | `kit.login.unificado`: `true` só para `/login`, `false` para as demais | `tests/Pest.php:ligarLoginUnificado():508` |
| `recadoDoRodape($html)` | o texto do **elemento** do recado, em qualquer posição do HTML; `''` se não existe | `tests/Pest.php:recadoDoRodape():2156` |
| `assinaturaDoRodape($html)` | o texto normalizado do **elemento** da assinatura; `''` se não existe | `tests/Pest.php:assinaturaDoRodape():2206` |
| `rodapeDe($html)` | a **cauda** da página após o fechamento do layout; **só** para a ausência **na cauda** e como entrada de `segmentoDaVersao()`/`assinaturaDoRodape()` — **nunca** para a ausência do recado | `tests/Pest.php:rodapeDe():2032` |
| `rodapeDoLayoutDeAutenticacao($html)` | o trecho depois do fechamento balanceado de `.fi-auth-layout`; `''` se a âncora não existe | `tests/Pest.php:rodapeDoLayoutDeAutenticacao():2040` |

### Helper novo, local ao arquivo de teste

`semRecadoEmLugarNenhum(string $html)` — afirma, sobre o **HTML inteiro**, a ausência **dupla** do recado: o texto `Fale com o suporte` **e** a classe `fi-login-rodape`. Fica em `tests/Kit/RodapeCoerenteTest.php` (só ele o usa, `.ai/rules/testes.md`). Usa `assertStringNotContainsString` com mensagem — nunca `not->toContain($x, $mensagem)`, em que o 2º argumento vira outra agulha — e **nenhuma** chamada `preg_*` com o marcador do rodapé na mesma linha, por causa do varredor `[CT-24]` da ancestral (`tests/Kit/RodapeCoerenteTest.php:'[CT-24]':857`).

**Por que a ausência é no HTML inteiro e não em `rodapeDe()`.** Com o recado dentro do cartão, a cauda **nunca** o contém. A ausência medida ali é verdade sempre: um recado emitido no cartão quando o texto é vazio, ou na tela autenticada, passaria. É a mesma fraqueza que `rodapeDe()` já tinha para o `wire:snapshot`, agora estrutural.

### Configuração — o `Dado` afirma o valor efetivo, nunca o ambiente

- O `phpunit.xml` **força** `APP_VERSION=""`, `KIT_LOGIN_RODAPE=""` e `KIT_EXIBIR_VERSAO=false` e **não** força `APP_NAME`. Todo `Dado` fixa as quatro chaves por `comIdentidade()`, inclusive o cenário que só discute o recado.
- Todo cenário que abre uma rota de login fixa `ligarLoginUnificado()` no `Dado`: com a chave no estado errado a rota **redireciona** e a asserção mediria o redirect.
- **A tela de registro** (`/app/register`) só responde 200 com `config()->set('kit.registro.habilitado', true)` (a rota redireciona ao login sem token nem registro aberto, como os casos de recusa de `tests/Kit/ConviteTest.php` já provam). A linha do CT-12 que a visita fixa essa chave e afirma `assertOk()`: sem isso a ausência do recado mediria um redirect.
- **Provedor social habilitado** (CT-10, linhas com provedor): `kit.login.google.habilitado = true` e as três chaves de `services.google` preenchidas (o arranjo de `ligarLoginComGoogleDoKit()`; ver Q5).
- `.ai/rules/testes.md` e `.ai/rules/testes-browser.md` vencem a skill onde divergirem (ver `## Divergências entre skill e rules do projeto`).

### A posição se mede pelo **marcador de classe**, não pelo texto

O `wire:snapshot` do Livewire serializa o estado do componente, e o recado pode aparecer nele **antes** do cartão. `strpos` do **texto** do recado mediria o snapshot. As posições do CT-10 são as do **marcador** `fi-login-rodape` (e `kit-versao`, `fi-login-social`, `fi-auth-layout`, o `</form>`): a classe não existe no snapshot.

### Armadilhas de asserção nesta base

| Não escrever | Escrever | Por quê |
|---|---|---|
| `expect($html)->not->toContain($x, 'mensagem')` | `$this->assertStringNotContainsString($x, $html, 'mensagem')` | `toContain()` é variádica: na forma negativa a cláusula extra é sempre verdadeira e o caso nunca falha |
| `rodapeDe($html)` para afirmar ausência do recado | `semRecadoEmLugarNenhum($html)` | a cauda nunca contém o recado no cartão |
| `strpos($html, $a) < strpos($html, $b)` sem provar que as duas existem | afirmar presença das duas **antes** de comparar | `strpos` devolve `false` para agulha ausente e `false < N` é verdadeiro em PHP |
| ausência sem controle positivo na mesma execução | a linha irmã com o recado presente, ou a assinatura presente na mesma resposta | ausência passa com página em branco, 404 e redirect |
| `assertOk()` como oráculo único | qualquer asserção sobre o conteúdo | 200 com o conteúdo errado |

---

## Regra R1 — o recado fica dentro do cartão, depois do formulário e dos botões; a assinatura fica fora, depois dele

> `RQ-01`, `RQ-02`, `P-01` · área A, perfil **padrão** · técnica: **EP** (tela de login × provedor social) + **valor limite ordinal** sobre a posição no documento
>
> A posição é o oráculo da separação: com o recado no `FOOTER` (estado de `main`) os dois rodapés **são** o mesmo rodapé. O requisito pede o recado "abaixo dos botões", "no lugar e com a composição que tinha, e não no footer": o oráculo é onde ele está, e a chave do hook é detalhe.

```gherkin
# language: pt
Funcionalidade: O rodapé do login e o rodapé automático ficam separados

  Regra: o recado fica dentro do cartão, depois do formulário e dos botões sociais; a assinatura fica fora do cartão, depois dele

    # @premissa P-01 (o "como estava" é o estado anterior à unificação) e P-02 (a assinatura segue para o visitante)
    # substitui o [CT-10] de rodape-coerente: a ordem "assinatura antes do recado" cai; a asserção nova é a de posição
    Esquema do Cenário: [CT-10] o recado sai dentro do cartão, depois do formulário e dos botões, e a assinatura fora dele, depois
      Dado o nome da aplicação gravado como "Acme" e o recado gravado como "Fale com o suporte"
      E <provedor> e o login unificado <unificado>
      Quando o visitante abre "<rota>"
      Então o layout de autenticação foi encontrado no documento
      E o elemento da assinatura é exatamente "© 2026 Acme" e o do recado contém "Fale com o suporte"
      E o recado vem depois do fim do formulário de login <e_dos_botoes>
      E o recado vem antes do fechamento do layout e a assinatura vem depois dele

      Exemplos:
        | rota         | provedor               | unificado | e_dos_botoes                                | # partição                                          |
        | /admin/login | nenhum provedor social | desligado | (sem botões a ordenar)                      | login de painel — a classe mãe                      |
        | /login       | nenhum provedor social | ligado    | (sem botões a ordenar)                      | página única — a classe filha                       |
        | /admin/login | Google habilitado      | desligado | e depois do fim do bloco dos botões sociais | a ordem botões → recado (limite inferior, M2)       |
        | /login       | Google habilitado      | ligado    | e depois do fim do bloco dos botões sociais | a ordem na classe filha, registro por outro caminho |
```

**O que o `Então` afirma, literalmente**: seja `$fim = strlen($html) - strlen(rodapeDoLayoutDeAutenticacao($html))`, a posição do fechamento balanceado de `.fi-auth-layout`.

1. **controle positivo, primeiro**: `rodapeDoLayoutDeAutenticacao($html) !== ''`. Sem a âncora, `$fim` seria o fim do documento e o recado estaria "dentro" de graça (M5);
2. a presença dos dois marcadores (`fi-login-rodape`, `kit-versao`) por `assertStringContainsString`, **antes** de qualquer posição (`false < N` é verdadeiro);
3. `assinaturaDoRodape($html) === '© 2026 Acme'` (exata: sem o texto do recado dentro) e `recadoDoRodape($html)` contém `Fale com o suporte`;
4. limite inferior 1: posição de `fi-login-rodape` **>** posição de `fi-auth-layout` e **>** a do primeiro `</form>` depois dela;
5. limite inferior 2 (linhas com provedor): posição de `fi-login-rodape` **>** o fim do bloco `fi-login-social`;
6. limite superior: posição de `fi-login-rodape` **<** `$fim`; posição de `kit-versao` **>=** `$fim`.

A geometria do layout vem do pacote: o conteúdo da página entra pelo `{{ $slot }}` dentro de `.fi-auth-layout` (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:'fi-auth-layout':28`) e o `FOOTER` sai depois do fechamento dessa div (`vendor/caresome/filament-auth-designer/resources/views/components/layouts/auth.blade.php:FOOTER:63`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o recado continua no `FOOTER` com `scopes:` (o estado de `main`, a unificação que o solicitante recusou) | CT-10 (todas as linhas); também CT-B01 | posição de `fi-login-rodape` **<** `$fim`; o mutante o põe depois do fechamento do layout (>= `$fim`) |
| M2 | o recado é registrado **antes** dos botões sociais, no mesmo hook | CT-10, linhas com Google habilitado | posição de `fi-login-rodape` **>** o fim do bloco `fi-login-social`; o mutante tem posição menor |
| M3 | o recado sai acima do formulário (no hook `AUTH_LOGIN_FORM_BEFORE`) | CT-10 (todas as linhas) | posição de `fi-login-rodape` **>** a do `</form>`; o mutante a tem antes dele |
| M4 | a assinatura passa a sair dentro do cartão, junto do recado (as duas no mesmo hook, ou concatenadas num nó só) | CT-10 (todas as linhas) | posição de `kit-versao` **>=** `$fim` e `assinaturaDoRodape` exatamente `© 2026 Acme`; o mutante deixa a assinatura antes do fechamento, ou com o texto do recado dentro |
| M5 | o layout do pacote deixa de emitir `.fi-auth-layout` (um `composer update` move âncoras de vendor em bloco): `$fim` degenera para o fim do documento e "recado antes do fim" passa de graça | CT-10 (controle positivo) | `rodapeDoLayoutDeAutenticacao($html) !== ''`, com a mensagem "âncora do layout ausente"; sem o controle o caso ficaria verde sobre nada |
| M6 | o botão de passkey do Breezy, registrado no mesmo hook, sai abaixo do recado se `passkeys` for ligado (D4 do plano) | ⚠️ **sem matador** — o plugin não liga passkeys no kit | — (lacuna declarada). **Tentado**: `grep -rin passkey app config --include=*.php` sem ocorrência; ligar exige reconfigurar o plugin do painel no boot (`vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:passkeys:94`), fora do alcance de um teste de rota sem reconstruir o painel. Risco de regressão futura, não de hoje; gatilho: o dia em que o kit ativar passkeys |

> Estouro do teto de mutantes (padrão: 5): R1 tem 6, e o sexto (M6) é lacuna declarada, não consome cenário.

---

## Regra R2 — o recado só existe na tela de login, e só com texto

> `RQ-02`, `P-01`, `P-04` · área B, perfil **padrão** · técnica: **tabela de decisão** classe de página × recado + **EP** do texto (ausente ≠ nulo ≠ vazio ≠ só espaços), cada caso com **controle positivo**
>
> A ausência é medida no **HTML inteiro** (`semRecadoEmLugarNenhum`), e cada ausência tem o controle positivo na mesma execução: ou uma linha irmã do mesmo Esquema com o recado presente, ou a assinatura presente na mesma resposta. Sem o controle, "o recado não aparece" é verdade com o recado nunca renderizado em lugar nenhum (M11).

```gherkin
# language: pt
  Regra: nenhuma tela que não seja de login emite o recado, e o texto sem conteúdo não deixa elemento nem texto no documento

    # substitui o [CT-01] de rodape-coerente: a linha da recuperação de senha troca rodapeDe() por semRecadoEmLugarNenhum();
    # a linha /admin/login ganha a presença do recado como controle positivo da linha irmã. As demais linhas, inalteradas.
    # @premissa P-02
    Esquema do Cenário: [CT-01] a assinatura sai em toda superfície, e a recuperação de senha não tem recado em lugar nenhum
      Dado o nome da aplicação gravado como "Acme", a versão "2.4.0", a versão do kit desligada e o recado "Fale com o suporte"
      E o login unificado <unificado>
      Quando <audiencia> abre "<rota>"
      Então o elemento da assinatura contém "© 2026 Acme"
      E <recado>

      Exemplos:
        | audiencia                        | rota                          | unificado | recado                                                  | # partição                              |
        | a administradora (admin)         | /admin                        | desligado | (o recado não é afirmado aqui: é do CT-12)              | painel admin                            |
        | a usuária do painel (panel_user) | /app                          | desligado | (idem)                                                  | painel com tenancy                      |
        | o operador de infra (infra)      | /infra                        | desligado | (idem)                                                  | terceiro painel                         |
        | o visitante                      | /admin/login                  | desligado | o recado recortado do elemento é "Fale com o suporte"   | **controle positivo da linha de baixo** |
        | o visitante                      | /app/login                    | desligado | (idem CT-11)                                            | login do painel com tenancy             |
        | o visitante                      | /login                        | ligado    | (idem CT-11)                                            | página única                            |
        | o visitante                      | /admin/password-reset/request | desligado | nem o texto nem a classe do recado existem no documento | tela pública que não é login            |

    # substitui o [CT-09] de rodape-coerente: a ausência na cauda (rodapeDe) passa a ser no HTML inteiro, e a linha "preenchido" é o controle
    # @premissa P-02
    Esquema do Cenário: [CT-09] o recado sem conteúdo não deixa elemento nem texto no documento e não apaga a assinatura
      Dado o nome da aplicação gravado como "Acme" e o recado gravado como <recado>
      Quando o visitante abre "/admin/login"
      Então o elemento da assinatura é "© 2026 Acme"
      E <resultado>

      Exemplos:
        | recado               | resultado                                               | # partição                                         |
        | "Fale com o suporte" | o recado recortado do elemento é "Fale com o suporte"   | **controle positivo do detector** (preenchido)     |
        | ausente (nulo)       | nem o texto nem a classe do recado existem no documento | ausente                                            |
        | ""                   | nem o texto nem a classe do recado existem no documento | vazio                                              |
        | "   "                | nem o texto nem a classe do recado existem no documento | só espaços — a que separa `filled()` de `!== null` |

    # substitui o [CT-12] de rodape-coerente: a ausência nas telas autenticadas passa a ser no HTML inteiro; ganha a linha da tela de registro
    # @premissa P-02
    Esquema do Cenário: [CT-12] o recado aparece na tela de login e em nenhuma outra tela
      Dado o nome da aplicação gravado como "Acme" e o recado gravado como "Fale com o suporte"
      E o registro aberto <registro>
      Quando <audiencia> abre "<rota>"
      Então a resposta é 200 e o elemento da assinatura contém "© 2026 Acme"
      E <espera>

      Exemplos:
        | audiencia                        | rota          | registro | espera                                                  | # partição                                         |
        | o visitante                      | /admin/login  | fechado  | o recado recortado do elemento é "Fale com o suporte"   | **controle positivo** — o destinatário existe      |
        | a administradora (admin)         | /admin        | fechado  | nem o texto nem a classe do recado existem no documento | a fronteira, painel admin                          |
        | a usuária do painel (panel_user) | /app          | fechado  | nem o texto nem a classe do recado existem no documento | painel com tenancy                                 |
        | o operador de infra (infra)      | /infra        | fechado  | nem o texto nem a classe do recado existem no documento | terceiro painel                                    |
        | o visitante                      | /app/register | aberto   | nem o texto nem a classe do recado existem no documento | tela de registro (hook `AUTH_REGISTER_FORM_AFTER`) |
```

**Por que cada linha discrimina**

- **A ausência nunca é na cauda.** Com o recado de volta ao cartão, `rodapeDe()` não o contém em tela nenhuma: o oráculo antigo do CT-09 ficava verde por motivo errado mesmo com o recado sendo emitido vazio dentro do cartão (M9). Só o HTML inteiro vê o cartão.
- **O hook do recado só é emitido pela tela de login** (`vendor/filament/filament/src/Auth/Pages/Login.php:content():416`; o `grep -rn AUTH_LOGIN_FORM_AFTER vendor/filament/filament/src` devolve só essa tela e a constante, e o único outro registrante é o do Breezy em `vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:AUTH_LOGIN_FORM_AFTER:100`). Isso dispensa o `scopes:`, e **dispensar um controle de fronteira é afirmação negativa**: o cenário escrito **como se ela fosse falsa** é a linha `/app/register` (a tela de registro emite o hook vizinho, `vendor/filament/filament/src/Auth/Pages/Register.php:AUTH_REGISTER_FORM_AFTER:325`) e a da recuperação de senha do CT-01.
- **A linha de registro fixa `registro aberto`** e afirma 200: sem isso a rota redireciona ao login e a ausência mediria o redirect. O destino do estado de erro (redirect) é, então, fixado no `Dado`, não afirmado.
- **A linha `"   "` do CT-09 é a discriminante**: separa `filled()` de `!== null`; com `!== null`, o recado só de espaços renderizaria uma faixa vazia no cartão (M10).
- **A linha `/admin/login` do CT-01 e a do CT-12, e a linha `preenchido` do CT-09, são o mesmo controle em três lugares**: provam que a classe `fi-login-rodape` e o texto são **os que o detector procura** na tela onde o recado existe. Sem elas, a ausência é verde com o recado nunca renderizado (M11).
- **CT-04, linha `/admin/password-reset/request`** (Q3): o bloco de ausência do recado troca o mesmo helper; é o terceiro matador de M7, sem cenário novo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M7 | o recado volta para o `FOOTER` **sem** escopo e aparece em toda tela que o emite, inclusive as autenticadas e a recuperação de senha | CT-12 (linhas `/admin`, `/app`, `/infra`), CT-01 (linha da recuperação de senha), CT-04 em parte | `semRecadoEmLugarNenhum($html)` nas telas autenticadas e na recuperação de senha: nem `fi-login-rodape` nem `Fale com o suporte`; o mutante tem os dois |
| M8 | o recado é registrado também (ou só) no hook do formulário de **registro** | CT-12, linha `/app/register` | `semRecadoEmLugarNenhum($html)` com `assertOk()` e registro aberto; o mutante emite o `<aside>` no cartão do registro |
| M9 | o recado é emitido no cartão **mesmo sem texto** (elemento vazio ou placeholder), porque a condição do texto foi retirada do hook | CT-09, linhas ausente, vazio e só espaços | a classe `fi-login-rodape` ausente do documento **inteiro**; com `rodapeDe()` o mutante sobrevive (o elemento está no cartão, fora da cauda) |
| M10 | a condição do texto troca `filled()` por comparação com `null`: o recado só de espaços renderiza uma faixa vazia | CT-09, linha `"   "` | a classe `fi-login-rodape` ausente do documento inteiro; as linhas nula e vazia não distinguem |
| M11 | o recado nunca é renderizado (hook que a tela de login não emite, ou chave errada): toda asserção de ausência fica verde sobre nada | CT-09 (linha preenchido), CT-12 (linha `/admin/login`), CT-01 (linha `/admin/login`) | `recadoDoRodape($html)` igual a `Fale com o suporte` na linha de controle; o mutante devolve `''` e o controle fica vermelho, impedindo que as ausências valham como prova |

---

## Regra R3 — o recado aparece, com o texto configurado, em toda tela de login

> `RQ-02`, `P-04` · área A, perfil **padrão** · técnica: **EP** por tela de login (classe mãe, outra instância da mãe, terceiro painel, classe filha)

```gherkin
# language: pt
  Regra: toda tela de login mostra o recado configurado

    # regressão do [CT-11] de rodape-coerente: o oráculo (presença por recorte do elemento) não depende da posição e vale com o recado no cartão
    # @premissa P-01
    Esquema do Cenário: [CT-11] o recado aparece em todas as telas de login
      Dado o nome da aplicação gravado como "Acme" e o recado gravado como "Fale com o suporte"
      E o login unificado <unificado>
      Quando o visitante abre "<rota>"
      Então o recado recortado do elemento contém "Fale com o suporte"
      E o elemento da assinatura contém "© 2026 Acme"

      Exemplos:
        | rota         | unificado | # partição                                                        |
        | /admin/login | desligado | login de painel (a classe mãe)                                    |
        | /app/login   | desligado | login de outro painel, mesma classe mãe                           |
        | /infra/login | desligado | terceiro painel — a linha que um registro painel a painel esquece |
        | /login       | ligado    | página única de login — a classe FILHA                            |
```

Os mutantes da ancestral sobre o **escopo** do hook (M16, M17 e M43 dela) deixam de existir com o hook sem escopo; o dataset mantém `/login` e `/infra/login` porque a regressão que elas pegam agora é "o recado sumiu de uma das telas": a filha herda o `content()` da mãe e nenhuma das duas o redeclara (`app/Filament/Pages/Auth/TelaLogin.php` e `app/Filament/Pages/Auth/TelaLoginUnificada.php` não têm `function content`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M12 | a página única (`/login`) fica sem o recado: a filha ganha um `content()` próprio sem o hook, ou o registro a deixa de fora | CT-11, linha `/login` | `recadoDoRodape($html)` contém `Fale com o suporte`; o mutante devolve `''` |
| M13 | o recado só é registrado para um painel (o admin) e os outros dois ficam sem | CT-11, linhas `/app/login` e `/infra/login` | idem, uma asserção por linha |
| M14 | o recado lê outra chave que não `kit.login.rodape` (a opção de configuração muda, contra P-04) | CT-11 (todas as linhas) e CT-08 | `recadoDoRodape($html)` igual ao valor gravado em `kit.login.rodape`; o mutante renderiza outro valor ou nada |

---

## Regra R4 — a assinatura fica como está, e o recado de volta não a substitui

> `RQ-01`, `RQ-03`, `P-02`, `P-03` · área C, perfil **mínimo** (1 por regra: CT-08; o resto é regressão da ancestral, que continua verde porque nenhum arquivo da assinatura entra no diff)
>
> A regra é negativa por natureza ("fica como está"): o oráculo é o conjunto de casos da ancestral que já afirmam a composição, o lugar e a visibilidade. Eles viram regressão obrigatória; só o CT-08 tem Gherkin aqui, porque é o que afirma a **independência** (o recado preenchido não toma o lugar da assinatura) e porque o plano o lista.

```gherkin
# language: pt
  Regra: com o recado no cartão, a assinatura continua sendo emitida, para o visitante, com a mesma composição

    # regressão do [CT-08] de rodape-coerente. Muda só um detalhe de implementação do teste: sai a variável $rodape, que o caso nunca usou.
    # @premissa P-02
    Cenário: [CT-08] o recado preenchido não substitui a assinatura
      Dado o nome da aplicação gravado como "Acme" e o recado gravado como "Fale com o suporte"
      Quando o visitante abre "/admin/login"
      Então o elemento da assinatura contém "© 2026 Acme"
      E o recado recortado do elemento contém "Fale com o suporte"
```

**Regressão da ancestral que esta regra convoca** (oráculo inalterado; entram no `## Índice de Cenários` porque cada um é o matador de um mutante abaixo): `[CT-02]` (a assinatura sai uma vez só), `[CT-04]` (visitante vê a assinatura e nenhuma versão), `[CT-07]` (a composição literal nas cinco superfícies, com o prefixo `v`), `[CT-18]` (para o visitante, a assinatura é a linha inteira). O `[CT-B02]` (a faixa tem o estilo do kit) também roda, mas **não** é matador de mutante desta feature e por isso fica fora do índice (ver `## Regressão em suíte existente`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M15 | o recado de volta ao cartão suprime a assinatura quando há recado (a alternativa "o login herda" que o `00` da ancestral já recusou) | CT-08 | `assinaturaDoRodape($html)` contém `© 2026 Acme` com o recado preenchido; o mutante devolve `''` |
| M16 | a assinatura passa a existir só para quem autenticou (a leitura "volta a ser só para quem entra", P-02 violada) | CT-04 e CT-18 (linhas de login), CT-10 | `assinaturaDoRodape($html)` igual a `© 2026 Acme` para o visitante em `/admin/login` e `/login`; o mutante devolve `''` |
| M17 | a reversão restaura também a assinatura antiga, e ela sai duas vezes (uma do `FOOTER`, outra junto do recado) | CT-02 | `substr_count($semSnapshot, '© 2026 Acme')` igual a 1 em cada superfície; o mutante dá 2 |
| M18 | a reversão desfaz a release inteira (`git revert` de `bfe9a8d`) e perde o `©` e o nome na assinatura e o prefixo `v` (P-03) | CT-07 e CT-18 | a composição literal `© 2026 Acme & Filhos · v2.4.0` no painel e `© 2026 Acme & Filhos` para o visitante; o mutante entrega a composição antiga, sem `©` nem `v` (o CSS da dobra que ele também desfaz é matéria do CT-B01, M19) |

---

## Regra R5 — o recado no cartão cabe na dobra, está acima da assinatura, e não acrescenta problema de acessibilidade

> `RQ-02`, `RQ-03`, `P-03`, `P-05` · área D, perfil **padrão**, costura **browser** · técnica: **geometria** (`getBoundingClientRect` contra `innerHeight`) e **invariante sobre conjunto** (axe), cada uma com controle positivo
>
> Gherkin, roteiro executável, seletores e a bifurcação do CT-B03 estão em `05-casos-de-teste-browser.md`. Aqui ficam a regra e os mutantes, para o gate ser um só.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M19 | o cartão mais alto (com o recado dentro) empurra a assinatura para fora da dobra, e a regra de CSS da dobra (`body.fi-body:has(> .fi-auth-layout)`, mantida por P-03) deixa de bastar | CT-B01 (as quatro telas de login) | `assinaturaBottom <= vh`; o mutante tem a assinatura abaixo do viewport |
| M20 | o recado sai fora do cartão, mas ainda dentro do `.fi-auth-layout` (por exemplo no hook `CardAfter` do designer): passa pelo CT-10 e por qualquer ordem | CT-B01 (as quatro telas de login) | o elemento do recado tem um ancestral `.fi-auth-card` ou `.fi-auth-form-container`; o mutante tem `null` |
| M21 | o recado volta a `<div>` (o M59 da ancestral) e perde o landmark | CT-B03 — **o matador depende da medição do passo 4** | (a) `fi-login-rodape` ausente da lista de nós acusados pelas quatro regras do axe; se a medição mostrar a `<div>` não acusada, (b) o recado está contido num landmark (`closest('aside,[role],main,form')` não nulo). O (b) mata M21 por construção; o (a) só se a medição o confirmar |
| M22 | o recado vira `<footer>` em vez de `<aside>`: limpa o `region` e duplica o `contentinfo` da assinatura | CT-B03 | `kit-versao` ausente da lista de nós acusados pelas regras `landmark-no-duplicate-contentinfo` e `landmark-unique`; o mutante acusa a assinatura |
| M23 | o axe não roda (script não injetado, página não carregada) e as ausências ficam verdes sobre a lista vazia | CT-B03 (controle positivo) | o total de nós acusados é maior que zero: a tela já acusa elementos do vendor (`landmark-one-main`, `region` na mídia e nos campos); lista vazia é varredura que falhou |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | não se aplica: a superfície é de leitura pública e global; não recebe `{id}` de recurso e o recado não pertence a organização | — |
| Autorização exercida na ação | não se aplica: nenhuma policy, permissão ou ação; a escrita do recado é a da tela de configurações, que não muda (P-04) | — |
| **Fronteira pública × autenticada** | CT-12 (linhas `/admin`, `/app`, `/infra`), CT-01 — todos por `GET` HTTP, **por fora do componente de UI** | Ausência do recado fora do login |
| **Superfície pública que não é login** | CT-01 (recuperação de senha), CT-12 (registro aberto) | Ausência do recado fora do login |
| Idempotência | não se aplica: sem escrita | — |
| Concorrência | não se aplica: sem contador, saldo ou limite | — |
| Fronteira no ponto de entrada (gravação) | não se aplica: o formulário de gravação do recado não muda (P-04) e tem os casos da ancestral e de `tests/Kit/LoginSocialGoogleTest.php` | — |
| Domínio condicionado | não se aplica: nenhum campo cujo domínio dependa de outro | — |
| Estado × operação de escrita | não se aplica: sem entidade com ciclo de vida | — |
| **Ausente ≠ nulo ≠ vazio ≠ só espaços** | CT-09 (as quatro linhas, a primeira é o controle) | Ausência do recado fora do login |
| Cardinalidade 0 / 1 / N | CT-10 e CT-11: uma tela por linha, quatro telas de login; botões sociais 0 (nenhum provedor) e 1 (Google) no CT-10. **N provedores**: lacuna declarada — o kit só tem o Google habilitável (`kit.login.google.habilitado`); a ordem recado depois do **bloco** vale para N, porque o oráculo é o fim do bloco | Posição e presença do recado |
| Paginação / ordenação | **ordenação do documento**: CT-10 (limites inferior e superior) e CT-B01 (ordem visual) | Posição e presença do recado |
| Timezone / DST / virada de ano | CT-01, CT-08, CT-10 e CT-12 congelam o relógio com `emJunhoDe2026()`; o ano da assinatura não é o eixo desta feature (`[CT-22]` da ancestral) | Assinatura intocada |
| Texto livre / Markdown / escape | **coberto fora desta wiki**: o caso `renderiza o rodapé como Markdown e descarta HTML cru e link inseguro` de `tests/Kit/LoginSocialGoogleTest.php` e `[CT-19]`/`[CT-20]` da ancestral. Esta entrega só muda o lugar do recado; a blade não muda além de comentário (passo 2 do plano), e o caso de Markdown visita a tela de login, qualquer que seja o ponto do cartão. Negativa que dispensa controle: o cenário "como se fosse falsa" é o próprio caso de Markdown, que roda sobre o novo lugar | — |
| Unicidade + soft delete / CRUD / mass assignment / upload / valor monetário | não se aplica: sem model, formulário novo, upload nem dinheiro | — |
| **Superfície Livewire** (método público, propriedade pública, estado do framework) | não se aplica: o `02` declara "não exigida" — nenhuma página, widget ou componente novo, e nenhum `public function` nem `public $` novo | — |
| Estado do framework usado sem validar | não se aplica: a blade do recado lê `kit.login.rodape`, não `$filters`/`$tableSearch` | — |
| IDOR / mass assignment por entidade | não se aplica: a feature não persiste entidade | — |
| Escopo com discriminante nulo | não se aplica: sem query escopada | — |
| **Saída do estado de erro** (4xx/redirect tem destino) | CT-12 linha `/app/register`: a rota sem registro aberto **redireciona** ao login; o `Dado` fixa o registro aberto e a linha afirma 200, e o destino do redirect já é de `tests/Kit/ConviteTest.php`. Nenhum outro cenário termina em 4xx, 5xx ou redirect | Ausência do recado fora do login |
| **Asserção de ausência com destinatário** | CT-01, CT-09, CT-12 — cada ausência com o recado gravado **e** a assinatura presente na mesma resposta **e** a linha de controle com o recado presente | Ausência do recado fora do login |
| **Não-efeito de render duplicado** | CT-02 (regressão): a assinatura sai uma vez só | Assinatura intocada |
| **Afirmação negativa que dispensa o escopo do hook** | o `grep` e as citações estão na regra R2; cenário como se fosse falsa: CT-12 (`/app/register`), CT-01 (recuperação de senha) | Ausência do recado fora do login |
| **Acessibilidade / geometria de superfície pública** | CT-B01, CT-B03 (R1 e R2 do plano: dobra e `<aside>` aninhado) | Dobra, ordem visual e acessibilidade |
| **Hook compartilhado com outro registrante** (Breezy passkey) | lacuna declarada: M6 | Posição e presença do recado |

## Regressão em suíte existente

Nenhum destes casos muda de oráculo; todos entram na regressão obrigatória do passo 3 e 4 do plano. A coluna diz **o que a mudança pode quebrar** em cada um.

| Caso existente | Risco com o recado de volta ao cartão | O que fazer |
|---|---|---|
| `tests/Kit/LoginSocialGoogleTest.php` — o caso `exibe o rodapé da tela de login só quando há texto configurado` e o de Markdown | `assertSee`/`assertDontSee('fi-login-rodape')` no HTML inteiro: vale em qualquer posição | nenhuma; é regressão |
| `tests/Kit/LoginSocialGoogleTest.php` — o caso que ordena `form.password` antes de `Entrar com Google` | o recado entra no mesmo hook, **depois** dos botões; a ordem dos botões não muda | nenhuma; é regressão |
| `tests/Browser/LoginSocialTest.php` (comentário "sai pelo MESMO render hook dos botões") | volta a ser verdadeiro | conferir que o comentário e o caso concordam |
| `tests/Kit/VersaoNoRodapeTest.php`, `tests/Kit/TelasDeAutenticacaoTest.php`, `tests/Kit/LoginUnificadoTest.php`, `tests/Kit/LoginSocialProvedoresTest.php`, `tests/Kit/LoginSocialContaIndisponivelTest.php` | leem `rodapeDe()` só para a assinatura; a assinatura não muda | nenhuma; é regressão |
| `[CT-B02]` da ancestral (`tests/Browser/RodapeNaDobraTest.php:'[CT-B02]':109`) | a faixa da assinatura tem o estilo do kit; o diff não toca `kit.css` nem a blade da assinatura (P-03) | nenhuma; roda na regressão do passo 4. Fora do índice: não mata nenhum mutante desta feature (candidato a corte da skill) |
| `[CT-24]` e `[CT-25]` da ancestral (`tests/Kit/RodapeCoerenteTest.php:'[CT-24]':857`) | varrem `tests/` por `preg_*` citando o marcador do rodapé | o texto novo dos testes não reintroduz essa cópia do extrator |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| um CT separado para a **ordem botões → recado** (o do plano, D4) | já é o limite inferior do CT-10, linhas com provedor habilitado: mata M2 sem cenário novo |
| um CT novo (`CT-26`) para o **recado ausente na tela de registro** | é uma classe de página da mesma tabela de decisão do CT-12: virou uma linha do Esquema; um cenário a mais não mataria mais mutante que a linha |
| um cenário para `AUTH_LOGIN_FORM_BEFORE` | o limite inferior do CT-10 (depois do `</form>`) mata M3 |
| o controle positivo do CT-01 e do CT-09 como **dois `GET` no mesmo caso** (forma do plano) | o Gherkin admite um `Quando`; virou linha irmã do Esquema, com o mesmo oráculo |
| a ordem "assinatura antes do recado" do CT-10 da ancestral | é o estado que o solicitante recusou: o `Então` agora é o contrário (o recado dentro do cartão, a assinatura fora e depois) |
| CT-B novo para o dark mode do recado no cartão | o recado herda o estilo do cartão; não é cor nova, e dark mode já tem guarda de outra wiki |
| CT-B01 e CT-B03 como **um** CT-B (teto do perfil padrão: 1) | **estouro justificado**: cada um mata mutantes que o outro não vê (geometria × landmark) e ambos já existem; o gate vence o teto |
| `pest --mutate` sobre o provider | não se aplica: o código novo é o registro de um hook, sem classe de regra a mutar; os mutantes M1, M2, M3, M7 e M8 são **manuais**, medidos pelo executor (ver `## Divergências entre skill e rules do projeto`) |

## Índice de Cenários

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-10 | **substitui o `[CT-10]` de rodape-coerente**: o recado dentro do cartão, depois do formulário e dos botões; a assinatura fora, depois | R1 | EP + valor limite ordinal | Posição e presença do recado | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M1, M2, M3, M4, M5 |
| CT-01 | **substitui o `[CT-01]` de rodape-coerente**: a assinatura em toda superfície; a recuperação de senha sem recado em lugar nenhum, com controle | R2 | tabela de decisão + controle positivo | Ausência do recado fora do login | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M7, M11 |
| CT-09 | **substitui o `[CT-09]` de rodape-coerente**: recado sem conteúdo não deixa elemento no documento, com controle | R2 | EP (ausente/vazio/espaços) | Ausência do recado fora do login | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M9, M10, M11 |
| CT-12 | **substitui o `[CT-12]` de rodape-coerente**: o recado só na tela de login; ganha a linha da tela de registro | R2 | tabela de decisão | Ausência do recado fora do login | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M7, M8, M11 |
| CT-04 | **substitui em parte o `[CT-04]` de rodape-coerente**: só o bloco de ausência do recado na linha da recuperação de senha troca `rodapeDe()` por `semRecadoEmLugarNenhum()` (Q3); o resto é regressão | R4 | regressão | Assinatura intocada | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M16, M7 |
| CT-11 | regressão do `[CT-11]` de rodape-coerente: o recado em toda tela de login | R3 | EP | Posição e presença do recado | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M12, M13, M14 |
| CT-08 | regressão do `[CT-08]` de rodape-coerente: o recado preenchido não substitui a assinatura | R4 | EP | Assinatura intocada | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M15, M14 |
| CT-02 | regressão do `[CT-02]` de rodape-coerente: a assinatura sai uma vez só | R4 | regressão | Assinatura intocada | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M17 |
| CT-07 | regressão do `[CT-07]` de rodape-coerente: a composição literal nas cinco superfícies, com o `v` | R4 | regressão | Assinatura intocada | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M18 |
| CT-18 | regressão do `[CT-18]` de rodape-coerente: para o visitante, a assinatura é a linha inteira | R4 | regressão | Assinatura intocada | Pest feature HTTP | `tests/Kit/RodapeCoerenteTest.php` | M16, M18 |
| CT-B01 | **substitui o `[CT-B01]` de rodape-coerente**: a assinatura e o recado na dobra, o recado dentro do cartão e acima da assinatura | R5 | geometria | Dobra, ordem visual e acessibilidade | browser | `tests/Browser/RodapeNaDobraTest.php` | M19, M20 (M1 também) |
| CT-B03 | **substitui o `[CT-B03]` de rodape-coerente**: o axe nas quatro regras de landmark, com o recado no cartão | R5 | invariante sobre conjunto | Dobra, ordem visual e acessibilidade | browser | `tests/Browser/RodapeNaDobraTest.php` | M21, M22, M23 |

## Sem CT-B

Não se aplica: a costura `browser` existe (grupo "Dobra, ordem visual e acessibilidade"), e o `05-casos-de-teste-browser.md` foi criado.

## Divergências entre skill e rules do projeto

| Instrução da skill | Rule que vence / decisão | Efeito aqui |
|---|---|---|
| `pest --mutate` fecha o ciclo | `.ai/rules/testes.md` (seção "UNTESTED do pest-plugin-mutate é sobrevivente, e --mutate com --filter no Windows não mede") e o plano (`## Verificação Final`): não se aplica | o código novo é um registro de hook, sem classe de regra a mutar; mutantes manuais M1, M2, M3, M7, M8 no provider, medidos pelo executor (a asserção que mata de cada um está nas tabelas) |
| `expect()->toContain($x, $msg)` como asserção de string | `.ai/rules/testes.md`, seção "`toContain()` do Pest não recebe mensagem — o 2º argumento é outra AGULHA" | ausência com `assertStringNotContainsString` |
| seletor por `data-testid`/`aria-label`/texto, não classe | `.ai/rules/testes-browser.md`, seção "Seletores: `aria-label` e texto, não classe de CSS" (o kit não tem `data-testid`, dívida conhecida) | o CT-B01 e o CT-B03 já usam classes do kit (`.kit-versao`, `.fi-login-rodape`); o precedente é o próprio `tests/Browser/RodapeNaDobraTest.php:'[CT-B01]':37`. Os seletores novos (`.fi-auth-card`, `.fi-auth-form-container`) são classes do vendor, sem alternativa por texto |
| oráculo de posição "fora do viewport" por `assertVisible` | `.ai/rules/testes-browser.md`, seção "`assertVisible` não prova posição — para layout, meça geometria via `script()`" | o CT-B01 mede `getBoundingClientRect` contra `innerHeight` |
| medir o CT-B contra o código sem a correção antes de aceitá-lo | a mesma rule, última frase da seção de geometria | CT-B01 medido contra o provider de `main` (vermelho), registrado no `03` |

## Revisão adversarial

Não exigida pelo critério da skill (perfil padrão em toda área, nenhum Impacto 3; ver `## Perfil de Derivação`). A sessão decide se a despacha. Nenhum achado a registrar.

## Saída dos scripts

Rodados na raiz do projeto, em 2026-10-07, sobre `wikis/specs/fix/rodape-separado/rodape-separado`:

- `rastreabilidade.sh {wiki}`: silêncio, exit 0.
- `citacoes.sh {wiki}`: silêncio, exit 0.
- `ids-ct.sh {wiki} 'tests/**/RodapeCoerenteTest.php' 'tests/**/RodapeNaDobraTest.php'`: exit 1, **só** linhas "no teste, sem cenário definido" para IDs da ancestral que esta wiki não toca (CT-03, 05, 06, 13 a 17, 19 a 22, 24, 25 e CT-B02). **Nenhum CT desta wiki sem teste e nenhum teste desta wiki sem cenário**: o ruído é o de dividir o arquivo com `rodape-coerente`, previsto em `## Como os IDs desta wiki convivem com os da ancestral`. (O `[CT-06]` do arquivo é comentário da ancestral, linha 203.)
