# Requisito — Enriquecimento do kit para a versão 1.0

## Fonte

- **Origem**: mensagem do usuário no chat, invocando `/feature-wiki`
- **Data**: 2026-08-18
- **Autor / solicitante**: Guilherme Ferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito, colado verbatim abaixo)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> **Anonimizado em 2026-09-01.** O nome da organização real que aparecia no texto original e as
> URLs dela foram substituídos por `Acme` / `exemplo.test` a pedido do solicitante — o
> repositório é público. Só o identificador mudou; nenhuma cláusula, número ou ordem foi
> alterada, e a decomposição em `RQ-##` continua a mesma.

> veja como esta configurado o AdminPanel no projeto: "D:\PROJECTS\<interno>\Mini PFF\mini-pff" e importe a view que exibe so dados do usuário que ficam no dropdown do canto superior direito para ca, e coloque em todos os painels
> - veja as demais features que estão implementadas lá e que poderia ser um bom aditivo ao starter-kit
> - ambos os projetos tem bases semelhantes, mas lá já tem muito negocio implementado, então foque somente em features que agregam ao starter-kit em si e não algo que envolva o negocio, pois não é o foco.
> - revise todo o nosso projeto. confirme que tudo esta funcionando e pense em melhorias para o starter-kit e features que agregariam valor.
> - faça uma revisão completa de todos os pacotes do filament aqui: "https://filamentphp.com/plugins?v=5&price=free&page=1". Liste possiveis candidatos que heriqueceriam o kit.
> - faça uma lista com os top 10 provaveis, com pros e contras
> - vamos usar apenas para o filament v5 e free
> - use sub-agentes para fazer a varredura dos pacotes e outros para estudadar e analisa-los frente ao starter-kit em si.
> - faça um estudo minuncioso e completo
> - agora é o momento de tornar o kit ainda mais completo e funcional para lançarmos a versão 1.0
> - deixe tudo muito bem documentado na wiki, pode criar mais arquivos em @wikis\ como uma lista de pacotes usados/implementados, descartados e possiveis candidatos.
> - crie uma nova branch para essa wiki para não sujar a main e a tag atual
> - faça tudo dentro desta branch, mas não faça o merge com a main, deixe que eu tomo essa decisão, e como é uma branch separada, voce pode terminar de escrever a /feature-wiki e já implementar pra que eu faça a validação posteriormente
> - use o /caveman ultra ao me responder aqui.
> - conto com voce, de o seu melhor! =)
> - use o loop para varrer todos os pacotes disponiveis do link enviado, vai do inicio ao fim das paginas conferido dos e cada um deles. depois feche um relatorio a respeito para minha consideração posterior
> - já lhe agradeço desde já.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Analisar como o `AdminPanel` do projeto `mini-pff` está configurado | "veja como esta configurado o AdminPanel no projeto" | funcional |
| RQ-02 | Importar para o starter-kit a view que exibe os dados do usuário no dropdown do canto superior direito | "importe a view que exibe so dados do usuário que ficam no dropdown do canto superior direito para ca" | funcional |
| RQ-03 | A view importada deve estar presente em **todos** os painéis do kit | "e coloque em todos os painels" | funcional |
| RQ-04 | Levantar as demais features do `mini-pff` que seriam bom aditivo ao starter-kit | "veja as demais features que estão implementadas lá e que poderia ser um bom aditivo ao starter-kit" | funcional |
| RQ-05 | Restringir o levantamento a features de plataforma; excluir o que for regra de negócio | "foque somente em features que agregam ao starter-kit em si e não algo que envolva o negocio" | restrição |
| RQ-06 | Revisar o projeto inteiro e confirmar que tudo está funcionando | "revise todo o nosso projeto. confirme que tudo esta funcionando" | funcional |
| RQ-07 | Propor melhorias e features de valor para o starter-kit | "pense em melhorias para o starter-kit e features que agregariam valor" | funcional |
| RQ-08 | Fazer revisão completa de todos os pacotes listados no diretório de plugins do Filament | "faça uma revisão completa de todos os pacotes do filament aqui" | funcional |
| RQ-09 | Varrer o diretório do início ao fim das páginas, conferindo cada pacote | "vai do inicio ao fim das paginas conferido dos e cada um deles" | não-funcional |
| RQ-10 | Listar os candidatos que enriqueceriam o kit | "Liste possiveis candidatos que heriqueceriam o kit" | funcional |
| RQ-11 | Produzir uma lista dos top 10 prováveis, com prós e contras de cada um | "faça uma lista com os top 10 provaveis, com pros e contras" | funcional |
| RQ-12 | Considerar apenas pacotes compatíveis com Filament v5 e gratuitos | "vamos usar apenas para o filament v5 e free" | restrição |
| RQ-13 | Usar sub-agentes para a varredura dos pacotes | "use sub-agentes para fazer a varredura dos pacotes" | não-funcional |
| RQ-14 | Usar outros sub-agentes para estudar e analisar os pacotes frente ao starter-kit | "e outros para estudadar e analisa-los frente ao starter-kit em si" | não-funcional |
| RQ-15 | Documentar tudo na wiki, podendo criar novos arquivos em `wikis/` | "deixe tudo muito bem documentado na wiki, pode criar mais arquivos em @wikis" | funcional |
| RQ-16 | Criar arquivo(s) de wiki com a lista de pacotes usados/implementados, descartados e possíveis candidatos | "como uma lista de pacotes usados/implementados, descartados e possiveis candidatos" | funcional |
| RQ-17 | Criar uma branch nova para este trabalho | "crie uma nova branch para essa wiki para não sujar a main e a tag atual" | restrição |
| RQ-18 | Não fazer merge com a `main` | "não faça o merge com a main, deixe que eu tomo essa decisão" | restrição |
| RQ-19 | Implementar dentro da branch, para validação posterior do usuário | "voce pode terminar de escrever a /feature-wiki e já implementar pra que eu faça a validação posteriormente" | funcional |
| RQ-20 | Fechar um relatório da varredura para consideração posterior do usuário | "depois feche um relatorio a respeito para minha consideração posterior" | funcional |
| RQ-21 | Responder ao usuário em modo Caveman `ultra` | "use o /caveman ultra ao me responder aqui" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-02** — "a view que exibe os dados do usuário" foi desambiguada por inspeção: no `mini-pff` são
  **duas** views encadeadas — `filament/user-menu-header.blade.php` (avatar, nome, e-mail) que faz
  `@include` de `filament/perfil-indicator.blade.php` (badge do papel). Ambas registradas por
  `PanelsRenderHook::USER_MENU_PROFILE_BEFORE`.
  - **Assumido**: importar as duas, adaptadas ao vocabulário do kit (`master_global` / `roles.painel`,
    `Tenant` no lugar de `Entidade`, `App\Support\Papeis` no lugar de `App\Enums\PerfilGerencial`).
  - **Se negado**: se a intenção era só avatar + nome + e-mail, o `perfil-indicator` sai e o
    `user-menu-header` perde o `@include` — o passo 3 do PRD e os cenários CT-05/CT-06/CT-07 são refeitos.

- **RQ-10 / RQ-11 versus `CLAUDE.md`** — o `CLAUDE.md` do projeto proíbe alterar dependências sem
  aprovação ("Do not change the application's dependencies without approval").
  - **Assumido**: pacote de terceiro **não é instalado** nesta entrega. RQ-08 a RQ-12 e RQ-20 são
    atendidos como **relatório para decisão**, que é o que o próprio texto pede ("para minha
    consideração posterior"). Só features de código próprio, sem dependência nova, são implementadas.
  - **Se negado**: se o usuário quiser instalação já nesta branch, cada pacote aprovado vira um passo
    novo de PRD com CTs próprios.

- **RQ-14** — "estudar e analisar frente ao starter-kit" foi executado pelos mesmos sub-agentes da
  varredura, que receberam o perfil do kit e a lista de pacotes já instalados como critério de
  classificação, mais a consolidação e o ranking feitos no thread principal.
  - **Assumido**: um agente que classifica com o critério em mãos cumpre as duas funções; separar
    varredura de análise dobraria o custo sem mudar o resultado.
  - **Se negado**: uma segunda leva de agentes lê o README/repositório de cada finalista e produz
    ficha técnica individual — trabalho adicional, não retrabalho.

## Fora de Escopo (declarado)

- Instalação de qualquer pacote de terceiro (barrado pelo `CLAUDE.md`; vira decisão do usuário).
- Merge para a `main` e criação da tag `1.0` (RQ-18 é explícito).
- Qualquer feature de domínio de negócio do `mini-pff` — PFF, projetos, aportes, pedidos, SAP,
  prestação de contas, RAG de documentos institucionais (RQ-05).
- Reescrita do tema visual do kit (o `mini-pff` tem 4 `theme.css`; o kit optou por não ter `viteTheme`,
  e essa decisão não foi colocada em questão pelo requisito).
