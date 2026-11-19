# Casos de Teste — Débitos G, H, J e K da aparência das telas de acesso

> Requisito: `00-requisito.md` · Oráculo do comportamento: o `00` da wiki de origem (`feat/aparencia-das-telas-de-acesso/tela-de-login-personalizavel`), RQ-12 (acessibilidade) e RQ-34..RQ-36 com P-33 (transição)
> **Escrito pela sessão, não por sub-agente da `feature-test-design`**. São três cenários de navegador, cujo comportamento esperado já está fixado no oráculo da origem; não há regra nova a derivar. Sem cegueira de derivação: a perda está declarada no `03`, em `## Despachos`.
> Revisão adversarial: não exigida pelo perfil (mínimo). A revisão adversarial desta entrega (RQ-08) é a dos CT do Adendo 4 da origem, registrada no `03`.

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| Acessibilidade das telas de acesso (axe, teclado) | 2 | 2 | 4 | padrão |
| Transição com "reduzir movimento" | 1 | 2 | 2 | mínimo |

- Técnicas: EP (layout × tela × tema no CT-B21), rastreio de efeito no navegador (CT-B22, CT-B23).
- Cenários: 4 · Regras: 4 · Mutantes previstos: 5 · Sem matador: 0 *(alterado em 2026-11-18: step 9 — CT-114 e M5; os IDs CT-01..CT-03 viraram CT-B21..CT-B23, na numeração de CT-B da origem)*
<!-- contado à mão: 4 blocos "Cenário", 4 linhas no Mapa de Regras, 5 linhas em ## Regra R4 — a sobrescrita acompanha o pacote

```gherkin
Cenário: [CT-114] A sobrescrita do alternador de tema é o partial do pacote mais o role="menu"
  Dado a sobrescrita do kit e o partial do auth-designer instalado
  Quando o comentário do kit é retirado
  Então o wrapper tem role="menu"
  E, sem esse atributo, o arquivo é idêntico ao do pacote
```

Vem do `/code-review` do step 9: o M1 só era pego pela suíte Browser. Agora a suíte Kit também o pega,
e uma mudança do partial no pacote deixa de ficar escondida atrás da cópia.

## Mutantes Previstos -->

## Mapa de Regras

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — As telas de acesso passam no axe, nos dois layouts e nos dois temas | acessibilidade (padrão) | RQ-02, P-02 | EP (layout × tela × tema) | CT-B21 |
| R2 — No clássico, o teclado leva do e-mail ao botão de entrar | acessibilidade (padrão) | RQ-03 | rastreio de efeito | CT-B22 |
| R3 — Com "reduzir movimento", a troca de tela não anima, mesmo com a transição ligada | transição (mínimo) | RQ-04 | rastreio de efeito | CT-B23 |
| R4 — A sobrescrita do alternador é o partial do pacote mais o `role="menu"` | acessibilidade (padrão) | P-02 | inspeção do arquivo | CT-114 |

- RQ-01 (G): não vira cenário, porque o visual não tem oráculo automático além do contraste, que o axe do CT-B21 já mede no tema escuro. A leitura das capturas está no `03`
- RQ-05..RQ-08 (J, K): são execuções e revisões, não comportamento. A evidência fica no `03`

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| Telas de acesso no navegador | R1, R2, R3 | browser | existente: `tests/Browser/AparenciaDasTelasDeAcessoTest.php` (`definirAparenciaGlobal()`, `aparenciaDeTeste()`, `visitaComMovimentoReduzido()`) | axe, foco e `prefers-reduced-motion` só existem num navegador real | sessão, 2026-11-18 |
| Arquivo da sobrescrita | R4 | Pest | existente: `tests/Kit/AparenciaDasTelasDeAcessoTest.php` | o `Então` compara dois arquivos versionados; não precisa de navegador | sessão, 2026-11-18 |

## Regra R1 — axe

```gherkin
Esquema do Cenário: [CT-B21] As telas de acesso passam no axe
  Dado a aparência global no <layout>
  E o navegador em 1440×900 no tema <tema>
  Quando a pessoa abre <tela>
  Então a página está no tema <tema>
  E o alternador de tema está presente, com o papel de menu
  E o axe não acusa nenhuma violação

  Exemplos:
    | layout          | tela                                   | tema          |
    | clássico        | /admin/login, /app/register, /admin/password-reset/request | claro, escuro |
    | painel de marca | as mesmas três                         | claro, escuro |
```

Cada caso aquece pelo kernel só a tela que visita. Aquecer as três antes (a primeira versão) deixava
o painel `admin` como o corrente no processo, e o `/app/register` servido em seguida saía sem a arte e
sem o alternador — o axe do cadastro rodava numa página incompleta (`/code-review` do step 9). A linha
"o alternador está presente" prova que isso não volta.

## Regra R2 — teclado

```gherkin
Cenário: [CT-B22] No clássico, o teclado leva do e-mail ao botão de entrar
  Dado a aparência global no clássico
  E o foco no campo de e-mail do /admin/login
  Quando a pessoa aperta Tab até oito vezes
  Então o foco passa pelo campo de senha
  E depois chega ao botão de enviar do mesmo formulário
```

## Regra R3 — reduzir movimento

```gherkin
Cenário: [CT-B23] Com "reduzir movimento", a troca de tela não anima
  Dado a aparência global no painel de marca, com a transição entre as telas ligada
  E a recuperação de senha com o formulário do outro lado
  E o sistema com "reduzir movimento"
  Quando a pessoa abre o /admin/login em 1440×900
  Então a página participa da transição (a regra e o painel marcado estão lá)
  Mas o painel não tem nome de transição
  Quando a pessoa clica em "Esqueceu sua senha?"
  Então a página de destino é a recuperação
  E a navegação aconteceu sem transição de visualização
```

## Mutantes Previstos

| ID | Mutante | Cenário que mata | Por quê |
|---|---|---|---|
| M1 | O wrapper do alternador perde o `role="menu"` | CT-B21 | os botões `menuitemradio` ficam sem o pai exigido (`aria-required-parent`) |
| M2 | A regra `@view-transition` perde a condição de "reduzir movimento" | CT-B23 | o `pageswap` passa a trazer `viewTransition` |
| M3 | O formulário do clássico ganha um `tabindex="-1"` no campo de senha | CT-B22 | o percurso pula a senha |
| M4 | O tema escuro de uma tela fica com texto de contraste insuficiente | CT-B21 | linha "escuro" do axe (`color-contrast`) |
| M5 | A sobrescrita perde o `role` ou diverge do partial do pacote | CT-114 | a comparação com o arquivo do vendor |

## Fronteira com o Plano

| Item do PRD | Recusado como oráculo porque | Destino |
|---|---|---|
| `role="menu"` no wrapper, e não nos botões | mecanismo da correção; o oráculo é "o axe não acusa" | CT-B21 aceita qualquer correção que zere a violação |
| `data-kit-foco` para guiar o `keys()` | técnica do teste, não comportamento | detalhe do CT-B22 |
