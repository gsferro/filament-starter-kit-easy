# Quadro dos tickets — `dock-de-acoes-rapidas`

> **Gerado** por `scripts/indice.sh` da skill `feature-tickets`, a partir dos arquivos desta pasta.
> **Não editar à mão**: rodar o script de novo — inclusive para resolver conflito de merge. O
> script lê arquivos, não roda testes: CT verdes são inferidos do `Status` (`em revisão` e
> `concluído` = todos verdes, `pronto` = nenhum), e ticket `em execução` mostra `—`.

**Branch**: `main` · **Wiki**: [`03-progresso.md`](../03-progresso.md) · **Todas as features**: [`INDEX.md`](../../../INDEX.md)

## Progresso

`[████████████████████] 100 %` — 7 de 7 tickets concluídos

| pronto | em execução | em revisão | concluído |
|---|---|---|---|
| 0 | 0 | 0 | 7 |

**Fronteira** (pode ser pego agora): todos concluídos · **CT verdes** (inferidos do `Status`): 66 de 66

## Tickets

| NN | Entrega | Status | CT verdes | Bloqueado por |
|---|---|---|---|---|
| [01](01-dock-aparece-por-painel-e-permissao.md) | Quem tem Usar:Dock vê o dock nos painéis ligados | concluído | 12/12 | — |
| [02](02-dock-sem-consulta-extra-ao-banco.md) | Ligar o dock não acrescenta consulta ao banco | concluído | 4/4 | 01 |
| [03](03-usuario-gerencia-favoritos-no-slideover.md) | O usuário gerencia os favoritos no slideover | concluído | 11/11 | 01 |
| [04](04-layout-e-estilo-do-dock.md) | Layout e estilo do dock escolhíveis | concluído | 6/6 | 01, 03 |
| [05](05-adicionar-esta-pagina-com-teto.md) | Adicionar a página atual aos favoritos, até 10 | concluído | 9/9 | 03 |
| [06](06-favoritos-entre-paineis-e-ocultos.md) | Favoritos de outro painel e favoritos ocultos | concluído | 9/9 | 03, 05 |
| [07](07-favoritos-presos-a-organizacao.md) | Favoritos do painel de organização presos à organização | concluído | 15/15 | 05, 06 |

## Dependências

Seta = "bloqueia". Cor e segunda linha de cada nó = status: verde concluído · roxo em revisão ·
âmbar em execução · azul pronto na fronteira · cinza pronto aguardando bloqueio · vermelho pergunta
ao solicitante aberta (seta pontilhada).

```mermaid
flowchart LR
  t01["01 · Quem tem Usar:Dock vê o dock nos painéi…<br/>concluído"]
  t02["02 · Ligar o dock não acrescenta consulta ao…<br/>concluído"]
  t03["03 · O usuário gerencia os favoritos no slid…<br/>concluído"]
  t04["04 · Layout e estilo do dock escolhíveis<br/>concluído"]
  t05["05 · Adicionar a página atual aos favoritos,…<br/>concluído"]
  t06["06 · Favoritos de outro painel e favoritos o…<br/>concluído"]
  t07["07 · Favoritos do painel de organização pres…<br/>concluído"]
  t01 --> t02
  t01 --> t03
  t01 --> t04
  t03 --> t04
  t03 --> t05
  t03 --> t06
  t05 --> t06
  t05 --> t07
  t06 --> t07
  classDef concluido fill:#1a7f37,stroke:#116329,color:#ffffff
  class t01,t02,t03,t04,t05,t06,t07 concluido
```
