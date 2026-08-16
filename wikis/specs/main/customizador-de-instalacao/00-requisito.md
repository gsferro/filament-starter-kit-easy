# Requisito — Customizador de instalação (`create-project` interativo)

## Fonte

- **Origem**: pedido do mantenedor colado no chat, via `/feature-wiki`
- **Data**: 2026-08-16
- **Autor / solicitante**: gsferro (mantenedor do kit)
- **Fidelidade**: alta (texto escrito)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> - no @README.md, temos a parte de "Personalize seu projeto" onde é listado os 11 itens de customização.
> - quando instalamos o laravel puro: "laravel new app", no processo de instalação, ele já faz algumas perguntas de customização. Então acho valido implementarmos aqui também, já que é um comportamento que o framework faz.
> - crie um customizador para que o usuário possa informar já na instalação todos os itens que colocamos como personalizavel quando for um "create project". uma vez instalado, é o kit:update que já funciona
> - veja como o laravel implementa e tente seguir o mesmo modelo para termos coerencia com o framework
> - na escolha do banco de dados, precisamos incluir a opção do mysql, com a observação que para funcionar algumas funções de IA local, o postegress é o recomendado
> - com esses dados de custom, a propria instalação já ajusta no codigo fonte
> - podemos ter a opção de pular a custom, deixando padrão e manual
> - ao final, de a opção de rodar os testes do kit (decisão do usuário) e exiba o resumo do que foi customizado, caso ele não pule
> - pense em mais coisas que poderia estar aqui mas que não torne o processo lento e exaustivo.
> - a ideia é ser eficiente e rapido, mesmo dando a opção de customização, o que já acelera para o usuário final
> - adicione a opção de dar uma estrela ao starter-kit ao final (como muitos pacotes fazem). acho que o pest faz isso (olhe na vendor, se precisar entender como é implementado para usarmos)

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A instalação por `composer create-project` deve fazer perguntas de customização, como o `laravel new` faz | "quando instalamos o laravel puro: 'laravel new app', no processo de instalação, ele já faz algumas perguntas de customização. Então acho valido implementarmos aqui também" | funcional |
| RQ-02 | O conjunto de itens perguntados sai da lista "Personalize seu projeto" do README (11 itens) | "no @README.md, temos a parte de 'Personalize seu projeto' onde é listado os 11 itens de customização" + "todos os itens que colocamos como personalizavel" | funcional |
| RQ-03 | O customizador vale **apenas** no `create-project`; projeto já instalado continua com `kit:update` | "quando for um 'create project'. uma vez instalado, é o kit:update que já funciona" | restrição |
| RQ-04 | A implementação deve seguir o modelo do instalador do Laravel, para coerência com o framework | "veja como o laravel implementa e tente seguir o mesmo modelo para termos coerencia com o framework" | não-funcional |
| RQ-05 | A escolha de banco deve incluir **MySQL** entre as opções | "na escolha do banco de dados, precisamos incluir a opção do mysql" | funcional |
| RQ-06 | A escolha de banco deve exibir a observação de que PostgreSQL é o recomendado para funções de IA local | "com a observação que para funcionar algumas funções de IA local, o postegress é o recomendado" | funcional |
| RQ-07 | As respostas são aplicadas automaticamente pela própria instalação — o usuário não edita arquivo depois | "com esses dados de custom, a propria instalação já ajusta no codigo fonte" | funcional |
| RQ-08 | Deve existir a opção de **pular** a customização, ficando tudo no padrão e manual | "podemos ter a opção de pular a custom, deixando padrão e manual" | funcional |
| RQ-09 | Ao final, oferecer rodar os testes do kit — a decisão é do usuário | "ao final, de a opção de rodar os testes do kit (decisão do usuário)" | funcional |
| RQ-10 | Ao final, exibir o resumo do que foi customizado — **exceto** quando o usuário pulou | "exiba o resumo do que foi customizado, caso ele não pule" | funcional |
| RQ-11 | Propor itens adicionais úteis, desde que não tornem o processo lento e exaustivo | "pense em mais coisas que poderia estar aqui mas que não torne o processo lento e exaustivo" | funcional |
| RQ-12 | O processo deve ser eficiente e rápido mesmo com a customização ligada | "a ideia é ser eficiente e rapido, mesmo dando a opção de customização" | não-funcional |
| RQ-13 | Ao final, oferecer dar uma estrela ao starter-kit no GitHub | "adicione a opção de dar uma estrela ao starter-kit ao final" | funcional |
| RQ-14 | A implementação da estrela deve seguir o modelo do Pest (verificado no `vendor`) | "acho que o pest faz isso (olhe na vendor, se precisar entender como é implementado para usarmos)" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-02 — "todos os itens" × itens que cabem num prompt.** Dos 11 itens do README, sete
  **não** são respondíveis por uma pergunta de terminal: arte do login (arquivo binário/SVG),
  acesso aos painéis (dado de papel, criado depois no `/admin`), matriz de permissões (código do
  `PapeisSeeder`), health checks (código do `KitServiceProvider`), comandos da UI
  (`config/command-center.php`), backups (`config/backup.php`) e agente de IA (prompt/tools, que
  é dado editável no `/admin`). Perguntar por eles no terminal seria pedir ao usuário que escreva
  PHP dentro de um prompt — e violaria RQ-11 e RQ-12.
  - **Resolvido com o solicitante em 2026-08-16**: o conjunto perguntado é o **essencial de 5**
    (nome, banco, credenciais do admin, cor primária, multi-tenancy). Os sete restantes viram
    **ponteiros no resumo final** — cada um com o arquivo a editar —, o que atende RQ-02 na parte
    que um instalador consegue atender e mantém RQ-12.
  - **Se negado**: o passo 4 do PRD (conjunto de perguntas) e os cenários derivados de RQ-02
    mudam de escopo.

- **RQ-01 — o `create-project` roda com terminal interativo?** Verificado no
  `composer.phar` desta máquina (Composer 2.9.5): `Composer\EventDispatcher\EventDispatcher`
  chama `executeTty($exec)` e este delega a `process->executeTty()` **quando `io->isInteractive()`**.
  Portanto o prompt dentro do `post-create-project-cmd` funciona. Em CI (`--no-interaction`,
  sem TTY) o customizador é pulado sozinho, o que é exatamente RQ-08.

- **RQ-04 — "mesmo modelo" do Laravel.** Lido o `NewCommand` do `laravel/installer`: `select`
  para banco, defaults aplicados em silêncio sob `--no-interaction`, e a escrita da escolha por
  substituição pontual no `.env`/`.env.example` (`configureDefaultDatabaseConnection`,
  `commentDatabaseConfigurationForSqlite`, `uncommentDatabaseConfiguration`, `replaceInFile`).
  **Diferença deliberada**: o instalador do Laravel é um binário externo que roda **antes** de o
  projeto existir; aqui as perguntas rodam **dentro** do projeto, no `kit:install`. O modelo
  copiado é o das perguntas e da escrita no `.env`, não o empacotamento.

- **RQ-03/RQ-07 — reinstalação sobre um `.env` já customizado.** O requisito só fala do
  `create-project`. Devolvida pela `feature-test-design` (P1 do `04`).
  - **Assumido**: `kit:install --force` reescreve **apenas** as chaves respondidas e
    preserva o resto do arquivo, inclusive chaves que o usuário acrescentou.
  - **Se negado**: CT-21 muda de oráculo e o passo 6 do PRD ganha uma confirmação explícita antes
    de sobrescrever.

- **RQ-09 — "os testes do kit" são quais?** Devolvida pela `feature-test-design` (P2 do `04`).
  - **Assumido**: o grupo `kit` (`composer test:kit`), que é o que o README chama de "os testes do
    kit" — e não `composer test`, que inclui Pint e PHPStan e leva minutos.
  - **Se negado**: muda o comando do passo 6 do PRD e o oráculo de CT-26.

## Fora de Escopo (declarado)

- Um binário `starter-kit new` (à la `laravel new`) publicado à parte — o kit é um skeleton de
  `create-project`, e RQ-01 pede paridade de **comportamento**, não de empacotamento.
- Prompts para os sete itens de código/arquivo listados na ambiguidade de RQ-02.
- Mudar o que o `kit:update` faz (RQ-03 declara que ele já resolve o pós-instalação).
- Idioma/timezone: o kit é pt-BR por decisão de produto, não é item da lista do README.
