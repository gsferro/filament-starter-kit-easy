# Casos de Teste — Débitos G, H, J e K da aparência das telas de acesso

> Requisito: `00-requisito.md` · Oráculo do comportamento: o `00` da wiki de origem (`feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`), RQ-12 (acessibilidade) e RQ-34..RQ-36 com P-33 (transição)
> **Escrito pela sessão, não por sub-agente da `feature-test-design`**. São três cenários de navegador, cujo comportamento esperado já está fixado no oráculo da origem; não há regra nova a derivar. Sem cegueira de derivação: a perda está declarada no `03`, em `## Despachos`.
> Revisão adversarial: não exigida pelo perfil (mínimo). A revisão adversarial desta entrega (RQ-08) é a dos CT do Adendo 4 da origem, registrada no `03`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Acessibilidade das telas de acesso (axe, teclado) | 2 | 2 | 4 | padrão |
| Transição com "reduzir movimento" | 1 | 2 | 2 | mínimo |

- Técnicas: EP (layout × tela × tema no CT-01), rastreio de efeito no navegador (CT-02, CT-03).
- Cenários: 3 · Regras: 3 · Mutantes previstos: 4 · Sem matador: 0
<!-- contado à mão: 3 blocos "Cenário", 3 linhas no Mapa de Regras, 4 linhas em ## Mutantes Previstos -->

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — As telas de acesso passam no axe, nos dois layouts e nos dois temas | acessibilidade (padrão) | RQ-02, P-02 | EP (layout × tela × tema) | CT-01 |
| R2 — No clássico, o teclado leva do e-mail ao botão de entrar | acessibilidade (padrão) | RQ-03 | rastreio de efeito | CT-02 |
| R3 — Com "reduzir movimento", a troca de tela não anima, mesmo com a transição ligada | transição (mínimo) | RQ-04 | rastreio de efeito | CT-03 |

- RQ-01 (G): não vira cenário, porque o visual não tem oráculo automático além do contraste, que o axe do CT-01 já mede no tema escuro. A leitura das capturas está no `03`
- RQ-05..RQ-08 (J, K): são execuções e revisões, não comportamento. A evidência fica no `03`

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Telas de acesso no navegador | R1, R2, R3 | browser | existente: `tests/Browser/AparenciaDasTelasDeAcessoTest.php` (`definirAparenciaGlobal()`, `aparenciaDeTeste()`, `visitaComMovimentoReduzido()`) | axe, foco e `prefers-reduced-motion` só existem num navegador real | sessão, 2026-11-18 |

## Regra R1 — axe

```gherkin
Esquema do Cenário: [CT-01] As telas de acesso passam no axe
  Dado a aparência global no <layout>
  E o navegador em 1440×900 no tema <tema>
  Quando a pessoa abre <tela>
  Então a página está no tema <tema>
  E o axe não acusa nenhuma violação

  Exemplos:
    | layout          | tela                                   | tema          |
    | clássico        | /admin/login, /app/register, /admin/password-reset/request | claro, escuro |
    | painel de marca | as mesmas três                         | claro, escuro |
```

Limite conhecido: o `beforeEach` aquece as três telas com `$this->get()` no processo do teste, e o
servidor do navegador roda no mesmo processo. Depois do GET num painel `admin`, o `/app/register`
servido em seguida sai sem a arte e sem o alternador de tema. O efeito é do harness: em requisição
nova, a arte e o alternador estão presentes (sonda no `03`). Por isso o axe do cadastro roda sem o
alternador. O alternador continua coberto no login e na recuperação.

## Regra R2 — teclado

```gherkin
Cenário: [CT-02] No clássico, o teclado leva do e-mail ao botão de entrar
  Dado a aparência global no clássico
  E o foco no campo de e-mail do /admin/login
  Quando a pessoa aperta Tab até oito vezes
  Então o foco passa pelo campo de senha
  E chega a um botão do tipo submit
```

## Regra R3 — reduzir movimento

```gherkin
Cenário: [CT-03] Com "reduzir movimento", a troca de tela não anima
  Dado a aparência global no painel de marca, com a transição entre as telas ligada
  E a recuperação de senha com o formulário do outro lado
  E o sistema com "reduzir movimento"
  Quando a pessoa abre o /admin/login em 1440×900
  Então o painel não tem nome de transição
  Quando a pessoa clica em "Esqueceu sua senha?"
  Então a página de destino é a recuperação
  E a navegação aconteceu sem transição de visualização
```

## Mutantes Previstos

| ID | Mutante | Cenário que mata | Por quê |
|---|---|---|---|
| M1 | O wrapper do alternador perde o `role="menu"` | CT-01 | os botões `menuitemradio` ficam sem o pai exigido (`aria-required-parent`) |
| M2 | A regra `@view-transition` perde a condição de "reduzir movimento" | CT-03 | o `pageswap` passa a trazer `viewTransition` |
| M3 | O formulário do clássico ganha um `tabindex="-1"` no campo de senha | CT-02 | o percurso pula a senha |
| M4 | O tema escuro de uma tela fica com texto de contraste insuficiente | CT-01 | linha "escuro" do axe (`color-contrast`) |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `role="menu"` no wrapper, e não nos botões | mecanismo da correção; o oráculo é "o axe não acusa" | CT-01 aceita qualquer correção que zere a violação |
| `data-kit-foco` para guiar o `keys()` | técnica do teste, não comportamento | detalhe do CT-02 |
