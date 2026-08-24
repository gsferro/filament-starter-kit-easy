# Requisito — página de boas-vindas na rota `/`

## Fonte

- **Origem**: arquivo `.claude/requisitos/w2-pagina-boas-vindas.txt` (texto do usuário, colado verbatim)
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito pelo próprio solicitante)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos criar modificar a view @resources/views/welcome.blade.php padrão do laravel quando acessa a rota "/" para uma pagina de bem-vindo ao starter-kit, com cards para acessar os paines e infos do kit
> - podemos exibir as informações da config e os dados que foram atualizados (caso tenham sido rodado o kit:install)
> - essa view sera exibida no lugar d welcome padrão
> - use a skill de design para gerar uma otima tela.
> - pode ser uma infolist: https://filamentphp.com/docs/5.x/infolists/overview ou outra estrutura nativa do filament, desde que a pagina use os componentes nativos, herdando o css e o darkmode já implemetados no starter-kit, além das informações customizadas
> - use o pacote de Cards para ser os links para os paines internos

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A rota `/` deixa de servir a welcome padrão do Laravel e passa a servir uma página de boas-vindas ao starter-kit | "modificar a view @resources/views/welcome.blade.php padrão do laravel quando acessa a rota \"/\" para uma pagina de bem-vindo ao starter-kit" | funcional |
| RQ-02 | A página substitui a welcome padrão — não convive com ela nem é uma segunda rota | "essa view sera exibida no lugar d welcome padrão" | restrição |
| RQ-03 | A página tem cards que levam aos painéis | "com cards para acessar os paines" | funcional |
| RQ-04 | Os links para os painéis internos são feitos com o pacote de Cards | "use o pacote de Cards para ser os links para os paines internos" | restrição |
| RQ-05 | A página exibe informações do kit | "e infos do kit" | funcional |
| RQ-06 | A página pode exibir as informações da config | "podemos exibir as informações da config" | funcional |
| RQ-07 | A página pode exibir os dados que o `kit:install` atualizou, quando ele foi rodado | "e os dados que foram atualizados (caso tenham sido rodado o kit:install)" | funcional |
| RQ-08 | O desenho da tela passa pela skill de design | "use a skill de design para gerar uma otima tela." | restrição |
| RQ-09 | A estrutura da página é nativa do Filament — infolist ou equivalente | "pode ser uma infolist: https://filamentphp.com/docs/5.x/infolists/overview ou outra estrutura nativa do filament, desde que a pagina use os componentes nativos" | restrição |
| RQ-10 | A página herda o CSS já implementado no starter-kit | "herdando o css [...] já implemetados no starter-kit" | não-funcional |
| RQ-11 | A página herda o dark mode já implementado no starter-kit | "herdando [...] o darkmode já implemetados no starter-kit" | não-funcional |
| RQ-12 | A página exibe as informações customizadas do projeto, além das nativas | "além das informações customizadas" | funcional |

## Ambiguidades e Perguntas Abertas

O usuário não estava disponível para responder. Cada premissa abaixo vem com o "se negado",
conforme a skill exige.

- **RQ-06** — "as informações da config": quais chaves? `config/kit.php` tem 291 linhas e mistura
  metadados do kit com credenciais do administrador inicial (`kit.admin.password`).
  - **Assumido**: um subconjunto curado, sem nenhum segredo. A rota `/` é anônima, então
    e-mail do admin, senha do admin, credenciais/host de banco, URL do repositório e o nome do
    ambiente **não** entram. Ver ADR-04.
  - **Se negado** (o usuário quer o dump completo da config): RQ-06 vira feature autenticada, a
    rota `/` precisa de `auth` ou de um gate, e os passos 4 e 6 do PRD são refeitos.

- **RQ-07** — "os dados que foram atualizados (caso tenham sido rodado o kit:install)": o
  `kit:install` **não grava nada em banco**; ele reescreve o `.env`
  (`app/Support/CustomizadorDaInstalacao.php`). Os valores que ele toca são `APP_NAME`, `DB_*`,
  `KIT_ADMIN_EMAIL`, `KIT_ADMIN_PASSWORD`, `KIT_COR_PRIMARIA` e `KIT_TENANCY*`. Não existe
  marcador de "instalação rodada" — nem flag, nem linha em tabela.
  - **Assumido**: exibir o que é seguro e observável — nome da aplicação, cor primária
    escolhida e estado da multi-organização (ligada/desligada + rótulo). O "caso tenham sido
    rodado" é atendido pela própria natureza do dado: quem não rodou o `kit:install` vê os
    defaults do kit, e é isso que a página mostra.
  - **Se negado** (o usuário quer um "instalação personalizada em {data}"): exige um marcador
    novo, gravado pelo `kit:install` — passo novo no PRD e mudança no `CustomizadorDaInstalacao`.

- **RQ-03** — "os paines": são três (`/admin`, `/app`, `/infra`), e o `/app` é multi-organização
  quando `kit.tenancy.enabled` está ligado, o que faz a URL virar `/app/{slug}`.
  - **Assumido**: um card por painel, apontando para a **raiz** do painel. Para o visitante
    anônimo isso cai no login do painel, que é o comportamento correto — a rota `GET app` existe
    e é `filament.app.tenant`, que resolve o tenant do usuário depois do login.
  - **Se negado**: nada muda no desenho; só o destino do card do `/app`.

- **Autenticação da rota `/`** — o requisito não fala em quem pode ver a página.
  - **Assumido**: continua **pública e anônima**, como a welcome padrão do Laravel é hoje.
  - **Se negado**: a página inteira muda de natureza (viraria um dashboard pós-login) e o ADR-01
    é refeito.

## Fora de Escopo (declarado)

- Internacionalização da página (o kit é pt-BR por decisão registrada em `config/kit.php`,
  bloco "Idiomas do painel").
- Autenticação, autorização ou gate na rota `/`.
- Alterar as telas de login dos painéis, o `AuthDesignerPlugin` ou os hubs existentes.
- Marcar no banco/`.env` que o `kit:install` foi executado.
- Exibir qualquer segredo, credencial, endereço de e-mail ou topologia de infraestrutura.
