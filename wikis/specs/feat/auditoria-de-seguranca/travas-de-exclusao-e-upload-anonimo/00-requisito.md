# Requisito — Auditoria de segurança com o Filament Blueprint

## Fonte

- **Origem**: mensagem do dono do projeto no chat, em duas partes (o pedido da rodada e, antes dele, a instalação do Blueprint com o passo a passo colado)
- **Data**: 2026-08-25
- **Autor / solicitante**: gsferro
- **Fidelidade**: **alta** para o pedido (texto escrito, colado); **alta** para os achados (produzidos pelo catálogo `filament-security-audit` do Blueprint, cada um verificado no código e no `vendor/`)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - depois de instalar o blueprint, vamos fazer uma rodada completa, com uma /feature-wiki usando o blueprint, conforme a documentação: "https://filamentphp.com/plugins/filament-blueprint#running-a-security-audit"
> - aqui ele diz: "When using Blueprint, 'Using Filament Blueprint' was added at the start of the prompt."
> - faça uma rodada de revisão completa, deixe tudo muito bem documentado, use worktree para fazer essa rodada, faça os commits individualizados, sem seguinte, com tudo implementado e passando nos testes, abra o PR e faça o push na main + release

E, na mensagem anterior, o passo a passo da instalação:

> aqui esta o passo a passo de instalação do blueprint:
> 1. composer config repositories.filament composer https://packages.filamentphp.com/composer
> 2. composer config --auth http-basic.packages.filamentphp.com "gsferroti+filamentblueprint@gmail.com" "REDIGIDO"
> 3. composer require filament/blueprint --dev

> **Nota de segurança sobre a transcrição.** A chave de licença foi **redigida** nesta cópia. É a
> única alteração no texto original, e ela é deliberada: o `00` é versionado e público, e
> transcrever credencial num arquivo do repositório é criar o vazamento que a wiki
> `blueprint-fora-do-pacote-publicado` acabou de fechar. A credencial foi configurada no
> `auth.json` **global** do Composer, fora do projeto.

## O que o Blueprint produziu

O catálogo `filament-security-audit` (21 checks, categorias A–E) foi rodado contra os três painéis.
O relatório completo — §1 Sumário, §2 Achados, §3 Checks Executados, §4 Testes Recomendados,
§5 Endurecimento — está em [`05-security.md`](05-security.md), no formato que a própria skill do
Blueprint especifica.

**Dois achados confirmados**, ambos de controle de acesso (categoria A). Eles são a fonte normativa
das cláusulas abaixo.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Rodar a auditoria de segurança do Blueprint sobre a aplicação | "uma /feature-wiki usando o blueprint, conforme a documentação" + "#running-a-security-audit" | funcional |
| RQ-02 | Corrigir F-01: a exclusão de usuário no `/app` tem de ser negada por um mecanismo que o Filament v5 **realmente consulta** | §2 do relatório: "os overrides `canDelete()`/`canDeleteAny()` não autorizam nada" | autorização |
| RQ-03 | Corrigir F-01 também no `ConviteResource` do `/app`, pelo mesmo motivo | §2: "App/UserResource **e** App/ConviteResource" | autorização |
| RQ-04 | Corrigir a documentação que aponta para a trava inexistente | §2: "o docblock de EditUser afirma 'a trava de verdade é UserResource::canDelete()', o que é falso" | restrição |
| RQ-05 | Corrigir F-02: a página pública `/` não pode expor o RPC de upload do Livewire a visitante anônimo | §2: "expõe o RPC de upload do Livewire a visitante não autenticado" | autorização |
| RQ-06 | Aplicar a mesma proteção em `ConvitesRecebidos` | §2: "Mesmo caso, menor gravidade, em App/Pages/ConvitesRecebidos" | autorização |
| RQ-07 | Cada correção tem teste que falha sem ela | "com tudo implementado e passando nos testes" | não-funcional |
| RQ-08 | A rodada acontece em worktree | "use worktree para fazer essa rodada" | restrição |
| RQ-09 | Commits individualizados, um por assunto | "faça os commits individualizados" | restrição |
| RQ-10 | Documentar tudo muito bem | "deixe tudo muito bem documentado" | não-funcional |
| RQ-11 | PR, merge na main e release | "abra o PR e faça o push na main + release" | restrição |

## Ambiguidades e Perguntas Abertas

- **RQ-02 / RQ-03 — "sem seguinte"**. A frase "faça os commits individualizados, sem seguinte"
  não tem leitura única. As duas possíveis: (a) "sem *seguir* adiante", isto é, não emendar um
  commit no outro; (b) erro de digitação por "sem seguida"/"em seguida".
  - **Assumido**: (a) — cada correção fecha em si, com o seu teste, sem commit de continuação
    consertando o anterior. É a leitura que combina com "individualizados", e é também a mais
    exigente das duas.
  - **Se negado**: nada de código muda; muda só o agrupamento dos commits, que é refazível com
    `rebase` antes do merge.

- **F-01 não é explorável hoje.** Nenhuma `DeleteAction` está registrada nos dois resources, então
  não existe caminho de request para excluir. Isso levanta a pergunta de escopo: corrigir uma trava
  que nada exercita é trabalho útil?
  - **Assumido**: sim, corrigir. Três razões medidas: o docblook do `EditUser` **instrui** o próximo
    mantenedor a confiar na trava (RQ-04); o gerador do Filament inclui `DeleteAction` por default,
    então a superfície nasce sozinha no próximo `make:filament-resource`; e o custo é de duas linhas
    por resource.
  - **Se negado**: RQ-02 e RQ-03 saem da entrega e RQ-04 vira o escopo inteiro — trocar o docblock
    por um que diga a verdade ("não há trava; a ausência de ação é a única barreira").

## Fora de Escopo (declarado)

- **Auditar o código-fonte do Filament.** A skill do Blueprint é explícita: *"Audit how the
  application uses Filament v5, not Filament's own source."*
- **Os 19 checks que passaram.** Estão registrados em §3 do relatório com o motivo de cada
  `Pass`/`N/A`, e a própria skill proíbe transformar `Pass` em achado de "endurecimento futuro".
- **A dica de §5 (default global de `preventFilePathTampering`).** É recomendação de configuração
  de projeto, não achado — entra no relatório, não no plano.
- **Manter o Blueprint no repositório.** Ele é ferramenta de desenvolvimento e não pode viajar no
  pacote; a decisão e a guarda são da entrega anterior (v0.19.11).
