# Requisito — Hub de navegação em cards

## Fonte

- **Origem**: pedido colado no chat pelo mantenedor do kit, invocando a skill `feature-wiki` (item 2 de 3 pacotes pedidos na mesma mensagem)
- **Data**: 2026-08-15
- **Autor / solicitante**: Guilherme Ferro (mantenedor do starter-kit-easy)
- **Fidelidade**: alta (texto escrito)
- **Wikis irmãs do mesmo pedido**: `lightbox-em-imagens-e-documentos` (item 1), `graficos-com-apexcharts` (item 3)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> 2. analise profunda o pacote: https://filamentphp.com/plugins/harvir-cards veja como ele pode ser
>   integrado ao projeto.
> - o que diz o pacote: "Transforme qualquer página do Filament em um hub de navegação baseado em cartões — perfeito para hubs de Configurações , páginas iniciais de Clusters , painéis de Recursos ou qualquer lugar onde você queira uma grade organizada de links em vez de uma árvore na barra lateral." talvez ele sirva em telas que tenhamos multiplos caminhos ou então para links rapidos ou multiplas escolhas. Existem muitas possibilidades e temos muitos layouts e visualizações que podemos explorar usando esse pacote.
> - ele precisa estar documentaod e entrar como sugestão de uso sempre que for criado alguma pagina/resource que ele recomenda.
> - deixe bem documentado os exmeplo e casos de uso para ele
> - se já houver, em alguma das paginas atuais oportunidade de usa-lo, implemente.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O pacote `harvirsidhu/filament-cards` é instalado e integrado ao projeto | "analise profunda o pacote: … veja como ele pode ser integrado ao projeto" | funcional |
| RQ-02 | O pacote é aplicado onde há múltiplos caminhos, links rápidos ou múltiplas escolhas | "talvez ele sirva em telas que tenhamos multiplos caminhos ou então para links rapidos ou multiplas escolhas" | funcional |
| RQ-03 | O pacote fica documentado | "ele precisa estar documentaod" | não-funcional |
| RQ-04 | O uso do pacote é **sugerido ao agente** sempre que for criada uma página/resource que ele recomenda | "e entrar como sugestão de uso sempre que for criado alguma pagina/resource que ele recomenda" | restrição |
| RQ-05 | Exemplos e casos de uso ficam bem documentados | "deixe bem documentado os exmeplo e casos de uso para ele" | não-funcional |
| RQ-06 | Onde já houver oportunidade nas páginas atuais, o pacote é implementado | "se já houver, em alguma das paginas atuais oportunidade de usa-lo, implemente" | funcional |

## Ambiguidades e Perguntas Abertas

### Resolvidas com o usuário em 2026-08-15

- **RQ-01 — o pacote não traz CSS próprio.**
  `FilamentCardsServiceProvider` só faz `->hasViews()`; a única blade (`cards-page.blade.php`) depende do Tailwind da aplicação, e a instalação oficial manda acrescentar `@source '…/vendor/harvirsidhu/filament-cards/resources/views'` a um `theme.css`. **O kit não tem tema Filament customizado** (`viteTheme()` não é usado em nenhum dos três painéis).
  - **Decisão do usuário**: CSS próprio do kit registrado por `FilamentAsset::register()`, o mesmo mecanismo já usado em `KitServiceProvider::configureCorrecoesDeCss()` para `resources/css/filament/kit.css`. **Não** criar tema Filament customizado.
  - **Consequência aceita**: o kit continua abrindo os painéis sem `npm run build`; em troca, o CSS dos cards é mantido à mão e precisa ser revisto se o plugin mudar a markup. Ver ADR-02.

- **RQ-06 — onde criar o hub.**
  - **Decisão do usuário**: nos três painéis — `/infra`, `/admin` e `/app` — "em qualquer que valha a pena ter essa feature".
  - **Leitura adotada**: implementar nos três, porque a mesma página custa ~15 linhas quando a descoberta de cards é compartilhada, e o `/app` é justamente o painel que o cliente final vê. Se a auditoria Ponytail (step 6) julgar o `/app` supérfluo, o corte fica registrado no `03-progresso.md`.

### Abertas — decididas por premissa, sujeitas a correção

- **RQ-04 — "sugestão de uso" para quem?**
  - **Assumido**: para o **agente de IA que trabalha no repositório**, que é quem cria página e resource no fluxo do kit. Materializa-se como (a) receita em `wikis/receitas.md`, (b) linha nos checklists de "Resource novo" e "Página de painel" e (c) candidato a Project Rule em `.ai/rules/filament.md`, que é o único mecanismo que alcança agentes em sessões futuras.
  - **Se negado** (a sugestão for para o desenvolvedor humano, via UI): seria um aviso na própria interface, o que o kit não faz para nenhum outro padrão — e não há superfície onde exibi-lo.

- **RQ-02 — "múltiplas escolhas" inclui formulário?**
  - **Assumido**: **não**. O pacote transforma uma **página** em grade de links; ele não é componente de formulário nem substitui `Radio`/`Select`. "Múltiplas escolhas" é lido como "vários destinos de navegação".
  - **Se negado**: não há como atender com este pacote — seria outro componente.

### Devolvidas pela derivação de testes (`feature-test-design`, 2026-08-15)

- **Os títulos das três telas não estão no requisito.**
  "Central de infraestrutura", "Hub de administração" e "Início" vêm do PRD.
  - **Assumido**: nenhum caso de teste afirma sobre o título — os `Então` falam dos **destinos**
    oferecidos. Renomear as telas não quebra teste nenhum.
  - **Se negado** (o usuário quiser títulos fixos): eles viram cláusula e ganham cenário.

- **Usuário autenticado num painel sem nenhum destino autorizado: hub vazio ou 403?**
  O requisito não determina.
  - **Assumido**: **hub vazio**, porque 403 esconderia justamente a página de aterrissagem.
    Cenário CT-06 marcado `@premissa`.
  - **Se negado**: o `canAccess()` da própria Page passa a depender de haver ao menos um destino.

- **RQ-04 não é observável em teste automatizado.**
  "Sugestão de uso para quem cria página/resource" se materializa em documentação e Project Rule.
  Não existe assertion que prove que um agente futuro leu a sugestão. **Lacuna declarada por
  natureza do requisito**, registrada para o `feature-quality-gate` não a ler como omissão.

## Fora de Escopo (declarado)

- Criar Cluster no kit só para exercitar `discoverClusterCards()` — o kit não usa Cluster hoje, e criar um mudaria a estrutura de navegação de todos os painéis
- Substituir a barra lateral pelos hubs: os hubs **somam**, a navegação em árvore continua
- Transformar páginas de Resource (`ViewTenant`, `EditUser`) em hubs de sub-páginas — o kit usa `SubNavigationPosition::Top`, que já resolve esse caso
- Tema Filament customizado (`make:filament-theme`) — recusado explicitamente pelo usuário
