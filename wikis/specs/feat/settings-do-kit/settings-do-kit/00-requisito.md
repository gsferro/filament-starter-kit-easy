# Requisito — Settings do kit em `/admin`

## Fonte

- **Origem**: `.claude/requisitos/w3a-settings-do-kit.txt` (recorte do pedido integral, que está em `.claude/requisitos/w3-settings-INTEGRAL.txt`)
- **Data**: 2026-08-24
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta (texto escrito pelo solicitante)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> Precisamos criar uma pagina de settings do starter-kit direto no "/admin".
> - analise profundamente o pacote: "https://filamentphp.com/plugins/filament-spatie-settings" que é oficial e usa o spatie settings
> - todas as informações que são customizadas no na instalação do starter-kit, passam a serem salvas e ficam disponiveis para alteração direto no painel
> - aqui poderemos salvar o favicon, nome da aplicação cor default (use o Enum Color como opção de seleção, mas pode deixar também uma escolha de color usando o input), dados de email, e tudo o mais que pode ser customizado direto aqui
> - atualize o @README.md com esssa evolução. Existe alguns "TODO" no projeto falando sobre virada de settings, agora é o momento da implementação
> - pode-se atualizar também a logo, brand e imagem das telas de login (pacote Auth designer)
> - é necessário ter as permissões para operar o settings, crie-as (este esta parcialmente envolvido com outra wiki a cima) para ser padrão desde o inicio do starter-kit
> - veja outros pontos que valem a pena estar no settings que eu não tenha citado aqui, mas que na sua analise do kit voce veja que agrega valor
> - a model do settings precisa estar com o pacote do audits implementado para trackings
> - não confundir com o settings do tenant, já que ali é para a configuração/customização no multi-tenancy
> - deixe toda essa parte muito bem documentado nos @README.md
> - caso essa parte fuga do contexto dessa wiki, pode criar uma nova

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Existe uma página de settings do kit no painel `/admin` | "criar uma pagina de settings do starter-kit direto no /admin" | funcional |
| RQ-02 | A implementação usa o `filament/spatie-laravel-settings-plugin` sobre o `spatie/laravel-settings` | "analise profundamente o pacote: ... que é oficial e usa o spatie settings" | restrição |
| RQ-03 | Tudo que o `kit:install` customiza é gravado e passa a ser alterável pelo painel | "todas as informações que são customizadas no na instalação do starter-kit, passam a serem salvas e ficam disponiveis para alteração direto no painel" | funcional |
| RQ-04 | O favicon é salvo pelo settings | "aqui poderemos salvar o favicon" | funcional |
| RQ-05 | O nome da aplicação é salvo pelo settings | "nome da aplicação" | funcional |
| RQ-06 | A cor default é escolhida por uma seleção baseada no Enum `Color` do Filament | "cor default (use o Enum Color como opção de seleção" | funcional |
| RQ-07 | Além da seleção, existe uma escolha de cor livre por input de cor | "mas pode deixar também uma escolha de color usando o input)" | funcional |
| RQ-08 | Os dados de e-mail são configuráveis pelo settings | "dados de email" | funcional |
| RQ-09 | O que mais for customizável entra na mesma tela | "e tudo o mais que pode ser customizado direto aqui" | funcional |
| RQ-10 | O `README.md` é atualizado com a evolução | "atualize o @README.md com esssa evolução" | não-funcional |
| RQ-11 | Os `TODO` do projeto sobre virada para settings são implementados agora | "Existe alguns TODO no projeto falando sobre virada de settings, agora é o momento da implementação" | funcional |
| RQ-12 | A logo (brand logo) é atualizável pelo settings | "pode-se atualizar também a logo, brand e imagem das telas de login (pacote Auth designer)" | funcional |
| RQ-13 | A imagem das telas de login (Auth designer) é atualizável pelo settings | "e imagem das telas de login (pacote Auth designer)" | funcional |
| RQ-14 | Existe permissão para operar o settings | "é necessário ter as permissões para operar o settings, crie-as" | autorização |
| RQ-15 | A permissão é padrão desde o início do kit (nasce semeada, sem passo manual) | "para ser padrão desde o inicio do starter-kit" | autorização |
| RQ-16 | Pontos adicionais de valor, não citados, são identificados na análise do kit e incluídos | "veja outros pontos que valem a pena estar no settings que eu não tenha citado aqui, mas que na sua analise do kit voce veja que agrega valor" | funcional |
| RQ-17 | A model do settings tem o pacote de audits implementado, para tracking das alterações | "a model do settings precisa estar com o pacote do audits implementado para trackings" | funcional |
| RQ-18 | O settings do kit não se confunde com a customização do multi-tenancy (por organização) | "não confundir com o settings do tenant, já que ali é para a configuração/customização no multi-tenancy" | restrição |
| RQ-19 | A parte toda fica muito bem documentada nos READMEs (plural — `README.md` e `README.en.md`) | "deixe toda essa parte muito bem documentado nos @README.md" | não-funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-03** — "todas as informações que são customizadas na instalação" inclui o **banco de dados** e as **credenciais do admin**, e nenhuma das duas pode virar campo de tela sem mentir sobre o efeito.
  - **Assumido**: o settings recebe as customizações da instalação **que valem por reescrita de configuração** (nome, cor, rótulos da organização) e deixa de fora as que exigem recriar o banco (driver/host/database) ou que não são sincronizadas por seeder (e-mail e senha do admin — `app/Support/CustomizadorDaInstalacao.php:170-192` documenta que o `UsuarioAdminSeeder` não sincroniza, de propósito). O recorte é o MESMO que o `kit:install --custom` já usa (`perguntarSemBanco()`), o que mantém uma regra única no kit em vez de duas.
  - **Se negado**: RQ-03 muda de escopo; os passos 3 e 6 do PRD ganham campos de banco/credencial e o ADR-06 é refeito.

- **RQ-08** — "dados de email" não diz se é só o remetente (`from`) ou o transporte inteiro (SMTP).
  - **Assumido**: o transporte inteiro (mailer, host, porta, esquema, usuário, senha e remetente). Só o `from` deixaria a tela decorativa: o kit nasce com `MAIL_MAILER=log`, que não envia nada, e o `config/kit.php` já avisa por escrito que convite e lembrete dependem de um mailer de verdade.
  - **Se negado**: a aba "E-mail" reduz a dois campos e o ADR-05 (segredo cifrado) deixa de ser necessário.

- **RQ-11** — o TODO de `app/Providers/Concerns/ConfiguraFilamentGlobal.php:35-38` promete quatro itens: paginação, **densidade da tabela**, persistência de filtros e colunas redimensionáveis. **Densidade de tabela não existe no Filament 5**: varredura em `vendor/filament/tables/src` não devolve nenhuma ocorrência de `density`, e `vendor/filament/tables/src/Enums/` tem `ColumnManagerLayout`, `ColumnManagerResetActionPosition`, `FiltersLayout`, `FiltersResetActionPosition`, `PaginationMode`, `RecordActionsPosition` e `RecordCheckboxPosition` — nenhum de densidade.
  - **Assumido**: entregam-se os três que existem, mais o alternador de linhas listradas (`striped()`), que é o único controle visual de "aperto" que o framework oferece. O TODO dos READMEs é **reescrito** dizendo que densidade não existe na versão instalada, em vez de apagado como se tivesse sido entregue.
  - **Se negado**: nada muda no código; muda o texto do README.

- **RQ-14 / RQ-15** — "as permissões" (plural) não diz se é uma (acessar) ou duas (ver e editar).
  - **Assumido**: **uma** permissão (`View:ConfiguracoesDoKit`) governa acessar e salvar. O `canEdit()` do plugin não é barreira de leitura — o próprio README do vendor diz isso por escrito — e a tela guarda a senha de SMTP; um papel "só leitura" nela seria um papel que lê credencial. Ver ADR-04.
  - **Se negado**: entra `custom_permissions` com `Update:ConfiguracoesDoKit` e os dois seeders precisam ser ressemeados.

- **RQ-17** — "a model do settings" pressupõe que o settings do spatie seja uma model Eloquent. **Não é**: `Spatie\LaravelSettings\Settings` é uma classe abstrata de propriedades tipadas (`vendor/spatie/laravel-settings/src/Settings.php:19`), persistida como linhas chave-valor na tabela `settings`. Aplicar a trait `App\Traits\AuditsFillables` nela é impossível, e aplicá-la a uma model sobre a tabela `settings` produz uma trilha **pela metade**, porque a gravação de propriedade existente usa `upsert()` (`vendor/spatie/laravel-settings/src/SettingsRepositories/DatabaseSettingsRepository.php:74-77`), que não dispara evento de Eloquent.
  - **Assumido**: a trilha é escrita por um listener do evento `SavingSettings` do próprio spatie (`vendor/spatie/laravel-settings/src/Settings.php:191`), que é o único ponto que carrega valor antigo **e** novo. As linhas vão para a mesma tabela `audits` e aparecem em `/infra/audits`. Ver ADR-07.
  - **Se negado**: não há caminho alternativo dentro do pacote instalado; a alternativa seria trocar de pacote de settings, o que RQ-02 proíbe.

## Fora desta entrega

O pedido original do usuário (`.claude/requisitos/w3-settings-INTEGRAL.txt`) é maior, e o próprio requisito autoriza o corte: *"caso essa parte fuga do contexto dessa wiki, pode criar uma nova"*. O que ficou de fora, e para onde foi:

### Branch `feat/registro-e-aprovacao`

- Opção de **Register** no painel `/app` como settings.
- Register **por organização** quando a multi-organização está ligada.
- Quem se registra recebe **somente** o perfil de acesso ao `/app`, sem nenhum outro papel.
- **Aprovação automática vs. pendente** (pesquisar se o Filament tem algo nativo antes de implementar).
- Opção de exigir **validação de e-mail** ao liberar o register.
- Default `false` para o register, com tudo o que vem dele refletindo o default.

### Branch `feat/login-social-google`

- `laravel/socialite` e login com **Google** (`GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `redirect`).
- Botão de login social exibido **só** quando todos os dados de config estiverem preenchidos.
- Recém-registrado por login social **redirecionado para a tela de perfil** para completar o cadastro.
- Ícone correspondente no botão, e o rodapé da tela de login vindo do settings.
- Roteiro futuro de outros provedores (github, facebook, linkedin, x, discord).
- Default `false` para o socialite.

Nenhuma das duas é implementada aqui, e nenhuma ganha campo antecipado nesta entrega (YAGNI). O que esta wiki deixa pronto para elas é o **mecanismo**: uma classe de settings com grupo `kit`, uma migration de settings que acrescenta propriedade sem tocar nas existentes, uma tela em abas onde uma aba nova é um `Tab::make()`, e o alinhamento de config em memória que faz qualquer propriedade nova valer para o processo sem editar consumidor. Acrescentar "Register" amanhã é uma propriedade, um campo e uma linha no mapa de config.

### Também fora, com o motivo

- **Driver/host/nome do banco** — trocar depois do `migrate` não é reescrita de config, é outra instalação (`app/Support/CustomizadorDaInstalacao.php:178-181`).
- **Ligar/desligar a multi-organização** — as tabelas de permissão só nascem com a coluna de contexto se `permission.teams` estiver ativo ANTES do migrate; o caminho é `php artisan kit:tenancy`.
- **E-mail e senha do administrador** — o `UsuarioAdminSeeder` não sincroniza de propósito, e um campo de tela que não troca a credencial é pior que campo nenhum. O caminho é a tela de perfil.
- **Slug do CRUD de organizações** (`kit.tenancy.slug`) — é lido no registro de rota, não no render; e a URL é identificador permanente (link salvo, inventário de telas dos CT-B).
- **Idiomas do painel** (`kit.idiomas`) — o próprio `config/kit.php` declara que a internacionalização do kit não está feita e que ligar um segundo idioma hoje troca metade da tela. Oferecer o botão seria oferecer um defeito.
- **Retenção das trilhas** (`kit.retencao.*`) — não é pergunta da instalação e não está em nenhum TODO; fica no `.env`, onde o zero tem semântica documentada (`.ai/rules/config.md`).
- **Identidade visual de uma organização** (RQ-18) — continua sendo CRUD em `/admin/organizacoes`, com as colunas `cor_primaria` e `logo` do `Tenant`. Nada é movido para cá.
- **Logo do modo escuro** (`darkModeBrandLogo`) — o kit não tem hoje, e ninguém pediu. Um campo a mais para um caso que não existe.
