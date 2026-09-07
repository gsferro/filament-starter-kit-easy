# Requisito — Telas externas com o login unificado: escolha de painel dinâmica, registro, reset e slug das configurações

## Fonte

- **Origem**: pedido colado no chat pelo solicitante, via `/feature-wiki`, em 2026-09-06, depois da release v0.31.0 (login unificado) e de um relato de uso numa instalação real (`projeto-3`).
- **Data**: 2026-09-06
- **Autor / solicitante**: gsferro
- **Fidelidade**: **alta** (texto escrito). Os dois relatos de defeito (registro e "Esqueci minha senha") vêm de observação do solicitante na instalação real; a causa **não** está na fonte e é achado da pesquisa, não cláusula.
- **Wiki ancestral**: `wikis/specs/feat/login-unificado/login-unificado/` (v0.31.0). Esta wiki é **evolução** dela.

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> ao ativar o login unificado e cair na tela de escolha de paineis, deve exibir as mesmas opções que são exibidas pelo pacote "Panel Switch" na tela do filament.
>
> - apos a instalação, podem surgir outros paineis e eles precisam estar listados para a escolha de seleção em caso de multiplos acessos
> - aproveite e corrija a url do "configuracoes-do-kit", mudamos o nome no menu para "Configurações da aplicação" e a url ficou ainda com o nome antigo
> - atualizei no projeto 3, que nasceu com o kit e é uma aplicação real, quando o login unificado esta ligado, e eu ativo o register, ele esta redirecionando para "/app/register" mas exibe a mensagem: "Convite inválido ou expirado Peça um convite novo a quem administra o sistema. Se você já tem conta, entre por aqui."
> - analise o banco e as infos em "D:\PROJECTS\FIOTEC\PROJETO 3\projeto-3"
> - o "Esqueci minha senha" também esta direcionando para "/app/password-reset/request" ao inves de simplesmente "/esqueci-minha-senha"
> - agora que a skill esta atualizada, faça uma revisão profunda do kit e das partes que envolvem as telas externar (todas que usam o Auth Design) quando ligamos a feature de login unificado

Mensagem posterior, na mesma sessão: *"use sub-agentes para rodar em paralelo"* — instrução de processo, não cláusula.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Com o login unificado ligado, a tela de escolha de painel lista **o mesmo conjunto de painéis**, com **o mesmo rótulo e o mesmo ícone**, que o Panel Switch mostra dentro do Filament para aquele usuário | "deve exibir as mesmas opções que são exibidas pelo pacote "Panel Switch" na tela do filament" | funcional |
| RQ-02 | Painel registrado **depois da instalação** (novo `PanelProvider`) aparece na escolha sem alteração de código do kit, quando o usuário tem acesso a mais de um painel | "apos a instalação, podem surgir outros paineis e eles precisam estar listados para a escolha de seleção em caso de multiplos acessos" | funcional |
| RQ-03 | A URL da página de configurações do painel admin deixa de ser `configuracoes-do-kit` e passa a refletir o nome exibido no menu, "Configurações da aplicação" | "corrija a url do "configuracoes-do-kit", mudamos o nome no menu para "Configurações da aplicação" e a url ficou ainda com o nome antigo" | funcional |
| RQ-04 | Com o login unificado **e** o registro ligados, o link de registro a partir da página única leva a uma tela de registro **funcional** — não à recusa "Convite inválido ou expirado" | "quando o login unificado esta ligado, e eu ativo o register, ele esta redirecionando para "/app/register" mas exibe a mensagem: "Convite inválido ou expirado…"" | funcional (defeito) |
| RQ-05 | O banco e a configuração da instalação `projeto-3` são analisados para explicar o defeito de RQ-04 (e o de RQ-06) na instalação real, e o achado é registrado | "analise o banco e as infos em "D:\PROJECTS\FIOTEC\PROJETO 3\projeto-3"" | investigação |
| RQ-06 | Com o login unificado ligado, "Esqueci minha senha" leva a **`/esqueci-minha-senha`**, sem prefixo de painel | "o "Esqueci minha senha" também esta direcionando para "/app/password-reset/request" ao inves de simplesmente "/esqueci-minha-senha"" | funcional |
| RQ-07 | Toda tela externa do kit que usa o Auth Designer é revisada **com o login unificado ligado**; cada divergência encontrada vira correção nesta entrega (ou adendo, se mudar o escopo) | "faça uma revisão profunda do kit e das partes que envolvem as telas externar (todas que usam o Auth Design) quando ligamos a feature de login unificado" | investigação + funcional |

## Ambiguidades e Perguntas Abertas

<!-- Cláusula que não dá para testar como está. Perguntar ANTES de implementar. Preenchido após a pesquisa (step 3). -->

- **RQ-01** — "mesmas opções": o Panel Switch mostra rótulo + ícone, sem descrição. Os cartões atuais têm uma frase de descrição por painel, e **rótulo e ícone diferentes** dos do Panel Switch (`Painel do negócio` × nome da aplicação; prédio × foguete).
  - **Assumido**: rótulo e ícone passam a ser os do Panel Switch, lidos da **mesma fonte** (`Paineis::rotulos()`/`icones()`, fallback `ucfirst(id)` e `heroicon-o-square-2-stack`, iguais aos do pacote); a descrição fica **só** para os três painéis do kit; painel novo vem sem descrição. A 3.1.0 do Panel Switch não tem `excludes()`: o único recorte é `canAccessPanel()`, que a escolha já aplica. **Efeito visível**: o cartão do `app` deixa de dizer "Painel do negócio" e passa a dizer o nome da aplicação — na escolha **e** na boas-vindas, que compartilham a fonte.
  - **Se negado**: os cartões mantêm textos próprios e só a lista vira dinâmica; RQ-01 fica parcialmente atendida.
- **RQ-04** — o pedido descreve o sintoma (`/app/register` + "Convite inválido"), não o destino desejado. Duas leituras: (a) o link deve continuar em `/app/register` e a página deve funcionar; (b) o registro também ganha URL sem prefixo, como RQ-06 pede para o reset.
  - **Assumido**: (b), por coerência com RQ-06 e com a página única — o registro aberto passa a responder em **`/cadastro`** quando o login unificado está ligado, e `/{painel}/register` redireciona para lá preservando a query (mesmo padrão de `/{painel}/login` → `/login`).
  - **Causa medida** (não é cláusula, é achado — RQ-05): o `projeto-3` tem `KIT_TENANCY=true`; com tenancy o registro aberto **exige** `?org={slug}` de uma organização ativa que aceitou cadastro público (decisão da wiki `registro-e-aprovacao`, ADR-07), e o link "Cadastre-se" da tela de login nunca carregou `?org=` — nem em `/app/login`, antes do login unificado. Some-se que a única organização do `projeto-3` (`padrao`) tem `registro_habilitado = 0`. A correção do kit: o link só aparece quando há organização resolvível e a carrega (`/cadastro?org=…`); a organização precisa ligar "Aceita cadastro público". A mensagem de recusa não muda.
  - **Se negado (b)**: cai a rota `/cadastro`; permanece a correção do link.
- **RQ-06** — só a **solicitação** de reset ("Esqueci minha senha") foi citada. O formulário de redefinição (`/{painel}/password-reset/reset?token=…`), que chega por e-mail, e a verificação de e-mail não foram.
  - **Assumido**: escopo mínimo — `/esqueci-minha-senha` para a solicitação; o link do e-mail continua na rota do painel (funciona e tem token), e a verificação de e-mail idem. Se a revisão (RQ-07) mostrar que essas telas quebram com a chave ligada, entram como correção, não como URL nova.
  - **Se negado**: rotas `/redefinir-senha` (e afins) entram no passo das rotas externas.
- **RQ-03** — o slug novo não foi ditado. **Assumido**: `configuracoes-da-aplicacao` (kebab do rótulo). Redirect do slug antigo para o novo é **opcional**: o slug antigo só existiu em URLs internas do painel (menu), nunca em e-mail ou doc externa.
  - **Se negado**: só o slug muda.

### Perguntas devolvidas pela `feature-test-design` (2026-09-06)

- **RQ-04 / RQ-06 / RQ-07** — quem **já está autenticado** e abre `/cadastro` ou `/esqueci-minha-senha` vai para onde? O Filament manda para o painel corrente (`/app`), que um `admin` não acessa.
  - **Assumido** (falha fechado): o mesmo destino que `/login` dá a quem já entrou (CT-32 da ancestral: painel único, pretendida ou escolha). Invariante: sessão preservada, nenhuma conta criada.
  - **Se negado**: CT-54 inverte só o destino (`/app`); o invariante permanece.
- **RQ-04** — com tenancy, a organização do link "Cadastre-se" vem **só** de `?org=` na URL da tela de login?
  - **Assumido** (falha fechado): só por `?org=`; sem ele, sem link. Invariante: o link nunca aponta para organização que não aceita cadastro.
  - **Se negado**: CT-58 linha E5 ganha a resolução alternativa (ex.: única organização com cadastro público); E2–E4 não mudam.
- **RQ-03** — redirect do slug antigo, que este `00` chamou de opcional: o plano decidiu fazê-lo, **301** (o slug não volta atrás; o 302 fica para os redirects de painel → página única, que dependem da chave e não podem ser cacheados).
  - **Assumido**: 301. Invariante: o slug antigo **nunca renderiza a tela** (301 ou 404).
  - **Se negado**: CT-49 passa a esperar 404.
- **RQ-07** — a tela de conta indisponível tem link de volta ao login? **Medido**: tem (`resources/views/auth/conta-indisponivel.blade.php:38`, "Voltar ao login", `$voltarPara` montado no `ContaIndisponivelController`). Com a chave ligada ele deve terminar em `/login` (CT-63).

## Fora de Escopo (declarado)

- SSO externo (SAML/OIDC) — segue o alerta da v0.31.0.
- Mudar o Panel Switch em si (dropdown/modal dentro dos painéis).
- Migrar a tela de escolha para o layout do Auth Designer (a ADR da ancestral já explica por que ela usa o layout `simple`; se RQ-07 mostrar problema, vira adendo).
- Alterar o `projeto-3`: a análise é somente leitura; a correção chega por `kit:update`.
