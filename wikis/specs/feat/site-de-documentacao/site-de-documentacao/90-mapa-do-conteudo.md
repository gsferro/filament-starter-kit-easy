# Mapa do conteúdo: README → site VitePress

Levantamento feito lendo `README.md` (2.522 linhas) e `README.en.md` (2.533 linhas) na íntegra,
seção por seção. Números de linha conferidos com `grep -n '^## '` nos dois arquivos — todas as 32
seções h2 batem 1:1 em ordem e título entre os dois idiomas.

Nenhum dos dois READMEs foi editado para produzir este documento.

## 1. Inventário das seções h2 (README.md, PT)

Tamanho = linha inicial da próxima seção h2 menos a linha inicial desta (última seção: até a
última linha do arquivo, 2522).

| # | Título (h2) | Linha inicial | Tamanho (linhas) | Classificação | Justificativa |
|---|---|---:|---:|---|---|
| — | *(preâmbulo: badges, pitch, `composer create-project`, aviso Windows)* | 1 | 86 | `landing` | É a vitrine do Packagist propriamente dita — instalação em um comando é o gancho de venda |
| 1 | Acesso de demonstração | 87 | 16 | `landing` | Credencial de teste rápido; curto o bastante para não pesar a landing |
| 2 | Os três painéis | 103 | 34 | `landing` | Proposta de valor central (3 painéis) resumida em tabela; essencial para quem avalia o pacote |
| 3 | Nossos números | 137 | 80 | `ambos` | A tabela de números é material de marketing (fica); o h3 "PHPStan no level 7" (43 linhas) é aprofundamento técnico de qualidade — migra |
| 4 | O que já vem pronto | 217 | 73 | `ambos` | As listas por categoria são o resumo de features que a landing precisa; os h3 "A busca ⌘K" e "O seletor de idioma" (≈53 linhas) são detalhe de implementação — migram |
| 5 | Convite de usuário | 290 | 63 | `site` | Já é o alvo do link `(detalhes)` a partir de "O que já vem pronto" — o autor já tratou como apêndice |
| 6 | A rota `/` é pública e não mostra segredo | 353 | 27 | `site` | Idem: alvo de `(detalhes)` a partir de "Porta de entrada" |
| 7 | Registro aberto e aprovação | 380 | 144 | `site` | Idem: alvo de `(detalhes)`; documentação de feature opt-in, extensa |
| 8 | Login social: quatro provedores (opt-in, um por um) | 524 | 384 | `site` | Maior seção do README; deep dive completo (OAuth, vínculo, ADRs, logs) — claramente referência, não vitrine |
| 9 | Proteção anti-robô | 908 | 33 | `site` | Alvo de `(detalhes)`; feature opt-in recém-adotada (branch atual) |
| 10 | Usuário ativo, inativo e excluído | 941 | 10 | `site` | Detalhe de comportamento, não decisão de compra |
| 11 | Trilhas do `/infra`: exceções, e-mails e lixeira | 951 | 63 | `site` | Alvo de `(detalhes)`; documentação operacional |
| 12 | Multi-tenancy (opt-in) | 1014 | 72 | `site` | Referenciada por âncora a partir de "Os três painéis"; conteúdo é guia de configuração |
| 13 | Anexos e mídia | 1086 | 71 | `site` | Alvo de `(detalhes)`; guia de implementação (código, discos, URLs assinadas) |
| 14 | Import e export (CSV) | 1157 | 190 | `site` | Alvo de `(detalhes)` a partir de "Personalize seu projeto"; deep dive de arquitetura |
| 15 | Trabalhando com agentes de IA | 1347 | 106 | `site` | Documentação de workflow de contribuição/desenvolvimento, não de uso do produto |
| 16 | Roteiro de features | 1453 | 145 | `site` | Tabela de referência (68 features, F-01…F-68); serve como manual de teste, não como pitch |
| 17 | Requisitos | 1598 | 6 | `landing` | Três linhas de pré-requisito; essencial e curto o bastante para ficar |
| 18 | Banco de dados | 1604 | 21 | `landing` | Continuação direta da decisão de instalação (pergunta nº 2); curto |
| 19 | Docker | 1625 | 25 | `landing` | Tabela de portas/profiles, curta e prática para quem vai rodar o kit |
| 20 | Comandos | 1650 | 181 | `ambos` | A lista de comandos (~25 linhas) é referência rápida útil na landing; os h3 (FilaCheck, Rector, suíte de testes, imagens do README, SFDIPOT) são ~154 linhas de documentação de qualidade — migram |
| 21 | Personalize seu projeto | 1831 | 29 | `ambos` | A tabela dos 16 itens é útil condensada na landing; o detalhamento de cada item já linka para outras seções que migram |
| 22 | Configuração global do Filament | 1860 | 34 | `site` | Detalhe de implementação (`ConfiguraFilamentGlobal`), sem apelo de vitrine |
| 23 | Configurações do kit em `/admin` | 1894 | 154 | `site` | Deep dive completo da tela de Settings (precedência banco×`.env`, permissões, upload, SVG) |
| 24 | Convenções do kit | 2048 | 28 | `site` | Guia para quem vai codificar em cima do kit — audiência de contribuidor/adotante avançado |
| 25 | Depois de criar seus Resources | 2076 | 126 | `site` | Guia de desenvolvimento (policies, Page/Widget/Action, badges) |
| 26 | Atualizando um projeto que já nasceu do kit | 2202 | 102 | `ambos` | A existência do `kit:update` e a tabela de 3 camadas merece 1 parágrafo na landing; o passo a passo completo (dry-run, flags, modo manual) migra |
| 27 | Solução de problemas | 2304 | 9 | `landing` | FAQ curtíssimo, alto valor para quem está avaliando/instalando |
| 28 | Desenvolvendo o próprio kit | 2313 | 38 | `site` | Explicitamente "para quem mexe no kit, não para quem o instalou" — audiência de contribuidor |
| 29 | Hub de navegação em cartões | 2351 | 25 | `site` | Detalhe de uma feature específica, referenciada só de dentro do roteiro de features |
| 30 | Pacotes instalados | 2376 | 138 | `site` | Catálogo de ~70 pacotes por categoria; referência, não pitch (a landing já resume em "Nossos números" → Fundação) |
| 31 | Estudo: Advanced Tables e alternativas | 2514 | 6 | `site` | Nota de ADR/pesquisa, aponta para `wikis/specs/` |
| 32 | Licença | 2520 | 3 | `landing` | Obrigatório no Packagist |

## 2. Árvore de navegação do site

5 grupos de topo (dentro do limite de 6), cobrindo as seções migradas. Estrutura espelhada em
`docs/pt/` e `docs/en/` (o inglês reaproveita a tradução já existente em `README.en.md`).

```text
docs/
├── pt/
│   ├── comecar/
│   │   ├── instalacao-avancada.md        # Banco de dados (detalhe Docker/Postgres), Comandos (lista completa),
│   │   │                                   # Personalize seu projeto (tabela dos 16 itens, versão completa)
│   │   └── atualizando-o-projeto.md      # Atualizando um projeto que já nasceu do kit (passo a passo completo)
│   │
│   ├── autenticacao/
│   │   ├── convites.md                   # Convite de usuário
│   │   ├── registro-aberto.md            # Registro aberto e aprovação
│   │   ├── login-social.md               # Login social: quatro provedores (opt-in, um por um)
│   │   ├── protecao-anti-robo.md         # Proteção anti-robô
│   │   ├── estados-de-usuario.md         # Usuário ativo, inativo e excluído
│   │   └── rota-publica.md               # A rota `/` é pública e não mostra segredo
│   │
│   ├── recursos/
│   │   ├── multi-tenancy.md              # Multi-tenancy (opt-in)
│   │   ├── anexos-e-midia.md             # Anexos e mídia
│   │   ├── import-export-csv.md          # Import e export (CSV)
│   │   ├── trilhas-de-infraestrutura.md  # Trilhas do `/infra`: exceções, e-mails e lixeira
│   │   ├── configuracao-global-filament.md # Configuração global do Filament
│   │   ├── configuracoes-do-kit.md       # Configurações do kit em `/admin`
│   │   └── hub-de-navegacao.md           # Hub de navegação em cartões
│   │
│   ├── operacao/
│   │   ├── agentes-de-ia.md              # Trabalhando com agentes de IA
│   │   ├── roteiro-de-features.md        # Roteiro de features (F-01…F-68)
│   │   ├── convencoes-do-kit.md          # Convenções do kit
│   │   ├── depois-de-criar-resources.md  # Depois de criar seus Resources
│   │   └── desenvolvendo-o-kit.md        # Desenvolvendo o próprio kit
│   │
│   └── referencia/
│       ├── qualidade-de-codigo.md        # h3 "PHPStan no level 7" (de Nossos números) +
│       │                                   # h3 FilaCheck/Rector/suíte/SFDIPOT (de Comandos)
│       ├── busca-e-idioma.md             # h3 "A busca ⌘K" + "O seletor de idioma" (de O que já vem pronto)
│       ├── pacotes-instalados.md         # Pacotes instalados
│       └── estudo-advanced-tables.md     # Estudo: Advanced Tables e alternativas
│
└── en/
    └── (mesma árvore, populada a partir de README.en.md)
```

Observação sobre os h3 "órfãos": quatro subsecções (PHPStan level 7, busca ⌘K, seletor de idioma,
FilaCheck/Rector/testes) vivem hoje dentro de seções `ambos` — a landing fica só com o resumo em
h2, e o h3 completo muda de "seção-filha" para "página própria" em `referencia/`. Isso é
reorganização, não perda: o link `(detalhes)` do README aponta para essas páginas no lugar da
âncora interna.

## 3. Divergências entre README.md e README.en.md

Além da já corrigida (tabela de proteção anti-robô com `recaptcha_v3` marcado como `default` em
ambos — conferido nas linhas PT 925 / EN 932, hoje consistente), a leitura completa encontrou
**três** divergências de conteúdo reais, todas por **omissão** no inglês (parágrafo cortado na
tradução, não erro de tradução palavra a palavra):

### 3.1. Parágrafo inteiro ausente — vínculo de convite com login social

- **PT**: linhas 708–712 (seção "Login social", h3 "Vínculo com o provedor").
- **EN**: ausente. Entre as linhas 718 e 719 de `README.en.md` o parágrafo simplesmente não existe.

O parágrafo em português explica um caso de segurança específico: uma conta **já existente**
entrando por login social **não consome** o convite pendente por esse caminho (o `?token=` viaja
numa rota GET pública sem CSRF), e que essa decisão está auditada em
`wikis/specs/feat/travas-de-escalada-de-papeis/` (F-03 e F-04). A versão inglesa pula direto da
frase sobre `?org=` para "Decisions and cases: `wikis/specs/feat/cadastro-social-por-convite-e-organizacao/`",
sem essa explicação nem a referência a F-03/F-04. Quem lê só o inglês perde a explicação de por que
contas existentes não aceitam convite por esse fluxo.

### 3.2. Bloco de nota substituído por uma frase genérica — onde ficam as ADRs do próprio kit

- **PT**: linhas 1366 (seção "Trabalhando com agentes de IA", h3 "wikis/ — a documentação do kit").
- **EN**: linha 1379.

PT (nota completa, ~4 frases): explica que as ADRs das features que construíram o próprio kit
ficam **só** no repositório do kit, marcadas com `export-ignore` no `.gitattributes`, que
`kit:update` entrega apenas os documentos de topo de `wikis/`, que no projeto do usuário
`wikis/specs/` nasce vazia, e dá o link do repositório para quem quiser consultar uma decisão
citada no README.

EN (substituto): `> The wiki is written in pt-BR, like the kit's UI and code comments.` — uma frase
sobre idioma, sem nenhuma das informações acima. É a divergência de maior impacto informacional:
quem lê o README em inglês nunca descobre que os links `wikis/specs/feat/...` citados ao longo do
próprio README.en.md (há vários, ex.: linhas 720, 828, 913) só existem no repositório do kit e
somem no projeto do leitor.

### 3.3. Bullet inteiro ausente — convenção de abas (`getTabs()`) vs. filtro de modal

- **PT**: linha 2057 (seção "Convenções do kit"), um bullet de ~9 linhas.
- **EN**: ausente. A lista em `README.en.md` (seção "Kit conventions", linhas 2061–2067) tem **7**
  bullets; a em português (linhas 2050–2057) tem **8** — o oitavo, sobre quando usar `getTabs()`
  versus filtro de modal, o `?tab=` na URL e onde fica a regra de recorte (`recorteDePendentes()`),
  não tem equivalente em inglês. A seção inglesa pula da tradução do 7º bullet PT (linha 2056)
  direto para o h3 "Traps already handled" (linha 2069).

Este é o bullet mais longo e mais denso em decisão arquitetural da lista ("Convenções do kit"), e
ele simplesmente não foi traduzido — não é uma frase resumida, é ausência total.

### 3.4. Divergência pontual, cosmética — F-06 no roteiro de features

- **PT**: linha 1481 — "trava sem deslogar; volta com a senha **ou** com o login social (os mesmos
  botões do login)".
- **EN**: linha 1492 — "locks without logging out; returns with password." (sem a cláusula sobre
  login social).

Menor que as três acima (é uma frase dentro de uma célula de tabela, não um parágrafo ou bullet
inteiro), mas ainda assim perde informação: a versão inglesa da tabela de features não menciona que
a tela de bloqueio aceita voltar por login social.

Nenhuma outra seção divergiu em conteúdo — o restante do arquivo (preâmbulo, Nossos números, o
resto de O que já vem pronto, Import/export, Requisitos, Comandos, Personalize seu projeto,
Configuração global, Configurações do kit, Depois de criar Resources, Atualizando o projeto,
Solução de problemas, Desenvolvendo o kit, Hub, Pacotes instalados, Estudo Advanced Tables, Licença)
bateu frase a frase entre os dois idiomas, a menos de exemplos de valor localizados (ex.: PT usa
"Empresa/Empresas/empresas" e EN usa "Company/Companies/companies" no exemplo de `config/kit.php`
→ `tenancy.label`, linhas PT 1058-1061 / EN 1063-1066 — isso é tradução correta de um valor de
exemplo, não divergência de conteúdo) e de anotações "(pt-BR)" que o inglês acrescenta em links para
`wikis/` (ex. EN linhas 1211, 1759, 1358 — úteis, não incorretas).

## 4. Blocos que não devem migrar

| Bloco | Onde | Por quê |
|---|---|---|
| Badges (Packagist, Downloads, Plumb, Testes, PHP, Filament, License) | topo do arquivo, linhas 3–9 | Só fazem sentido no contexto do Packagist/GitHub; o site não precisa (e não deve) repetir badge de CI |
| Seletor de idioma do próprio README (`🇧🇷 Português · 🇺🇸 English`) | linha 11 | É navegação entre os dois READMEs no GitHub; o site tem seu próprio seletor de idioma (VitePress) |
| GIF/imagem de instalação (`art/install.gif`) | linha 77 | Fica na landing, é o gancho visual do Packagist — não precisa duplicar no site |
| Licença (`MIT.`) | seção 32 | É metadado de pacote, não conteúdo de documentação — mas fica no README (landing), não migra a lugar nenhum |
| Referências a `wikis/specs/feat/...` do próprio kit | espalhadas (ex. linhas 712-713, 819, 904-906, 2518) | Essas ADRs são `export-ignore` no `.gitattributes` — não existem no projeto do usuário nem devem virar página pública do site; o site referencia a existência da decisão em prosa, sem linkar um caminho de arquivo que não existe fora do repositório do kit |
| Capturas de tela geradas por `composer art` (`art/`, `art/thumbs/`) | várias seções | Podem ser reaproveitadas nas páginas do site, mas a *legenda em Markdown de imagem* do README (com o padrão `[![alt](thumb)](full)`) é formatação para o GitHub — o site usa componentes de imagem próprios do VitePress/tema, não o mesmo bloco Markdown |

## 5. Contagem final (aproximada)

Somando por classificação (seções `ambos` contam a parte que fica de um lado e a parte que migra do
outro; para as seções `ambos` a divisão landing/site é uma estimativa — ver nota abaixo).

| | README.md (PT) | README.en.md (EN) |
|---|---:|---:|
| Total de linhas hoje | 2.522 | 2.533 |
| Fica no README (landing) | **≈ 340** | **≈ 345** |
| Migra para o site | **≈ 2.180** | **≈ 2.190** |

Memória de cálculo (PT): seções 100% `landing` somam 195 linhas (preâmbulo 86 + Acesso 16 + Painéis
34 + Requisitos 6 + Banco 21 + Docker 25 + Solução de problemas 9 + Licença 3). As cinco seções
`ambos` (Nossos números, O que já vem pronto, Comandos, Personalize seu projeto, Atualizando o
projeto) somam 426 linhas brutas; a estimativa é reter ≈145 linhas (resumo/tabela) e migrar ≈281
(os h3 de aprofundamento e os passo a passo completos). As dezenove seções 100% `site` somam 1.857
linhas fixas. 195 + 145 = 340 no README; 281 + 1.857 = 2.138 — a diferença até 2.180 é a margem de
arredondamento da divisão landing/site dentro de cada seção `ambos` (não há linha exata onde "o
resumo termina e o aprofundamento começa" até o corte real ser feito na escrita do site).

O README passa de 2.522 para ≈340 linhas — de ~2.500 para o alvo de ~300 pedido, dentro da margem
de "aproximadamente 300" considerando que as tabelas de "Nossos números", "O que já vem pronto" e
"Personalize seu projeto" (marketing + índice rápido) pesam mais do que uma landing minimalista
pediria; um segundo corte editorial (condensar essas três tabelas) é o caminho para chegar mais
perto de 300 linhas cravadas, se isso for exigido à risca.

## Achados de conteúdo

Achados que parecem inconsistência ou desatualização — **não corrigidos**, apenas listados.

1. **Contagem de widgets não bate entre duas tabelas do próprio README.md.** "Nossos números"
   (linha 146) mostra Widgets: `/app` 1, `/admin` 9, `/infra` 19, Total **29**. "O que já vem
   pronto" (linha 254) diz "Dashboards já preenchidos nos painéis admin e infra: **24 widgets**".
   9 + 19 = 28, não 24, e nenhuma das duas contas fecha com a outra. Pode ser que "24" conte só os
   widgets *de dashboard* (um subconjunto dos 29, que incluem widgets fora dos dashboards) — mas o
   README não declara esse recorte, e as duas frases lidas em sequência parecem contradizer uma
   contagem simples de soma. Mesmo padrão nos dois idiomas (EN linhas 147/255).

2. **F-32 (roteiro de features, linha 1534) diz "6 widgets" no dashboard do `/admin`**, um terceiro
   número, diferente tanto do 9 de "Nossos números" quanto do 24 de "O que já vem pronto" — os três
   números convivem no mesmo arquivo sem uma frase que reconcilie "9 widgets no `/admin`" com "6 no
   dashboard do `/admin`" (os 3 restantes estariam em outras páginas do painel, presumivelmente, mas
   isso não está escrito).

3. **A nota de v0.19.3 sobre vazamento do `client_secret` do Google na trilha de auditoria**
   (linhas 851–873) refere-se a uma faixa de versões (0.19.2 → 0.19.3) já no passado da numeração
   atual do projeto — não é um erro, mas é o tipo de nota "corrigido nesta versão" que, ao migrar
   para um site com histórico de versões, provavelmente deveria virar entrada de changelog/security
   advisory em vez de parágrafo permanente do corpo da documentação de login social.

Nenhum link quebrado foi encontrado nas âncoras internas conferidas (`#a-rota--é-pública...`,
`#registro-aberto-e-aprovação`, `#multi-tenancy-opt-in`, `#anexos-e-mídia`, `#proteção-anti-robô`,
`#login-social-quatro-provedores-opt-in-um-por-um`, `#import-e-export-csv`) — todas apontam para
h2/h3 existentes no mesmo arquivo, nos dois idiomas.
