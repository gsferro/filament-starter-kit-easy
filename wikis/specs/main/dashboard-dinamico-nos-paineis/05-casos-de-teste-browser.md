# Casos de Teste de Browser — Dashboard dinâmico nos painéis

> Runtime: `pest-plugin-browser` (Playwright). O plugin sobe o próprio servidor.
> Comando: `composer test:browser` (em série — nunca `--parallel`; o script já
> embute o `npm run build`).

## Por que este arquivo existe

A grade do `mddev31/filament-dynamic-dashboard` é GridStack: arrastar,
redimensionar e persistir posição são JavaScript executado + chamada Livewire.
Nenhum teste de componente prova que o drag funciona — só o navegador.

## Pré-requisitos

- [ ] `npm run build` executado (embutido em `composer test:browser`)
- [ ] `tests/Browser/Screenshots` no `.gitignore`
- [ ] Autenticação por `$this->actingAs($user)` antes do `visit()`

## Seletores

| Elemento | Seletor | Já existe? |
|---|---|---|
| célula arrastável da grade | `.grid-stack-item` (GridStack) | sim — markup do pacote |
| ação "Add Widget" | texto traduzido do botão no header da página | confirmar na implementação |
| item "Dashboard" do menu | texto visível na navegação do painel | sim |

---

## CT-B01: arrastar um widget persiste a nova posição

**Por que browser e não Livewire**: a asserção é sobre drag-and-drop de
GridStack — JS executado. O teste de componente prova o endpoint de update;
não prova que o gesto chega nele.

```gherkin
# language: pt

  Cenário: [CT-B01] arrastar um widget persiste a nova posição
    Dado que a feature está ligada e o gestor_app tem um dashboard com 1 widget no /app
    Quando ele arrasta o widget para outra posição da grade
    Então a posição gravada do widget muda
    E ao recarregar a página o widget permanece na posição nova
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir a raiz do /app | `visit('/app')` | grade com o widget |
| 2 | arrastar a célula | `->drag('.grid-stack-item', ...)` ou o gesto que o plugin oferecer | widget move |
| 3 | recarregar | `->visit('/app')` novamente | widget na posição nova |
| 4 | console limpo | `->assertNoJavaScriptErrors()` | — |

**Assertions**: posição persistida (âncora de banco: `dashboard_widgets.x/y`)
· `assertNoJavaScriptErrors()` · widget visível na posição nova após reload.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB1 | drag visual sem persistência (posição volta no reload) | CT-B01 |
| MB2 | asset JS do GridStack não carregado — grade estática | CT-B01 |

---

## CT-B02: usuário sem permission não tem superfície de edição

**Por que browser e não Livewire**: o "modo edição" do GridStack é JS — o
componente pode omitir a ação e ainda assim entregar o JS de drag. Só o
navegador prova que a célula não é arrastável. (Estouro do teto justificado:
é o matador do mutante "gate só no botão, drag continua ativo" — M14 tem
superfície JS que o CT-08 não alcança.)

```gherkin
  Cenário: [CT-B02] sem a permission de gestão, a grade não é arrastável
    Dado que a feature está ligada e o usuário comum tem um dashboard com 1 widget no /app
    Quando ele abre a raiz do painel
    Então a grade renderiza o widget
    E a célula não oferece drag nem resize
    E não há ação de adicionar widget na tela
```

**Roteiro executável**

| # | Ação | Código Pest | Resultado visível |
|---|---|---|---|
| 1 | abrir a raiz do /app | `visit('/app')` | widget visível |
| 2 | procurar a ação de edição | `->assertDontSee('{rótulo Add Widget}')` | ausente |
| 3 | console limpo | `->assertNoJavaScriptErrors()` | — |

**Assertions**: widget visível · ação de edição ausente · célula sem handle de
drag (markup `.grid-stack-item` sem classe/attr de edição — confirmar o marcador
exato na implementação) · `assertNoJavaScriptErrors()`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata |
|---|---|---|
| MB3 | `canEdit()` escondendo o botão mas deixando o drag ativo | CT-B02 |

---

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| redimensionar widget (resize) | mesmo eixo do CT-B01; o drag já exercita o canal de persistência |
| abrir o seletor de dashboards | provável por componente (dropdown é Filament Action, não JS livre) |
| dark mode da grade | cosmético; sem requisito de tema |
| toggle na tela de settings em browser | CT-22 já prova gravação por componente; nada de JS exclusivo |

## Roteiro de Validação: Desenhado × Implementado

| # | O que o PRD desenhou | O que foi implementado | Confere? | Evidência |
|---|---|---|---|---|
| 1 | grade GridStack com drag persistindo | — | ☐ | — |
| 2 | read-only real para `panel_user` | — | ☐ | — |
