# Casos de Teste — mapa-interativo-do-kit: Mapa interativo do kit com a skill archify

> Requisito: `00-requisito.md` (RQ-01 a RQ-33, P-01 a P-16, Adendos 1 a 4, perguntas Q6, Q7, Q9, Q11, Q12, Q13, Q16, Q27, Q28, Q29 e Q30) *(alterado em 2026-11-23 — Adendo 2: era RQ-01 a RQ-26, P-01 a P-12 e o Adendo 1)* *(alterado em 2026-11-23 — Adendo 3: RQ-30 a RQ-32 e Q27 a Q29)* *(alterado em 2026-11-23 — Adendo 4: RQ-33 e Q30)* · Plano: `01-plano-acao.md` (só paths, rotas, stack e `## Superfície de UI`, recebidos por trecho no despacho) · `02`: só `## Superfície Livewire` ("não se aplica")
> Derivado do **requisito**, não do plano. Nenhum cenário foi escrito olhando implementação: ela ainda não existe.
> Base congelada: commit `d9608c5a` (2026-11-19). Todo "da base" deste arquivo foi medido nele, nesta derivação.
> **Revisão adversarial: feita por decisão da sessão, em duas rodadas, as duas fechadas.** O perfil não a exigia: nenhuma área é `completo` e nenhuma tem Impacto 3 (o motivo está abaixo da tabela). A primeira rodada deu 34 achados (ADV-01 a ADV-34), fechados na quarta rodada; a segunda deu 32 (ADV2-01 a ADV2-32), fechados na quinta. É o teto de duas rodadas da skill: não há terceira, e todo oráculo novo ou reescrito no fechamento da segunda foi conferido por comando contra dados reais (`## Revisão adversarial — segunda rodada`).
> *(alterado em 2026-11-20: rodada de revisão do step 7 — perguntas renumeradas para Q6 a Q15 e respondidas pela sessão; regras R27 a R33, CT-28 a CT-34 e CT-B04 a CT-B06 novos; os CT-B saem do conferidor de acessibilidade para um conferidor próprio, `site/verifica-mapa.mjs`)*
> *(alterado em 2026-11-20: terceira rodada — as duas perguntas novas da rodada anterior viraram Q16 e Q17 e foram fechadas; a R27 e o CT-28 partem dos manifestos e conferem as duas direções, com a referência de pacotes)*
> *(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial e P-12; regras R34 a R36, CT-35 a CT-43 e CT-B07 novos; R30, CT-32, CT-33 e R24-M5 estacionados na Q11; CT-B02 e CT-B03 reescritos pelos fatos de plataforma medidos pela sessão)*
> *(alterado em 2026-11-20: ajuste da quarta rodada — as duas perguntas novas da rodada viraram Q18 e Q19, decididas pela sessão no `01` (D25 e D26); a R10 ganha a quinta forma; a R5 casa o termo por palavra inteira; a R6 troca a regra da rota (40 linhas nos dois idiomas); a leitura da porta fica fechada pela P-10)*
> *(alterado em 2026-11-20: quinta rodada — fechamento da segunda revisão adversarial, a última; regra R37 e CT-44 e CT-45 novos; oráculos conferidos por comando contra o código, o catálogo, o README e o esqueleto do mapa já construído; os do site construído, conferidos de novo no commit `4adaf9a`, que a sessão publicou durante a rodada com os construtores do mapa)*
> *(alterado em 2026-11-21: step 9 — os 16 achados da revisão do diff, como Adendo. Premissas P-13 (A1, A5) e P-14 (A2, A4, A6, A11) no `00`; regras R38 a R40, CT-46 a CT-55 e a costura G5 novos; linhas novas no CT-09, CT-10, CT-35 e CT-38; no `05`, o CT-B08 novo e linhas novas nos CT-B01, CT-B02, CT-B04, CT-B06 e CT-B07. Todo oráculo novo foi conferido contra o commit `168d1827`, e a nota de cada um diz o que o dado real dá hoje; onde o dado reprova o oráculo, a lista está na nota e a decisão é da sessão. Sem revisão adversarial nesta rodada: o teto de duas já foi atingido, e o perfil não a exige)*
> *(alterado em 2026-11-21: step 9 — fechamento com as decisões da sessão. As perguntas Q20 a Q26 decididas; a costura G5 confirmada; o CT-46 por uma seta por verbo (Q22); a raiz da árvore com o nome do modelo (Q23); o CT-49 com o `--tentativas 0` (Q24); a R10 com duas formas novas — o invólucro do kit e a configuração global do pacote — e a leitura do middleware na forma do plugin, cada uma com a sua linha de controle no CT-10. Os vermelhos medidos que a sessão classificou como implementação ficam nas notas, e a linha "publicados" de cada CT diz o que deve dar depois dela)*
> *(alterado em 2026-11-23 — Adendo 2: a Q11 foi respondida pela recomendação (RQ-27 a RQ-29) e a sessão gravou a P-15 e a P-16. Destravados, com os IDs reservados: a R30 (o modelo guarda a condição; CT-32, e o CT-56 novo, com tenant), o CT-33 (agora da R41, nova: a peça condicional só entra no diagrama do modo em que fica disponível), a linha admin do CT-37, o passo 4 do CT-B01 e o R24-M5. Nova a R42 (a marca no desenho; CT-57). A população das peças condicionais foi medida pelo discriminante vivo — o `canAccess()` ou o `canView()`, como super_admin, com a configuração de fábrica, em cada modo — e está no Setup Global, com o procedimento; ela achou, além das duas que o `04` nomeava, quatro peças do `/app` que só abrem com tenant e uma do `/infra` que nenhuma chave `kit.*` liga (Q27). Linhas novas no CT-14, CT-19, CT-37 e CT-38; a definição de "a peça entra no diagrama do modo" mudou para todos que a usam (Setup Global). Perguntas novas: quatro, em numeração provisória — as de requisito viraram a Q27, a Q28 e a Q29 *(alterado em 2026-11-23 — Adendo 3)*, e a de desenho, a Q?3, foi decidida pela sessão. Cada "Medido no HEAD `c642c23c`" diz o que se espera hoje, e o executor confere. Sem revisão adversarial nesta rodada: o teto de duas já foi atingido)*
> *(alterado em 2026-11-23 — Adendo 3: o mantenedor respondeu as três perguntas de requisito da rodada pela recomendação — a Q27, a Q28 e a Q29, e a RQ-30 a RQ-32 —, e a sessão decidiu a Q?3, de desenho, as duas colisões, as leituras que esta derivação tomou por determinadas e as costuras. Nova a R43, a população da condição: a configuração de pacote conta como chave (o `CommandRecordResource`), a peça que só aparece dentro de tela condicional herda a condição (o `AcessosPorPainel`), e plugin e middleware nunca a têm; mora em linhas novas do CT-32, do CT-33, do CT-56 e do CT-57. Nova a R44, o card "Condições de disponibilidade" que a sessão decidiu; mora no CT-57 e no passo 4 do CT-B01. A marca do CT-57 e do passo 4 passa à redação decidida. O CT-37 e o CT-38 ligam também a configuração de pacote que o modelo nomeia. Pergunta nova: a Q30, o componente flutuante ligado a chave, que nenhuma das RQ-27, RQ-31 e RQ-32 alcança)* *(alterado em 2026-11-23 — Adendo 4)* A Q30 foi respondida pela RQ-33 (nota abaixo).
> *(alterado em 2026-11-23 — Adendo 4: a pergunta nova do Adendo 3 foi ao mantenedor como Q30 e voltou pela recomendação — RQ-33: o chat do assistente e o checklist do tutorial levam a marca da chave e do valor de fábrica, o chat as duas chaves, e nunca aparecem como sempre disponíveis. Entram na população da R43, avaliados pelo predicado que decide a renderização de cada um; linhas novas no CT-32, no CT-33, no CT-56, no CT-57 e no passo 4 do CT-B01. A sessão confirmou as duas escolhas desta derivação: o modelo guarda o valor que liga a configuração de pacote, e a marca do `CommandRecordResource` cita a `DatabaseSource` e "de fábrica" — esta deixa de ser premissa. A definição do discriminante, que vira o helper único do `tests/Pest.php`, está em `### A peça condicional e o discriminante`, com o contrato no primeiro item)*
> *(alterado em 2026-11-23 — step 9 do Adendo, achados A1 a A7: a revisão do diff do ciclo dos Adendos 2 a 4, como no Adendo — entrada, o `00` e este `04`. Sem cenário novo e sem regra nova: as linhas novas moram nos cenários que já cobrem o comportamento — o CT-50 (R40: a condição do modelo que o gerador não pode usar, e a ligação de registro com a ponta fora do modelo, A1), o CT-14 (R12: a ligação de registro de um modo só na árvore compartilhada, A2), o CT-57 (R42, R43 e R44: o "vazia" / "empty" da chave que é lista, A3; a marca em peça sem condição, A4; o nó escrito à mão pela D43, A5 e A6) e o CT-45 (R37: a homônima pela D43, A6; a coluna en do card, A7). A D43, decisão da sessão sobre o nó do diagrama escrito à mão, está no Setup Global, com a regra da árvore compartilhada (A2); a recusa do modo `sem` e da configuração de pacote que não liga fonte nova, nas notas da R40, da R41 e da R43. Origem: o que o achado viola, sem P-nn nova — a P-14 no A1; a P-09 e o Setup Global no A2; a RQ-27, a RQ-33 e o Setup Global no A3 a A6; a RQ-27 e a P-02 no A7. Cada "Medido no HEAD `1dd48acd`" diz o que se espera hoje e de quem é o vermelho: da implementação (causa b) ou da guarda de hoje, que o executor ajusta ao escrever a linha (causa a). Os 18 mutantes novos são achados medidos da revisão do diff e, como os do step 9 e os da revisão adversarial, não contam para o teto da regra. Uma pergunta nova, de raia desenho (Q?6). Sem revisão adversarial nesta rodada: o teto de duas já foi atingido)*
> *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: achado da reconciliação do ciclo dos Adendos, pela sessão, como no Adendo — entrada, o `00` e este `04`. A raiz da árvore de registro diz, no rótulo secundário, o primeiro modo do diagrama, e a árvore compartilhada, a `infra-registro`, sai "sem tenant" / "tenancy off", embora valha nos dois modos (P-09). A sessão decidiu, na raia de desenho, que o rótulo secundário da raiz diz o modo em que o diagrama vale, pelo termo da grade da página — e "os dois modos" / "both modes" na que vale nos dois. Sem cenário novo e sem regra nova: a R18 ganha a cláusula da raiz, e o CT-19, três linhas na forma de leitura da G5 — as árvores publicadas, a cópia da compartilhada com os modos noutra ordem e o controle, a mesma árvore valendo num modo só. A tabela dos termos está no Setup Global. Origem: a P-09, e a P-02 pelo termo en. Os 4 mutantes novos (R18-M8 a M11) são achado medido e, como os do step 9 do Adendo, não contam para o teto. Medido no HEAD `1dd48acd`: as duas primeiras linhas ficam vermelhas pelo gerador (causa b), e o controle passa. O `05` não muda: o conferidor não confere o rótulo secundário da raiz, e o CT-19 prova o mesmo mais barato. Sem revisão adversarial nesta rodada: o teto de duas já foi atingido)*
> *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2: os achados AD2-01 a AD2-03 da revisão do diff das correções do step 9 do Adendo (commit `b130c686`), como no Adendo — entrada, o `00` e este `04`. Sem cenário novo e sem regra nova: as linhas novas moram no CT-50 (R40: a condição do modelo com o modo em lista, com a fonte "nova" que só muda o namespace ou ganha `::class`, e com chave em maiúsculas fora de `KIT_*`, AD2-01; o arquivo de diagrama com `modos` vazio, repetido ou fora de `sem`/`com`, AD2-03), no CT-14 (R12: o mapa compartilhado com uma ligação de um modo só e com uma ponta registrada num modo só, AD2-02), no CT-38 (R34: a ligação de registro de um modo só nos diagramas compartilhados do `/infra`, com tenant, AD2-02) e, no `05`, no CT-B08 (R40: o conferidor recusa as mesmas condições do AD2-01, pela função com que lê o modelo real). A regra do Setup Global "O diagrama que vale nos dois modos e a ligação de um modo só" passa a valer, com o nome, para o mapa de arquitetura e para a peça registrada num modo só. Origem: o que o achado viola, sem P-nn nova — a P-14 no AD2-01 e no AD2-03, com a decisão da sessão sobre a redação da marca (RQ-27, RQ-30) e a P-09 no termo da raiz; a P-09 e o Setup Global no AD2-02. Cada "Medido no HEAD `b130c686`" diz o que se espera hoje e de quem é o vermelho. Os 15 mutantes novos (R12-M11 a M13, R34-M6, R36-M7, R40-M26 a M35) são achados medidos da revisão do diff e não contam para o teto; o R36-M7 é sem matador, por equivalência provada. O lugar do lado do conferidor no AD2-01 — o CT-B08, e não o CT-57 — é escolha desta derivação, de raia desenho, que a sessão confirma: a forma de leitura da G5 só importa o gerador. Sem revisão adversarial nesta rodada: o teto de duas já foi atingido)*

## Perfil de Derivação

| Área | P | I | P×I | Perfil |
|---|---|---|---|---|
| A — catálogo de features: base, numeração, fontes (README, páginas, pacotes), origem, cobertura pelo mapa e piso do registro | 2 | 2 | 4 | padrão |
| B — modelo de peças e ligações, provas, registro das peças dos painéis e a disponibilidade da peça condicional — a condição, a marca e o diagrama de modo *(alterado em 2026-11-23 — Adendo 2: a disponibilidade estava estacionada na Q11)* | 3 | 2 | 6 | padrão |
| C — página do mapa, grade painel × modo, tipos de visualização, diagramas de dado e dependências | 3 | 2 | 6 | padrão |
| D — publicação e site construído: build, fluxos de CI, visualizador, idioma da interface e acessibilidade | 3 | 2 | 6 | padrão |
| E — README e os diagramas Mermaid da base | 2 | 1 | 2 | mínimo |
| F — rule do catálogo | 1 | 2 | 2 | mínimo |

- **Por que Impacto 2 e não 3.** O mapa é documentação pública de um kit de código aberto. Um defeito desenha errado, engana o leitor e custa retrabalho, e o build seguinte o desfaz. Nenhuma área toca dinheiro, autorização, dado de terceiro ou perda irreversível.
  - Só um caminho publicaria conteúdo de fora do repositório: uma prova citando arquivo fora do commit. Ele não alcança segredo, porque o artefato publicado sai do CI, de um checkout limpo (`pages.yml`). O CT-09 o reprova mesmo assim, porque o link dessa prova daria 404 (P-07).
  - A tolerância da P-11 a dois defeitos do archify afrouxa o falha fechado. O risco dela é de acessibilidade do site público, e é reversível. Fica no Impacto 2, mas a técnica da R32 é escalada (abaixo).
- **Por que P é 3 em B, C e D.**
  - B confronta o modelo com o registro vivo dos três painéis nos dois modos. São centenas de peças (o spike da P-10 mediu 243 peças e 496 ligações só no `/admin`), as provas são por trecho, e há peça que o plugin registra e peça que só abre com uma condição.
  - C cruza dez tipos e o comparativo com três painéis e dois modos, com célula compartilhada, e confere diagrama de dado contra o CHANGELOG.
  - D baixa o archify no build (integração externa), depende de JavaScript no navegador e tolera dois defeitos de terceiro na acessibilidade.
- **Técnica escalada.**
  - A R4 usa BVA de 3 valores na borda 71/72, e não de 2. "Começa na F-72" e "pula a F-72" só se distinguem com o terceiro valor. A borda−1 (71) morre no CT-03.
  - A R32 usa o teto de mutantes do perfil completo, que é 6. A tolerância afrouxa o falha fechado (P-11), e cada um dos seis mutantes é um jeito diferente de ela vazar.
  - *(alterado em 2026-11-23 — Adendo 4)* A R43 usa o teto do perfil completo, 6, nos mutantes do Adendo 3: a população tem três frentes (configuração de pacote, peça que herda, plugin e middleware) e uma lacuna declarada. Os quatro do Adendo 4 (R43-M7 a M10, os componentes flutuantes) levam a marca do Adendo 4, com a Q30: nasceram de achado medido desta derivação, que o mantenedor transformou em requisito, e, como os do step 9 e os da revisão adversarial, não contam para o teto. Partir a regra renumeraria a rastreabilidade que a sessão já gravou para a R43.
  - *(alterado em 2026-11-23 — step 9 do Adendo, achados A1 a A7)* Os mutantes que os achados trouxeram ficam fora do teto, como os do step 9: a R40 passa de 18 para 25, a R42 de 6 para 12, a R12 de 8 para 10, a R37 de 3 para 5, e a R43 de 10 para 11. Partir as regras renumeraria a rastreabilidade que a sessão já gravou. *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* E a R18 passa de 7 para 11, com os quatro da raiz da árvore.
  - *(alterado em 2026-11-23 — Adendo 2)* A R42 também usa o teto do perfil completo, 6. A marca é o único texto que diz ao leitor que a peça não está disponível (RQ-27, RQ-28), e os seis mutantes são seis jeitos independentes de ela mentir; o sexto, a marca dada pelo nome, é o caso medido dos dois `UserResource` e dos dois `ConviteResource`, em que só os do `/app` são condicionais.
- **Técnicas aplicadas.**
  - EP.
  - BVA de 3 valores na numeração.
  - Tabela de decisão e produto cartesiano fechado. No registro, 3 painéis × 2 modos × 5 tipos de registro. No piso da varredura, 3 × 2 × 4, mais as 135 classes de `app/` fora de `app/Filament` (P-12). Na grade e na arquitetura por modo, 3 × 2. Nos tipos, 63 células por idioma. Na origem, F-nn × pacote nomeado × pacote da peça. No tipo de peça, classe × tipo declarado.
  - Estado × operação no número do catálogo: 15 células.
  - *(alterado em 2026-11-23 — Adendo 2)* Tabela de decisão da peça condicional (R30): disponível sem tenant × disponível com tenant × disponível com a chave ligada, 6 linhas, partida entre a suíte Kit (sem tenant) e a Tenancy (com), porque o modo é decidido no boot. *(alterado em 2026-11-23 — Adendo 3: a chave inclui a configuração de pacote, e a população, a peça que herda; EP da população na R43 — peça registrada, peça fora do registro que herda, plugin e middleware)*
  - Controle negativo em cópia adulterada em memória, no molde de `conferirDg10()` de `tests/Kit/GuardasDosDiagramasTest.php`.
  - Controle sintético no conferidor do site, decidido pela mesma função que decide o real (o molde dos controles de `site/verifica-acessibilidade.mjs`).
- Cenários: 57 · Regras: 44 · Mutantes previstos: 340 · Sem matador: 10 (mais 8 CT-B no `05`, nas regras R6, R12, R13, R14, R21, R23, R24, R31, R32, R40 e R44; os mutantes delas estão contados aqui) *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2: recalculado pelos quatro `grep -c`; eram 57 · 44 · 325 · 9. Mutantes: 15 novos — R12-M11 a M13 e R34-M6 (AD2-02), R36-M7 (AD2-02, sem matador por equivalência), R40-M26 a M32 (AD2-01; M30 a M32 morrem no CT-B08) e R40-M33 a M35 (AD2-03); cenários e regras não mudam — as linhas novas moram no CT-14, no CT-38, no CT-50 e no CT-B08. Sem matador: o R36-M7. A R40 passa de 25 para 35 mutantes, e a R12 de 10 para 13, fora do teto, como os do step 9)* *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: recalculado pelos quatro `grep -c`; eram 57 · 44 · 321 · 9. Mutantes: 4 novos — R18-M8 a M11, da raiz da árvore de registro; cenários, regras e sem matador não mudam — as linhas novas moram no CT-19)* *(alterado em 2026-11-23 — step 9 do Adendo, achados A1 a A7: recalculado pelos quatro `grep -c`; eram 57 · 44 · 301 · 9. Mutantes: 18 novos — R12-M9 e M10 (A2), R37-M4 (A6) e M5 (A7), R40-M19 a M25 (A1), R42-M7 e M8 (A3), M9 e M10 (A4), M11 e M12 (A6), e R43-M11 (A5); cenários, regras e sem matador não mudam — as linhas novas moram no CT-14, no CT-45, no CT-50 e no CT-57)* *(alterado em 2026-11-23 — Adendo 4: recalculado pelos quatro `grep -c`; eram 57 · 44 · 297 · 9. Mutantes: 4 novos, R43-M7 a M10, do componente flutuante; cenários, regras e sem matador não mudam — as linhas novas moram no CT-32, no CT-33, no CT-56, no CT-57 e no passo 4 do CT-B01)* *(alterado em 2026-11-23 — Adendo 3: recalculado pelos quatro `grep -c`; eram 57 · 42 · 287 · 8. Regras: a R43 e a R44, novas, sem cenário próprio — as linhas delas moram no CT-32, no CT-33, no CT-56, no CT-57 e no passo 4 do CT-B01. Mutantes: 10 novos — R43-M1 a M6 e R44-M1 a M4. Sem matador: o R43-M6)* *(alterado em 2026-11-23 — Adendo 2: recalculado pelos quatro `grep -c`: eram 53 · 39 · 264 · 7. Cenários: o CT-32 e o CT-33, destravados, e o CT-56 e o CT-57, novos. Regras: a R30, destravada, e a R41 e a R42, novas. Mutantes: 23 novos — R30-M1 a M7, R41-M1 a M5, R42-M1 a M6, R12-M8, R18-M7, R24-M5 (destravado), R34-M5 e R19-M9; dois são da revisão adversarial (R30-M6 e M7, do ADV-17) e não contam para o teto. Sem matador: o R19-M9. O passo 4 do CT-B01 voltou sem CT-B novo)* *(recalculado em 2026-11-21 pelos quatro `grep -c`, no step 9: eram 43 · 36 · 219 · 7, e 7 CT-B. Os 32 mutantes do step 9 — 27 dos achados e 5 das decisões da sessão sobre as perguntas e as 16 ligações do A9 — levam a marca `step 9 (A{n})`: são achados medidos da revisão do diff e, como os da revisão adversarial, não contam para o teto da regra)* *(alterado em 2026-11-21 — step 9, re-revisão: mais 3 mutantes — R9-M13, R40-M7 e R40-M8 —, de 251 para 254, recontados pelos quatro `grep -c`; cenários, regras e sem matador não mudam)* *(alterado em 2026-11-21 — step 10, a P-14 sobre a projeção: mais 6 mutantes — R40-M9 a M14 —, de 254 para 260, recontados pelos quatro `grep -c`; cenários, regras e sem matador não mudam)* *(alterado em 2026-11-21 — step 11, QA-04: mais 4 mutantes — R40-M15 a M18 —, de 260 para 264, recontados pelos quatro `grep -c`; cenários, regras e sem matador não mudam)*
<!-- derivado por grep -c (template-04.md §Contagem do cabeçalho) -->

**Divergências declaradas.**
- **A costura `Pest feature HTTP` mora em `tests/Kit` e `tests/Tenancy`, não em `tests/Feature`.** No kit, o `tests/Pest.php` liga o `TestCase`, o `RefreshDatabase` e o grupo `kit` a essas duas pastas. O `tests/Feature` é do projeto que nasce do kit. O `tests/Unit` não liga o `TestCase`, então uma guarda que precisa do `base_path()` ou do painel não cabe nele. Vence a convenção do projeto.
- **Os CT-B rodam num conferidor Node + Playwright próprio, `site/verifica-mapa.mjs`, e não no `pest-plugin-browser`.**
  - O plugin sobe a aplicação Laravel e não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`).
  - O arranjo é o da wiki irmã `diagramas-da-arquitetura`, mas num arquivo à parte: o `site/verifica-acessibilidade.mjs` já carrega o `[CT-B01]` e o `[CT-B02]` daquela wiki, e os IDs desta também começam em 01.
  - O `ids-ct.sh` só lê o `it()` e o `test()` do Pest, então acusa os CT-B desta wiki como sem teste. Isso é esperado, por desenho.

## Varredura SFDIPOT

| Letra | O que existe nesta feature | Cenários gerados |
|---|---|---|
| S | <ul><li>Em `site/mapa/`: o modelo de peças, o modelo de ligações e um arquivo por diagrama; a tradução da interface do visualizador; o gerador e o download do archify.</li><li>A página "Mapa interativo" em pt e en, com navegação, redirecionamento e stub.</li><li>O catálogo (a página Roteiro de features em pt e en), a referência de pacotes instalados e o README em pt e en.</li><li>A rule do catálogo e a linha dela no índice.</li><li>`site/package.json`, o conferidor novo `site/verifica-mapa.mjs` e o conferidor de acessibilidade.</li><li>`pages.yml` e o job `site` do `ci.yml`.</li><li>Nenhum arquivo de `app/` muda (RQ-15 e a Superfície Livewire do `02`).</li></ul> | CT-01 a CT-45, CT-56, CT-57 *(alterado em 2026-11-23 — Adendo 2: o CT-32 e o CT-33 estavam estacionados na Q11)* |
| F | <ul><li>Desenhar peças e ligações por painel e por modo.</li><li>Provar cada ligação com o arquivo e o trecho.</li><li>Cobrir toda feature do catálogo e toda peça registrada.</li><li>Mostrar a grade painel × modo e os tipos.</li><li>*(alterado em 2026-11-23 — Adendo 2: estava estacionada na Q11)* Guardar a condição da peça condicional, marcá-la em todo diagrama que a desenha e desenhá-la só nos diagramas do modo em que fica disponível.</li><li>Ancorar no código cada transição de fluxo.</li><li>Unificar e numerar o catálogo.</li><li>Falhar quando o código diverge (P-03).</li><li>Publicar no build do commit.</li><li>Deixar o leitor buscar a peça e ler a prova, na língua da página.</li><li>Passar pela conferência de acessibilidade por amostra.</li></ul> | CT-03, CT-06, CT-08 a CT-14, CT-17, CT-20, CT-25, CT-29 a CT-31, CT-36 a CT-41, CT-44, CT-45, CT-B01 a CT-B07 |
| D | <ul><li>Peças e ligações: 243 e 496 só no `/admin`.</li><li>Os diagramas.</li><li>Na base: 74 F-nn por idioma (F-01 a F-71, mais F-03a, F-03b e F-03c), 46 itens de feature do README por idioma, 39 páginas do site por idioma, 91 pacotes nos manifestos e 87 na referência por idioma (os quatro que faltam a entrega acrescenta, Q16), 141 versões com data no CHANGELOG, mais o `[Unreleased]`, sem data, e 135 classes de `app/` fora de `app/Filament` (P-12). Das 74 linhas do catálogo, 40 trazem no "onde fica" um caminho de rota sem "→", nos dois idiomas.</li><li>Provas: arquivo do kit versionado (válida), e as inválidas — ausente, vazia, só de espaços, só em comentário, fora do commit (`vendor/` ou fora do git).</li><li>Peças de disponibilidade condicional *(alterado em 2026-11-23 — Adendo 2: eram duas, nomeadas à mão, com a marca estacionada na Q11)*: medidas pelo discriminante vivo (Setup Global), 15 peças distintas registradas — pelo modo (o `TenantResource` e quatro do `/app`), pelo modo e pela chave (o `ProjetoResource`, `KIT_DEMO`), pela chave (`KIT_AGENTES_IA`, `KIT_ONBOARDING`, `KIT_HUB`), e uma que nenhuma chave `kit.*` liga (o `CommandRecordResource`, Q27). Partições da condição: nenhuma, o modo, a chave e o valor de fábrica, os dois. *(alterado em 2026-11-23 — Adendo 3: o `CommandRecordResource` é condicional pela configuração de pacote `command-center.sources` (RQ-30); e entra a peça que painel nenhum registra e que herda a condição da tela que a contém, o `AcessosPorPainel` (RQ-31) — 16 peças. Plugin e middleware ficam sem condição (RQ-32). Fora do alcance das três: o componente flutuante ligado a chave (Q30))* *(alterado em 2026-11-23 — Adendo 4: entra, pela RQ-33 — o chat do assistente, com duas chaves, e o checklist do tutorial —, e a população tem 18 peças)*</li><li>Os modos (sem e com tenant), os 10 tipos e o comparativo, os idiomas pt e en, os temas claro e escuro.</li><li>Cardinalidade 0/1/N: painel sem diagrama, feature sem peça, peça registrada ou classe de `app/` sem F-nn, ligação sem diagrama, transição sem âncora, célula sem diagrama nem motivo, amostra vazia.</li><li>Dado de outro tenant não se aplica: o modo é configuração, não dado.</li></ul> | CT-01, CT-05, CT-06, CT-09, CT-14, CT-17, CT-21, CT-28, CT-29, CT-31, CT-35, CT-39, CT-41, CT-B05 |
| I | <ul><li>A página estática `/{pt,en}/referencia/mapa-interativo/` e os HTML `/mapa/{pt,en}/{slug}.html`.</li><li>O README no GitHub, o catálogo, a referência de pacotes e a rule, que os agentes leem pelo índice.</li><li>O registro vivo dos painéis: `Filament::getPanels()` e os getters de cada painel; o roteador da aplicação, para o "onde fica" do catálogo.</li><li>Os arquivos citados pelas provas e pelas âncoras de fluxo, e os links de evidência para o repositório do `support.source` do `composer.json`.</li><li>O `CHANGELOG.md`.</li><li>`npm run build` e os dois workflows.</li><li>*(alterado em 2026-11-21 — step 9, achados A2, A4, A6 e A11)* As ferramentas de autor pela linha de comando: `node mapa/gerar.mjs --saida <pasta> [slug …]` e `node mapa/posicionar.mjs <slug> [--tentativas N]`, com os argumentos, a pasta de saída e os arquivos de entrada que elas leem.</li></ul> | CT-07, CT-11, CT-12, CT-15, CT-25, CT-26, CT-28, CT-31, CT-36, CT-39, CT-42, CT-B03; CT-48 a CT-51, CT-B08 |
| P | <ul><li>PHP 8.4, Laravel 13.42, Filament 5.10.2 (getters conferidos no vendor) e Pest 5.3.7.</li><li>Node 24, Astro 7.3.3 + Starlight 0.42.2, Playwright 1.63 e axe-core 4.13.</li><li>archify 3.2.0 no commit `4478129`, segundo o README e o `schemas/README.md` do archify nesse commit: não tem catálogo pt (tem en, zh-CN, zh-TW, es, ko e ar); o `meta.locale` escolhe a interface e o `lang`, mas não traduz o texto escrito; a prova é por nó (`sources`, de 1 a 3), sem prova por aresta; a casca do comparativo existe só em inglês (P-11); e há dois defeitos de acessibilidade tolerados (P-11).</li><li>Medido pela sessão num HTML do archify, no commit fixado, no Chromium:<ul><li>o passaporte de uma peça em foco mostra o rótulo, o tipo, "origem verificada {repositório} @ {commit}", as provas da própria peça (até três) como links para o GitHub no commit, e só as contagens das ligações;</li><li>os cards ficam no painel lateral (`.reader-rail .card`), sempre presentes;</li><li>o DOM traz `data-node-id`, `data-node-label`, `data-edge-from`, `data-edge-to` e `data-edge-id`, e o `lang` do documento segue o `meta.locale`;</li><li>nenhuma requisição externa e nenhum erro.</li></ul></li><li>O `examples/locales/pt.json` do archify (461 chaves) fica fora do manifesto dos catálogos embutidos. O kit entrega o pt pelas traduções do diagrama, e o en é o `locales/en.json` (486 chaves).</li><li>Nos mapas de arquitetura, a aresta é direta, de agrupamento ou de porta.</li><li>GitHub Pages sob a base `/filament-starter-kit-easy`.</li><li>Desenvolvimento no Windows (caminho com `\`, fim de linha CRLF) e CI no Linux.</li><li>*(alterado em 2026-11-21 — step 9, achados A4, A12 e G5)* O caminho pelo qual a ferramenta é chamada: por um link (junção no Windows, que não pede privilégio; link simbólico no Linux), ou numa pasta com espaço e acento. E o `node` na suíte do kit, que os jobs `qualidade` e `cobertura` do `ci.yml` não instalam com `actions/setup-node` — decidido (Q21): passam a instalar.</li></ul> | CT-09, CT-14, CT-16, CT-40, CT-B01 a CT-B07; CT-46 a CT-52, CT-B06, CT-B08 |
| O | <ul><li>O leitor do site avalia o kit.</li><li>O mantenedor cria feature: número e rule.</li><li>O agente de IA lê a rule pelo índice.</li><li>O CI roda em todo PR e publica no Pages.</li><li>Volume: nenhum painel cabe num desenho só (P-10).</li><li>Uso indevido: prova copiada de outra ligação ou tirada de outro arquivo, prova que só casa em comentário, número reaproveitado, aresta inventada ou invertida, catálogo pt com o texto en, conferidor que não reprova o job, tolerância de acessibilidade larga demais.</li><li>*(alterado em 2026-11-23 — Adendo 2)* Uso indevido da disponibilidade: a marca que mente (chave vizinha, valor de fábrica trocado, modo invertido, marca dada pelo nome à peça homônima de outro painel), a condição dada à peça que só pede permissão ou só esconde o item de menu (P-16), e a peça condicional desenhada num diagrama compartilhado. *(alterado em 2026-11-23 — Adendo 3)* E a marca dada ao plugin ou ao middleware que serve a tela condicional (RQ-32), a configuração de pacote vizinha, ligada de fábrica, posta como a chave (RQ-30), e a peça que herda desenhada nos dois modos (RQ-31).</li></ul> | CT-03, CT-07, CT-10, CT-14, CT-40, CT-42, CT-B06, CT-B07; CT-32, CT-33, CT-57 |
| T | <ul><li>O mapa reflete o commit que publica (P-07), e a linha do link de prova é a desse commit.</li><li>A suíte do kit roda em todo PR, e o job `site` só roda quando o PR toca `docs/` ou `site/`. Por isso as guardas de prova, de registro e de catálogo moram na suíte do kit.</li><li>A numeração só cresce (P-08), e o número removido continua removido nas entregas seguintes.</li><li>Envelhecem: o trecho que some do código, a âncora de fluxo, o recurso novo no painel, a classe nova em `app/` (P-12), o pacote atualizado, a versão nova do CHANGELOG e o tempo medido. *(alterado em 2026-11-23 — Adendo 2)* E a disponibilidade: a chave nova desligada de fábrica, o `canAccess()` que passa a ler o modo, a chave que muda de valor de fábrica — o discriminante vivo pega cada uma no PR que a traz.</li></ul> | CT-03, CT-04, CT-09, CT-11, CT-25, CT-29, CT-31, CT-39, CT-41, CT-B02; CT-32, CT-56 |

## Mapa de Regras

*(alterado em 2026-11-20: quarta rodada — fechamento da revisão adversarial; R34 a R36 novas; R30 estacionada na Q11. Quinta rodada — segunda revisão adversarial; R37 nova)*
*(alterado em 2026-11-23 — Adendo 2: a R30 destravada, só com a metade do modelo; a metade do desenho virou a R41 (onde a peça entra) e a R42 (a marca). A R34 perde a exceção da peça condicional e ganha a RQ-28, a RQ-29 e a P-15; a R18, a RQ-28; a R24, a RQ-27; a R37, a RQ-27 e o `canView()`)*
*(alterado em 2026-11-23 — Adendo 3: a R43, nova, diz quem está na população da condição — RQ-30 a RQ-32 —, e mora nos cenários da R30, da R41 e da R42; a R44, nova, é o card "Condições de disponibilidade" que a sessão decidiu na Q?3. A R41 ganha a RQ-31, e a R42, a RQ-30)*

| Regra | Área (perfil herdado) | Origem (`RQ`/`P-nn`) | Técnica | Cenários |
|---|---|---|---|---|
| R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature e o mesmo estado em cada número, uma linha por F-nn e uma origem do glossário em cada | A (padrão) | RQ-21, RQ-22, RQ-04, RQ-05, P-02 | EP + controles | CT-01 |
| R2 — README, páginas do site e mapa só citam F-nn que o catálogo tem, e nenhum deles lista feature por fora dele | A (padrão) | RQ-22, RQ-21, P-08 | EP + controles | CT-02 |
| R3 — Toda F-nn da base continua com a sua feature, ou fica marcada como removida com o motivo, e a removida não volta | A (padrão) | P-08, RQ-22 | estado × operação + controles | CT-03 |
| R4 — A feature nova recebe o próximo número livre: a F-72, e as seguintes em sequência, sem buraco e sem sufixo novo | A (padrão) | P-08 | BVA 3-valores (escalada) | CT-04 |
| R5 — Todo item de feature do README da base e toda página do site correspondem a ao menos uma F-nn — os quinze itens que a base não cobre, pelo termo fixado —, ou a página está na lista das que não descrevem feature, com o motivo | A (padrão) | RQ-19, RQ-04, RQ-05, P-02 | EP por fonte + controles | CT-05, CT-35 |
| R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, com a F-nn no desenho da peça; todo caminho do "onde fica" resolve; e nenhuma peça entrega F-nn removida | A/B (padrão) | RQ-04, RQ-05, RQ-21, P-03, P-10, P-08 | EP por origem + tabela de decisão + controles | CT-06, CT-36, CT-44, CT-B01 (no `05`) |
| R7 — Manter o catálogo único é uma rule indexada, que alcança os lugares onde a feature nasce e manda o número só crescer | F (mínimo) | RQ-23, P-08 | EP | CT-07 |
| R8 — Toda peça do modelo é peça com nome que existe, do tipo que a classe dela é, dentro do conjunto fechado, e aparece uma vez | B (padrão) | RQ-24, P-03 | EP + tabela de decisão (classe × tipo) + controles | CT-08 |
| R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário pela sintaxe do tipo de arquivo, e a prova não guarda número de linha, nem no modelo nem nos arquivos de diagrama | B (padrão) | RQ-24, P-03, P-04, P-07 | EP (uma partição inválida por linha) + controles | CT-09, CT-52, CT-53 *(step 9: A3, A7, A10)* |
| R10 — A prova é a da ligação: está no arquivo da origem, num trait ou concern que a origem usa, no provider do painel (descoberta, plugin — direto ou pelo invólucro do kit — ou configuração), na configuração global que o kit faz do pacote que registra a peça sozinho, no método de contrato do alvo ou, quando a origem é peça de pacote, num arquivo do kit que nomeia a origem; o trecho é o dessa ligação; e as duas pontas são peças | B (padrão) | RQ-24, P-03, P-07 | EP (forma da prova × arquivo) + controles | CT-10 |
| R11 — Tudo que o painel registra tem ligação de registro no modelo, e o modelo não dá a um modo o que o painel não registra nele | B (padrão) | P-05, RQ-08, RQ-10, RQ-11, P-03 | tabela de decisão (3 × 2 × 5, nas duas direções) + controles | CT-11, CT-12, CT-13, CT-55 *(step 9: A15)* |
| R12 — Toda ligação do modelo está desenhada em diagrama publicado, direto ou por agrupamento que nomeia os membros num card do diagrama ou no rótulo dele; menção em texto não conta | B (padrão) | P-10, RQ-08, P-09 *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* | EP (forma da aresta) + controles | CT-14, CT-B02 (no `05`) |
| R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num item próprio de card do painel lateral do próprio diagrama, inteiro e à vista sem foco; o comparativo fica fora | B (padrão) | P-04, P-07, RQ-08 | rastreio exaustivo no site construído | CT-B02 (no `05`) |
| R14 — O leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build | B (padrão) | RQ-07, P-04, P-07 | EP (idioma) + amostra por `architecture` | CT-B03 (no `05`) |
| R15 — A página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe | C (padrão) | RQ-03, P-02 | EP | CT-15 |
| R16 — Todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido; o título só fica igual quando é o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | C (padrão) | P-02, RQ-03 | EP (tipo de texto) + controles | CT-16 |
| R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado e só ele, e a célula não leva a diagrama do outro modo nem de outro painel | C (padrão) | RQ-10, RQ-11, P-06, P-09 | tabela de decisão (3 × 2) + controles | CT-17, CT-18, CT-43 |
| R18 — Só se compartilha o que é igual nos dois modos, o que é igual não se duplica, toda ligação desenhada vale no modo do diagrama, e a raiz da árvore de registro diz esse modo — "os dois modos", na compartilhada *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: a cláusula da raiz)* | C (padrão) | P-09, RQ-11, RQ-28 *(alterado em 2026-11-23 — Adendo 2: RQ-28)*, P-02 *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: o termo en da raiz)* | tabela de decisão + controles; EP do `modos` da árvore — sem, com, os dois, em qualquer ordem — nas linhas da raiz *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | CT-19 |
| R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, cada combinação tipo × painel × modo tem diagrama ou motivo traduzido, e só é do kit inteiro o diagrama que não desenha peça registrada por painel | C (padrão) | RQ-06, RQ-25, P-06, P-02 | produto cartesiano fechado (63 células por idioma) + controles | CT-20, CT-21 |
| R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel | C (padrão) | RQ-25, RQ-11, P-04 | EP + controles | CT-22 |
| R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base nos quatro campos, `site/mapa/` só tem os arquivos do mapa, e o HTML de cada diagrama não carrega recurso de fora dele | C (padrão) | RQ-25 | EP (lista fechada) + controles + rastreio no site construído | CT-23, CT-B01 (no `05`) |
| R22 — Há fluxo publicado, e, nos tipos `workflow`, `sequence` e `dataflow`, todo passo percorre peça do modelo | C (padrão) | RQ-09, P-03 | EP (tipo de fluxo) + controles | CT-24 |
| R23 — O mapa nasce no build do commit que publica, os dois fluxos de CI o conferem com o conferidor do mapa antes de enviar, e a falha do conferidor reprova o job | D (padrão) | P-07, RQ-03, RQ-17, P-11 | EP por workflow + controles | CT-25, CT-42, CT-B06 (no `05`) |
| R24 — Todo diagrama publicado abre desenhado, com as peças que a fonte dá a ele — e a marca da peça condicional à vista no carregamento *(alterado em 2026-11-23 — Adendo 2)* — e a interface do visualizador montada, sem erro de console nem exceção, no idioma da página | D (padrão) | RQ-07, RQ-03, P-02, RQ-27 *(alterado em 2026-11-23 — Adendo 2: RQ-27)* | rastreio exaustivo no site construído | CT-B01, CT-B03, CT-B04 (no `05`) |
| R25 — O README de cada idioma leva ao mapa do mesmo idioma | E (mínimo) | RQ-02, P-02 | EP | CT-26 |
| R26 — Os diagramas Mermaid da base continuam onde estavam | E (mínimo) | P-01, RQ-25 | EP | CT-27 |
| R27 — Todo pacote instalado está na referência de pacotes e numa F-nn de origem pacote, ou na lista dos sem feature própria, com o motivo; e todo pacote que a referência lista está instalado | A (padrão) | RQ-19, RQ-04, P-03 | EP por partição de pacote + controles | CT-28 |
| R28 — Toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista da guarda das peças e classes sem feature própria, com o motivo | A/B (padrão) | RQ-20, RQ-04, RQ-05, P-05, P-12 | tabela de decisão (3 × 2 × 4) + EP (classe de `app/`) + controles | CT-29, CT-30, CT-41 |
| R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela, e o título visível do marco aparece na seção dessa versão; e cada tempo medido traz a data e a fonte da medição | C (padrão) | P-03, P-06 | EP (uma partição inválida por linha) + controles | CT-31 |
| R30 — Toda peça que o painel registra e que fica indisponível num modo, com a configuração de fábrica, guarda no modelo a condição que o discriminante mede — o modo, a chave e o valor de fábrica, ou os dois —, com a âncora do código que decide; e a peça disponível nos dois modos não guarda condição *(alterado em 2026-11-23 — Adendo 2: destravada; estava estacionada na Q11, ADV-17 e ADV-31; a metade do desenho foi para a R41 e a R42)* | B (padrão) | RQ-27, RQ-04, RQ-05, RQ-11, P-03, P-15, P-16 | tabela de decisão (disponível sem tenant × com tenant × com a chave ligada, 6 linhas) + controles em cópia | CT-32, CT-56 |
| R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo | D (padrão) | P-11, P-02, RQ-03 | rastreio exaustivo no site construído + controles | CT-B04, CT-B07 (no `05`) |
| R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento | D (padrão; técnica escalada) | P-11, RQ-03, RQ-07 | amostra estratificada + controle sintético | CT-B05, CT-B06 (no `05`) |
| R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11, a do comparativo na P-04 e o limite da varredura da P-12 | D (padrão) | P-11, P-12, P-04, P-02 | EP (idioma) | CT-34 |
| R34 — Os diagramas de arquitetura e a árvore de registro de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo que fica disponível nesse modo — com a chave ligada (P-15) —, e nada além *(alterado em 2026-11-23 — Adendo 2: era "todo o registro do painel nesse modo, e nada que ele não registra nesse modo", com a peça condicional fora, na Q11)* | C (padrão) | P-09, RQ-11, RQ-10, P-05, RQ-28, RQ-29, P-15 | tabela de decisão (3 × 2) + controles | CT-37, CT-38 |
| R35 — Toda transição de fluxo publicado tem âncora que resolve; no `lifecycle`, todo estado também; e duas transições seguidas ancoradas no mesmo método estão na ordem do código | C (padrão) | P-03, RQ-09 | EP + controles | CT-39 |
| R36 — O que o mapa desenha está no modelo: no mapa de arquitetura e na árvore de registro, toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e todo diagrama de painel só desenha, como peça, peça do painel ou do kit inteiro, além da caixa de porta | B (padrão) | P-03, RQ-24, P-10, RQ-10 | EP (forma da aresta) + controles | CT-40, CT-54 *(step 9: A8)* |
| R37 — Toda marca de disponibilidade que o mapa publica é a do código: a chave que decide o acesso da peça, com o valor de fábrica dela, ou o modo, ou os dois *(alterado em 2026-11-23 — Adendo 2: "ou os dois")* | B (padrão) | P-03, RQ-04, RQ-05, RQ-11, RQ-27, P-02 *(alterado em 2026-11-23 — step 9 do Adendo, achado A7)* | EP + controles | CT-45 |
| R38 — O traço da seta e a caixa de agrupamento do mapa de arquitetura só dizem do que representam o que o modelo dá ao diagrama, no modo dele *(regra nova em 2026-11-21: step 9, achados A1 e A5)* | C (padrão) | P-13, RQ-08, P-10, P-02 | EP (verbos do par de pontas: um, dois, três; uma seta por verbo, Q22) + BVA na contagem da caixa (0, 1, 2) + controles | CT-46, CT-47 |
| R39 — As ferramentas do mapa não apagam o que não geraram e não terminam com código 0 sem ter feito o que se pediu, por qualquer caminho que sejam chamadas *(regra nova em 2026-11-21: step 9, achados A2 e A4)* | D (padrão) | P-14, P-07 | EP (pasta de saída; caminho da chamada) + controles | CT-48, CT-51 |
| R40 — Argumento sem valor válido e arquivo de entrada quebrado são recusados antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o culpado *(regra nova em 2026-11-21: step 9, achados A6 e A11)* | D (padrão) | P-14, P-03 | EP (uma partição inválida por linha) | CT-49, CT-50, CT-B08 (no `05`) |
| R41 — A peça condicional só entra nos diagramas que valem só nos modos em que ela fica disponível — com a chave ligada (P-15) —, e nunca num diagrama compartilhado se fica indisponível num dos modos; o `/admin` tem diagramas separados sem e com tenant, e o `TenantResource` só está nos de com *(alterado em 2026-11-23 — Adendo 2: regra nova)* | B (padrão) | RQ-28, RQ-29, P-15, RQ-11, P-09, P-10, RQ-31 *(alterado em 2026-11-23 — Adendo 3: RQ-31)* | tabela de decisão (condição da peça × modos do diagrama) + controles em cópia | CT-33 |
| R42 — Em todo diagrama que a desenha, a peça condicional leva a marca da condição que o modelo dá a ela, no idioma do diagrama e à vista no carregamento — no nó dela, ou na linha do card que a nomeia como membro de agrupamento —; e a peça sem condição não leva marca *(alterado em 2026-11-23 — Adendo 2: regra nova)* | B (padrão; técnica escalada) | RQ-27, RQ-28, P-15, P-02, P-17 *(alterado em 2026-11-23 — step 11)*, RQ-30 *(alterado em 2026-11-23 — Adendo 3: RQ-30, a marca da configuração de pacote)*, RQ-33 *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* | EP (forma do desenho: nó, membro; condição: modo, chave, as duas; idioma) + controles em memória | CT-57, CT-B01 (no `05`, passo 4, pelo R24-M5) |
| R43 — A população da condição: a peça que a configuração de pacote deixa fechada de fábrica conta como peça de chave; a peça que painel nenhum registra e só aparece dentro de uma tela condicional herda a condição dela; o componente flutuante ligado a chave — o chat do assistente e o checklist do tutorial — leva a condição das chaves que decidem a renderização dele; e plugin e middleware nunca guardam condição nem levam marca *(alterado em 2026-11-23 — Adendo 3: regra nova)* *(alterado em 2026-11-23 — Adendo 4: o componente flutuante, RQ-33)* | B (padrão; teto de mutantes do completo) | RQ-30, RQ-31, RQ-32, RQ-33, P-16, RQ-27 | EP (peça registrada; peça fora do registro que herda; componente flutuante; plugin e middleware) + controles em cópia | CT-32, CT-33, CT-56, CT-57 (linhas do Adendo 3 e do Adendo 4) |
| R44 — Todo diagrama que desenha peça condicional traz o card "Condições de disponibilidade" ("Availability conditions"), com uma linha por peça condicional desenhada, a condição dela e a âncora do código que a decide, inteira e à vista sem foco *(alterado em 2026-11-23 — Adendo 3: regra nova; o card é decisão de desenho da sessão, na Q?3)* | B (padrão) | RQ-27, P-03, P-02, P-17 *(alterado em 2026-11-23 — step 11)* | EP (peça do card: desenhada como nó, como membro, não desenhada) + controles em memória | CT-57 (linhas do card), CT-B01 (no `05`, passo 4) |

### Cláusulas sem regra

- RQ-01 — restrição de processo, sem regra e sem cenário. A análise do archify, registrada antes do uso, confere-se pela wiki e pela ordem dos commits, no quality gate (dimensão A).
- RQ-12 — restrição de processo, sem regra e sem cenário. O Codex com `--yolo` como auxiliar confere-se pelos despachos do `03`.
- RQ-13 — restrição de processo, sem regra e sem cenário. A própria wiki a cumpre.
- RQ-14 — restrição de processo, sem regra e sem cenário. A RQ-26 substitui em parte o "até o fim". O resto se cumpre pelo conjunto implementado e verde.
- RQ-15 — restrição sobre o diff, sem regra e sem cenário. Um teste "`app/` não mudou" não sobrevive ao merge. O gate confere o `git diff --stat` sem `app/`, e a Superfície Livewire do `02` já declara que nenhum arquivo de `app/` muda.
- RQ-16 — condicional à RQ-15, sem regra e sem cenário. Se nenhum código de aplicação mudar, ela fica vazia. Se algum mudar, o gate confere no diff a documentação de cada mudança.
- RQ-17 — não-funcional, citada pela R23. O CT-25 prova que os dois fluxos de CI rodam os conferidores do site. O resto se cumpre pelo próprio conjunto: o Índice aponta arquivos das suítes padrão (`tests/Kit`, `tests/Tenancy`) e o conferidor do mapa. Um cenário "os testes existem" não falsificaria nada.
- RQ-18 — restrição de processo, sem regra e sem cenário. Os subagentes em paralelo conferem-se pelos despachos do `03`.
- RQ-26 — restrição de processo, sem regra e sem cenário. O PR abre com o CI verde, e merge e release só acontecem com o OK do mantenedor. Confere-se no PR. *(alterado em 2026-11-20)* O ADV-34 pedia um cenário para o executor que acumula o papel de aprovador, e foi rejeitado como cenário. É controle de processo desta cláusula: a sessão e o Codex nunca fazem merge, push na `main` nem tag, e a sessão confere HEAD, tags e o estado do PR antes e depois de cada despacho do Codex. Confere-se no quality gate e no PR.

> **Aviso à sessão.** O `rastreabilidade.sh` acusa "RQ sem CT" para RQ-01, RQ-12, RQ-13, RQ-14, RQ-15, RQ-16, RQ-18 e RQ-26 se o `## Cobertura do Requisito` do `01` não as marcar com passo "—" e justificativa na Observação. Não li o `01`, então não posso conferir.

### Cláusulas abertas e coberturas parciais

A parte de cada cláusula que já está decidida tem cenário. O recorte espera a resposta. Nenhuma RQ está inteira aberta: a RQ-20 deixou de estar com a Q8 decidida, e a P-12 deu a ela o piso das classes de `app/` (R28).

- RQ-09 — cobertura parcial: CT-24, CT-39; recorte {quais fluxos, e se todos} aberto (Q6); recorte {o trecho da transição do `lifecycle` faz a transição} lacuna declarada (R35-M5, D25)
*(alterado em 2026-11-23 — Adendo 2: as cinco linhas abaixo tinham o recorte da Q11 aberto — a marca da peça condicional e o diagrama de modo em que ela entra. A RQ-27 a RQ-29, a P-15 e a P-16 o fecham; o que sobrou de aberto são as perguntas novas desta rodada.)*
- RQ-11 — cobertura parcial: CT-11 a CT-13, CT-17 a CT-19, CT-22, CT-32, CT-33, CT-37, CT-38, CT-43, CT-45, CT-56, CT-57; recorte {o modo de toda ligação de uso confere com o código, e o nó de fluxo com rótulo abreviado} lacuna declarada (R18-M5 e R18-M6)
- P-09 — cobertura completa: CT-17 a CT-19, CT-33, CT-37, CT-38, CT-43. A disponibilidade por modo conta como diferença de estrutura (RQ-28, RQ-29): o diagrama que desenha a peça que só fica disponível num modo ganha um por modo (CT-33), e os dois que só diferem nela não são "igual duplicado" (CT-19, linha nova). *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* E a visualização que vale nos dois modos não se diz de um modo só: a raiz da árvore de registro diz o modo em que ela vale, "os dois modos" / "both modes" na compartilhada (CT-19, as três linhas da S-AD-01)
- RQ-04 — cobertura completa *(alterado em 2026-11-23 — Adendo 3: era parcial, com o recorte da Q27, que a RQ-30 fechou)*: CT-01, CT-05, CT-06, CT-28 a CT-30, CT-32, CT-33, CT-35, CT-36, CT-41, CT-44, CT-45, CT-56, CT-57, CT-B01
- RQ-05 — cobertura completa: CT-01, CT-05, CT-06, CT-29, CT-30, CT-32, CT-33, CT-35, CT-36, CT-41, CT-44, CT-45, CT-56, CT-57, CT-B01. Toda peça condicional de código próprio que o painel registra está no discriminante; a de código próprio que painel nenhum registra (o `AcessosPorPainel`) fica fora do texto da RQ-27, e foi perguntada (Q28) *(alterado em 2026-11-23 — Adendo 3: a Q28 virou a Q28, e a RQ-31 a põe na população — R43)*
- P-03 — cobertura parcial *(alterado em 2026-11-23 — Adendo 3: o recorte da Q27 fechou pela RQ-30)*: CT-06, CT-08 a CT-13, CT-24, CT-28, CT-31, CT-32, CT-33, CT-36, CT-39, CT-40, CT-44, CT-45, CT-56, CT-57, CT-B01; recorte {o trecho da transição do `lifecycle` faz a transição} lacuna declarada (R35-M5, D25)
- RQ-27 — cobertura *(alterado em 2026-11-23 — Adendo 3: os recortes da Q27, da Q28 e da Q29 fecharam pela RQ-30, pela RQ-31 e pela RQ-32)*: CT-32, CT-56, CT-57, CT-B01 (passo 4). Fora do texto dela e das três do Adendo 3, e perguntado: o componente flutuante que só aparece com uma chave, que painel nenhum registra e que não fica dentro de tela (Q30) *(alterado em 2026-11-23 — Adendo 4: respondido pela RQ-33 — entra na população, R43 —, e a RQ-27 fica sem recorte aberto)*
- RQ-28 — cobertura: CT-33, CT-37, CT-38, CT-19, CT-57. *(alterado em 2026-11-23 — Adendo 3: o `CommandRecordResource` abre nos dois modos com a configuração ligada, e por isso entra nos dois — RQ-30)*
- RQ-29 — cobertura: CT-33 (o `/admin` com diagramas separados, o `TenantResource` só nos de com), CT-37 (linha admin: sem tenant, sem o `TenantResource`) e CT-38 (linha admin: com tenant, com ele)
- P-15 — cobertura *(alterado em 2026-11-23 — Adendo 3: o recorte da Q27 fechou; "ligada", na configuração de pacote, é o valor que a condição do modelo dá como ligado — R43)*: CT-32, CT-56 (com a chave ligada, a peça fica disponível), CT-33 e CT-37 (a condicional só pela chave fica nos dois modos)
- P-16 — cobertura *(alterado em 2026-11-23 — Adendo 3: o recorte da Q27 fechou pela RQ-30, que a substitui em parte)*: CT-32 (a peça que só pede permissão e a que só esconde o item de menu ficam sem condição; a chave ligada de fábrica não marca) e CT-56
- RQ-30 — cobertura *(alterado em 2026-11-23 — Adendo 3)*: CT-32 e CT-56 (o `CommandRecordResource` guarda a condição `command-center.sources`, posta à prova ligando a fonte de banco), CT-57 e CT-B01 (a marca com o caminho e o valor de fábrica), CT-37 e CT-38 (entra nos dois modos)
- RQ-31 — cobertura *(alterado em 2026-11-23 — Adendo 3)*: CT-32 e CT-56 (o `AcessosPorPainel` guarda a condição das telas de organizações), CT-33 (só nos diagramas com tenant), CT-57 e CT-B01 (a marca). O limite mecânico: a peça é achada pelo `canAccess()` ou `canView()` próprio; a que só aparece dentro de tela condicional sem método próprio não é vista, e não existe no HEAD — lacuna declarada (R43)
- RQ-32 — cobertura *(alterado em 2026-11-23 — Adendo 3)*: CT-32 (plugin e middleware sem condição no modelo) e CT-57 (sem marca no desenho)
- RQ-33 — cobertura *(alterado em 2026-11-23 — Adendo 4)*: CT-32 e CT-56 (o chat e o checklist guardam a condição das chaves que decidem a renderização deles, posta à prova pelo predicado vivo — o chat, com as duas chaves ligadas juntas), CT-33 (os dois ficam nos dois modos, como as peças de chave), CT-57 e CT-B01 (a marca em todo diagrama que os desenha, inclusive os escritos à mão, com as duas chaves no chat)

*(alterado em 2026-11-20: ADV-32)* A ordem dos passos saiu do recorte da Q6. A P-03 vale para todo fluxo publicado, qualquer que seja a lista. Por isso a âncora de cada transição e a ordem no mesmo método são da R35, e só a ordem entre arquivos ou métodos diferentes ficou como lacuna declarada (R35-M4).

**Estacionado na Q11 (ADV-17 e ADV-31) — destravado no Adendo 2** *(alterado em 2026-11-23 — Adendo 2)*.
- **Por que estacionava.** A Q11 tinha três leituras: a peça condicional entra marcada e desenhada só onde está disponível; entra marcada e desenhada onde está registrada; ou entra desenhada sem marca. O mantenedor escolheu a primeira, a recomendação (RQ-27 a RQ-29); as duas outras ("Onde registra, com marca" e "Onde registra, sem marca") foram recusadas.
- **O que voltou, com os IDs reservados:**
  - a R30, só com a metade do modelo: o CT-32 (sem tenant) e o CT-56, novo (com tenant), que é o que o ADV-17 pedia — a detecção nos dois modos, cada chave ligada no valor oposto ao de fábrica e a linha da âncora que não resolve;
  - o CT-33, agora da R41, nova: a peça condicional só entra nos diagramas do modo em que fica disponível. O enunciado reservado juntava a marca; a marca foi para a R42, nova, com o CT-57;
  - o passo 4 do CT-B01 (no `05`) e o R24-M5: a marca à vista no desenho construído;
  - a linha admin do CT-37 (ADV2-29): deixa de tirar a peça condicional pelo nome.
- **A R37** (ADV2-30) deixa de ser vácua: o mapa passa a marcar.
- **O que não voltou.** Duas peças que a medição achou e que o Adendo não decide: o `CommandRecordResource` (Q27) e o `AcessosPorPainel` (Q28). E o plugin e o middleware, que o discriminante não vê (Q29). *(alterado em 2026-11-23 — Adendo 3: as três voltaram respondidas — Q27, Q28 e Q29, RQ-30 a RQ-32 —, e moram na R43. Fica de fora só o componente flutuante ligado a chave, perguntado na Q30)* *(alterado em 2026-11-23 — Adendo 4)* Voltou também, pela RQ-33: nada ficou de fora.

A RQ-19 tem cobertura completa desde a terceira rodada (CT-05, CT-28 e, na quarta, CT-35), com a Q16 fechada. A RQ-24 tem cobertura completa desde o ajuste da quarta rodada (CT-08 a CT-10, CT-40), com a Q19 decidida (D26).

Fechadas ou decididas nas rodadas de revisão, e com cenário:

| Pergunta | Cenários |
|---|---|
| Q7 | CT-05, CT-28 |
| Q8 | CT-29, CT-30, CT-41 |
| Q9 | CT-09, CT-10 |
| Q10 | CT-31 |
| Q12 | CT-34, CT-B04, CT-B05, CT-B06, CT-B07 |
| Q13 | CT-04 |
| Q14 | CT-03, CT-05 |
| Q15 | CT-15 |
| Q16 | CT-28 |
| Q17 | CT-28 |
| Q18 | CT-39 |
| Q19 | CT-10 |

## Costuras de Teste

| Grupo | Regras | Costura | Existente ou nova | Por quê esta camada | Confirmada |
|---|---|---|---|---|---|
| G1 — arquivos versionados do catálogo, do README, da referência de pacotes, da rule, da página do mapa e dos workflows (Kit) | R1, R2, R3, R4, R5, R7, R21 (CT-23), R23, R25, R26, R27, R33 | Pest feature HTTP | **nova**: `tests/Kit/MapaInterativoTest.php`. Moldes: a base congelada e a leitura de `docs/` de `tests/Kit/SiteDeDocumentacaoTest.php` (`[CT-24]`, `[CT-40]`, `[CT-42]`); a rule e a linha do índice de `tests/Kit/OnboardingGuiadoInstalacaoTest.php`; o inventário Mermaid de `blocosMermaidDaArvore()` | o `Então` afirma o conteúdo de arquivos versionados — catálogo, README, páginas, referência de pacotes, `composer.json`, `package.json`, rule, índice, `site/package.json` e workflows — contra a base congelada e contra a árvore. A aplicação fica de pé pelo arnês do kit, e por isso a linha do CT-28 que lê o registro dos painéis (ADV-04) cabe aqui | sessão, 2026-11-20 — convenção do projeto: guarda de documentação em `tests/Kit`, no molde do `SiteDeDocumentacaoTest` e do `GuardasDosDiagramasTest` |
| G2 — modelo e diagramas do mapa × aplicação de pé, sem tenant (Kit) | R6, R8, R9, R10, R11 (CT-11, CT-13), R12, R15, R16, R17, R18, R19, R20, R22, R28 (CT-29, CT-41), R29, R30 (CT-32), R34 (CT-37), R35, R36, R37, R41 (CT-33), R43 (CT-32, CT-33) *(alterado em 2026-11-23 — Adendo 2: R30 e R41, proposta desta derivação, que a sessão confirma. O CT-32, o CT-33 e o CT-37 avaliam o `canAccess()`/`canView()` vivo como super_admin, e por isso semeiam o `PapeisSeeder`)* *(alterado em 2026-11-23 — Adendo 3: R43, nas linhas novas do CT-32 e do CT-33)* | Pest feature HTTP | **nova**: `tests/Kit/MapaInterativoTest.php`. Moldes: o `[CT-25]` de `tests/Kit/SiteDeDocumentacaoTest.php`, que confronta o README com `Filament::getPanel()`; o `conferirDg10()` de `tests/Kit/GuardasDosDiagramasTest.php`, para o controle em cópia | o `Então` afirma o modelo e os arquivos de diagrama contra o que os painéis registram, contra o roteador da aplicação, contra os arquivos de código citados e as classes de `app/`, contra o CHANGELOG e contra a página. Roda em todo PR, e o job `site` não (T) | sessão, 2026-11-20 — convenção do projeto: guarda que confronta documentação com o painel vivo em `tests/Kit` (molde do `[CT-25]` do `SiteDeDocumentacaoTest`); sessão, 2026-11-23 — a R30 e a R41 nesta costura, confirmadas; a R43 mora no CT-32 e no CT-33, já desta costura *(alterado em 2026-11-23 — Adendo 3)* |
| G3 — registro dos painéis com tenant (Tenancy) | R6 (CT-44), R11 (CT-12), R28 (CT-30), R30 (CT-56), R34 (CT-38), R43 (CT-56) *(alterado em 2026-11-23 — Adendo 3: R43, nas linhas novas do CT-56)* *(alterado em 2026-11-23 — Adendo 2: R30, proposta desta derivação, que a sessão confirma. O modo com tenant é decidido no boot, e a metade "com" da tabela de decisão só existe aqui; o CT-56 e o CT-38 avaliam o discriminante vivo com uma organização aberta no `/app`)* | Pest feature HTTP | **nova**: `tests/Tenancy/MapaInterativoTenancyTest.php`. Molde: `tests/Tenancy/DiagramasDaArquiteturaTenancyTest.php` | o modo com tenant só existe com o `Tests\TenancyTestCase`, que fixa `kit.tenancy.enabled` antes do boot | sessão, 2026-11-20 — convenção do projeto: o modo com tenant só existe na suíte `Tenancy` (molde do `DiagramasDaArquiteturaTenancyTest`); sessão, 2026-11-23 — a R30 nesta costura, confirmada; a R43 mora no CT-56, já desta costura *(alterado em 2026-11-23 — Adendo 3)* |
| G4 — site construído (conferidor do mapa, Node + Playwright) | R6 (CT-B01), R12 (CT-B02), R13, R14, R21 (CT-B01), R23 (CT-B06), R24, R31, R32, R40 (CT-B08 *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01: e os controles sintéticos da condição que o conferidor não pode ler, decididos pela função que lê o modelo real; proposta desta derivação, que a sessão confirma)*), R44 (CT-B01, passo 4) *(alterado em 2026-11-23 — Adendo 3: R44, o card à vista; o CT-B01 já é desta costura)* *(R40 em 2026-11-21: step 9, achado A11 — o conferidor importa o Playwright e lê o `axe-core` de `site/node_modules` antes de ler os diagramas, e por isso só roda no job `site`)* | browser | **nova** *(alterado em 2026-11-20: era "existente — `site/verifica-acessibilidade.mjs`")*: `site/verifica-mapa.mjs`, sobre o `astro preview`. Os dois workflows o rodam logo depois do conferidor de acessibilidade, com a mesma prévia de pé; no `pages.yml`, antes do envio do artefato. Molde: `site/verifica-acessibilidade.mjs`, inclusive os controles sintéticos | o HTML do mapa só existe depois do build (o `site/dist` é ignorado pelo git), o visualizador desenha no navegador, e acessibilidade e idioma da interface só o navegador mede. O catálogo en do archify só existe depois do build, que baixa o archify, e por isso o CT-B07 também mora aqui. O `pest-plugin-browser` não serve site estático (`site/verifica-acessibilidade.mjs:'pest-plugin-browser':6`). O arquivo é próprio porque o conferidor de acessibilidade já tem `[CT-B01]` e `[CT-B02]` de outra wiki. O `ids-ct.sh` acusa estes CT-B como sem teste, por desenho: ele só lê `it()` e `test()` | sessão, 2026-11-20 — convenção do projeto: o site construído é conferido por Node + Playwright no CI (molde do `site/verifica-acessibilidade.mjs`); arquivo próprio pela D17 do `01` |
| G5 — o gerador e as ferramentas de autor do mapa, rodados pelo Pest como processo filho do `node` (Kit) *(grupo novo em 2026-11-21: step 9)* | R9 (CT-52), R38, R39, R40 (CT-49, CT-50), R42 (CT-57), R43 (CT-57), R44 (CT-57) *(alterado em 2026-11-23 — Adendo 3: R43 e R44, nas linhas novas do CT-57)*, R12 (CT-14, as duas linhas da árvore compartilhada, na montagem pelo gerador; o CT-14 segue no G2) *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)*: proposta desta derivação, que a sessão confirma — só a forma de leitura vê a árvore que o gerador monta, e o construtor da guarda tem o mesmo defeito *(alterado em 2026-11-23 — step 9 do Adendo, confirmada pela sessão)* *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)*: e as linhas do mapa compartilhado `infra-convites` com a ligação de um modo só e a de controle, na montagem pelo gerador — proposta desta derivação, que a sessão confirma, pelo mesmo motivo; a linha da ponta registrada num modo só fica só no G2, porque o pedido da forma de leitura não muda peça, R18 (CT-19, as três linhas da raiz da árvore de registro, na montagem pelo gerador; o CT-19 segue no G2) *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)*: proposta desta derivação, que a sessão confirma — o rótulo secundário da raiz só existe na entrada que o gerador monta, e o construtor da guarda não o monta *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: confirmada pela sessão; o lugar e a redação são a D45 do `01`)* *(alterado em 2026-11-23 — Adendo 2: R42, proposta desta derivação, que a sessão confirma. A marca do diagrama construído só existe na entrada que o gerador monta do modelo; forma de leitura)* | Pest feature HTTP | **nova**: nenhum teste do kit roda o Node. Moldes: o `Process` do Laravel, que `tests/Kit/MapaInterativoPublicacaoTest.php` já usa para o `git`, e o controle de processo do conferidor (`site/verifica-mapa.mjs:conferirControleDeProcesso:1448`). Duas formas. **Leitura**: um `node` que importa `site/mapa/gerar.mjs` e devolve a entrada que o gerador entrega ao archify, diagrama a diagrama — a função exportada `entradaDoArchify`, a mesma que o posicionador usa —, sem baixar o archify nem renderizar; os controles adulteram em memória o diagrama passado ou o modelo que `carregarModelo()` devolve. **Efeito**: um clone descartável do repositório no commit do teste (`git clone --shared`, com `site/mapa` e a raiz), numa pasta temporária, com o download do archify bloqueado (`GIT_ALLOW_PROTOCOL=file`, que deixa o `git show` local e recusa o `fetch` por https); as ferramentas rodam nele, e a árvore do kit nunca é tocada | o `Então` afirma o que só existe com o Node rodando: o traço, a caixa e a linha da prova que o gerador entrega ao archify, e o código de saída, a mensagem, a pasta e os arquivos depois de uma ferramenta de autor. Nada disso está nos arquivos versionados. O clone existe porque, sob o defeito, o posicionador regrava o arquivo do diagrama e o gerador apaga a pasta; e o gerador não tem como ler outra pasta de diagramas. Roda em todo PR, como a suíte do kit (T). O `Dado` afirma o `node` lido (`node --version`); sem ele o caso reprova, e não pula (Q21) | sessão, 2026-11-21 (Q21) — confirmada; os jobs do CI que rodam a suíte do kit ganham `actions/setup-node`, o que é implementação; sessão, 2026-11-23 — a R42 nesta costura, confirmada; a R43 e a R44 moram no CT-57, já desta costura *(alterado em 2026-11-23 — Adendo 3)* |

## Fronteira com o Plano

| Item do plano | Recusado como oráculo porque | Destino |
|---|---|---|
| nomes e formato de `site/mapa/pecas.json`, `ligacoes.json` e `diagramas/{slug}.json` ("a pasta é o manifesto") | escolha de implementação | detalhe: os cenários falam de "modelo de peças", "modelo de ligações" e "arquivos de diagrama". O oráculo vem da RQ-24 e de P-03 a P-11 |
| `site/mapa/archify.mjs` baixa o archify no commit fixo e o confere | o `00` não fixa versão nem integridade do download | sem CT. A integridade do download fica para a dimensão de segurança do quality gate |
| `site/mapa/interface.json` *(alterado em 2026-11-21 — step 11, QA-03: o arquivo era `pt-BR.json` no plano; virou `interface.json` no passo 1, porque guarda as sobrescritas dos tipos de peça nos dois idiomas)*: as chaves que faltam no catálogo pt do archify e as sobrescritas dos rótulos de tipo de peça | mecanismo do idioma | o oráculo é a P-11 (a interface pt, CT-B04; o catálogo pt sem o texto en, CT-B07) e a P-02 (o texto do kit, CT-16 e CT-B01) |
| `site/mapa/gerar.mjs` grava em `site/dist/mapa/{pt,en}/{slug}.html` depois do `astro build`; `"build": "astro build && node mapa/gerar.mjs"` | mecanismo. Quem dá o oráculo é a P-07: o mapa nasce no build do commit que publica | o CT-25 afirma que o build dos dois workflows gera o mapa e que nenhum HTML do mapa é versionado. O caminho do HTML é detalhe dos CT-B |
| `site/mapa/posicionar.mjs`, script de desenvolvimento que nunca roda no build | o `00` não fala de posição | sem CT para a posição (ver Cogitado e cortado). *(alterado em 2026-11-21 — step 9, achados A6 e A11)* O argumento e o arquivo de entrada do posicionador têm CT pela P-14: CT-49 e CT-50 (R40), no clone descartável da G5. O nome do script e a função `entradaDoArchify` são detalhe do cenário, não oráculo |
| `node mapa/gerar.mjs --saida <pasta> [slug …]`, a forma do gerador para quem escreve diagramas, e a guarda do fim do arquivo que só roda o gerador quando ele é chamado direto *(linha nova em 2026-11-21 — step 9, achados A2, A4 e A11)* | o mecanismo é do plano; o comportamento — não apagar o que não gerou, não terminar com 0 sem gerar, recusar e nomear o culpado antes de qualquer efeito — vem da P-14 | CT-48 a CT-51 (R39, R40). O docblock do gerador ("sem um apagar o do outro") não é oráculo: a P-14 é |
| página em `docs/{pt,en}/referencia/mapa-interativo.md`, na seção Referência, em Markdown sem JS | superfície. O `00` pede "um lugar escolhido" no site | detalhe: CT-15 a CT-21 e CT-34 leem a página nesse caminho. A seção não é oráculo |
| o catálogo é a página Roteiro de features (`docs/{pt,en}/operacao/roteiro-de-features.md`) | o glossário também o determina (Catálogo de features, decidido nesta feature) | **aceito como oráculo**, pelo glossário e pela RQ-22 |
| rota `{base}/mapa/{pt,en}/{slug}.html` | superfície | detalhe dos CT-B |
| na Superfície de UI: "rota entre nós, lente, tema, exportação" | são recursos do archify, e testá-los é testar o archify. A RQ-07 pede interação; a P-04 pede a prova à vista; a P-11 pede o tema na acessibilidade | "busca de nó" e o passaporte com as provas da peça viram oráculo no CT-B03; os cards de prova, no CT-B02; e "tema", no CT-B05 *(alterado em 2026-11-20: fatos de plataforma — o passaporte mostra as provas da peça e só as contagens das ligações)*. O resto vai para Cogitado e cortado |
| `site/verifica-acessibilidade.mjs` alterado | o mapa não entra nele *(alterado em 2026-11-20)*: os CT-B desta wiki moram em `site/verifica-mapa.mjs`, por decisão da sessão nesta rodada, porque aquele arquivo já carrega `[CT-B01]` e `[CT-B02]` de outra wiki | detalhe. O CT-25 confere a ordem dos dois conferidores no workflow |
| a P-11 cita como origem o `01` (D2, D8, D12) e o `02` (ADR-06) | o oráculo vem da P-11, vigente no `00`, e não desses documentos | **aceito como oráculo pela P-11** (R31, R32, R33) |
| os dois defeitos tolerados: `nested-interactive` no `<svg>` raiz do diagrama e `color-contrast` em `.node-outline-heading > small` | a P-11 diz "dois defeitos do próprio archify, que a conferência tolera nomeados pela regra e pelo elemento", sem nomeá-los. Os nomes vieram da sessão, medidos no archify do commit fixado | aceitos como fato da plataforma que preenche a P-11. Se o archify mudar de commit, muda a tabela de controles do CT-B06, não a regra |
| `site/sidebar.json`, `site/redirects.json` e os stubs `site/public/{pt,en}/referencia/mapa-interativo.html` | superfície que guardas herdadas já vigiam: `[CT-20]`, `[CT-23]`, `[CT-36]` e `[CT-38]` de `tests/Kit/SiteDeDocumentacaoTest.php` | sem CT novo: se faltar algo, os herdados ficam vermelhos |
| link da página para os HTML do mapa | **decidido (Q15)** *(alterado em 2026-11-20: era achado)*. A página usa `<a href>` relativo cru dentro do Markdown, e não link Markdown. O `[CT-22]` (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-22]':613`) e o `[CT-45]` (`tests/Kit/SiteDeDocumentacaoTest.php:'[CT-45]':1753`) herdados só leem link Markdown e não mudam. O `site/verifica-links.mjs` confere o alvo no `dist/` | o CT-15 lê os `<a href>` e confere cada um contra os arquivos de diagrama |
| edição de `docs/{pt,en}/referencia/index.md`, `arquitetura-em-diagramas.md`, `operacao/index.md`, `depois-de-criar-resources.md`, `desenvolvendo-o-kit.md` e `roteiro-de-features.md` | superfície. Três desses arquivos carregam blocos Mermaid da base: DG-01, DG-02, DG-03 e DG-13 na página de diagramas, DG-11 no catálogo e DG-19 em "Desenvolvendo o kit" | P-01: o CT-27 confere que todo bloco da base continua onde estava |
| `tests/Browser/RoteiroDoKitTest.php`, só o docblock | documentação de teste | sem CT. Os números que ele cita (F-03, F-13, F-45, F-51, F-52) ficam protegidos pelo CT-03 |
| `.ai/rules/catalogo-de-features.md` e a linha em `.ai/rules/index.md` | o `00` pede "uma rule"; o nome do arquivo é detalhe | o CT-07 acha a rule pela linha do índice |
| `CHANGELOG.md` | registro da entrega. Asserção sobre o topo do CHANGELOG expira (`.ai/rules/testes.md`) | sem CT sobre o registro da entrega; o gate confere. O CT-31 lê o CHANGELOG só como fonte das versões, das datas e do texto de cada seção |
| "nenhuma rota do app muda", "nenhum arquivo de `app/` muda" | é a RQ-15 aplicada pelo plano: uma restrição sobre o diff | sem CT (ver RQ-15) |
| archify 3.2.0, commit `4478129…` | versão (entrada 6 do despacho) | os identificadores dos dez tipos, tirados do `schemas/README.md` do archify nesse commit, entram no CT-20 como fato da plataforma. O número dez e o comparativo vêm do `00` (RQ-25) |
| *(alterado em 2026-11-23 — Adendo 3)* a marca no rótulo secundário da caixa e no item do agrupamento; a redação "só com tenant ativo" / "tenancy on only", "KIT_HUB=false de fábrica" / "KIT_HUB=false by default", a configuração de pacote pelo caminho (`command-center.sources`); o card "Condições de disponibilidade" / "Availability conditions", com a prova de cada condição | decisão de desenho da sessão, em resposta à Q?3 (2026-11-23): o lugar e a redação são escolha de implementação. O conteúdo da marca vem da RQ-27 e da RQ-30, e o "à vista no carregamento", da RQ-28; a prova da condição no card vem da P-03 — o que o mapa afirma tem a âncora no código | o CT-57 e o passo 4 do CT-B01 conferem o lugar e a redação decididos, marcados `@premissa`; a R44 confere o card. Se a redação mudar, mudam os exemplos, não o oráculo |
| *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* o modo do diagrama no rótulo secundário da raiz da árvore de registro, e a redação: "sem tenant" / "tenancy off", "com tenant ativo" / "tenancy on" — o termo da grade da página — e "os dois modos" / "both modes" na árvore que vale nos dois | decisão de desenho da sessão, na reconciliação do ciclo dos Adendos (S-AD-01): o lugar e a redação são escolha de implementação. O conteúdo — a visualização igual nos dois modos vale para os dois, e não se diz de um modo só — vem da P-09; o termo en, da P-02 | as três linhas da S-AD-01 no CT-19 conferem o lugar e a redação decididos, pela tabela do Setup Global. Se a redação mudar, mudam os exemplos e a tabela, não o oráculo |

### Perguntas da derivação: estado depois da rodada de revisão

As de raia requisito estão no `00`, em `## Perguntas ao Solicitante`, com o estado de cada uma. As de raia desenho foram decididas pela sessão.

| ID | Raia | Afeta | Estado | No `04` |
|---|---|---|---|---|
| Q6 | requisito | RQ-09 | aberta | recorte bloqueado: quais fluxos, e se todos. O passo que nomeia peça (R22), a âncora de cada transição e a ordem no mesmo método (R35) não dependem dela (ADV-32) |
| Q7 | requisito | RQ-19, RQ-04, RQ-05 | fechada pelo `00` | R5 (todas as páginas) e R27 (pacotes instalados) |
| Q8 | desenho (reclassificada) | RQ-20 | decidida pela sessão | R28 (CT-29, CT-30) |
| Q9 | requisito | RQ-24, P-04, P-05, P-07 | fechada pelo `00` | R9 (prova fora do commit reprova) e R10 (registro pelo plugin que traz a peça) |
| Q10 | desenho | P-03, P-06 | decidida pela sessão | R29 (CT-31) |
| Q11 | requisito | RQ-04, RQ-05, RQ-11, P-03, P-09 | respondida no Adendo 2 (a recomendação) *(alterado em 2026-11-23 — Adendo 2: era "aberta")* | destravada: a R30 (CT-32, CT-56), a R41 (CT-33), a R42 (CT-57), a linha admin do CT-37, as linhas novas do CT-14, do CT-19 e do CT-38, o passo 4 do CT-B01 e o R24-M5. Antes: recorte estacionado (ADV-17, ADV-31, ADV2-29), e a parte que valia nas três leituras virou a R37 (ADV2-30) |
| Q12 | requisito | P-02, RQ-03, RQ-07 | fechada pela P-11 | R31, R32 e R33 |
| Q13 | requisito | P-08, RQ-23 | fechada pelo `00` | R4 (linha F-03d) |
| Q14 | desenho | P-08, RQ-19 | decidida (a recomendação) | R3 e R5 (`@premissa`) |
| Q15 | desenho | RQ-03, P-02 | decidida antes da derivação | R15 (`<a href>` relativo cru) |
| Q16 | requisito | RQ-19, RQ-04, P-03 | fechada pelo `00` | R27: o CT-28 parte dos manifestos, e a linha "instalado e não listado" reprova |
| Q17 | desenho | RQ-19, P-03 | decidida (a recomendação) | R27 (`@premissa` do CT-28) |
| Q18 | desenho | P-03, RQ-09 | decidida pela sessão (D25: não) | R35-M5 continua sem matador, com o motivo do D25; o CT-39 não muda |
| Q19 | desenho | RQ-24, P-03, P-07 | decidida pela sessão (D26) | R10: a quinta forma (CT-10; R10-M8 e M9) |
| Q27 *(alterado em 2026-11-23 — Adendo 3: era a Q?1)* | requisito | RQ-27, P-16 | respondida no Adendo 3 (RQ-30) | R43: o `CommandRecordResource` na população, pela configuração de pacote (CT-32, CT-56, CT-57, CT-37, CT-38) |
| Q28 *(alterado em 2026-11-23 — Adendo 3: era a Q?2)* | requisito | RQ-27, RQ-28 | respondida no Adendo 3 (RQ-31) | R43: o `AcessosPorPainel` herda a condição das telas de organizações (CT-32, CT-33, CT-56, CT-57) |
| Q29 *(alterado em 2026-11-23 — Adendo 3: era a Q?4)* | requisito | RQ-27, P-16 | respondida no Adendo 3 (RQ-32) | R43: plugin e middleware sem condição nem marca (CT-32, CT-57) |
| Q30 *(alterado em 2026-11-23 — Adendo 4: era a Q?5)* | requisito | RQ-27, RQ-31, RQ-28, P-03 | respondida no Adendo 4 (RQ-33) | R43: o chat do assistente e o checklist do tutorial na população, pelo predicado de renderização (CT-32, CT-33, CT-56, CT-57, CT-B01) |

Nenhuma pergunta nova na terceira rodada. Ficam abertas só a Q6 e a Q11, que já estão no `00`. A Q18 e a Q19, de raia desenho, nasceram na quarta rodada e foram decididas pela sessão (D25 e D26). A P-12, que veio do ADV-33, é premissa vigente do `00`, e não pergunta. *(alterado em 2026-11-23 — Adendo 2: a Q11 foi respondida; as perguntas que a derivação do Adendo abriu estão em `### Perguntas do Adendo 2`, abaixo)*

Perguntas da quarta rodada, renumeradas e decididas pela sessão no `01`:

**Q18** · raia: desenho · afeta: P-03, RQ-09 · depende de: —
No diagrama `lifecycle`, a âncora de cada transição tem de nomear, no trecho, o estado de destino, como o código o chama? A decisão do ADV-11 pede só que a âncora resolva. Uma transição inventada, como Recusado → Pendente, passa se receber a âncora de um trecho que existe e não faz essa transição (R35-M5).
**decidida — D25:** não. Os estados do kit são calculados, e não gravados:
- o `Convite::situacao()` deriva o estado de `aceito_em`, `recusado_em` e `expira_em` (`app/Models/Convite.php:situacao:619`);
- o `User::rotuloDaSituacao()` deriva de `aprovacao_pendente` e `ativo` (`app/Models/User.php:rotuloDaSituacao:558`);
- a transição grava a coluna e nunca escreve o nome do estado (`app/Models/Convite.php:'aceito_em':690`), e "prazo vence" não tem linha que a faça.

Exigir o nome do destino reprovaria transição real. O R35-M5 continua sem matador, com esse motivo, e o CT-39 não muda.

**Q19** · raia: desenho · afeta: RQ-24, P-03, P-07 · depende de: —
A sessão mediu que parte das ligações tem a prova em `vendor/`, e que o modelo a troca pelo trecho do kit que liga ou configura a peça. Quando a origem é peça de pacote e esse trecho fica em `config/` ou num provider que não é de painel, qual forma da R10 o aceita? As formas decididas no ADV-07 e no ADV-08 aceitam o provider só nas ligações do painel, e qualquer outro arquivo reprova. Uma entrega correta pode ficar vermelha no CT-10.
**decidida — D26:** a quinta forma da R10, mais larga que a recomendação. Na ligação cuja origem é peça de pacote, a prova é o trecho do kit que liga a origem ao destino, num arquivo versionado fora de `vendor/`, em qualquer pasta:
- o trecho nomeia a peça de destino (classe, alias ou chave);
- o arquivo nomeia a peça de origem (classe, plugin ou a chave de config do pacote);
- `vendor/` e saída de comando (`artisan route:list`) nunca valem.

A sessão mediu as 288 ligações da varredura cuja origem não é classe do kit nem painel. Só `config/` e provider deixariam vermelhas as ligações de `routes/` e de `app/Support` (a distribuição está na R10).

### Perguntas do step 9 (decididas pela sessão em 2026-11-21; os números `Q?n` são provisórios, e a sessão os renumera ao gravar)

*(seção nova em 2026-11-21: step 9 — derivação dos achados da revisão do diff)* Cinco de raia desenho — como o cenário se prende ao código — e duas de raia requisito: a Q23, porque o rótulo da raiz é texto que o leitor vê, e a Q24, porque decide se um valor é aceito ou recusado, e por isso a recomendação dela segue o falha fechado. O recorte de cada uma está marcado no cenário, e o resto do cenário não espera a resposta.

❓ Q20 · raia: desenho · afeta: P-03, R9 (CT-53) · depende de: —
O "fora de comentário" da R9 tem regra para PHP, Blade, JS e stub. O CT-53 passa a conferir, na suíte do kit, 55 âncoras distintas em tipos sem regra: 30 no `CHANGELOG.md`, 22 em YAML de `.github/`, 2 no `composer.json` e 1 no `.env.example`. YAML e `.env` comentam com `#`, e Markdown com `<!-- -->`: a regra se estende a eles?
➡️ Recomendação: sim — `#` até o fim da linha em YAML e `.env`, `<!-- -->` em Markdown; JSON não tem comentário. Falha fechado, e medido no commit `168d1827`: nenhuma das 55 tem a primeira ocorrência numa linha de comentário, e a extensão não reprova dado de hoje. Até a decisão, a linha de controle do YAML no CT-53 vai marcada `@premissa`.
**decidida pela sessão (2026-11-21):** sim. "Fora de comentário" vale também para YAML e `.env` (`#` até o fim da linha) e para Markdown (`<!-- -->`); JSON não tem comentário. A linha do YAML no CT-53 deixa de ser `@premissa`.

❓ Q21 · raia: desenho · afeta: P-14, R38, R39, R40, R9 (CT-52) · depende de: —
A costura G5 roda o `node` na suíte do kit. Os jobs `qualidade` e `cobertura` do `ci.yml` não têm `actions/setup-node`: o runner `ubuntu-latest` traz um Node sem versão fixada, e o job `site` fixa o 24. O caso usa o Node do runner, ou os dois jobs ganham `actions/setup-node` com a versão do job `site`?
➡️ Recomendação: os dois jobs ganham `actions/setup-node` com a versão do job `site` — o gerador é o mesmo do build, e outro Node pode aceitar o que o build recusa. Sem `node`, o caso reprova, e não pula.
**decidida pela sessão (2026-11-21):** os jobs do CI que rodam a suíte do kit ganham `actions/setup-node` — é implementação —, e a costura G5 fica confirmada. Sem `node`, o caso reprova, e não pula.

❓ Q22 · raia: desenho · afeta: P-13, R38 (CT-46) · depende de: —
A seta que junta ligações de verbos diferentes: divide-se em uma seta por verbo, ou leva um traço próprio de "vários verbos", explicado na legenda "Como ler as setas"? O CT-46 aceita as duas formas e reprova só o traço de um verbo só.
➡️ Recomendação: uma seta por verbo — a legenda fica como está, e nenhum traço novo precisa de explicação; o custo são mais setas nos resumos (51 pares de pontas hoje).
**decidida pela sessão (2026-11-21):** uma seta por verbo. A seta junta só ligações do mesmo verbo; dois verbos no mesmo par de pontas viram duas setas, cada uma com o traço do seu verbo. O archify só tem os quatro traços, todos já de verbo; a sessão conferiu que duas conexões no mesmo par renderizam, e que a validação de layout pede reposicionar os mapas afetados, o que é implementação. O CT-46 passa a conferir isso, e as 51 setas de hoje são vermelho de implementação.

❓ Q23 · raia: requisito · afeta: R24 (CT-B01), R13 (CT-B02) · depende de: —
A raiz da árvore de registro é a peça painel (`painel:app`), e a R24 diz que, no diagrama construído, o rótulo da peça é o nome dela no modelo: `/app`. O gerador desenha "Painel /app" em pt e "Panel /app" em en, e os itens de prova da árvore começam por esse rótulo. Medido no commit `168d1827`: as 4 árvores, nos 2 idiomas, reprovam o oráculo da R24 — 8 raízes, e todo item de prova delas. Muda a implementação (a raiz leva o nome do modelo) ou a regra (a raiz da árvore leva "Painel {nome}" em pt e "Panel {nome}" em en, com o nome da peça painel no modelo)?
➡️ Recomendação: a regra, com o formato escrito no `05` — "Painel /app" lê melhor que "/app" sozinho, e o esperado continua vindo do modelo, e não do HTML.
**decidida pela sessão (2026-11-21):** a implementação. A raiz da árvore leva o nome da peça no modelo (`/app`), como toda peça (R24). As 8 raízes de hoje são vermelho de implementação, e o CT-B01 e o CT-B02 tiram o rótulo esperado da raiz do modelo, nunca do HTML.

❓ Q24 · raia: requisito · afeta: P-14, R40 (CT-49) · depende de: —
`--tentativas 0` é recusado, ou quer dizer "só a grade inicial, sem busca"? O CT-49 recusa o `--tentativas` sem número e o não numérico; o zero fica fora até a resposta.
➡️ Recomendação: recusado — falha fechado: zero tentativas não é busca, e gravar a grade inicial sem busca é o efeito que o achado A6 descreve.
**decidida pela sessão (2026-11-21):** recusado. O valor válido de `--tentativas` é um inteiro positivo; o CT-49 ganha a linha do zero.

❓ Q25 · raia: desenho · afeta: R23 (CT-B06) · depende de: —
O controle de caminho do CT-B06 confere, a partir de uma URL de módulo sintética numa pasta com espaço e acento, o caminho com que o conferidor se relançaria. Isso pede que essa derivação seja a mesma função que o controle de processo usa. Ou a sessão prefere a prova de ponta a ponta: o conferidor rodado de uma cópia dele numa pasta de `site/` com espaço e acento no nome, o que roda o conferidor duas vezes no job `site`? (Uma junção não serve: o Node resolve o link na URL do módulo, como o achado A4 mostra.)
➡️ Recomendação: o controle sobre a derivação, pela mesma função — custa milissegundos e roda em toda execução; a prova de ponta a ponta dobra o tempo do job.
**decidida pela sessão (2026-11-21):** o controle sobre a derivação do caminho, pela mesma função; sem prova de ponta a ponta.

❓ Q26 · raia: desenho · afeta: R28 (CT-30), R6 (CT-44) · depende de: —
O docblock de `tests/Tenancy/MapaInterativoTenancyTest.php` promete cópias de controle nos CT-12, CT-30 e CT-44, e os datasets não as têm (achado A15). O CT-55 dá os controles à guarda do registro (R11). O CT-30 e o CT-44 ganham uma linha de controle cada, ou o docblock muda?
➡️ Recomendação: ganham — as guardas da suíte Tenancy são funções próprias (o achado A15 é exatamente uma delas divergindo da da Kit), e o controle da Kit (CT-29, CT-36) não as exercita.
**decidida pela sessão (2026-11-21):** o docblock da suíte `Tenancy` muda para dizer o que os datasets têm. O CT-30 e o CT-44 não ganham controle: o CT-55 e a linha nova do CT-38 cobrem o achado A15.

### Perguntas do Adendo 2 (os números `Q?n` são provisórios, e a sessão os renumera ao gravar)

*(alterado em 2026-11-23 — Adendo 2: seção nova)* Três de raia requisito, que vão ao `00`, e uma de raia desenho, que vai ao `01`. Cada uma bloqueia só o recorte que depende dela; o resto do cenário vale já. *(alterado em 2026-11-23 — Adendo 3: as três de requisito foram respondidas pela recomendação — Q27, Q28 e Q29 no `00` —, e a de desenho, decidida pela sessão. A derivação do Adendo 3 abriu uma, a Q30, de raia requisito)* *(alterado em 2026-11-23 — Adendo 4)* A Q30 foi respondida no Adendo 4.

❓ Q27 · raia: requisito · afeta: RQ-27, RQ-28, RQ-04, P-15, P-16, P-03 · depende de: —
O `CommandRecordResource`, que o `/infra` registra nos dois modos, fica indisponível de fábrica: o `canAccess()` dele exige a fonte de banco do command-center (`vendor/ssbityukov/filament-command-center/src/Filament/Resources/CommandRecordResource.php:canAccess:46`), que está comentada em `command-center.sources` (`config/command-center.php:'sources':69`). Medido no HEAD `c642c23c`: com `DatabaseSource` na lista, ele abre nos dois modos, como super_admin; nenhuma chave `kit.*` o liga. É configuração do kit, mas não é interruptor nem variável `KIT_*`. Ela é "a chave de configuração do kit" da P-16, e a peça leva a marca da RQ-27?
➡️ Recomendação: sim — falha fechado: sem a marca, o mapa mostra como disponível uma tela que de fábrica não abre, e a RQ-28 proíbe isso. A marca diz a chave de config e o valor de fábrica (`command-center.sources` sem `DatabaseSource`), e a peça entra nos dois modos, porque abre neles com a fonte ligada (P-15). Até a resposta, o CT-32 e o CT-56 a tiram pelo nome; o CT-37 e o CT-38 a desenham nos dois modos do `/infra`, que é o que as duas leituras fazem.
**respondida no Adendo 3 — virou a Q27 (era a Q?1), e a RQ-30** *(alterado em 2026-11-23 — Adendo 3)*: sim, pela recomendação. A configuração de pacote conta como chave: o `CommandRecordResource` leva a marca com a configuração e o valor de fábrica, e entra nos diagramas como as peças de chave do kit — nos dois modos. Sai a exclusão pelo nome; mora na R43.

❓ Q28 · raia: requisito · afeta: RQ-27, RQ-28, P-03 · depende de: —
O `AcessosPorPainel`, widget que painel nenhum registra (a lista do `TenantResource` o usa), está no modelo como peça do kit inteiro e só abre com tenant: o `canView()` dele chama o do `TenantResource` (`app/Filament/Admin/Resources/Tenants/Widgets/AcessosPorPainel.php:canView:39`). Hoje o `kit-multi-tenancy-opt-in`, compartilhado, e o `kit-resumo-sem` o desenham. A RQ-27 fala da peça "que o painel registra". A peça que só existe por meio de uma peça condicional segue a condição dela — marca e diagrama de modo?
➡️ Recomendação: sim — falha fechado: entra só nos diagramas do modo em que abre, com a marca. Medido no HEAD `c642c23c`: entre as peças do modelo fora do registro de painel que têm `canAccess()` ou `canView()` estático (11), é a única indisponível num modo. Até a resposta, fica fora do discriminante, que é o registro do painel, e nenhum CT a confere.
**respondida no Adendo 3 — virou a Q28 (era a Q?2), e a RQ-31** *(alterado em 2026-11-23 — Adendo 3)*: sim, herda. A peça que só aparece dentro de uma tela condicional leva a marca dela e só entra nos diagramas do modo em que a tela abre. Mora na R43; o `AcessosPorPainel` entra no CT-32, no CT-33, no CT-56 e no CT-57.

❓ Q?3 · raia: desenho · afeta: RQ-27, P-02, R42 (CT-57), R24 (CT-B01, passo 4) · depende de: —
Onde e com que texto a marca aparece no desenho? O requisito fixa o conteúdo — o modo, ou a chave e o valor de fábrica — e, pela RQ-28, que ela esteja à vista no carregamento: a peça sem marca à vista é mostrada como sempre disponível. O lugar e a redação não são do requisito.
➡️ Recomendação: no rótulo secundário do nó, e não no `tag`, que o archify desenha como detalhe fino, com opacidade 0 no nível de leitura do carregamento (medido no commit `4adaf9a`, R6). Na árvore de registro, o rótulo secundário foi medido à vista no carregamento (a F-nn, R6); no mapa de arquitetura, não foi medido, e o passo 4 do CT-B01 o confere — se ele também esconder o rótulo secundário no carregamento, a decisão muda de lugar, e não de oráculo. Na peça que é membro de agrupamento, a linha dela no card "Quem está em …". O modo, pelo termo da coluna da grade da página do mesmo idioma ("com tenant ativo" e "tenancy on", na linha 30 das duas páginas do mapa); a chave, pela variável do `.env` e o valor de fábrica, como a pergunta do Adendo 2 a escreveu (`KIT_HUB=false`); a composta, as duas. Até a decisão, o CT-57 e o passo 4 do CT-B01 vão marcados `@premissa`.
**decidida pela sessão (2026-11-23)** *(alterado em 2026-11-23 — Adendo 3)*: a marca mora no rótulo secundário da caixa, à vista no carregamento, e no item do agrupamento que nomeia a peça. O modo, pelo termo da grade: "só com tenant ativo" em pt, "tenancy on only" em en. A chave, pela variável e pelo valor de fábrica: "KIT_HUB=false de fábrica" / "KIT_HUB=false by default"; a configuração de pacote, pelo caminho dela, `command-center.sources`. A peça de modo e chave leva as duas. E um card "Condições de disponibilidade" / "Availability conditions" lista cada peça condicional desenhada, com a prova da condição (R44). O CT-57 e o passo 4 do CT-B01 ficam `@premissa` nessa decisão. O número final é o que a sessão dá no `01`.

❓ Q29 · raia: requisito · afeta: RQ-27, P-16 · depende de: —
O plugin e o middleware que o painel registra sempre, e cujo efeito só acontece com uma chave — o `ExigeTutorialGuiadoLigado`, que responde 404 sem `KIT_ONBOARDING` (`app/Providers/Filament/AdminPanelProvider.php:ExigeTutorialGuiadoLigado:485`); o `FilamentCardsPlugin`, registrado sempre para as páginas hub, que só abrem com `KIT_HUB` (`app/Providers/Filament/AdminPanelProvider.php:FilamentCardsPlugin:367`) —, são peça condicional? O discriminante que a sessão fixou, o `canAccess()` e o `canView()`, não os vê: plugin e middleware não têm nenhum dos dois.
➡️ Recomendação: não. O plugin e o middleware estão disponíveis: rodam, e o middleware que responde 404 está fazendo o que a chave desligada pede. A disponibilidade deles é o registro, que a R11 já confere por modo, e o que a chave liga são as telas que eles trazem, que o discriminante mede (o `JornadaResource`, o `CondicaoResource`, o `PrimeirosPassos`). Medido nos três providers: fora o `/app`, que muda o registro com tenant, nenhum plugin nem middleware é registrado sob uma chave `kit.*`.
**respondida no Adendo 3 — virou a Q29 (era a Q?4), e a RQ-32** *(alterado em 2026-11-23 — Adendo 3)*: não. Plugin e middleware são desenhados sem marca, e a condição fica na tela que eles servem. Mora na R43: o CT-32 recusa a condição no modelo, e o CT-57, a marca no desenho.

❓ Q30 · raia: requisito · afeta: RQ-27, RQ-31, RQ-28, P-03 · depende de: — *(alterado em 2026-11-23 — Adendo 3: pergunta nova)*
O componente que painel nenhum registra, que não fica dentro de uma tela — aparece por cima de todas, por um gancho de renderização — e que só aparece com uma chave leva a marca? São dois no modelo; a condição de cada um foi lida no código do HEAD `c642c23c`, e o lugar no desenho, medido:
- o chat do assistente, `AssistenteChatWidget`, renderizado só quando `AgentesDeIa::chatNaTela()` (`app/Support/AgentesDeIa.php:chatNaTela:49`, chamado em `app/Providers/Concerns/ConfiguraFilamentGlobal.php:chatNaTela:188`): pede `KIT_AGENTES_IA` ligada **e** o painel na lista `KIT_AGENTES_IA_PAINEIS`, vazia de fábrica — duas chaves juntas;
- o checklist do tutorial, `ChecklistDoOnboarding`, que o kit põe no lugar do lançador do plugin (`app/Providers/KitServiceProvider.php:configureChecklistDoOnboarding:215`) e que o plugin só renderiza quando o lançador está ligado — `->launcher(fn () => self::visivel())` (`app/Support/TutorialGuiado.php:launcher:67`), e o `visivel()` é o `canAccess()` da página Primeiros passos (`app/Support/TutorialGuiado.php:visivel:52`), que pede `KIT_ONBOARDING`.

Hoje os dois são desenhados sem marca nos diagramas do kit inteiro, sem e com tenant (`kit-ia`, `kit-produtividade-e-ui-sem` e `-com`, `kit-resumo-sem` e `-com`; o chat também no `app-chat-de-ia` e no `ia-do-prompt-ao-ledger`, escritos à mão). A RQ-27 fala da peça que o painel registra; a RQ-31, da que aparece dentro de uma tela condicional; a RQ-32, de plugin e middleware. Nenhuma alcança estes dois.
➡️ Recomendação: sim — falha fechado: sem a marca, o mapa mostra como sempre presente um componente que de fábrica não aparece. A marca diz a chave e o valor de fábrica — as duas, no chat —, e os dois entram nos dois modos, como as peças de chave (P-15). Sem `canAccess()` nem `canView()`, o discriminante vivo não os vê: a condição no modelo seria conferida pela âncora, e não ao vivo. Até a resposta, ficam fora da população, e o lugar deles nos diagramas não muda, porque as duas leituras os desenham nos dois modos.
**respondida no Adendo 4 — virou a Q30 (era a Q?5), e a RQ-33** *(alterado em 2026-11-23 — Adendo 4)*: sim, pela recomendação. Os dois levam a marca da chave e do valor de fábrica — o chat, as duas chaves —, como as telas condicionais, e nunca aparecem como sempre disponíveis. Moram na R43, avaliados pelo predicado que decide a renderização de cada um (Setup Global).

### Pergunta do step 9 do Adendo — Q31 (era a Q?6), decidida pela sessão

*(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* Seção nova. Uma de raia desenho, que vai ao `01`. Ela bloqueia só a célula que depende dela; as linhas do A6 no CT-57 e no CT-45 valem já.

❓ Q31 (era a Q?6) · raia: desenho · afeta: RQ-27, R44 (CT-57), R37 (CT-45), D43 · depende de: —
No diagrama escrito à mão, o nó de rótulo homônimo sem prova não designa peça (D43), e nenhuma guarda pede marca nem linha de card para ele. Se o card "Condições de disponibilidade" do diagrama tiver, mesmo assim, uma linha com esse rótulo — "ConviteResource: só com tenant ativo — …" num `admin-convite` com o participante "ConviteResource" sem prova —, a guarda da marca (CT-57) e a R37 (CT-45) a reprovam, ou a ignoram? A D43 não diz: ela decide o que se pede, e não o que se aceita a mais.
➡️ Recomendação: reprovam — falha fechado: a linha afirma a condição de uma peça que o diagrama não designa, e a R44 não admite linha de peça que o diagrama não desenha; a R37 não tem peça contra a qual conferir a marca. Medido no HEAD `1dd48acd`: no dado publicado, o único nó de rótulo homônimo (o `pagina` do `configuracoes-config`) tem prova e não tem condição, e nenhum card escrito à mão tem linha assim. Até a decisão, essa célula fica sem linha.

**decidida pela sessão (2026-11-23) — virou a D44 do `01`** *(alterado em 2026-11-23 — step 9 do Adendo, Q31)*: reprovam, pela recomendação. A célula ganhou a linha no CT-57 e no CT-45.

### Colisões do Adendo 2 — proposta (a sessão decide e grava)

*(alterado em 2026-11-23 — Adendo 2: seção nova)* Os cenários desta rodada estão escritos sob as duas propostas. Se a sessão decidir outra coisa, mudam o CT-14, o CT-33, o CT-37 e o CT-38.
**decididas pela sessão (2026-11-23)** *(alterado em 2026-11-23 — Adendo 3)*: as duas propostas foram aceitas como estão. A completude da P-05 vale para o modelo — a peça segue registrada nos dois modos, e o `modos` da ligação segue o do código —; a RQ-28 vale para todo diagrama, inclusive a árvore de registro; e a ligação só conta como desenhada onde as duas pontas entram. A sessão confirmou também as leituras que esta derivação tomou por determinadas: a RQ-29 como "ao menos um diagrama só sem e um só com tenant"; o membro de agrupamento leva a marca; o `ProjetoResource` leva as duas marcas; "à vista no carregamento" vem da RQ-28.

**Colisão a — a árvore de registro.** A P-05 manda conferir "o que o painel registra (…) contra o painel vivo, nos dois modos"; a RQ-28 manda a peça condicional entrar "só no diagrama do modo em que está disponível". O `TenantResource` é registrado sem tenant e não abre.
- **Proposta.** A completude da P-05 é do **modelo**, não do desenho. O modelo continua com a ligação de registro em todo modo em que o painel registra (o CT-11 e o CT-12 não mudam: a linha admin do CT-11 exige o `TenantResource` "nos dois lados" sem tenancy, e o R11-M5 continua matando a guarda que compara a disponibilidade no lugar do registro). A **árvore** é um diagrama, e a RQ-28 vale para ela: a árvore do modo sem tenant desenha o registro do painel nesse modo menos a peça que fica indisponível nele mesmo com a chave ligada; a árvore do modo com tenant desenha o resto, com a marca. A guarda que confere a árvore contra o registro vivo (o CT-37 e o CT-38) subtrai do registro o que o **discriminante vivo** dá como indisponível no modo e que nenhuma chave `kit.*` desligada torna disponível — tirado do código, e não da condição do modelo, para que o modelo que marca peça demais não esvazie a árvore e passe.
- **Recusado.** Desenhar a peça na árvore do modo em que está indisponível, com uma marca: é a opção "Onde registra, com marca", que o mantenedor recusou no Adendo 2.
- **Consequência — o que os CT forçam, e o que só se recomenda.** Os CT forçam, em cada modo, que os mapas de arquitetura e a árvore do painel que valem nele desenhem **juntos** o registro disponível no modo (R34: CT-37, CT-38); que nenhum diagrama compartilhado desenhe peça que só abre num modo, e que o `/admin` tenha diagramas que valem só sem e só com tenant (R41: CT-33). Uma árvore do `/admin` compartilhada sem o `TenantResource`, com um mapa só com tenant que o desenhe e outro só sem tenant, passa. A forma recomendada é a árvore por modo no `/admin`, como o `/app` já tem, porque a árvore é o diagrama que diz "o que o painel registra"; mas é recomendação, e não oráculo. O `/infra` fica com a compartilhada, porque nenhuma peça dele é condicional pelo modo.

**Colisão b — a ligação cuja ponta some num modo.** "O painel admin registra `TenantResource`" vale nos dois modos no código, e a ponta só é desenhada com tenant.
- **Proposta.** O `modos` da ligação continua o do código — o fato de o código fazer a ligação nesse modo —, e a disponibilidade fica na **peça** (a condição da R30). A ligação conta como desenhada num diagrama quando as duas pontas entram nele pela regra do Setup Global (registrada **e** disponível no modo do diagrama). Assim a ligação do `TenantResource` só é desenhada nos diagramas com tenant; a P-10 ("toda ligação do modelo aparece desenhada em pelo menos uma visualização") se cumpre neles; e a R18 ("toda ligação desenhada vale no modo do diagrama") continua verdadeira. As guardas de "toda ligação desenhada" — o CT-14 e o CT-B02 — não mudam de oráculo: mudam a definição de ponta desenhada, que agora é a mesma do gerador, e o CT-14 ganha a linha que a controla.
- **Recusado.** Estreitar o `modos` da ligação pela disponibilidade (`["com"]`): quebra a R11 (o painel registra a peça sem tenant, e o modelo deixaria de dar o registro a esse modo) e a P-03 (o modelo deixaria de dizer o que o código faz).
- **Medido no HEAD `c642c23c`.** 19 ligações tocam peça que só abre com tenant, todas com `modos` sem e com, e todas desenháveis no modo com; das 413 ligações do modelo, nenhuma fica sem um modo em que as duas pontas estejam disponíveis. A P-10 não quebra sob a proposta.

## Perguntas para o 00-requisito.md

*(alterado em 2026-11-20: virou apontador)* As perguntas de raia requisito estão no `00`, em `## Perguntas ao Solicitante` (Q6, Q7, Q9, Q11, Q12, Q13 e Q16), com o estado de cada uma. As de raia desenho (Q8, Q10, Q14, Q15, Q17, Q18 e Q19) foram decididas pela sessão; a Q18 e a Q19, no `01` (D25 e D26).
*(alterado em 2026-11-23 — Adendo 2)* A Q11 está respondida no Adendo 2. As de raia requisito desta rodada — Q27, Q28 e Q29 — a sessão leva ao `00`; a Q?3, de raia desenho, ao `01`. Esta derivação não edita o `00`.
*(alterado em 2026-11-23 — Adendo 3)* As três de raia requisito estão no `00` como Q27, Q28 e Q29, respondidas no Adendo 3; a Q?3 foi decidida pela sessão. A pergunta nova, de raia requisito, a sessão leva ao `00`. *(alterado em 2026-11-23 — Adendo 4: ela está no `00` como Q30, respondida no Adendo 4; resta só a Q?3, cujo número final é o do `01`)*
*(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* A pergunta nova desta rodada, a Q?6, é de raia desenho e vai ao `01`; nenhuma vai ao `00`. *(alterado em 2026-11-23 — step 9 do Adendo: virou a Q31, decidida pela sessão — D44)*

## Revisão adversarial

*(seção nova em 2026-11-20, quarta rodada)* A sessão despachou a revisão por decisão própria (o perfil não a exigia). O revisor recebeu só o `00`, o `04`, o `05` e o glossário, e devolveu 34 achados: 18 implementações erradas que passavam, 12 oráculos fracos, 3 de cobertura de RQ e 1 da sonda 6. A sessão decidiu cada um. O fechamento criou cenário novo, então cabe uma re-revisão, uma vez só.

Resultado:
- 32 aceitos. Um deles, o ADV-31, foi aceito com um sub-ponto rejeitado, e o que ele aponta ficou estacionado na Q11.
- 1 estacionado na Q11 (ADV-17).
- 1 rejeitado como cenário (ADV-34).

Os mutantes que vieram da revisão levam a marca `revisão adversarial` e não contam para o teto do perfil.

| Achado | O que o revisor achou | Decisão | Destino ou motivo |
|---|---|---|---|
| ADV-01 | os seis itens do README sem F-nn na base podiam casar com a F-nn de outra feature | aceito | R5: o termo de cada item fica fixado no `04`; CT-05 (a linha casada traz o termo) e CT-35 (controle); R5-M6 |
| ADV-02 | F-nn marcada como removida e ainda entregue por peça do modelo | aceito | R6: CT-36, linha da F-05 removida e entregue; R6-M5 |
| ADV-03 | a associação peça → F-nn era livre: o pacote da peça e o lugar da feature não eram conferidos | aceito | R6: CT-06 (o pacote que a linha nomeia; a rota do "onde fica" no código próprio) e CT-36; R6-M6 e M7; R6-M8 sem matador (código próprio cujo "onde fica" não é rota) |
| ADV-04 | pacote cujo plugin um painel registra, jogado na lista dos sem feature própria | aceito | R27: CT-28, linha do Health na lista; R27-M5 |
| ADV-05 | o `/app` só com diagrama de arquitetura compartilhado, sem as peças do modo com tenant | aceito | R34, nova: CT-37 (sem tenant, G2) e CT-38 (com tenant, G3) |
| ADV-06 | o catálogo pt do kit recebe o texto en, e o filtro do CT-B04 isentava essas chaves | aceito | R31: CT-B07, novo, no `05`; R31-M7 e M8 |
| ADV-07 | prova tirada de outro arquivo cujo trecho nomeia a peça usada | aceito, com as formas válidas medidas pela sessão | R10: arquivo da origem, trait ou concern que a origem usa, provider do painel, ou método de contrato do alvo; CT-10, linha do `UserExporter`; R10-M6. A quinta forma, para a ligação cuja origem é peça de pacote, veio da Q19 (decidida — D26), com R10-M8 e M9 |
| ADV-08 | a peça que o framework acrescenta pela configuração do painel não tinha forma de prova | aceito | R10: quarta forma, o trecho que configura o painel; CT-10, linhas de `->id()` e `->tenant()`; R10-M7 |
| ADV-09 | o número novo é uma feature no pt e outra no en | aceito | R1: CT-01, linha da F-72; R1-M5 |
| ADV-10 | descrição e passo en copiados do pt; marca de nome de código de fachada; motivo da célula en não conferido | aceito; a marca só vale para título que é o nome de uma peça do modelo ou de um comando `kit:*` | R16: CT-16, duas linhas; R16-M4 e M5. R19: CT-21, linha do motivo en; R19-M6 |
| ADV-11 | o `lifecycle` não nomeia peça, e a transição inventada passava | aceito | R22 separa os tipos de fluxo; R35, nova (CT-39): estado e transição com âncora que resolve; R35-M1 e M3; R35-M5 sem matador (Q18, decidida — D25) |
| ADV-12 | marco com versão e data certas e a feature de outra versão | aceito | R29: o marco traz um termo da seção dessa versão; CT-31, linha "Passkeys" na 0.56.0; R29-M6 |
| ADV-13 | o passo do conferidor do mapa entra no workflow sem poder reprovar | aceito | R23: CT-42, novo; R23-M5 |
| ADV-14 | biblioteca nova por CDN, por arquivo copiado ou como devDependency fora da lista de recusa | aceito | R21: as devDependencies viram lista fechada (CT-23), e o HTML não carrega recurso de fora (CT-B01); R21-M4 e M5 |
| ADV-15 | agrupamento que nomeia os membros só no texto da peça agrupadora | aceito | R12: CT-14, linha do texto da peça agrupadora; R12-M5 |
| ADV-16 | tipo de peça errado dentro do conjunto fechado | aceito | R8: o tipo confere com a classe; CT-08, linha do `User` como recurso; R8-M6 |
| ADV-17 | a detecção do CT-32 rodava numa configuração só, e a âncora que não resolve não tinha linha | estacionado — Q11 | só vale se a Q11 confirmar a marca; volta com a R30. *(alterado em 2026-11-23 — Adendo 2: voltou. O CT-56, novo, detecta com tenant; o CT-32 e o CT-56 ligam a chave da condição no valor oposto ao de fábrica; o CT-32 tem a linha da âncora que não resolve; R30-M6 e M7)* |
| ADV-18 | a linha da rule no índice casava só a página do catálogo | aceito | R7: CT-07 (casa também `app/**`, `composer.json`, `package.json` e `routes/**`); R7-M4 |
| ADV-19 | o R13-M3, número de linha guardado no modelo, não divergia no PR que o cria | aceito | R9: CT-09, linha da prova com número de linha, na suíte do kit, que roda em todo PR; o R13-M3 passa a morrer no CT-09 |
| ADV-20 | lista à parte de features no mapa passava no CT-02 | aceito | R2: CT-02, linhas da tabela de F-nn na página do mapa e da lista num arquivo do mapa; R2-M1 com matador novo; R2-M4 |
| ADV-21 | CT-05 e CT-06 só tinham o mundo publicado | aceito | CT-35 (R5) e CT-36 (R6), com as cópias sugeridas |
| ADV-22 | motivo de enfeite na remoção, na página e no pacote | aceito | CT-03, CT-35 e CT-28, linhas "—" e "não se aplica" sem porquê; R3-M6, R5-M7 e R27-M6 |
| ADV-23 | o piso do CT-09 não separava todas as ligações das de registro | aceito | R9: o CT-09 confere tantas provas quantas ligações o modelo tem; R9-M6 |
| ADV-24 | aresta inventada ou invertida no desenho | aceito, com as três formas de aresta medidas pela sessão | R36, nova (CT-40); R36-M1 a M4 |
| ADV-25 | link de prova para outro repositório; card que não aparece ao leitor | aceito | R13: o card no painel lateral, à vista sem foco (CT-B02); R14: o link do passaporte vai ao `support.source` no commit do build (CT-B03); R13-M6 e R14-M3 |
| ADV-26 | célula que leva a diagrama do outro modo; a metade "outro painel" do R19-M4 sem linha | aceito | R17: CT-17 e CT-43, novo; R17-M6. R19: CT-21, linha do `sequence` do app |
| ADV-27 | diagrama declarado de um painel, copiado de outro | aceito, com as três formas de aresta | R36: CT-40, linha do `UserResource` no `/infra`; R36-M5 |
| ADV-28 | o CT-B01 reduzia o oráculo à contagem de peças | aceito | R24: CT-B01 confere o conjunto de rótulos; R24-M6 |
| ADV-29 | a exceção não capturada ficava fora da escuta | aceito | R24 e R32: CT-B01 e CT-B05 escutam também a exceção; R24-M7 |
| ADV-30 | a busca e o detalhe montam os textos só na interação | aceito | R31: CT-B04 lê a interface também com a busca e o passaporte abertos; R31-M6 |
| ADV-31 | o `04` afirmava a marca da peça condicional, que a Q11 deixa aberta | aceito por inteiro, e a marca fica estacionada — Q11; o sub-ponto do Estado das RQ no `00`, rejeitado | a R30 (CT-32, CT-33), a metade de condição do CT-B01 e o R24-M5 saem do conjunto vigente, com os IDs reservados (Cláusulas abertas). *(alterado em 2026-11-23 — Adendo 2: voltaram com a resposta — a R30 com o CT-32 e o CT-56, o CT-33 na R41, a marca na R42, o passo 4 do CT-B01 e o R24-M5)* O sub-ponto foi rejeitado porque, pela regra da skill, cláusula que depende só em parte da resposta continua "fechada", e o recorte fica listado no `04` |
| ADV-32 | a ordem dos passos estava bloqueada na Q6 sem precisar | aceito | R35 (CT-39): toda transição de fluxo tem âncora que resolve; a ordem é conferida quando duas transições seguidas têm âncora no mesmo arquivo e método; R35-M2; R22-M3 ganha matador; R35-M4 fica sem matador, com o motivo |
| ADV-33 | o piso da varredura parava no registro dos painéis | aceito (virou a P-12) | R28: a lista com motivo vale também para peça registrada; CT-41, novo (classes de `app/` fora de `app/Filament`); R33: a página declara o limite (CT-34); R28-M4 a M6 e R33-M4 |
| ADV-34 | o executor acumula o papel de aprovador (merge, tag) | rejeitado como cenário | é controle de processo da RQ-26: a sessão e o Codex nunca fazem merge, push na `main` nem tag, e a sessão confere HEAD, tags e o estado do PR antes e depois de cada despacho do Codex. Confere-se no quality gate e no PR |

## Revisão adversarial — segunda rodada

*(seção nova em 2026-11-20, quinta rodada)* É a segunda e última rodada: a skill tem teto de duas, e não há terceira. O revisor recebeu só o `00`, o `04`, o `05` e o glossário, e devolveu 32 achados. A sessão aceitou todos, alguns com o oráculo ajustado pelo que mediu. Esta derivação conferiu por comando cada oráculo novo ou reescrito, e cada fato de linha de controle, contra dados reais: o código, o vendor, o catálogo, o README, o CHANGELOG e o esqueleto do mapa construído (os 13 arquivos de diagrama de `site/mapa/diagramas/` e o HTML de `site/dist/mapa/`, na revisão `ae5d20f`). Durante a rodada, a sessão publicou os construtores do mapa (commit `4adaf9a`), e os oráculos do site construído foram conferidos de novo nesse commit: os 17 arquivos de diagrama dele, gerados pelo `site/mapa/gerar.mjs --saida` numa pasta de rascunho, sem tocar no `dist/`. O gerador aceita 10 dos 17; os 7 escritos à mão no esqueleto, sem prova por ligação, ele recusa, e os tipos `lifecycle`, `erd`, `class`, `sequence`, `workflow` e `dataflow` ficam sem medição nesse commit (o `erd`, o `sequence`, o `workflow` e o `dataflow` foram medidos no trabalho em andamento da sessão, fora de commit, só para os seletores do `05`). A sessão publicou também o catálogo da entrega (commit `0f38e2f`, com a F-72 a F-121 e a coluna Origem), e os oráculos do catálogo que dependem das linhas novas — a identidade por tokens (ADV2-01) e a resolução dos caminhos (ADV2-04) — foram conferidos nele. Por último, a sessão publicou a página do mapa (commit `e7d6eb5`) e os fluxos escritos a partir do código (commit `4cc9517`, que tira os diagramas de arquitetura escritos à mão do esqueleto). **A base da conferência fica congelada no `4cc9517`**: os 43 arquivos de diagrama dele, de todos os tipos e todos aceitos pelo gerador, foram gerados de novo na pasta de rascunho e conferidos no Chromium, e a página, o README e o modelo foram lidos nele; commit posterior não foi conferido. Medido no `4cc9517`: o `<h1>` e o rótulo de cada peça iguais aos da fonte nos 84 HTML fora o comparativo; a busca com um resultado por peça em 42 de 42 diagramas, e nenhum sem JavaScript; nenhum erro, nenhuma requisição para fora e nenhum `<script src>` nos 86; os pares de arestas dos 4 mapas de arquitetura iguais aos da fonte; 1.172 itens de prova, todos achados, inteiros e sem corte; 685 links de prova de peça, todos com o trecho na linha; 1.416 textos en de duas palavras ou mais sem palavra funcional do pt; nenhuma marca de disponibilidade; nenhum item que é nome de F-nn nas 13 listas e tabelas da página nem nos 328 cards; e o registro do `/admin` (57 peças) e o do `/infra` (84) iguais nos dois modos, e o do `/app` com 46 sem tenant e 48 com. Onde a conferência reprovaria dado correto, o oráculo foi ajustado, e a última coluna diz o que se mediu.

Placar: 32 aceitos. Os achados estruturais — ADV2-14, ADV2-20, ADV2-29 e ADV2-30 — a sessão escalou ao mantenedor. Os mutantes que vieram desta rodada levam a marca `revisão adversarial (ADV2-nn)` e, pela regra da skill, não contam para o teto da regra.

| Achado | O que o revisor achou | Decisão | Destino | Conferido contra dados reais |
|---|---|---|---|---|
| ADV2-01 | duas F-nn novas, sem página, pacote nem rota, trocam de feature entre pt e en | aceito, com o oráculo ajustado | R1: nas linhas novas, os tokens entre crases do pt são os do en; CT-01; R1-M6, e a troca entre linhas sem token, lacuna declarada (R1-M8) | 4 das 74 linhas da base diferem nos tokens (F-02 e F-04 por `{painel}`/`{panel}`, F-07 com o en errado, F-51 com a palavra traduzida) e 10 não têm token: a regra vale só para as linhas novas. No commit `0f38e2f`, as 50 linhas novas (F-72 a F-121) têm os mesmos tokens nos dois idiomas, e a F-118 ("Voltar ao topo", código próprio, sem página nem rota) não tem token nenhum: "e não vazios" reprovaria dado correto, e saiu |
| ADV2-02 | F-nn removida num idioma só | aceito | R1: o estado entra na identidade; CT-01; R1-M7 | a base não tem F-nn removida |
| ADV2-03 | o README publicado ganha item fora do catálogo | aceito | R5: o CT-05 lê também a lista publicada; CT-35; R5-M9 | 46 itens e 46 títulos diferentes por idioma: 26 em negrito, 17 pelo separador e 3 pelo item inteiro. A lista não muda no commit `4cc9517`: a seção "Mapa interativo" que o `e7d6eb5` pôs no README fica fora dela |
| ADV2-04 | caminho velho no "onde fica" passava com "basta um caminho" | aceito | R6: todo caminho resolve; CT-06, CT-36 (F-07 en) e CT-44, novo, com tenant; R6-M9 | na base, pt: 51 caminhos, 3 só com tenant, e todos resolvem; en: só a F-07 (`/{panel}/my-profile`) não resolve. No catálogo da entrega (commit `0f38e2f`): 92 caminhos por idioma, 6 só com tenant, e todos resolvem nos dois modos; a F-07 en foi corrigida |
| ADV2-05 | a F-nn não chega ao leitor | aceito (D29), com o oráculo ajustado | R6: o CT-B01 confere a F-nn no desenho da peça — o `tag` no nível de leitura cheio, no mapa de arquitetura, e o sublabel, na árvore de registro; R6-M10 | no commit `4adaf9a`: o `tag` das 4 peças com F-nn do `app-acesso` fica com opacidade 0 no carregamento (nível "read") e com 1 no nível "full", pelo botão "+", e com a peça em foco pela busca; o passaporte mostra o `tag`, e a busca por "F-02" acha as duas telas que a entregam. O esquema `tree` do archify não tem `tag`, e a árvore leva a F-nn no sublabel, à vista no carregamento (5 peças em cada árvore do `/app`). "À vista" no carregamento reprovaria o mapa de arquitetura, e o ponto vai ao coordenador |
| ADV2-06 | "fora de comentário" só definido para PHP | aceito | R9: comentário por tipo de arquivo; CT-09, linha Blade; R9-M7 | a linha 1 do `convites-recebidos.blade.php` é um `{{-- --}}` com `ConvitesRecebidos::table()`, que o arquivo não traz fora dele |
| ADV2-07 | o número de linha guardado fora do caminho | aceito | R9: o modelo não tem campo de linha, e as provas dos arquivos de diagrama são âncora; CT-09, duas linhas; R9-M8 | 405 provas nos 13 arquivos do esqueleto: 3 com `arquivo` e `trecho` e 402 também com `label`; nenhuma com linha. No commit `4adaf9a`: 977 âncoras no modelo e nos 17 arquivos de diagrama, 652 com `arquivo` e `trecho` e 325 também com `label`, nenhuma com linha |
| ADV2-08 | trait que a origem não usa, e método qualquer do alvo | aceito | R10: o trait que a origem usa e a lista fechada de métodos de contrato; CT-10, duas linhas; R10-M10 e M11 | 6 interfaces e 7 métodos de contrato no vendor; o `Projeto` usa o `BelongsToTenant`, e o `UserResource`, não |
| ADV2-09 | par de mapeamento com as pontas trocadas | aceito (D27) | R10: o trecho é a linha inteira do par; CT-10, linha do `MailLog`; R10-M12 | a linha 67 do `PoliciesDeVendor` tem 22 espaços de alinhamento, e a 68 mapeia o `MailLog`. No modelo do commit `4adaf9a`, as 10 ligações que saem do `PoliciesDeVendor` levam a linha inteira do par como trecho, com os espaços |
| ADV2-10 | as páginas que o painel liga pela configuração ficam fora do registro | aceito (D30) | R11: o registro vivo lê os getters da configuração; CT-11 e CT-13; R11-M6 | `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` (três painéis), `RegistroPorConvite` e `TelaVerificacaoDeEmail` (`/app`) fora do `getPages()`; com tenant, os getters da organização devolvem nulo. No modelo do commit `4adaf9a`, o painel app e o admin registram a `TelaLogin` nos dois modos, com o trecho `->usingPage(TelaLogin::class)` |
| ADV2-11 | rótulo de nó que não é peça sai igual ao pt | aceito, com o oráculo ajustado | R16: termo do glossário com o en dele, frase pelo critério do ADV2-28, código isento; CT-16; R16-M6 | 373 pares iguais nas traduções do esqueleto, todos código ou texto em inglês; dos 4 rótulos que são termo do glossário, 2 não batem (Inativa → Off; Telas de acesso → Access screens) |
| ADV2-12 | diagrama de um modo marcado como compartilhado | aceito | R17: nas duas direções; CT-17 e CT-43; R17-M7 | no esqueleto, os diagramas de um modo só têm um modo em `modos` |
| ADV2-13 | ligação de um modo desenhada no diagrama do outro | aceito | R18: CT-19; R18-M4, e o modo da ligação de uso, lacuna declarada (R18-M5) | no esqueleto, o `app-resumo-sem` não desenha peça nem ligação do modo com tenant, e o `app-resumo-com` desenha 5. No modelo do commit `4adaf9a`, "o painel app usa o model `Tenant`" está nos dois modos, embora o trecho dela, o `->tenant()`, só rode com tenant, e o "registra `IdentifyTenant`", com o mesmo trecho, só no modo com tenant: é defeito do modelo, que a linha de controle do CT-19 acusa; nenhuma ligação do modelo vale só com tenant entre peças dos dois modos no `/app` |
| ADV2-14 | fluxo de painel declarado do kit inteiro | aceito (D28); estrutural, escalado | R19: CT-21; R19-M7 | os 6 diagramas do kit inteiro do esqueleto não desenham peça registrada; o `conta` cita o `UserResource` só como âncora. No commit `4cc9517`: 22 diagramas do kit inteiro, nenhum nó com o nome exato de uma das 112 peças que painel registra (o marco 0.33.0 cita `Dashboard` numa frase, e conferir por palavra reprovaria dado correto); a pilha da requisição saiu em dois diagramas de painel, o comum, dos três painéis e dos dois modos, e o do `/app` com tenant |
| ADV2-15 | biblioteca embutida, ou em `peerDependencies` | aceito (D32), com a lista ajustada | R21: o CT-23 vira Esquema, com os quatro campos e a lista fechada de `site/mapa/`; R21-M6 e M7 | sem `optionalDependencies` nem `peerDependencies`. No commit `4adaf9a`, `site/mapa/` tem `archify.mjs`, `diagramas/`, `gerar.mjs`, `interface.json`, `ligacoes.json`, `pecas.json` e `posicionar.mjs`. A lista da D32 não nomeia os dois arquivos do modelo, e com ela a linha "publicados" reprovaria dado correto; a lista fechada os inclui, e a divergência vai ao coordenador |
| ADV2-16 | o conferidor reprova e termina com código 0 | aceito | R23: controle de processo no CT-B06; R23-M6 | o `site/verifica-mapa.mjs` do commit `4adaf9a`, rodado numa pasta de rascunho sobre uma cópia em que o HTML do `app-resumo-sem` não desenha nada, saiu com código 1 ("nenhum `<svg role="img">` em 15000 ms", nas quatro entradas da amostra); sobre a cópia com o HTML gerado, saiu com 0 |
| ADV2-17 | o gerador perde as arestas | aceito, com o oráculo ajustado | R12: o CT-B02 confere os pares do DOM contra a fonte — o arquivo, no diagrama escrito à mão; o modelo, no construído; R12-M7 | no esqueleto, 76 e 76, 14 e 14, 43 e 43 pares nos três `architecture`. No commit `4adaf9a`, o arquivo construído não tem `connections`, e "o do arquivo" reprovaria dado correto; contra o modelo, 17 e 17 (`app-acesso`), 14 e 14 (`app-organizacoes`) e 16 e 16 (`app-resumo-sem` e `app-resumo-com`) |
| ADV2-18 | comando `kit:*` na lista dos sem feature | aceito | R28: CT-41; R28-M7 | 9 comandos `kit:*`. No modelo do commit `4adaf9a`, nenhum dos 9 tem F-nn, e o catálogo da entrega (`0f38e2f`) dá a F-102 ao `kit:install` e a F-105 ao `kit:update`: o CT-41 e o CT-06 reprovam esse modelo até a sessão ligar as F-nn, e o oráculo fica |
| ADV2-19 | violação com alvos mistos | aceito | R32: CT-B06; R32-M7 | o conferidor já filtra os alvos (`v.nodes.filter`) |
| ADV2-20 | comparativo sem cards | aceito (P-04 emendada); estrutural, escalado | R20: CT-22; R13 tira o comparativo dos cards; R33: CT-34; R24: o CT-B01 abre o comparativo; R20-M3 e R33-M5 | comparativo nos dois idiomas: `lang="en"`, nenhum card, nenhuma requisição para fora; a `base` e a `cabeca` estão publicadas. No commit `4adaf9a`, o mesmo; a casca não tem `<h1>` com o título do arquivo: mostra o subtítulo "base → cabeça", com os títulos dos dois diagramas no idioma da página |
| ADV2-21 | lista só de nomes de feature | aceito, com o oráculo ajustado | R2: limiar de 3 nomes de F-nn em item de lista, linha de tabela ou item de card; CT-02; R2-M5 | os nós do `infra-registro` trazem 3 nomes de F-nn (são peças), então nó não conta; cards do esqueleto: nenhum; a página prevista no passo 6 do `01` só tem link de diagrama e motivo. No commit `4cc9517`, com a página publicada e os cards dos 43 diagramas: nenhum item que é nome de F-nn nas 13 listas e tabelas da página nem nos 328 cards, nos dois idiomas |
| ADV2-22 | a prova do card não amarrada à ligação | aceito, com o oráculo ajustado | R13: CT-B02; R13-M4 reescrito e R13-M7 | o gerador escreve um item por ligação, "origem → destino (verbo): arquivo:linha — trecho". No commit `4adaf9a`, o painel lateral do mapa construído tem também os cards "Como ler as setas" e "Quem está em …", e o item de prova se reconhece pela forma; nos 6 diagramas construídos, 281 itens de prova por idioma, igual às ligações desenhadas (75 no `app-resumo-sem`, sobre 16 pares de arestas), todos inteiros, e nenhum diagrama repete nome de peça |
| ADV2-23 | amostra de uma peça só, e a linha nunca conferida | aceito, com o oráculo ajustado | R14: CT-B03; R14-M6 | no esqueleto, 226 links de prova de peça, todos com o trecho na linha. No commit `4adaf9a`, todos também: 21, 9, 49, 47, 1 e 1 nos construídos, e 73, 31 e 26 no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`; o `app-acesso` tem 5 peças de pacote em 21, e os outros três `architecture`, nenhuma |
| ADV2-24 | a exceção da interação não é pega | aceito | R14 e R31: CT-B03 e CT-B04; o R24-M7 passa a eles | nenhum erro no esqueleto com a busca aberta e uma peça em foco; no commit `4adaf9a`, nenhum no `app-acesso` e no `app-organizacoes`, nos dois idiomas, com a busca e o passaporte abertos |
| ADV2-25 | o termo do marco à escolha dele | aceito | R29: o título visível; CT-31; R29-M7 | "login" está na seção da 0.56.0, e "Passkeys", não |
| ADV2-26 | a lista de textos iguais sem teto | aceito, com o oráculo ajustado | R31: CT-B07 fechado às 14 chaves medidas e às só de marcadores; R31-M9 | 14 das 461 chaves do pt do archify iguais ao en. No commit `4adaf9a`, cada HTML embute só as 390 chaves de interface (as mesmas no pt e no en), e "o pt tem as 486" sobre o HTML reprovaria dado correto; as outras 96 o archify aplica no render. Iguais ao en: 9 a 11 por HTML entre as 390 e 14 na fonte do kit, todas entre as 14 |
| ADV2-27 | o en do CT-B04 sem critério | aceito | R31: CT-B04; R31-M5 reescrito | no esqueleto, a legenda en do `admin-acesso` traz os nomes en dos tipos. No commit `4adaf9a`, a do `app-acesso` traz Screen (panel, page, resource, widget, Livewire), Kit code (provider, service, command), Data (model, settings), Third-party package or plugin e Access (middleware, policy); nenhum texto de interface en igual ao pt de uma chave cujo en é outro |
| ADV2-28 | "traduzido" conferido como "diferente do pt" | aceito | R16 e R19: palavra funcional do pt; CT-16 e CT-21; R16-M7 e R19-M8 | nenhum dos 482 valores en de duas palavras ou mais traz uma; o detector acha 199 dos 827 textos pt. No commit `4adaf9a`: nenhum dos 403 textos en dos 17 arquivos de diagrama, nem dos 14 textos en do gerador, nem dos 9 das sobrescritas de tipo. "Do" também é palavra inglesa: nenhum texto en do commit `4adaf9a` a traz, e, se um vier a trazer, a lista é decisão da sessão |
| ADV2-29 | o CT-37 afirmava o recorte da Q11 | aceito; estrutural, escalado | R34: a linha admin tira a peça condicional; a Q11 segura o recorte. *(alterado em 2026-11-23 — Adendo 2: a Q11 respondeu na direção da recomendação; a linha admin deixa de tirar a peça pelo nome e passa a subtrair o que o discriminante vivo dá como indisponível sem tenant; R34-M5)* | — |
| ADV2-30 | a marca publicada não era conferida | aceito; estrutural, escalado | R37, nova: CT-45; R37-M1 a M3. *(alterado em 2026-11-23 — Adendo 2: a regra deixa de ser vácua; a presença da marca é da R42, o conteúdo continua da R37)* | o esqueleto não publica marca (a regra é vácua hoje); o `canAccess()` das duas peças condicionais lido no código. No commit `4adaf9a`, nenhum dos 17 arquivos de diagrama publica marca, e o modelo registra o `TenantResource` e o `AgenteIaResource` nos dois modos |
| ADV2-31 | publicação a partir de outro ramo | aceito (D31) | R23: CT-25 e CT-42; R23-M7 | `if: github.ref == 'refs/heads/main'` no job `build` do `pages.yml` |
| ADV2-32 | texto cortado à vista | aceito, com o oráculo ajustado | R13: o item de card inteiro (CT-B02); R24: o rótulo igual ao da fonte (CT-B01); R13-M8 | o item de card do archify quebra a linha (`overflow-wrap: anywhere`); um item de 160 caracteres sem espaço ficou com 346 px nas duas medidas; o esqueleto escreve "…" à mão em 33 rótulos, e por isso o rótulo é conferido por igualdade, e não por "sem reticências". No commit `4adaf9a`, nenhum dos 562 itens de prova dos construídos é cortado |

## Setup Global

### Sentinela, grupo e suíte
- Todo caso lê `site/`, `docs/`, `README*.md`, `CHANGELOG.md` ou `.ai/rules/`, e nada disso viaja para o projeto instalado (`.gitattributes`: `/site`, `/docs` e `CHANGELOG.md` com `export-ignore`). Os dois arquivos levam a sentinela `naArvoreDoKit()` no `beforeEach`, com `markTestSkipped`, no molde de `tests/Kit/GuardasDosDiagramasTest.php`. Fontes: `.ai/rules/testes.md` (path interpolado) e `[CT-10]`/`[CT-11]` de `tests/Kit/RedeDeDocumentacaoTest.php`.
- Grupo `kit`, pelo `tests/Pest.php`. Nenhum caso Pest chama a rede.

### Modo de tenancy
- `tests/Kit/MapaInterativoTest.php` usa `Tests\TestCase`, o modo sem tenant. `tests/Tenancy/MapaInterativoTenancyTest.php` usa `Tests\TenancyTestCase`, o modo com tenant.
- O `Dado` de cada caso afirma o **valor lido** — `config('kit.tenancy.enabled')` e, por painel, `hasTenancy()` — e não presume a suíte.

### Registro vivo: getters conferidos no vendor (Filament 5.10.2)
- Painéis: `Filament::getPanels()` (`vendor/filament/filament/src/FilamentManager.php:getPanels:384`). É a lista da grade e do registro, nunca escrita à mão.
- Recursos, páginas e widgets: `getResources()`, `getPages()` e `getWidgets()` (`vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getResources:466`, `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getPages:442`, `vendor/filament/filament/src/Panel/Concerns/HasComponents.php:getWidgets:490`).
- *(alterado em 2026-11-20: ADV2-10, D30)* Páginas que o painel liga pela configuração, e não pelo `->pages()`: `getLoginRouteAction()` (`vendor/filament/filament/src/Panel/Concerns/HasAuth.php:getLoginRouteAction:561`), `getRegistrationRouteAction()`, `getRequestPasswordResetRouteAction()`, `getResetPasswordRouteAction()`, `getEmailVerificationPromptRouteAction()` e `getProfilePage()`, todos em `HasAuth`; e, com tenant, `getTenantRegistrationPage()` e `getTenantProfilePage()` (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:getTenantProfilePage:262`). Medido no kit, nos dois modos: `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` nos três painéis, e `RegistroPorConvite` e `TelaVerificacaoDeEmail` no `/app`, fora do `getPages()`; `getProfilePage()` e os dois getters da organização devolvem nulo.
- Plugins: `getPlugins()` (`vendor/filament/filament/src/Panel/Concerns/HasPlugins.php:getPlugins:38`).
- Middlewares:
  - `getMiddleware()` devolve também o alias `panel:{id}` (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getMiddleware:97`, `vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getId:100`).
  - `getAuthMiddleware()` (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:getAuthMiddleware:108`).
  - `getTenantMiddleware()` devolve `IdentifyTenant` **sempre** (`vendor/filament/filament/src/Panel/Concerns/HasMiddleware.php:IdentifyTenant:119`). Por isso o middleware de organização só conta com `hasTenancy()` do painel (`vendor/filament/filament/src/Panel/Concerns/HasTenancy.php:hasTenancy:207`), nunca com a chave da instalação.
- **A peça que um plugin traz.** O plugin registra a peça no `register()` dele. O `FilamentSpatieLaravelHealthPlugin` faz `$panel->pages([$this->getPage()])` com a página `HealthCheckResults` (`vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php:pages:36`). No código versionado do kit, essa peça só aparece como o registro do plugin no provider do painel (`app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`). A guarda confere que o plugin traz a peça pelo próprio plugin: o `register()` dele, num painel de teste, devolve a peça. Não confere pelo nome.
- **O que muda de registro entre os modos, medido nesta derivação.**
  - **No `/app`**, a organização é decidida no boot (`app/Providers/Filament/AppPanelProvider.php:'kit.tenancy.enabled':658`).
  - **No `/admin` e no `/infra`**, o registro é o mesmo nos dois modos, e o que muda é a disponibilidade. A tela de organizações é descoberta sempre e aberta só com tenant (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`). A tela opcional segue a mesma convenção (`app/Providers/Filament/AdminPanelProvider.php:Par sempre registrado:151`, `app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:canAccess:70`). Fora do `/app`, `grep -n "if (config(" app/Providers/Filament/*.php` só acha `lockscreen.enabled`, que acrescenta item de menu, e não peça.
  - Por isso os CT-11 e CT-12 afirmam a completude do registro sem amarrá-la ao modo, e só afirmam o modo na direção "o modelo não dá a um modo o que o painel não registra nele". A disponibilidade (a marca da peça condicional e o diagrama de modo em que ela entra) está estacionada na Q11 *(alterado em 2026-11-20: ADV-31; era "é da R30")*. *(alterado em 2026-11-23 — Adendo 2: destravada — a condição no modelo é da R30, o diagrama de modo da R41 e da R34, a marca da R42; a subseção seguinte diz como se mede)*
  - O `/app` é o único painel cujo registro muda entre os modos. Por isso é o único que precisa de um diagrama de arquitetura por modo (R34). *(alterado em 2026-11-23 — Adendo 2: pela RQ-28 e pela RQ-29, a disponibilidade também separa. O `/admin` ganha diagramas por modo por causa do `TenantResource`, e o `/app`, além do registro, tem quatro peças registradas nos dois modos que só abrem com tenant — medido abaixo. O `/infra` continua igual nos dois modos)*

### A peça condicional e o discriminante *(alterado em 2026-11-23 — Adendo 2: subseção nova)*

- **Contrato do helper único** *(alterado em 2026-11-23 — Adendo 4)*. Esta subseção, com a regra da guarda da R30 (o parágrafo "A guarda, nas duas suítes…") e a tabela da população da R43, é a definição única do discriminante. Ela vira um helper só, no `tests/Pest.php`, que os três arquivos chamam do mesmo jeito: o `tests/Kit/MapaInterativoTest.php` (CT-32), o `tests/Kit/MapaInterativoDiagramasTest.php` (CT-33, CT-37) e o `tests/Tenancy/MapaInterativoTenancyTest.php` (CT-56, CT-38).
  - **Entra:** o painel, o modo da suíte, a peça e a condição que o modelo dá a ela, se houver.
  - **Sai** — duas saídas, e cada chamador usa a sua:
    - **a da guarda da condição** (R30: CT-32, CT-56): disponível de fábrica; e disponível com **só** as chaves e a configuração que a condição do modelo nomeia, ligadas juntas. É ela que põe a condição do modelo à prova: chave errada não abre a peça, e a guarda reprova;
    - **a do lugar** (a subtração e a metade viva: CT-33 sem tenant, CT-37, CT-38): disponível no modo se abre de fábrica, **ou** com alguma chave booleana de `kit.*` desligada ligada, uma de cada vez, **ou** com as chaves e a configuração que a condição do modelo nomeia, ligadas juntas — a soma. O modelo só propõe: a chave errada no modelo não tira do diagrama a peça que a chave certa abre, e a peça que nada abre sai.
  - **O modo nunca é ligado pela guarda** *(alterado em 2026-11-23 — Adendo 4)*: `kit.tenancy.enabled` fica fora das chaves que ela liga, em qualquer saída. O modo é o da suíte, decidido no boot; ligá-lo em tempo de execução "abriria" o `TenantResource` sem tenant e o tiraria da subtração.
  - **A população:** o que o item "A população", abaixo, enumera, por tipo — o registro vivo, a peça fora do registro com método próprio, o componente flutuante pelo predicado dele —, nunca uma lista de nomes, fora o mapa fechado de predicados do componente flutuante.
  - **O arnês:** o super_admin, o `PapeisSeeder`, o descarte do Shield e o boot por painel, a organização aberta no `/app` com tenant, e a chave desligada de volta no mesmo caso.

- **O discriminante.** Uma peça que o painel registra — recurso, página ou widget, a população de `registroVivoDoPainel()` (`tests/Pest.php:registroVivoDoPainel:4074`) — está **disponível** num modo quando o `canAccess()` (recurso e página) ou o `canView()` (widget) dela, vivo, devolve verdadeiro:
  - com o super_admin do kit autenticado: o papel que `filament-shield.super_admin.name` nomeia, `master_global` (`config/filament-shield.php:'super_admin':92`), que vence toda permissão pelo `Gate::before` (`app/Providers/KitServiceProvider.php:isMasterGlobal:488`). Por isso a permissão e o papel não decidem nada aqui, como a P-16 pede: o `AiRunResource`, que só pede a permissão `ver-ai-tasks` (`app/Filament/Infra/Resources/AiRuns/AiRunResource.php:canAccess:96`), fica disponível;
  - com a configuração de fábrica: o `Dado` afirma o valor **lido** de toda chave booleana de `kit.*` igual ao que o `config/kit.php` dá sem ambiente nenhum (o default do `env()`), fora `kit.tenancy.enabled`, que é o modo. O `phpunit.xml` força a maior parte das `KIT_*`; a que ele não força vem do `.env` de quem roda, e por isso o `Dado` confere o lido em vez de presumir;
  - no modo da suíte: a Kit (G2) é o modo sem tenant, e a Tenancy (G3), o com. O modo é decidido no boot, e por isso a tabela de decisão da R30 se parte nas duas suítes.
- **A chave (P-15).** A peça indisponível num modo é condicional:
  - pela **chave**, quando, com essa chave ligada — o valor oposto ao de fábrica —, fica disponível nesse modo; *(alterado em 2026-11-23 — Adendo 3: a chave é a booleana de `kit.*` ou a configuração de pacote (RQ-30). A booleana se liga pelo valor oposto ao de fábrica; a configuração de pacote, pelo valor que a condição do modelo dá como ligado — no `CommandRecordResource`, o `command-center.sources` com a `DatabaseSource` (`config/command-center.php:'sources':69`). O modelo só propõe a configuração; quem decide é o `canAccess()` vivo, com ela ligada)*
  - pelo **modo**, quando fica disponível no outro modo, com a configuração de fábrica;
  - pelos **dois**, quando só fica disponível no outro modo e com a chave ligada (o `ProjetoResource` do `/app`: tenant e `KIT_DEMO`, `app/Filament/App/Resources/Projetos/ProjetoResource.php:daDemo:93`).
  - A peça disponível nos dois modos com a configuração de fábrica não é condicional, inclusive a que só esconde o item de menu (P-16): o `Dashboard` do `/admin` some do menu sem `KIT_DASHBOARD_DINAMICO` (`app/Filament/Admin/Pages/Dashboard.php:shouldRegisterNavigation:63`) e abre.
  - A chave ligada de fábrica nunca marca (P-16).
- **A população** *(alterado em 2026-11-23 — Adendo 3)*:
  - a peça que o painel registra — recurso, página e widget, do registro vivo;
  - a peça do modelo que painel nenhum registra e que tem `canAccess()` ou `canView()` estático (RQ-31: a peça que só aparece dentro de uma tela condicional herda a condição dela). Ela é avaliada em cada painel do registro, e a condição é uma só por peça: se os painéis discordarem, a guarda reprova, nomeando a peça e os painéis — falha fechado, porque o modelo não teria como dar a ela a condição certa. Medido: o `AcessosPorPainel` dá o mesmo nos três — indisponível sem tenant, disponível com —, porque o `canView()` dele chama o do `TenantResource` (`app/Filament/Admin/Resources/Tenants/Widgets/AcessosPorPainel.php:canView:39`). Das 11 peças assim, é a única indisponível num modo. A peça que só aparecesse dentro de tela condicional e não tivesse método próprio ficaria invisível ao discriminante; medido, nenhuma no modelo — lacuna declarada (R43);
  - plugin e middleware ficam fora (RQ-32): nunca guardam condição nem levam marca, nem o que serve a tela condicional — o `FilamentCardsPlugin` dos hubs (`app/Providers/Filament/AdminPanelProvider.php:FilamentCardsPlugin:367`) e o `ExigeTutorialGuiadoLigado` (`app/Providers/Filament/AdminPanelProvider.php:ExigeTutorialGuiadoLigado:485`);
  - fora do alcance da RQ-27, da RQ-31 e da RQ-32: o componente flutuante que só aparece com uma chave — o chat do assistente e o checklist do tutorial —, perguntado na Q30. *(alterado em 2026-11-23 — Adendo 4)* Já não fica fora: a RQ-33 o põe na população, no item seguinte.
  - *(alterado em 2026-11-23 — Adendo 4)* o componente flutuante ligado a chave (RQ-33): a peça Livewire do modelo que painel nenhum registra e não tem `canAccess()` nem `canView()` próprio. Medido: no modelo são duas, e são as que a RQ-33 nomeia. Sem método próprio, cada uma é avaliada pelo predicado que decide a renderização dela, num mapa fechado de dois itens:
    - o chat do assistente, pelo `AgentesDeIa::assistenteNoPainel()` do painel (`app/Support/AgentesDeIa.php:assistenteNoPainel:35`), o mesmo que o `chatNaTela()` chama. Pede `KIT_AGENTES_IA` ligada e o painel em `KIT_AGENTES_IA_PAINEIS`, que vem vazia de fábrica;
    - o checklist do tutorial, pelo `TutorialGuiado::visivel()` (`app/Support/TutorialGuiado.php:visivel:52`), o mesmo que o `->launcher()` do plugin usa (`app/Support/TutorialGuiado.php:launcher:67`). Pede `KIT_ONBOARDING`.
    - Um terceiro componente assim, fora do mapa, reprova a guarda, nomeado — falha fechado —, até ganhar o predicado dele.
    - "Ligada", na lista `KIT_AGENTES_IA_PAINEIS`, é a lista com o painel em que a guarda avalia: é o valor que a condição do modelo dá como ligado, como na configuração de pacote.
- **A condição é da peça**, e vale em todo painel que a registra. Medido: o `PrimeirosPassos`, nos três painéis, com a mesma condição. A peça homônima de outro painel é outra peça: só o `UserResource` e o `ConviteResource` do `/app` são condicionais, e os do `/admin`, não. Os exemplos dizem o painel, e a guarda confere pelo id.
- **Entrar no diagrama de um modo — definição compartilhada** *(alterado em 2026-11-23 — Adendo 2: era "registrada no modo")*. A peça entra no diagrama que vale num modo — como nó, como membro de agrupamento ou de porta, na árvore de registro, ou como ponta de item de prova — só quando o painel a registra nesse modo **e** ela fica disponível nele, com a chave ligada (P-15). O diagrama compartilhado vale nos dois modos, e por isso não desenha peça que fica indisponível num deles. A ligação conta como desenhada num diagrama quando as duas pontas entram nele por esta regra. A definição vale para o gerador, para as guardas do kit e para o conferidor do mapa: R12 (CT-14, CT-B02), R17, R18 (CT-19), R34 (CT-37, CT-38), R36, R38 (CT-46, CT-47), R41, R42 e o esperado do `05`. Guarda que conta a ponta só pelo registro fica vermelha contra a implementação certa. *(alterado em 2026-11-23 — Adendo 3: a peça que painel nenhum registra — model, serviço, a peça do kit inteiro — entra pelo que o modelo dá a ela; e, se tem condição — a herdada da tela que a contém (RQ-31) —, só entra no diagrama do modo em que a condição a deixa disponível, como a peça registrada. O `AcessosPorPainel` sai do `kit-resumo-sem` e do `kit-multi-tenancy-opt-in`, compartilhado, no gerador, nas guardas e no conferidor)* *(alterado em 2026-11-23 — Adendo 4)* **No diagrama escrito à mão**, o nó é a peça do nome exato do rótulo, ou a peça cuja classe a prova do nó declara — o arquivo da evidência da peça no modelo e o trecho da declaração. Medido: o participante "AssistenteChat" do `app-chat-de-ia` é o chat pela prova `class AssistenteChatWidget extends Component`; só pelo nome, ele não seria peça, como no R18-M6. A regra vale para o lugar (CT-33), a marca (CT-57, CT-45) e o passo 4 do CT-B01. *(alterado em 2026-11-23 — step 11)* No diagrama escrito à mão, a marca da peça fica só na linha dela no card "Condições de disponibilidade" (R42, P-17), e é pelo nó reconhecido aqui que o card tem de ter a linha.
  - *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* **O nó do diagrama escrito à mão — a D43**, decisão da sessão que completa a regra do Adendo 4 e vale no lugar dela:
    - o nó é a peça cuja classe uma fonte (`sources`) do nó declara: o arquivo da fonte é um arquivo de evidência da peça no modelo, e o trecho da fonte declara a classe (`class`, `interface`, `trait` ou `enum` seguido do nome), sem precisar ser igual ao trecho da evidência;
    - sem fonte que declare classe de peça, o nó é a peça do nome exato do rótulo, só se uma peça só tem esse nome. A prova vem antes do nome: com ela, a homônima do rótulo não entra;
    - com homônimas e sem prova, o nó não designa peça: nenhuma guarda pede marca nem linha de card para ele. A linha que existe mesmo assim é a Q?6;
    - a regra é a mesma para o lugar (CT-33), a marca (CT-57), a R37 (CT-45) e o passo 4 do CT-B01.
    - **Medido no HEAD `1dd48acd`**, nos 339 nós dos diagramas escritos à mão (um `node` sobre os arquivos e o modelo, fora do repositório): 72 são designados pela prova; um só tem rótulo de peça homônima — o `pagina` do `configuracoes-config`, "ConfiguracoesDoKit", que a prova `class ConfiguracoesDoKit extends SettingsPage` faz ser a página do `/admin` (a homônima é a configuração `App\Settings\ConfiguracoesDoKit`) —, e nenhuma das duas tem condição. Com a D43, nenhuma linha de card é ganha nem perdida, e os "publicados" do CT-33, do CT-57 e do CT-45 não mudam.
    - **Hoje três implementações divergem dela**, sem efeito no dado publicado: a guarda do CT-57 e do CT-33 exige o trecho da fonte igual ao da evidência e soma toda peça do nome do rótulo (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasPecasPelaProva` e o ramo do escrito à mão de `mapaDiagramasConstruir`); a da R37 toma, sem prova, a primeira peça do nome (`tests/Kit/MapaInterativoTest.php`, `mapaModeloPecaDoNo`); o conferidor soma o nome à prova e toma toda peça do nome (`site/verifica-mapa.mjs`, `pecasDoNo`). As linhas do A5 e do A6 no CT-57 e no CT-45, e a nota do passo 4 do CT-B01 no `05`, as alinham.
  - *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* **O diagrama que vale nos dois modos e a ligação de um modo só.** "O painel a registra nesse modo" vale para cada modo do diagrama, e a ligação conta como desenhada só se vale em cada um: a árvore de registro compartilhada desenha só a ligação `registra` que vale nos dois modos, e a de um modo só fica fora dela, qualquer que seja a ordem dos modos no arquivo do diagrama. O gerador, as guardas do kit e o conferidor seguem igual.
    - **Hoje** o gerador (`site/mapa/gerar.mjs`, `arvoreDeRegistro`, no commit `1dd48acd`) e o construtor das guardas do kit (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasConstruir`, ramo `registro`) guardam a ligação quando o modo dela inclui o **primeiro** modo do diagrama; o conferidor (`site/verifica-mapa.mjs`, `esperadoDaArvore`), quando inclui **todos**. A árvore compartilhada é uma só, o `infra-registro` (`modos: ["sem", "com"]`), e nenhuma ligação "o painel infra registra" vale num modo só: no dado publicado, a divergência não aparece.
    - **Medido no HEAD `1dd48acd`**, com a ligação "o painel infra registra `FilamentShieldPlugin`" numa cópia em memória do modelo: só sem tenant, o gerador (forma de leitura) e o construtor da guarda (copiado do arquivo de teste e executado fora do repositório) desenham o plugin no `infra-registro` — o gerador, com o item de prova da ligação; o construtor, com a ligação entre as que ele dá como representadas —; só com tenant, nenhum dos dois; nos dois modos, os dois. As linhas novas do CT-14 o conferem.
    - *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* **A mesma regra vale para o mapa de arquitetura e para a ponta.** No mapa de arquitetura compartilhado, a ligação — de registro, de uso ou de qualquer verbo — conta como desenhada só se vale em cada modo do diagrama; e a peça só entra nele se o modelo a dá como registrada em cada modo (o campo `registrada`) e ela fica disponível em cada um — o "registra nesse modo" da definição acima, que a disponibilidade sozinha não cobre. Vale nos mesmos consumidores: o gerador, as guardas do kit — as da suíte do kit e a da `Tenancy` — e o conferidor.
    - **Hoje**, no HEAD `b130c686`, divergem dela, pelo código: o gerador (`site/mapa/gerar.mjs`, `mapaDeArquitetura`), que dá ao diagrama de dois modos o `modo` nulo e não filtra a ligação pelo modo; o construtor das guardas do kit (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasConstruir`, ramo `mapa`), com o mesmo `$modo` nulo na ligação e, na ponta, sem o registro no diagrama de dois modos; o helper da ponta (`tests/Pest.php`, `pecaAbreNosModosDoDiagrama`), que só lê a disponibilidade, e não o `registrada`; a guarda da R34 com tenant (`tests/Tenancy/MapaInterativoTenancyTest.php`, `mapaTenancyLigacoesDesenhadas`), que filtra a ligação de registro pelo modo da suíte, na árvore e no mapa; a guarda da R36 (`tests/Kit/MapaInterativoTest.php`, `mapaModeloArestasDoDiagrama`), pelo primeiro modo na árvore (`modos[0]`) e pelo `$modo` nulo no mapa; e o conferidor (`site/verifica-mapa.mjs`, `esperadoDoMapa`), que espera no mapa compartilhado a ligação que vale em **algum** modo dele (`l.modos.some`). O `entraNosModos` do gerador e o do conferidor conferem o `registrada` em cada modo, e o ramo `registro` do construtor das guardas do kit e o `esperadoDaArvore` do conferidor já seguem a regra da árvore (A2).
    - **Medido no HEAD `b130c686`.** Nos dados publicados a divergência não aparece: nas 281 ligações cujas duas pontas estão num mapa de arquitetura compartilhado, nenhuma vale num modo só; as 84 ligações de registro do `/infra`, cujos 20 diagramas são todos compartilhados, valem nos dois; e as duas peças registradas num modo só no modelo (o `IdentifyTenant` e o `DefinirTenantDePermissoes`, só com tenant) não estão em mapa compartilhado. Pela forma de leitura, numa cópia em memória do modelo com a ligação nova "a notificação `ConviteDeAcesso` usa o model `Convite`" (com a prova da ligação "o model `Convite` usa `ConviteDeAcesso`"), o gerador desenha no `infra-convites`, compartilhado, a seta `ConviteDeAcesso` → `Convite` e o item de prova "ConviteDeAcesso → Convite (usa)", em pt e em en, com a ligação só sem tenant, só com tenant ou nos dois — e também com os modos do arquivo na ordem `["com", "sem"]`. As linhas novas do CT-14 e do CT-38 o conferem.
  - *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* **O diagrama que vale nos dois modos e o rótulo da raiz.** A raiz da árvore de registro — o nó da peça painel do arquivo — diz, no rótulo secundário, o modo em que o diagrama vale: o do campo `modos` do arquivo, qualquer que seja a ordem, pelo termo da grade da página do mesmo idioma; e a árvore que vale nos dois diz "os dois modos" / "both modes", o termo da P-09 e da página ("igual nos dois modos" / "the same in both modes"). É decisão de desenho da sessão, e o oráculo é a P-09: a visualização igual nos dois modos vale para os dois, e não se diz de um modo só.

    | `modos` do arquivo | pt | en |
    |---|---|---|
    | `["sem"]` | sem tenant | tenancy off |
    | `["com"]` | com tenant ativo | tenancy on |
    | `["sem", "com"]`, em qualquer ordem | os dois modos | both modes |

    - O esperado sai do `modos` do arquivo e desta tabela, nunca das constantes do gerador, e a comparação é por igualdade, com o rótulo secundário inteiro, e não por conter o termo. O termo do modo com tenant é o da grade, "com tenant ativo", e não o da marca da peça condicional, "só com tenant ativo": a raiz é a peça painel, sem condição, e não leva marca (R42). Nenhum dos seis termos da tabela é marca para a guarda da R42 (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasTemMarca`; medido no HEAD `1dd48acd` e na árvore de trabalho, a expressão dela não casa nenhum).
    - **Hoje** o gerador (`site/mapa/gerar.mjs`, `arvoreDeRegistro`) diz o primeiro modo do arquivo (`const [modo] = diagrama.modos`), no HEAD `1dd48acd` e na árvore de trabalho. Nenhuma guarda lê o rótulo secundário da raiz: o construtor das guardas do kit (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasConstruir`, ramo `registro`) não o monta, e o conferidor (`site/verifica-mapa.mjs`, `esperadoDaArvore`) confere o rótulo da raiz (o passo 8a do CT-B01), e não o secundário. O vermelho é só do gerador (causa b), e a guarda nasce com as linhas novas do CT-19.
    - **Medido no HEAD `1dd48acd`**, pela entrada que o gerador monta (`entradaDoArchify`, num `node` fora do repositório que importa o gerador de um clone descartável nesse commit, sem baixar o archify nem renderizar; a árvore de trabalho dá o mesmo): as quatro árvores de um modo só dizem o termo da tabela — o `admin-registro-sem` e o `app-registro-sem`, "sem tenant" / "tenancy off"; o `admin-registro-com` e o `app-registro-com`, "com tenant ativo" / "tenancy on" —; a compartilhada, o `infra-registro` (`modos: ["sem", "com"]`), "sem tenant" / "tenancy off"; a cópia em memória dele com `["com", "sem"]`, "com tenant ativo" / "tenancy on"; e a com `["sem"]`, "sem tenant" / "tenancy off". As linhas novas do CT-19 o conferem.
- **De onde cada um lê "fica disponível".** O código PHP vivo só existe na suíte do kit e na `Tenancy`; quem não roda nelas lê a condição do modelo, que o CT-32 e o CT-56 conferem com o código. Por consumidor:
  - o gerador (`site/mapa/gerar.mjs`) e o conferidor (`site/verifica-mapa.mjs`), e por isso o CT-B01 e o CT-B02: a condição do modelo;
  - a forma de leitura da G5 — o CT-46, o CT-47 e o CT-57 —, que monta a entrada pelo gerador: a condição do modelo;
  - o CT-14 e o CT-19, que contam a ponta desenhada como o construtor: a condição do modelo. Hoje o modelo não tem condição, e eles se comportam como antes do Adendo; as linhas novas deles trazem, na cópia, a condição do `TenantResource`;
  - o CT-33: na metade sem tenant, o discriminante vivo da suíte do kit; na metade com tenant, a condição do modelo;
  - o CT-37 e o CT-38: o discriminante vivo, cada um na suíte do seu modo.
  - *(alterado em 2026-11-23 — Adendo 3)* Quem lê o discriminante vivo liga, além de cada booleana de `kit.*` desligada (nunca `kit.tenancy.enabled`, que é o modo *(alterado em 2026-11-23 — Adendo 4)*), a configuração de pacote que a condição do modelo nomeia, no valor que ela dá como ligado. *(alterado em 2026-11-23 — Adendo 4)* E liga juntas todas as chaves que a condição nomeia: o chat do assistente só abre com as duas, e ligá-las uma de cada vez o deixaria fechado em todo modo. O modelo só propõe a configuração: a peça que não abre com ela continua fora, e o modelo que marca peça demais não esvazia diagrama nenhum.
- **O arnês, medido.** A guarda percorre os três painéis no mesmo processo:
  - antes de cada painel, `noPainelDoShield()` (`tests/Pest.php:noPainelDoShield:1131`) e o boot do painel, como em `noPainelBootado()` (`tests/Pest.php:noPainelBootado:543`). Sem o descarte, o mapa do Shield fica no do primeiro painel (`vendor/bezhansalleh/filament-shield/src/FilamentShield.php:getPages:71`, memoizado por `once()`), e o `PermissaoDaTela::permite()` falha fechado nas páginas de pacote dos outros (`app/Support/PermissaoDaTela.php:permite:66`). Medido no HEAD `c642c23c`: 6 peças indisponíveis no `/infra` sem o descarte, e 2 com ele;
  - no `/app` com tenant, `noPainelDa()` (`tests/Pest.php:noPainelDa:521`) com uma organização de `organizacaoComRegistro()` (`tests/Pest.php:organizacaoComRegistro:802`);
  - o super_admin vem de `usuarioDoKit()` (`tests/Pest.php:usuarioDoKit:586`), depois do `PapeisSeeder`;
  - cada chave é ligada por `config()->set()` e desligada de volta no mesmo caso.
- **A população, medida no HEAD `c642c23c`.** Procedimento: o `tinker` com o `<php>` do `phpunit.xml` no ambiente, o cache de config e de rotas fora de alcance (como o `Tests\TestCase`), `KIT_TENANCY` do modo, o SQLite em memória migrado, o `PapeisSeeder` e um `master_global`; por painel, o descarte do Shield e o boot; cada peça indisponível avaliada de novo com cada chave booleana de `kit.*` desligada, uma de cada vez, ligada — fora `kit.tenancy.enabled`, que a sonda não liga *(alterado em 2026-11-23 — Adendo 4)* —. Sem tenant, nenhuma chave booleana de `kit.*` lida difere da de fábrica; com tenant, só `kit.tenancy.enabled`.

  | Painel | Registradas (recurso, página, widget) | Indisponíveis sem tenant | Indisponíveis com tenant | Pelo modo (só com tenant) | Pelo modo e pela chave | Pela chave | Pela configuração de pacote *(alterado em 2026-11-23 — Adendo 3: era "Nenhuma chave `kit.*` liga", Q27; RQ-30)* |
  |---|---|---|---|---|---|---|---|
  | `/admin` | 26 | 9 | 8 | `TenantResource` | — | `AgenteIaResource`, `AgentesIaStats`, `AgentesIaPorProvider` (`KIT_AGENTES_IA`); `CondicaoResource`, `JornadaResource`, `PrimeirosPassos`, `ProgressoOnboarding` (`KIT_ONBOARDING`); `HubDeAdministracao` (`KIT_HUB`) | — |
  | `/app` | 16 | 6 | 3 | `UserResource` e `ConviteResource` do `/app`; `ConvitesRecebidos` | `ProjetoResource` (`KIT_DEMO`) | `HubDoNegocio` (`KIT_HUB`); `PrimeirosPassos` (`KIT_ONBOARDING`) | — |
  | `/infra` | 44 | 2 | 2 | — | — | `PrimeirosPassos` (`KIT_ONBOARDING`) | `CommandRecordResource`: abre com `DatabaseSource` em `command-center.sources`, nos dois modos |

  - São 15 peças distintas (o `PrimeirosPassos` é uma só, nos três painéis): 14 no discriminante e 1 na Q27. Nenhuma peça fica disponível só sem tenant. *(alterado em 2026-11-23 — Adendo 3: as 15 estão no discriminante — a da Q27 pela configuração de pacote —, e a população tem 16 peças, com o `AcessosPorPainel`)* *(alterado em 2026-11-23 — Adendo 4)* Com os dois componentes flutuantes, 18. Medido pelo predicado de cada um, como super_admin, nos três painéis e nos dois modos, sempre igual: o chat fica fechado de fábrica, com `KIT_AGENTES_IA` sozinha e com o painel em `KIT_AGENTES_IA_PAINEIS` sozinho, e abre com as duas juntas; o checklist fica fechado de fábrica e abre com `KIT_ONBOARDING`. Os dois são condicionais só pela chave, sem modo.
  - Fora do registro de painel, entre as 11 peças do modelo que têm `canAccess()` ou `canView()` estático, uma fica indisponível sem tenant: o `AcessosPorPainel` (Q28). *(alterado em 2026-11-23 — Adendo 3: entra, pela RQ-31; condição de modo, só com tenant)*
  - *(alterado em 2026-11-23 — Adendo 3)* As âncoras das duas: `vendor/ssbityukov/filament-command-center/src/Filament/Resources/CommandRecordResource.php:canAccess:46`, com o valor de fábrica em `config/command-center.php:'sources':69`; e `app/Filament/Admin/Resources/Tenants/Widgets/AcessosPorPainel.php:canView:39`. A âncora do `CommandRecordResource` fica em `vendor/`, fora do commit, e não resolve como prova (R9): a âncora da condição é o trecho do kit que a decide, o `'sources'` do `config/command-center.php`.
  - As âncoras do código que decide: `app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`; `app/Filament/App/Resources/Users/UserResource.php:canAccess:98`; `app/Filament/App/Resources/Convites/ConviteResource.php:canAccess:68`; `app/Filament/App/Pages/ConvitesRecebidos.php:regraLocalDeAcesso:75`; `app/Filament/App/Resources/Projetos/ProjetoResource.php:canAccess:88`; `app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:canAccess:70`; `app/Filament/Admin/Widgets/AgentesIaStats.php:fonteDeDadosDisponivel:29`; `app/Filament/Admin/Widgets/AgentesIaPorProvider.php:fonteDeDadosDisponivel:30`; `app/Filament/Admin/Resources/Onboarding/CondicaoResource.php:canAccess:26`; `app/Filament/Admin/Resources/Onboarding/JornadaResource.php:canAccess:26`; `app/Filament/Pages/PrimeirosPassos.php:regraLocalDeAcesso:30`; `app/Filament/Admin/Widgets/ProgressoOnboarding.php:fonteDeDadosDisponivel:40`; `app/Filament/Admin/Pages/HubDeAdministracao.php:regraLocalDeAcesso:77`; `app/Filament/App/Pages/HubDoNegocio.php:regraLocalDeAcesso:73`. Os valores de fábrica: `config/kit.php:'enabled':452` (`KIT_TENANCY`), `config/kit.php:'demo':561`, `config/kit.php:'hub':590`, `config/kit.php:'habilitado':682` (`KIT_AGENTES_IA`) e `config/kit.php:'habilitado':711` (`KIT_ONBOARDING`), todos `false`.
  - O modelo de hoje não guarda condição em peça nenhuma: o campo que a guarda da R37 lê não existe em nenhuma das 284 peças.
- **O que o mapa desenha hoje, medido no HEAD `c642c23c`.** As 14 peças do discriminante aparecem em 70 lugares dos arquivos de diagrama: 20 nós de mapa de arquitetura, 22 nós de árvore de registro e 28 membros de agrupamento, nenhum com marca. Em 15 deles a peça só abre com tenant e o diagrama vale sem tenant: o `TenantResource` no `admin-multi-tenancy-opt-in`, no `admin-registro` e no `admin-resumo`, os três compartilhados; e o `UserResource` e o `ConviteResource` do `/app`, o `ConvitesRecebidos` e o `ProjetoResource`, cada um num diagrama compartilhado (`app-acesso-e-autenticacao`, `app-convites`, `app-produtividade-e-ui`), no `app-registro-sem` e no `app-resumo-sem`. Os 13 diagramas do `/admin` valem nos dois modos. *(alterado em 2026-11-23 — Adendo 3: com o `CommandRecordResource` — nó do `infra-infraestrutura-2`, nó do `infra-registro` e membro no `infra-resumo` — e o `AcessosPorPainel` — nó do `kit-multi-tenancy-opt-in` e membro no `kit-resumo-sem` e no `kit-resumo-com` —, as 16 peças da população aparecem em 76 lugares: 22 nós de mapa, 23 nós de árvore e 31 membros, nenhum com marca. Os lugares em que uma peça que só abre com tenant está em diagrama que vale sem tenant passam a 17: o `AcessosPorPainel` no `kit-multi-tenancy-opt-in`, compartilhado, e no `kit-resumo-sem`)* *(alterado em 2026-11-23 — Adendo 4)* Com os dois componentes flutuantes, as 18 peças aparecem em 85 lugares de 28 arquivos de diagrama — 25 nós de mapa, 23 nós de árvore, 35 membros e 2 nós de diagrama escrito à mão —, nenhum com marca. O chat é nó do `kit-ia`, membro no `kit-resumo-sem` e no `kit-resumo-com`, participante do `app-chat-de-ia` (sequence) e nó do `ia-do-prompt-ao-ledger` (dataflow), os dois escritos à mão; o checklist é nó do `kit-produtividade-e-ui-sem` e do `-com` e membro nos dois resumos do kit. Os dois são condicionais só pela chave, e por isso o lugar deles não muda.

### Base congelada (commit `d9608c5a`, medida nesta derivação)
- **Catálogo:** 74 F-nn por idioma (F-01 a F-71, mais F-03a, F-03b e F-03c), e 71 é o maior inteiro. A guarda congela o par F-nn → termo-chave da feature, por exemplo F-05 → "Passkeys", F-10 → "403", F-11 → "master_global" e F-15 → "Convite individual". Nenhuma F-nn está marcada como removida na base.
- **README:** 46 itens por idioma na lista "O que já vem pronto" / "What's already there", até o `### A densidade…`. A guarda congela cada item com a F-nn que o cobre (`@premissa`, Q14c). A base do catálogo está defasada em relação a essa lista: o dock de atalhos, o tutorial guiado, as visões de tabela, a página única de login, a proteção anti-robô e o "+" no e-mail não têm F-nn nela. O termo que a linha casada de cada um traz está na R5 (ADV-01).
- **"Onde fica" do catálogo:** das 74 linhas por idioma, 40 trazem na célula um caminho entre crases que começa por `/`, sem "→"; 4 têm caminho e "→"; e 30 não têm caminho (menu, ação, "os três", "—"). A conta é a mesma em pt e em en. O oráculo da rota (R6) vale nas 40.
- **Classes de `app/` fora de `app/Filament` (P-12):** 135 arquivos PHP, que declaram 123 classes, 7 enums e 5 traits. A P-12 mediu que 103 já eram peça na varredura do step 3.
- **Repositório do kit:** o `support.source` do `composer.json`, `https://github.com/gsferro/filament-starter-kit-easy`. É o repositório que os links de evidência têm de levar (R14).
- **Pacotes instalados (Q16):** instalado é o que os manifestos declaram, o `composer.json` (`require` e `require-dev`) e o `package.json` da aplicação (`dependencies`, `devDependencies` e `optionalDependencies`). O `php` é plataforma e fica fora da conta.
  - Na base são 91: 61 no `require` (fora o `php`), 23 no `require-dev`, nenhum nas `dependencies`, 6 nas `devDependencies` e 1 nas `optionalDependencies`, o `@laravel/multiplex`.
- **Referência de pacotes instalados:** 87 pacotes por idioma, os mesmos em pt e em en, nas seções fora de "Avaliados e não adotados". A seção de front-end vem **depois** da dos avaliados.
  - O nome do pacote vem do endereço do link: o texto `pest-plugin-laravel` leva a `pestphp/pest-plugin-laravel`.
  - Todos os 87 estão instalados.
  - **Fato da base que a entrega corrige (Q16):** quatro pacotes instalados ficam fora da referência nos dois idiomas: `hoceineel/filament-quick-action-dock` (o do dock de atalhos), `brianium/paratest`, `icanhazstring/composer-unused` e `maglnet/composer-require-checker`. Por isso o CT-28 reprova a base. Ele passa quando a referência listar os quatro, cada um numa F-nn de origem pacote ou na lista dos sem feature própria, com o motivo.
- **Inventário Mermaid:** DG-01 a DG-20 por idioma nos arquivos de `docs/`, mais o DG-01 no README de cada idioma, com o arquivo de cada bloco congelado. A lista vem de `blocosMermaidDaArvore()` (`tests/Pest.php:blocosMermaidDaArvore:1482`) rodado na base.
- **`site/package.json`:** as `dependencies` são `@astrojs/starlight`, `astro`, `astro-mermaid`, `mermaid` e `sharp`; as `devDependencies` são `axe-core`, `playwright` e `wait-on`.
- **CHANGELOG:** 141 cabeçalhos `## [x.y.z] - AAAA-MM-DD` e o `## [Unreleased]`, sem data. Exemplo: `## [0.56.0] - 2026-11-19`. Só cinco versões citam F-nn, e por isso o marco da linha do tempo casa por termo, e não por F-nn (R29).
- **Lido da árvore atual, não congelado:** as páginas do site (39 por idioma na base), os diagramas e o modelo.

### Os tipos de visualização e os tipos de peça
- **Os dez tipos nativos (archify 3.2.0, commit `4478129…`):** `diagram_type` ∈ {`architecture`, `workflow`, `sequence`, `dataflow`, `lifecycle`, `erd`, `class`, `tree`, `timeline`, `waterfall`}, conforme o `schemas/README.md` do archify nesse commit.
- **O comparativo de arquitetura:** soma-se aos dez, mas sai de `compare architecture <antes> <depois>` e não é um `diagram_type`.
- **Os de fluxo, para a R22 e a R35:** `workflow`, `sequence` e `dataflow`, cujo passo nomeia peça; e `lifecycle`, cujo estado e cuja transição têm âncora no código *(alterado em 2026-11-20: ADV-11)*.
- **As formas de aresta nos mapas de arquitetura** (medidas pela sessão): direta, de agrupamento e de porta. A R12 e a R36 as definem.
- **Os tipos de peça (RQ-24 e glossário), 13 ao todo:** painel, recurso, página, widget, plugin de pacote, provider, middleware, serviço próprio e model, e também policy, setting, comando e componente Livewire quando a ligação passa por eles. O tipo declarado confere com a classe pela tabela da R8 (ADV-16).

### Guarda e controles
- Toda guarda é uma função aplicada ao modelo publicado e a cópias adulteradas **em memória**, nunca ao arquivo real. Ela devolve "aceita" ou "reprova", com um motivo que nomeia o item. O molde é `conferirDg10()` (`tests/Kit/GuardasDosDiagramasTest.php:conferirDg10:117`).
- A guarda do catálogo recebe a base como parâmetro. Assim o controle monta uma base em que um número já está removido, sem tocar na base congelada.
- O piso de não vácuo vem de uma fonte que o modelo não controla: o registro do painel, a árvore de `docs/`, os manifestos de pacotes ou o catálogo da base. Assim um modelo vazio não passa em "toda peça…".
- Trecho fora de comentário, por tipo de arquivo *(alterado em 2026-11-20: ADV2-06)*: em PHP, `codigoSemComentario()` (`tests/Pest.php:codigoSemComentario:2192`); em Blade, fora de `{{-- --}}` e de `<!-- -->`; em JS e stub, fora de `//` e de `/* */`. *(alterado em 2026-11-21 — step 9, achado A7)* Em YAML e `.env`, fora de `#` até o fim da linha, e em Markdown, fora de `<!-- -->`, decidido pela sessão (Q20); JSON não tem comentário.
- "No commit que publica" quer dizer que o arquivo está no índice do git. Invocar o `git` exige a sentinela (`[CT-26]` de `tests/Kit/RedeDeDocumentacaoTest.php`).
- Arquivos que existem no disco e estão fora do commit:
  - `bootstrap/cache/packages.php`, que o Composer gera e o `bootstrap/cache/.gitignore` ignora;
  - todo arquivo de `vendor/`.
- Quarto painel: `painelRegistradoEmTeste('financeiro')` (`tests/Pest.php:painelRegistradoEmTeste:816`). O registro morre com o container do caso.
- A guarda das classes de `app/` recebe a lista de classes como parâmetro. Assim o CT-41 acrescenta uma classe que não existe, sem tocar na árvore.
- *(alterado em 2026-11-20: segunda revisão adversarial)* Listas fechadas e critérios que as guardas compartilham, cada um conferido na regra dele: os métodos de contrato do alvo (R10, ADV2-08); os comandos `kit:*`, que nunca entram na lista das sem feature (R28, ADV2-18); os 39 termos do glossário e as palavras funcionais do pt na tradução (R16, ADV2-11 e ADV2-28); o título do item do README (R5, ADV2-03); a normalização dos caminhos do "onde fica" (R6, ADV2-04); os arquivos de `site/mapa/` (R21, D32).
- **Motivo de enfeite** *(alterado em 2026-11-20: ADV-22)*: o motivo vazio, só de travessão ou pontuação, ou "não se aplica" sem dizer por quê. A definição é uma só, nos CT-03, CT-21, CT-28, CT-29, CT-35 e CT-41, e no CT-B07 do `05`.
  - *(alterado em 2026-11-21 — step 9, achado A13)* **Uma tabela só, nos dois lados.** "Uma só" quer dizer que a guarda do kit (`tests/Pest.php:motivoDeEnfeite:4221`) e o conferidor do mapa (`site/verifica-mapa.mjs:motivoDeEnfeite:524`) decidem igual esta tabela, e o CT-35 (o lado PHP) e o CT-B07 (o lado JS) a citam linha a linha. É enfeite: vazio; ausente ou nulo; só travessão, pontuação ou símbolo ("—", "- ", "..."); "não se aplica", "nao se aplica", "not applicable", "does not apply" ou "N/A", em qualquer caixa, com ou sem pontuação, sem dizer por quê; e o valor que não é texto (o número 0). É motivo: o texto que diz por quê, mesmo começando por "não se aplica:" ("não se aplica: a classe é abstrata e vazia").
  - *(alterado em 2026-11-21 — step 9, correção devolvida pelos executores do CT-35 e do CT-B07: o texto dizia 17 entradas e citava 16)* A tabela, entrada a entrada:

    | Entrada | Decisão |
    |---|---|
    | `""` (vazio) | enfeite |
    | ausente (sem a chave; no PHP, o mesmo que nulo) | enfeite |
    | `null` | enfeite |
    | `"—"` | enfeite |
    | `"- "` | enfeite |
    | `"..."` | enfeite |
    | `"não se aplica"` | enfeite |
    | `"nao se aplica"` | enfeite |
    | `"not applicable"` | enfeite |
    | `"Not applicable."` | enfeite |
    | `"does not apply"` | enfeite |
    | `"Does not apply"` | enfeite |
    | `"N/A"` | enfeite |
    | `"n/a"` | enfeite |
    | `0` (não é texto) | enfeite |
    | `"não se aplica: a classe é abstrata e vazia"` | motivo |

  - Medido no commit `168d1827`, com as 16 entradas da tabela: a guarda do kit decide todas como acima; o conferidor do mapa diverge em 7 — "N/A", "n/a", "not applicable", "Not applicable.", "does not apply", "Does not apply" e o número 0, que ele aceita como motivo. As linhas novas do CT-B07 ficam vermelhas até o conferidor seguir a tabela.
- A detecção da peça condicional (o `master_global`, o `canAccess()` e o `canView()`) saiu com a R30, estacionada na Q11 *(alterado em 2026-11-20: ADV-31)*. *(alterado em 2026-11-23 — Adendo 2: voltou com a R30, e o como está em `### A peça condicional e o discriminante`. A guarda da condição é uma função sobre o modelo publicado e sobre cópias em memória, como as outras; o que ela avalia ao vivo é a disponibilidade de cada peça, e a cópia nunca a altera)*
- *(alterado em 2026-11-21 — step 9)* **As guardas da G5** (o gerador e as ferramentas de autor como processo filho) seguem a mesma regra: a guarda é uma função sobre o que o processo devolve, e o controle a chama sobre uma entrada adulterada em memória (leitura) ou num clone descartável (efeito). A forma de cada uma está na linha G5 de `## Costuras de Teste`. Nenhum caso da G5 escreve em `site/` da árvore do kit, e todo caso de efeito afirma o não-efeito pelos bytes: o arquivo do autor, o arquivo do diagrama e a ausência de `site/.archify` no clone, que o download criaria. Conferido no commit `168d1827`: a forma de leitura monta a entrada dos 87 diagramas em 4 s, sem baixar nem renderizar; no clone `--shared` com `GIT_ALLOW_PROTOCOL=file`, o `git show` funciona, o `fetch` do archify por https sai com "fatal: transport 'https' not allowed", e a tentativa deixa o `site/.archify` criado.

### Helpers
- O helper usado pelos dois arquivos mora em `tests/Pest.php` (`.ai/rules/testes.md`, com a prova em `tests/Kit/HelpersDeTesteTest.php`). É o caso de ler o modelo, de montar o conjunto de registro de um painel num modo e de achar a F-nn de uma peça.
- O helper de um arquivo só fica nele.
- Reusar estes, sem clone com outro nome: `naArvoreDoKit()`, `paginasDoSite()`, `blocosMermaidDaArvore()`, `codigoSemComentario()` e `painelRegistradoEmTeste()`. O `usuarioDoKit()` saiu da lista com o CT-32, estacionado. *(alterado em 2026-11-23 — Adendo 2: voltam, com os casos que avaliam o discriminante vivo — o CT-32, o CT-33 e o CT-37, na suíte do kit; o CT-56 e o CT-38, na `Tenancy` —, o `usuarioDoKit()`, o `registroVivoDoPainel()`, o `noPainelDoShield()`, o `noPainelBootado()`, o `noPainelDa()` e o `organizacaoComRegistro()`. O discriminante é usado pelos dois arquivos, e por isso mora no `tests/Pest.php`)*

### Guardas herdadas que alcançam a entrega (não re-derivadas)
- **`tests/Kit/SiteDeDocumentacaoTest.php`:**
  - `[CT-04]` e `[CT-34]`: espelho pt/en.
  - `[CT-20]` e `[CT-23]`: navegação.
  - `[CT-36]` e `[CT-38]`: redirecionamento e stub.
  - `[CT-41]`: ordem.
  - `[CT-29]`, `[CT-30]` e `[CT-44]`: formato da página.
  - `[CT-14]`: o link do README resolve.
  - `[CT-12]`: as dependências do `package.json` da raiz congeladas.
  - `[CT-22]` e `[CT-45]`: links internos. Só leem link Markdown. Como a página do mapa usa `<a href>` relativo cru (Q15), eles não mudam.
- **`tests/Kit/DiagramasDaArquiteturaTest.php`:** `[CT-01]`, o DG onde o catálogo daquela wiki diz. Ele também mata o M1 da R26.
- **`site/verifica-links.mjs`:** confere todo `href` de todo `.html` do `dist/`, inclusive os `<a href>` da página para o mapa.

### Fakes
- Nenhum. A entrega não tem efeito colateral (e-mail, fila, evento, HTTP).

### Estratégia de DB
- O `RefreshDatabase` do `tests/Pest.php`, nas suítes Kit e Tenancy. Nenhum caso grava nem semeia: o único que semeava, o CT-32, está estacionado na Q11. O registro dos painéis e o roteador não dependem do banco. *(alterado em 2026-11-23 — Adendo 2: os casos que avaliam o discriminante vivo semeiam o `PapeisSeeder` e criam o super_admin — na suíte do kit, o CT-32, o CT-33 e o CT-37; na `Tenancy`, o CT-56 e o CT-38, que criam também a organização que o `/app` abre. Sem o super_admin, quase toda peça dá indisponível, e a subtração do CT-37 e do CT-38 esvazia o conjunto. Medido: com o banco migrado, as tabelas que os widgets consultam existem, e nenhum widget fica indisponível por tabela que falta)*

---

## Regra R1 — O catálogo é um só, com as mesmas F-nn em pt e em en, a mesma feature e o mesmo estado em cada número, uma linha por F-nn e uma origem do glossário em cada

> `RQ-21`, `RQ-22`, `RQ-04`, `RQ-05`, `P-02` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia

```gherkin
# language: pt
Funcionalidade: Mapa interativo do kit com a skill archify

  Regra: O catálogo de features é um só, em paridade pt e en, e cada F-nn declara uma origem do glossário

    Esquema do Cenário: [CT-01] o catálogo tem as mesmas F-nn nos dois idiomas, a mesma feature em cada número, uma linha por F-nn e uma origem em cada
      Dado o catálogo de features em pt e em en "<mundo>"
      Quando a guarda do mapa confere o catálogo
      Então o resultado é "<resultado>"
      E a guarda conferiu, em cada idioma, ao menos as 74 F-nn da base

      Exemplos:
        | mundo                                                                                   | resultado                                | # partição                   |
        | publicado                                                                               | aceita                                   | válida                       |
        | cópia em que o en não tem a F-69, que o pt tem                                          | reprova, nomeando a F-69 e o idioma en   | paridade quebrada            |
        | cópia em que a F-72 do pt leva à página do dock de atalhos, e a do en, à do tutorial guiado | reprova, nomeando a F-72             | número com outra feature     |
        | cópia em que a F-30 declara a origem "biblioteca"                                       | reprova, nomeando a F-30 e a origem      | origem fora do conjunto      |
        | cópia em que a F-30 não declara origem                                                  | reprova, nomeando a F-30                 | origem ausente               |
        | cópia em que a F-30 está em duas linhas do pt                                           | reprova, nomeando a F-30                 | número repetido              |
        | cópia em que a F-118 e a F-121, novas, de código próprio e sem página nem rota, trocam de descrição entre pt e en | reprova, nomeando a F-118 e a F-121 | número novo com outra feature |
        | cópia com a F-05 marcada como removida, com o motivo, no pt e vigente no en | reprova, nomeando a F-05 e o idioma en | estado diferente entre os idiomas |
```

As origens válidas são as três do glossário (Catálogo de features): pacote, código próprio e pacote com customização própria. A linha "publicado" afirma que cada F-nn declara **exatamente uma** delas.

*(alterado em 2026-11-20: ADV-09, ADV2-01 e ADV2-02)* A mesma F-nn é a mesma feature, no mesmo estado, nos dois idiomas:
- **Nas linhas da base** (F-01 a F-71, F-03a, F-03b e F-03c): as duas linhas declaram a mesma origem, nomeiam o mesmo pacote, levam à mesma página (a do outro idioma, no espelho de `docs/`) e dão os mesmos caminhos de rota no "onde fica", com `{painel}` e `{panel}` tidos como o mesmo curinga.
- **Nas linhas novas** (F-72 em diante), que a entrega escreve: além disso, o conjunto de tokens entre crases da linha pt é o da linha en. Assim a F-nn nova sem página, pacote ou rota, como a de um comando `kit:*`, também fica amarrada pelo que só ela tem. A linha nova sem token em nenhum dos dois idiomas não tem essa âncora, e a troca entre duas linhas assim é lacuna declarada (R1-M8). *(alterado em 2026-11-20: "e não é vazio" saiu, medido no catálogo da entrega)*
- **O estado:** a F-nn removida está marcada como removida no pt e no en.

Medido na base: das 74 linhas, 4 têm tokens diferentes entre pt e en — F-02 e F-04 (`{painel}`/`{panel}`), F-07 (`/{painel}/meu-perfil`/`/{panel}/my-profile`, e o en está errado) e F-51 (`homologação`/`staging`, palavra traduzida) — e 10 não têm token nenhum. Por isso a identidade por tokens vale só para as linhas novas; nas da base fica a identidade acima, que reprova a F-07 en até a entrega a corrigir (R6, ADV2-04).

Medido no catálogo da entrega (commit `0f38e2f`, F-72 a F-121): as 50 linhas novas têm os mesmos tokens nos dois idiomas; só a F-118 ("Voltar ao topo") não tem token nenhum, e exigir token reprovaria dado correto. As linhas novas de código próprio sem página nem rota são duas, a F-118 e a F-121 ("Senha forte e comandos destrutivos bloqueados em produção", com `php artisan`, `APP_ENV=production` e `migrate:fresh`), e a linha de controle da troca usa as duas. As linhas da F-72, da F-118, da F-121 e da F-05 são de controle, em cópia: no commit, a F-72 é o login social por painel, com página, e a F-05, as passkeys, desligadas no kit.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação é feita só no pt, e o en fica sem F-nn que o pt tem | CT-01 | linha "en sem a F-69": reprova nomeando F-69 e en; o mutante aceita |
| M2 | origem como texto livre ("biblioteca", "pacote + código") | CT-01 | linha "origem biblioteca": reprova nomeando a F-30; o mutante aceita |
| M3 | a coluna de origem é opcional, e a F-nn sem origem passa | CT-01 | linha "não declara origem": reprova; o mutante aceita |
| M4 | duas linhas com o mesmo número (a feature nova copiada de uma linha antiga sem trocar o número) | CT-01 | linha "F-30 em duas linhas": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-09) | com pt e en editados em paralelo, os números novos saem em ordem diferente: a F-72 do pt é o dock, e a do en, o tutorial | CT-01 | linha da F-72: reprova; o mutante aceita, porque confere só o conjunto de números |
| M6 — revisão adversarial (ADV2-01) | duas F-nn novas sem página, pacote nem rota trocam de feature entre pt e en, e os atributos de identidade vazios e iguais deixam passar | CT-01 | linha da F-118 e da F-121: os tokens entre crases diferem (nenhum na F-118, três na F-121), e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-02) | a F-nn é marcada como removida num idioma só | CT-01 | linha da F-05 removida no pt e vigente no en: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-01) | duas F-nn novas sem token nenhum, sem página e sem rota trocam de descrição entre pt e en | ⚠️ **sem matador**: no catálogo da entrega (commit `0f38e2f`), a F-118 é a única linha nova sem token, e exigir token reprovaria dado correto; sem token, página nem rota, não há âncora mecânica da identidade entre os idiomas. Fica para o quality gate | — (lacuna declarada) |

---

## Regra R2 — README, páginas do site e mapa só citam F-nn que o catálogo tem, e nenhum deles lista feature por fora dele

> `RQ-22`, `RQ-21`, `P-08` · perfil **padrão** · técnica: **EP** + controles em cópia

```gherkin
  Regra: Nenhum segundo catálogo nasce: quem cita feature cita a F-nn do catálogo do mesmo idioma

    Esquema do Cenário: [CT-02] o README, as páginas do site e o mapa só citam F-nn que o catálogo tem, e nenhum deles traz lista ou tabela de features
      Dado o README, as páginas de `docs/` fora do catálogo e os arquivos do mapa "<mundo>"
      Quando a guarda do mapa confere cada F-nn citada neles contra o catálogo do mesmo idioma
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu ao menos a F-03 e a F-04, que `docs/` já cita na base, e a F-nn de cada feature que o mapa cobre

      Exemplos:
        | mundo                                                                                       | resultado                                   |
        | publicados                                                                                  | aceita                                      |
        | cópia de uma página de `docs/pt/` que cita a F-199                                          | reprova, nomeando a página e a F-199        |
        | cópia do modelo em que uma peça cita a sua feature pelo nome, sem F-nn                      | reprova, nomeando a peça                    |
        | cópia do `README.en.md` que cita uma F-nn que só o catálogo pt tem                          | reprova, nomeando a F-nn e o `README.en.md` |
        | cópia da página do mapa com uma tabela de três F-nn                                         | reprova, nomeando a página                  |
        | cópia de um arquivo do mapa com uma lista de features, cada uma com a F-nn e o nome          | reprova, nomeando o arquivo                 |
        | cópia da página do mapa com uma lista só de nomes, "Passkeys", "Impersonate" e "Lixeira", sem número | reprova, nomeando a página |
        | cópia de um arquivo do mapa com um card de três itens que são nomes de F-nn do catálogo | reprova, nomeando o arquivo |
```

A varredura cobre o README, `docs/` e `site/mapa/`. Ficam de fora `tests/` e `app/`, onde "F-nn" também nomeia achado de revisão, como "F-01 da conformidade Blueprint" (`.ai/rules/kit.md`). Ali a regex daria falso positivo.

*(alterado em 2026-11-20: ADV-20)* "Lista ou tabela de features" tem dois sinais:
- **Nas páginas fora do catálogo:** linha de tabela ou item de lista que cita F-nn. Na base não há nenhuma, e as duas citações de `docs/`, em `autenticacao/login-social.md`, estão em prosa.
- **Nos arquivos do mapa:** a F-nn junto do nome que o catálogo dá a ela. A peça aponta a F-nn só pelo número.

*(alterado em 2026-11-20: ADV2-21)* A lista de features também pode vir só com os nomes, sem número. O terceiro sinal é este: um item de lista, uma linha de tabela ou um item de card que é o nome que o catálogo dá a uma F-nn (o texto da coluna "Feature", sem a marcação), com ou sem o número. Três ou mais itens assim na mesma lista, tabela ou card da página do mapa e dos arquivos do mapa reprovam. Ficam fora da conta:
- o nó de diagrama, porque nomeia a peça ou o marco. Medido no esqueleto: o `infra-registro` (tree) tem 3 nós cujo rótulo é nome de F-nn — Execuções de IA, Grafo de dependências e Lixeira —, e são peças;
- o item que é link para um diagrama do mapa, que é navegação.

Medido: nos cards dos 13 arquivos do esqueleto, nenhum item é nome de F-nn. A página do mapa ainda não existe; pelo passo 6 do `01`, as tabelas e listas dela são a grade painel × modo, com `<a href>` em cada célula, os fluxos, a matriz de tipos com o motivo de cada célula vazia e os limites, e nenhuma é lista de nomes de feature.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* A F-nn que o catálogo não tem é a F-199: a F-99 dos exemplos derivados virou feature (UI 100% em pt-BR) na unificação. A guarda confere antes que o catálogo não tem o número da cópia.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o mapa guarda uma lista própria de features, pelo nome, ao lado do catálogo *(alterado em 2026-11-20: ADV-20, a asserção antiga não divergia)* | CT-02 | linhas "tabela de três F-nn na página do mapa" e "lista de features num arquivo do mapa": reprova nomeando a página ou o arquivo; o mutante aceita |
| M2 | uma página do site cita número que o catálogo não tem (inventado, ou de antes da unificação) | CT-02 | linha "F-199": reprova nomeando a página; o mutante aceita |
| M3 | o README en cita F-nn que só o catálogo pt ganhou | CT-02 | linha `README.en.md`: reprova; o mutante aceita |
| M4 | a peça aponta a feature pelo nome, e não pela F-nn | CT-02 | linha "peça cita a feature pelo nome": reprova nomeando a peça; o mutante aceita |
| M5 — revisão adversarial (ADV2-21) | a lista própria de features vem só com os nomes, sem número, e não acende nenhum dos dois sinais | CT-02 | linhas da lista de três nomes na página do mapa e do card de três nomes: reprova; o mutante aceita |

---

## Regra R3 — Toda F-nn da base continua com a sua feature, ou fica marcada como removida com o motivo, e a removida não volta

> `P-08`, `RQ-22` · perfil **padrão** · técnica: **estado × operação** (matriz abaixo) + controles em cópia

Matriz do número do catálogo. São 3 estados (da base; removida com registro; nova) × 5 operações (manter; dar o número a outra feature; remover sem registro; remover com registro; acrescentar) = **15 células**. Delas, 9 têm cenário: 4 válidas, 4 inválidas e a célula mista "nova × acrescentar", que tem a borda válida e as inválidas. As outras 6 não se aplicam, com motivo.

O estado "removida" não existe na base, porque nenhum número está removido nela. As células dele rodam com uma base de controle em que a F-05 já está marcada como removida (linhas com a base "com a F-05 removida").

| Estado \ operação | manter | dar o número a outra feature | remover sem registro | remover com registro | acrescentar |
|---|---|---|---|---|---|
| da base | ✅ CT-03 (publicado) | ❌ CT-03 (F-10 e F-11 trocadas) | ❌ CT-03 (sem a F-05; removida sem motivo ou com motivo de enfeite) | ✅ CT-03 (F-05 removida com motivo) | não se aplica: acrescentar cria número novo |
| removida com registro | ✅ CT-03 (base com a F-05 removida: o catálogo mantém a marca) | ❌ CT-03 (base com a F-05 removida: o número é dado a outra feature) | ❌ CT-03 (base com a F-05 removida: a linha dela some) | não se aplica: já está removida | não se aplica: acrescentar cria número novo |
| nova | ✅ CT-04 (publicado) | não se aplica nesta entrega: a nova nasce agora, e na entrega seguinte ela é base | não se aplica nesta entrega (idem) | não se aplica nesta entrega (idem) | ✅ CT-04 (F-72) / ❌ CT-04 (F-73, buraco, F-03d) |

Legenda: ❌ = a guarda reprova, nomeando o número. Não há efeito colateral a afirmar, porque a operação é edição de arquivo e a guarda só lê.

```gherkin
  Regra: O número da base não troca de feature, não some sem registro, e o removido não volta

    @premissa
    Esquema do Cenário: [CT-03] o número da base fica com a sua feature, ou fica marcado como removido com o motivo, e não volta
      Dado a base "<base>", cada F-nn com o termo que identifica a sua feature
      E o catálogo "<mundo>"
      Quando a guarda do mapa confere o catálogo contra a base
      Então o resultado é "<resultado>"

      Exemplos:
        | base                              | mundo                                                       | resultado                          | # célula                                 |
        | `d9608c5a`                        | publicado, pt e en                                          | aceita                             | da base × manter                         |
        | `d9608c5a`                        | cópia com a F-10 e a F-11 trocadas de feature               | reprova, nomeando a F-10 e a F-11  | da base × dar o número a outra feature   |
        | `d9608c5a`                        | cópia sem a linha da F-05                                   | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo        | aceita                             | da base × remover com registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, sem o motivo        | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo "—"    | reprova, nomeando a F-05           | da base × remover sem registro           |
        | `d9608c5a`                        | cópia com a F-05 marcada como removida, com o motivo "não se aplica", sem porquê | reprova, nomeando a F-05 | da base × remover sem registro  |
        | `d9608c5a` com a F-05 removida    | cópia que mantém a F-05 marcada como removida, com o motivo | aceita                             | removida × manter                        |
        | `d9608c5a` com a F-05 removida    | cópia em que a F-05 descreve outra feature                  | reprova, nomeando a F-05           | removida × dar o número a outra feature  |
        | `d9608c5a` com a F-05 removida    | cópia sem a linha da F-05                                   | reprova, nomeando a F-05           | removida × remover sem registro          |
```

`@premissa` de mecanismo, decidida pela sessão (Q14a): a removida fica na tabela do catálogo, marcada e com o motivo. A F-05 ("Passkeys", que o catálogo dá como desligada) é o exemplo discriminante porque é a candidata natural a sair na unificação.

*(alterado em 2026-11-20: ADV-02 e ADV-22)*
- O "aceita" da remoção com motivo é veredito só do catálogo. A F-nn removida que uma peça do modelo ainda entrega reprova no CT-36 (R6), que lê o modelo.
- "Motivo de enfeite" tem a definição do Setup Global, a mesma do CT-21.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação renumera por ordem de seção (as F-62 a F-68 de Autorização passam a vir logo depois da F-14) | CT-03 | linha "publicado": a F-15 da base deixa de ter o termo "Convite individual" e a guarda reprova; o mutante aceita |
| M2 | a guarda confere só que o número existe, e não a feature dele | CT-03 | linha "F-10 e F-11 trocadas": reprova; o mutante aceita |
| M3 | um número some sem registro: a F-05 apagada na unificação, ou a linha de uma removida apagada depois | CT-03 | as duas linhas "sem a linha da F-05": reprova; o mutante aceita |
| M4 | número removido é reaproveitado para uma feature nova | CT-03 | linha "base com a F-05 removida", "a F-05 descreve outra feature": reprova; o mutante aceita |
| M5 | a marca de removida vem sem o motivo | CT-03 | linha "removida sem o motivo": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-22) | a marca de removida vem com motivo de enfeite ("—", ou "não se aplica" sem porquê) | CT-03 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |

---

## Regra R4 — A feature nova recebe o próximo número livre: a F-72, e as seguintes em sequência, sem buraco e sem sufixo novo

> `P-08` · perfil **padrão** · técnica: **BVA 3-valores** (escalada; fronteira: o maior inteiro da base, 71; incremento: 1 número)

A borda−1 (71) é número da base. Dar a F-71 a uma feature nova troca a feature dela, e isso morre no CT-03. O CT-04 cobre a borda (72), a borda+1 (73), o buraco e o sufixo de letra. O sufixo foi fechado pelo `00` (Q13): a P-08 dá à feature nova "o próximo número livre", e depois da F-71 o próximo é a F-72. Um sufixo não é o próximo número.

```gherkin
  Regra: O primeiro número novo é a F-72, os seguintes vêm em sequência, e nenhum número novo tem sufixo de letra

    Esquema do Cenário: [CT-04] os números que a base não tem começam na F-72, seguem em sequência e não têm sufixo
      Dado o maior inteiro da base, 71
      E o catálogo "<mundo>"
      Quando a guarda do mapa confere os números que a base não tem
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                    | resultado                                  | # borda          |
        | publicado, pt e en                       | aceita                                     | sequência real   |
        | cópia com um único número novo, a F-72   | aceita                                     | borda            |
        | cópia com um único número novo, a F-73   | reprova, nomeando a F-73 (pula a F-72)     | borda+1          |
        | cópia com os números novos F-72 e F-74   | reprova, nomeando a F-74 (pula a F-73)     | buraco           |
        | cópia com um único número novo, a F-03d  | reprova, nomeando a F-03d                  | sufixo novo      |
```

A linha "publicado" não é vazia: a base está defasada em relação ao README (ver Setup Global), então a unificação acrescenta F-nn.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | os novos começam depois de um salto (F-73, ou F-100 "para separar as novas") | CT-04 | linha "F-73": reprova; o mutante aceita |
| M2 | a nova ocupa um número qualquer, sem sequência | CT-04 | linha "F-72 e F-74": reprova nomeando a F-74; o mutante aceita |
| M3 | a variante de uma feature existente ganha sufixo de letra (o "+" no e-mail como F-03d, ao lado de F-03a, F-03b e F-03c) | CT-04 | linha "F-03d": reprova; o mutante aceita |

---

## Regra R5 — Todo item de feature do README da base e toda página do site correspondem a ao menos uma F-nn — os quinze itens que a base não cobre, pelo termo fixado —, ou a página está na lista das que não descrevem feature, com o motivo

> `RQ-19`, `RQ-04`, `RQ-05`, `P-02` · perfil **padrão** · técnica: **EP por fonte** + controles em cópia

A Q7 foi fechada pelo `00`: a RQ-19 diz "páginas do site" sem recorte. Então todas as páginas de `docs/{pt,en}` contam. As de `recursos/` e de `autenticacao/`, fora os índices, nunca vão para a lista das que não descrevem feature, porque cada uma descreve uma. Esse é o invariante que já valia antes da resposta. Os índices de seção, como `recursos/index.md`, são justamente páginas que não descrevem feature.

```gherkin
  Regra: Toda fonte da RQ-19 chega ao catálogo: o README da base e todas as páginas do site

    @premissa
    Esquema do Cenário: [CT-05] todo item de feature do README da base e toda página do site chegam ao catálogo, ou a página está na lista das que não descrevem feature
      Dado a fonte "<fonte>" em "<idioma>", com <itens> itens
      Quando a guarda do mapa casa cada item com o catálogo do mesmo idioma, por "<correspondência>"
      Então cada item corresponde a ao menos uma F-nn que existe nesse catálogo — ou, se é página que não descreve feature, está na lista da guarda com o motivo
      E nenhum item fica sem uma das duas coisas, e nenhuma página de `recursos/` ou de `autenticacao/`, fora os índices, está nessa lista
      E <casamento>
      E a guarda conferiu exatamente <itens> itens

      Exemplos:
        | fonte                                  | idioma | itens                       | correspondência                                                    | casamento                                                                                                            |
        | lista de features do README da base    | pt     | 46                          | a tabela da base congelada na guarda                               | cada um dos quinze itens que a base não cobre casa com uma F-nn cuja linha traz o termo pt do item, como palavra inteira (tabela abaixo)      |
        | lista de features do README da base    | en     | 46                          | a tabela da base congelada na guarda                               | cada um dos quinze itens que a base não cobre casa com uma F-nn cuja linha traz o termo en do item, como palavra inteira (tabela abaixo)      |
        | todas as páginas de `docs/pt/`         | pt     | as da árvore (39 na base)   | o link da linha do catálogo para a página, ou a lista da guarda    | a linha de cada F-nn casada leva por link à própria página                                                           |
        | todas as páginas de `docs/en/`         | en     | as da árvore (39 na base)   | o link da linha do catálogo para a página, ou a lista da guarda    | a linha de cada F-nn casada leva por link à própria página                                                           |
        | lista de features do README publicado  | pt     | as da lista publicada (46 na base) | o título do item: o da base, pela tabela congelada; o novo, pela tabela da guarda, com a F-nn e o termo | todo item cujo título não é de um dos 46 da base casa com uma F-nn cuja linha traz o termo dele, como palavra inteira |
        | lista de features do README publicado  | en     | as da lista publicada (46 na base) | o título do item: o da base, pela tabela congelada; o novo, pela tabela da guarda, com a F-nn e o termo | todo item cujo título não é de um dos 46 da base casa com uma F-nn cuja linha traz o termo dele, como palavra inteira |

    @premissa
    Esquema do Cenário: [CT-35] a guarda das fontes reprova o item casado com a feature errada, a página escondida na lista e o motivo de enfeite
      Dado a tabela e a lista da guarda das fontes "<mundo>"
      Quando a guarda do mapa casa as fontes com o catálogo do mesmo idioma
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                           | resultado                                 | # partição                        |
        | cópia da tabela que casa o item "Dock de atalhos" do README pt com a F-06 (Bloqueio de sessão), cuja linha não traz `dock` | reprova, nomeando o item     | termo ausente da linha casada     |
        | cópia da tabela que casa o item "Dock de atalhos" do README pt com a F-56 (Inferência local), cuja linha traz "docker", e não a palavra `dock` | reprova, nomeando o item | termo só como pedaço de palavra |
        | cópia da tabela que casa o item "Tutorial guiado" do README pt com a F-199, que o catálogo não tem               | reprova, nomeando o item e a F-199        | F-nn inexistente                  |
        | cópia da lista com a página `recursos/dock-de-atalhos.md`, com motivo                                            | reprova, nomeando a página                | página de recurso na lista        |
        | cópia da lista com a página `comecar/index.md`, sem o motivo                                                     | reprova, nomeando a página                | motivo ausente                    |
        | cópia da lista com a página `comecar/index.md`, com o motivo "—"                                                 | reprova, nomeando a página                | motivo de enfeite                 |
        | cópia da lista com a página `comecar/index.md`, com o motivo "não se aplica", sem porquê                         | reprova, nomeando a página                | motivo de enfeite                 |
        | cópia do README pt com um item a mais na lista, "**Mapa interativo**: …", fora da tabela da guarda | reprova, nomeando o item | item novo sem F-nn |
        | cópia da lista com a página `comecar/index.md`, com o motivo "N/A"                                              | reprova, nomeando a página                | motivo de enfeite (tabela do Setup Global) |
        | cópia da lista com a página `comecar/index.md`, com o motivo "not applicable"                                   | reprova, nomeando a página                | motivo de enfeite (tabela do Setup Global) |
        | cópia da lista com a página `comecar/index.md`, com o motivo "Does not apply"                                   | reprova, nomeando a página                | motivo de enfeite (tabela do Setup Global) |
        | cópia da lista com a página `comecar/index.md`, com o motivo 0, que não é texto                                  | reprova, nomeando a página                | motivo de enfeite (tabela do Setup Global) |
```

*(alterado em 2026-11-21 — step 9, achado A13)* As quatro linhas novas são o lado PHP da tabela do motivo de enfeite (Setup Global); o CT-B07 do `05` tem as mesmas quatro do lado do conferidor. Medido no commit `168d1827`: a guarda do kit decide as quatro como enfeite, e elas passam hoje.

`@premissa` de mecanismo, decidida pela sessão (Q14b e Q14c):
- a linha da F-nn leva por link à página que a descreve;
- a página sem feature fica listada na guarda, com o motivo;
- os itens do README da base casam pela tabela congelada na guarda.

*(alterado em 2026-11-20: ADV-01)* Os seis itens do README que a base do catálogo não cobre, com o termo que a linha da F-nn casada tem de trazer. O termo casa por palavra inteira, sem diferença de maiúscula e com acento *(alterado em 2026-11-20: conferido pela sessão)*. Como pedaço de palavra, `dock` casaria "docker" na linha da F-56 (`docs/pt/operacao/roteiro-de-features.md:F-56:133`, `docs/en/operacao/roteiro-de-features.md:F-56:131`), e `views` casaria "reviews". O `"+"` discrimina: nenhuma linha da base o traz.

| Item do README da base (pt / en) | Termo pt | Termo en |
|---|---|---|
| Dock de atalhos / Shortcut dock | `dock` | `dock` |
| Tutorial guiado / Guided tutorial | `tutorial` | `tutorial` |
| Visões salvas de tabela / Saved table views | `visões` | `views` |
| Página única de login / Single login page | `página única` | `single login` |
| Proteção anti-robô opcional / Optional anti-robot protection | `anti-robô` | `anti-robot` |
| "+" no e-mail do cadastro / "+" in the sign-up e-mail | `"+"` | `"+"` |
| Aparência das telas de acesso escolhida na tela / Sign-in screen appearance chosen on screen | `telas de acesso` | `sign-in screen` |
| DTO com `spatie/laravel-data` / DTOs with `spatie/laravel-data` | `Laravel Data` | `Laravel Data` |
| Login social por painel / Social login per panel | `login social` | `social login` |
| Desligada por padrão / Off by default | `interruptor` | `switch` |
| UI 100% em pt-BR, inclusive nos plugins que só trazem inglês / 100% pt-BR UI, including plugins that ship English only | `pt-BR` | `pt-BR` |
| Aviso de alterações não salvas / Unsaved-changes warning | `não salvas` | `unsaved` |
| Densidade do layout em três níveis / Layout density in three steps | `densidade` | `density` |
| Carregamento contínuo de tabela / Continuous table loading | `carregamento contínuo` | `continuous` |
| Link de acesso direto ao painel de cada organização / Direct link to each organization's panel | `cada organização` | `each organization` |

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* Medido no `d9608c5a` com a tabela congelada da guarda: são 15 os itens do README da base sem F-nn na base, e não 6. Os nove a mais (as nove últimas linhas acima) casavam sem termo, e o R5-M6 não os alcançava; agora todos têm termo, e o CT-05 e o CT-35 conferem os quinze. O termo de cada um está na célula "Feature" da F-nn casada (F-75, F-111, F-72, F-84, F-99, F-82, F-83, F-96 e F-87), nos dois idiomas.

*(alterado em 2026-11-20: ADV2-03)* A R5 lê também a lista publicada do README, e não só a da base. O item é reconhecido pelo título: o texto em negrito do começo; sem negrito, o texto antes do primeiro ":", "—" ou "("; e, sem nenhum dos dois, o item inteiro. Medido nos dois READMEs: 46 itens e 46 títulos diferentes por idioma, 26 em negrito, 17 pelo separador e 3 pelo item inteiro. O item cujo título não é de um dos 46 da base fica na tabela da guarda, com a F-nn e o termo, como os seis do ADV-01.

*(alterado em 2026-11-20: ADV-21 e ADV-22)* O CT-35 dá a cada mutante da guarda das fontes uma cópia que o mata, sem depender de a árvore real estar errada.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* A lista das páginas que não descrevem feature mora na guarda do catálogo, com o motivo de cada uma: os seis índices, o próprio catálogo, `operacao/desenvolvendo-o-kit.md`, `referencia/arquitetura-em-diagramas.md`, `referencia/estudo-advanced-tables.md` e `referencia/mapa-interativo.md` (11 por idioma; as quatro últimas são as que o trabalho do catálogo sugeriu, conferidas pela sessão). A marca de removida é a palavra removida/removed na célula "Feature", com o motivo depois do ":".

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a unificação esquece um item do README que a base do catálogo não tinha (dock de atalhos, tutorial guiado, "+" no e-mail) | CT-05 | linha README pt: o item fica sem F-nn e a contagem de casados fica abaixo de 46; o mutante aceita |
| M2 | a lista unificada só entra no catálogo pt | CT-05 | linha README en: reprova; o mutante aceita |
| M3 | a tabela da guarda aponta F-nn que o catálogo não tem | CT-35 | linha da F-199: reprova; o mutante aceita |
| M4 | uma página fica sem F-nn e fora da lista, ou entra na lista sem o motivo (a página nova de `comecar/`, criada depois da base) | CT-05, CT-35 | CT-05, linhas de páginas, lidas da árvore: a página sem nenhuma das duas coisas reprova; CT-35, linha "sem o motivo": reprova; o mutante aceita |
| M5 | página de recurso jogada na lista das que não descrevem feature, para fugir do catálogo | CT-35 | linha `recursos/dock-de-atalhos.md`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-01) | a tabela da guarda, escrita pela mesma mão que escreve o catálogo, casa um item novo do README com a F-nn de outra feature | CT-05, CT-35 | CT-35, linha do dock casado com a F-06: reprova; e, no publicado, o CT-05 exige o termo na linha casada; o mutante aceita |
| M7 — revisão adversarial (ADV-22) | página na lista com motivo de enfeite | CT-35 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-01; conferido pela sessão) | o termo casa como pedaço de palavra, e `dock` acha "docker" na linha da F-56 | CT-35 | linha do dock casado com a F-56: reprova; o mutante aceita |
| M9 — revisão adversarial (ADV2-03) | a entrega acrescenta à lista do README um item sem F-nn, como o próprio mapa, e a guarda só lê o README da base | CT-05, CT-35 | CT-35, linha do item "Mapa interativo": reprova; e, no publicado, o CT-05 lê a lista publicada; o mutante aceita |
| M10 — step 9 (A13) | a guarda do kit e o conferidor do mapa têm duas definições de motivo de enfeite, e a do kit, alinhada à do conferidor, aceita "N/A", "not applicable", "Does not apply" ou um valor que não é texto | CT-35 | linhas "N/A", "not applicable", "Does not apply" e 0: reprova; o mutante aceita |

---

## Regra R6 — Toda F-nn vigente é entregue por peça desenhada da origem que ela declara — do pacote que a linha nomeia, ou, no código próprio, da rota do "onde fica" —, com a F-nn no desenho da peça; todo caminho do "onde fica" resolve; e nenhuma peça entrega F-nn removida

> `RQ-04`, `RQ-05`, `RQ-21`, `P-03`, `P-10`, `P-08` · perfil **padrão** · técnica: **EP por origem** (as três partições do glossário) + tabela de decisão (F-nn × pacote nomeado × pacote da peça; removida × entregue) + controles em cópia

```gherkin
  Regra: A demonstração cobre todas as features, as de pacote e as de código próprio

    @premissa
    Esquema do Cenário: [CT-06] toda F-nn é entregue por peça desenhada, e ao menos uma dessas peças é da origem que ela declara
      Dado as F-nn do catálogo com a origem "<origem>", fora as marcadas como removidas
      Quando a guarda do mapa procura as peças que entregam cada uma
      Então cada F-nn é entregue por ao menos uma peça do modelo que está desenhada em diagrama publicado
      E ao menos uma dessas peças é <peça da origem>
      E todo caminho do "onde fica" dessas F-nn resolve no roteador, no modo sem tenant (o caminho com `{org}` fica para o CT-44)

      Exemplos:
        | origem                          | peça da origem                                                                                                                                                      |
        | pacote                          | do pacote que a linha nomeia, pelo nome do Composer ou do npm                                                                                                       |
        | código próprio                  | do código do kit (fora de `vendor/` e de `node_modules/`); e, quando a célula "onde fica" traz um caminho entre crases que começa por `/`, em qualquer ponto, sem "→", a página que responde num desses caminhos, ou o recurso dono dela |
        | pacote com customização própria | uma do pacote que a linha nomeia e outra do código do kit                                                                                                           |

    @premissa
    Esquema do Cenário: [CT-36] a guarda da origem reprova a F-nn entregue pela peça errada e a removida que ainda é entregue
      Dado o catálogo e o modelo "<mundo>"
      Quando a guarda do mapa confere a origem e a entrega de cada F-nn
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                                         | resultado                                  | # partição                          |
        | publicados                                                                                                                    | aceita: nenhuma peça do modelo entrega F-nn marcada como removida | válida                    |
        | cópia em que a F-33 (Health checks), de origem pacote, nomeia `shuvroroy/filament-spatie-laravel-health` e é entregue só pelo `FilamentJobsMonitorPlugin` | reprova, nomeando a F-33 e o pacote | peça de outro pacote |
        | cópia em que a F-29 (Usuários, `/admin/users`), de código próprio, é entregue só pelo `ConviteResource`                        | reprova, nomeando a F-29 e a rota          | peça que não responde na rota       |
        | cópia em que uma F-nn de código próprio é entregue só por peça de pacote                                                      | reprova, nomeando a F-nn                   | origem errada                       |
        | cópia em que uma F-nn de pacote com customização própria é entregue por uma peça só                                           | reprova, nomeando a F-nn                   | customização com uma peça só        |
        | cópia com a F-05 marcada como removida, com o motivo, e uma peça do modelo que ainda a entrega                                | reprova, nomeando a F-05 e a peça          | removida e entregue                 |
        | cópia em que o "onde fica" da F-07 en é `/{panel}/my-profile`, que o roteador não resolve | reprova, nomeando a F-07 e o caminho | caminho que não resolve |

    Esquema do Cenário: [CT-44] com tenant, todo caminho do "onde fica" que leva a organização resolve no roteador
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o catálogo em "<idioma>"
      Quando a guarda do mapa resolve no roteador cada caminho do "onde fica" que traz `{org}` ou `{/org}`, com uma organização no lugar do curinga
      Então todo caminho resolve
      E a guarda conferiu ao menos os caminhos da F-15, da F-22 e da F-25

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

O piso de não vácuo é o do CT-01: cada F-nn tem exatamente uma origem, e as três linhas somadas percorrem o catálogo inteiro.

`@premissa` de mecanismo, decidida pela sessão (Q17): a linha do catálogo nomeia o pacote pelo nome do Composer ou do npm.

*(alterado em 2026-11-20: ADV-02, ADV-03 e ADV-21)*
- **O pacote da peça.** A peça é do pacote quando a classe dela está em `vendor/{pacote}/`.
- **A rota do "onde fica".**
  - O caminho de rota é o texto entre crases que começa com `/`.
  - A guarda troca `{painel}` por cada painel do registro, tira o `{org}` (a suíte do kit roda sem tenant) e a query, e resolve o caminho no roteador da aplicação.
  - Todo caminho da célula resolve no roteador, em toda linha, de qualquer origem *(alterado em 2026-11-20: ADV2-04; era "basta um caminho")*. `{painel}` e `{panel}` valem para cada um dos três painéis, e todos têm de resolver; `{/org}` vale sem a organização no modo sem tenant e com ela no modo com tenant; `{org}` só existe com tenant e é conferido no CT-44, na suíte Tenancy; outro parâmetro aceita qualquer valor.
  - No código próprio, ao menos um caminho resolve para uma peça que entrega a F-nn: a página que responde nele, ou o recurso dono dela. Medido com `php artisan route:list`: `admin/users` resolve para `ListUsers`, página do `UserResource`; `app/login`, para `TelaLogin`; `infra/health-check-results`, para `HealthCheckResults`.
  - Medido com `php artisan route:list --json` nos dois modos: no pt, 51 caminhos, 3 só do modo com tenant (F-15, F-22 e F-25, `/app/{org}…`), e todos resolvem; no en, os mesmos, menos a F-07, `/{panel}/my-profile`, que não resolve sem tenant em painel nenhum (a rota é `/meu-perfil` nos três). É defeito da base, que a entrega corrige no passo 5. No catálogo da entrega (commit `0f38e2f`, a coluna "Onde" achada pelo cabeçalho, depois da Origem): 92 caminhos por idioma, 6 só do modo com tenant, e todos resolvem nos dois modos (136 rotas sem tenant e 137 com).
- **Até onde o oráculo vai.** A regra é a célula "onde fica" trazer um caminho entre crases que começa por `/`, em qualquer ponto, sem "→". Na base, dá 40 das 74 linhas, nos dois idiomas. Outras 4 têm caminho e "→", e 30 não têm caminho (menu, ação, "os três", "—"). *(alterado em 2026-11-20: a regra antiga, "começa por caminho", dava 37 em pt e 38 em en, porque a F-44 é "topbar do `/infra`" em pt e "`/infra` topbar" em en)*
  - Nessas 40, a rota fecha o oráculo do código próprio.
  - Nas outras, ele é só "do código do kit", e trocar uma peça do kit por outra fica como lacuna (M8).
- **A F-nn no desenho da peça** *(alterado em 2026-11-20: ADV2-05, D29; oráculo ajustado pela medição no commit `4adaf9a`)*. Nos diagramas que os construtores geram do modelo — o mapa de arquitetura e a árvore de registro —, a F-nn de cada peça com nó próprio que entrega feature está no desenho dela, e o CT-B01 confere as duas formas. A peça que só aparece como membro de um agrupamento não tem nó próprio: o card "Quem está em …" a nomeia, e a F-nn dela fica no diagrama em que ela tem nó (medido: o `app-resumo-sem` desenha 1 peça e 7 agrupamentos).
  - **No mapa de arquitetura, no `tag`** do componente ("F-29 · F-30"). O archify o desenha como detalhe fino (`data-detail="fine"`): o visualizador abre no nível de leitura "read", e o `tag` fica com opacidade 0; a partir de 175 % de zoom (nível "full", pelo botão "+") e com a peça em foco pela busca, opacidade 1. O CT-B01 leva o visualizador ao nível cheio e confere a opacidade. Medido: 4 peças com F-nn no `app-acesso`, em pt e em en, com 0 no carregamento e 1 no nível cheio; o passaporte mostra o `tag`, e a busca por "F-02" acha as duas telas que a entregam.
  - **Na árvore de registro, no sublabel.** O esquema `tree` do archify não tem `tag` — só `architecture`, `workflow`, `dataflow`, `lifecycle` e `erd` o têm, e no `erd` ele é a faixa de domínio, e não um texto da entidade. A F-nn vai no sublabel, à vista no carregamento. Medido: 5 peças com F-nn em cada árvore do `/app`, com opacidade 1.
  - **No carregamento, o `tag` não aparece.** A P-10 não conta como desenho a etiqueta que o zoom esconde, e o ADV2-05 era "a F-nn não chega ao leitor". O oráculo segue a D29 e pede o nível cheio; se a F-nn tiver de aparecer sem zoom, a D29 muda (por exemplo, a F-nn no sublabel, como na árvore), e o ponto foi ao coordenador.
- **O CT-36 controla com cópias.** Cada mutante da guarda da origem morre sem depender de a árvore real estar errada.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)*
- **A lista fechada da D33.** Seis F-nn não têm peça possível no modelo e ficam na guarda com o motivo de cada uma: F-64 (guarda de teste), F-99 (arquivos de tradução), F-107 (`docker-compose.yml`) e F-108 (`deploy_docker_local.sh`), sem peça nenhuma; F-100 (o componente de formulário da media library) e F-113 (o plugin de browser do Pest e o Playwright), conferidas só pela parte do kit. A decisão nasceu na implementação (D33, depois deste `04`).
- **O CT-44** confere todo caminho do "onde fica" com `{org}` ou `{/org}`, sem a restrição "sem →" do CT-06: entram a F-13, a F-15, a F-22, a F-25, a F-88 e a F-92.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o modelo nasce só da varredura de `app/`, e a feature de pacote fica sem peça | CT-06 | linha "pacote": uma F-nn de pacote sem peça do pacote nomeado reprova; o mutante aceita |
| M2 | a peça da feature está no modelo, mas não está desenhada em diagrama publicado | CT-06 | "desenhada em diagrama publicado": reprova; o mutante aceita |
| M3 | origem declarada errada: "código próprio" numa feature entregue só por peça de pacote | CT-36 | linha "código próprio entregue só por peça de pacote": reprova; o mutante aceita |
| M4 | a guarda ou o gerador tratam só duas origens, e a "pacote com customização própria" passa sem conferência | CT-36 | linha "customização com uma peça só": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-02) | feature que o kit ainda implementa sai como removida (a F-05, "desligada por padrão"), e a peça continua a entregá-la | CT-36 | linha da F-05 removida e entregue: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-03) | a associação peça → F-nn é livre: a F-nn do Health é entregue só por outro plugin | CT-36 | linha da F-33 entregue pelo `FilamentJobsMonitorPlugin`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-03) | a F-nn de código próprio é entregue por uma peça do kit que não responde na rota do "onde fica" | CT-36 | linha da F-29 entregue pelo `ConviteResource`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-03) | a F-nn de código próprio cujo "onde fica" não é rota (menu, ação, painel inteiro) é entregue por outra peça do kit | ⚠️ **sem matador**: nas 34 linhas da base cujo "onde fica" não traz caminho sem "→" (4 com "→" e 30 sem caminho), não há peça que a guarda resolva sem julgamento. Fica para o quality gate | — (lacuna declarada) |
| M9 — revisão adversarial (ADV2-04) | o "onde fica" guarda um segundo caminho velho, que não resolve mais, e "basta um caminho" aceita a célula | CT-36, CT-44 | CT-36, linha da F-07 en: reprova; com tenant, o CT-44 resolve os caminhos com `{org}`; o mutante aceita |
| M10 — revisão adversarial (ADV2-05) | o modelo liga cada peça à F-nn dela, mas nenhum diagrama mostra a F-nn ao leitor | CT-B01 | "a F-nn de cada peça com nó próprio que entrega feature está no desenho dela: no `tag`, com opacidade 1 no nível de leitura cheio, ou no sublabel da árvore, à vista": reprova; o mutante aceita |

---

## Regra R7 — Manter o catálogo único é uma rule indexada, que alcança os lugares onde a feature nasce e manda o número só crescer

> `RQ-23`, `P-08` · perfil **mínimo** · técnica: **EP**

```gherkin
  Regra: A rule do catálogo existe, está no índice e manda o que mantém o catálogo único

    Cenário: [CT-07] a rule do catálogo está no índice das rules, alcança o catálogo e os lugares onde a feature nasce, e manda o número só crescer
      Dado o índice das rules do projeto
      Quando a guarda do mapa procura a linha que aponta para a rule do catálogo de features
      Então a linha existe, e cada caminho dela casa ao menos um arquivo existente
      E os caminhos da linha casam a página do catálogo em pt e em en, e também `app/**`, `composer.json`, `package.json` e `routes/**`
      E a rule nomeia a página do catálogo como o lugar único das features
      E a rule manda a feature nova receber o próximo número livre e proíbe trocar, reaproveitar ou remover número sem registro
```

*(alterado em 2026-11-20: ADV-18)* A feature nasce num recurso, num plugin, num comando ou numa rota, e o pacote, nos manifestos. A rule que só casa a página do catálogo nunca é aberta por quem cria a feature, e o catálogo volta a defasar (RQ-22).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a rule existe, mas o índice não aponta para ela, e o agente nunca a abre | CT-07 | "a linha existe": reprova; o mutante aceita |
| M2 | o caminho da linha do índice não casa arquivo nenhum (erro de digitação no glob) | CT-07 | "cada caminho casa ao menos um arquivo existente": reprova; o mutante aceita |
| M3 | a rule fala do catálogo único e esquece a numeração | CT-07 | "manda o próximo número livre e proíbe…": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-18) | a linha da rule casa só a página do catálogo, e quem cria recurso, plugin ou comando nunca a abre | CT-07 | "casam também `app/**`, `composer.json`, `package.json` e `routes/**`": reprova; o mutante aceita |

---

## Regra R8 — Toda peça do modelo é peça com nome que existe, do tipo que a classe dela é, dentro do conjunto fechado, e aparece uma vez

> `RQ-24`, `P-03` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + tabela de decisão (classe × tipo declarado) + controles em cópia

```gherkin
  Regra: Módulo é peça com nome que existe no código, e o modelo não inventa nem repete peça

    Esquema do Cenário: [CT-08] toda peça do modelo existe no código, é de um tipo do conjunto fechado e aparece uma vez
      Dado o modelo de peças "<mundo>"
      Quando a guarda do mapa confere cada peça contra o código, contra os pacotes instalados e contra o registro de painéis
      Então o resultado é "<resultado>"
      E, no publicado, cada painel do registro de painéis é uma peça do tipo painel

      Exemplos:
        | mundo                                                                                         | resultado                   |
        | publicado                                                                                     | aceita                      |
        | cópia com a peça `App\Filament\Admin\Resources\Inexistente\InexistenteResource`               | reprova, nomeando a classe  |
        | cópia com uma peça do tipo "rota"                                                             | reprova, nomeando o tipo    |
        | cópia com `App\Models\User` do tipo recurso                                                   | reprova, nomeando a classe e o tipo |
        | cópia com `App\Models\User` em duas peças                                                     | reprova, nomeando a classe  |
        | cópia com uma peça do pacote `vendor-inexistente/plugin`, que não está instalado              | reprova, nomeando o pacote  |
        | cópia com a peça painel "financeiro", que o registro de painéis não tem                       | reprova, nomeando o painel  |
```

Os tipos válidos são os 13 do Setup Global.

*(alterado em 2026-11-20: ADV-16)* O tipo declarado confere com a classe. A guarda testa as linhas abaixo, na ordem, e a primeira que a classe satisfaz dá o tipo esperado. As classes-base do Filament foram conferidas no vendor (`vendor/filament/filament/src/Resources/Resource.php`, `vendor/filament/filament/src/Pages/BasePage.php`, `vendor/filament/widgets/src/Widget.php` e `vendor/filament/filament/src/Contracts/Plugin.php`).

| Tipo | A classe… |
|---|---|
| painel | não é classe: é um id do registro de painéis |
| recurso | estende `Filament\Resources\Resource` |
| página | estende `Filament\Pages\BasePage`: o `Page` dos painéis e o `SimplePage` das telas de autenticação (o `TelaLogin` estende o `SimplePage`) |
| widget | estende `Filament\Widgets\Widget` |
| plugin de pacote | implementa `Filament\Contracts\Plugin` |
| model | estende `Illuminate\Database\Eloquent\Model` |
| provider | estende `Illuminate\Support\ServiceProvider` |
| comando | estende `Illuminate\Console\Command` |
| setting | estende `Spatie\LaravelSettings\Settings` |
| componente Livewire | estende `Livewire\Component` |
| policy | é a que o `Gate` devolve para um model |
| middleware | é registrada como middleware por um painel ou pela aplicação, ou é o alias `panel:{id}` |
| serviço próprio | é do código do kit e não cai em nenhuma linha acima |

*(alterado em 2026-11-20 — guardas do passo 8, leitura fixada pelo executor e conferida pela sessão)* "Middleware registrado pela aplicação" inclui dois casos que o painel não lista: a classe que o `bind` do container põe no lugar de um middleware registrado (o `BloquearSessaoComRetorno` no lugar do `Locker`), e o middleware do pipeline de um agente de IA (`LaravelAiContractsHasMiddleware`): a classe com `handle` citada no `middleware()` do agente, direto ou pelo registro que ele consulta (os guardrails de `AppAiGuardrails`). Sem isso, a guarda dava aos guardrails o tipo `servico`, e o modelo, com razão, `middleware`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a peça de uma classe apagada continua no modelo | CT-08 | linha `InexistenteResource`: reprova; o mutante aceita |
| M2 | tipo de peça fora do conjunto da RQ-24 e do glossário ("rota", "tabela") | CT-08 | linha "rota": reprova; o mutante aceita |
| M3 | a mesma classe vira duas peças, e as ligações dela se dividem entre as duas | CT-08 | linha `User` em duas peças: reprova; o mutante aceita |
| M4 | a peça de um pacote desinstalado continua no modelo | CT-08 | linha `vendor-inexistente/plugin`: reprova; o mutante aceita |
| M5 | o painel é escrito à mão no modelo, sem conferir o registro | CT-08 | linha "financeiro": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-16) | a varredura erra o tipo dentro do conjunto fechado: o model `User` como recurso, um widget como página | CT-08 | linha do `User` do tipo recurso: reprova; o mutante aceita |

---

## Regra R9 — Toda prova resolve no commit que publica: o arquivo é versionado no kit, o trecho está nele, fora de comentário pela sintaxe do tipo de arquivo, e a prova não guarda número de linha, nem no modelo nem nos arquivos de diagrama

> `RQ-24`, `P-03`, `P-04`, `P-07` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha, nunca duas juntas) + controles em cópia

A Q9 foi fechada pelo `00`. Pela P-07, o link de prova aponta para o commit que publica, e o `vendor/` não está nele. Pela RQ-24, a prova de uma peça que um plugin traz é, no código versionado do kit, o registro do plugin no painel (R10). Então toda prova que cita arquivo fora do commit reprova, inclusive a de `vendor/`.

```gherkin
  Regra: A ligação vem com o trecho de código que a prova, no commit que publica, e a guarda falha quando o código deixa de bater

    Esquema do Cenário: [CT-09] a prova de toda ligação resolve no commit que publica: o arquivo é versionado, o trecho está nele, fora de comentário, e a prova não guarda número de linha
      Dado uma ligação do modelo, com a prova "<prova>"
      Quando a guarda do mapa confere a prova
      Então o resultado é "<resultado>"
      E, no modelo publicado, a guarda conferiu a prova de tantas ligações quantas o modelo tem, e de ao menos uma de cada painel do registro

      Exemplos:
        | prova                                                                                                                                                      | resultado                    | # partição                        |
        | "o painel admin registra o plugin do Shield": `app/Providers/Filament/AdminPanelProvider.php`, trecho `FilamentShieldPlugin::make()`                       | aceita                       | válida: arquivo versionado        |
        | a mesma ligação, sem trecho                                                                                                                                | reprova, nomeando a ligação  | trecho ausente                    |
        | a mesma ligação, trecho vazio                                                                                                                              | reprova, nomeando a ligação  | trecho vazio                      |
        | a mesma ligação, trecho só de espaços                                                                                                                      | reprova, nomeando a ligação  | trecho só de espaços              |
        | a mesma ligação, contra uma cópia em memória do arquivo sem a linha do trecho                                                                              | reprova, nomeando a ligação  | trecho que saiu do código         |
        | a mesma ligação, trecho `ver a nota longa no AppPanelProvider`, que só existe num comentário do arquivo                                                    | reprova, nomeando a ligação  | trecho só em comentário           |
        | a mesma ligação, em `app/Providers/Filament/AdminPainelProvider.php`, que não existe                                                                       | reprova, nomeando o arquivo  | arquivo inexistente               |
        | a mesma ligação, em `bootstrap/cache/packages.php`, que está no disco e fora do git, com um trecho que está nele                                          | reprova, nomeando o arquivo  | arquivo fora do git               |
        | "o painel infra registra a página `HealthCheckResults`": `vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php`, trecho `$panel->pages([$this->getPage()]);` | reprova, nomeando o arquivo  | arquivo de pacote, fora do commit |
        | a primeira ligação, com a prova `app/Providers/Filament/AdminPanelProvider.php:240`, que guarda o número da linha, e o mesmo trecho                         | reprova, nomeando a ligação  | prova com número de linha         |
        | a mesma ligação, em `resources/views/filament/pages/convites-recebidos.blade.php`, trecho `ConvitesRecebidos::table()`, que só existe dentro do `{{-- --}}` da linha 1 | reprova, nomeando a ligação | trecho só em comentário Blade |
        | cópia do modelo com a prova de uma ligação num campo `linha: 240`, ao lado do arquivo e do trecho | reprova, nomeando a ligação | linha num campo do modelo |
        | cópia de um arquivo de diagrama em que a prova de uma peça, no `sources`, traz `line: 240` | reprova, nomeando o diagrama e a peça | linha nas provas do diagrama |
        | cópia do modelo de peças em que a evidência do `UserResource` do `/admin` traz `line: 240` ao lado do arquivo e do trecho | reprova, nomeando a peça | linha na evidência de peça |
        | cópia do modelo de peças em que a evidência do `UserResource` do `/admin` traz `end_line: 241` | reprova, nomeando a peça | fim de intervalo na evidência de peça |
        | cópia do modelo de peças em que a evidência do `UserResource` do `/admin` traz `path: app/Models/User.php` ao lado do arquivo dela | reprova, nomeando a peça | outro caminho na evidência de peça |
```

*(alterado em 2026-11-20: ADV-19 e ADV-23)*
- A prova é só o arquivo e o trecho. O número da linha envelhece a cada edição do arquivo (P-07), e quem o calcula é o build. A guarda reprova a prova que guarda linha, num campo próprio ou colada no caminho (`:240`, `#L240`).
- Essa linha está aqui, na suíte do kit, porque ela roda em todo PR. O job `site` não roda no PR que só toca `app/`, e é nesse PR que a linha guardada envelhece.

*(alterado em 2026-11-20: ADV2-06 e ADV2-07)*
- **Fora de comentário, por tipo de arquivo.** PHP: `codigoSemComentario()`. Blade (`.blade.php`): fora de `{{-- --}}` e de `<!-- -->`. JS e stub: fora de `//` e de `/* */`. Medido: a linha 1 de `resources/views/filament/pages/convites-recebidos.blade.php` é um `{{-- --}}` com `ConvitesRecebidos::table()`, e o arquivo, de 4 linhas, não traz o trecho fora dele.
- **A linha nunca é guardada.** O modelo não tem campo `linha` nem `line`, e a prova de peça nos arquivos de diagrama (`sources`) é sempre âncora `{arquivo, trecho}`, com `label` opcional. Medido nos 13 arquivos de diagrama do esqueleto: 405 provas, 3 com `arquivo` e `trecho`, 402 também com `label`, e nenhuma com linha nem com `:N` ou `#LN` no caminho. O gerador projeta a âncora e acha a linha na revisão publicada (`site/mapa/gerar.mjs`, função `linhaDaAncora`), e não aceita outra forma como prova.
- O piso conta todas as ligações do modelo, e não só as de registro.

*(alterado em 2026-11-21 — step 9, achado A10)*
- **A âncora tem chaves fechadas: `arquivo`, `trecho` e, opcional, `label`.** O gerador projeta a âncora com `{ ...linhaDaAncora(...), ...projetar(resto) }` (`site/mapa/gerar.mjs`, `linhaDaAncora`, no commit `168d1827`): qualquer chave a mais que venha do arquivo vence a que ele calcula — `line` e `end_line` trocam a linha, e `path` troca o arquivo do link. A guarda reprova a chave a mais em toda âncora: a das ligações, a evidência de cada peça do modelo de peças, e as dos arquivos de diagrama. Antes, a guarda olhava só `linha` e `line`, e só nas ligações e nos diagramas.
- Medido no commit `168d1827`: nenhuma âncora com chave fora de `arquivo`, `trecho` e `label` — nem as 413 das ligações, nem as 284 evidências de peça, nem as 863 dos diagramas escritos à mão. As três linhas novas passam hoje no publicado, e reprovam as cópias.

*(alterado em 2026-11-21 — step 9, achados A3 e A7)* Dois cenários novos nesta regra, nos Exemplos abaixo: o CT-52, sobre a linha que o build dá à prova, e o CT-53, sobre as âncoras que nenhuma guarda Pest conferia.

```gherkin
    Esquema do Cenário: [CT-52] a linha que o build dá a cada prova é a do código: o trecho está fora de comentário nela
      Dado o `node` do ambiente, lido pela versão
      E os arquivos de diagrama do mapa "<mundo>"
      Quando o gerador monta, para o archify, a entrada de cada diagrama, sem baixar o archify nem renderizar
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu a linha de tantas provas quantas o gerador projeta — o item de prova de cada ligação desenhada e cada fonte de peça —, e ao menos uma âncora cujo trecho aparece mais de uma vez no arquivo (22 no commit `168d1827`)

      Exemplos:
        | mundo                                                                                                                                                                                          | resultado                                                                                                     | # partição                              |
        | publicados                                                                                                                                                                                     | aceita: em todo diagrama, a linha de cada item de prova e de cada fonte de peça tem o trecho da âncora fora de comentário, pela sintaxe do tipo do arquivo | válida                                  |
        | um diagrama escrito à mão, em memória, com uma ligação provada por `app/Providers/AppServiceProvider.php`, trecho `Locker::class`, que aparece primeiro num comentário `//` e depois no código | aceita (idem): a linha do item de prova é a do código, ou o gerador recusa a prova, nomeando a ligação         | trecho que aparece antes num comentário |
        | o mesmo diagrama em memória, sobre o texto de `app/Providers/AppServiceProvider.php` com um nowdoc cujo corpo tem um apóstrofo, inserido em memória antes do comentário que cita `Locker::class` | aceita (idem): a linha do item de prova é a do código, e não a do comentário | comentário depois de um nowdoc com apóstrofo *(re-revisão)* |
```

*(alterado em 2026-11-21 — step 9, re-revisão: RR-03)* **O controle do nowdoc.** O lexer de comentários do gerador pula as strings entre aspas e não conhece heredoc nem nowdoc: um apóstrofo dentro do nowdoc abre, para ele, uma string que só fecha no próximo apóstrofo do arquivo, e o `//` de um comentário real que fique no caminho não é visto como comentário. A linha nova controla isso.
- **De onde vem o PHP.** O controle tira o texto de `app/Providers/AppServiceProvider.php` no commit do teste — o mesmo arquivo do controle do `Locker::class` — e, em memória, insere depois da linha 19 (o `{` do `register()`) um nowdoc de três linhas com um apóstrofo no corpo: `$aviso = <<<'TXT'`, `it's a nowdoc` e `TXT;`. O texto adulterado chega ao gerador no lugar da leitura que ele faz do arquivo na revisão publicada — por exemplo, o processo filho da forma de leitura intercepta o `git show` desse arquivo —, e nada é gravado no repositório. Com o nowdoc, o comentário que cita `Locker::class` passa à linha 28, e o código, à 31.
- **Medido no HEAD `bc27379f`.** Sem o nowdoc, o item de prova aponta a linha 28 do arquivo, a do código: o controle anterior passa. Com o nowdoc, aponta a linha 28 do texto adulterado, a do comentário, e não a 31: a linha nova reprova hoje. Nenhum PHP versionado tem hoje um comentário real que o lexer do gerador deixe de ver — conferido nos 25 PHP do commit que usam `<<<` —, e por isso o controle monta o caso em memória.

- **Por que o oráculo é a linha que o build dá, e não a âncora.** O link de evidência e o item de card levam a linha que o gerador acha na revisão publicada (P-07), e é ela que o leitor abre. A guarda do CT-09 exige o trecho fora de comentário em algum lugar do arquivo; o gerador usa a primeira ocorrência no texto cru (`site/mapa/gerar.mjs`, `linhaDaAncora`, no commit `168d1827`), que pode ser um comentário. O oráculo aceita as duas correções — o gerador pular o comentário, ou recusar a âncora ambígua — e reprova só a linha de comentário.
- **O controle.** Medido no commit `168d1827`: em `app/Providers/AppServiceProvider.php`, o trecho `Locker::class` aparece primeiro num comentário (`app/Providers/AppServiceProvider.php:Locker:25`) e depois no código (`app/Providers/AppServiceProvider.php:Locker:28`). Executado pela forma de leitura da G5, com o diagrama de controle em memória: o item de prova que o gerador monta aponta a linha 25 do `AppServiceProvider.php`, a do comentário. A linha de controle reprova hoje.
- **Hoje.** Das 1.127 âncoras distintas do modelo e dos diagramas, 22 têm o trecho mais de uma vez no arquivo, e em nenhuma a primeira ocorrência está num comentário: a linha "publicados" passa hoje. 55 âncoras estão em YAML, `.env`, Markdown e JSON, que ganharam regra de comentário com a Q20; a primeira ocorrência de nenhuma delas fica numa linha de comentário `#` ou `<!--`.
- **Costura G5, forma de leitura.** O caso roda num `node` a função que o gerador exporta para montar a entrada do archify; o controle passa o diagrama em memória. Sem `node`, o caso reprova (Q21).

```gherkin
    Esquema do Cenário: [CT-53] toda âncora do mapa resolve na suíte do kit: a evidência de cada peça do modelo e toda âncora dos diagramas escritos à mão
      Dado o modelo de peças e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere, como prova, cada evidência de peça e cada âncora dos diagramas escritos à mão — a fonte de participante, nó, entidade, tipo, evento e span, e a prova de relação
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu tantas âncoras quantas o modelo de peças e os diagramas escritos à mão têm, e ao menos uma de cada coleção: `participants`, `nodes`, `entities`, `types`, `events`, `spans` e `relationships`

      Exemplos:
        | mundo                                                                                                                                       | resultado                                     | # partição                         |
        | publicados                                                                                                                                  | aceita                                        | válida                             |
        | cópia do modelo de peças em que a evidência do `UserResource` do `/admin` tem um trecho que não está em `app/Filament/Admin/Resources/Users/UserResource.php` | reprova, nomeando a peça | evidência de peça que saiu do código |
        | cópia do `erd-identidade-e-permissoes` em que a fonte de uma entidade cita uma migração de `database/migrations/` que não existe              | reprova, nomeando o diagrama e o arquivo      | arquivo inexistente                |
        | cópia do `marcos-do-changelog` em que a fonte de um evento cita `CHANGELOG.md` com um trecho que não está nele                               | reprova, nomeando o diagrama                  | trecho que não está no arquivo     |
        | cópia do `classes-heranca-e-contratos` em que a prova de uma relação é um trecho que só existe num comentário do arquivo                     | reprova, nomeando o diagrama e a relação      | prova de relação só em comentário  |
        | cópia do `tempos-ci` em que a fonte de um span cita `.github/workflows/ci.yml` com um trecho que só está numa linha de comentário `#` (Q20, decidida) | reprova, nomeando o diagrama | trecho só em comentário de YAML |
```

- **O que ficava sem guarda.** A guarda do CT-09 resolve a prova das ligações; a do CT-39, a âncora das transições e dos estados de `lifecycle`. Ficavam para o build do site — que o job `site` só roda quando o PR toca `docs/` ou `site/` — a evidência de cada peça do modelo de peças e as âncoras dos diagramas escritos à mão que não são transição nem estado. Um PR só de `app/`, `database/`, `config/`, do CHANGELOG ou de `.github/` quebrava uma delas sem vermelho, e o próximo PR de `docs/`, de outra pessoa, reprovava no build. A página do mapa promete que as guardas confrontam o mapa com o código em todo PR.
- **A guarda é a da R9.** Arquivo versionado, trecho presente fora de comentário pela sintaxe do tipo, sem número de linha e com as chaves fechadas da âncora.
- **Medido no commit `168d1827`:** 878 âncoras sem guarda Pest — 284 evidências de peça (208 não coincidem com a âncora de nenhuma ligação) e 594 dos diagramas escritos à mão: 181 fontes de `nodes`, 129 de `participants`, 75 de `entities`, 61 de `types`, 47 de `spans`, 30 de `events` e 71 provas de `relationships`. Por pasta: 696 em `app/`, 115 em `database/`, 30 no `CHANGELOG.md`, 23 em `.github/`, 8 em `config/`, 3 em `routes/`, 2 no `composer.json` e 1 no `.env.example`. A guarda da R9 aceita as 878: a linha "publicados" passa hoje.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a guarda confere só que o arquivo existe | CT-09 | linha "cópia sem a linha do trecho": reprova; o mutante aceita |
| M2 | `str_contains()` com trecho vazio ou ausente devolve verdadeiro | CT-09 | linhas "sem trecho" e "trecho vazio": reprova; o mutante aceita |
| M3 | o trecho casa dentro de comentário (os arquivos do kit citam o que não fazem, `.ai/rules/testes.md`) | CT-09 | linha "só num comentário": reprova; o mutante aceita |
| M4 | prova em arquivo fora do git, cujo link para o commit daria 404 | CT-09 | linha `bootstrap/cache/packages.php`: reprova; o mutante aceita |
| M5 | a prova da peça de plugin cita o `register()` do plugin em `vendor/`, que existe no disco e não está no commit | CT-09 | linha `vendor/shuvroroy/…`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-23) | a guarda confere a prova só das ligações de registro, e a prova velha de uma ligação de uso (`UserResource` → `User`) passa | CT-09 | "conferiu a prova de tantas ligações quantas o modelo tem": a conta fica abaixo do total e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-06) | a prova cita uma view Blade, um stub ou um JS, e o trecho só existe dentro de `{{-- --}}`, `<!-- -->` ou `//`, que a guarda de PHP não reconhece | CT-09 | linha do `convites-recebidos.blade.php`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-07) | o número de linha fica guardado fora do caminho: num campo do modelo, ou no `sources` do arquivo de diagrama | CT-09 | linhas do campo `linha` e do `sources` com `line`: reprova; o mutante aceita |
| M9 — step 9 (A7) | a guarda da R9 confere só a prova das ligações, e a evidência de peça e a âncora de diagrama escrito à mão envelhecem sem vermelho no PR que só toca `app/`, `database/`, `config/`, o CHANGELOG ou `.github/` | CT-53 | linhas da evidência do `UserResource`, da migração que não existe, do trecho fora do CHANGELOG e da relação só em comentário: reprova; e, nos publicados, "conferiu tantas âncoras quantas o modelo de peças e os diagramas escritos à mão têm"; o mutante aceita |
| M10 — step 9 (A3) | o build dá à prova a linha da primeira ocorrência do trecho no texto cru, e ela está num comentário | CT-52 | linha do `Locker::class`: o item de prova sai com a linha 25, de comentário, e reprova; o mutante aceita |
| M11 — step 9 (A10) | a evidência de peça traz uma chave a mais (`line`, `end_line`, `path`) que vence a que o build calcula, e a guarda não olha o modelo de peças | CT-09 | linhas do `line`, do `end_line` e do `path` na evidência do `UserResource`: reprova; o mutante aceita |
| M12 — step 9 (A7; Q20) | o trecho de uma âncora em YAML, `.env` ou Markdown só existe num comentário, e a guarda, sem regra para esses tipos, o acha no arquivo inteiro | CT-53 | linha do `tempos-ci` com o trecho só numa linha `#` do `ci.yml`: reprova; o mutante aceita |
| M13 — step 9 (re-revisão, RR-03) | o lexer de comentários do build não conhece heredoc nem nowdoc: o apóstrofo dentro de um nowdoc abre uma string que engole o `//` de um comentário abaixo, e a linha da prova é a do comentário | CT-52 | linha do nowdoc com apóstrofo: o item de prova aponta a linha 28, a do comentário, e não a 31, a do código; reprova; o mutante aceita |

---

## Regra R10 — A prova é a da ligação: está no arquivo da origem, num trait ou concern que a origem usa, no provider do painel (descoberta, plugin — direto ou pelo invólucro do kit — ou configuração), na configuração global que o kit faz do pacote que registra a peça sozinho, no método de contrato do alvo ou, quando a origem é peça de pacote, num arquivo do kit que nomeia a origem; o trecho é o dessa ligação; e as duas pontas são peças

> `RQ-24`, `P-03`, `P-07` · perfil **padrão** · técnica: **EP** (forma da prova × arquivo) + controles em cópia

*(alterado em 2026-11-20: ADV-07 e ADV-08)* Fato medido pela sessão nos grafos da varredura do step 3: 687 de 748 ligações (92 %) têm a prova no arquivo da peça de origem. As demais ficam:
- num trait ou concern que a origem usa;
- no arquivo do alvo, quando o trecho é o método de contrato que o framework chama nele;
- ou em `vendor/`, que a P-07 proíbe; o modelo troca por um trecho do kit que liga ou configura a peça.

As formas válidas de a prova ser a da ligação são estas. Qualquer outro arquivo reprova, mesmo com um trecho que nomeia a peça usada.
- **Arquivo da origem, com o nome da peça usada.** O trecho nomeia a peça (a primeira linha dos Exemplos).
- **Trait ou concern que a origem usa.** O `Projeto` usa o `BelongsToTenant` (`app/Models/Projeto.php:BelongsToTenant:38`), e o trait liga ao `Tenant` (`app/Traits/BelongsToTenant.php:belongsTo:84`).
- **Provider do painel, nas ligações do painel.** São quatro formas *(três até o step 9)*.
  - *Descoberta por pasta.* É a regra nos três painéis (`app/Providers/Filament/AdminPanelProvider.php:discoverResources:147`). A prova de "o painel admin registra `UserResource`" não nomeia o recurso: é a linha de descoberta cuja pasta o contém.
  - *Registro do plugin que traz a peça* (Q9). A peça que um plugin traz só aparece no código versionado como o registro do plugin no provider. A prova de "o painel infra registra `HealthCheckResults`" é o `FilamentSpatieLaravelHealthPlugin::make()` (`app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`), porque o `register()` desse plugin registra a página (`vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php:pages:36`).
    - *(alterado em 2026-11-21 — step 9, achado A9: leitura da forma, decidida pela sessão)* Vale também para o middleware que o plugin traz no `authMiddleware` do painel: o `Locker`, pelo `Lockscreen` (`vendor/marjose123/filament-lockscreen/src/Lockscreen.php:authMiddleware:42`), e o `MustTwoFactor`, pelo `BreezyCore` (`vendor/jeffgreco13/filament-breezy/src/BreezyCore.php:authMiddleware:62`). A forma é avaliada sobre o plugin como o painel vivo o configura: o `BreezyCore` só põe o `MustTwoFactor` com o 2FA ligado. É leitura da forma, e não forma nova.
  - *Invólucro do kit que registra o plugin* *(forma nova em 2026-11-21 — step 9, achado A9, decidida pela sessão)*. O provider do painel registra o plugin por uma classe de `app/`: o trecho chama `X::make()` ou `X::plugin()`, `X` resolve, pelos `use` do provider, para uma classe de `app/`, e o arquivo de `X` nomeia a classe da peça. No código de hoje: `PluginDoDock::make()` (`app/Providers/Filament/AdminPanelProvider.php:PluginDoDock:324`), cujo arquivo nomeia o `QuickActionDockPlugin` (`app/Filament/Dock/PluginDoDock.php:QuickActionDockPlugin:24`); e `TutorialGuiado::plugin()` (`app/Providers/Filament/AdminPanelProvider.php:TutorialGuiado:313`), cujo arquivo nomeia o `FilamentOnboardingPlugin` (`app/Support/TutorialGuiado.php:FilamentOnboardingPlugin:15`). O mesmo nos três painéis.
  - *Configuração do painel* (ADV-08). O framework acrescenta peças a partir da configuração do painel: o alias `panel:{id}` sai do `->id()` (`app/Providers/Filament/AdminPanelProvider.php:id:77`), e o `IdentifyTenant` sai do `->tenant()` (`app/Providers/Filament/AppPanelProvider.php:tenant:660`). A prova é o trecho que configura, no provider desse painel.
- **Configuração global do pacote que registra a peça sozinho** *(forma nova em 2026-11-21 — step 9, achado A9, decidida pela sessão)*. O pacote registra a peça em todo painel por conta própria — o service provider do `bezhansalleh/filament-language-switch` põe o `SwitchLanguageLocale` no middleware de cada painel (`vendor/bezhansalleh/filament-language-switch/src/LanguageSwitchServiceProvider.php:SwitchLanguageLocale:67`) —, e a prova é a configuração global que o kit faz dele: `Y::configureUsing(` num arquivo de `app/Providers` ou num trait dele, com `Y` classe do mesmo pacote da peça. No código de hoje: `LanguageSwitch::configureUsing(` (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configureUsing:210`), no trait que o `KitServiceProvider` usa (`app/Providers/KitServiceProvider.php:ConfiguraFilamentGlobal:79`).
- **Método de contrato do alvo.** Quando o framework chama um método no alvo, o trecho é esse método, no arquivo do alvo: o `canAccessPanel()` do `User` (`app/Models/User.php:canAccessPanel:174`). O método é um da lista fechada, abaixo dos Exemplos.
- **Arquivo do kit que liga a peça de pacote** (Q19, decidida — D26). Quando a origem é peça de pacote, a prova é o trecho do kit que liga a origem ao destino, num arquivo versionado fora de `vendor/`, em qualquer pasta.
  - O trecho nomeia a peça de destino, pela classe, pelo alias ou pela chave.
  - O arquivo nomeia a peça de origem, pela classe, pelo plugin ou pela chave de config do pacote.
  - `vendor/` e saída de comando (`artisan route:list`) nunca valem.
  - Exemplos do código de hoje: o `bind` do `LockerScreen` do pacote para a `TelaBloqueio` (`app/Providers/AppServiceProvider.php:bind:23`); o mapa das policies dos models de pacote (`app/Support/PoliciesDeVendor.php:ExceptionPolicy:67`); e o `Event::listen` dos eventos do `laravel/ai` para o `RegistrarAiRun` (`app/Providers/KitServiceProvider.php:listen:529`). O terceiro fica fora dos Exemplos do CT-10: as pontas dele são eventos, e evento não está na tabela de tipos da R8.
  - Medido pela sessão nas 288 ligações da varredura cuja origem não é classe do kit nem painel: provider de painel 116, sem prova 63, `vendor/` 57, outro provider 11, `database/` 11, `config/` 9, `routes/` 6, `app/Filament` 4, saída de `artisan` 4, `app/Support` 3, model 2, view 1 e stub 1. Aceitar só `config/` e provider deixaria vermelhas as ligações de `routes/` e de `app/Support`.

Sem a partição do plugin e a da configuração, toda ligação de registro que a R11 exige para peça de plugin, para `panel:{id}` e para `IdentifyTenant` ficaria vermelha. Sem a quinta forma, ficariam vermelhas as ligações cuja origem é peça de pacote. *(alterado em 2026-11-21 — step 9, achado A9)* Sem a leitura do middleware, ficariam vermelhas as 6 ligações de registro do `Locker` e do `MustTwoFactor`; sem o invólucro do kit, as 6 do dock e do tutorial; e sem a configuração global do pacote, as 3 do `SwitchLanguageLocale`.

```gherkin
  Regra: O trecho de cada prova é o da ligação que ela prova

    Esquema do Cenário: [CT-10] a prova é a da ligação: está num arquivo que a ligação permite, e o trecho é o dela
      Dado a ligação "<ligação>", com a prova em "<arquivo>" e o trecho "<trecho>"
      Quando a guarda do mapa confere se a prova é a dessa ligação
      Então o resultado é "<resultado>"

      Exemplos:
        | ligação                                                  | arquivo                                                | trecho                                                         | resultado                    | # partição                              |
        | `UserResource` usa o model `User`                        | `app/Filament/Admin/Resources/Users/UserResource.php`  | `protected static ?string $model = User::class;`               | aceita                       | arquivo da origem, nomeia a peça usada  |
        | `Projeto` usa o model `Tenant`                           | `app/Traits/BelongsToTenant.php`                       | `return $this->belongsTo(Tenant::class);`                      | aceita                       | trait que a origem usa                  |
        | o painel admin registra `UserResource`                   | `app/Providers/Filament/AdminPanelProvider.php`        | `->discoverResources(in: app_path('Filament/Admin/Resources')` | aceita                       | descoberta que alcança a pasta          |
        | o painel infra registra a página `HealthCheckResults`    | `app/Providers/Filament/InfraPanelProvider.php`        | `FilamentSpatieLaravelHealthPlugin::make()`                    | aceita                       | registro pelo plugin que traz a peça    |
        | o painel admin registra `panel:admin`                    | `app/Providers/Filament/AdminPanelProvider.php`        | `->id('admin')`                                                | aceita                       | configuração do painel                  |
        | o painel app registra `IdentifyTenant`                   | `app/Providers/Filament/AppPanelProvider.php`          | `->tenant(Tenant::class, slugAttribute: 'slug')`               | aceita                       | configuração do painel                  |
        | o painel admin usa o model `User`                        | `app/Models/User.php`                                  | `public function canAccessPanel(Panel $panel): bool`           | aceita                       | método de contrato do alvo              |
        | a página `LockerScreen` do pacote do lockscreen resolve para a `TelaBloqueio` | `app/Providers/AppServiceProvider.php` | `$this->app->bind(LockerScreen::class, TelaBloqueio::class);` | aceita | arquivo do kit que liga a peça de pacote |
        | o model `Exception` do `filament-exceptions` usa a `ExceptionPolicy` | `app/Support/PoliciesDeVendor.php` | `Exception::class                      => ExceptionPolicy::class,` | aceita | par de mapeamento: a linha inteira |
        | `UserResource` usa o model `User`                        | `app/Filament/Exports/UserExporter.php`                | `protected static ?string $model = User::class;`               | reprova, nomeando a ligação  | outro arquivo, que nomeia a peça        |
        | o painel infra registra o `UserResource` do `/admin`     | `app/Providers/Filament/InfraPanelProvider.php`        | `->discoverResources(in: app_path('Filament/Infra/Resources')` | reprova, nomeando a ligação  | descoberta que não alcança              |
        | o painel infra registra a página `HealthCheckResults`    | `app/Providers/Filament/InfraPanelProvider.php`        | `FilamentJobsMonitorPlugin::make()`                            | reprova, nomeando a ligação  | registro de plugin que não traz a peça  |
        | o painel admin registra `panel:admin`                    | `app/Providers/Filament/AppPanelProvider.php`          | `->id('app')`                                                  | reprova, nomeando a ligação  | configuração de outro painel            |
        | o painel admin registra `IdentifyTenant`                 | `app/Providers/Filament/AppPanelProvider.php`          | `->tenant(Tenant::class, slugAttribute: 'slug')`               | reprova, nomeando a ligação  | configuração de outro painel            |
        | `UserResource` usa o model `Tenant`                      | `app/Traits/BelongsToTenant.php`                       | `return $this->belongsTo(Tenant::class);`                      | reprova, nomeando a ligação  | trait que a origem não usa              |
        | o painel admin usa o model `Convite`                     | `app/Models/Convite.php`                               | `public function situacao(): string`                           | reprova, nomeando a ligação  | método fora da lista de contrato        |
        | o model `MailLog` do pacote usa a `ExceptionPolicy`      | `app/Support/PoliciesDeVendor.php`                     | `Exception::class                      => ExceptionPolicy::class,` | reprova, nomeando a ligação | par que não nomeia a origem |
        | o painel admin registra o plugin do Shield               | `app/Providers/Filament/AdminPanelProvider.php`        | `AuthDesignerPlugin::make()`                                   | reprova, nomeando a ligação  | prova de outra ligação                  |
        | a página `HealthCheckResults` do pacote do Health resolve para a `TelaBloqueio` | `app/Providers/AppServiceProvider.php` | `$this->app->bind(LockerScreen::class, TelaBloqueio::class);` | reprova, nomeando a ligação | arquivo que não nomeia a origem |
        | o plugin `FilamentSpatieLaravelHealthPlugin` traz a página `HealthCheckResults` | `vendor/shuvroroy/filament-spatie-laravel-health/src/FilamentSpatieLaravelHealthPlugin.php` | `protected string $page = HealthCheckResults::class;` | reprova, nomeando o arquivo | arquivo de `vendor/` |
        | a página `LockerScreen` do pacote do lockscreen resolve para a `TelaBloqueio` | a saída de `php artisan route:list` | a linha da rota da tela de bloqueio | reprova, nomeando a ligação | saída de comando |
        | `UserResource` usa o model `User`                        | `app/Filament/Admin/Resources/Users/UserResource.php`  | `class UserResource`                                           | reprova, nomeando a ligação  | nome só dentro de outro nome            |
        | `UserResource` usa `User`, numa cópia do modelo sem a peça `User` | `app/Filament/Admin/Resources/Users/UserResource.php` | `protected static ?string $model = User::class;`      | reprova, nomeando `User`     | ponta que não é peça                    |
        | o painel admin registra o middleware `Locker`               | `app/Providers/Filament/AdminPanelProvider.php`        | `Lockscreen::make()`                                           | aceita                       | plugin que traz o middleware *(step 9)* |
        | o painel admin registra `QuickActionDockPlugin`             | `app/Providers/Filament/AdminPanelProvider.php`        | `PluginDoDock::make(),`                                        | aceita                       | invólucro do kit *(step 9)*             |
        | o painel admin registra `FilamentOnboardingPlugin`          | `app/Providers/Filament/AdminPanelProvider.php`        | `TutorialGuiado::plugin(),`                                    | aceita                       | invólucro do kit *(step 9)*             |
        | o painel admin registra o middleware `SwitchLanguageLocale` | `app/Providers/Concerns/ConfiguraFilamentGlobal.php`   | `LanguageSwitch::configureUsing(`                              | aceita                       | configuração global do pacote *(step 9)* |
        | o painel admin registra o middleware `MustTwoFactor`        | `app/Providers/Filament/AdminPanelProvider.php`        | `Lockscreen::make()`                                           | reprova, nomeando a ligação  | plugin que não traz o middleware *(step 9)* |
        | o painel admin registra `QuickActionDockPlugin`             | `app/Providers/Filament/AdminPanelProvider.php`        | `TutorialGuiado::plugin(),`                                    | reprova, nomeando a ligação  | invólucro cujo arquivo não nomeia a peça *(step 9)* |
        | o painel admin registra o middleware `SwitchLanguageLocale` | `app/Providers/Concerns/ConfiguraFilamentGlobal.php`   | `Toggle::configureUsing(fn (Toggle $toggle): Toggle => $this->configuraToggle($toggle));` | reprova, nomeando a ligação | configuração global de classe de outro pacote *(step 9)* |
        | cada ligação do modelo publicado, uma por uma                | o arquivo da prova dela                                 | o trecho da prova dela                                         | aceita, em todas, e a guarda conferiu tantas ligações quantas o modelo tem | publicado |
```

*(alterado em 2026-11-21 — step 9, achado A9)* **A linha "publicado".** Até aqui o CT-10 só tinha os Exemplos acima, e nenhuma linha passava as ligações publicadas pela guarda; toda outra guarda do mapa tem a linha "publicado". A linha nova passa cada ligação do modelo pela mesma função das linhas de controle.

**Hoje o dado reprova o oráculo.** Medido no commit `168d1827`, com a mesma função das linhas de controle sobre as 413 ligações: 16 reprovam pela forma, todas ligações de registro de painel provadas no provider por uma linha que não nomeia a peça, nem é a descoberta que alcança a pasta dela, nem o registro do plugin que a traz:
- `painel:admin`, `painel:app` e `painel:infra` → `Locker`, com o trecho `Lockscreen::make()` (o plugin que traz o middleware) — 3;
- os três painéis → `MustTwoFactor`, com `BreezyCore::make()` (o plugin que traz o middleware) — 3;
- os três painéis → `QuickActionDockPlugin`, com `PluginDoDock::make(),` (um invólucro do kit) — 3;
- os três painéis → `FilamentOnboardingPlugin`, com `TutorialGuiado::plugin(),` (um invólucro do kit) — 3;
- os três painéis → `SwitchLanguageLocale`, com um `Toggle::configureUsing(…)` de `app/Providers/Concerns/ConfiguraFilamentGlobal.php` (o `configureUsing` global, fora do provider do painel) — 3;
- `painel:app` → `Tenant`, com `$tenant = Filament::getTenant();` no `AppPanelProvider` — 1.

*(alterado em 2026-11-21 — step 9: decisão da sessão sobre as 16, por grupo)*
- **Âncora errada do modelo — vermelho de implementação (4).** `painel:app` → `Tenant` passa à linha do `AppPanelProvider` que nomeia a classe, a do `instanceof Tenant` (`app/Providers/Filament/AppPanelProvider.php:instanceof:211`); os três painéis → `SwitchLanguageLocale` passam à linha `LanguageSwitch::configureUsing(` (`app/Providers/Concerns/ConfiguraFilamentGlobal.php:configureUsing:210`). A âncora de hoje, `Toggle::configureUsing`, nada tem a ver com a peça. Medido no commit `168d1827`: o trecho `instanceof Tenant` sozinho aparece antes num comentário do mesmo arquivo (`app/Providers/Filament/AppPanelProvider.php:instanceof:209`), e a linha que o build daria a ele seria a do comentário, que o CT-52 reprova; a âncora nova precisa de um trecho cuja primeira ocorrência seja a do código, como a linha inteira do `if`.
- **Formas novas da R10 — correção do `04` (6 + 3).** O invólucro do kit que registra o plugin aceita as 6 do dock e do tutorial; a configuração global do pacote aceita as 3 do `SwitchLanguageLocale`, com a âncora corrigida acima. Cada forma tem a sua linha de controle, que reprova: o invólucro cujo arquivo não nomeia a peça, e o `configureUsing` de classe de outro pacote — a âncora de hoje do `SwitchLanguageLocale`.
- **Leitura da forma do plugin no middleware (6).** O `Locker` e o `MustTwoFactor` dos três painéis passam pelo registro do plugin que os traz no `authMiddleware`, avaliado como o painel vivo configura o plugin. A linha de controle: o plugin que não traz o middleware reprova.
- **Depois da implementação** — as âncoras do modelo e as duas formas e a leitura na guarda —, a linha "publicado" aceita as 413. O oráculo não foi ajustado para o dado passar: as 16 reprovam hoje.

Cada linha inválida tem um único defeito. Os trechos dela estão no arquivo, e a R9 os aceitaria, fora as linhas de `vendor/` e da saída de comando, que a R9 também reprova; a quinta forma as reprova por conta própria (D26). O `UserExporter` traz o mesmo trecho do `UserResource` (`app/Filament/Exports/UserExporter.php:$model:24`), e por isso a única diferença da linha dele é o arquivo.

*(alterado em 2026-11-20: ADV2-08 e ADV2-09)*
- **O trait vale só quando a origem o usa.** Medido: o `Projeto` usa o `BelongsToTenant`, e o `UserResource`, não.
- **O método de contrato é um da lista fechada,** a das interfaces de `vendor/filament/filament/src/Models/Contracts` no Filament instalado: `canAccessPanel` (`FilamentUser`), `canAccessTenant` e `getTenants` (`HasTenants`), `getDefaultTenant` (`HasDefaultTenant`), `getCurrentTenantLabel` (`HasCurrentTenantLabel`), `getFilamentName` (`HasName`) e `getFilamentAvatarUrl` (`HasAvatar`). Medido: 6 interfaces e 7 métodos; o `User` implementa `FilamentUser`, `HasAvatar` e `HasTenants`. O `situacao()` do `Convite` (`app/Models/Convite.php:situacao:619`) não está na lista.
- **Par de mapeamento** (D27). Quando o trecho é um par `X::class => Y::class`, como no `app/Support/PoliciesDeVendor.php`, ele é a linha inteira, com os espaços de alinhamento, e nomeia as duas pontas. Fora desse caso vale a D26. Conferido o literal: a linha 67, sem o recuo, é `Exception::class`, 22 espaços e `=> ExceptionPolicy::class,`, e a 68 mapeia o `MailLog`. Medido pela sessão nas 116 provas em provider de painel: a linha nomeia as duas pontas em só 3, e por isso a regra das duas pontas vale só para o par de mapeamento.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | prova copiada de outra ligação do mesmo arquivo | CT-10 | linha `AuthDesignerPlugin::make()`: reprova; o mutante aceita |
| M2 | qualquer linha de descoberta vale para qualquer recurso | CT-10 | linha da descoberta do `/infra`: reprova; o mutante aceita |
| M3 | "nomeia a peça" conferido por substring do nome curto | CT-10 | linha `class UserResource` para a peça `User`: reprova; o mutante aceita |
| M4 | ligação com ponta solta: a peça saiu do modelo, e a ligação ficou | CT-10 | linha da cópia sem a peça `User`: reprova nomeando `User`; o mutante aceita |
| M5 | qualquer `::make()` de plugin do mesmo provider vale como prova de peça de plugin | CT-10 | linha `FilamentJobsMonitorPlugin::make()` para `HealthCheckResults`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-07) | a prova sai de outro arquivo versionado cujo trecho nomeia a peça usada (o exportador, uma policy) | CT-10 | linha do `UserExporter`: reprova; o mutante aceita, porque só olha o destino |
| M7 — revisão adversarial (ADV-08) | a peça que o framework acrescenta pela configuração do painel fica dispensada de prova, ou aceita a configuração de outro painel | CT-10 | linhas do `->id('app')` e do `->tenant()` do `/app` como prova do `/admin`: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-07; D26) | na ligação cuja origem é peça de pacote, vale qualquer arquivo do kit cujo trecho nomeia o destino, mesmo sem nomear a origem | CT-10 | linha do `HealthCheckResults` com o `bind` do `AppServiceProvider`: reprova; o mutante aceita |
| M9 — revisão adversarial (ADV-07; D26) | a prova da ligação cuja origem é peça de pacote fica no `vendor/` dela, ou na saída de `artisan route:list` | CT-10 | linhas de `vendor/` e da saída de comando: reprova; o mutante aceita |
| M10 — revisão adversarial (ADV2-08) | a guarda aceita qualquer trait como prova, sem conferir se a origem o usa | CT-10 | linha "`UserResource` usa o model `Tenant`", com o `BelongsToTenant`: reprova; o mutante aceita |
| M11 — revisão adversarial (ADV2-08) | a guarda aceita qualquer método no arquivo do alvo, sem conferir se é de contrato | CT-10 | linha do `situacao()` do `Convite`: reprova; o mutante aceita |
| M12 — revisão adversarial (ADV2-09) | num arquivo de mapeamento, o trecho nomeia só o destino, e toda origem que o arquivo mapeia passa com qualquer policy dele | CT-10 | linha do `MailLog` com a linha do `Exception`: reprova; o mutante aceita |
| M13 — step 9 (A9) | a guarda da R10 só roda sobre os Exemplos, e a prova de uma ligação publicada que não é a dela — um registro de painel provado por uma linha do provider que não nomeia a peça — passa | CT-10 | linha "cada ligação do modelo publicado": as 16 de hoje reprovam; o mutante, que nunca as confere, aceita |
| M14 — step 9 (A9; forma nova) | o invólucro do kit vale como prova de qualquer peça de plugin, sem conferir que o arquivo dele nomeia a peça | CT-10 | linha do `QuickActionDockPlugin` com `TutorialGuiado::plugin(),`: reprova; o mutante aceita |
| M15 — step 9 (A9; forma nova) | qualquer `configureUsing` global vale como prova da peça que um pacote registra sozinho, mesmo de classe de outro pacote | CT-10 | linha do `SwitchLanguageLocale` com `Toggle::configureUsing(…)`: reprova; o mutante aceita |
| M16 — step 9 (A9; leitura da forma) | o registro de um plugin vale como prova de qualquer middleware do painel, sem conferir que o plugin, como o painel o configura, traz aquele middleware | CT-10 | linha do `MustTwoFactor` com `Lockscreen::make()`: reprova; o mutante aceita |

---

## Regra R11 — Tudo que o painel registra tem ligação de registro no modelo, e o modelo não dá a um modo o que o painel não registra nele

> `P-05`, `RQ-08`, `RQ-10`, `RQ-11`, `P-03` · perfil **padrão** · técnica: **tabela de decisão** — 3 painéis × 2 modos × 5 tipos de registro (recurso, página, widget, plugin, middleware) = 30 células, cada uma nas duas direções. O CT-11 cobre as 15 do modo sem tenant, o CT-12 as 15 do modo com tenant, e o CT-13 controla cada direção.

A completude (painel → modelo) não se amarra ao modo. A peça registrada nos dois modos e aberta só num deles — como a tela de organizações do `/admin` — tem a ligação de registro no modelo. A disponibilidade dela (a marca e o diagrama de modo) está estacionada na Q11 *(alterado em 2026-11-20: ADV-31; era "é da R30")*. O modo só é afirmado na direção modelo → painel. *(alterado em 2026-11-23 — Adendo 2: a regra não muda. Pela proposta da colisão a, a completude da P-05 é do modelo, e a ligação de registro do `TenantResource` continua nos dois modos; a condição é da R30, e o diagrama de modo, da R41 e da R34)*

```gherkin
  Regra: O que o painel registra está no modelo, e o modelo não dá a um modo o que o painel não registra nele

    Esquema do Cenário: [CT-11] sem tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, sem tenancy
      Quando a guarda do mapa compara o que o painel registra com as ligações de registro do modelo
      Então todo recurso, página, widget, plugin e middleware que o painel registra é destino de uma ligação de registro dele no modelo
      E toda peça que o modelo dá a ele no modo sem tenant é algo que ele registra
      E <registrado> está nos dois lados
      E <fora> não está no registro dele, e o modelo não o dá a ele no modo sem tenant

      Exemplos:
        | painel | registrado                                                                                                  | fora                                                                    |
        | admin  | o plugin `FilamentShieldPlugin`, o middleware `panel:admin` e o recurso `TenantResource`, registrado mesmo sem tenancy | `IdentifyTenant`, que `getTenantMiddleware()` devolve mesmo sem tenancy |
        | app    | o plugin `FilamentSearchSpotlightPlugin` e a página `TelaLogin`, que o painel liga pela configuração de login | `DefinirTenantDePermissoes` e `IdentifyTenant` |
        | infra  | o plugin `FilamentSpatieLaravelHealthPlugin` e a página `HealthCheckResults`, que ele registra               | `IdentifyTenant`                                                        |

    Esquema do Cenário: [CT-12] com tenant, tudo que o painel registra está no modelo, e o modelo não dá a esse modo nada além disso
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis, com tenancy "<tenancy>"
      Quando a guarda do mapa compara o que o painel registra com as ligações de registro do modelo
      Então todo recurso, página, widget, plugin e middleware que o painel registra é destino de uma ligação de registro dele no modelo
      E toda peça que o modelo dá a ele no modo com tenant é algo que ele registra
      E <registrado> está nos dois lados
      E <fora> não está no registro dele, e o modelo não o dá a ele no modo com tenant

      Exemplos:
        | painel | tenancy | registrado                                                                    | fora                                                                                    |
        | app    | sim     | os middlewares de organização `IdentifyTenant` e `DefinirTenantDePermissoes` | o `UserResource` do `/admin`                                                            |
        | admin  | não     | o plugin `FilamentShieldPlugin` e o recurso `TenantResource`                  | `IdentifyTenant`: o modo está ligado, mas o painel não tem tenancy                      |
        | infra  | não     | o plugin `FilamentSpatieLaravelHealthPlugin` e a página `HealthCheckResults`  | `IdentifyTenant`: o modo está ligado, mas o painel não tem tenancy                      |

    Esquema do Cenário: [CT-13] a guarda do registro reprova o modelo que esquece peça, que sobra peça ou que troca o modo
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o modelo "<mundo>"
      Quando a guarda do mapa compara o registro dos três painéis com o modelo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                         | resultado                                     | # direção          |
        | publicado                                                                                                     | aceita                                        | —                  |
        | cópia sem a ligação "o painel admin registra `FilamentShieldPlugin`"                                          | reprova, nomeando o plugin e o painel admin   | painel ⊄ modelo    |
        | cópia com a ligação "o painel admin registra `FilamentSpatieLaravelHealthPlugin`", que só o `/infra` registra  | reprova, nomeando o plugin e o painel admin   | modelo ⊄ painel    |
        | cópia que dá `DefinirTenantDePermissoes` ao painel app no modo sem tenant                                     | reprova, nomeando o middleware                | modo trocado       |
        | cópia sem a ligação "o painel app registra `TelaLogin`"                                                     | reprova, nomeando a `TelaLogin` e o painel app | painel ⊄ modelo    |
```

*(alterado em 2026-11-20: ADV2-10, D30)* O registro vivo inclui as páginas que o painel liga pela configuração, e não pelo `->pages()` (Setup Global). Medido com os getters do Filament, nos dois modos: `TelaLogin`, `TelaRecuperarSenha` e `TelaRedefinirSenha` nos três painéis, e `RegistroPorConvite` e `TelaVerificacaoDeEmail` no `/app`, estão fora do `getPages()`; com tenant, o kit não liga página de cadastro nem de perfil da organização (os dois getters devolvem nulo).

As âncoras das colunas "registrado" e "fora" vêm do código de hoje:
- `FilamentShieldPlugin::make()` em `app/Providers/Filament/AdminPanelProvider.php:FilamentShieldPlugin:240`;
- `FilamentSearchSpotlightPlugin::make()` em `app/Providers/Filament/AppPanelProvider.php:FilamentSearchSpotlightPlugin:264`;
- `FilamentSpatieLaravelHealthPlugin::make()` em `app/Providers/Filament/InfraPanelProvider.php:FilamentSpatieLaravelHealthPlugin:345`, que nenhum outro provider registra, e a página `HealthCheckResults`, que o plugin registra;
- o `tenantMiddleware` de `DefinirTenantDePermissoes` dentro de `kit.tenancy.enabled`, em `app/Providers/Filament/AppPanelProvider.php:DefinirTenantDePermissoes:670`;
- o `TenantResource`, descoberto nos dois modos e aberto só com tenant (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`).

Elas provam que cada suíte roda no seu modo e que cada guarda leu o registro, e não um conjunto vazio nem a disponibilidade.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* O diagrama vale no modo quando o campo `modos` dele contém o modo; o compartilhado (`sem` e `com`) vale nos dois. A ligação conta como desenhada quando as duas pontas estão desenhadas, como peça ou como membro de agrupamento — a mesma regra do construtor. *(alterado em 2026-11-23 — Adendo 2: a ponta só está desenhada no diagrama do modo em que o painel a registra e ela fica disponível, com a chave ligada — a definição do Setup Global)*

*(alterado em 2026-11-21 — step 9, achado A15)* O CT-13 controla a guarda do registro só no modo sem tenant, e a guarda da suíte `Tenancy` é outra função: ela filtra o lado do modelo pelos cinco tipos de registro antes de comparar, e a sobra de outro tipo passa. O CT-55 dá a ela os controles, no modo com tenant.

```gherkin
    Esquema do Cenário: [CT-55] com tenant, a guarda do registro reprova o modelo que sobra peça de qualquer tipo, que esquece peça ou que troca o painel
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o modelo "<mundo>"
      Quando a guarda do mapa compara o registro dos três painéis com o modelo, no modo com tenant
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                                                       | resultado                                                  | # direção                  |
        | publicado                                                                                                                                   | aceita                                                     | —                          |
        | cópia com a ligação "o painel app registra `App\Livewire\InformacoesPessoais`", só no modo com tenant — uma peça do tipo Livewire, que o painel não registra | reprova, nomeando `InformacoesPessoais` e o painel app | modelo ⊄ painel, fora dos cinco tipos |
        | cópia sem a ligação "o painel app registra `IdentifyTenant`" no modo com tenant                                                            | reprova, nomeando `IdentifyTenant` e o painel app          | painel ⊄ modelo            |
        | cópia que dá `DefinirTenantDePermissoes` ao painel admin no modo com tenant                                                                 | reprova, nomeando `DefinirTenantDePermissoes` e o painel admin | modelo ⊄ painel, painel trocado |
```

- **Por que na suíte `Tenancy`.** A ligação só do modo com tenant é invisível à guarda da suíte do kit, que roda sem tenant; e o modo com tenant só existe com o `Tests\TenancyTestCase` (G3).
- **Medido no commit `168d1827`:** `App\Livewire\InformacoesPessoais` é peça do tipo Livewire no modelo, e nenhum painel tem ligação de registro para peça desse tipo: os registros de painel do modelo são 37 páginas, 20 recursos, 29 widgets, 47 middlewares e 56 plugins. A linha "publicado" é a do CT-12, e passa hoje; a linha do Livewire reprova a guarda de hoje, que a aceita.
- **O docblock da suíte `Tenancy`** muda para dizer o que os datasets têm (Q26, decidida pela sessão em 2026-11-21): o CT-30 e o CT-44 não ganham controle, e o achado A15 fica coberto pelo CT-55 e pela linha nova do CT-38.
- **Estouro do teto.** A R11 passa a ter quatro cenários, um acima do teto do perfil padrão. O gate vence o teto: o CT-55 é o único matador do M7, e a guarda da suíte `Tenancy` é uma função própria, que os controles do CT-13 não exercitam.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o registro do modelo é uma lista escrita à mão que esquece um plugin, ou a página que um plugin registra | CT-13, CT-11 | CT-13, linha "sem a ligação do Shield": reprova nomeando o plugin. No publicado, o CT-11, linha "infra", reprova a falta de `HealthCheckResults`. O mutante aceita |
| M2 | peça a mais: um registro de outro painel, ou de peça que saiu | CT-13 | linha "admin registra o plugin do Health": reprova; o mutante aceita |
| M3 | um modo só: o modelo não tem o que só o modo com tenant registra | CT-12 | linha "app": os dois middlewares de organização estão no modelo; o mutante não os tem, e a guarda reprova |
| M4 | o middleware de organização é contado pela chave da instalação, ou sem `hasTenancy()`, e `IdentifyTenant` entra no registro de painel sem tenancy | CT-11, CT-12 | CT-11, linhas admin, app e infra, e CT-12, linhas admin e infra: `IdentifyTenant` fora dos dois lados. O mutante o põe no modelo, e a guarda mutante o conta no painel |
| M5 | a guarda filtra o lado do painel: deixa fora o alias `panel:{id}`, que não é classe, ou a peça que só abre com tenant (compara a disponibilidade, não o registro) | CT-11 | linha "admin": `panel:admin` e `TenantResource` nos dois lados; o modelo do mutante pode omiti-los, e a guarda mutante não reclama |
| M6 — revisão adversarial (ADV2-10) | o registro vivo usa só o `getPages()`, e as páginas que o painel liga pela configuração (login, cadastro, recuperação de senha, verificação de e-mail) ficam fora do modelo | CT-13, CT-11 | CT-13, linha "sem a ligação do `TelaLogin`": reprova; e, no publicado, o CT-11, linha "app", exige a `TelaLogin`; o mutante aceita |
| M7 — step 9 (A15) | na suíte `Tenancy`, a guarda do registro filtra o lado do modelo pelos cinco tipos de registro, e o modelo que dá ao painel, só com tenant, o registro de uma peça de outro tipo passa | CT-55 | linha do `InformacoesPessoais` registrado no painel app só com tenant: reprova; o mutante aceita |

---

## Regra R12 — Toda ligação do modelo está desenhada em diagrama publicado, direto ou por agrupamento que nomeia os membros num card do diagrama ou no rótulo dele; menção em texto não conta

> `P-10`, `RQ-08` · perfil **padrão** · técnica: **EP** (forma da aresta) + controles em cópia

*(alterado em 2026-11-20: ADV-15 e fatos de plataforma medidos pela sessão)* Nos mapas de arquitetura, a aresta tem três formas:
- **direta**, uma ligação do modelo;
- **de agrupamento**, uma caixa que nomeia os membros, e a aresta representa as ligações entre membros;
- **de porta**, uma caixa "→ outro mapa", que representa as ligações cujo outro lado está no mapa que a porta nomeia.

A P-10 conta como desenho a aresta direta e o agrupamento que nomeia os membros. Por isso:
- **O agrupamento nomeia os membros num card do diagrama ou no rótulo da caixa.** O card fica no painel lateral, sempre à vista; o rótulo aparece no zoom padrão. Nomear só no texto secundário da peça agrupadora não conta: é a etiqueta que o zoom esconde (M3).
- **A porta é um agrupamento cujos membros são as peças do outro lado.** Ela conta como desenho da ligação quando nomeia essas peças, num card ou no rótulo. A porta que só nomeia o mapa não desenha a ligação, e a ligação tem de estar desenhada noutro diagrama. Leitura fechada pela P-10 (texto literal): "direto, ou por um agrupamento que nomeia os seus membros".

```gherkin
  Regra: "Todas as ligações" vale para o conjunto dos diagramas publicados, e só aresta conta como desenho

    Esquema do Cenário: [CT-14] toda ligação do modelo aparece desenhada em diagrama publicado, e só aresta conta
      Dado o modelo de ligações e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa procura cada ligação nos diagramas que a página leva
      Então o resultado é "<resultado>"
      E, nos publicados, o total de ligações achadas é o total do modelo

      Exemplos:
        | mundo                                                                                                      | resultado                          | # partição                        |
        | publicados                                                                                                 | aceita                             | direta, ou por agrupamento ou porta que nomeia os membros |
        | cópia em que "o painel admin registra `FilamentShieldPlugin`" só aparece no texto da peça painel admin      | reprova, nomeando a ligação        | menção em texto                   |
        | cópia com um agrupamento dos recursos do `/admin` que não nomeia os membros                                 | reprova, nomeando o agrupamento    | agrupamento sem membros           |
        | cópia com um agrupamento dos recursos do `/admin` que nomeia os membros só no texto secundário da caixa      | reprova, nomeando o agrupamento    | membros só no texto que o zoom esconde |
        | cópia em que uma ligação só é representada por uma porta que nomeia o mapa, e não a peça do outro lado       | reprova, nomeando a ligação        | porta sem os membros              |
        | cópia sem essa ligação em diagrama nenhum                                                                   | reprova, nomeando a ligação        | ausente                           |
        | cópia em que essa ligação só está num diagrama que a página não leva                                        | reprova, nomeando a ligação        | diagrama órfão                    |
        | cópia do modelo com a condição "só com tenant" no `TenantResource`, e dos diagramas em que ele só está num diagrama do `/admin` que vale só sem tenant, e a ligação "o painel admin registra `TenantResource`" em nenhum outro | reprova, nomeando a ligação: a ponta não entra no diagrama sem tenant, e a ligação não está desenhada | ponta indisponível no modo do diagrama |
        | cópia em memória do modelo com a ligação "o painel infra registra `FilamentShieldPlugin`" só no modo sem tenant, e um mundo que é só a árvore `infra-registro`, compartilhada, montada pelo gerador (forma de leitura da G5) e pelo construtor da guarda *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* | reprova, nomeando a ligação, nas duas montagens: a árvore compartilhada vale nos dois modos, e a ligação de um modo só não entra nela — o gerador não desenha o nó do `FilamentShieldPlugin` nem o item de prova da ligação | ligação de registro de um modo só na árvore compartilhada |
        | controle: a mesma cópia, com a ligação nos dois modos *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* | aceita, nas duas montagens: a árvore compartilhada desenha a ligação, e o gerador, o nó e o item de prova dela | a mesma ligação, nos dois modos |
        | cópia em memória do modelo com a ligação nova "a notificação `ConviteDeAcesso` usa o model `Convite`", com a prova da ligação "o model `Convite` usa `ConviteDeAcesso`", só no modo sem tenant, e um mundo que é só o mapa de arquitetura `infra-convites`, compartilhado, montado pelo gerador (forma de leitura da G5) e pelo construtor da guarda *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* | reprova, nomeando a ligação `ConviteDeAcesso` → `Convite`, nas duas montagens: o mapa compartilhado vale nos dois modos, e a ligação de um modo só não entra nele — o gerador não desenha a seta `ConviteDeAcesso` → `Convite` nem o item de prova dela | ligação de um modo só no mapa compartilhado |
        | cópia em memória do modelo com o `ConviteDeAcesso` dado como registrado só no modo sem tenant (o campo `registrada`), e um mundo que é só o `infra-convites`, montado pelo construtor da guarda *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* | reprova, nomeando a ligação "o model `Convite` usa `ConviteDeAcesso`": a ponta registrada num modo só não entra no mapa compartilhado, e a ligação, embora valha nos dois modos, não está desenhada nele | ponta registrada num modo só no mapa compartilhado |
        | controle: a cópia das duas linhas anteriores com a ligação nova nos dois modos e o `ConviteDeAcesso` registrado nos dois, o valor publicado, montada pelo gerador e pelo construtor da guarda *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* | aceita, nas duas montagens, as duas ligações: o mapa compartilhado as desenha, e o gerador, as duas setas e os dois itens de prova | as duas ligações e as duas pontas, nos dois modos |
```

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* No diagrama construído, a ligação desenhada é a que o construtor transforma em aresta: as duas pontas desenhadas (peça ou membro de agrupamento), no painel e no modo do diagrama (a ligação de outro painel ou só do outro modo não conta), como os itens de prova do CT-B02. Expandir os pares pelas caixas de agrupamento sem esse filtro contava ligações do modo com tenant no diagrama sem tenant.

*(alterado em 2026-11-23 — Adendo 2, colisão b)* "Ponta desenhada" segue a definição do Setup Global: a peça entra no diagrama do modo só quando o painel a registra nele **e** ela fica disponível nele, com a chave ligada. O oráculo do CT-14 não muda — toda ligação do modelo desenhada em ao menos um diagrama publicado —, e a ligação de uma peça que só abre com tenant passa a ser desenhada só nos diagramas com tenant. A linha nova controla a guarda que conta a ponta pelo registro. **Medido no HEAD `c642c23c`:** a linha "publicados" passa (o `TenantResource` está hoje em três diagramas compartilhados); a linha nova é cópia, e a guarda de hoje, que conta a ponta pelo registro, a aceitaria.

*(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* **A ligação de um modo só na árvore compartilhada.** Pelo Setup Global ("O diagrama que vale nos dois modos e a ligação de um modo só"), a árvore de registro compartilhada só desenha a ligação `registra` que vale nos dois modos. O defeito está em dois lugares iguais — o gerador e o construtor da guarda guardam a ligação pelo primeiro modo do diagrama —, e por isso a linha monta o mundo duas vezes: pela forma de leitura da G5 (a entrada que `entradaDoArchify` monta para o `infra-registro`, com a ligação acrescentada à cópia em memória do modelo que `carregarModelo()` devolve, como o pedido `ligacoes` de `entradasDoGeradorDoMapa()` já faz) e pelo construtor da guarda. O resultado vale para as duas: a montagem pelo gerador mata o defeito no gerador (M9), e a da guarda, o mesmo defeito no espelho dela (M10), que contaria como desenhada uma ligação que o gerador certo não desenha. A linha de controle, com a ligação nos dois modos, prova que o mundo discrimina: ela é desenhada.
- **Medido no HEAD `1dd48acd`.** A linha da ligação só sem tenant fica vermelha hoje nas duas montagens — a guarda aceitaria a ligação como desenhada: o gerador desenha no `infra-registro` o nó do `FilamentShieldPlugin` e dois itens de card que o nomeiam — a linha dele no card "F-nn de cada peça" e o item de prova "/infra → FilamentShieldPlugin (registra)", com o arquivo, a linha e o trecho `FilamentShieldPlugin::make()` do provider do `/admin` —, e a guarda o contaria como desenhado (causa b, no gerador); o construtor da guarda, copiado do arquivo de teste e executado fora do repositório, também o desenha (causa a, no teste, que o executor ajusta ao escrever a linha). A linha de controle passa nas duas: com a ligação nos dois modos, os dois desenham o plugin. A ligação só com tenant não discrimina — os dois já a deixam fora — e não ganhou linha.

*(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* **O mapa compartilhado: a ligação de um modo só e a ponta registrada num modo só.** Pelo Setup Global ("A mesma regra vale para o mapa de arquitetura e para a ponta"), o mapa de arquitetura compartilhado só desenha a ligação que vale nos dois modos, entre peças registradas nos dois. São dois defeitos, e por isso duas linhas, uma partição inválida em cada:
- **A ligação de um modo só** está em dois lugares iguais — o gerador e o ramo `mapa` do construtor da guarda dão ao diagrama de dois modos o `modo` nulo e não filtram a ligação —, e a linha monta o mundo duas vezes, como a da árvore: pela forma de leitura da G5 (a entrada que `entradaDoArchify` monta para o `infra-convites`, com a ligação acrescentada pelo pedido `ligacoes` de `entradasDoGeradorDoMapa()`) e pelo construtor da guarda. A ligação é nova e vai no sentido contrário ao da publicada, para a seta dela ser outra; só sem tenant basta: a correção que tomasse o primeiro modo do arquivo, ou qualquer um dos modos, ainda a desenharia. A ligação só com tenant não discrimina a correção pelo primeiro modo, e não ganhou linha.
- **A ponta registrada num modo só** só se monta pelo construtor da guarda: o pedido da forma de leitura acrescenta ligações, e não muda peça. O gerador já confere o `registrada` em cada modo (`site/mapa/gerar.mjs`, `entraNosModos`); a guarda, no diagrama de dois modos, só pela disponibilidade (`tests/Pest.php`, `pecaAbreNosModosDoDiagrama`), e contaria como desenhada a ligação de uma ponta que o gerador não desenha.
- **A linha de controle** — as duas ligações e as duas pontas nos dois modos, o valor publicado — prova que o mundo discrimina: as duas montagens desenham as duas ligações.
- **Medido no HEAD `b130c686`.** A linha da ligação só sem tenant fica vermelha na montagem pelo gerador: a entrada do `infra-convites` traz a seta `ConviteDeAcesso` → `Convite` e o item de prova "ConviteDeAcesso → Convite (usa)", com o arquivo `app/Models/Convite.php`, e a guarda a acharia desenhada (causa b, no gerador; executado pela forma de leitura, num clone descartável). Na montagem pela guarda (o construtor e os helpers copiados dos arquivos de teste e executados fora do repositório, sobre o modelo do commit): o ramo `mapa` de `mapaDiagramasConstruir` pula o filtro da ligação com o `$modo` nulo e conta a ligação nova como representada no `infra-convites` — vermelho da guarda (causa a), que o executor ajusta ao escrever a linha. Com o `ConviteDeAcesso` registrado só sem tenant, o mesmo ramo, que no diagrama de dois modos não lê o `registrada`, ainda conta "o model `Convite` usa `ConviteDeAcesso`" como representada (causa a). A linha de controle passa nas duas montagens: as duas ligações representadas, e as duas setas e os dois itens de prova na entrada do gerador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | os diagramas são gerados de um subconjunto (só as ligações de registro) | CT-14 | linha "sem essa ligação" reprova; e, nos publicados, o total achado fica abaixo do total do modelo; o mutante aceita |
| M2 | agrupamento que não nomeia os membros conta como desenho | CT-14 | linha do agrupamento: reprova; o mutante aceita |
| M3 | a menção da ligação no texto da peça conta como desenho (o que some com o zoom) | CT-14 | linha "só no texto": reprova; o mutante aceita |
| M4 | a ligação só aparece num diagrama que a página não publica | CT-14 | linha "diagrama órfão": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-15) | o agrupamento nomeia os membros só no texto secundário da caixa, que o zoom esconde | CT-14 | linha "membros só no texto secundário": reprova; o mutante aceita |
| M6 | a porta conta como desenho das ligações do outro lado sem nomear as peças de lá | CT-14 | linha "porta sem os membros": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-17) | na conversão para o esquema do archify, o gerador perde as arestas, e o diagrama desenha só os nós | CT-B02 | "em cada `architecture`, o conjunto de pares origem → destino das arestas desenhadas é o da fonte": reprova; o mutante aceita |
| M8 *(alterado em 2026-11-23 — Adendo 2: colisão b)* | a guarda conta a ponta pela peça que o diagrama lista e o painel registra no modo, sem a disponibilidade, e a ligação do `TenantResource`, que só um diagrama sem tenant listaria, passa como desenhada — embora o gerador não a desenhe ali | CT-14 | linha "ponta indisponível no modo do diagrama": a ligação não está desenhada, e reprova; o mutante aceita |
| M9 — step 9 do Adendo (A2) | na árvore de registro compartilhada, o gerador guarda a ligação `registra` quando o modo dela inclui o primeiro modo do diagrama, e a peça que o painel registra só sem tenant entra na árvore que vale nos dois modos — o caso de hoje | CT-14 | linha da ligação só sem tenant, na montagem pelo gerador: a entrada do `infra-registro` traz o nó do `FilamentShieldPlugin` e o item de prova da ligação, a guarda a acha desenhada e aceita; reprova; o mutante aceita |
| M10 — step 9 do Adendo (A2) | o construtor da guarda monta a árvore compartilhada pelo primeiro modo do diagrama, como o gerador de hoje, e conta como desenhada a ligação de um modo só que o gerador certo deixa fora | CT-14 | a mesma linha, na montagem pela guarda: a ligação conta como desenhada, e a guarda aceita; reprova; o mutante aceita |
| M11 — step 9 do Adendo, rodada 2 (AD2-02) | no mapa de arquitetura compartilhado, o gerador dá ao diagrama o `modo` nulo e não filtra a ligação pelo modo, e a ligação de um modo só entra no mapa que vale nos dois — o caso de hoje | CT-14 | linha da ligação `ConviteDeAcesso` → `Convite` só sem tenant, na montagem pelo gerador: a entrada do `infra-convites` traz a seta e o item de prova dela, a guarda a acha desenhada e aceita; reprova; o mutante aceita |
| M12 — step 9 do Adendo, rodada 2 (AD2-02) | o ramo `mapa` do construtor da guarda pula o filtro da ligação no diagrama de dois modos, como o gerador de hoje, e conta como desenhada a ligação de um modo só que o gerador certo deixa fora | CT-14 | a mesma linha, na montagem pela guarda: a ligação conta como desenhada; reprova; o mutante aceita |
| M13 — step 9 do Adendo, rodada 2 (AD2-02) | a guarda decide a ponta do diagrama de dois modos só pela disponibilidade, sem o `registrada` em cada modo, e conta como desenhada a ligação de uma peça que o modelo registra num modo só | CT-14 | linha do `ConviteDeAcesso` registrado só sem tenant, na montagem pela guarda: a ligação "o model `Convite` usa `ConviteDeAcesso`" conta como desenhada; reprova; o mutante aceita |

*(alterado em 2026-11-20: ADV2-17; oráculo ajustado pela medição no commit `4adaf9a`)* O CT-B02 confere que as arestas que o DOM desenha são as da fonte. No diagrama escrito à mão, a fonte é a lista de ligações do arquivo. No construído (o campo `construtor`), o arquivo só diz o recorte e o layout, e a fonte é o modelo: cada ligação do modelo no painel e no modo do diagrama vira o par das pontas desenhadas — a peça, ou o agrupamento que a nomeia —, e a ligação cuja ponta não está desenhada, ou cujas duas pontas caem na mesma caixa, não vira aresta. Medido no esqueleto (`ae5d20f`): `admin-acesso`, 76 pares no arquivo e 76 no DOM; `app-resumo-sem`, 14 e 14; `app-resumo-com`, 43 e 43. No commit `4adaf9a`, contra o modelo: `app-acesso`, 17 e 17; `app-organizacoes`, 14 e 14; `app-resumo-sem` e `app-resumo-com`, 16 e 16. O DOM tem mais elementos de aresta que pares (62 elementos e 43 pares no `app-resumo-com` do esqueleto), e por isso a conta é de pares únicos, lidos em `svg [data-edge-from][data-edge-to]`.

---

## Regra R13 — Toda ligação que o diagrama desenha — direta, ou por agrupamento ou porta que nomeia os membros — traz a prova, com o arquivo e o trecho, num item próprio de card do painel lateral do próprio diagrama, inteiro e à vista sem foco; o comparativo fica fora

> `P-04`, `P-07`, `RQ-08` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B02, no `05`** (costura browser, G4)

O archify só tem prova por nó (`sources`, de 1 a 3) e nenhuma por aresta. Por isso a prova de cada ligação no artefato publicado é responsabilidade do gerador, e só o HTML construído a mostra. O Gherkin e o roteiro estão no `05`. A tabela abaixo é a mesma do CT-B02, mais o M3, que morre na suíte do kit.

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)*
- **Onde fica a prova.** Os cards ficam no painel lateral do diagrama, sempre presentes, sem depender de foco. A prova de cada ligação fica num card do próprio diagrama, agrupado pela peça de origem; esse arranjo é decisão de desenho, e o oráculo é só "num card do painel lateral, à vista".
- **O card não tem link.** O archify escreve o item do card como texto escapado, num `<li>` (a função `renderCards`, em `renderers/shared/utils.mjs` do archify, no commit fixado). O link de evidência que o leitor clica é o do passaporte da peça (R14). Por isso a P-07 aqui é a do M3: a prova não guarda número de linha.

*(alterado em 2026-11-20: ADV2-20, ADV2-22 e ADV2-32)*
- **O comparativo fica fora** da conferência de cards: pela P-04 emendada, a casca do archify não leva cards nem passaporte. A `base` e a `cabeca` dele são diagramas do mapa, publicados ao lado com os cards (R20). Medido no HTML do comparativo, nos dois idiomas: 43 pares de arestas (62 elementos de aresta no DOM) e nenhum card.
- **Cada item amarrado à ligação.** O gerador escreve um item por ligação, "origem → destino (rótulo): arquivo:linha — trecho", no card da peça de origem (`site/mapa/gerar.mjs`, função `cardsDeProva`). O CT-B02 lê o item que nomeia a ligação e confere o arquivo e o trecho dela; e o total de itens de prova lidos no DOM, por diagrama, é o de ligações desenhadas, contado do lado do DOM.
- **Como se reconhece o item de prova** *(alterado em 2026-11-20: medido no commit `4adaf9a`)*. O painel lateral tem outros cards — no mapa construído, "Como ler as setas" e "Quem está em …", com os membros de cada agrupamento —, e eles ficam fora da conta. O item de prova é o que tem a forma "origem → destino (verbo): arquivo:linha — trecho". O total é por ligação do modelo, e não por par de arestas: o agrupamento desenha várias ligações numa aresta só. Medido: nos 6 diagramas construídos, 281 itens de prova por idioma, igual às ligações desenhadas (75 no `app-resumo-sem`, sobre 16 pares; 77 no `app-resumo-com`, sobre 16), e nenhum diagrama repete nome de peça, que é o que o item nomeia.
- **O item aparece inteiro.** Medido no Chromium: o item de card do archify quebra a linha (`overflow-wrap: anywhere`), e um item sintético de 160 caracteres sem espaço ficou com `scrollWidth` e `clientWidth` iguais, de 346 px; os 25 itens de card do esqueleto também não transbordam, nem os 562 itens de prova dos construídos no commit `4adaf9a`. O CT-B02 afirma `scrollWidth <= clientWidth + 1` e o trecho inteiro do modelo, sem reticências que o modelo não tenha.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador perde a prova das ligações desenhadas por agrupamento ou por porta que nomeia os membros *(alterado em 2026-11-20: a porta entrou com os fatos de plataforma)* | CT-B02 | "uma por ligação": a ligação do membro sem prova reprova; o mutante aceita |
| M2 | o card traz só o arquivo, sem o trecho *(reescrito em 2026-11-20: era "link de prova para `blob/main`", que passou à R14 como M5, porque o card do archify é texto, sem link)* | CT-B02 | "um item de prova que a nomeia, … com o arquivo e o trecho do modelo": o item sem o trecho reprova; o mutante aceita |
| M3 | número de linha guardado no modelo, que envelhece a cada edição do arquivo *(alterado em 2026-11-20: ADV-19, o matador passou do CT-B02 para a suíte do kit)* | CT-09 | linha da prova com número de linha, na suíte do kit, que roda em todo PR: reprova; o mutante aceita |
| M4 | o card mostra a prova de outra ligação (índice trocado, ou provas trocadas entre os cards de duas origens) *(alterado em 2026-11-20: ADV2-22, a asserção antiga contava o conjunto de provas em qualquer card)* | CT-B02 | "um item de prova que a nomeia, 'origem → destino', com o arquivo e o trecho do modelo": o item que nomeia a ligação traz a prova de outra, e reprova; o mutante aceita |
| M5 | a prova só fica num documento à parte, nunca no diagrama | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas, maior que zero em todo diagrama que desenha ligação": dá zero e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-25) | o card da prova fica fora do painel lateral: oculto, ou só aparece quando o leitor foca a peça | CT-B02 | "num card do painel lateral, à vista", com o diagrama aberto sem focar peça nenhuma: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-22) | o conferidor conta as provas do lado esperado, e a ligação de membro sem item próprio passa, porque outra ligação desenhada tem a mesma prova | CT-B02 | "o total de itens de prova lidos no DOM é o de ligações desenhadas": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-32) | o item de card corta o trecho longo, com reticências que o modelo não tem | CT-B02 | "o item mostra o trecho inteiro do modelo, e não é cortado (`scrollWidth <= clientWidth + 1`)": reprova; o mutante aceita |
| M9 — step 9 (A14) | o item de prova da árvore de registro nomeia a origem com um rótulo que não é o da peça painel no modelo, e o conferidor, que monta o prefixo esperado com o rótulo que lê da raiz no próprio HTML, aceita | CT-B02 | "na árvore de registro, a origem de cada item de prova é o rótulo que a fonte dá à peça painel do arquivo": reprova; o mutante aceita |

---

## Regra R14 — O leitor acha a peça pela busca, e o passaporte dela mostra as provas da própria peça, com o link para o repositório do kit no commit do build

> `RQ-07`, `P-04`, `P-07` · perfil **padrão** · técnica: **EP** (idioma) · cenário: **CT-B03, no `05`** (costura browser, G4)

A interação que o `00` pede (RQ-07) é provada pela busca e pelo passaporte com as provas (P-04, P-07). Rota entre nós, lente e exportação são recursos do archify e ficam em Cogitado e cortado. A tabela abaixo é a mesma do CT-B03.

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão e ADV-25)* Ao focar uma peça, o passaporte do archify mostra:
- o rótulo e o tipo da peça;
- "origem verificada {repositório} @ {commit}";
- as provas da própria peça (`sources`, até três), como links `https://github.com/{dono}/{repo}/blob/{commit}/{arquivo}#L{linha}`;
- só as contagens de ligações, "N de saída · M de entrada", e o alcance.

Ele não lista cada ligação com a prova dela: essa prova está no card (R13). Por isso o CT-B03 deixou de afirmar a prova da ligação no detalhe.

O oráculo do link é o da P-07: o repositório do `support.source` do `composer.json`, o commit do build e um arquivo que existe nesse commit.

*(alterado em 2026-11-20: ADV2-23 e ADV2-24)*
- **A amostra.** Em cada `architecture`, a primeira peça de pacote que o arquivo desenha, quando houver, e a primeira do kit; e, no conjunto dos `architecture`, ao menos uma de pacote. Medido no esqueleto: o `admin-acesso` tem 4 peças de pacote em 41, e o `app-resumo-sem` e o `app-resumo-com` não têm nenhuma; exigir uma de pacote em cada `architecture` reprovaria esses dois. No commit `4adaf9a`: o `app-acesso` tem 5 em 21, e o `app-organizacoes`, o `app-resumo-sem` e o `app-resumo-com`, nenhuma. A peça de pacote é a que o modelo dá como de pacote (a origem `pacote`); no desenho, é a do tipo `cloud`, "Pacote ou plugin de terceiros" na legenda.
- **A linha.** A prova de peça é âncora `{arquivo, trecho}` (R9), e o gerador acha a linha na revisão publicada. A linha do `#L` contém, no commit do build, o trecho da âncora. Medido no HTML construído do esqueleto (revisão `ae5d20f`): 226 links de prova de peça em 4 diagramas, e a linha de todos contém o trecho. No commit `4adaf9a`, também todos: 21, 9, 49, 47, 1 e 1 nos 6 construídos, e 73, 31 e 26 no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`.
- **Sem erro na interação.** Com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console. Medido no Chromium: nenhum erro no esqueleto com a busca aberta e uma peça em foco; no commit `4adaf9a`, nenhum no `app-acesso` (a peça de pacote `AuthDesignerPlugin`, achada pela busca, com o passaporte e um link para `AdminPanelProvider.php#L200` no commit) e no `app-organizacoes`, nos dois idiomas.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama é exportado como imagem estática, sem busca nem passaporte | CT-B03 | "o leitor busca a peça e a foca": não há busca e reprova; o mutante aceita |
| M2 | o nó sai sem provas, e o passaporte não mostra a origem verificada *(reescrito em 2026-11-20: era "o detalhe não traz a prova da ligação", que o passaporte do archify não mostra por desenho; ADV2-23 estendeu a amostra)* | CT-B03 | "cada peça da amostra mostra de uma a três provas": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-25) | o link do passaporte leva a outro repositório, como um fork ou um nome errado, com o mesmo commit e caminho *(reescrito em 2026-11-20: era "o detalhe do agrupamento não nomeia os membros", que passou à R12 como M5)* | CT-B03 | "leva ao repositório do `support.source`": reprova; o mutante aceita |
| M4 | a prova da peça de pacote aponta para `vendor/`, fora do commit, e o link dá 404 | CT-B03 | "a um arquivo que existe nesse commit": reprova; o mutante aceita |
| M5 | link de evidência para `blob/main`, um ramo que anda *(veio da R13, onde era o M2)* | CT-B03 | "no commit do build": o `href` não traz o commit e reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-23) | a linha do `#L` sai fixa em 1, ou envelhece, e o link leva a outra linha | CT-B03 | "a linha do `#L` contém o trecho da âncora, no commit do build": reprova; o mutante aceita |

---

## Regra R15 — A página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe

> `RQ-03`, `P-02` · perfil **padrão** · técnica: **EP** (idioma)

A Q15 foi decidida antes da derivação. A página linka cada diagrama com `<a href>` relativo cru dentro do Markdown, e não com link Markdown. O `[CT-22]` e o `[CT-45]` herdados só leem link Markdown e não mudam. O `site/verifica-links.mjs` confere o alvo no `dist/`.

```gherkin
  Regra: A página do mapa e os arquivos de diagrama se espelham, idioma a idioma

    Esquema do Cenário: [CT-15] a página de cada idioma leva, por `<a href>` relativo, a todo diagrama do seu idioma, e só a diagrama que existe
      Dado os arquivos de diagrama do mapa
      E a página do mapa em "<idioma>"
      Quando a guarda do mapa lê os `<a href>` da página que apontam para o mapa
      Então o conjunto de diagramas que eles levam é o conjunto de arquivos de diagrama, nenhum a mais e nenhum a menos
      E todo `<a href>` é relativo e leva ao HTML do diagrama em `/mapa/<idioma>/`, e nenhum ao do outro idioma

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | diagrama órfão: o arquivo existe e a página não o leva | CT-15 | "nenhum a menos": reprova; o mutante aceita |
| M2 | link morto: a página leva a um diagrama que não existe | CT-15 | "nenhum a mais": reprova; o mutante aceita |
| M3 | a página en leva aos HTML pt (link copiado da página pt) | CT-15 | linha "en", "nenhum ao do outro idioma": reprova; o mutante aceita |
| M4 | `href` absoluto (`/mapa/pt/x.html`), que no Pages perde a base `/filament-starter-kit-easy` | CT-15 | "todo `<a href>` é relativo": reprova; o mutante aceita |
| M5 | link Markdown no lugar do `<a href>` (o `[CT-22]` herdado o acusaria como morto) | CT-15 | a guarda lê zero `<a href>`, e "nenhum a menos" reprova; o mutante aceita |

---

## Regra R16 — Todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido; o título só fica igual quando é o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código

> `P-02`, `RQ-03` · perfil **padrão** · técnica: **EP** + controles em cópia

*(alterado em 2026-11-20: ADV-10, ADV2-11 e ADV2-28)* O texto en é traduzido por quatro critérios, e nunca por "diferente do pt":
- **Termo do glossário ou nome de estado.** Quando o texto pt é um termo do glossário — os 39 pares "termo (en: …)" de `wikis/glossario.md` —, o en é o termo en do glossário, sem diferença de maiúscula. Vale para o rótulo de nó que não é peça: o estado do `lifecycle`, a caixa de agrupamento e a de porta, o marco.
- **Frase.** O texto en de duas palavras ou mais não traz palavra funcional do pt — de, do, da, para, com, não ou uma — como palavra: não colada a letra, dígito, `_`, `.`, `/` ou `-`. Vale para título, descrição, passo, rótulo de ligação e rótulo de nó que não é peça.
- **Nome de código e rótulo de peça.** Ficam isentos: o rótulo de peça é o nome dela, igual nos dois idiomas de propósito, e o token único (classe, método, caminho, comando, chave) é código.
- **O título igual nos dois idiomas.** Só passa quando é exatamente o nome de uma peça do modelo ou de um comando `kit:*` (`kit:install`), e o arquivo o marca como nome de código. Antes, o exemplo dava também `2FA`, que essa restrição recusa. A marca num título que não é nome de peça nem de comando reprova.

Medido nas traduções do esqueleto (827 pares pt → en):
- 373 pares têm o en igual ao pt, 77 deles com espaço, e todos são código ou texto já em inglês (`22 migrations`, `CHANGELOG v0.56.0`, `/infra/logs · LogsExplorer`). "Diferente do pt" reprovaria todos.
- Dos 482 valores en de duas palavras ou mais, nenhum traz palavra funcional do pt; o mesmo detector acha alguma em 199 dos 827 textos pt. No commit `4adaf9a`, também nenhum: 403 textos en de duas palavras ou mais nos 17 arquivos de diagrama, 14 no gerador e 9 nas sobrescritas de tipo. "Do" também é palavra inglesa ("do not"); nenhum texto en do commit `4adaf9a` a traz, e, se um texto en legítimo vier a trazê-la, a lista da ADV2-28 é decisão da sessão.
- Dos 4 rótulos do esqueleto que são termo do glossário, 2 batem com o en dele (Pendente → Pending; Excluída → Deleted) e 2 não: Inativa → Off (o glossário dá Inactive) e Telas de acesso → Access screens (o glossário dá sign-in screens). São defeitos do esqueleto, que a entrega corrige.

```gherkin
  Regra: O site vale nos dois idiomas, e o que o kit escreve em cada diagrama também

    Esquema do Cenário: [CT-16] todo diagrama tem os seus textos em pt e em en, e o texto en é traduzido, salvo o título que é nome de peça ou de comando, marcado
      Dado os arquivos de diagrama do mapa "<mundo>"
      Quando a guarda do mapa confere os textos que o kit escreveu em cada diagrama
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                              | resultado                                                                                                                              | # partição               |
        | publicados                                                                         | aceita: todo título, rótulo, descrição e passo tem pt e en não vazios; o termo do glossário usa o en dele; o texto en de duas palavras ou mais não traz palavra funcional do pt; e o título igual nos dois idiomas é exatamente o nome de uma peça do modelo ou de um comando `kit:*`, marcado como nome de código | válida |
        | cópia em que um rótulo não tem o texto en                                          | reprova, nomeando o diagrama e o rótulo                                                                                                | texto en ausente         |
        | cópia em que o título en é igual ao pt, sem a marca de nome de código              | reprova, nomeando o diagrama                                                                                                           | título copiado           |
        | cópia em que o título `kit:install` é igual nos dois idiomas, marcado como nome de código | aceita                                                                                                                          | nome de comando          |
        | cópia em que o título "Painel admin — sem organização" é igual nos dois idiomas, marcado como nome de código | reprova, nomeando o diagrama e o título                                                                        | marca de fachada         |
        | cópia em que a descrição en de um passo é a frase pt "Gerando senha do admin"         | reprova, nomeando o diagrama e o passo | descrição copiada |
        | cópia do `lifecycle` do convite em que o estado Pendente tem o rótulo en "Pendente" | reprova, nomeando o diagrama e o estado | termo do glossário sem o en dele |
        | cópia em que o passo en é a frase pt com uma palavra trocada, "Generating senha do admin" | reprova, nomeando o diagrama e o passo | frase com palavra do pt |
        | cópia em que um diagrama de fluxo só tem os textos pt                              | reprova, nomeando o diagrama                                                                                                           | diagrama de um idioma só |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o texto en que falta cai no pt, sem aviso | CT-16 | linha "rótulo sem en": reprova; o mutante aceita |
| M2 | título en copiado do pt | CT-16 | linha "título igual, sem a marca": reprova; o mutante aceita |
| M3 | diagrama de fluxo escrito só em pt | CT-16 | linha "só pt": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-10) | o título copiado do pt ganha a marca de nome de código para passar | CT-16 | linha "Painel admin — sem organização", marcado: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-10) | a descrição ou o passo en é a frase pt copiada | CT-16 | linha "descrição en é a frase pt": a frase traz "do", e reprova; o mutante aceita, porque só exige en não vazio |
| M6 — revisão adversarial (ADV2-11) | o rótulo en do estado ou da caixa que não é peça sai igual ao pt ("Pendente") | CT-16 | linha do estado Pendente: o glossário dá Pending, e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-28) | a frase en é a pt com uma palavra trocada, e passa no "diferente do pt" | CT-16 | linha "Generating senha do admin": traz "do", e reprova; o mutante aceita |

---

## Regra R17 — A grade painel × modo é inteira: cada painel do registro tem diagrama próprio em cada modo, o compartilhado vem marcado e só ele, e a célula não leva a diagrama do outro modo nem de outro painel

> `RQ-10`, `RQ-11`, `P-06`, `P-09` · perfil **padrão** · técnica: **tabela de decisão** (3 painéis × 2 modos = 6 células por idioma, 12 nos dois) + controles em cópia

```gherkin
  Regra: Cada painel do kit tem a sua visualização, sem e com tenant, e a página mostra a grade inteira

    Esquema do Cenário: [CT-17] cada painel do registro tem diagrama próprio em cada modo, e o compartilhado vem marcado
      Dado os painéis do registro de painéis
      E a grade painel × modo da página do mapa em "<idioma>"
      Quando a guarda do mapa confere a célula do painel "<painel>" no modo "<modo>"
      Então a célula leva a ao menos um diagrama desse painel que vale nesse modo
      E todo diagrama da célula que vale nos dois modos está também na outra célula do painel, marcado como compartilhado
      E nenhum diagrama da célula é de outro painel
      E nenhum diagrama da célula vale só no outro modo
      E todo diagrama marcado como compartilhado vale nos dois modos

      Exemplos:
        | idioma | painel | modo       |
        | pt     | admin  | sem tenant |
        | pt     | admin  | com tenant |
        | pt     | app    | sem tenant |
        | pt     | app    | com tenant |
        | pt     | infra  | sem tenant |
        | pt     | infra  | com tenant |
        | en     | admin  | sem tenant |
        | en     | admin  | com tenant |
        | en     | app    | sem tenant |
        | en     | app    | com tenant |
        | en     | infra  | sem tenant |
        | en     | infra  | com tenant |

    Cenário: [CT-18] um painel novo no registro, sem diagrama, deixa a grade incompleta
      Dado um quarto painel, "financeiro", registrado no caso
      Quando a guarda do mapa confere a grade da página contra o registro de painéis
      Então ela reprova, nomeando o painel "financeiro" nos dois modos
      E os três painéis do kit continuam conferidos

    Esquema do Cenário: [CT-43] a guarda da grade reprova a célula que leva a diagrama do outro modo ou de outro painel
      Dado a grade painel × modo da página do mapa "<mundo>"
      Quando a guarda do mapa confere cada célula
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                          | resultado                                   | # partição                    |
        | publicada, pt e en                                                                                             | aceita                                      | válida                        |
        | cópia em que a célula app × com tenant leva também ao diagrama de arquitetura do app que só vale sem tenant      | reprova, nomeando a célula e o diagrama     | diagrama do outro modo        |
        | cópia em que a célula infra × sem tenant leva ao diagrama de arquitetura do admin                              | reprova, nomeando a célula e o diagrama     | diagrama de outro painel      |
        | cópia em que a célula app × com tenant marca como compartilhado o diagrama de arquitetura que só vale com tenant | reprova, nomeando a célula e o diagrama | compartilhado de um modo só |
```

*(alterado em 2026-11-20: ADV-26)* O CT-17 pede "ao menos um" diagrama válido na célula. Sem a linha "vale só no outro modo", a célula com tenant podia levar também ao diagrama que só vale sem tenant. O CT-43 dá a cópia que mata esse defeito, e também a do diagrama de outro painel, que antes só tinha o mundo publicado.

*(alterado em 2026-11-20: ADV2-12)* A marca de compartilhado vale nas duas direções: o diagrama que vale nos dois modos está marcado nas duas células, e o diagrama marcado vale nos dois. O modo de cada diagrama é o do campo `modos` do arquivo. Medido no esqueleto: os diagramas de um modo só, `app-resumo-sem` e `app-resumo-com`, têm um modo em `modos`, e os demais, os dois.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um painel é esquecido na grade (o `/infra`) | CT-17 | linhas "infra": "a célula leva a ao menos um diagrama" reprova; o mutante aceita |
| M2 | a lista de painéis da grade é escrita à mão | CT-18 | "reprova, nomeando o painel financeiro": o mutante aceita, porque não lê o registro |
| M3 | a célula leva a diagrama de outro painel | CT-17, CT-43 | CT-17, "nenhum diagrama da célula é de outro painel", e CT-43, linha "infra leva ao admin": reprova; o mutante aceita |
| M4 | o diagrama dos dois modos aparece numa célula só, ou sem a marca | CT-17 | "está também na outra célula, marcado como compartilhado": reprova; o mutante aceita |
| M5 | só o modo sem tenant tem diagramas | CT-17 | linhas "com tenant": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-26) | a célula com tenant leva também ao diagrama que só vale sem tenant | CT-43 | linha "app × com tenant leva ao diagrama que só vale sem tenant": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-12) | um diagrama que só vale num modo vem marcado como compartilhado na célula | CT-43, CT-17 | CT-43, linha "marca como compartilhado o diagrama que só vale com tenant": reprova; e, no publicado, o CT-17 exige que todo marcado valha nos dois modos; o mutante aceita |

---

## Regra R18 — Só se compartilha o que é igual nos dois modos, o que é igual não se duplica, toda ligação desenhada vale no modo do diagrama, e a raiz da árvore de registro diz esse modo — "os dois modos", na compartilhada

> `P-09`, `RQ-11` · perfil **padrão** · técnica: **tabela de decisão** (igual nos dois modos × publicado uma vez) + controles em cópia · *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01: o enunciado ganhou a raiz da árvore; nas linhas dela, EP do `modos` da árvore — sem, com, os dois, em qualquer ordem — na forma de leitura da G5, e a origem ganhou a `P-02`, pelo termo en)*

As linhas usam `DefinirTenantDePermissoes`, que o `/app` só registra com tenant. Assim o oráculo vem do registro (CT-11 e CT-12), e não da disponibilidade. Se a disponibilidade por modo conta como diferença de estrutura é recorte aberto (Q11). *(alterado em 2026-11-23 — Adendo 2: conta. A peça que só fica disponível num modo só entra no diagrama desse modo (RQ-28), e o `/admin` ganha diagramas separados por causa dela (RQ-29). Dois diagramas do mesmo painel e tipo, um por modo, que só diferem nessa peça não são "igual duplicado": a linha nova do CT-19 o controla. A ligação desenhada segue a definição do Setup Global)*

*(alterado em 2026-11-20: ADV2-13)* Toda ligação desenhada tem, nos modos dela, o modo do diagrama. A ligação "o painel app usa o model `Tenant`" vem do `->tenant()` dentro do bloco `kit.tenancy.enabled` (`app/Providers/Filament/AppPanelProvider.php:tenant:660`), e só vale no modo com tenant. Medido no esqueleto: o `app-resumo-sem` não desenha peça nem ligação do modo com tenant, e o `app-resumo-com` desenha 5 ligações do `IdentifyTenant` e do `DefinirTenantDePermissoes`.

*(alterado em 2026-11-20: medido no modelo do commit `4adaf9a`)* O modelo dá "o painel app usa o model `Tenant`" aos dois modos, e o "o painel app registra `IdentifyTenant`", com o mesmo trecho, só ao modo com tenant. O trecho só roda com tenant, e por isso o defeito é do modelo, e não do oráculo: a linha de controle do CT-19 parte do código e reprova enquanto o modelo der a ligação ao modo sem tenant. Nenhuma ligação do modelo do commit `4adaf9a` vale só com tenant entre peças dos dois modos no `/app`. O modo das demais ligações de uso não tem guarda: o registro vivo (R11) dá o modo só das ligações de registro, e a P-05 não promete a varredura de uso inteira. É lacuna declarada (R18-M5), e o ponto foi ao coordenador.

*(alterado em 2026-11-20: fato da implementação, dado pelo coordenador, e medição no commit `4cc9517`)* A pilha da requisição saiu em dois diagramas `sequence`: o `requisicao-autenticada`, comum, com os três painéis e os dois modos, e o `requisicao-autenticada-com`, só do `/app` e só com tenant, com a camada da organização. O comum não desenha o `IdentifyTenant` nem o `DefinirTenantDePermissoes`: só os cita num card que leva ao `-com`, e menção em texto não é desenho (R12). O `-com` os desenha, o segundo com o rótulo abreviado "DefinirTenant". A guarda reconhece a peça do fluxo pelo nome exato, e o rótulo abreviado de uma peça de um modo só, desenhado no fluxo comum, passaria: é lacuna declarada (R18-M6).

```gherkin
  Regra: Ganha um diagrama por modo só o que muda de estrutura

    Esquema do Cenário: [CT-19] compartilha-se só o que é igual nos dois modos, e o igual não se duplica
      Dado o modelo, com o modo de cada peça e de cada ligação, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere o modo de cada diagrama
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                                                  | resultado                               | # partição                         |
        | publicados                                                                                                             | aceita                                  | válida                             |
        | cópia em que o diagrama compartilhado do painel app desenha `DefinirTenantDePermissoes`, que só existe com tenant       | reprova, nomeando o diagrama e a peça   | compartilhado esconde a diferença  |
        | cópia com dois diagramas do painel app, do mesmo tipo, um por modo, que desenham as mesmas peças e ligações             | reprova, nomeando os dois diagramas     | igual duplicado                    |
        | cópia em que o diagrama do painel app sem tenant desenha `DefinirTenantDePermissoes`                                   | reprova, nomeando o diagrama e a peça   | peça do modo errado                |
        | cópia em que o diagrama de arquitetura do painel app sem tenant desenha "o painel app usa o model `Tenant`", que o código só faz com tenant (o `->tenant()` no bloco `kit.tenancy.enabled`) | reprova, nomeando o diagrama e a ligação | ligação do modo errado |
        | cópia do modelo com a condição "só com tenant" no `TenantResource`, e com duas árvores de registro do painel admin, uma por modo, que por ela desenham as mesmas peças e ligações fora o `TenantResource`, que só a de com tenant desenha | aceita: a peça que só fica disponível num modo é diferença de estrutura (RQ-28) | disponibilidade é diferença |
        | as árvores de registro publicadas, montadas pelo gerador em pt e em en (forma de leitura da G5) *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | aceita: o rótulo secundário da raiz de cada árvore é, por igualdade, o termo da tabela do Setup Global para o `modos` do arquivo — "sem tenant" / "tenancy off" na que vale só sem tenant, "com tenant ativo" / "tenancy on" na que vale só com, "os dois modos" / "both modes" na compartilhada, o `infra-registro` —, e todas as árvores de registro publicadas foram conferidas (hoje, cinco) | a árvore de cada modo — sem, com, os dois — diz o seu (hoje reprova: nota abaixo) |
        | cópia em memória do `infra-registro`, com o mesmo slug e os modos na ordem `["com", "sem"]`, montada pelo gerador *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | aceita: a raiz diz "os dois modos" / "both modes" — a ordem dos modos no arquivo não muda o termo | os dois modos noutra ordem (hoje reprova: nota abaixo) |
        | controle: cópia em memória do `infra-registro`, com o mesmo slug, valendo só sem tenant (`["sem"]`), montada pelo gerador *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | aceita: a raiz diz "sem tenant" / "tenancy off" — o termo sai do `modos` do arquivo, e não do painel nem do slug | a mesma árvore, num modo só |
```

*(alterado em 2026-11-23 — Adendo 2)* **Medido no HEAD `c642c23c`:** o `/admin` tem uma árvore só (`admin-registro`), compartilhada, e a linha nova é cópia em memória, do modelo e dos arquivos. A árvore é construída do modelo, e a guarda de hoje conta a peça dela pelo registro, sem a condição: as duas árvores da cópia lhe saem iguais, e espera-se que ela reprove a linha hoje — o R18-M7 vivo — até seguir a definição do Setup Global. O executor confere.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* "As mesmas peças e ligações" lê a ligação desenhada como na R12. No ERD, cujas tabelas não são peças do modelo, a comparação inclui a estrutura do próprio diagrama (entidades e relações): sem isso, os dois ERDs de identidade pareciam vazios e iguais. Só conta como "só com tenant" o bloco `if (config('kit.tenancy.enabled')) {`; a primeira chave depois de um `config('kit.tenancy.enabled')` dentro de um `->visible(fn …)` é o corpo de outro método.

*(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* **A raiz da árvore diz o modo do diagrama.** A visualização igual nos dois modos é publicada uma vez e vale para os dois (P-09): a árvore que vale nos dois não pode dizer, na raiz, que vale num só. Pelo Setup Global ("O diagrama que vale nos dois modos e o rótulo da raiz"), o rótulo secundário da raiz diz o modo do campo `modos` do arquivo, qualquer que seja a ordem, pelo termo da tabela de lá; o lugar e a redação são decisão de desenho da sessão, e o oráculo é a P-09.
- **Por que no CT-19.** A guarda dele confere o modo de cada diagrama, e a raiz é o que a árvore diz desse modo. O CT-14 procura ligações, e a raiz não é ligação; o CT-43 lê a grade da página, e o rótulo da raiz é texto do diagrama (Cogitado e cortado).
- **A montagem.** As três linhas leem a entrada que o gerador entrega ao archify, pela forma de leitura da G5 (`entradasDoGeradorDoMapa()`, a função `entradaDoArchify` do gerador): a das árvores publicadas, pelo pedido de construtor `registro`; cada cópia, por `extras` com `soExtras`, uma por pedido, porque leva o slug da publicada. A raiz é o nó da peça painel do arquivo, pelo id do archify, e o texto é o `sublabel` dele, em cada idioma. O esperado sai do `modos` da árvore montada e da tabela, nunca das constantes do gerador, e a comparação é por igualdade, com o rótulo secundário inteiro: "só com tenant ativo" contém "com tenant ativo", e "tenancy on only" contém "tenancy on", e a comparação por conter deixaria vivo o R18-M11. A linha das publicadas conta as árvores conferidas, e nenhuma pode faltar.
- **O que cada linha discrimina.** A das publicadas pega a árvore compartilhada que diz um modo só e a de um modo só que diz o termo errado; a cópia com os modos noutra ordem pega a correção que depende da ordem; o controle — a mesma árvore e o mesmo painel, num modo só — pega o "os dois modos" que não sai do `modos` do arquivo, e prova que o termo de um modo só continua certo onde ele é certo.
- **Medido no HEAD `1dd48acd`**, pela entrada que o gerador monta (Setup Global; a árvore de trabalho dá o mesmo): a linha das publicadas fica vermelha pela raiz do `infra-registro`, "sem tenant" / "tenancy off" no lugar de "os dois modos" / "both modes" (causa b: o gerador toma o primeiro modo do arquivo, em `arvoreDeRegistro`); as outras quatro árvores dão o termo certo. A cópia com `["com", "sem"]` fica vermelha: "com tenant ativo" / "tenancy on" (causa b). O controle passa: "sem tenant" / "tenancy off". Nenhuma guarda lê hoje o rótulo secundário da raiz, e por isso nenhum vermelho é da guarda (causa a): ela nasce com as linhas.
- **Por que não no navegador.** O rótulo secundário que o gerador entrega é o texto que o archify desenha. Na árvore, o rótulo secundário dos nós de peça fica à vista no carregamento (medido com a F-nn, R6 e CT-B01); o da raiz não foi medido à parte, e o que o CT-19 afirma é o texto, que a entrada já prova. O conferidor do mapa não muda, e o `05` também não.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama compartilhado esconde a diferença de modo | CT-19 | linha "compartilhado desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |
| M2 | o que é igual é publicado duas vezes, uma por modo | CT-19 | linha "dois iguais": reprova; o mutante aceita |
| M3 | uma peça de um modo é desenhada no diagrama do outro | CT-19 | linha "sem tenant desenha `DefinirTenantDePermissoes`": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV2-13) | o diagrama sem tenant desenha uma ligação que o modelo dá só ao modo com tenant, entre peças que existem nos dois modos | CT-19 | linha "o painel app usa o model `Tenant`" no diagrama sem tenant: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV2-13) | o modelo dá aos dois modos uma ligação de uso cujo trecho só roda com tenant | ⚠️ **sem matador**: nenhuma guarda deriva do código o modo da ligação de uso; o registro vivo (R11) dá o modo só das ligações de registro, e a P-05 não promete a varredura de uso inteira. Medido no modelo do commit `4adaf9a`: "o painel app usa o model `Tenant`", com o trecho `->tenant(Tenant::class, slugAttribute: 'slug')` no bloco `kit.tenancy.enabled`, está nos dois modos. A linha de controle do CT-19 acusa esse caso, e só ele; o resto fica para o quality gate | — (lacuna declarada) |
| M6 — revisão adversarial (ADV2-13) | o fluxo comum aos dois modos desenha, com o rótulo abreviado, uma peça que só existe com tenant ("DefinirTenant") | ⚠️ **sem matador**: no fluxo escrito à mão, a guarda só reconhece a peça do modelo pelo nome exato, e o rótulo abreviado não é peça para ela. Medido no commit `4cc9517`: o `requisicao-autenticada-com` desenha o `DefinirTenantDePermissoes` como "DefinirTenant", e 61 dos 99 nós dos 14 fluxos de painel não têm nome de peça. Fica para o quality gate | — (lacuna declarada) |
| M7 *(alterado em 2026-11-23 — Adendo 2)* | a guarda do igual duplicado compara o registro do painel nos dois modos, sem a disponibilidade, e reprova as duas árvores do `/admin` que só diferem no `TenantResource` — o que a RQ-29 pede | CT-19 | linha "disponibilidade é diferença": aceita; o mutante reprova |
| M8 — step 9 do Adendo (S-AD-01) | o gerador diz na raiz da árvore de registro o primeiro modo do arquivo, e a árvore compartilhada, o `infra-registro` (`["sem", "com"]`), sai "sem tenant" / "tenancy off" — o caso de hoje | CT-19 | linha das árvores publicadas, montadas pelo gerador: a raiz do `infra-registro` diz "sem tenant" / "tenancy off", e não "os dois modos" / "both modes"; reprova; o mutante aceita |
| M9 — step 9 do Adendo (S-AD-01) | a correção depende da ordem dos modos no arquivo: a raiz diz "com tenant ativo" sempre que o primeiro modo é o com, e só cai em "os dois modos" quando ele é o sem | CT-19 | linha da cópia do `infra-registro` com `["com", "sem"]`: a raiz diz "com tenant ativo" / "tenancy on", e não "os dois modos" / "both modes"; reprova; o mutante aceita |
| M10 — step 9 do Adendo (S-AD-01) | a raiz diz "os dois modos" sem ler o `modos` do arquivo: em toda árvore, na do painel que hoje só tem a árvore compartilhada (o `/infra`), ou na árvore cujo slug não termina em `-sem` nem em `-com` | CT-19 | linha de controle, a cópia do `infra-registro` valendo só sem tenant: a raiz diz "os dois modos", e não "sem tenant" / "tenancy off"; reprova; o mutante aceita — e, no "em toda árvore", também a linha das publicadas, nas quatro de um modo só |
| M11 — step 9 do Adendo (S-AD-01) | a raiz da árvore que vale só com tenant diz a marca do modo da peça condicional, "só com tenant ativo" / "tenancy on only", e não o termo da grade | CT-19 | linha das árvores publicadas, comparada por igualdade: a raiz do `admin-registro-com` e a do `app-registro-com` dizem "só com tenant ativo" / "tenancy on only", e não "com tenant ativo" / "tenancy on"; reprova; o mutante aceita — a comparação por conter o termo o deixaria vivo |

---

## Regra R19 — Cada um dos dez tipos e o comparativo têm uso ou motivo, cada combinação tipo × painel × modo tem diagrama ou motivo traduzido, e só é do kit inteiro o diagrama que não desenha peça registrada por painel

> `RQ-06`, `RQ-25`, `P-06`, `P-02` · perfil **padrão** · técnica: **produto cartesiano fechado**: (10 tipos × 3 painéis × 2 modos) + (comparativo × 3 painéis) = **63 células por idioma**, 126 nos dois. Cada célula é resolvida pelo CT-21. No nível do tipo, os 11 tipos são resolvidos pelo CT-20

```gherkin
  Regra: Nenhum tipo disponível fica sem uso sem um motivo escrito

    Esquema do Cenário: [CT-20] cada um dos dez tipos nativos e o comparativo de arquitetura têm diagrama publicado ou o motivo escrito
      Dado os arquivos de diagrama do mapa e a página do mapa em pt e em en
      Quando a guarda do mapa procura o tipo "<tipo>"
      Então há diagrama publicado desse tipo, ou a página traz, nos dois idiomas, o motivo de não haver

      Exemplos:
        | tipo                                           |
        | architecture                                   |
        | workflow                                       |
        | sequence                                       |
        | dataflow                                       |
        | lifecycle                                      |
        | erd                                            |
        | class                                          |
        | tree                                           |
        | timeline                                       |
        | waterfall                                      |
        | comparativo de arquitetura (sem e com tenant)  |

    Esquema do Cenário: [CT-21] toda combinação tipo × painel × modo tem diagrama ou motivo ao lado, e só do conjunto fechado de tipos
      Dado as 63 células de cada idioma na página "<mundo>"
      Quando a guarda do mapa confere cada célula
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                          | resultado                                                                                                                  | # partição             |
        | publicada, pt e en | aceita: todo diagrama a que a célula leva é daquele tipo e daquele painel e vale naquele modo, ou a célula traz o motivo; o motivo da página en é traduzido (critério da R16); e o diagrama do kit inteiro (`paineis` vazio) não desenha peça que painel registra | válida |
        | cópia com a célula `waterfall` × admin × com tenant sem diagrama e sem motivo                  | reprova, nomeando a célula                                                                                                 | célula vazia           |
        | cópia com o motivo "—" na célula comparativo × infra                                           | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia com o motivo "não se aplica", sem porquê, na célula `erd` × app × com tenant             | reprova, nomeando a célula                                                                                                 | motivo de enfeite      |
        | cópia da página en em que o motivo da célula comparativo × infra é o texto pt "o resumo do painel é igual nos dois modos" | reprova, nomeando a célula | motivo en copiado do pt |
        | cópia da página en em que o motivo de uma célula é a frase pt com uma palavra trocada, "no flow de dados in this panel" | reprova, nomeando a célula | motivo en com palavra do pt |
        | cópia em que um `workflow` que desenha peças que o `/app` registra tem `paineis` vazio e resolve as células `workflow` × app com o motivo "do kit inteiro" | reprova, nomeando o diagrama | fluxo de painel declarado do kit inteiro |
        | cópia em que a célula `sequence` × admin × sem tenant leva a um diagrama `workflow`            | reprova, nomeando a célula e o tipo                                                                                        | link na célula errada  |
        | cópia em que a célula `sequence` × admin × sem tenant leva ao `sequence` do app                | reprova, nomeando a célula                                                                                                 | link de outro painel   |
        | cópia com o tipo "mindmap" na página                                                           | reprova, nomeando o tipo                                                                                                   | tipo fora do conjunto  |
```

Um tipo do kit inteiro (P-06: linha do tempo das versões, tempos, modelo de dados e de classes) resolve as células de painel com o motivo "do kit inteiro" e o link para o diagrama dele.

*(alterado em 2026-11-20: ADV-10 e ADV-26)* O motivo da célula na página en é diferente do da mesma célula na página pt. A linha "leva ao `sequence` do app" dá à metade "outro painel" do M4 a cópia que faltava.

*(alterado em 2026-11-20: ADV2-14, D28, e ADV2-28)*
- **Do kit inteiro.** Vale só para o diagrama que não desenha peça registrada por painel: versões, tempos, modelo de dados e de classes, e os comandos `kit:*` (P-06, D28). O fluxo transversal — login, 2FA, login social, recuperação de senha, bloqueio de sessão, pilha de middleware — desenha peça que todo painel registra e é diagrama dos painéis que ele desenha: `paineis[]` substitui `painel?`, e o diagrama aparece, compartilhado, na célula de cada um. Medido no esqueleto: os 6 diagramas com `paineis` vazio (lifecycle, erd, waterfall, workflow do `kit:install`, timeline e class) não desenham peça registrada; o `conta` cita o `UserResource` só como âncora de um estado; e o `requisicao-autenticada` (sequence) tem os três painéis em `paineis`.
- *(alterado em 2026-11-20: medido no commit `4cc9517`)* **Desenhar a peça** é ter um nó com o id do modelo dela ou com o nome dela, exato. O marco da linha do tempo que cita a feature numa frase não desenha a peça: o título do marco 0.33.0, "Dashboard dinâmico nos três painéis", traz `Dashboard`, nome de peça registrada, e conferir por palavra reprovaria dado correto. Medido: 22 diagramas com `paineis` vazio, e nenhum nó com o nome exato de uma das 112 peças que painel registra. A pilha da requisição é de painel: o `requisicao-autenticada` tem os três painéis e os dois modos, e o `requisicao-autenticada-com`, só o `/app` e só com tenant.
- *(alterado em 2026-11-20: medido na página do commit `e7d6eb5`)* **As células do comparativo.** A página repete o comparativo nas duas colunas de modo de cada painel (66 células por idioma, e não 63); as duas valem pela mesma combinação comparativo × painel, e a guarda as lê assim. O motivo real das células do `/admin` e do `/infra` é "o resumo do painel é igual nos dois modos" ("the panel summary is the same in both modes"), e as linhas de controle do motivo usam essas células. Na mesma página, as células da árvore de registro e da arquitetura do `/admin` e do `/infra` trazem "—", e o CT-21 as reprova até a entrega pôr a árvore compartilhada de cada um e o motivo da arquitetura.
- **O motivo en é traduzido** pelo critério da R16: não traz palavra funcional do pt, e nunca "diferente do pt".
- *(alterado em 2026-11-23 — Adendo 2)* **O motivo que o Adendo 2 pode desmentir.** O motivo das células comparativo × `/admin`, "o resumo do painel é igual nos dois modos", deixa de ser verdade se o resumo do `/admin` ganhar um diagrama por modo (a RQ-28 tira o `TenantResource`, membro do agrupamento de multi-tenancy do `admin-resumo`, do resumo sem tenant). Nenhuma guarda confere a verdade de um motivo, que é texto livre: é o R19-M9, sem matador.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | tipo sem uso e sem motivo (o `waterfall` esquecido) | CT-20 | linha `waterfall`: reprova; o mutante aceita |
| M2 | célula sem diagrama e sem motivo | CT-21 | linha "célula vazia": reprova; o mutante aceita |
| M3 | motivo de enfeite ("—", "não se aplica") | CT-21 | linhas "—" e "não se aplica": reprova; o mutante aceita |
| M4 | link na célula de outro tipo ou de outro painel | CT-21 | linhas "`sequence` leva a `workflow`" e "`sequence` leva ao `sequence` do app": reprova; o mutante aceita |
| M5 | a página traz um tipo que o archify não tem | CT-21 | linha "mindmap": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-10) | o motivo da célula na página en é o texto pt copiado | CT-21 | linha "motivo en copiado do pt": o texto pt traz "do", e reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-14) | os fluxos do `/app` são declarados do kit inteiro, e as células de fluxo × painel se resolvem com o motivo "do kit inteiro" | CT-21 | linha do `workflow` com `paineis` vazio: reprova; o mutante aceita |
| M8 — revisão adversarial (ADV2-28) | o motivo en é a frase pt com uma palavra trocada, e passa no "diferente do pt" | CT-21 | linha "no flow de dados in this panel": traz "de", e reprova; o mutante aceita |
| M9 *(alterado em 2026-11-23 — Adendo 2)* | o resumo do `/admin` ganha um diagrama por modo, e a célula comparativo × `/admin` continua com o motivo "o resumo do painel é igual nos dois modos" | ⚠️ **sem matador**: o motivo é texto livre, e conferir a verdade dele exigiria a guarda entender a frase. O sinal mecânico mais próximo — célula de comparativo com motivo num painel com resumo por modo — depende de reconhecer o resumo pelo slug. Fica para o quality gate, e a sessão sabe do ponto | — (lacuna declarada) |

---

## Regra R20 — Todo comparativo publicado vai do modo sem tenant (antes) para o com tenant (depois) do mesmo painel

> `RQ-25`, `RQ-11`, `P-04` · perfil **padrão** · técnica: **EP** + controles em cópia

```gherkin
  Regra: O comparativo de arquitetura compara o mesmo painel, de sem tenant para com tenant

    Esquema do Cenário: [CT-22] todo comparativo publicado vai do modo sem tenant para o com tenant do mesmo painel
      Dado os comparativos de arquitetura do mapa "<mundo>"
      Quando a guarda do mapa confere o par de cada comparativo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                     | resultado                                                                                                             |
        | publicados | aceita: o "antes" é o diagrama de arquitetura do painel sem tenant, e o "depois" é o do mesmo painel com tenant, os dois arquivos de diagrama do mapa, publicados ao lado com a prova de cada ligação (P-04) |
        | cópia com o comparativo do painel app indo de "com tenant" para "sem"     | reprova, nomeando o comparativo                                                                                       |
        | cópia com o comparativo indo de "admin sem tenant" para "app com tenant"  | reprova, nomeando o comparativo                                                                                       |
        | cópia com o comparativo cuja `base` não é arquivo de diagrama do mapa | reprova, nomeando o comparativo |
```

O comparativo é opcional pela R19: ele pode ser trocado por um motivo. Esta regra vale para todo comparativo que for publicado.

*(alterado em 2026-11-20: ADV2-20, P-04 emendada)* O comparativo é a exceção da P-04: a casca do archify não leva cards nem passaporte. Ele só é publicado quando a `base` e a `cabeca` são diagramas do mapa, publicados ao lado com a prova de cada ligação. Medido no esqueleto: o `app-resumo-sem-com` tem `base` `app-resumo-sem` e `cabeca` `app-resumo-com`, os dois publicados; o HTML dele, nos dois idiomas, tem `lang="en"`, nenhum card e nenhuma requisição para fora.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | antes e depois trocados: o delta mostra a organização como removida | CT-22 | linha "de com para sem": reprova; o mutante aceita |
| M2 | painéis misturados no par | CT-22 | linha "admin sem → app com": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV2-20) | o comparativo compara arquivos que não são diagramas do mapa publicados, e o leitor fica sem a prova das ligações dele | CT-22 | linha "a `base` não é arquivo de diagrama do mapa": reprova; o mutante aceita |

---

## Regra R21 — Nenhuma dependência nova de visualização no site: o `site/package.json` fica com as dependências da base nos quatro campos, `site/mapa/` só tem os arquivos do mapa, e o HTML de cada diagrama não carrega recurso de fora dele

> `RQ-25` · perfil **padrão** · técnica: **EP** (lista fechada: os quatro campos de dependência e os arquivos de `site/mapa/`) + controles em cópia + rastreio no site construído (CT-B01, no `05`)

```gherkin
  Regra: Nenhuma dependência nova de visualização além do archify

    Esquema do Cenário: [CT-23] o site não ganha dependência de visualização além do archify, nem pelo manifesto nem por arquivo em `site/mapa/`
      Dado as dependências de `site/package.json` da base: `@astrojs/starlight`, `astro`, `astro-mermaid`, `mermaid` e `sharp` para execução; `axe-core`, `playwright` e `wait-on` para desenvolvimento
      E o `site/package.json` e a pasta `site/mapa/` "<mundo>"
      Quando a guarda do mapa lê os quatro campos de dependência e a lista de arquivos de `site/mapa/`
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo | resultado | # partição |
        | publicados | aceita: as de execução e as de desenvolvimento são exatamente as da base; `optionalDependencies` e `peerDependencies` não existem ou estão vazias; e `site/mapa/` só tem o download do archify, o gerador, a interface, o posicionador, os dois arquivos do modelo (o de peças e o de ligações) e a pasta de diagramas | válida |
        | cópia com `d3` nas de execução | reprova, nomeando a dependência | fora da lista fechada |
        | cópia com `@antv/g6` nas de desenvolvimento | reprova, nomeando a dependência | fora da lista fechada |
        | cópia com `chart.js` em `peerDependencies` | reprova, nomeando a dependência | campo que a guarda não lia |
        | cópia de `site/mapa/` com `chart.min.js` | reprova, nomeando o arquivo | arquivo fora da lista |
```

O `package.json` da raiz já está congelado pelo `[CT-12]` herdado de `tests/Kit/SiteDeDocumentacaoTest.php`.

*(alterado em 2026-11-20: ADV-14)*
- **Lista fechada.** As dependências de desenvolvimento eram conferidas contra uma lista de recusa, e passava qualquer nome fora dela (`@antv/g6`, `sigma`, `vega`). Agora são lista fechada, como as de execução.
- **O canal que o manifesto não vê.** A biblioteca pode entrar por CDN ou por arquivo copiado para `site/public/`. Isso só aparece no HTML construído, então quem confere é o CT-B01, no `05`.
  - Nenhuma requisição sai da origem da prévia, fora `data:` e `blob:`.
  - O documento não tem `<script src>` nem `<link rel="stylesheet">`.
  - O HTML do archify no commit fixado é autocontido: o `assets/template.html` e os exemplos não trazem nenhum dos dois. A sessão mediu zero requisição externa num HTML gerado.

*(alterado em 2026-11-20: ADV2-15, D32)* A biblioteca de visualização também entra copiada para `site/mapa/` e embutida no HTML como `<script>` inline, sem requisição e sem `<script src>`, ou num campo de dependência que a guarda não lia. Por isso a guarda lê os quatro campos (`dependencies`, `devDependencies`, `optionalDependencies` e `peerDependencies`) e fecha a lista de `site/mapa/`: o download (`archify.mjs`), o gerador (`gerar.mjs`), a interface (`interface.json`), o posicionador (`posicionar.mjs`), os dois arquivos do modelo (`pecas.json` e `ligacoes.json`) e a pasta `diagramas/`. Medido: o `site/package.json` não tem `optionalDependencies` nem `peerDependencies`, e as outras duas são as da base. No esqueleto, `site/mapa/` tinha `archify.mjs`, `diagramas/`, `gerar.mjs` e `interface.json`; no commit `4adaf9a`, tem também `ligacoes.json`, `pecas.json` e `posicionar.mjs`. A D32 lista o download, o gerador, a interface, o posicionador e a pasta de diagramas, e não nomeia o modelo, que o plano põe na mesma pasta; sem os dois arquivos do modelo na lista, a linha "publicados" reprovaria o commit `4adaf9a`, e a divergência foi ao coordenador. O `archify check` não serve de oráculo: medido pela sessão, ele só acusa alteração do HTML com o recibo `.delivery.json`, que o `render` não grava.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | `d3` ou `cytoscape` nas dependências de execução, para desenhar o que o archify não desenha | CT-23 | linha `d3`: reprova; o mutante aceita |
| M2 | `@mermaid-js/mermaid-cli` nas de desenvolvimento, para pré-renderizar | CT-23 | linha "publicados": as de desenvolvimento são exatamente as da base, e o mutante não é; reprova; o mutante aceita |
| M3 | uma biblioteca PHP de grafo entra no `composer.json` para gerar o modelo | ⚠️ **sem matador**: os pacotes PHP do kit mudam toda semana, e uma lista congelada ficaria vermelha em toda adição legítima (é o precedente do `[CT-12]` de `tests/Kit/SiteDeDocumentacaoTest.php`). O gate confere no diff | — (lacuna declarada) |
| M4 — revisão adversarial (ADV-14) | a biblioteca nova entra nas de desenvolvimento com um nome fora da lista de recusa antiga (`@antv/g6`) | CT-23 | linha `@antv/g6`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-14) | o HTML do diagrama carrega a biblioteca por CDN, ou de um arquivo copiado para `site/public/` | CT-B01 | "nenhuma requisição sai da origem da prévia, e o documento não tem `<script src>` nem `<link rel="stylesheet">`": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-15) | a biblioteca entra em `optionalDependencies` ou em `peerDependencies`, que a guarda não lia | CT-23 | linha `chart.js` em `peerDependencies`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-15) | o gerador copia a biblioteca para `site/mapa/` e a embute no HTML como `<script>` inline | CT-23 | linha `chart.min.js` em `site/mapa/`: reprova; o mutante aceita |

---

## Regra R22 — Há fluxo publicado, e, nos tipos `workflow`, `sequence` e `dataflow`, todo passo percorre peça do modelo

> `RQ-09` (parcial; recorte aberto em Q6), `P-03` · perfil **padrão** · técnica: **EP** (tipo de fluxo) + controles em cópia

```gherkin
  Regra: A demonstração exibe fluxos do kit, e cada passo deles é peça que existe

    Esquema do Cenário: [CT-24] o mapa publica fluxos, e todo passo deles percorre peça do modelo
      Dado os arquivos de diagrama do mapa "<mundo>"
      Quando a guarda do mapa lê os diagramas de fluxo dos tipos `workflow`, `sequence` e `dataflow`
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                       | resultado                                                                                                   |
        | publicados                                                  | aceita: há ao menos um diagrama de fluxo, de um dos quatro tipos de fluxo, e todo passo desses três tipos nomeia peça do modelo |
        | cópia em que um passo de fluxo nomeia `ConviteService`      | reprova, nomeando o passo e o diagrama                                                                      |
        | cópia sem nenhum diagrama de fluxo                          | reprova: nenhum fluxo publicado                                                                             |
        | cópia em que, no `dataflow`, uma ponta de um passo entre dois depósitos vira nó `backend` que nomeia `ConviteService` | reprova, nomeando o passo e o diagrama                                          |
```

*(alterado em 2026-11-20: ADV-11 e ADV-32)*
- **Os tipos se separam.** O `lifecycle` não nomeia peça: os estados não estão entre os 13 tipos da R8. Por isso o CT-24 confere os passos só dos outros três tipos de fluxo. O estado e a transição do `lifecycle` têm âncora no código (R35).
*(alterado em 2026-11-20 — guardas do passo 8: o executor leu "passo" e "nomeia" sem definição no `04`, e o dado real tem passo entre depósitos)*
- **O passo.** É a aresta: a mensagem do `sequence` e a seta do `workflow` e do `dataflow`. Ele percorre peça quando uma das pontas é peça do modelo — pelo id ou pelo nome exato do rótulo, como na R19 — ou quando a prova do próprio passo está no arquivo de uma peça. A âncora da outra ponta não conta: era ela que deixava passar a linha `ConviteService`.
- **O depósito do `dataflow` fica de fora, como o estado do `lifecycle`.** O passo entre dois nós de depósito (`type: database` — arquivo de configuração, migração de settings, tabela) não nomeia peça, porque depósito não é tipo de peça da R8; a âncora dele é guardada pela R35. Medido no commit do passo 8: só o `configuracoes-config` tem passo assim (`.env` → `config/*.php` → migração de settings → tabela `settings`). O passo com ao menos uma ponta fora de depósito continua obrigado a percorrer peça, e a linha nova do Exemplo prova que a exceção não engole o mutante M2.
- **A âncora vem da P-03.** Pelo glossário, fluxo não é ligação, e a RQ-24 não pede prova em cada seta. Mas a P-03 vale para todo fluxo publicado: cada transição afirma algo sobre o código e precisa de uma guarda que falhe quando o código deixar de bater. Essa guarda é a âncora da R35, e não depende da lista de fluxos (Q6).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o mapa publica só arquitetura, sem fluxo | CT-24 | linha "sem nenhum diagrama de fluxo" reprova; a linha "publicados" exige ao menos um; o mutante aceita |
| M2 | passo de fluxo escrito de memória, nomeando peça que não existe | CT-24 | linha `ConviteService`: reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-32) | dois passos seguidos, ancorados no mesmo método, saem na ordem trocada *(alterado em 2026-11-20: era "a ordem diverge do código", que não tinha matador; a ordem entre arquivos ou métodos diferentes ficou na R35, como M4)* | CT-39 | linha "duas transições seguidas, ancoradas no mesmo método, trocadas de ordem": reprova; o mutante aceita |

---

## Regra R23 — O mapa nasce no build do commit que publica, os dois fluxos de CI o conferem com o conferidor do mapa antes de enviar, e a falha do conferidor reprova o job

> `P-07`, `RQ-03`, `RQ-17`, `P-11` · perfil **padrão** · técnica: **EP por workflow** + controles em cópia

```gherkin
  Regra: O que o mapa afirma corresponde à revisão publicada, e é conferido antes de sair

    Esquema do Cenário: [CT-25] o mapa nasce no build do workflow, e o conferidor do mapa roda depois do de acessibilidade, com a mesma prévia, antes de o artefato sair
      Dado o workflow "<workflow>"
      Quando a guarda do mapa lê os passos dele e o `site/package.json`
      Então o passo de build roda o build do site, e o build do site gera os diagramas do mapa
      E o conferidor do mapa, `site/verifica-mapa.mjs`, roda depois do conferidor de acessibilidade, com a mesma prévia de pé, <e antes>
      E nenhum HTML de diagrama do mapa está versionado no repositório

      Exemplos:
        | workflow                                    | e antes                         |
        | `.github/workflows/pages.yml`               | e antes do envio do artefato, e o job `build` só roda na `main` (`if: github.ref == 'refs/heads/main'`) |
        | o job `site` de `.github/workflows/ci.yml`  | (o job não envia artefato)      |

    Esquema do Cenário: [CT-42] a falha do conferidor do mapa reprova o job, e o build do Pages só roda na `main`
      Dado o workflow "<workflow>" "<mundo>"
      Quando a guarda do mapa lê o passo que roda o conferidor do mapa
      Então o resultado é "<resultado>"

      Exemplos:
        | workflow                                    | mundo                                                                  | resultado                                                    |
        | `.github/workflows/pages.yml`               | publicado                                                              | aceita: o passo não tem `continue-on-error`, e o comando não engole a falha |
        | o job `site` de `.github/workflows/ci.yml`  | publicado                                                              | aceita (idem)                                                |
        | `.github/workflows/pages.yml`               | cópia em que o passo do conferidor do mapa tem `continue-on-error: true` | reprova, nomeando o passo                                  |
        | o job `site` de `.github/workflows/ci.yml`  | cópia em que o passo roda `node verifica-mapa.mjs \|\| true`             | reprova, nomeando o passo                                    |
        | `.github/workflows/pages.yml`               | cópia em que o job `build` não tem a condição `github.ref == 'refs/heads/main'` | reprova, nomeando o job |
```

Molde: `[CT-40]` e `[CT-42]` de `tests/Kit/SiteDeDocumentacaoTest.php` (passos do workflow por posição).

*(alterado em 2026-11-20: ADV-13)* Sem o CT-42, o conferidor rodava no lugar certo e não reprovava nada, e o artefato saía com o mapa reprovado. "Engolir a falha" é o `|| true` ou qualquer `||` depois do comando do conferidor.

*(alterado em 2026-11-20: ADV2-16 e ADV2-31, D31)* O CT-42 lê o YAML; quem prova que a reprovação do conferidor vira código de saída é o CT-B06, no `05`, com o conferidor rodado sobre um HTML do mapa quebrado. Medido no commit `4adaf9a`: o `site/verifica-mapa.mjs` grava `process.exitCode = reprovado ? 1 : 0`; rodado numa pasta de rascunho sobre uma cópia em que o HTML do `app-resumo-sem` não desenha nada, ele saiu com código 1 ("nenhum `<svg role="img">` em 15000 ms", nas quatro entradas da amostra), e sobre a cópia com o HTML gerado, com 0. O job `build` do `pages.yml` só roda na `main` (medido: `if: github.ref == 'refs/heads/main'`): um dispatch de outro ramo publicaria links para um commit que some com o ramo (Q9).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o HTML do mapa é gerado na máquina do mantenedor e versionado: envelhece e não é o do commit | CT-25 | "nenhum HTML de diagrama do mapa está versionado": reprova; o mutante aceita |
| M2 | o workflow chama `astro build` direto e pula a geração do mapa | CT-25 | "o build do site gera os diagramas do mapa": reprova; o mutante aceita |
| M3 | o conferidor do mapa roda depois do envio do artefato, ou antes de a prévia subir | CT-25 | linha `pages.yml`: "depois do conferidor de acessibilidade, com a mesma prévia, e antes do envio" reprova; o mutante aceita |
| M4 | o workflow roda só o conferidor de acessibilidade, e o do mapa nunca roda | CT-25 | "o conferidor do mapa roda": reprova nos dois workflows; o mutante aceita |
| M5 — revisão adversarial (ADV-13) | o passo do conferidor do mapa entra sem poder reprovar (`continue-on-error: true`, ou `\|\| true`) | CT-42 | linhas do `continue-on-error` e do `\|\| true`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV2-16) | o conferidor registra a reprovação no log e termina com código 0, e o job passa | CT-B06 | linha do controle de processo: o conferidor, sobre um HTML do mapa que não desenha peça nenhuma, termina com código diferente de zero; o mutante termina com 0 |
| M7 — revisão adversarial (ADV2-31) | o build do Pages roda a partir de outro ramo, por dispatch, e os links de prova apontam para um commit que some com o ramo | CT-42 | linha "o job `build` sem a condição da `main`": reprova; o mutante aceita |
| M8 — step 9 (A12) | o conferidor se relança pelo caminho da URL do módulo com o escape dela (`new URL(import.meta.url).pathname`, com `%20` e `%C3%A3`), e, num clone com espaço ou acento no caminho, o controle de processo reprova porque o filho não acha o arquivo — vermelho falso | CT-B06 | linha do controle de caminho: o caminho derivado da URL de módulo numa pasta "meus projetos/João" é o do disco, sem escape; o mutante devolve `meus%20projetos/Jo%C3%A3o` e reprova |

*(alterado em 2026-11-21 — step 9, achado A12)* Medido no commit `168d1827`: um módulo em `…/meus projetos/João/`, com a mesma expressão do conferidor (`site/verifica-mapa.mjs`, `conferirControleDeProcesso`, no commit `168d1827`), deriva `…/meus%20projetos/Jo%C3%A3o/…`, que não existe no disco; o `fileURLToPath` deriva o caminho certo. O CI de hoje roda num caminho sem espaço nem acento, e por isso o defeito só aparece na máquina de quem tem um.

---

## Regra R24 — Todo diagrama publicado abre desenhado, com as peças que a fonte dá a ele — e a marca da peça condicional à vista no carregamento — e a interface do visualizador montada, sem erro de console nem exceção, no idioma da página

> `RQ-07`, `RQ-03`, `P-02`, `RQ-27` *(alterado em 2026-11-23 — Adendo 2: a marca no enunciado, e a RQ-27 na origem)* · perfil **padrão** · técnica: **rastreio exaustivo** no site construído · cenário: **CT-B01, no `05`** (costura browser, G4)

*(alterado em 2026-11-20: ADV-31, ADV-28 e ADV-29)*
- **A condição saiu.** A metade de condição do CT-B01 (o passo 4) e o M5 estão estacionados na Q11, com o ID reservado. A RQ-11 saiu da origem desta regra: o que restou não afirma modo.
- *(alterado em 2026-11-23 — Adendo 2)* **A condição voltou.** O passo 4 do CT-B01 confere, no HTML construído, a marca de cada peça condicional que o diagrama desenha, à vista no carregamento — sem zoom, sem busca e sem foco —, no idioma da página: no nó, com opacidade 1 no nível de leitura do carregamento; na peça que é membro de agrupamento, na linha dela no card que a nomeia. "À vista no carregamento" vem da RQ-28: a peça desenhada sem a marca à vista é mostrada como sempre disponível. O esperado sai do modelo, nunca do HTML. A origem ganhou a RQ-27, e a regra continua sem afirmar o modo, que é da R41. Volta o M5. Lugar e redação da marca: Q?3 (`@premissa`). **Medido no HEAD `c642c23c`:** nenhuma peça condicional tem marca; o passo 4 reprova as 14 do discriminante nos 70 lugares em que os arquivos de hoje as desenham, nos dois idiomas.
- **O oráculo é o rótulo de cada peça.** O CT-B01 confere, pelo id, que cada peça desenhada tem o rótulo que a fonte dá a ela, e não só a contagem. Os pares id e rótulo pegam também a troca de rótulos entre peças, que um conjunto de nomes não pega.
- **A escuta.** Ela pega também a exceção não capturada.
- **O CT-B01 serve também à R21.** Nenhum recurso de fora entra no HTML; o mutante dela, R21-M5, está na tabela da R21.
- *(alterado em 2026-11-20: ADV2-20, ADV2-24 e ADV2-32)* **O comparativo também é aberto,** para a escuta de erro e a de recurso de fora (P-04 emendada); os cards e o passaporte dele ficam fora (CT-B02 e CT-B03).
- **A exceção da interação** — a busca e o passaporte — passa a ser pega no CT-B03 e no CT-B04, que abrem os dois; o CT-B01 só abre o diagrama.
- **O rótulo mostrado é o da fonte, inteiro.** O oráculo é a igualdade, pelo id, com o rótulo da fonte no idioma da página, e não "sem reticências": medido, o esqueleto escreve "…" à mão em 33 rótulos e 29 sublabels para caber na caixa, e o archify recusa no render o rótulo mais largo que a caixa nos tipos `architecture`, `dataflow`, `sequence` e `workflow` ("Label … is wider than component"). Medido no Chromium: o `[data-node-id]` sem escopo pega também o resultado da busca, um botão por peça (82 elementos para 41 peças no `admin-acesso` do esqueleto); dentro do SVG, há um elemento por peça (21 no `app-acesso` e 52 no `app-registro-sem` do commit `4adaf9a`). Por isso o seletor é `svg [data-node-id]`, e a conta é pelo conjunto de ids. *(corrigido em 2026-11-20: a leitura antiga, "dois elementos por peça no DOM", contava o resultado da busca)*
- **A F-nn no desenho da peça** (D29) e o mutante dela, R6-M10, estão na R6.
- *(alterado em 2026-11-20: medido no commit `4adaf9a`)* **O título mostrado é o `<h1>`.** Ele é igual ao título do arquivo no idioma da página. O `document.title` é a moldura do archify, a chave `page.title`: "Diagrama {título}" em pt e "{title} Diagram" em en. O comparativo não tem `<h1>` com o título dele: a casca mostra o subtítulo "base → cabeça", com os títulos dos dois diagramas, e fica fora do título, das peças e dos rótulos.
- **O rótulo de cada peça é o da fonte.** No diagrama escrito à mão, é o campo de rótulo do tipo no esquema do archify: `label` em `architecture`, `workflow`, `sequence`, `dataflow`, `lifecycle`, `erd`, `tree` e `class`; `title` em `timeline`; `name` em `waterfall`. No construído, é o nome da peça no modelo, e o das caixas de agrupamento e de porta, o do arquivo. Medido: rótulo igual, pelo id, em todas as peças dos 9 diagramas aceitos no commit `4adaf9a` fora o comparativo, nos dois idiomas.
- **O desenho já vem no HTML.** O archify grava o SVG pronto: sem JavaScript, o `app-acesso` tem as 21 peças e as 17 arestas. O que só existe com o script rodando é a interface do visualizador — a busca, o nível de leitura, os textos de interface, o passaporte. O CT-B01 confere que ela montou: a busca tem um resultado por peça desenhada, pelo id. Medido com JavaScript no `app-acesso`, no `app-registro-sem`, no `app-resumo-sem`, no `infra-registro`, no `kit-install-ci` e no `marcos-do-changelog`: 21 de 21, 52 de 52, 9 de 9, 39 de 39, 12 de 12 e 26 de 26; sem JavaScript, nenhum resultado. Os seletores são `svg [data-node-id]`, porque o resultado da busca também traz `data-node-id`.

A tabela abaixo é a mesma do CT-B01.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o conferidor do mapa lista os diagramas varrendo o `dist/` por `index.html`, como o de acessibilidade (`site/verifica-acessibilidade.mjs:'index.html':78`), e não acha nenhum HTML do mapa | CT-B01 | "o total de diagramas abertos é o número de arquivos vezes dois": dá zero e reprova; o mutante aceita |
| M2 | o gerador grava o HTML sem declarar UTF-8, e os acentos se trocam | CT-B01 | "o `<h1>` é o título do arquivo no idioma da página, com os acentos intactos": reprova; o mutante aceita |
| M3 | o HTML en sai com os textos pt | CT-B01 | idioma en, "o `<h1>` é o título do arquivo no idioma da página": reprova; o mutante aceita |
| M4 | o script do visualizador não entra no HTML, ou quebra antes de montar a interface: o SVG estático aparece, sem busca nem nível de leitura *(reescrito em 2026-11-20: medido no commit `4adaf9a`, o SVG vem pronto no HTML, e "dá zero peça" não matava o mutante)* | CT-B01 | "a busca tem um resultado por peça desenhada, pelo id": dá zero resultado e reprova; o mutante aceita |
| M5 *(alterado em 2026-11-23 — Adendo 2: destravado; estava estacionado na Q11)* | a marca chega à entrada do archify, mas o desenho a perde, ou a deixa só no detalhe fino que o nível de leitura do carregamento esconde | CT-B01 (passo 4) | "toda peça condicional que o diagrama desenha mostra, à vista no carregamento, a marca da condição que o modelo dá a ela": a opacidade computada é 0, ou o texto não está no nó; reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-28) | o desenho tem o mesmo número de peças, com os rótulos trocados entre elas | CT-B01 | "toda peça que a fonte dá a ele está desenhada e tem, pelo id, o rótulo que a fonte dá a ela no idioma da página": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-29) | o script do visualizador lança exceção não capturada na busca ou no detalhe, e o conferidor só escuta o console *(alterado em 2026-11-20: ADV2-24, o matador passou do CT-B01, que não abre a busca, ao CT-B03 e ao CT-B04)* | CT-B03, CT-B04 | "com a busca aberta e o passaporte aberto, nenhuma exceção não capturada e nenhum erro de console": reprova; o mutante aceita |
| M8 — step 9 (A14) | a raiz da árvore de registro sai com o rótulo de outro painel, ou sem o nome do painel, e o conferidor, que dá à raiz o esperado `null`, não a confere | CT-B01 | "na árvore de registro, a raiz é a peça painel do arquivo e tem o rótulo que a fonte dá a ela": reprova; o mutante aceita |

*(alterado em 2026-11-21 — step 9, achado A14)* A raiz da árvore de registro é uma peça do modelo (`painel:app`), e por isso cai na regra do rótulo de cada peça: no construído, é o nome dela no modelo (`/app`). O conferidor dava à raiz o esperado `null` e tirava o rótulo dela do próprio HTML, para o CT-B02; o cabeçalho dele promete o esperado da fonte, nunca do `dist/`. Medido no commit `168d1827`, com a árvore do `/app` sem tenant gerada pelo `site/mapa/gerar.mjs --saida` numa pasta de rascunho, nos dois idiomas: a raiz desenha "Painel /app" em pt e "Panel /app" em en, e os itens de prova começam por esse rótulo; o gerador monta a raiz das quatro árvores do mesmo jeito (`site/mapa/gerar.mjs`, `arvoreDeRegistro`, no commit `168d1827`). O nome da peça no modelo é `/app` (o do `/admin`, `/admin`; o do `/infra`, `/infra`). O oráculo da R24, sem ajuste, reprova as 8 raízes de hoje (4 árvores × 2 idiomas) e todo item de prova delas no CT-B02. *(alterado em 2026-11-21 — step 9: Q23 decidida pela sessão)* A raiz leva o nome da peça no modelo (`/app`), como toda peça: as 8 raízes, e os itens de prova que começam por elas, são vermelho de implementação, e o CT-B01 e o CT-B02 tiram o rótulo esperado da raiz do modelo, nunca do HTML.

---

## Regra R25 — O README de cada idioma leva ao mapa do mesmo idioma

> `RQ-02`, `P-02` · perfil **mínimo** · técnica: **EP** (idioma)

```gherkin
  Regra: O README ganha o lugar que leva à demonstração, nos dois idiomas

    Esquema do Cenário: [CT-26] o README de cada idioma leva ao mapa do mesmo idioma
      Dado o "<readme>"
      Quando a guarda do mapa procura nele o link para o mapa interativo
      Então há um link para a página do mapa em "<idioma>" no site do kit
      E nenhum link para a página do mapa do outro idioma

      Exemplos:
        | readme         | idioma |
        | `README.md`    | pt     |
        | `README.en.md` | en     |
```

O link vale com a URL do site (`…/<idioma>/referencia/mapa-interativo/`) ou com o stub `.html`, que os links atuais do README usam. A resolução do link fica com o `[CT-14]` herdado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | só o README pt ganha o lugar | CT-26 | linha `README.en.md`: não há link, reprova; o mutante aceita |
| M2 | o README en leva ao mapa pt | CT-26 | linha `README.en.md`, "nenhum link para a página do outro idioma": reprova; o mutante aceita |

---

## Regra R26 — Os diagramas Mermaid da base continuam onde estavam

> `P-01`, `RQ-25` · perfil **mínimo** · técnica: **EP** (inventário da base)

```gherkin
  Regra: A demonstração nova se soma aos diagramas Mermaid, sem substituí-los

    Cenário: [CT-27] todo bloco Mermaid da base continua no arquivo em que estava, nos dois idiomas
      Dado o inventário Mermaid da base: DG-01 a DG-20 por idioma, cada um no seu arquivo de `docs/`, e o DG-01 também no README de cada idioma
      Quando a guarda do mapa extrai os blocos Mermaid do README e das páginas de cada idioma
      Então cada DG da base está no mesmo arquivo da base, fora de comentário HTML
      E entre eles estão o DG-11, no catálogo de features, e o DG-19, em "Desenvolvendo o kit": duas páginas que esta entrega reescreve
```

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* O inventário dos blocos Mermaid da base fica congelado na guarda (21 pares por idioma, medidos com `blocosMermaidDe()` no `d9608c5a`). O git só confere essa lista quando `git cat-file -t d9608c5a` resolve: o checkout raso do `ci.yml` não traz o commit, e o caso não pode quebrar no CI por isso.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o DG-11 some na reescrita do catálogo | CT-27 | "o DG-11 no catálogo de features": reprova; o mutante aceita (o `[CT-01]` herdado de `DiagramasDaArquiteturaTest` também mata) |
| M2 | o bloco do README é trocado por uma imagem ou por um link do mapa | CT-27 | o DG-01 sai do README: reprova; o mutante aceita |
| M3 | DG-01, DG-02, DG-03 e DG-13 vão da página de diagramas para a página do mapa | CT-27 | "no mesmo arquivo da base": reprova; o mutante aceita |

---

## Regra R27 — Todo pacote instalado está na referência de pacotes e numa F-nn de origem pacote, ou na lista dos sem feature própria, com o motivo; e todo pacote que a referência lista está instalado

> `RQ-19`, `RQ-04`, `P-03` · perfil **padrão** · técnica: **EP** por partição de pacote + controles em cópia *(regra nova em 2026-11-20: Q7; alterada na terceira rodada: Q16 e Q17)*

A Q7 e a Q16 foram fechadas pelo `00`: a RQ-19 diz "pacotes instalados", e instalado é o que os manifestos declaram. São eles:
- o `composer.json`, em `require` e `require-dev`;
- o `package.json` da aplicação, em `dependencies`, `devDependencies` e `optionalDependencies`.

A referência de pacotes (`docs/{pt,en}/referencia/pacotes-instalados.md`) é o meio de mostrá-los. Ela conta nas seções de produção, na `## Desenvolvimento (require-dev)` e na `## Front-end (package.json)`, mas não na `## Avaliados e não adotados`. Na base ela está defasada em quatro pacotes, e a entrega a corrige (Setup Global).

```gherkin
  Regra: Os pacotes instalados chegam à referência e ao catálogo, e a referência só lista pacote instalado

    @premissa
    Esquema do Cenário: [CT-28] todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature própria; e a referência só lista pacote instalado
      Dado os pacotes que os manifestos declaram e a referência de pacotes em "<idioma>" "<mundo>", fora a seção dos avaliados e não adotados
      Quando a guarda do mapa confere os dois conjuntos
      Então o resultado é "<resultado>"
      E, na publicada, a guarda conferiu ao menos os 91 pacotes dos manifestos da base, inclusive o `@laravel/multiplex`, de `optionalDependencies`

      Exemplos:
        | idioma | mundo                                                                                                  | resultado                                                                                                                                                                                                                                                             | # partição                  |
        | pt     | publicada                                                                                              | aceita: todo pacote dos manifestos está na referência e é nomeado por ao menos uma F-nn de origem pacote, ou pacote com customização própria, ou está na lista da guarda dos pacotes sem feature própria, com o motivo; e todo pacote da referência está nos manifestos | válida                      |
        | en     | publicada                                                                                              | aceita (idem)                                                                                                                                                                                                                                                         | válida                      |
        | pt     | cópia da referência sem a linha de `hoceineel/filament-quick-action-dock`, que os manifestos declaram  | reprova, nomeando o pacote                                                                                                                                                                                                                                            | instalado e não listado     |
        | pt     | cópia que lista `vendor-inexistente/plugin`, que não está instalado                                    | reprova, nomeando o pacote                                                                                                                                                                                                                                            | listado e não instalado     |
        | pt     | cópia do catálogo sem a F-nn que nomeia `shuvroroy/filament-spatie-laravel-health`                     | reprova, nomeando o pacote                                                                                                                                                                                                                                            | pacote sem F-nn             |
        | pt     | cópia da lista da guarda com `laravel/tinker`, sem o motivo                                            | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo ausente              |
        | pt     | cópia da lista da guarda com `laravel/tinker`, com o motivo "—"                                        | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo de enfeite           |
        | pt     | cópia da lista da guarda com `laravel/tinker`, com o motivo "não se aplica", sem porquê                | reprova, nomeando o pacote                                                                                                                                                                                                                                            | motivo de enfeite           |
        | pt     | cópia da lista da guarda com `shuvroroy/filament-spatie-laravel-health`, com motivo, cujo plugin o `/infra` registra | reprova, nomeando o pacote                                                                                                                                                                                                                                | pacote com peça registrada na lista |
```

`@premissa` de mecanismo, decidida pela sessão (Q17):
- a linha do catálogo nomeia o pacote pelo nome do Composer ou do npm, o mesmo do link da referência;
- a lista dos pacotes sem feature própria mora na guarda, com o motivo de cada um, como a lista das páginas (Q14b).

O pacote vem do endereço do link: o texto `pest-plugin-laravel` leva a `pestphp/pest-plugin-laravel`. O `php` é requisito de plataforma, não pacote, e fica fora da conta.

*(alterado em 2026-11-20: ADV-04 e ADV-22)*
- **Pacote com peça registrada não vai para a lista.** O pacote cuja classe um painel registra nunca entra na lista dos sem feature própria: a classe do plugin, do recurso, da página ou do widget está em `vendor/{pacote}/`, e o registro dos painéis está de pé. Essa linha lê o registro, o que a aplicação de pé do arnês do kit permite.
- **Motivo de enfeite** tem a definição do Setup Global.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* A lista dos pacotes sem feature própria mora na guarda da publicação, com o motivo de cada um: `laravel/framework`, `laravel/tinker`, `laravel/reverb`, `predis/predis`, `livewire/blaze`, `awcodes/filament-badgeable-column`, `lara-zeus/progress`, `syriable/filament-activitylog`, `filament/upgrade`, `laravel/pail`, `nunomaduro/collision`, `@tailwindcss/vite`, `concurrently`, `laravel-vite-plugin`, `tailwindcss`, `vite` e `@laravel/multiplex` — os 17 instalados que nenhuma F-nn de origem pacote nomeia, conferidos pela sessão contra a lista que o trabalho do catálogo sugeriu. "Nomeado" é o pacote entre crases na coluna Origem que começa por "pacote"/"package"; na referência, conta todo link da primeira célula.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um pacote sai do `composer.json`, e a referência e a F-nn que o cita ficam | CT-28 | linha `vendor-inexistente/plugin`: reprova; o mutante aceita |
| M2 | pacote instalado fora da referência (o do dock, na base): a guarda parte da referência e não dos manifestos | CT-28 | linha "sem a linha de `hoceineel/filament-quick-action-dock`": reprova; o mutante aceita |
| M3 | a unificação esquece um pacote, que fica sem F-nn e fora da lista | CT-28 | linha "sem a F-nn do Health": reprova; o mutante aceita |
| M4 | pacote jogado na lista dos sem feature, sem o motivo | CT-28 | linha `laravel/tinker`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-04) | o pacote cujo plugin um painel registra vai para a lista dos sem feature, com um motivo qualquer ("dependência do painel") | CT-28 | linha do Health na lista: reprova; o mutante aceita, porque o controle antigo usava um pacote sem peça |
| M6 — revisão adversarial (ADV-22) | pacote na lista com motivo de enfeite | CT-28 | linhas do motivo "—" e do "não se aplica" sem porquê: reprova; o mutante aceita |

Saiu o mutante "a guarda para de ler a referência na seção dos avaliados e perde a de front-end". Com as duas direções conferidas, esse erro da guarda não deixa defeito passar. Os sete pacotes do front-end (seis de `devDependencies` e o opcional) viram "instalado e não listado", e a guarda reprova a implementação correta, o que denuncia a si mesma. O mesmo vale para a guarda que ignora o `optionalDependencies` ou lê a seção dos avaliados como instalados.

---

## Regra R28 — Toda peça que um painel registra — recurso, página, widget e plugin —, nos dois modos, e toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista da guarda das peças e classes sem feature própria, com o motivo

> `RQ-20`, `RQ-04`, `RQ-05`, `P-05`, `P-12` · perfil **padrão** · técnica: **tabela de decisão** (3 painéis × 2 modos × 4 tipos de peça) + EP (classe de `app/`) + controles em cópia *(regra nova em 2026-11-20: Q8; alterada na quarta rodada: P-12, que veio do ADV-33)*

A Q8 foi reclassificada para a raia desenho e decidida pela sessão: é o piso mecânico da varredura da RQ-20. O middleware fica fora do piso do registro.

*(alterado em 2026-11-20: P-12)* A P-12 estendeu o piso e deu a ele a lista com o motivo:
- **O piso.** Vale para a peça registrada e também para toda classe de `app/` fora de `app/Filament`.
  - "Classe" é o tipo que cada arquivo PHP dali declara. São 135 na base: 123 classes, 7 enums e 5 traits, a conta da própria P-12.
  - A P-12 mediu que 103 delas já eram peça na varredura do step 3.
- **A guarda.** Reprova a peça ou a classe que não entrega F-nn e não está na lista com o motivo.
- **O limite.** Além desse piso, a completude da varredura não tem garantia, e a página do mapa diz isso (R33, CT-34).

```gherkin
  Regra: A varredura não deixa peça registrada nem classe de `app/` fora do catálogo sem dizer por quê

    Esquema do Cenário: [CT-29] sem tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso
      E o painel "<painel>" do registro de painéis, e o modelo e a lista da guarda "<mundo>"
      Quando a guarda do mapa procura a F-nn de cada recurso, página, widget e plugin que o painel registra
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                          | resultado                                                                                              |
        | admin  | publicado                                                      | aceita: cada peça registrada é, no modelo, peça de ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo |
        | app    | publicado                                                      | aceita (idem)                                                                                          |
        | infra  | publicado                                                      | aceita (idem), inclusive a página `HealthCheckResults`, que o plugin registra                           |
        | admin  | cópia em que `TenantResource` não entrega F-nn nenhuma e está fora da lista | reprova, nomeando `TenantResource`                                                        |
        | infra  | cópia em que a página `HealthCheckResults` não entrega F-nn nenhuma e está fora da lista | reprova, nomeando a página                                                     |
        | admin  | cópia em que o widget `AccountWidget` aponta a F-999, que o catálogo não tem | reprova, nomeando o widget                                                                  |
        | admin  | cópia em que o widget `AccountWidget` não entrega F-nn e está na lista sem o motivo | reprova, nomeando o widget                                                          |

    Esquema do Cenário: [CT-30] com tenant, toda peça que o painel registra entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro
      E o painel "<painel>" do registro de painéis
      Quando a guarda do mapa procura a F-nn de cada recurso, página, widget e plugin que o painel registra
      Então cada peça registrada é, no modelo, peça de ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo
      E a guarda conferiu ao menos <âncora>

      Exemplos:
        | painel | âncora                                               |
        | app    | o plugin `FilamentSearchSpotlightPlugin`             |
        | admin  | o recurso `TenantResource`                           |
        | infra  | a página `HealthCheckResults`, que o plugin registra |

    Esquema do Cenário: [CT-41] toda classe de `app/` fora de `app/Filament` entrega ao menos uma F-nn do catálogo, ou está na lista com o motivo
      Dado as classes de `app/` fora de `app/Filament`, lidas da árvore, e o modelo e a lista da guarda "<mundo>"
      Quando a guarda do mapa procura a F-nn de cada classe
      Então o resultado é "<resultado>"
      E, no publicado, a guarda conferiu ao menos as 135 classes da base

      Exemplos:
        | mundo                                                                                                     | resultado                                                                                                     | # partição                  |
        | publicado                                                                                                 | aceita: cada classe é peça do modelo que entrega ao menos uma F-nn que existe no catálogo, ou está na lista da guarda com o motivo | válida |
        | cópia em que `App\Console\Commands\KitUpdate` não entrega F-nn e está fora da lista                         | reprova, nomeando a classe                                                                                    | classe sem F-nn nem motivo  |
        | lista de classes com uma a mais, `App\Support\RelatorioNovo`, que o modelo não tem                          | reprova, nomeando a classe                                                                                    | classe nova esquecida       |
        | cópia da lista com `App\Support\TelaDeAutenticacao`, sem o motivo                                          | reprova, nomeando a classe                                                                                    | motivo ausente              |
        | cópia da lista com `App\Traits\TemUuid`, com o motivo "—"                                                  | reprova, nomeando a classe                                                                                    | motivo de enfeite           |
        | cópia da lista com `App\Console\Commands\KitUpdate`, com o motivo "classe de apoio"                       | reprova, nomeando a classe                                                                                    | comando `kit:*` na lista    |
```

O widget `AccountWidget` é registrado no `/admin` em `app/Providers/Filament/AdminPanelProvider.php:AccountWidget:158`.

A guarda das classes recebe a lista de classes como parâmetro. Assim o controle acrescenta uma classe que não existe, sem tocar na árvore. O comando `kit:update` e o serviço `TelaDeAutenticacao` são os exemplos que a revisão deu de feature fora do registro (ADV-33): `app/Console/Commands/KitUpdate.php` e `app/Support/TelaDeAutenticacao.php`.

*(alterado em 2026-11-20: ADV2-18)* Comando `kit:*` nunca entra na lista das sem feature própria, como a página de `recursos/` não entra na da R5 e o pacote com peça registrada não entra na da R27. Medido com `Artisan::all()`: 9 comandos `kit:*` — `kit:admin`, `kit:arte`, `kit:cobertura`, `kit:convites-lembrar`, `kit:info`, `kit:install`, `kit:midia-privada`, `kit:tenancy` e `kit:update`. No modelo do commit `4adaf9a`, nenhum dos 9 tem F-nn, e o catálogo da entrega (`0f38e2f`) dá a F-102 ao `kit:install` e a F-105 ao `kit:update`: o CT-41 e o CT-06 reprovam esse modelo até a sessão ligar as F-nn.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* A lista das peças e classes sem feature própria mora na guarda do modelo (`tests/Kit/MapaInterativoTest.php`), com o motivo de cada uma: hoje, só classes de `app/` (o `Controller` base, o `BooleanoDoEnv` e o `NumeroDoEnv`). Nenhuma peça registrada está nela; a guarda com tenant (CT-30) a recebe vazia, e passa porque toda peça registrada entrega F-nn.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | um recurso novo entra no painel sem F-nn e fora da lista (a varredura o esqueceu) *(alterado em 2026-11-20: P-12, a lista com motivo vale para peça registrada)* | CT-29 | linha "`TenantResource` sem F-nn e fora da lista": reprova; o mutante aceita |
| M2 | a guarda confere só `getResources()` e esquece a página que o plugin registra | CT-29, CT-30 | CT-29, linha "`HealthCheckResults` sem F-nn", e CT-30, linha "infra": reprova; o mutante aceita |
| M3 | a peça aponta uma F-nn que o catálogo não tem | CT-29 | linha "`AccountWidget` aponta a F-999": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-33) | a varredura para no registro dos painéis, e o comando `kit:update` fica sem F-nn e fora da lista | CT-41 | linha do `KitUpdate`: reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-33) | uma classe nova entra em `app/`, e ninguém a cataloga | CT-41 | linha da classe a mais, `RelatorioNovo`: reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-33) | peça ou classe na lista sem o motivo, ou com motivo de enfeite | CT-29, CT-41 | CT-29, linha do `AccountWidget` na lista sem o motivo, e CT-41, linhas do `TelaDeAutenticacao` e do `TemUuid`: reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-18) | a feature que a varredura achou vai para a lista das sem feature própria com um motivo crível ("classe de apoio") | CT-41 | linha do `KitUpdate` na lista: reprova; o mutante aceita |

---

## Regra R29 — Cada marco da linha do tempo cita uma versão do CHANGELOG com a data dela, e o título visível do marco aparece na seção dessa versão; e cada tempo medido traz a data e a fonte da medição

> `P-03`, `P-06` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: Q10)*

A Q10 é de raia desenho e foi decidida pela sessão. Cada marco da linha do tempo afirma features de uma versão, e cada barra da cascata é um passo de um fluxo, então a P-03 vale para os dois. Os marcos são escolhidos e não precisam ser todas as versões. Se não houver diagrama `timeline` nem `waterfall` publicado (a R19 aceita motivo), a regra não tem o que conferir.

```gherkin
  Regra: O que a linha do tempo e a cascata afirmam bate com a fonte delas

    Esquema do Cenário: [CT-31] cada marco da linha do tempo cita versão do CHANGELOG com a data dela e um termo da seção dela, e cada tempo medido traz data e fonte
      Dado os diagramas `timeline` e `waterfall` do mapa "<mundo>"
      E o `CHANGELOG.md`
      Quando a guarda do mapa confere cada marco e cada tempo
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                         | resultado                                                                                                                                                         | # partição          |
        | publicados | aceita: cada marco cita uma versão que tem cabeçalho no CHANGELOG, com a data desse cabeçalho, e o título visível do marco aparece na seção dessa versão; cada tempo traz a data e a fonte da medição (o comando ou o run do CI) | válida |
        | cópia com um marco da versão 0.99.0, que o CHANGELOG não tem                  | reprova, nomeando o marco                                                                                                                                         | versão inexistente  |
        | cópia com o marco da 0.56.0 datado de 2026-11-18 (o CHANGELOG diz 2026-11-19) | reprova, nomeando o marco                                                                                                                                         | data trocada        |
        | cópia com o marco "Passkeys" da 0.56.0, datado de 2026-11-19, que guarda o termo "login" | reprova, nomeando o marco | título que a seção não traz |
        | cópia com um marco do `[Unreleased]`                                          | reprova, nomeando o marco                                                                                                                                         | versão sem data     |
        | cópia com um tempo sem a fonte da medição                                     | reprova, nomeando o passo                                                                                                                                         | tempo sem fonte     |
        | cópia com um tempo sem a data da medição                                      | reprova, nomeando o passo                                                                                                                                         | tempo sem data      |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | marco de versão escrito de memória, que o CHANGELOG não tem | CT-31 | linha "0.99.0": reprova; o mutante aceita |
| M2 | marco com a data do commit, e não a do cabeçalho do CHANGELOG | CT-31 | linha "0.56.0 datado de 2026-11-18": reprova; o mutante aceita |
| M3 | o `[Unreleased]` vira marco da linha do tempo | CT-31 | linha `[Unreleased]`: reprova; o mutante aceita |
| M4 | tempo medido sem a fonte (o comando ou o run do CI) | CT-31 | linha "sem a fonte": reprova; o mutante aceita |
| M5 | tempo medido sem a data da medição | CT-31 | linha "sem a data": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-12) | o marco traz a versão e a data certas e atribui a ela a feature de outra versão | CT-31 | linha do marco "Passkeys": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-25) | o marco guarda um termo da seção diferente do título que mostra ao leitor | CT-31 | linha do marco "Passkeys" com o termo "login": o título não está na seção, e reprova; o mutante aceita |

*(alterado em 2026-11-20: ADV-12)*
- **O termo vale como oráculo, e a F-nn não.** O marco afirma features de uma versão, e o termo é o que liga as duas. A F-nn não serve: o CHANGELOG cita F-nn em só cinco versões (medido na base).
- **O termo do marco é o título visível dele** *(alterado em 2026-11-20: ADV2-25)*, e não um campo à escolha do marco. Ele aparece no texto da seção da versão, entre o cabeçalho dela e o próximo. Medido na seção da 0.56.0: "login" está nela, e "Passkeys", não; com o termo à escolha, o marco "Passkeys" passaria guardando "login".

---

## Regra R30 — Toda peça que o painel registra e que fica indisponível num modo, com a configuração de fábrica, guarda no modelo a condição que o discriminante mede — o modo, a chave e o valor de fábrica, ou os dois —, com a âncora do código que decide; e a peça disponível nos dois modos não guarda condição

> `RQ-27`, `RQ-04`, `RQ-05`, `RQ-11`, `P-03`, `P-15`, `P-16` · perfil **padrão** · técnica: **tabela de decisão** (disponível sem tenant × disponível com tenant × disponível com a chave ligada, 6 linhas) + controles em cópia *(alterado em 2026-11-23 — Adendo 2: destravada; estava estacionada na Q11, com o ID e os do CT-32 e do CT-33 reservados; o CT-33 foi para a R41)*

O discriminante, a chave, o arnês e a população medida estão em `### A peça condicional e o discriminante`, no Setup Global. A RQ-27 pede a marca "da condição — o modo, ou a chave e o valor de fábrica"; esta regra garante que a condição que o modelo guarda, e de onde sai toda marca (R42), é a que o código decide, nas duas direções: a peça indisponível tem condição, e a peça com condição está indisponível onde ela diz e disponível onde ela diz.

| # | Sem tenant | Com tenant | Com a chave ligada | Condição no modelo | Medido no HEAD `c642c23c` |
|---|---|---|---|---|---|
| 1 | disponível | disponível | — | nenhuma (P-16) | 69 das 86 peças por painel, entre elas o `AiRunResource`, que só pede permissão, e o `Dashboard` do `/admin`, que só esconde o item de menu |
| 2 | indisponível | disponível | — | o modo: só com tenant | 4: o `TenantResource`; o `UserResource` e o `ConviteResource` do `/app`; o `ConvitesRecebidos`. *(alterado em 2026-11-23 — Adendo 3)* E o `AcessosPorPainel`, fora do registro, que herda a condição das telas de organizações (RQ-31) |
| 3 | disponível | indisponível | — | o modo: só sem tenant *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)*: sem redação decidida, o gerador recusa essa condição (R40, CT-50); a peça que cair aqui fica vermelha, e não calada, até a sessão decidir a redação | nenhuma |
| 4 | indisponível | indisponível | disponível nos dois | a chave e o valor de fábrica | 9: `KIT_AGENTES_IA` (3), `KIT_ONBOARDING` (4), `KIT_HUB` (2). *(alterado em 2026-11-23 — Adendo 3)* E o `CommandRecordResource`, pela configuração de pacote `command-center.sources`, que conta como chave (RQ-30): 10. *(alterado em 2026-11-23 — Adendo 4)* E os dois componentes flutuantes (RQ-33): o chat do assistente, com duas chaves juntas, e o checklist do tutorial: 12 |
| 5 | indisponível | indisponível | disponível só com tenant | o modo e a chave | 1: o `ProjetoResource` do `/app` (`KIT_DEMO`) |
| 6 | indisponível | indisponível | indisponível com toda chave e toda configuração que a condição nomeia | nenhuma serve: reprova, nomeando a peça — falha fechado *(alterado em 2026-11-23 — Adendo 3: era o recorte aberto da Q27, com o `CommandRecordResource`, que foi para a linha 4)* | nenhuma |

A guarda, nas duas suítes, para cada peça que cada painel registra, no modo `m` da suíte, com a condição do modelo dizendo os modos `M` em que a peça fica disponível (os dois, se a condição não fala de modo) e, se houver, a chave `k` com o valor de fábrica `v`:
- com a configuração de fábrica, a peça está disponível se, e só se, `m` está em `M` e a condição não tem chave;
- com a chave `k` ligada — o valor oposto a `v` —, está disponível se, e só se, `m` está em `M`;
- `v` é o default de `k` no `config/kit.php`, é o valor lido no caso, e é o desligado: a chave ligada de fábrica não marca (P-16);
- a âncora da condição resolve como uma prova (R9): o arquivo versionado no kit, o trecho nele, fora de comentário.
- *(alterado em 2026-11-23 — Adendo 3)* A população vai além do registro, e a chave, além de `kit.*` — as três frentes são da R43, e a guarda é a mesma:
  - a peça do modelo que painel nenhum registra e tem `canAccess()` ou `canView()` estático entra, avaliada em cada painel do registro (RQ-31);
  - a chave pode ser uma configuração de pacote (RQ-30): `v` é o valor de fábrica dela no arquivo de config do kit, e "ligada" é o valor que a condição do modelo dá como ligado, que a guarda põe e avalia — no `CommandRecordResource`, o `command-center.sources` com a `DatabaseSource`. A âncora fica no código do kit, e não em `vendor/` (R9): o `'sources'` do `config/command-center.php`;
  - nenhuma peça de tipo plugin ou middleware guarda condição (RQ-32).
- *(alterado em 2026-11-23 — Adendo 4)* A chave `k` pode ser mais de uma: com todas as chaves da condição ligadas **juntas**, a peça está disponível se, e só se, `m` está em `M`. O chat do assistente pede `KIT_AGENTES_IA` e o painel em `KIT_AGENTES_IA_PAINEIS`, e não abre com nenhuma das duas sozinha. O componente flutuante, sem `canAccess()` nem `canView()`, é avaliado pelo predicado do mapa fechado do Setup Global (RQ-33).

Com as duas suítes, as seis linhas da tabela têm matador: a Kit confere a coluna "sem tenant" e a Tenancy, a "com tenant". A linha 6 fica fora até a Q27, pelo nome. *(alterado em 2026-11-23 — Adendo 3: não fica mais; a linha 6 não tem peça no HEAD, e a peça que caísse nela reprova)*

```gherkin
  Regra: O modelo guarda, de cada peça condicional, a condição que o código decide, e de nenhuma outra

    Esquema do Cenário: [CT-32] sem tenant, toda peça registrada que fica indisponível guarda no modelo a condição que a explica, e a peça disponível não guarda condição
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso, e toda chave booleana de `kit.*` lida com o valor de fábrica
      E o super_admin do kit autenticado, e o modelo "<mundo>"
      Quando a guarda do mapa avalia o `canAccess()` ou o `canView()` de cada recurso, página e widget que cada painel registra, e de cada peça do modelo fora do registro que tem um dos dois ou que é componente flutuante, com a configuração de fábrica e, na peça cuja condição traz chaves ou uma configuração de pacote, de novo com elas ligadas juntas
      Então o resultado é "<resultado>"
      E, no publicado, a guarda achou indisponíveis o `TenantResource`, o `AgenteIaResource`, o `CommandRecordResource`, o `AcessosPorPainel` e o chat do assistente, e disponíveis o `AiRunResource` e o `Dashboard` do `/admin`

      Exemplos:
        | mundo                                                                                                     | resultado                                                                                                                                       | # partição                                  |
        | publicado                                                                                                 | aceita: cada peça indisponível sem tenant guarda a condição que a explica — com tenant, com a chave dela ligada, ou com os dois —, com a âncora que resolve, e nenhuma peça disponível guarda condição; inclusive o `CommandRecordResource`, pela configuração `command-center.sources`, e o `AcessosPorPainel`, fora do registro; e nenhum plugin nem middleware guarda condição *(alterado em 2026-11-23 — Adendo 3: era "fora o `CommandRecordResource`, que fica com a Q27")*; e o chat do assistente, com as duas chaves, e o checklist do tutorial, com a dele *(alterado em 2026-11-23 — Adendo 4)* | válida (hoje reprova: nota abaixo)          |
        | cópia sem a condição do `TenantResource`                                                                  | reprova, nomeando o `TenantResource`                                                                                                            | indisponível sem condição                   |
        | cópia que dá ao `AgenteIaResource` a chave `KIT_REGISTRO`, que não decide o acesso dele                    | reprova, nomeando o `AgenteIaResource` e a chave: com `KIT_REGISTRO` ligada, ele continua indisponível                                          | chave que não decide                        |
        | cópia que dá ao `AgenteIaResource` a chave `KIT_AGENTES_IA` ligada de fábrica                             | reprova, nomeando a peça: o `config/kit.php` a dá desligada de fábrica, e a chave ligada de fábrica não marca                                    | valor de fábrica trocado                    |
        | cópia que dá ao `TenantResource` a condição "só sem tenant"                                               | reprova, nomeando a peça: sem tenant ela está indisponível                                                                                      | modo invertido                              |
        | cópia que dá ao `ProjetoResource` do `/app` só a chave `KIT_DEMO`, sem o modo                             | reprova, nomeando a peça: com `KIT_DEMO` ligada, ela continua indisponível sem tenant                                                           | modo e chave, só a chave                    |
        | cópia que dá ao `Dashboard` do `/admin` a chave `KIT_DASHBOARD_DINAMICO`, que só esconde o item de menu dele | reprova, nomeando o `Dashboard`: ele está disponível                                                                                         | condição em peça disponível: menu           |
        | cópia que dá ao `AiRunResource` a chave `KIT_AGENTES_IA`                                                  | reprova, nomeando o `AiRunResource`: ele está disponível, e a permissão `ver-ai-tasks` não é condição                                          | condição em peça disponível: permissão      |
        | cópia com a âncora da condição do `TenantResource` num trecho que não está no arquivo                     | reprova, nomeando a peça e a âncora                                                                                                             | âncora que não resolve                      |
        | cópia sem a condição do `CommandRecordResource` *(alterado em 2026-11-23 — Adendo 3)*                     | reprova, nomeando o `CommandRecordResource`: sem tenant e de fábrica, ele não abre                                                              | configuração de pacote sem condição (RQ-30) |
        | cópia que dá ao `CommandRecordResource` a configuração `command-center.enabled` *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando a peça: ela vem ligada de fábrica, e ligada não abre a peça                                                | configuração vizinha (RQ-30)                |
        | cópia sem a condição do `AcessosPorPainel` *(alterado em 2026-11-23 — Adendo 3)*                          | reprova, nomeando o `AcessosPorPainel`: sem tenant, o `canView()` dele dá falso                                                                 | peça que herda sem condição (RQ-31)         |
        | cópia que dá ao `FilamentCardsPlugin` a chave `KIT_HUB` *(alterado em 2026-11-23 — Adendo 3)*             | reprova, nomeando o plugin: plugin e middleware não guardam condição                                                                            | plugin com condição (RQ-32)                 |
        | cópia sem a condição do `ChecklistDoOnboarding` *(alterado em 2026-11-23 — Adendo 4)*                                                    | reprova, nomeando o checklist: de fábrica, o `TutorialGuiado::visivel()` dá falso                                                               | componente flutuante sem condição (RQ-33)   |
        | cópia que dá ao `AssistenteChatWidget` só a chave `KIT_AGENTES_IA` *(alterado em 2026-11-23 — Adendo 4)*                                 | reprova, nomeando o chat: com ela ligada sozinha, ele continua fechado — falta o painel em `KIT_AGENTES_IA_PAINEIS` | chat com uma das duas chaves (RQ-33)  |

    Esquema do Cenário: [CT-56] com tenant, toda peça registrada que fica indisponível guarda no modelo a condição que a explica, e a peça que a condição dá ao modo com tenant está disponível nele
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro, e toda outra chave booleana de `kit.*` lida com o valor de fábrica
      E o super_admin do kit autenticado, uma organização aberta no `/app`, e o modelo "<mundo>"
      Quando a guarda do mapa avalia o `canAccess()` ou o `canView()` de cada recurso, página e widget que cada painel registra, e de cada peça do modelo fora do registro que tem um dos dois ou que é componente flutuante, com a configuração de fábrica e, na peça cuja condição traz chaves ou uma configuração de pacote, de novo com elas ligadas juntas
      Então o resultado é "<resultado>"
      E, no publicado, a guarda achou disponíveis o `TenantResource`, o `UserResource` do `/app` e o `AcessosPorPainel`, e o `ProjetoResource` do `/app` indisponível até a `KIT_DEMO` ligar

      Exemplos:
        | mundo                                                                                  | resultado                                                                                                                         | # partição                                    |
        | publicado                                                                              | aceita: cada peça indisponível com tenant guarda a condição que a explica, e cada peça cuja condição dá o modo com tenant está disponível nele, com a chave dela ligada quando houver; inclusive o `CommandRecordResource`, que abre com a `DatabaseSource` em `command-center.sources`, e o `AcessosPorPainel`, fora do registro, que abre com tenant *(alterado em 2026-11-23 — Adendo 3: era "fora o `CommandRecordResource`, que fica com a Q27")*; e o chat e o checklist, que abrem com as chaves deles ligadas *(alterado em 2026-11-23 — Adendo 4)* | válida (hoje reprova: nota abaixo) |
        | cópia que dá ao `ProjetoResource` do `/app` só o modo "com tenant", sem a chave        | reprova, nomeando a peça: com tenant e a configuração de fábrica, ela está indisponível                                           | modo e chave, só o modo                       |
        | cópia que dá ao `AccountWidget` do `/admin` a condição "só sem tenant"                 | reprova, nomeando o widget: com tenant, ele está disponível                                                                       | "só sem tenant" numa peça dos dois modos      |
        | cópia sem a condição do `HubDoNegocio`                                                 | reprova, nomeando o `HubDoNegocio`                                                                                                | indisponível sem condição, com tenant         |
```

- **Medido no HEAD `c642c23c`.** O modelo não guarda condição em peça nenhuma. O CT-32 "publicado" reprova pelas 14 peças indisponíveis sem tenant fora a da Q27, e o CT-56, pelas 10 indisponíveis com tenant fora a da Q27 (as oito do `/admin` pela chave, o `ProjetoResource` e o `HubDoNegocio`). As âncoras das linhas "publicado" passam hoje: o `TenantResource` e o `AgenteIaResource` indisponíveis sem tenant; o `AiRunResource` e o `Dashboard` do `/admin` disponíveis; com tenant, o `TenantResource` e o `UserResource` do `/app` disponíveis, e o `ProjetoResource` do `/app` indisponível até a `KIT_DEMO` ligar. As linhas de cópia partem do modelo publicado; as que tiram uma condição só discriminam depois que o modelo a tiver.
- **A peça da Q27 sai pelo nome**, como o CT-37 tirava o `TenantResource` antes do Adendo 2. Com a resposta, ela volta como linha 4 ou como linha 1 da tabela, conforme a sessão decidir. *(alterado em 2026-11-23 — Adendo 3: voltou como linha 4, pela RQ-30, e não sai mais pelo nome)*
- *(alterado em 2026-11-23 — Adendo 3)* **Medido no HEAD `c642c23c`, com a população do Adendo 3.** O CT-32 "publicado" reprova pelas 16 peças indisponíveis sem tenant — as 14 de antes, o `CommandRecordResource` e o `AcessosPorPainel` —, e o CT-56, pelas 11 indisponíveis com tenant: as 10 de antes e o `CommandRecordResource`. O `AcessosPorPainel` abre com tenant, e por isso o CT-56 não o reprova hoje. As âncoras passam: sem tenant, o `CommandRecordResource` e o `AcessosPorPainel` dão falso; com tenant, o `AcessosPorPainel` dá verdadeiro; com a `DatabaseSource` em `command-center.sources`, o `CommandRecordResource` abre nos dois modos. As quatro linhas de cópia do Adendo 3 partem do modelo publicado: a sem a condição do `CommandRecordResource` e a sem a do `AcessosPorPainel` só discriminam depois que o modelo as tiver; a do `command-center.enabled` e a do `FilamentCardsPlugin` discriminam já.
- *(alterado em 2026-11-23 — Adendo 4)* **Medido no HEAD `c642c23c`, com os componentes flutuantes.** O CT-32 "publicado" reprova pelas 18 peças indisponíveis sem tenant (as 16 de antes, o chat e o checklist), e o CT-56, pelas 13 com tenant (as 11 de antes, o chat e o checklist). A âncora nova passa: o chat dá fechado de fábrica nos três painéis. A linha sem a condição do checklist só discrimina depois que o modelo a tiver; a do chat com uma chave só discrimina já, porque com `KIT_AGENTES_IA` sozinha ele continua fechado (medido).
- **O `AccountWidget`** é registrado no `/admin` em `app/Providers/Filament/AdminPanelProvider.php:AccountWidget:158`, e está disponível nos dois modos.
- **A cópia nunca muda a disponibilidade**, que a guarda avalia ao vivo: ela muda só a condição do modelo.
- **O que fica fora:** a peça que painel nenhum registra (Q28); o plugin e o middleware, que não têm `canAccess()` nem `canView()` (Q29); e a peça que só abriria com duas chaves ligadas juntas, que não existe no HEAD (medido: toda peça da linha 4 abre com uma chave só). *(alterado em 2026-11-23 — Adendo 3: a peça que painel nenhum registra e tem método próprio entra (RQ-31); o plugin e o middleware ficam fora por requisito (RQ-32), e a guarda reprova a condição neles. Fica fora o componente flutuante ligado a chave (Q30) — e é nele que existe, medido, a peça de duas chaves: o chat do assistente)* Se existir, o CT-32 a reprova com a chave da condição ligada sozinha, e a sessão decide. *(alterado em 2026-11-23 — Adendo 4)* O componente flutuante entrou, pela RQ-33, e a peça de duas chaves existe: o chat. A guarda liga juntas as chaves da condição, e por isso a frase acima, "reprova com a chave da condição ligada sozinha", deixou de valer — com as duas juntas, o chat abre, e a guarda aceita a condição certa.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a peça condicional fica sem condição no modelo — o caso de hoje | CT-32, CT-56 | o publicado de cada um, e as linhas "sem a condição do `TenantResource`" (CT-32) e "sem a condição do `HubDoNegocio`" (CT-56): reprova; o mutante aceita |
| M2 | a condição traz uma chave vizinha, que não decide o acesso da peça | CT-32 | linha `KIT_REGISTRO`: com ela ligada, o `AgenteIaResource` continua indisponível, e reprova; o mutante aceita |
| M3 | a condição traz o valor de fábrica trocado, ou marca uma chave ligada de fábrica | CT-32 | linha "`KIT_AGENTES_IA` ligada de fábrica": reprova; o mutante aceita |
| M4 | o modelo dá condição à peça disponível: a que só esconde o item de menu, ou a que só pede permissão (P-16) | CT-32 | linhas do `Dashboard` do `/admin` e do `AiRunResource`: os dois estão disponíveis, e reprova; o mutante aceita |
| M5 | a condição composta perde uma parte: o `ProjetoResource` do `/app` só com a chave, ou só com o modo | CT-32, CT-56 | CT-32, linha "só a chave": com `KIT_DEMO` ligada, continua indisponível sem tenant; CT-56, linha "só o modo": com tenant e de fábrica, indisponível; reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-17) | a detecção roda num modo só, sem tenant, e a condição que diz o modo com tenant nunca é posta à prova nele | CT-56 | linhas "`AccountWidget` só sem tenant" e "`ProjetoResource` só o modo": reprova; o mutante, que só roda sem tenant, aceita as duas |
| M7 — revisão adversarial (ADV-17) | a âncora da condição aponta um trecho que não está no arquivo | CT-32 | linha da âncora: reprova; o mutante aceita |

A linha "modo invertido" do CT-32 mata também o R37-M3, no modelo.

---

## Regra R31 — A interface do visualizador está no idioma da página, e só a casca do comparativo fica em inglês; o catálogo pt que o kit entrega não copia o texto en, fora a lista fechada com o motivo

> `P-11`, `P-02`, `RQ-03` · perfil **padrão** · técnica: **rastreio exaustivo** no site construído + controles em cópia · cenários: **CT-B04 e CT-B07, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: ADV-06 e ADV-30)*

*(alterado em 2026-11-20: fatos de plataforma medidos pela sessão, ADV-06 e ADV-30)*
- **O catálogo pt que o kit entrega.** O archify, no commit fixado, tem o `examples/locales/pt.json`, com 461 chaves, mas ele não está no manifesto dos catálogos embutidos. O kit entrega a interface pt pelas traduções do próprio diagrama: essas 461, mais 25 chaves do kit e as sobrescritas dos tipos de peça. O en é o catálogo embutido, `locales/en.json`, com 486 chaves.
- *(alterado em 2026-11-20: medido no commit `4adaf9a`)* **O catálogo chega em duas partes.** O archify embute em cada HTML só as 390 chaves de interface (`<script id="archify-i18n-data">`, as mesmas 390 no pt e no en), e aplica as outras 96 no render — o título da página, a descrição, o contexto do nó, as legendas e os eixos de `timeline` e `waterfall` —, sem embuti-las. Por isso o CT-B07 lê as 390 no HTML pt e as 96 na fonte do kit (o `examples/locales/pt.json` do archify fixado e o `site/mapa/interface.json`, que o build junta), e compara as 486 com o `locales/en.json`. Medido: nenhuma das 486 sem texto pt; iguais ao en, 9 a 11 por HTML entre as 390 (os construtores de arquitetura sobrescrevem as dos tipos) e 14 na fonte, todas entre as 14 do archify. O mesmo vale para o CT-B04: o texto pt de cada chave é o embutido ou, nas 96, o da fonte.
- **Por que o CT-B07.** Como todo texto pt sai do que o kit entrega, uma chave com o texto en copiado não mostra o aviso de idioma reserva. Por isso o CT-B07 compara os dois catálogos, chave a chave.
- **Por que a busca e o passaporte abertos.** A busca e o passaporte montam os textos deles na interação. Por isso o CT-B04 lê a interface também com os dois abertos.
- *(alterado em 2026-11-20: ADV2-26 e ADV2-27)* **A lista fechada do CT-B07 tem teto:** só as 14 chaves cujo pt, no `examples/locales/pt.json` do archify fixado, é igual ao en, e as chaves só de marcadores. Medido: 14 das 461 chaves.
- **Os critérios da legenda no CT-B04.** Valem para o mapa de arquitetura, o único tipo cuja legenda nomeia os tipos de peça: medido no commit `4adaf9a`, a árvore, a `timeline`, o `waterfall`, o `erd` e o `sequence` não têm legenda de tipo, e a do `workflow` e a do `dataflow` falam de passos e de dados. Em pt, a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo do grupo dele: os da RQ-24 — painel, recurso, página, widget, plugin, provider, middleware, serviço e model — e, fora dela, policy, comando, Livewire e settings. Em en, nenhum texto de interface igual ao pt de uma chave cujo en é outro; nenhum aviso de idioma reserva; e, no mapa de arquitetura, a legenda com o nome en de cada tipo de peça desenhado — panel, resource, page, widget, plugin, provider, middleware, service, model, policy, setting, command, Livewire —, como pedaço do rótulo. Medido no esqueleto: a legenda en do `admin-acesso` traz panel, page, resource, widget, Livewire, provider, service, model, settings, policy, middleware e plugin. No commit `4adaf9a`, no `app-acesso`: em pt, "Tela (painel, página, recurso, widget, Livewire)", "Código do kit (provider, serviço, comando)", "Dados (model, settings)", "Pacote ou plugin de terceiros" e "Acesso (middleware, policy)"; em en, os mesmos grupos com os nomes en; e nenhum dos tipos desenhados (página, serviço, plugin, middleware, Livewire, model) falta. "Plugin de pacote" e "serviço próprio", por extenso, reprovariam dado correto: a legenda diz "Pacote ou plugin de terceiros" e "serviço".

A tabela abaixo é a mesma do CT-B04 e do CT-B07.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | uma chave da interface do archify sem tradução pt cai no inglês, com o aviso de idioma reserva | CT-B04 | "nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro, nenhum aviso de idioma reserva aparece": reprova; o mutante aceita |
| M2 | o documento pt se declara `lang="en"` (o idioma não chega ao HTML) | CT-B04 | "o `lang` do documento começa por `pt`": reprova; o mutante aceita |
| M3 | a legenda dos tipos de peça fica com os rótulos do archify em inglês | CT-B04 | "a legenda traz o nome pt de cada tipo de peça desenhado, como pedaço do rótulo": reprova; o mutante aceita |
| M4 | a exceção do comparativo vale para todo diagrama, e a interface inteira fica em inglês sem reprovar | CT-B04 | "na versão pt de todo diagrama fora o comparativo, nenhum texto de interface é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M5 | a versão en recebe a tradução pt (catálogo de tradução trocado de idioma) *(alterado em 2026-11-20: ADV2-27, a asserção antiga não tinha critério)* | CT-B04 | versão en, "nenhum texto de interface igual ao pt de uma chave cujo en é outro, e a legenda com os nomes en dos tipos": reprova; o mutante aceita |
| M6 — revisão adversarial (ADV-30) | a busca e o passaporte montam os textos na interação, e uma chave deles cai no inglês | CT-B04 | "com a busca aberta e o passaporte de uma peça aberto, nenhum texto visível é igual ao en de uma chave cujo texto pt é outro": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV-06) | para calar o aviso de idioma reserva, o catálogo pt do kit recebe o texto en nas chaves não traduzidas | CT-B07 | linha "a chave da busca traz o texto en": reprova; o mutante aceita |
| M8 — revisão adversarial (ADV-06) | a lista fechada de textos iguais aceita a chave sem o motivo | CT-B07 | linha "na lista, sem o motivo": reprova; o mutante aceita |
| M9 — revisão adversarial (ADV2-26) | quem copia o texto en para calar o aviso acrescenta a chave à lista fechada, com um motivo que não é de enfeite | CT-B07 | linha `viewer.finder.placeholder` na lista, com motivo: reprova; o mutante aceita |
| M10 — step 9 (A13) | o conferidor do mapa tem a própria definição de motivo de enfeite, e aceita como motivo "N/A", "not applicable", "Does not apply" ou um número, que a guarda do kit trata como enfeite | CT-B07 | linhas da lista com `viewer.guide.facts` e o motivo "N/A", "not applicable", "Does not apply" e 0: reprova; o mutante aceita |
| M11 — step 9 (A16) | o catálogo pt perde as chaves que o kit acrescenta fora do catálogo do archify (`viewer.kind.class`, `viewer.kind.abstract`), e o passaporte pt mostra o tipo do nó cru, em inglês | CT-B04 | "na versão pt de todo diagrama de classes, o tipo que o passaporte mostra é o nome pt que o desenho dá ao nó": o passaporte diz `class` e o desenho, "Classe", e reprova; o mutante aceita |

*(alterado em 2026-11-21 — step 9, achados A13 e A16)*
- **A13.** A tabela do motivo de enfeite é a do Setup Global, a mesma do CT-35. Medido no commit `168d1827`: o conferidor aceita como motivo 7 das 17 entradas da tabela que a guarda do kit trata como enfeite; as quatro linhas novas do CT-B07 ficam vermelhas até ele seguir a tabela.
- **A16.** O archify do commit fixado não tem `viewer.kind.class` nem `viewer.kind.abstract` no catálogo, e o kit as acrescenta ao catálogo pt embutido depois do render (`site/mapa/gerar.mjs:completarCatalogoEmbutido:647`). O critério do CT-B04 — nenhum texto igual ao en de uma chave cujo pt é outro — só pegava `Class` por coincidência (é o en de `node.context.class.class`), e `Abstract` não é texto de chave nenhuma do en. Medido no commit `168d1827`, no `classes-heranca-e-contratos` gerado numa pasta de rascunho: o desenho dá ao nó o contexto "Interface", "Classe" e "Classe abstrata" em pt, e o passaporte mostra os mesmos três; em en, o desenho dá "Class" e "Abstract class", e o passaporte, `class` e `abstract`, crus, que são inglês e ficam fora do oráculo. O tipo `interface` não discrimina — é a mesma palavra nos dois idiomas —, e por isso a amostra é um nó de cada tipo, e quem mata o mutante são o `class` e o `abstract`.

---

## Regra R32 — A conferência de acessibilidade audita a amostra inteira e tolera só os dois defeitos do archify, pela regra e pelo elemento

> `P-11`, `RQ-03`, `RQ-07` · perfil **padrão**, técnica escalada (6 mutantes, o teto do completo) · técnica: **amostra estratificada** (tipo × idioma × tema) + **controle sintético** · cenários: **CT-B05 e CT-B06, no `05`** (costura browser, G4) *(regra nova em 2026-11-20: Q12, fechada pela P-11)*

Os dois defeitos tolerados são estes:
- `nested-interactive` cujo alvo é o `<svg>` raiz do diagrama;
- `color-contrast` cujo alvo é a contagem de grupo do índice de nós, `.node-outline-heading > small`.

Qualquer outra violação serious ou critical, qualquer `console.error` e qualquer exceção não capturada reprovam *(alterado em 2026-11-20: ADV-29; o mutante da exceção está na R24, como M7, e o teto de seis desta regra fica)*. A tabela abaixo é a mesma do `05`.

*(alterado em 2026-11-20: ADV2-19)* A tolerância decide por alvo, e não pela violação inteira: a violação com alvos mistos reprova pelo alvo que não é tolerado. Medido: o `site/verifica-mapa.mjs` já filtra os alvos da violação (`v.nodes.filter`).

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a tolerância casa pelo texto do seletor do axe: qualquer alvo cujo seletor contém `svg` ou `small` passa | CT-B06 | linhas "`nested-interactive` num `<svg>` que não é o raiz" e "`color-contrast` num `<small>` fora de `.node-outline-heading`": reprova; o mutante tolera |
| M2 | a tolerância casa pela regra sem o elemento: todo `color-contrast` passa | CT-B06 | linha "`color-contrast` num parágrafo do corpo": reprova; o mutante tolera |
| M3 | uma terceira violação entra na tolerância | CT-B06 | linha "`button-name` num botão sem nome": reprova; o mutante tolera |
| M4 | amostra vazia: o conferidor não acha diagrama e não audita nada | CT-B05 | "a amostra tem exatamente uma entrada por tipo publicado, idioma e tema, e nenhuma é vazia": reprova; o mutante aceita |
| M5 | um tipo publicado fica fora da amostra | CT-B05 | idem, pelo tipo que falta: reprova; o mutante aceita |
| M6 | o comparativo não é auditado | CT-B05 | "mais o comparativo no tema escuro": reprova; o mutante aceita |
| M7 — revisão adversarial (ADV2-19) | a função de decisão tolera a violação inteira quando algum alvo, ou o primeiro, é tolerado | CT-B06 | linha "`color-contrast` de dois alvos: `.node-outline-heading > small` e um parágrafo do corpo": reprova pelo parágrafo; o mutante tolera |

---

## Regra R33 — A página do mapa declara, nos dois idiomas, as duas exceções da P-11, a do comparativo na P-04 e o limite da varredura da P-12

> `P-11`, `P-12`, `P-04`, `P-02` · perfil **padrão** · técnica: **EP** (idioma) *(regra nova em 2026-11-20: Q12, fechada pela P-11; alterada na quarta rodada: P-12)*

```gherkin
  Regra: O leitor sabe o que o mapa não cumpre, e por quê

    Esquema do Cenário: [CT-34] a página do mapa de cada idioma declara a casca do comparativo em inglês, os dois defeitos tolerados do archify e o limite da varredura
      Dado a página do mapa em "<idioma>"
      Quando a guarda do mapa lê a declaração de exceções da página
      Então a página diz que a casca do comparativo fica em inglês
      E a página nomeia os dois defeitos tolerados pela regra e pelo elemento: `nested-interactive` no `<svg>` raiz do diagrama, e `color-contrast` na contagem de grupo do índice de nós (`.node-outline-heading > small`)
      E a página diz qual é o piso da varredura — as peças que os painéis registram e as classes de `app/` fora de `app/Filament` — e que, além dele, a completude não tem garantia
      E a página diz que o comparativo não leva cards nem passaporte, e que a base e a cabeça dele são diagramas do mapa com a prova de cada ligação (P-04)

      Exemplos:
        | idioma |
        | pt     |
        | en     |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a página não declara as exceções | CT-34 | "a página diz…" e "a página nomeia…": reprova; o mutante aceita |
| M2 | a declaração está só na página pt | CT-34 | linha "en": reprova; o mutante aceita |
| M3 | a declaração é genérica ("o archify tem defeitos de acessibilidade"), sem a regra e o elemento | CT-34 | "nomeia os dois defeitos pela regra e pelo elemento": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-33) | a página apresenta o mapa como completo e não diz o piso da varredura nem o limite dela | CT-34 | "diz qual é o piso da varredura e que, além dele, a completude não tem garantia": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV2-20) | a página não declara a exceção do comparativo, e o leitor procura nele a prova que ele não tem | CT-34 | "diz que o comparativo não leva cards nem passaporte": reprova; o mutante aceita |

---

## Regra R34 — Os diagramas de arquitetura e a árvore de registro de um painel que valem num modo desenham, juntos, todo o registro do painel nesse modo que fica disponível nesse modo — com a chave ligada (P-15) —, e nada além

> `P-09`, `RQ-11`, `RQ-10`, `P-05`, `RQ-28`, `RQ-29`, `P-15` · perfil **padrão** · técnica: **tabela de decisão** (o registro muda entre os modos × há arquitetura por modo; 3 painéis × 2 modos) + controles em cópia *(regra nova em 2026-11-20: ADV-05)* *(alterado em 2026-11-23 — Adendo 2: o enunciado era "todo o registro do painel nesse modo, e nada que ele não registra nesse modo"; a origem ganhou a RQ-28, a RQ-29 e a P-15)*

O `/app` muda de registro entre os modos: `IdentifyTenant` e `DefinirTenantDePermissoes` só existem com tenant (CT-11 e CT-12). O `/admin` e o `/infra`, não (Setup Global).

A regra não manda o `/app` ter dois diagramas. Ela pede, em cada modo, que os diagramas `architecture` e a árvore de registro do painel que valem nesse modo — os do modo ou os compartilhados — desenhem juntos o registro do painel nesse modo, nem mais nem menos. O compartilhado só passa nos dois modos se o registro for o mesmo, e por isso o `/app` precisa de um por modo.

*(alterado em 2026-11-20: fato da implementação, dado pelo coordenador, e medição no commit `4cc9517`)* O registro do painel sai na árvore de registro, que o construtor gera do modelo: o `/app` tem uma árvore por modo (`app-registro-sem` e `app-registro-com`), porque com tenant registra o `IdentifyTenant` e o `DefinirTenantDePermissoes`; o `/admin` e o `/infra` têm uma árvore só cada um, compartilhada pelos dois modos, porque o registro deles não muda entre os modos *(alterado em 2026-11-23 — Adendo 2: pela RQ-28 e pela RQ-29, o que o `/admin` desenha sem tenant não pode ter o `TenantResource`, e o que desenha com tenant tem de tê-lo; a forma recomendada é uma árvore por modo, como no `/app` — a colisão a, em `### Colisões do Adendo 2`; o `/infra` fica com a compartilhada)*. Por isso a árvore de registro conta aqui, ao lado da arquitetura, e só contar a arquitetura reprovaria o `/admin` e o `/infra`, que não têm mapa de arquitetura. Medido no modelo do commit `4cc9517`: o registro do `/admin` tem 57 peças nos dois modos, e o do `/infra`, 84, iguais; o do `/app` tem 46 sem tenant e 48 com, e a diferença é o `IdentifyTenant` e o `DefinirTenantDePermissoes`. As duas árvores do `/app` estão nos arquivos desse commit; a do `/admin` e a do `/infra` ainda não, e o CT-37 e o CT-38 as cobram: sem elas, nada desenha o registro do `/admin` (falta 57) nem o do `/infra` (falta 84). No `/app`, os mapas e a árvore que valem em cada modo desenham juntos o registro dele, nem mais nem menos: 46 de 46 sem tenant e 48 de 48 com; os dois mapas compartilhados (`app-acesso` e `app-organizacoes`) não desenham peça nem ligação de um modo só, e nenhum dos 4 mapas desenha peça que só outro painel registra.

A ligação de registro que só aparece num diagrama de outro tipo — fora a árvore de registro — não conta aqui, embora conte no CT-14.

O modo com tenant só existe na suíte `Tenancy`. Sem tenant, o `/app` não registra os dois middlewares, e a cópia "só o compartilhado" passaria no CT-37. Quem a mata é o CT-38.

*(alterado em 2026-11-20: ADV2-29)* O `TenantResource` está no registro do `/admin` nos dois modos e só abre com tenant. Afirmar que o diagrama sem tenant o desenha responderia à Q11 ("em que diagrama de modo ela entra"), e ao contrário da recomendação dela. Até a resposta, a linha admin do CT-37 tira do "nem mais nem menos" as peças de disponibilidade condicional, e a Q11 segura esse recorte.

*(alterado em 2026-11-23 — Adendo 2, colisão a)* A Q11 respondeu na direção da recomendação. O conjunto que os diagramas de cada modo desenham é o registro do painel nesse modo **menos** a peça que fica indisponível nele mesmo com a chave ligada:
- **De onde sai o que se subtrai.** Do discriminante vivo, na suíte do modo: a peça registrada cujo `canAccess()` ou `canView()` dá falso com a configuração de fábrica e com cada chave booleana de `kit.*` desligada, uma de cada vez, ligada (nunca `kit.tenancy.enabled`, que é o modo *(alterado em 2026-11-23 — Adendo 4)*). Não sai da condição do modelo: o modelo que marca peça demais esvaziaria a árvore e passaria. Nem de uma lista escrita à mão, como a do CT-37 até aqui, que só tinha o `TenantResource`. *(alterado em 2026-11-23 — Adendo 3: liga também a configuração de pacote que a condição do modelo nomeia, no valor que ela dá como ligado (RQ-30). O modelo só propõe a configuração; a peça que não abre com ela continua subtraída)*
- **O que fica.** A peça condicional só pela chave fica nos dois modos, porque com a chave ligada abre neles (P-15); a da Q27 fica nos dois, porque as duas leituras a desenham assim, e por isso a guarda a tira da subtração pelo nome. *(alterado em 2026-11-23 — Adendo 3: sai a exclusão pelo nome. O `CommandRecordResource` fica nos dois modos porque abre neles com a configuração ligada — RQ-30)*
- **Medido no HEAD `c642c23c`.** Sem tenant, a subtração tira o `TenantResource` no `/admin`, e o `UserResource`, o `ConviteResource`, o `ConvitesRecebidos` e o `ProjetoResource` do `/app`; com tenant, nada. O `/admin` tem uma árvore só, compartilhada, que desenha o `TenantResource` (o `admin-registro`), e o mapa `admin-multi-tenancy-opt-in`, também compartilhado, o desenha: a linha admin do CT-37 reprova hoje. A `app-registro-sem` desenha as quatro do `/app`, e os mapas compartilhados `app-acesso-e-autenticacao`, `app-convites` e `app-produtividade-e-ui` também: a linha app reprova hoje. A linha infra passa. O CT-38 passa nas três linhas. *(alterado em 2026-11-23 — Adendo 3: sem a exclusão pelo nome, e enquanto o modelo não der ao `CommandRecordResource` a condição com a configuração, a subtração viva o tira dos dois modos — nada o abre —, e a árvore do `/infra` o desenha: a linha infra do CT-37 e a do CT-38 reprovam hoje por ele, e passam quando o modelo tiver a condição. A linha admin e a app do CT-38 passam)*

```gherkin
  Regra: O que muda de registro entre os modos ganha um diagrama de arquitetura por modo

    Esquema do Cenário: [CT-37] sem tenant, os diagramas de arquitetura e a árvore de registro de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso, toda chave booleana de `kit.*` lida com o valor de fábrica, e o super_admin do kit autenticado
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` e a árvore de registro do painel que valem no modo sem tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                    | resultado                                                                                         |
        | admin  | publicados                                                                               | aceita: o conjunto é o registro do painel sem tenant menos a peça que fica indisponível sem tenant mesmo com a chave ligada, nem mais nem menos — o `TenantResource` está fora, e o `AgenteIaResource`, condicional só por `KIT_AGENTES_IA`, dentro *(alterado em 2026-11-23 — Adendo 2: era "fora a peça de disponibilidade condicional (o `TenantResource`), que fica com a Q11")* |
        | app    | publicados                                                                               | aceita (idem), sem `IdentifyTenant` nem `DefinirTenantDePermissoes`, que o painel não registra sem tenant, e sem o `UserResource`, o `ConviteResource` e o `ProjetoResource` do `/app` e o `ConvitesRecebidos`, que só abrem com tenant; o `HubDoNegocio`, condicional só por `KIT_HUB`, dentro *(alterado em 2026-11-23 — Adendo 2)* |
        | infra  | publicados                                                                               | aceita (idem), com a página `HealthCheckResults`, que o plugin registra, e com o `CommandRecordResource`, que as duas leituras da Q27 desenham nos dois modos *(alterado em 2026-11-23 — Adendo 2)* *(alterado em 2026-11-23 — Adendo 3: e que abre nos dois modos com a `DatabaseSource` em `command-center.sources`, RQ-30)* |
        | admin  | cópia em que nem a árvore de registro nem um diagrama de arquitetura do admin desenha o `FilamentShieldPlugin` | reprova, nomeando o painel admin e o plugin                                     |
        | admin  | cópia em que a árvore de registro do admin que vale sem tenant desenha o `TenantResource` *(alterado em 2026-11-23 — Adendo 2: linha nova)* | reprova, nomeando o painel admin e o `TenantResource`                            |
        | admin  | cópia em que nem a árvore de registro nem um diagrama de arquitetura do admin que vale sem tenant desenha o `AgenteIaResource` *(alterado em 2026-11-23 — Adendo 2: linha nova)* | reprova, nomeando o painel admin e o `AgenteIaResource`: com a chave ligada, ele abre sem tenant (P-15) |

    Esquema do Cenário: [CT-38] com tenant, os diagramas de arquitetura e a árvore de registro de cada painel que valem nesse modo desenham o registro dele nesse modo, e nada além
      Dado o modo multi-tenant ligado, com `kit.tenancy.enabled` lido como verdadeiro, toda outra chave booleana de `kit.*` lida com o valor de fábrica, o super_admin do kit autenticado e uma organização aberta no `/app`
      E o painel "<painel>" do registro de painéis, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa junta as ligações de registro que os diagramas `architecture` e a árvore de registro do painel que valem no modo com tenant desenham
      Então o resultado é "<resultado>"

      Exemplos:
        | painel | mundo                                                                                                                    | resultado                                                                                   |
        | app    | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant, inclusive `IdentifyTenant` e `DefinirTenantDePermissoes` |
        | admin  | publicados                                                                                                               | aceita: o conjunto é o registro do painel com tenant, inclusive o `TenantResource` *(alterado em 2026-11-23 — Adendo 2: a RQ-29 o põe "só nos de com", e sem o nome a linha passaria com ele fora de todo diagrama)* |
        | infra  | publicados                                                                                                               | aceita (idem)                                                                               |
        | app    | cópia em que o painel app só tem diagramas compartilhados — a arquitetura e a árvore —, sem `IdentifyTenant` nem `DefinirTenantDePermissoes` | reprova, nomeando o painel app e as duas peças                     |
        | app    | cópia em que o `IdentifyTenant` só aparece no `sequence` `requisicao-autenticada-com`, e nem a árvore nem a arquitetura do `/app` com tenant o desenham | reprova, nomeando o painel app e `IdentifyTenant` |
        | app    | cópia do modelo com a ligação "o painel app registra `App\Livewire\InformacoesPessoais`", só no modo com tenant, que a árvore de registro do `/app` com tenant desenha e o painel não registra | reprova, nomeando o painel app e `InformacoesPessoais` |
        | infra  | cópia do modelo com a ligação "o painel infra registra `HubDeInfraestrutura`" só no modo com tenant — no lugar da publicada, que vale nos dois —, e os diagramas publicados do `/infra`, todos compartilhados *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* | reprova, nomeando o painel infra e o `HubDeInfraestrutura`: a árvore `infra-registro` e o mapa `infra-resumo` valem nos dois modos e não desenham a ligação de um modo só, e nenhum diagrama do `/infra` que vale só com tenant a desenha |
```

*(alterado em 2026-11-21 — step 9, achado A15)* A linha nova é a do "nada além" com tenant, que o CT-38 não tinha: as duas linhas de controle anteriores provam o "nada a menos". Medido no commit `168d1827`: a guarda do desenho da suíte `Tenancy` junta tudo o que o painel registra, sem filtro de tipo, e reprova a cópia; a linha passa hoje e guarda contra a guarda passar a filtrar pelos cinco tipos, como a do registro (CT-55).

*(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* **A ligação de registro de um modo só nos diagramas compartilhados, com tenant.** A guarda da suíte `Tenancy` junta o que os diagramas desenham com a própria função (`tests/Tenancy/MapaInterativoTenancyTest.php`, `mapaTenancyLigacoesDesenhadas`), e não com o construtor das guardas do kit: ela filtra a ligação de registro pelo modo da suíte (`in_array($modo, $l['modos'])`), na árvore e no mapa, e conta como desenhada pela árvore compartilhada uma ligação que só vale com tenant, que o gerador certo deixa fora dela (Setup Global, "A mesma regra vale para o mapa de arquitetura e para a ponta"). Falha aberto: o registro do painel com tenant fica "desenhado" sem que diagrama nenhum o desenhe.
- **Por que só com tenant.** A guarda filtra pelo modo da suíte, que aqui é o com: a cópia com a ligação só sem tenant ela já deixa fora, e não discriminaria. A peça é uma que o `/infra` registra de verdade com tenant, para o veredito depender só do modo da ligação, e não do registro vivo: o `HubDeInfraestrutura`, sem condição, que a árvore `infra-registro` desenha e o `infra-resumo` nomeia como membro — os dois compartilhados, como os 20 diagramas do `/infra`.
- **O controle** é a linha "publicados" do `/infra`: com a ligação nos dois modos, a árvore compartilhada a desenha, e a linha aceita.
- **Medido no HEAD `b130c686`**, com `mapaTenancyLigacoesDesenhadas` e `pecaAbreNosModosDoDiagrama` copiadas dos arquivos de teste e executadas fora do repositório, sobre o modelo do commit com a ligação da cópia só em `["com"]`: a guarda de hoje conta o `HubDeInfraestrutura` como desenhado no `infra-registro` e no `infra-resumo` — o modo da suíte está nos modos da ligação, e o `HubDeInfraestrutura` não tem condição —, e aceitaria a linha; vermelho da guarda (causa a), que o executor ajusta ao escrever a linha. Com a ligação nos dois modos, o publicado, ela o conta nos mesmos dois.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* Vale a mesma leitura da R11 para o diagrama que vale no modo (`modos` contém o modo) e para a ponta desenhada (peça ou membro de agrupamento). *(alterado em 2026-11-23 — Adendo 2: com a definição do Setup Global — a peça só entra no diagrama do modo em que fica disponível, com a chave ligada)*

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-05) | o `/app` sai só com diagramas compartilhados — a arquitetura e a árvore —, que omitem as peças do modo com tenant | CT-38 | linha "só diagramas compartilhados": reprova; o mutante aceita, porque o compartilhado não desenha peça de um modo só (CT-19) |
| M2 — revisão adversarial (ADV-05) | as ligações de registro do modo com tenant ficam num diagrama de outro tipo, marcado "com tenant" | CT-38 | linha "só no `requisicao-autenticada-com`": reprova; o mutante aceita, porque o CT-14 acha a ligação desenhada *(alterado em 2026-11-20: a linha de controle era um `workflow` hipotético; todo `workflow` do commit `4cc9517` é do kit inteiro, e o caso real é a pilha da requisição com tenant, que desenha o `IdentifyTenant`)* |
| M3 | a árvore de registro de um painel esquece uma peça registrada, como a de plugin, e nenhum diagrama de arquitetura dele a desenha | CT-37 | linha "nem a árvore nem a arquitetura desenham o `FilamentShieldPlugin`": reprova; o mutante aceita, se a ligação estiver noutro tipo de diagrama |
| M4 — step 9 (A15) | na suíte `Tenancy`, a guarda do desenho do registro só compara os cinco tipos de registro, e o diagrama com tenant que desenha o registro de uma peça de outro tipo, que o painel não registra, passa | CT-38 | linha do `InformacoesPessoais` na árvore do `/app` com tenant: reprova; o mutante aceita |
| M5 *(alterado em 2026-11-23 — Adendo 2)* | a guarda tira a peça condicional por uma lista escrita à mão — a de hoje, só com o `TenantResource` —, e a peça que só abre com tenant fora da lista passa no diagrama sem tenant | CT-37 | linha app: o `UserResource`, o `ConviteResource` e o `ProjetoResource` do `/app` e o `ConvitesRecebidos`, desenhados na árvore sem tenant, reprovam; o mutante aceita |
| M6 — step 9 do Adendo, rodada 2 (AD2-02) | na suíte `Tenancy`, a guarda do desenho filtra a ligação de registro pelo modo da suíte, e não por cada modo do diagrama, e conta como desenhada pela árvore ou pelo mapa compartilhado a ligação que só vale com tenant — o caso de hoje | CT-38 | linha infra da ligação do `HubDeInfraestrutura` só com tenant: a guarda a acha desenhada no `infra-registro` e aceita; reprova; o mutante aceita |

---

## Regra R35 — Toda transição de fluxo publicado tem âncora que resolve; no `lifecycle`, todo estado também; e duas transições seguidas ancoradas no mesmo método estão na ordem do código

> `P-03`, `RQ-09` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: ADV-11 e ADV-32)*

- **A âncora.** É o arquivo e o trecho, e resolve como uma prova (R9): o arquivo é versionado no kit, e o trecho está nele, fora de comentário.
- **A transição** é a seta entre dois passos de `workflow`, `sequence` e `dataflow`, ou entre dois estados de `lifecycle`.
- **A ordem.** Só é conferível quando duas transições seguidas do mesmo fluxo (`workflow`, `sequence` ou `dataflow`) têm âncora no mesmo arquivo e no mesmo método: o trecho da primeira vem antes do da segunda nesse método. Entre arquivos ou métodos diferentes, a ordem depende da chamada em tempo de execução, que uma guarda estática não reconstrói. Essa parte é lacuna declarada (M4).
- **Qual fluxo entra no mapa** é recorte da Q6. Esta regra vale para todo fluxo publicado, qualquer que seja a lista.

```gherkin
  Regra: O que um fluxo afirma tem âncora no código, e a guarda falha quando a âncora deixa de bater

    Esquema do Cenário: [CT-39] toda transição de fluxo publicado tem âncora que resolve, todo estado de `lifecycle` também, e as transições seguidas no mesmo método estão na ordem do código
      Dado os diagramas de fluxo do mapa "<mundo>"
      Quando a guarda do mapa confere a âncora de cada transição e de cada estado
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu a âncora de todas as transições de todos os diagramas de fluxo

      Exemplos:
        | mundo                                                                                                                         | resultado                                     | # partição                       |
        | publicados                                                                                                                    | aceita                                        | válida                           |
        | cópia com um diagrama `lifecycle` do convite em que a transição Recusado → Pendente não tem âncora                             | reprova, nomeando a transição                 | transição sem âncora             |
        | cópia com a transição Recusado → Pendente ancorada num trecho que não está no arquivo                                          | reprova, nomeando a transição                 | âncora que não resolve           |
        | cópia com um estado do `lifecycle` sem âncora                                                                                  | reprova, nomeando o estado                    | estado sem âncora                |
        | cópia de um fluxo cuja transição tem a âncora num arquivo copiado em memória sem a linha do trecho                             | reprova, nomeando a transição                 | chamada que saiu do código       |
        | cópia de um fluxo com duas transições seguidas, ancoradas no mesmo método, trocadas de ordem                                   | reprova, nomeando as duas transições          | ordem trocada no mesmo método    |
```

O exemplo do convite vem do glossário: "Expirado … é o único que volta a Pendente". A cópia põe o diagrama em memória, então a linha vale mesmo que a Q6 não leve esse fluxo ao mapa.

*(alterado em 2026-11-20 — guardas do passo 8, correção de causa a devolvida pelo executor)* "Seguidas": no `sequence`, a mensagem seguinte no tempo (o `y` cresce com a ordem da lista); no `workflow` e no `dataflow`, a seta encadeada, e ramos não contam. "Mesmo método": o método nomeado que envolve o trecho, com as closures de dentro dele. Medido no passo 8: seis pares fora de ordem em quatro diagramas, corrigidos na implementação por re-âncora (a linha que prova a transição na ordem do código, ou noutro arquivo) e por uma troca de ordem entre duas respostas alternativas do mesmo `handle`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-11) | o `lifecycle` é dispensado da guarda do passo, e uma transição inventada (Recusado → Pendente) passa | CT-39 | linha "transição sem âncora": reprova; o mutante aceita |
| M2 — revisão adversarial (ADV-32) | a chamada sai do código, e a transição continua desenhada, porque as classes continuam existindo | CT-39 | linha "chamada que saiu do código": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-11) | estado do `lifecycle` escrito de memória, sem nada no código que o sustente | CT-39 | linha "estado sem âncora": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-32) | a ordem diverge do código entre transições ancoradas em arquivos ou métodos diferentes | ⚠️ **sem matador**: a ordem entre arquivos depende da chamada em tempo de execução, que a guarda estática não reconstrói. O quality gate a confere na leitura do fluxo | — (lacuna declarada) |
| M5 — revisão adversarial (ADV-11) | a transição inventada ganha uma âncora que resolve, num trecho que existe e não faz essa transição | ⚠️ **sem matador**: a âncora prova que o trecho existe, e não que ele faz a transição. Exigir no trecho o nome do estado de destino reprovaria transição real (Q18, decidida — D25): os estados do kit são calculados, e não gravados (`app/Models/Convite.php:situacao:619`, `app/Models/User.php:rotuloDaSituacao:558`); a transição grava a coluna e nunca escreve o nome do estado (`app/Models/Convite.php:'aceito_em':690`); e "prazo vence" não tem linha que a faça. Nos outros tipos de fluxo, o nome do destino no trecho falharia na chamada por injeção de dependência | — (lacuna declarada) |

---

## Regra R36 — O que o mapa desenha está no modelo: no mapa de arquitetura e na árvore de registro, toda aresta é ligação do modelo no mesmo sentido — direta, de agrupamento ou de porta —, e todo diagrama de painel só desenha, como peça, peça do painel ou do kit inteiro, além da caixa de porta

> `P-03`, `RQ-24`, `P-10`, `RQ-10` · perfil **padrão** · técnica: **EP** (forma da aresta) + controles em cópia *(regra nova em 2026-11-20: ADV-24 e ADV-27)*

A R12 vai do modelo ao desenho. Esta regra vai do desenho ao modelo, com as três formas de aresta que a sessão mediu nos mapas de arquitetura:
- **Direta.** É uma ligação do modelo, no mesmo sentido.
- **De agrupamento.** Toda ligação que ela representa existe no modelo, e liga peças que o agrupamento nomeia.
- **De porta.** O outro lado de cada ligação que ela representa é peça desenhada no mapa que a porta nomeia.

O diagrama de um painel só desenha três coisas:
- peça do painel: o que ele registra (R11) e as pontas das ligações que partem dele ou das peças dele;
- peça do kit inteiro: peça que nenhum painel registra, como model, serviço próprio, comando ou setting;
- caixa de porta.

*(alterado em 2026-11-20: medido no commit `4cc9517`, com os fluxos escritos a partir do código)* A regra da aresta vale para o mapa de arquitetura e para a árvore de registro, que desenham ligações do modelo. O fluxo (`sequence`, `workflow`, `dataflow`, `lifecycle`) desenha a ordem de execução: a mensagem e a transição dele não são ligação do modelo, e quem as confere é a âncora (R35). No fluxo de painel, a regra da peça vale para o nó que é peça do modelo, pelo nome exato; o nó que não é peça — o navegador, o provedor de IA, um formulário — fica fora dela. Medido nos 14 fluxos de painel escritos à mão do commit: 99 nós, 38 com o nome exato de uma peça do modelo, todos peça de um dos painéis do diagrama ou do kit inteiro, e 61 que não são peça; das 157 arestas, só 4 são ligação do modelo entre as duas pontas. A regra antiga, "toda aresta de todo diagrama", reprovaria esses fluxos.

```gherkin
  Regra: O desenho não inventa nem inverte ligação, e o diagrama de um painel não desenha o que é de outro

    Esquema do Cenário: [CT-40] toda aresta de mapa é ligação do modelo no mesmo sentido, e todo diagrama de painel só desenha, como peça, o que é dele, do kit inteiro ou porta
      Dado o modelo e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere cada aresta e cada peça desenhada
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu todas as arestas dos mapas de arquitetura e das árvores de registro, e todo nó que é peça do modelo nos diagramas de painel

      Exemplos:
        | mundo                                                                                                         | resultado                                     | # partição                    |
        | publicados                                                                                                    | aceita                                        | válida                        |
        | cópia com a aresta `User` → `UserResource`, invertida                                                         | reprova, nomeando o diagrama e a aresta       | direta invertida              |
        | cópia com uma aresta entre duas peças do diagrama que o modelo não liga                                       | reprova, nomeando o diagrama e a aresta       | direta inventada              |
        | cópia com uma aresta de agrupamento que representa a ligação de uma peça que o agrupamento não nomeia          | reprova, nomeando o agrupamento e a peça      | membro não nomeado            |
        | cópia com uma aresta de porta para o mapa do `/infra`, cuja outra ponta o mapa do `/infra` não desenha          | reprova, nomeando a porta                     | porta para o mapa errado      |
        | cópia de um diagrama declarado do `/infra`, copiado do `/admin`, que desenha o `UserResource`                   | reprova, nomeando o diagrama e a peça         | peça de outro painel          |

    Esquema do Cenário: [CT-54] todo id que um arquivo de diagrama construído nomeia — peça desenhada ou membro de agrupamento — é peça do modelo, em diagrama de painel e do kit inteiro, de um modo ou dos dois
      Dado o modelo de peças e os arquivos de diagrama construídos "<mundo>"
      Quando a guarda do mapa confere cada id das peças e dos membros de agrupamento de cada arquivo construído contra o modelo de peças
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu os ids de todos os arquivos construídos, os de painel e os do kit inteiro

      Exemplos:
        | mundo                                                                                                                      | resultado                                   | # partição                                   |
        | publicados                                                                                                                 | aceita                                      | válida                                       |
        | cópia do `kit-resumo-sem` (kit inteiro, um modo) com `App\Support\PecaQueNaoExiste` como membro de um agrupamento           | reprova, nomeando o diagrama e o id         | membro desconhecido que o filtro de modo some |
        | cópia do `infra-resumo` (painel, dois modos) com `App\Support\PecaQueNaoExiste` como membro de um agrupamento *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-resumo`, que a divisão por modo partiu em `admin-resumo-sem` e `admin-resumo-com`, de um modo cada)* | reprova, nomeando o diagrama e o id         | membro desconhecido que entra cru no card    |
        | cópia do `app-resumo-sem` (painel, um modo) com a peça `App\Support\PecaQueNaoExiste` entre as peças desenhadas              | reprova, nomeando o diagrama e o id         | peça desconhecida que o filtro de modo some  |
```

*(alterado em 2026-11-21 — step 9, achado A8)*
- **Por que a guarda lê o arquivo, e não o desenho.** O gerador trata o id desconhecido como "não registrado no modo" e o tira do desenho antes de conferir se ele é peça; o erro "a peça … não está no modelo" só é alcançável em diagrama de dois modos, e lá o membro desconhecido entra no "N peças" e no card "Quem está em …" como nome de classe cru. A guarda do CT-40 lê as peças desenhadas e pula o diagrama do kit inteiro, e a do CT-19 aceita id desconhecido nos dois modos. O CT-54 confere o próprio arquivo.
- **Medido no commit `168d1827`:** nos 50 arquivos construídos (46 mapas e 4 árvores), nenhum id de peça nem de membro fora do modelo de peças: a linha "publicados" passa hoje. A reprodução do revisor, em memória: o `App\Support\PecaQueNaoExiste` como membro some calado no `kit-resumo-sem`, e no `admin-resumo` aparece cru no card e faz "24 peças" de 23.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV-24) | o gerador inverte o sentido da aresta (`User` → `UserResource`) | CT-40 | linha "invertida": reprova; o mutante aceita, porque o CT-14 só vai do modelo ao desenho |
| M2 — revisão adversarial (ADV-24) | um mapa de arquitetura escrito à mão traz uma aresta que o modelo não tem | CT-40 | linha "inventada": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV-24) | a aresta de agrupamento representa a ligação de uma peça que não é membro nomeado | CT-40 | linha "membro não nomeado": reprova; o mutante aceita |
| M4 — revisão adversarial (ADV-24) | a porta aponta para um mapa que não desenha o outro lado da ligação | CT-40 | linha "porta para o mapa errado": reprova; o mutante aceita |
| M5 — revisão adversarial (ADV-27) | o diagrama de um painel é copiado de outro e declarado com o painel novo | CT-40 | linha do `UserResource` no `/infra`: reprova; o mutante aceita, porque o CT-17 decide pelo painel que o arquivo declara |
| M6 — step 9 (A8) | a peça ou o membro de agrupamento de um arquivo construído que não é peça do modelo some calado do desenho (diagrama de um modo) ou entra cru no card e na contagem (diagrama de dois modos), e nenhuma guarda compara os ids do arquivo com o modelo | CT-54 | linhas do `PecaQueNaoExiste` no `kit-resumo-sem`, no `infra-resumo` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-resumo`)* e no `app-resumo-sem`: reprova; o mutante aceita |
| M7 — step 9 do Adendo, rodada 2 (AD2-02) | a guarda da R36 deriva as arestas da árvore compartilhada pelo primeiro modo do arquivo (`modos[0]`), e as do mapa compartilhado sem filtro de modo, de disponibilidade nem de registro, e confere arestas que o gerador certo não desenha | ⚠️ **sem matador**, por equivalência para o veredito do CT-40 (equivalente comprovado; contado como sem matador para a sessão decidir se o encerra): a guarda tira as arestas da árvore e do mapa construídos do próprio modelo, e cada uma que o mutante acrescenta é ligação do modelo, no sentido dela, com a ponta de painel que a ligação torna permitida (`tests/Kit/MapaInterativoTest.php`, `mapaModeloPecasDoPainel`). O mutante só confere um conjunto maior que o desenhado, sem perder aresta do gerador certo, e o veredito não muda em mundo nenhum; a contagem de arestas conferidas, que muda, não é oráculo da R36. A divergência da regra do Setup Global fica para o executor alinhar, sem linha nova | — (equivalente para o veredito; prova ao lado) |

*(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* **O R36-M7 não ganhou linha.** A revisão apontou a cópia do ramo `registro` de `mapaModeloArestasDoDiagrama` (o `modos[0]`), e o ramo `mapa` tem o `$modo` nulo do gerador de hoje. As duas só fazem a guarda conferir arestas a mais, todas tiradas do modelo, e nenhum mundo — publicado ou cópia — muda o veredito por elas: uma linha nova ficaria verde sob o mutante, e a skill a proíbe ("lacuna fechada sem discriminar é piora"). O que pegaria a cópia seria um `Então` sobre o conjunto de arestas conferidas, igual ao da montagem do gerador; ele testaria a guarda, e não a R36. Os mutantes vizinhos que mudam veredito morrem no CT-14 (R12-M11 a M13) e no CT-38 (R34-M6).

---

## Regra R37 — Toda marca de disponibilidade que o mapa publica é a do código: a chave que decide o acesso da peça, com o valor de fábrica dela, ou o modo, ou os dois

> `P-03`, `RQ-04`, `RQ-05`, `RQ-11`, `RQ-27` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha) + controles em cópia *(regra nova em 2026-11-20: ADV2-30)* *(alterado em 2026-11-23 — Adendo 2: "ou os dois" no enunciado, e a RQ-27 na origem)*

A Q11 não diz se o mapa marca a peça condicional (R30, estacionada). Mas, se marcar, a marca tem de ser a do código, e isso vale nas três leituras da Q11. No publicado sem marca nenhuma, a regra é vácua.

*(alterado em 2026-11-23 — Adendo 2)*
- **Deixa de ser vácua.** O mapa marca (RQ-27): a presença da marca é da R42 (CT-57), e o conteúdo de cada marca publicada — no modelo ou num nó de diagrama escrito à mão — continua daqui.
- **O widget.** O discriminante que a sessão fixou lê o `canView()` do widget. A chave da marca é a que o `canAccess()` (recurso e página) **ou o `canView()` (widget)** lê, direto ou pelo apoio que ele chama — no kit, o `fonteDeDadosDisponivel()` que o `canView()` de `ExigePermissaoDoWidget` chama (`app/Filament/Admin/Widgets/AgentesIaStats.php:fonteDeDadosDisponivel:29`). Uma guarda que só lê o `canAccess()` reprovaria, como "chave que não decide", a marca certa dos três widgets condicionais do `/admin`.
- **A marca dupla.** A do `ProjetoResource` do `/app` tem o modo e a chave (`KIT_DEMO`), e é conferida nas duas partes: conferir só o modo e seguir deixaria passar a chave errada.
- **Redundância, proposta à sessão.** Os três mutantes desta regra morrem também no CT-32, pelo discriminante vivo (a linha "modo invertido" do CT-32 mata o M3 no modelo). O que só o CT-45 confere é a marca escrita à mão num nó de diagrama, que hoje não existe. A sessão decide se o CT-45 fica com esse papel ou vira `@obsoleto`; esta derivação não o risca, porque ele tem teste no repositório. *(alterado em 2026-11-23 — Adendo 3: decidido pela sessão — o CT-45 fica, e confere a marca escrita à mão nos diagramas curados. Pela RQ-30 e pela RQ-31, a leitura estática dele tem de aceitar a chave que é configuração de pacote — o `canAccess()` do `CommandRecordResource` lê `command-center.sources`, e a marca nomeia esse caminho — e a condição que chega pelo apoio de outra peça — o `canView()` do `AcessosPorPainel` chama o `canAccess()` do `TenantResource`, que lê `kit.tenancy.enabled`. Sem isso, o CT-45 ficaria vermelho contra a marca certa das duas)* *(alterado em 2026-11-23 — Adendo 4)* E, pela RQ-33, a leitura estática do componente flutuante, que não tem `canAccess()` nem `canView()`, segue o predicado que decide a renderização dele: o `AgentesDeIa::assistenteNoPainel()`, que lê `kit.agentes_ia.habilitado` e `kit.agentes_ia.paineis` (`KIT_AGENTES_IA` e `KIT_AGENTES_IA_PAINEIS`), para o chat; o `TutorialGuiado::visivel()`, que chega a `kit.onboarding.habilitado` (`KIT_ONBOARDING`) pelo `canAccess()` da página Primeiros passos, para o checklist. É no CT-45 que a marca do chat nos dois diagramas escritos à mão — o `app-chat-de-ia` e o `ia-do-prompt-ao-ledger` — tem o conteúdo conferido. *(alterado em 2026-11-23 — step 11, Adendo 4: no diagrama escrito à mão, a marca fica só na linha da peça no card "Condições de disponibilidade" (R42), e é essa linha que o CT-45 lê: medido no archify (commit `4478129`), a caixa do participante do sequence chega no máximo a 190 px (`renderers/sequence/render-sequence.mjs`, `participantSpreadGeometry`), e o nó do dataflow, sem largura explícita, à largura do estágio, 168 px (`renderers/dataflow/dataflow-compiler.mjs`, `stageW`); a marca do chat pede cerca de 345 px e 381 px, e a largura explícita que a comportaria quebra o roteamento das setas *(alterado em 2026-11-23 — step 9 do Adendo, RD-05: dizia "96 px fixos" e "144 px", a medida daqueles diagramas, e não o limite do archify)*)*
- **Medido no HEAD `c642c23c`:** nenhuma marca publicada, no modelo nem nos 87 arquivos de diagrama; o CT-45 aceita por vácuo.
- *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* **O nó do card é o da D43** (Setup Global), o mesmo da guarda da marca (CT-57): a mesma cópia decide igual nos dois. A linha nova é o mundo do achado — no `admin-convite`, o participante "ConviteResource", que a prova da classe do `/admin` faz ser o `ConviteResource` do `/admin` (sem condição), e no card a linha da marca do `ConviteResource` do `/app` —, e reprova na R37 e no CT-57. **Medido no HEAD `1dd48acd`**, executando as guardas de hoje copiadas dos arquivos de teste, fora do repositório: a R37 reprova ("marca a peça `App\Filament\Admin\Resources\Convites\ConviteResource` como disponível só no modo "com", e o que decide o acesso dela lê []"), e a linha fica verde hoje; o CT-57 aceita a mesma cópia, porque toma as duas homônimas pelo nome — é a linha nova dele que fica vermelha hoje.
- *(alterado em 2026-11-23 — step 9 do Adendo, achado A7)* **As duas colunas do card.** A linha en do card "Condições de disponibilidade" do diagrama escrito à mão é marca publicada, como a pt (P-02), e a guarda a lê do mesmo jeito, na redação en decidida — "tenancy on only", "by default", e "empty" na chave que é lista. **Medido no HEAD `1dd48acd`**, executando a guarda de hoje copiada do arquivo de teste: o coletor (`tests/Kit/MapaInterativoTest.php`, `mapaModeloMarcasDeDisponibilidade`) só lê a coluna pt; `KIT_REGISTRO=false` na linha en do chat passa, e a mesma troca na pt reprova, nomeando o chat e a chave. A linha nova fica vermelha hoje pela guarda, que aceitaria a cópia (causa a). Varrido no HEAD `1dd48acd`: as 2 linhas dos cards escritos à mão — a do `app-chat-de-ia` e a do `ia-do-prompt-ao-ledger` — dizem em en o mesmo rótulo, a mesma condição e a mesma âncora da pt; com a guarda que lê as duas colunas, a linha "publicados" do CT-45 continua verde.
- **A chave e o valor de fábrica.** A chave da marca é a que o `canAccess()` da peça lê, direto ou pelo apoio que ele chama, e o valor de fábrica é o default dela no `config/kit.php`. A chave vale pela variável do `.env` ou pela chave de config.
- **O modo.** A marca de modo diz em que modo a peça abre, pela leitura de `kit.tenancy.enabled` no `canAccess()`.

Medido no código:
- o `canAccess()` do `AgenteIaResource` lê `AgentesDeIa::habilitado()` (`app/Filament/Admin/Resources/AgentesIa/AgenteIaResource.php:canAccess:70`), que lê `kit.agentes_ia.habilitado` (`app/Support/AgentesDeIa.php:habilitado:29`), cuja variável é `KIT_AGENTES_IA`, desligada de fábrica (`config/kit.php:'habilitado':682`);
- o `canAccess()` do `TenantResource` lê `kit.tenancy.enabled` (`app/Filament/Admin/Resources/Tenants/TenantResource.php:canAccess:107`), cuja variável é `KIT_TENANCY`, desligada de fábrica (`config/kit.php:'enabled':452`);
- o esqueleto publicado não tem marca de disponibilidade, e a regra hoje é vácua.

```gherkin
  Regra: O que o mapa diz sobre a disponibilidade de uma peça é o que o código decide

    Esquema do Cenário: [CT-45] toda marca de disponibilidade publicada é a do código, com a chave que decide e o valor de fábrica, ou o modo
      Dado o modelo e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere cada marca de disponibilidade publicada contra o `canAccess()` ou o `canView()` da peça
      Então o resultado é "<resultado>"

      Exemplos:
        | mundo                                                                                         | resultado                                                        | # partição               |
        | publicados                                                                                    | aceita: cada marca é a do código; sem marca nenhuma, aceita por vácuo *(alterado em 2026-11-23 — step 11, Adendo 4: com a marca publicada, a regra deixa de ser vácua; a guarda lê também a linha de cada peça no card "Condições de disponibilidade" dos dois diagramas escritos à mão, o `app-chat-de-ia` e o `ia-do-prompt-ao-ledger`)* | válida              |
        | cópia que marca o `AgenteIaResource` com a chave `KIT_REGISTRO`, que não decide o acesso dele | reprova, nomeando a peça                                         | chave que não decide     |
        | cópia que marca o `AgenteIaResource` com `KIT_AGENTES_IA` ligada de fábrica                   | reprova, nomeando a peça                                         | valor de fábrica trocado |
        | cópia que marca o `TenantResource` como disponível só sem tenant                              | reprova, nomeando a peça                                         | modo invertido           |
        | controle: cópia com a condição certa em cada peça condicional do modelo *(alterado em 2026-11-23 — step 11, Adendo 4)* | aceita: uma marca por peça condicional do modelo, e uma por linha dos cards dos diagramas escritos à mão | válida, com a população inteira marcada |
        | cópia do card "Condições de disponibilidade" do `app-chat-de-ia` com `KIT_REGISTRO=false` no lugar de `KIT_AGENTES_IA=false`, na linha do chat *(alterado em 2026-11-23 — step 11, Adendo 4)* | reprova, nomeando o chat e a chave `KIT_REGISTRO` | chave que não decide, no card do diagrama escrito à mão |
        | cópia do `admin-convite` com o participante "ConviteResource", de prova `class ConviteResource extends Resource` no arquivo do `ConviteResource` do `/admin`, e a linha "ConviteResource: só com tenant ativo — app/Filament/App/Resources/Convites/ConviteResource.php — …", a marca do `ConviteResource` do `/app`, no card "Condições de disponibilidade" *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* | reprova, nomeando o `ConviteResource` do `/admin`: pela prova, o nó é ele, e o acesso dele não lê o modo — o mesmo mundo e o mesmo veredito da linha do CT-57 | homônima designada pela prova, com a marca da outra (D43) |
        | cópia do card "Condições de disponibilidade" do `app-chat-de-ia` com `KIT_REGISTRO=false` no lugar de `KIT_AGENTES_IA=false` só na linha en do chat, e a pt intacta *(alterado em 2026-11-23 — step 9 do Adendo, achado A7)* | reprova, nomeando o chat e a chave `KIT_REGISTRO` | chave que não decide, na coluna en do card escrito à mão (P-02) |
        | cópia do `admin-convite` com o participante "ConviteResource" sem fonte que declare classe de peça e a linha "ConviteResource: só com tenant ativo — …" no card *(alterado em 2026-11-23 — step 9 do Adendo, D44)* | reprova: o rótulo não designa peça (D43), e a R37 não tem peça contra a qual conferir a marca | linha de card de nó que não designa peça (D44) |
```

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — revisão adversarial (ADV2-30) | o mapa marca a peça com uma chave vizinha, que não decide o acesso dela | CT-45 | linha `KIT_REGISTRO`: reprova; o mutante aceita |
| M2 — revisão adversarial (ADV2-30) | a marca traz o valor de fábrica trocado | CT-45 | linha "ligada de fábrica": reprova; o mutante aceita |
| M3 — revisão adversarial (ADV2-30) | a marca de modo sai invertida, e a tela de organizações aparece como disponível só sem tenant | CT-45, CT-32 *(alterado em 2026-11-23 — Adendo 2: CT-32)* | CT-45, linha "só sem tenant"; CT-32, linha "modo invertido", no modelo: reprova; o mutante aceita |
| M4 — step 9 do Adendo (A6) | a guarda aceita a linha do card escrito à mão se ela for a marca de alguma peça do nome do rótulo — toma as homônimas, como a guarda do CT-57 de hoje —, e a marca do `ConviteResource` do `/app` passa pelo participante que a prova faz ser o do `/admin`; as duas guardas decidem diferente a mesma cópia | CT-45, CT-57 | CT-45, linha do participante "ConviteResource" de prova do `/admin` com a marca do `/app` no card: reprova, nomeando o `ConviteResource` do `/admin`; CT-57, a mesma cópia: reprova; o mutante aceita |
| M5 — step 9 do Adendo (A7) | a guarda lê só a coluna pt do card escrito à mão, e a linha en com a chave que não decide passa | CT-45 | linha do `KIT_REGISTRO=false` só na linha en do chat: reprova, nomeando o chat e `KIT_REGISTRO`; o mutante aceita |
| M6 — step 9 do Adendo (D44) | a guarda ignora a linha do card escrito à mão cujo rótulo não designa peça, e a marca de uma peça que o diagrama não desenha passa | CT-45 | linha da D44 no `admin-convite`: reprova; o mutante aceita |

---

## Regra R38 — O traço da seta e a caixa de agrupamento do mapa de arquitetura só dizem do que representam o que o modelo dá ao diagrama, no modo dele

> `P-13`, `RQ-08`, `P-10`, `P-02` · perfil **padrão** · técnica: **EP** (os verbos das ligações de um par de pontas: um, dois, três) + **BVA** na contagem da caixa (0, 1 e N membros no modo do diagrama) + controles em memória · costura **G5**, forma de leitura *(regra nova em 2026-11-21: step 9, achados A1 e A5)*

- **A seta.** No mapa de arquitetura construído, as ligações do modelo que caem no mesmo par de pontas desenhadas — a peça, ou o agrupamento que a nomeia, no painel e no modo do diagrama, como na R12 — viram uma seta só, e o traço dela é a variante que o gerador entrega ao archify (`site/mapa/gerar.mjs`, `mapaDeArquitetura`, no commit `168d1827`), que o desenho leva como a classe `a-{variante}` da aresta. A legenda "Como ler as setas" diz o verbo de cada traço: `registra`, traço forte (`emphasis`); `usa`, comum (`default`); `lê`, tracejado (`dashed`); `grava`, alerta (`security`). O gerador dá à seta o traço da primeira ligação do modelo que cai nela.
- **Uma seta por verbo** *(alterado em 2026-11-21 — step 9: Q22 decidida pela sessão)*. A seta junta só ligações do mesmo verbo; dois verbos no mesmo par de pontas viram duas setas, cada uma com o traço do seu verbo. O archify só tem os quatro traços, todos já de verbo, e duas conexões no mesmo par renderizam (conferido pela sessão).
- **O oráculo.** Em cada mapa construído, toda seta desenhada leva ligações de um verbo só, com o traço desse verbo, e cada par (pontas, verbo) das ligações do modelo que o mapa desenha tem a sua seta, uma só. Lido na entrada do gerador: para cada par de pontas desenhadas, os traços das setas do par são os dos verbos das ligações do modelo que caem nele — nenhum a mais, nenhum a menos, nenhum repetido.
- **A caixa.** O rótulo secundário da caixa de agrupamento diz quantas peças ela nomeia no modo do diagrama — depois do filtro de modo, que tira o membro que o painel não registra naquele modo *(alterado em 2026-11-23 — Adendo 2: e o que fica indisponível nele mesmo com a chave ligada, pela definição do Setup Global; a caixa de multi-tenancy do resumo do `/admin` sem tenant perde o `TenantResource`)* —, com a palavra no número certo: "1 peça" / "1 piece", "2 peças" / "2 pieces". O gerador escreve sempre o plural (`site/mapa/gerar.mjs`, `mapaDeArquitetura`, no commit `168d1827`). A caixa que o filtro de modo esvazia não é desenhada, ou o gerador recusa o diagrama, nomeando o agrupamento.

```gherkin
  Regra: O desenho do mapa de arquitetura não diz da ligação nem do agrupamento o que o modelo não diz

    Esquema do Cenário: [CT-46] cada seta do mapa de arquitetura leva ligações de um verbo só, com o traço desse verbo, e cada par de pontas tem uma seta por verbo
      Dado o `node` do ambiente, lido pela versão
      E o modelo e os arquivos de diagrama "<mundo>"
      Quando o gerador monta, para o archify, cada mapa de arquitetura construído, sem baixar o archify nem renderizar
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu as setas de todos os mapas construídos, e ao menos um par de pontas com ligações de dois verbos

      Exemplos:
        | mundo                                                                                                                                          | resultado                                                                                                                                         | # partição                                  |
        | publicados                                                                                                                                     | aceita: em cada mapa, os traços das setas de cada par de pontas desenhadas são os dos verbos das ligações do modelo que caem nele, um por verbo, sem faltar nem repetir | válida (hoje reprova: nota abaixo)          |
        | a entrada do `app-acesso-e-autenticacao-com` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `app-acesso-e-autenticacao`, que a divisão por modo partiu; o par está no de com tenant, o modo em que o `UserResource` do `/app` abre)*, adulterada em memória: o par `UserResource` → `User` do `/app`, com a ligação "lê" e a "usa", desenhado por uma seta só, a de "lê" | reprova, nomeando o diagrama, o par e o verbo "usa", que ficou sem seta                                        | par sem a seta de um dos verbos             |
        | a entrada do mesmo mapa, adulterada em memória: o mesmo par com as duas setas, a de "usa" com o traço de "lê"                                   | reprova, nomeando o diagrama e o par                                                                                                              | seta com o traço de outro verbo             |
        | cópia em memória do modelo com a ligação "o `UserResource` do `/app` grava `User`" ao lado da que "lê" e da que "usa"                          | aceita (idem): três setas no par, uma por verbo, com três traços                                                                                  | três verbos num par, que o publicado pode deixar de ter |

    Esquema do Cenário: [CT-47] a caixa de agrupamento diz quantas peças nomeia no modo do diagrama, no número certo, e a caixa vazia não é desenhada
      Dado o `node` do ambiente, lido pela versão
      E o modelo e os arquivos de diagrama "<mundo>"
      Quando o gerador monta, para o archify, cada mapa de arquitetura construído, sem baixar o archify nem renderizar
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu as caixas de agrupamento de todos os mapas construídos

      Exemplos:
        | mundo                                                                                                                                                    | resultado                                                                                                                                         | # borda                                |
        | publicados                                                                                                                                               | aceita: o rótulo secundário de cada caixa diz o número de membros dela no modo do diagrama — "N peças" / "N pieces" com N maior que 1, "1 peça" / "1 piece" com um —, e nenhuma caixa desenhada tem zero membro | válida (hoje reprova: nota abaixo)     |
        | cópia em memória do `app-resumo-sem` com um agrupamento de três membros: o `Dashboard` do `/app` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `UserResource` do `/app`, que só abre com tenant e que o diagrama sem tenant não desenha mais)*, e `IdentifyTenant` e `DefinirTenantDePermissoes`, que o painel app só registra com tenant | a caixa diz "1 peça" / "1 piece"                                                                                      | 1, depois do filtro de modo            |
        | cópia em memória do `app-resumo-sem` com um agrupamento só de `IdentifyTenant` e `DefinirTenantDePermissoes`                                              | a caixa não é desenhada, ou o gerador recusa o diagrama, nomeando o agrupamento                                                                    | 0, depois do filtro de modo            |
```

**Hoje o dado reprova o CT-46.** Medido no commit `168d1827`, com o filtro e a junção do gerador replicados sobre o modelo e os 46 mapas construídos: 268 setas, das quais 51, em 13 mapas, juntam ligações de verbos diferentes e levam o traço da primeira ligação. O revisor contou 36, e a contagem não se reproduz por diagrama (51), por par de pontas único (23) nem fora dos mapas do kit inteiro (44): a lista abaixo é a medida. Por mapa — "origem → destino {verbos}, traço de …", com "agrupamento …" para a caixa:
- `admin-acesso-e-autenticacao` (2): `BreezyCore` → `DefinirSenhaPorEmail` e `BreezyCore` → `InformacoesPessoais` {registra, usa}, traço de registra.
- `admin-administracao` (1): `TutorialGuiado` → `FilamentOnboardingPlugin` {registra, usa}, registra.
- `admin-resumo` (11): painel admin → agrupamentos acesso-e-autenticacao, produtividade-e-ui, administracao e multi-tenancy-opt-in {registra, usa}, registra; acesso-e-autenticacao → produtividade-e-ui {usa, registra}, usa; acesso-e-autenticacao → multi-tenancy-opt-in {usa, lê}, usa; administracao → acesso-e-autenticacao {usa, lê}, usa; administracao → autorizacao {lê, usa}, lê; administracao → produtividade-e-ui {usa, lê}, usa; convites → acesso-e-autenticacao {grava, usa}, grava; produtividade-e-ui → multi-tenancy-opt-in {lê, usa}, lê.
- `app-acesso-e-autenticacao` (3): `UserResource` → `User` {lê, usa}, lê; `BreezyCore` → `DefinirSenhaPorEmail` e `BreezyCore` → `InformacoesPessoais` {registra, usa}, registra.
- `app-administracao` (1): `TutorialGuiado` → `FilamentOnboardingPlugin` {registra, usa}, registra.
- `app-resumo-sem` e `app-resumo-com` (9 cada, os mesmos pares): painel app → agrupamentos convites, produtividade-e-ui e administracao {registra, usa}, registra; painel app → multi-tenancy-opt-in {lê, usa}, lê; acesso-e-autenticacao → produtividade-e-ui {registra, usa}, registra; acesso-e-autenticacao → multi-tenancy-opt-in {lê, usa}, lê; convites → acesso-e-autenticacao {usa, grava}, usa; produtividade-e-ui → multi-tenancy-opt-in {usa, lê}, usa; administracao → produtividade-e-ui {usa, lê}, usa.
- `infra-acesso-e-autenticacao` (2): `BreezyCore` → `DefinirSenhaPorEmail` e `BreezyCore` → `InformacoesPessoais` {registra, usa}, registra.
- `infra-administracao` (1): `TutorialGuiado` → `FilamentOnboardingPlugin` {registra, usa}, registra.
- `infra-resumo` (5): acesso-e-autenticacao → multi-tenancy-opt-in {lê, usa}, lê; acesso-e-autenticacao → produtividade-e-ui {registra, usa}, registra; administracao → produtividade-e-ui {usa, lê}, usa; convites → acesso-e-autenticacao {grava, usa}, grava; produtividade-e-ui → multi-tenancy-opt-in {lê, usa}, lê.
- `kit-acesso-e-autenticacao` (1): `AppServiceProvider` → `BloquearSessaoComRetorno` {registra, usa}, registra.
- `kit-resumo-sem` e `kit-resumo-com` (3 cada, os mesmos pares): ia → administracao {usa, lê}, usa; autorizacao → produtividade-e-ui {registra, usa}, registra; autorizacao → administracao {lê, usa, registra}, lê.

O desenho confirma o traço: no `app-acesso-e-autenticacao` gerado numa pasta de rascunho, a aresta `UserResource` → `User` sai com a classe `a-dashed`, o traço de "lê", embora junte também a ligação "usa". O item de prova de cada ligação traz o verbo certo, em texto; é o traço que mente. *(alterado em 2026-11-21 — step 9: Q22 decidida)* As 51 setas são vermelho de implementação: pela decisão, cada um desses pares vira uma seta por verbo — 104 setas no lugar das 51 (49 pares de dois verbos e os 2 pares de três, nos dois resumos do kit), e os 46 mapas passam de 268 a 321 setas —, e a validação de layout do archify pede reposicionar os mapas afetados, o que também é implementação.

**Hoje o dado reprova o CT-47.** Medido no commit `168d1827`: das caixas de agrupamento dos 46 mapas construídos, uma tem um membro no modo do diagrama — o agrupamento `grupo-ia` ("IA") do `admin-resumo`, só com o `TelaDeAutenticacao` —, e sai com "1 peças" / "1 pieces". Nenhuma caixa fica vazia no modo do diagrama hoje; a linha da caixa vazia é controle, e o gerador de hoje a desenharia com "0 peças" e sem fonte.

- **Costura G5, forma de leitura.** Os dois casos rodam num `node` a função que o gerador exporta para montar a entrada do archify, mapa a mapa; a cópia em memória do modelo é a que `carregarModelo()` devolve, alterada antes da montagem, e a entrada adulterada controla a própria guarda. A guarda acha as ligações de cada par pela regra de desenho da R12.
- **Executado no commit `168d1827`, pela forma de leitura:** a função montou a entrada dos 87 diagramas, o comparativo inclusive, em 4 s, sem erro, sem baixar o archify nem renderizar. Pela entrada: as mesmas 51 setas, em 13 mapas, com o traço de um verbo só. Os controles em memória: a seta `UserResource` → `User` do `/app` sai `dashed` antes e depois da ligação "grava" a mais; a caixa de três membros sai "1 peças" / "1 pieces" — a contagem do modo está certa, o número não —; e a caixa só de `IdentifyTenant` e `DefinirTenantDePermissoes` sai desenhada no `app-resumo-sem`, com "0 peças" e nenhuma fonte. As três linhas de controle reprovam hoje, como a "publicados".
- **Por que não no navegador.** O traço que o gerador entrega é o que o desenho leva (a classe `a-{variante}`, medida), e a caixa é texto da entrada; conferir no HTML construído provaria o mesmo, mais caro, e só no job `site`.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — step 9 (A1) | uma seta só por par de pontas, com o traço da primeira ligação do modelo que cai nele | CT-46 | publicados: nos 51 pares de hoje falta a seta de um verbo, e reprova; linha da cópia com "grava": o par tem uma seta, e não três; o mutante aceita |
| M2 — step 9 (A1; Q22) | o par é dividido por verbo, mas as setas dele saem todas com o traço do primeiro verbo | CT-46 | linha da cópia com "grava": três setas com um traço só, e não três traços; reprova; o mutante aceita |
| M6 — step 9 (A1; Q22) | a divisão por verbo mantém o id da seta pelo par de pontas (`{de}--{para}`), e a coleção de setas, indexada pelo id, guarda uma só por par | CT-46 | publicados e linha da cópia com "grava": o par fica com uma seta, e falta a dos outros verbos; reprova; o mutante aceita |
| M3 — step 9 (A5) | o rótulo secundário da caixa é sempre plural, e a de um membro sai "1 peças" / "1 pieces" | CT-47 | publicados: o `grupo-ia` do `admin-resumo`; e a linha de três membros: "1 peças" reprova; o mutante aceita |
| M4 — step 9 (A5) | o filtro de modo esvazia o agrupamento, e a caixa sai desenhada com "0 peças" e sem fonte | CT-47 | linha do agrupamento só de `IdentifyTenant` e `DefinirTenantDePermissoes`: a caixa desenhada reprova; o mutante aceita |
| M5 — step 9 (A5) | a caixa conta os membros do arquivo, antes do filtro de modo | CT-47 | linha de três membros: "3 peças" no lugar de "1 peça" reprova; o mutante aceita |

---

## Regra R39 — As ferramentas do mapa não apagam o que não geraram e não terminam com código 0 sem ter feito o que se pediu, por qualquer caminho que sejam chamadas

> `P-14`, `P-07` · perfil **padrão** · técnica: **EP** (a pasta de saída: com arquivo do autor, a própria `site/`; o caminho da chamada: o real, por link) + controle · costura **G5**, forma de efeito *(regra nova em 2026-11-21: step 9, achados A2 e A4)*

- **A pasta de saída.** Com `--saida` e sem slug, o gerador apaga a pasta que o autor passou antes de gerar (`site/mapa/gerar.mjs`, `gerar`, no commit `168d1827`): `--saida .` dentro de `site/` apagaria `site/`, e `--saida /tmp/mapa`, o que outro autor gerou ali. Pela P-14, ele confere a pasta antes de qualquer efeito e recusa a que já tem arquivo que ele não gerou — arquivo que não é HTML do mapa em `pt/` e `en/` —, antes de baixar o archify e de renderizar, e é isso que deixa o caso rápido e sem rede. A pasta vazia, a que não existe e a que só tem HTML do mapa continuam aceitas, e não entram aqui: aceitá-las baixa o archify e renderiza.
- **O caminho da chamada.** A guarda do fim do gerador só o roda quando `process.argv[1]` é o caminho do módulo (`site/mapa/gerar.mjs`, `process.argv`, no commit `168d1827`), e o Node resolve o link na URL do módulo e não no argumento: chamado por um caminho que passa por um link, o gerador não roda e termina com código 0.
- **Medido no commit `168d1827`, na árvore do kit, com o archify já em `site/.archify`** (fora da G5, só para expor o defeito): o gerador, com `--saida` numa pasta de rascunho com um `leia-me.txt`, imprimiu "[mapa] archify … pronto" e apagou o `leia-me.txt` em 567 ms, antes do primeiro render. No Windows, uma junção (`mklink /J`, sem privilégio) para `site/`: `node <junção>/mapa/gerar.mjs --saida` terminou com código 0 e não imprimiu nada; pelo caminho real, terminou com 1 e um erro. O achado A4 é falsificável nesta pilha, ao contrário do que o despacho supunha: a junção faz no Windows o que o link simbólico faz no Linux. A forma do `npm run build` — o diretório corrente dentro da junção e o caminho relativo — rodou normal no Git Bash, que resolve o diretório; o oráculo é a chamada direta pelo caminho do link.
- **Medido no commit `168d1827`, na G5** — um clone `--shared` com `site/mapa` e a raiz, sem `site/.archify`, com `GIT_ALLOW_PROTOCOL=file`; o arnês conferido: o `git show` do clone funciona, e o `fetch` do archify por https sai com "fatal: transport 'https' not allowed":
  - CT-48, as duas linhas: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", que não nomeia a pasta; o `site/.archify` do clone criado pela tentativa; o `leia-me.txt` e o `site/` do clone intactos, porque o download falha antes de o gerador apagar. No clone, o defeito do achado A2 aparece pela mensagem e pela pasta do archify, e não pela remoção, que o download bloqueado mascara;
  - CT-51: pelo caminho real, código 1 e o mesmo erro do archify, com o `site/.archify` criado; pela junção, código 0, nenhuma saída, e nada criado.

```gherkin
  Regra: A ferramenta do mapa não apaga o que não gerou, e não sai com 0 sem fazer o que se pediu

    Esquema do Cenário: [CT-48] com `--saida` e sem slug, o gerador recusa, antes de qualquer efeito, a pasta que tem o que ele não gerou, e não apaga nada
      Dado o `node` do ambiente, lido pela versão, e um clone descartável do repositório no commit, sem `site/.archify` e com o download do archify bloqueado
      E a pasta de saída "<pasta>"
      Quando o autor roda, de `site/` do clone, `node mapa/gerar.mjs --saida` com essa pasta e sem slug
      Então o gerador termina com código diferente de zero, com a mensagem `[mapa]` que nomeia a pasta e diz por que a recusa
      E <intacto> continua, com os mesmos bytes
      E nada foi baixado nem gerado: o clone continua sem `site/.archify`, e nenhum HTML do mapa foi escrito

      Exemplos:
        | pasta                                                                                  | intacto                                                             | # partição                 |
        | uma pasta temporária com o arquivo `leia-me.txt`, do autor, e nenhum HTML do mapa      | o `leia-me.txt`                                                     | pasta com arquivo do autor |
        | `.`, a própria `site/` do clone                                                        | todo arquivo versionado de `site/` do clone (o `git status` limpo)  | a pasta do site            |

    Cenário: [CT-51] o gerador chamado por um caminho que passa por um link faz o mesmo que pelo caminho real, e não termina com 0 calado
      Dado o `node` do ambiente, lido pela versão, e um clone descartável do repositório no commit, sem `site/.archify` e com o download do archify bloqueado
      E um link para `site/` do clone: uma junção no Windows (`mklink /J`, sem privilégio), ou um link simbólico no Linux
      Quando o autor pede `mapa/gerar.mjs --saida` com uma pasta temporária vazia e o slug `diagrama-que-nao-existe`, uma vez pelo caminho real de `site/` do clone e outra pelo link, com o `site/.archify` apagado entre as duas
      Então as duas chamadas terminam com o mesmo código e a mesma saída, fora o caminho
      E o código das duas é diferente de zero
```

- **Hoje.** As duas linhas do CT-48 reprovam pela mensagem e pela pasta do archify criada (acima). O CT-51 reprova: pelo caminho real, código 1 e o erro do archify; pelo link, código 0 e nada. O oráculo do CT-51 é relativo de propósito: ele isola o achado A4 da ordem da recusa, que é do CT-49 e do CT-50, e vale qualquer que seja a mensagem do caminho real, desde que diferente de zero.
- **Costura G5, forma de efeito.** O clone descartável protege a árvore do kit, que o gerador, sob o defeito e com o archify à mão, apagaria; o download bloqueado faz a implementação que baixa antes de recusar reprovar sem rede, pela mensagem e pelo `site/.archify` criado.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — step 9 (A2) | com `--saida` e sem slug, o gerador apaga a pasta inteira antes de gerar, sem recusá-la | CT-48 | no clone: "a mensagem `[mapa]` que nomeia a pasta" — o gerador de hoje sai com o erro do archify — e "o clone continua sem `site/.archify`" — a tentativa cria a pasta; reprova; o mutante aceita. O download bloqueado mascara a remoção; com o archify à mão, o `leia-me.txt` some (medido: 567 ms) |
| M2 — step 9 (A2) | o gerador confere a pasta só depois de baixar o archify, ou recusa depois de ter apagado | CT-48 | "o clone continua sem `site/.archify`" (o primeiro) e "o `leia-me.txt` continua, com os mesmos bytes" (o segundo, que apaga antes do download e da recusa): reprova; o mutante aceita |
| M3 — step 9 (A4) | o gerador só roda quando `process.argv[1]` é o caminho real do módulo, e, chamado por um link, termina com 0 sem fazer nada | CT-51 | "as duas chamadas terminam com o mesmo código": pelo link, 0 e nenhuma saída; pelo caminho real, 1; reprova; o mutante aceita |

---

## Regra R40 — Argumento sem valor válido e arquivo de entrada quebrado são recusados antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o culpado

> `P-14`, `P-03` · perfil **padrão** · técnica: **EP** (uma partição inválida por linha: argumento ausente, argumento não numérico, JSON quebrado por arquivo) · costura **G5**, forma de efeito; e **G4** para o conferidor (CT-B08, no `05`) *(regra nova em 2026-11-21: step 9, achados A6 e A11)*

- **O argumento.** O posicionador lê `--tentativas` com `Number()` (`site/mapa/posicionar.mjs`, `TENTATIVAS`, no commit `168d1827`): sem número, dá NaN, a busca não roda, e a ferramenta baixa o archify, avalia a grade inicial e sai com "nenhuma grade aceita em NaN tentativas" — ou grava a grade inicial sem busca, se o archify a aceitar. O `--saida` sem pasta, no gerador, cai num erro do Node. O valor válido de `--tentativas` é um número inteiro positivo; o zero é recusado (Q24, decidida pela sessão em 2026-11-21).
- **A entrada.** O gerador lê o modelo e os arquivos de diagrama com `JSON.parse` sem o nome do arquivo; o posicionador, o arquivo do diagrama pedido; o conferidor, os arquivos de diagrama (CT-B08, no `05`, porque ele importa o Playwright e lê o `axe-core` de `site/node_modules` antes de ler os diagramas).
- **Medido no commit `168d1827`, com o archify à mão** (um clone com `site/.archify` ligado ao da árvore, fora da G5, só para expor o defeito):
  - `node mapa/posicionar.mjs app-resumo-sem --tentativas`, sem número: "[mapa] archify … pronto", a grade inicial avaliada e "nenhuma grade aceita em NaN tentativas", código 1;
  - um diagrama a mais com JSON quebrado: depois de "[mapa] archify … pronto", "Expected double-quoted property name in JSON at position 45 (line 1 column 46)", código 1, sem o arquivo;
  - o `pecas.json` quebrado: "[mapa] ERRO app-resumo-sem pt — Expected double-quoted property name …", que culpa o diagrama, e não o arquivo do modelo.
- **Medido no commit `168d1827`, na G5** (o clone sem `site/.archify`, com o `fetch` por https recusado), linha a linha:
  - CT-49, gerador, `--saida` sem pasta: código 1 e "The "paths[0]" argument must be of type string. Received undefined", sem `[mapa]` nem o argumento; nada criado;
  - CT-49, posicionador, `--tentativas` sem número, `--tentativas muitas` e `--tentativas 0`: código 1 e a exceção do download do archify, sem tratamento (o stack do `archify.mjs`), com o `site/.archify` criado; o arquivo do diagrama com os mesmos bytes. O argumento nunca chega a ser lido: o posicionador obtém o archify antes;
  - CT-50, gerador, as três linhas: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", com o `site/.archify` criado; o arquivo quebrado nunca chega a ser lido;
  - CT-50, posicionador: código 1 e o `SyntaxError` do `JSON.parse`, sem o arquivo; nada criado, porque o posicionador lê o diagrama antes do archify;
  - nenhum HTML escrito em linha nenhuma.

```gherkin
  Regra: A ferramenta do mapa recusa o que não pode usar antes de qualquer efeito, e diz qual é o culpado

    Esquema do Cenário: [CT-49] o argumento sem valor válido, desconhecido, a mais ou ausente é recusado antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o argumento
      Dado o `node` do ambiente, lido pela versão, e um clone descartável do repositório no commit, sem `site/.archify` e com o download do archify bloqueado
      Quando o autor roda, de `site/` do clone, "<comando>"
      Então a ferramenta termina com código diferente de zero, com a mensagem `[mapa]` que nomeia <argumento> e diz <o que falta>
      E nada foi baixado nem gravado: o clone continua sem `site/.archify`, e os arquivos de diagrama do clone têm os mesmos bytes

      Exemplos:
        | comando                                                         | argumento      | o que falta                       | # partição                        |
        | `node mapa/gerar.mjs --saida`                                   | `--saida`      | o valor que ele espera            | gerador: pasta ausente            |
        | `node mapa/posicionar.mjs app-resumo-sem --tentativas`          | `--tentativas` | o valor que ele espera            | posicionador: número ausente      |
        | `node mapa/posicionar.mjs app-resumo-sem --tentativas muitas`   | `--tentativas` | o valor que ele espera            | posicionador: valor não numérico  |
        | `node mapa/posicionar.mjs app-resumo-sem --tentativas 0`        | `--tentativas` | o valor que ele espera            | posicionador: zero, abaixo do inteiro positivo (Q24) |
        | `node mapa/posicionar.mjs app-resumo-sem --tentativa 0`         | `--tentativa`  | que a opção não existe, e a que existe (`--tentativas`) | posicionador: opção desconhecida, com um slug real *(step 11, QA-04)* |
        | `node mapa/posicionar.mjs`                                      | o slug         | que ele falta, com o uso da ferramenta | posicionador: sem slug *(step 11, QA-04)* |
        | `node mapa/posicionar.mjs app-resumo-sem app-resumo-com`        | `app-resumo-com` | que o posicionador recebe um slug só | posicionador: um slug a mais *(step 11, QA-04)* |
        | `node mapa/posicionar.mjs app-resumo-sem --tentativas 5 --tentativas 0` | `--tentativas` | que ele aparece mais de uma vez, ou que o segundo valor, `0`, não é inteiro positivo | posicionador: opção repetida, com o segundo valor inválido *(step 11, QA-04)* |

    Esquema do Cenário: [CT-50] o arquivo de entrada que a ferramenta não pode usar — JSON quebrado, peça que não está no modelo, ou prova que não resolve — é recusado antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o culpado
      Dado o `node` do ambiente, lido pela versão, e um clone descartável do repositório no commit, sem `site/.archify` e com o download do archify bloqueado
      E, no clone, o arquivo "<arquivo>" <defeito>
      Quando o autor roda, de `site/` do clone, "<comando>"
      Então a ferramenta termina com código diferente de zero, com a mensagem `[mapa]` que nomeia <culpado>
      E nada foi baixado, gerado nem gravado: o clone continua sem `site/.archify`, nenhum HTML do mapa foi escrito, e a pasta de saída e os arquivos de entrada do clone — os de diagrama e os do modelo — têm os mesmos bytes de antes do comando

      Exemplos:
        | arquivo                                                         | defeito                                                                                     | comando                                                                        | culpado                    | # partição                                    |
        | `site/mapa/diagramas/zz-quebrado.json`, um diagrama a mais      | com JSON quebrado                                                                           | `node mapa/gerar.mjs --saida` com uma pasta temporária vazia e `app-resumo-sem` | o arquivo                  | gerador: diagrama quebrado, mesmo fora do pedido |
        | `site/mapa/pecas.json`                                          | com JSON quebrado                                                                           | `node mapa/gerar.mjs --saida` com uma pasta temporária vazia e `app-resumo-sem` | o arquivo                  | gerador: modelo de peças quebrado             |
        | `site/mapa/ligacoes.json`                                       | com JSON quebrado                                                                           | `node mapa/gerar.mjs --saida` com uma pasta temporária vazia e `app-resumo-sem` | o arquivo                  | gerador: modelo de ligações quebrado          |
        | `site/mapa/diagramas/app-resumo-sem.json`                       | com JSON quebrado                                                                           | `node mapa/posicionar.mjs app-resumo-sem`                                      | o arquivo                  | posicionador: diagrama quebrado               |
        | `site/mapa/diagramas/admin-acesso-e-autenticacao.json`          | com a peça `App\Support\PecaQueNaoExiste`, que não é peça do modelo, entre as peças desenhadas | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e o diagrama | gerador: peça fora do modelo *(re-revisão)* |
        | `site/mapa/diagramas/admin-resumo-com.json` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-resumo.json`)* | com `App\Support\PecaQueNaoExiste` como membro de um agrupamento                            | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o membro e o diagrama | gerador: membro de agrupamento fora do modelo *(re-revisão)* |
        | `site/mapa/diagramas/zz-quebrado.json`, um diagrama irmão do pedido | com JSON quebrado                                                                       | `node mapa/posicionar.mjs app-resumo-sem`                                      | o arquivo                  | posicionador: diagrama irmão quebrado *(re-revisão)* |
        | `site/mapa/diagramas/requisicao-autenticada.json`, um diagrama escrito à mão | com a `prova` da primeira mensagem trocada por um trecho que não está no arquivo que ela cita (`->id('financeiro')` em `app/Providers/Filament/AdminPanelProvider.php`) | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o trecho, o arquivo que ele cita e o diagrama | gerador: prova de ligação que não está no arquivo *(step 10)* |
        | `site/mapa/ligacoes.json`                                       | com a evidência da ligação "`AppServiceProvider` registra `BloquearSessaoComRetorno`", que o `kit-acesso-e-autenticacao` desenha, trocada por um trecho que só aparece num comentário de `app/Providers/AppServiceProvider.php` ("pelo mesmo caminho: o plugin registra") | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o arquivo que a evidência cita e o diagrama que desenha a ligação | gerador: evidência de ligação só em comentário *(step 10)* |
        | `site/mapa/diagramas/requisicao-autenticada.json`, um diagrama escrito à mão | com a primeira mensagem sem `prova`                                                     | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a ligação e o diagrama | gerador: ligação sem prova *(step 10)* |
        | `site/mapa/pecas.json`                                          | com a evidência do `UserResource` do `/admin` trocada por um trecho que não está no arquivo que ela cita (`class UserResource extends Model`) | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e o arquivo que a evidência cita | gerador: evidência de peça que não está no arquivo *(step 10)* |
        | `site/mapa/diagramas/app-resumo-sem.json`                       | com a peça `App\Support\PecaQueNaoExiste`, que não é peça do modelo, entre as peças desenhadas | `node mapa/posicionar.mjs app-resumo-sem`                                      | a peça e o diagrama        | posicionador: peça fora do modelo *(step 10)* |
        | `site/mapa/pecas.json`                                          | com JSON quebrado                                                                           | `node mapa/posicionar.mjs app-resumo-sem`                                      | o arquivo                  | posicionador: modelo quebrado *(step 10)* |
        | `site/mapa/ligacoes.json`                                       | com a evidência da ligação "o painel app registra `Dashboard`", que o `app-resumo-sem` desenha, trocada por um trecho que não está no arquivo que ela cita (`Dashboard::make(),` em `app/Providers/Filament/AppPanelProvider.php`) *(alterado em 2026-11-23 — step 11, Adendo 2: era a do `ConvitesRecebidos`, que só abre com tenant e saiu do `app-resumo-sem`)* | `node mapa/posicionar.mjs app-resumo-sem` | o arquivo que a evidência cita e o diagrama | posicionador: prova que não resolve *(step 10)* |
        | `site/mapa/pecas.json`                                          | com a condição do `TenantResource` do `/admin` no modo `Com`, que não é modo do mapa *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e o modo `Com` | gerador: condição com o modo fora do domínio |
        | `site/mapa/pecas.json`                                          | com a condição do `TenantResource` do `/admin` no modo `sem`, que não tem redação decidida *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e o modo `sem` | gerador: condição no modo sem tenant, sem redação decidida |
        | `site/mapa/pecas.json`                                          | com a chave da condição do `AgenteIaResource` trocada pelo número `123` *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a chave `123` | gerador: chave que não é variável `KIT_*` nem caminho de configuração |
        | `site/mapa/pecas.json`                                          | com a configuração `command-center.sources` da condição do `CommandRecordResource` com a `ligada` igual ao `padrao`, sem fonte nova *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a configuração `command-center.sources` | gerador: configuração de pacote que não liga fonte nova |
        | `site/mapa/pecas.json`                                          | com a condição do `AgenteIaResource` sem a âncora (`evidencia`) *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a âncora da condição, que falta | gerador: condição sem âncora |
        | `site/mapa/ligacoes.json`                                       | com a ligação "o painel admin registra `App\Support\PecaQueNaoExiste`", que não é peça do modelo, com a prova da ligação "o painel admin registra `FilamentShieldPlugin`" *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a ligação e a peça `App\Support\PecaQueNaoExiste`, que não está no modelo | gerador: ligação de registro com a ponta fora do modelo |
        | `site/mapa/pecas.json`                                          | com o modo da condição do `TenantResource` do `/admin` escrito como lista, `["com"]` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e o modo `["com"]`, que não é texto | gerador: modo que não é texto |
        | `site/mapa/pecas.json`                                          | com a `ligada` da configuração `command-center.sources` da condição do `CommandRecordResource` trocada por `ConfigSource`, a de fábrica, e uma `ConfigSource` de outro namespace (`Outro\Pacote\ConfigSource`) *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a configuração `command-center.sources` | gerador: fonte "nova" que só muda o namespace |
        | `site/mapa/pecas.json`                                          | com a `ligada` da configuração `command-center.sources` da condição do `CommandRecordResource` trocada por `ConfigSource` e a mesma classe escrita com `::class` (`Bityukov\CommandCenter\Sources\ConfigSource::class`) *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a configuração `command-center.sources` | gerador: fonte "nova" que só ganha `::class` |
        | `site/mapa/pecas.json`                                          | com a chave da condição do `AgenteIaResource` trocada por `APP_DEBUG`, variável em maiúsculas fora de `KIT_*` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | a peça e a chave `APP_DEBUG` | gerador: variável em maiúsculas fora de `KIT_*` |
        | `site/mapa/diagramas/infra-registro.json`                       | com `modos` vazio, `[]` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-03)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o arquivo do diagrama e os `modos` | gerador: diagrama que não vale em modo nenhum |
        | `site/mapa/diagramas/infra-registro.json`                       | com `modos` repetido, `["sem", "sem"]` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-03)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o arquivo do diagrama e os `modos` | gerador: modo repetido |
        | `site/mapa/diagramas/admin-resumo-com.json`, um mapa de arquitetura | com `modos` fora de `sem`/`com`, `["Com"]` *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-03)* | `node mapa/gerar.mjs --saida` com uma pasta que já tem `pt/` e `en/` de uma geração anterior, sem slug | o arquivo do diagrama e o modo `Com` | gerador: modo do diagrama fora do domínio, num mapa |
```

*(alterado em 2026-11-21 — step 10: a P-14 sobre a projeção)* A reconciliação achou mais uma lacuna da P-14, que nenhuma revisão apontou: a projeção — resolver a prova de cada ligação e de cada peça na revisão publicada, e conferir o mapa contra o modelo — fica depois do archify nas duas ferramentas. O gerador obtém o archify (`site/mapa/gerar.mjs`, `obterArchify`, no commit `563cda13`) e, sem slug, apaga `pt/` e `en/` da pasta de saída antes do laço de render, e só nele resolve a prova (`site/mapa/gerar.mjs`, `linhaDaAncora` e `provasDasLigacoes`, no commit `563cda13`); o posicionador obtém o archify (`site/mapa/posicionar.mjs`, `obterArchify`, no commit `563cda13`) antes de montar a entrada, e é ela que confere as peças contra o modelo, lê o modelo e resolve as provas. O layout, que só o archify confere ao renderizar, fica fora: a sessão registra a exceção na P-14. O título do CT-50 passa a nomear a prova que não resolve, e o último passo, os arquivos de entrada do modelo ao lado dos de diagrama; as linhas anteriores não mudam.
- **As partições, uma por classe e por ferramenta (EP).** No gerador, o defeito da projeção tem três classes — o trecho que não está no arquivo, o trecho que só aparece em comentário e a ligação sem `prova` —, a âncora tem dois destinos — o item de prova da ligação e a fonte da peça, que vira o passaporte —, e a origem dela, dois lugares — o diagrama escrito à mão e o modelo. As quatro linhas cobrem cada valor ao menos uma vez: a prova de ligação de um diagrama escrito à mão que não está no arquivo; a evidência de ligação do modelo só em comentário; a ligação de diagrama escrito à mão sem `prova`; e a evidência de peça do modelo que não está no arquivo. No posicionador, três classes: a peça fora do modelo, o modelo com JSON quebrado (o `pecas.json` e o `ligacoes.json` são a mesma classe, a mesma leitura) e a prova que não resolve. O membro de agrupamento fora do modelo fica na mesma classe da peça no posicionador.
- **A pasta e os arquivos de entrada.** As linhas do gerador usam a pasta de uma geração anterior, porque a remoção de `pt/` e `en/` é o efeito que a projeção tardia deixa passar; as do posicionador conferem os bytes dos arquivos de entrada, que ele regrava quando aceita uma grade.
- **Medido no HEAD `563cda13`, na G5** (o clone sem `site/.archify`, com o `fetch` por https recusado), linha a linha:
  - as quatro linhas do gerador reprovam: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", que não nomeia o trecho, a ligação, a peça nem o arquivo, com o `site/.archify` criado; a pasta de saída e os arquivos de entrada ficam com os mesmos bytes só porque o download falha antes de o gerador apagar a pasta;
  - as três linhas do posicionador reprovam: código 1 e a exceção do download do archify, sem tratamento (`Error: [mapa] ERRO archify — …`, com o stack), com o `site/.archify` criado; o arquivo do diagrama pedido fica com os mesmos bytes.
- **Com o archify à mão** (fora da G5, pelo código, não medido): o gerador apaga `pt/` e `en/` e só então sai com "[mapa] ERRO <slug> <idioma> — o trecho … não está em <arquivo> …" — ou "só aparece em comentário", ou "a ligação … não tem prova (P-04)" —; e o posicionador sai com o erro da peça, do `JSON.parse` do modelo ou do trecho, depois do download.

*(alterado em 2026-11-21 — step 11, QA-04)* **O CT-49 ganha a opção desconhecida, o slug ausente, o slug a mais e a opção repetida.** O posicionador confere só o `--tentativas`, achado por posição na lista de argumentos: a opção que ele não conhece, o segundo slug e a segunda ocorrência de `--tentativas` passam sem ser vistos, e ele assume o padrão de 200 tentativas; e o erro de uso, sem slug, sai sem o prefixo `[mapa]`. O gerador já recusa o argumento que não conhece, como "diagrama inexistente", nomeado. A coluna `o que falta` é nova; as quatro linhas anteriores ficam como estavam.
- **As partições.** EP no nome da opção — conhecida ou não — e BVA na cardinalidade do slug (0, 1, 2) e na do `--tentativas` (0, 1, 2). O slug 0 é a linha sem slug, e o 2, a do slug a mais; o `--tentativas` 2 é a linha da opção repetida, com o segundo valor inválido — com o primeiro inválido, a conferência de hoje já recusa pela primeira ocorrência, e a linha não discriminaria. O `--tentativas` 0 é o padrão, lado válido fora do teste rápido; o 1 está nas linhas anteriores.
- **Medido no HEAD `a624bff1`, na G5** (o clone sem `site/.archify`, com o `fetch` por https recusado):
  - o controle, `--tentativas 0`, passa: código 2 e `[mapa] ERRO --tentativas: espera um inteiro positivo, e recebeu "0"`, sem nada criado;
  - a opção desconhecida reprova: código 1 e a exceção do download do archify, sem tratamento, com o `site/.archify` criado — a opção não é vista, e vale o padrão; com um slug que não existe (`nao-existe --tentativa 0`), a saída é `[mapa] ERRO site/mapa/diagramas/nao-existe.json — o diagrama não existe`, código 2, e a opção continua sem ser vista;
  - a linha sem slug reprova: código 2 e `uso: node mapa/posicionar.mjs <slug> [--tentativas 200]`, sem o prefixo `[mapa]`; nada criado;
  - a do slug a mais e a da opção repetida reprovam: código 1 e a exceção do download, com o `site/.archify` criado; a opção repetida com o primeiro valor inválido (`--tentativas 0 --tentativas 5`) sai com a recusa `[mapa]` do `0`, código 2;
  - em todas, os arquivos de diagrama do clone ficam com os mesmos bytes.
- **Com o archify à mão** (pelo código, não executado): a opção desconhecida, o slug a mais e a opção repetida seguem com 200 ou 5 tentativas, obtêm o archify e gravam o layout do primeiro slug no arquivo dele.

*(alterado em 2026-11-21 — step 9, re-revisão: RR-01 e RR-02)* O CT-50 passa a cobrir o arquivo de entrada que a ferramenta não pode usar, e não só o JSON quebrado: as colunas `defeito` e `culpado` são novas, e as quatro linhas anteriores ficam como estavam.
- **RR-01.** O gerador recusa a peça e o membro de agrupamento fora do modelo só dentro do laço de render (`site/mapa/gerar.mjs`, `mapaDeArquitetura`, no commit `bc27379f`), depois de obter o archify e, sem slug, de apagar `pt/` e `en/` da pasta de saída (`site/mapa/gerar.mjs`, `gerar`, no commit `bc27379f`). As duas linhas novas pedem a recusa antes de qualquer efeito, com a pasta de saída de uma geração anterior intacta; as duas partições são separadas porque a peça e o membro têm conferências separadas.
- **RR-02.** O posicionador lê os outros arquivos de diagrama — para saber se o mapa é base ou cabeça de um comparativo — com `JSON.parse` cru, depois de obter o archify (`site/mapa/posicionar.mjs`, `doComparativo`, no commit `bc27379f`). A linha nova pede a recusa que nomeia o arquivo irmão, antes de qualquer efeito.
- **Medido no HEAD `bc27379f`, na G5** (o clone sem `site/.archify`, com o `fetch` por https recusado):
  - as quatro linhas anteriores passam: código 1 (2 no posicionador) e "[mapa] ERRO site/mapa/… — JSON inválido: …", que nomeia o arquivo, sem `site/.archify` criado e com a pasta de saída intacta;
  - as duas linhas do RR-01 reprovam: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", que não nomeia a peça nem o membro, com o `site/.archify` criado; a pasta de saída fica intacta só porque o download falha antes de o gerador apagá-la;
  - a linha do RR-02 reprova: código 1 e a exceção do download do archify, sem tratamento, com o `site/.archify` criado.
- **Medido no HEAD `bc27379f`, com o archify à mão** (o mesmo clone, com `site/.archify` ligado ao da árvore, fora da G5, só para expor o efeito que o download bloqueado mascara): a peça fora do modelo sai com "[mapa] ERRO admin-acesso-e-autenticacao pt — a peça App\Support\PecaQueNaoExiste do diagrama não está no modelo", código 1, mas o `pt/` e o `en/` da pasta de saída já foram apagados; o diagrama irmão quebrado sai com o `SyntaxError` do `JSON.parse`, sem o arquivo.

*(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* **A condição de disponibilidade do modelo é arquivo de entrada.** O gerador usa o campo `disponibilidade` de cada peça sem conferir a forma dele (`site/mapa/gerar.mjs`, `marcaDaCondicao`, `entraNosModos` e `cardDeCondicoes`, no commit `1dd48acd`), e o conferidor o confere e falha fechado (`site/verifica-mapa.mjs`, `condicaoDaPeca`). Pela P-14, o gerador recusa, antes de qualquer efeito e com a mensagem `[mapa]` que nomeia a peça, a condição que não pode usar — e a ligação de registro cuja ponta não é peça do modelo, que antes do Adendo o fazia estourar e hoje some calada.
- **As partições, uma inválida por linha (EP)**, uma por repro da revisão: o modo fora do domínio (`Com`); o modo `sem` — a D41 só decidiu o "só com tenant ativo" / "tenancy on only", e a sessão decidiu que o gerador recusa o `sem`, como o conferidor e o oráculo Pest já o tratam; a chave que não é variável `KIT_*` nem caminho de configuração (`123`); a configuração de pacote cuja `ligada` não acrescenta fonte ao `padrao` — decidido pela sessão: ela liga ao menos uma fonte nova, senão a marca sai vazia; a condição sem âncora; e a ligação de registro com a ponta fora do modelo. Escolha desta derivação: a peça de cada linha é uma que o modelo de hoje já marca, para a cópia mudar um campo só.
- **Medido no HEAD `1dd48acd`, na G5** (o clone `--shared` sem `site/.archify`, com o `fetch` por https recusado, e a pasta de saída com o `pt/` e o `en/` de uma geração anterior), linha a linha:
  - o modo `Com`, o modo `sem`, a chave `123`, a `ligada` igual ao `padrao` e a ligação com a ponta fora do modelo: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", que não nomeia a peça nem a ligação, com o `site/.archify` criado; a pasta de saída fica intacta só porque o download falha antes de o gerador apagá-la — vermelho da implementação (causa b);
  - a condição sem âncora: código 1 e "[mapa] ERRO admin-administracao pt — Cannot destructure property 'arquivo' of 'peca.disponibilidade.evidencia' as it is undefined.", sem `site/.archify` e com a pasta intacta: a mensagem nomeia o diagrama, e não a peça nem a âncora que falta — vermelho da implementação (causa b).
- **Medido no HEAD `1dd48acd`, pela forma de leitura** (a entrada que o gerador monta, com a cópia do modelo no clone, sem o archify): com o modo `Com`, o `TenantResource` some do `admin-registro-com`, do `admin-registro-sem` e do `admin-resumo-com`, calado; com o modo `sem`, ele entra no `admin-registro-sem` com "só sem tenant" / "tenancy off only"; a chave `123` sai "123 sem  de fábrica" / "123 without  by default"; a `ligada` igual ao `padrao`, "command-center.sources sem  de fábrica"; e a ligação com a ponta fora do modelo some das duas árvores de registro do `/admin`, calada.
- **O lado válido não ganha linha no CT-50**: ela baixaria o archify. Quem mata a recusa a mais — a validação nova que recusa a condição válida, como a lista vazia de fábrica do chat (`padrao: []`) ou a `ligada` do `CommandRecordResource`, que acrescenta a `DatabaseSource` — é a linha "publicados" do CT-57: ela monta a entrada de todo diagrama em pt e em en, e a entrada que volta com o erro do gerador reprova (M25).

*(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achados AD2-01 e AD2-03)* **A condição e os modos que a recusa do A1 deixou passar.** O gerador recusa hoje, ao carregar o modelo, a condição que não se lê (`site/mapa/gerar.mjs`, `problemasDaCondicao`, no commit `b130c686`), mas três formas ainda passam, e o arquivo de diagrama não tem conferência nenhuma do campo `modos`:
- **O modo que não é texto (AD2-01 a).** A conferência pergunta se a tabela da marca tem a chave (`Object.hasOwn`), que converte a lista `["com"]` no texto "com" e a aceita; depois, o lugar compara o modo por igualdade, e a peça sai de todo diagrama, calada.
- **A fonte "nova" que é a de fábrica (AD2-01 b).** A conferência da configuração de pacote compara a `ligada` com o `padrao` pelo valor inteiro, e a marca, pelo nome curto da classe (`marcaDaCondicao`): a `ConfigSource` de outro namespace, ou com `::class`, passa como fonte nova, e a marca sai sem a fonte que falta. A comparação decidida é pelo nome curto, sem namespace e sem `::class` — o que a marca mostra. Duas linhas, porque são duas normalizações: a correção que tira o namespace e esquece o `::class` passa na primeira e cai na segunda. Cada linha muda só a `ligada`, e mantém a fonte de fábrica nela.
- **A chave em maiúsculas fora de `KIT_*` (AD2-01 c).** A conferência toma por variável do kit qualquer nome em maiúsculas (`eVariavelDoKit`), e o `APP_DEBUG` vira marca "APP_DEBUG=false de fábrica". A chave do kit é a variável `KIT_*`, ou o caminho de configuração (a redação decidida da marca, R42 e R43).
- **Os modos do arquivo de diagrama (AD2-03).** O campo `modos` é lista não vazia, sem repetição, de `sem` e `com` (P-09: o diagrama vale num modo, ou nos dois). O termo da raiz decide pelo tamanho da lista (`termoDosModos`): `["sem", "sem"]` sai "os dois modos" / "both modes", e `[]` e `["Com"]`, "sem tenant" / "tenancy off". Três partições, uma por linha: vazio, repetido e fora do domínio; a de fora vai num mapa de arquitetura, e não na árvore, porque a conferência que morasse só no termo da raiz deixaria o mapa com `["Com"]` sair sem peça nenhuma, calado — o lugar filtra pelo modo `Com`, que nenhuma peça tem.
- **O lado válido.** Os 95 arquivos de diagrama publicados têm `modos` válido — 28 com `["sem"]` ou `["com"]` e 67 com `["sem", "com"]`, inclusive os dois comparativos —, e as 18 condições do modelo usam só `KIT_*` e a `command-center.sources` com a `DatabaseSource` ligada além da `ConfigSource` de fábrica. A recusa a mais morre na linha "publicados" do CT-57 (M25), que monta a entrada de todo diagrama fora o comparativo, e na linha do CT-19 com o `infra-registro` em `["com", "sem"]`, que tem de continuar aceita. O comparativo não entra na forma de leitura (`entradasDoGeradorDoMapa()` o pula); a recusa a mais nele só apareceria no build, e o CT-B01 o abre.
- **O lado do conferidor (AD2-01).** O conferidor lê a condição pela própria função (`site/verifica-mapa.mjs`, `condicaoDaPeca`), e falha fechado na condição que não se lê: ela tem de recusar as mesmas formas. Ele importa o Playwright e só roda no job `site` (CT-B08), e a forma de leitura da G5 só importa o gerador: por isso as linhas dele moram no CT-B08, no `05`, como controle sintético decidido pela mesma função que lê o modelo real, e não no CT-57.
- **Medido no HEAD `b130c686`, na G5** (o clone `--shared` sem `site/.archify`, com o download bloqueado por `GIT_ALLOW_PROTOCOL=file`, e a pasta de saída com o `pt/` e o `en/` de uma geração anterior), as sete linhas novas: código 1 e "[mapa] ERRO archify — fatal: transport 'https' not allowed …", que não nomeia a peça nem o diagrama, com o `site/.archify` criado; a pasta de saída fica intacta só porque o download falha antes de o gerador apagá-la — vermelho da implementação (causa b).
- **Medido no HEAD `b130c686`, pela forma de leitura** (a entrada que o gerador monta com a cópia do modelo ou do diagrama no clone, sem o archify): com o modo `["com"]`, o `TenantResource` some do `admin-registro-com` e do `admin-resumo-com`, calado; com a `ConfigSource` de outro namespace, a marca do `CommandRecordResource` sai "command-center.sources sem  de fábrica" / "command-center.sources without  by default"; com o `::class`, "command-center.sources sem ConfigSource::class de fábrica" — a fonte de fábrica dada como a que falta; com o `APP_DEBUG`, "APP_DEBUG=false de fábrica" / "APP_DEBUG=false by default"; e a raiz do `infra-registro` diz "os dois modos" / "both modes" com `["sem", "sem"]` (e com `["com", "com"]`), e "sem tenant" / "tenancy off" com `[]` e com `["Com"]`; e o mapa `admin-resumo-com` com `["Com"]` sai sem caixa nem seta (com `["com"]`, 11 caixas e 36 setas).

- **Hoje** todas as linhas do CT-49 e do CT-50 reprovam pela mensagem — nenhuma nomeia o culpado com `[mapa]` —, e as do posicionador com `--tentativas` e as três do gerador no CT-50 também pela pasta do archify criada.
- **Fora do teste rápido.** O lado válido de `--tentativas` (um inteiro positivo) e as entradas corretas fazem a ferramenta baixar o archify e renderizar ou validar, e ficam fora da G5; a recusa de um valor válido não é mutante plausível desta regra, e o uso normal do autor a exibiria.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 — step 9 (A6) | `--tentativas` sem número vira `Number(undefined)`, NaN; a busca roda zero vezes, e a ferramenta sai com "nenhuma grade aceita em NaN tentativas" ou grava a grade inicial sem busca | CT-49 | linhas do posicionador: a mensagem não nomeia `--tentativas` (no clone, a de hoje é a exceção do download) e, com o archify à mão, o arquivo do diagrama pode mudar de bytes; reprova; o mutante aceita |
| M2 — step 9 (A6) | o `--saida` sem pasta cai num erro do Node, sem o prefixo `[mapa]` nem o argumento | CT-49 | linha do gerador: a mensagem é "The "paths[0]" argument must be of type string…", e não nomeia `--saida`; reprova; o mutante aceita |
| M3 — step 9 (A11) | o JSON quebrado reprova com a mensagem do `JSON.parse`, sem o arquivo nem o `[mapa]` | CT-50, CT-B08 | CT-50, linha do posicionador (o `SyntaxError` sem o arquivo) e linha do diagrama a mais; CT-B08, a do conferidor: a mensagem não nomeia o arquivo; reprova; o mutante aceita |
| M4 — step 9 (A11) | com o modelo quebrado, a mensagem culpa o diagrama que estava sendo gerado, e não o arquivo do modelo | CT-50 | linhas do `pecas.json` e do `ligacoes.json`: "a mensagem `[mapa]` que nomeia o arquivo" — o mutante nomeia `app-resumo-sem` (medido com o archify à mão); reprova; o mutante aceita |
| M5 — step 9 (P-14) | a ferramenta confere o argumento e lê os arquivos de entrada só depois de baixar o archify, e o erro dela custa a rede e o tempo do download | CT-49, CT-50 | "o clone continua sem `site/.archify`": a tentativa cria a pasta (medido nas linhas do posicionador com `--tentativas` e nas três do gerador no CT-50), e reprova; o mutante aceita |
| M6 — step 9 (A6; Q24) | `--tentativas 0` é aceito: a busca roda zero vezes, e a ferramenta sai com "nenhuma grade aceita em 0 tentativas" ou grava a grade inicial sem busca | CT-49 | linha do zero: a mensagem não nomeia `--tentativas` e o valor que ele espera; reprova; o mutante aceita |
| M7 — step 9 (re-revisão, RR-01) | o gerador confere as peças e os membros de agrupamento dos diagramas construídos contra o modelo só no laço de render, depois de obter o archify e de apagar `pt/` e `en/` da pasta de saída | CT-50 | linhas da peça e do membro fora do modelo: no clone, a mensagem é a do archify, que não os nomeia, e o `site/.archify` fica criado; com o archify à mão, a pasta de saída perde o `pt/` e o `en/`; reprova; o mutante aceita |
| M8 — step 9 (re-revisão, RR-02) | o posicionador lê os outros arquivos de diagrama com `JSON.parse` cru, depois de obter o archify, e o diagrama irmão quebrado reprova sem o nome dele | CT-50 | linha do diagrama irmão quebrado: a mensagem não nomeia `zz-quebrado.json` (no clone, a exceção do download; com o archify à mão, o `SyntaxError` cru) e o `site/.archify` fica criado; reprova; o mutante aceita |
| M9 — step 10 (P-14 sobre a projeção) | o gerador resolve a prova de cada ligação e de cada peça só no laço de render, depois de obter o archify e, sem slug, de apagar `pt/` e `en/` da pasta de saída | CT-50 | as quatro linhas do gerador do step 10: no clone, a mensagem é a do archify, que não nomeia o culpado, e o `site/.archify` fica criado; com o archify à mão, a pasta de saída perde o `pt/` e o `en/` antes do erro; reprova; o mutante aceita |
| M10 — step 10 (P-14 sobre a projeção) | a conferência antecipada do gerador procura o trecho no texto cru, e o trecho que só aparece em comentário passa por ela e só cai na projeção | CT-50 | linha da evidência de ligação só em comentário: a recusa antes do efeito não vem, e a ferramenta segue para o archify; reprova; o mutante aceita |
| M11 — step 10 (P-14 sobre a projeção) | a conferência antecipada do gerador resolve só a prova das ligações, e a evidência da peça — a fonte do passaporte — fica para a projeção | CT-50 | linha da evidência de peça que não está no arquivo: a recusa antes do efeito não vem; reprova; o mutante aceita |
| M12 — step 10 (P-14 sobre a projeção) | a conferência antecipada do gerador resolve as provas que existem, e a ligação de diagrama escrito à mão sem `prova` só é recusada na projeção | CT-50 | linha da ligação sem prova: a recusa antes do efeito não vem; reprova; o mutante aceita |
| M13 — step 10 (P-14 sobre a projeção) | o posicionador obtém o archify antes de montar a entrada — e é ela que confere as peças contra o modelo, lê o modelo e resolve as provas | CT-50 | as três linhas do posicionador do step 10: no clone, a exceção do download, sem nomear o culpado, e o `site/.archify` criado; reprova; o mutante aceita |
| M14 — step 10 (P-14 sobre a projeção) | o posicionador antecipa a leitura do modelo e a conferência das peças, mas resolve as provas só ao montar a entrada, depois do archify | CT-50 | linha da prova que não resolve no `app-resumo-sem`: a recusa antes do efeito não vem; reprova; o mutante aceita |
| M15 — step 11 (QA-04) | o posicionador confere só o `--tentativas`, e a opção que ele não conhece passa calada: vale o padrão de 200 tentativas | CT-49 | linha do `--tentativa 0`: a mensagem não nomeia `--tentativa`, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M16 — step 11 (QA-04) | o erro de uso do posicionador, sem slug, sai sem o prefixo `[mapa]` | CT-49 | linha sem slug: a saída é `uso: …`, sem `[mapa]`; reprova; o mutante aceita |
| M17 — step 11 (QA-04) | o posicionador recusa a opção desconhecida pelo prefixo `--`, e o slug a mais, sem prefixo, passa calado | CT-49 | linha do slug a mais: a mensagem não nomeia `app-resumo-com`, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M18 — step 11 (QA-04) | o posicionador confere só a primeira ocorrência de `--tentativas`, e a segunda, inválida, é ignorada | CT-49 | linha da opção repetida (`--tentativas 5 --tentativas 0`): a mensagem não nomeia `--tentativas`, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M19 — step 9 do Adendo (A1) | o gerador compara o modo da condição com o de cada diagrama sem conferir o domínio, e a peça de modo `Com` sai de todo diagrama, calada | CT-50 | linha do modo `Com`: a mensagem é a do archify, que não nomeia o `TenantResource`, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M20 — step 9 do Adendo (A1) | o gerador aceita o modo `sem`, que tem texto na tabela dele, e publica "só sem tenant" / "tenancy off only", redação que ninguém decidiu | CT-50 | linha do modo `sem`: a recusa antes do efeito não vem; reprova; o mutante aceita |
| M21 — step 9 do Adendo (A1) | o gerador trata a chave que não é variável `KIT_*` como configuração de pacote, e a marca sai "123 sem  de fábrica" | CT-50 | linha da chave `123`: a recusa que nomeia a peça e a chave não vem; reprova; o mutante aceita |
| M22 — step 9 do Adendo (A1) | o gerador aceita a configuração de pacote cuja `ligada` não acrescenta fonte ao `padrao`, e a marca sai sem a fonte que falta: "command-center.sources sem  de fábrica" | CT-50 | linha da `ligada` igual ao `padrao`: a recusa que nomeia a peça e `command-center.sources` não vem; reprova; o mutante aceita |
| M23 — step 9 do Adendo (A1) | o gerador lê a âncora da condição sem conferir que ela existe, e o `TypeError` nomeia o diagrama, e não a peça nem a âncora | CT-50 | linha da condição sem âncora: a linha `[mapa]` nomeia o `admin-administracao`, e não o `AgenteIaResource`; reprova; o mutante aceita |
| M24 — step 9 do Adendo (A1) | o gerador tira da árvore de registro a ligação cuja ponta não acha no modelo, calado, em vez de recusá-la | CT-50 | linha da ligação para `App\Support\PecaQueNaoExiste`: a mensagem é a do archify, que não nomeia a ligação, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M25 — step 9 do Adendo (A1) | a validação nova recusa a condição válida — a lista vazia de fábrica (`padrao: []`) do chat, ou a `ligada` que acrescenta a `DatabaseSource` —, e o gerador recusa o mapa publicado | CT-57 | linha "publicados": a entrada do `kit-ia` (o chat) e a do `infra-infraestrutura-2` (o `CommandRecordResource`) voltam com o erro do gerador, e a guarda reprova ("o gerador recusou o diagrama"); o mutante faz a linha, que espera "aceita", reprovar |
| M26 — step 9 do Adendo, rodada 2 (AD2-01) | o gerador confere o modo da condição pela chave da tabela da marca (`Object.hasOwn`), que aceita a lista `["com"]`, e o lugar, que compara por igualdade, tira a peça de todo diagrama, calado — o caso de hoje | CT-50 | linha do modo `["com"]`: a mensagem é a do archify, que não nomeia o `TenantResource`, e o `site/.archify` fica criado; reprova; o mutante aceita |
| M27 — step 9 do Adendo, rodada 2 (AD2-01) | o gerador compara a `ligada` com o `padrao` pelo valor inteiro, e a marca, pelo nome curto: a `ConfigSource` de outro namespace passa como fonte nova, e a marca sai "command-center.sources sem  de fábrica" — o caso de hoje | CT-50 | linha da `ConfigSource` de outro namespace: a recusa que nomeia a peça e `command-center.sources` não vem; reprova; o mutante aceita |
| M28 — step 9 do Adendo, rodada 2 (AD2-01) | a correção compara pelo nome curto, mas só tira o namespace, e a mesma classe com `::class` passa como fonte nova: a marca diz que falta a `ConfigSource::class`, que é a de fábrica | CT-50 | linha da `ConfigSource::class`: a recusa não vem; reprova; o mutante aceita |
| M29 — step 9 do Adendo, rodada 2 (AD2-01) | o gerador toma por variável do kit qualquer nome em maiúsculas, e a chave `APP_DEBUG` vira marca "APP_DEBUG=false de fábrica" — o caso de hoje | CT-50 | linha do `APP_DEBUG`: a recusa que nomeia a peça e a chave não vem; reprova; o mutante aceita |
| M30 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor aceita a configuração de pacote com a `ligada` não vazia, sem conferir que ela traz fonte nova pelo nome curto, e espera a marca sem a fonte que falta — o caso de hoje | CT-B08 | linhas de controle da `ConfigSource` de outro namespace e da `ConfigSource::class`: a função que lê o modelo real as aceita; reprova; o mutante aceita |
| M31 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor toma por variável do kit qualquer nome em maiúsculas, e espera a marca "APP_DEBUG=false de fábrica" — o caso de hoje | CT-B08 | linha de controle do `APP_DEBUG`: a função a aceita; reprova; o mutante aceita |
| M32 — step 9 do Adendo, rodada 2 (AD2-01) | o conferidor, alinhado ao gerador, troca a conferência do tipo do modo pela chave da tabela da marca, e passa a aceitar o modo `["com"]` | CT-B08 | linha de controle do modo `["com"]`: a função a aceita; reprova; o mutante aceita |
| M33 — step 9 do Adendo, rodada 2 (AD2-03) | o termo da raiz decide pelo tamanho da lista, e a árvore de `modos` repetido, `["sem", "sem"]`, sai "os dois modos" — o caso de hoje | CT-50 | linha do `infra-registro` com `["sem", "sem"]`: a recusa que nomeia o arquivo e os `modos` não vem; reprova; o mutante aceita |
| M34 — step 9 do Adendo, rodada 2 (AD2-03) | o gerador aceita o diagrama de `modos` vazio, que vale em modo nenhum: a raiz sai "sem tenant", e o lugar toma toda peça, porque "em cada modo" de uma lista vazia é sempre verdade — o caso de hoje | CT-50 | linha do `infra-registro` com `[]`: a recusa não vem; reprova; o mutante aceita |
| M35 — step 9 do Adendo, rodada 2 (AD2-03) | a conferência dos `modos` mora só no termo da raiz da árvore, e o mapa de arquitetura com `["Com"]` sai sem peça nenhuma, calado | CT-50 | linha do `admin-resumo-com` com `["Com"]`: a recusa que nomeia o arquivo e o modo `Com` não vem; reprova; o mutante aceita |

---

## Regra R41 — A peça condicional só entra nos diagramas que valem só nos modos em que ela fica disponível — com a chave ligada (P-15) —, e nunca num diagrama compartilhado se fica indisponível num dos modos; o `/admin` tem diagramas separados sem e com tenant, e o `TenantResource` só está nos de com

> `RQ-28`, `RQ-29`, `P-15`, `RQ-11`, `P-09`, `P-10` · perfil **padrão** · técnica: **tabela de decisão** (condição da peça: nenhuma, só pela chave, pelo modo, pelo modo e pela chave × modos do diagrama: só sem, só com, os dois) + controles em cópia *(alterado em 2026-11-23 — Adendo 2: regra nova; o CT-33 é o ID reservado na R30, que juntava a marca: a marca foi para a R42)*

| Condição da peça × diagrama que vale… | só sem tenant | só com tenant | nos dois modos |
|---|---|---|---|
| nenhuma | entra | entra | entra |
| só pela chave (P-15) | entra, com a marca | entra, com a marca | entra, com a marca |
| pelo modo: só com tenant | **não entra** | entra, com a marca | **não entra** |
| pelo modo e pela chave | **não entra** | entra, com a marca | **não entra** |

"Entrar" segue a definição do Setup Global: como nó, como membro de agrupamento ou de porta, na árvore de registro, ou como ponta de item de prova. A marca é da R42; aqui só o lugar. A linha "só sem tenant" não tem peça no HEAD (medido), e a tabela a trata como o espelho da de com. *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* A condição "só sem tenant" não tem redação decidida — a D41 só decidiu o "só com tenant ativo" / "tenancy on only" —, e o gerador a recusa (R40, CT-50), como o conferidor e o oráculo Pest: o espelho vale para o lugar quando a sessão decidir a redação dela.

**A RQ-29.** "O `/admin` ganha diagramas separados sem e com tenant" se lê, com a P-09 no que ela não foi substituída ("uma visualização que é igual nos dois modos é publicada uma vez"), como: o `/admin` tem ao menos um diagrama que vale só sem tenant e um que vale só com tenant, e o diagrama que desenha o `TenantResource` vale só com tenant. Partir todo diagrama do `/admin` em dois, inclusive o que é igual nos dois modos, publicaria o igual duplicado, que a R18 reprova (CT-19).

**De onde sai o "fica disponível".** No modo sem tenant, do discriminante vivo, na suíte do kit, que roda sem tenant: a peça desenhada num diagrama que vale sem tenant tem de abrir sem tenant, de fábrica ou com uma chave booleana de `kit.*` desligada ligada (nunca `kit.tenancy.enabled`, que é o modo *(alterado em 2026-11-23 — Adendo 4)*; e, pela saída "do lugar" do contrato, também com as chaves e a configuração que a condição do modelo nomeia, juntas). No modo com tenant, da condição do modelo, que o CT-56 confere contra o código na suíte `Tenancy`. Assim o caso não depende do modelo para pegar o defeito de hoje, que é todo do lado sem tenant.

*(alterado em 2026-11-23 — Adendo 3)* **A peça que herda e a configuração de pacote.** A metade sem tenant avalia também a peça do modelo que painel nenhum registra e tem `canAccess()` ou `canView()` próprio — o `AcessosPorPainel`, que herda a condição das telas de organizações (RQ-31) e por isso fica como "pelo modo: só com tenant" na tabela —, e liga, além das booleanas de `kit.*`, a configuração de pacote que a condição do modelo nomeia: o `CommandRecordResource` abre sem tenant com a `DatabaseSource` em `command-center.sources`, e por isso fica como "só pela chave", nos dois modos (RQ-30). O modelo só propõe a configuração; a peça que não abre com ela continua barrada.

*(alterado em 2026-11-23 — Adendo 4)* **O componente flutuante.** A metade sem tenant avalia também o chat do assistente e o checklist do tutorial, pelo predicado do mapa fechado do Setup Global, com as chaves da condição ligadas **juntas**. Ligadas uma de cada vez, o chat ficaria fechado em todo modo, e a guarda o barraria do resumo sem tenant, onde ele tem de estar. Medido: os dois estão hoje nos diagramas dos dois modos, e é onde ficam; a linha "publicados" não reprova por eles depois que o modelo tiver a condição. Hoje, sem a condição no modelo, a metade viva não tem as chaves do chat para ligar juntas, e o barra nos 4 lugares em que ele está num diagrama que vale sem tenant — o nó do `kit-ia`, o membro do `kit-resumo-sem` e os nós do `app-chat-de-ia` e do `ia-do-prompt-ao-ledger`, escritos à mão —: o vermelho de hoje ganha esses 4. O checklist abre com `KIT_ONBOARDING` sozinha, e não entra nele.

```gherkin
  Regra: A peça condicional só entra nos diagramas do modo em que fica disponível

    Esquema do Cenário: [CT-33] nenhum diagrama desenha a peça condicional num modo em que ela fica indisponível, e o /admin tem diagramas separados sem e com tenant, com o TenantResource só nos de com
      Dado o modo multi-tenant desligado, com `kit.tenancy.enabled` lido como falso, toda chave booleana de `kit.*` lida com o valor de fábrica, e o super_admin do kit autenticado
      E o modelo, com a condição de cada peça, e os arquivos de diagrama "<mundo>"
      Quando a guarda do mapa confere, em cada diagrama, as peças que ele desenha — como nó, como membro de agrupamento ou de porta, ou na árvore de registro — contra os modos em que o diagrama vale
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu todos os diagramas de painel e do kit inteiro, e ao menos o `TenantResource`, o `UserResource` do `/app` e o `AgenteIaResource`

      Exemplos:
        | mundo                                                                                                       | resultado                                                                                                                                       | # partição                              |
        | publicados                                                                                                  | aceita: toda peça condicional desenhada está só em diagramas que valem só nos modos em que ela fica disponível, com a chave ligada; a condicional só pela chave fica nos dois modos; o `/admin` tem ao menos um diagrama que vale só sem tenant e um que vale só com tenant, e o `TenantResource` só está nos de com | válida (hoje reprova: nota abaixo) |
        | cópia da árvore de registro do `/admin` valendo nos dois modos, que desenha o `TenantResource` *(alterado em 2026-11-23 — step 11, Adendo 2: era "cópia do `admin-registro`, compartilhado"; a divisão por modo partiu o arquivo)* | reprova, nomeando o diagrama e o `TenantResource`                                                                                              | compartilhado com peça de um modo       |
        | cópia do `app-registro-sem` que desenha o `UserResource` do `/app`                                          | reprova, nomeando o diagrama e a peça                                                                                                          | diagrama do modo em que ela não abre    |
        | cópia do `app-resumo-sem` com o `ConviteResource` do `/app` membro do agrupamento de convites               | reprova, nomeando o diagrama, o agrupamento e a peça                                                                                           | membro de agrupamento                   |
        | cópia do `app-produtividade-e-ui-com` valendo nos dois modos, que desenha o `ProjetoResource` do `/app` *(alterado em 2026-11-23 — step 11, Adendo 2: era "cópia do `app-produtividade-e-ui`, compartilhado"; a divisão por modo partiu o arquivo)* | reprova, nomeando o diagrama e a peça: com `KIT_DEMO` ligada, ela continua sem abrir sem tenant                                                 | modo e chave                            |
        | cópia em que o `admin-administracao`, compartilhado, desenha o `AgenteIaResource`                           | aceita: com `KIT_AGENTES_IA` ligada, ele abre nos dois modos (P-15)                                                                            | só pela chave, nos dois modos           |
        | cópia em que todo diagrama do `/admin` vale nos dois modos e nenhum desenha o `TenantResource`              | reprova, nomeando o `/admin`: não há diagrama que valha só com tenant                                                                          | `/admin` sem diagrama separado (RQ-29)  |
        | cópia do `kit-resumo-sem` com o `AcessosPorPainel` membro do agrupamento de multi-tenancy *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando o diagrama, o agrupamento e o `AcessosPorPainel`: ele só abre com tenant                                  | peça que herda, no modo errado (RQ-31)  |
        | cópia do modelo com a condição `command-center.sources` no `CommandRecordResource`, que o `infra-infraestrutura-2`, compartilhado, desenha *(alterado em 2026-11-23 — Adendo 3)* | aceita: com a `DatabaseSource` em `command-center.sources`, ele abre nos dois modos (RQ-30) | configuração de pacote, nos dois modos  |
        | cópia do modelo com a condição do chat do assistente — `KIT_AGENTES_IA` e o painel em `KIT_AGENTES_IA_PAINEIS` —, que o `kit-resumo-sem` desenha como membro *(alterado em 2026-11-23 — Adendo 4)* | aceita: com as duas chaves ligadas juntas, ele abre nos dois modos (RQ-33, P-15) | duas chaves juntas, nos dois modos |
```

- **Medido no HEAD `c642c23c`.** A linha "publicados" reprova: em 15 lugares, uma peça que só abre com tenant está num diagrama que vale sem tenant — o `TenantResource` no `admin-multi-tenancy-opt-in`, no `admin-registro` e no `admin-resumo`; o `UserResource` e o `ConviteResource` do `/app`, o `ConvitesRecebidos` e o `ProjetoResource`, cada um num mapa compartilhado (`app-acesso-e-autenticacao`, `app-convites`, `app-produtividade-e-ui`), no `app-registro-sem` e no `app-resumo-sem` —; e os 13 diagramas do `/admin` valem nos dois modos. As âncoras passam: o `TenantResource`, o `UserResource` do `/app` e o `AgenteIaResource` estão desenhados. A linha do `AgenteIaResource` aceita hoje, porque ele está no `admin-administracao`. As demais linhas são cópias e dependem da guarda nova.
- **O `AcessosPorPainel`** está hoje em dois diagramas que valem sem tenant (o `kit-multi-tenancy-opt-in` e o `kit-resumo-sem`), e fica fora deste caso até a Q28: painel nenhum o registra. *(alterado em 2026-11-23 — Adendo 3: entra, pela RQ-31. A linha "publicados" reprova hoje em 17 lugares — os 15 de antes e esses dois —, e a linha de cópia dele discrimina já: o `canView()` dele dá falso sem tenant)*
- **A Q27** não toca este caso: as duas leituras desenham o `CommandRecordResource` nos dois modos. *(alterado em 2026-11-23 — Adendo 3: a RQ-30 confirmou a leitura de chave. Hoje o modelo não tem a condição dele, e a metade viva não tem de onde tirar a `DatabaseSource`: o publicado reprova também nos 3 lugares em que ele está — o `infra-infraestrutura-2`, o `infra-registro` e o `infra-resumo`, todos compartilhados — até o modelo dar a condição com a configuração. A linha de cópia dele traz a condição na cópia do modelo, e aceita já)*

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o diagrama compartilhado desenha a peça que só abre com tenant — o caso de hoje, o `TenantResource` no `admin-registro`, no `admin-resumo` e no `admin-multi-tenancy-opt-in` | CT-33 | linha da árvore de registro do `/admin` valendo nos dois modos *(alterado em 2026-11-23 — step 11, Adendo 2)*, e o publicado: reprova; o mutante aceita |
| M2 | o filtro de modo olha só o registro, e o diagrama que vale só sem tenant desenha, como nó ou como membro, a peça registrada nos dois modos que só abre com tenant | CT-33, CT-37 | CT-33, linhas do `app-registro-sem` e do membro do `app-resumo-sem`; CT-37, linha app: reprova; o mutante aceita |
| M3 | a peça condicional só pela chave sai dos diagramas sem tenant, ou do mapa inteiro, porque está desligada de fábrica | CT-37 | linha nova do `AgenteIaResource` fora da árvore e da arquitetura do `/admin` sem tenant: reprova; o mutante aceita |
| M4 | o `/admin` fica só com diagramas compartilhados, e o `TenantResource` sai de todos eles e não entra em diagrama nenhum | CT-33, CT-38, CT-14 | CT-33, linha "sem diagrama separado"; CT-38, linha admin, "inclusive o `TenantResource`"; CT-14, a ligação de registro dele sem diagrama: reprova; o mutante aceita |
| M5 | a condição composta é lida só pela chave, e o `ProjetoResource` do `/app` entra num diagrama compartilhado | CT-33 | linha do `app-produtividade-e-ui-com` valendo nos dois modos *(alterado em 2026-11-23 — step 11, Adendo 2)*: reprova; o mutante aceita |

---

## Regra R42 — Em todo diagrama que a desenha, a peça condicional leva a marca da condição que o modelo dá a ela, no idioma do diagrama e à vista no carregamento — no nó dela, ou na linha do card que a nomeia como membro de agrupamento —; e a peça sem condição não leva marca

> `RQ-27`, `RQ-28`, `P-15`, `P-02`, `P-17` *(alterado em 2026-11-23 — step 11)* · perfil **padrão**; teto de mutantes do perfil completo (6), escalado no Perfil de Derivação · técnica: **EP** (forma do desenho: nó próprio, membro de agrupamento; condição: modo, chave, as duas; idioma: pt, en) + controles em memória · costura **G5**, forma de leitura *(alterado em 2026-11-23 — Adendo 2: regra nova)*

- **O conteúdo vem do requisito.** A RQ-27: "a marca da condição — o modo, ou a chave e o valor de fábrica —, em todo diagrama que a desenha". A peça com as duas condições (o `ProjetoResource` do `/app`) leva as duas: a marca só do modo a mostraria disponível com tenant de fábrica, e ela não abre.
- **"Em todo diagrama que a desenha" inclui o membro de agrupamento**, que a P-10 conta como desenho. A marca vai na linha dele no card que o nomeia.
- **À vista no carregamento** vem da RQ-28 ("nenhum diagrama a mostra como sempre disponível"): a peça desenhada sem a marca à vista, no carregamento, é mostrada como sempre disponível. Por isso a marca não pode estar só no `tag`, que o archify desenha como detalhe fino, com opacidade 0 no nível de leitura do carregamento (medido no commit `4adaf9a`, na R6).
- **O lugar e a redação** são de desenho (Q?3). O cenário vai `@premissa` na recomendação: no rótulo secundário do nó; o modo pelo termo da coluna da grade da página do mesmo idioma ("com tenant ativo", "tenancy on"); a chave como `VARIAVEL=valor de fábrica` (`KIT_AGENTES_IA=false`). *(alterado em 2026-11-23 — Adendo 3: decididos pela sessão — o rótulo secundário da caixa e o item do agrupamento que nomeia a peça; o modo, "só com tenant ativo" / "tenancy on only"; a chave, "KIT_HUB=false de fábrica" / "KIT_HUB=false by default"; a configuração de pacote, pelo caminho, `command-center.sources`. O `@premissa` fica nessa decisão. A redação do valor de fábrica da configuração de pacote, que é uma lista, não foi fixada: o caso confere que a marca traz o caminho, a fonte que falta de fábrica (`DatabaseSource`) e o "de fábrica" / "by default")* *(alterado em 2026-11-23 — Adendo 4)* A sessão confirmou essa redação: deixa de ser premissa desta derivação, e o `@premissa` do CT-57 marca só a decisão de desenho do lugar e da redação, como o do CT-06 (Q17). A mesma regra vale para a chave que é lista: a marca do chat diz `KIT_AGENTES_IA=false` e `KIT_AGENTES_IA_PAINEIS` vazia, com o "de fábrica" / "by default" — as duas chaves (RQ-33). *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* O "vazia" / "empty" é o valor de fábrica da chave que é lista, e faz parte da marca: "KIT_AGENTES_IA_PAINEIS vazia de fábrica" / "KIT_AGENTES_IA_PAINEIS empty by default", que é o que o gerador escreve. A guarda o confere em cada idioma. **Medido no HEAD `1dd48acd`**, executando a guarda de hoje copiada do arquivo de teste sobre a entrada que o gerador monta: o oráculo espera só o nome da variável (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasPartesDaMarca`), e aceita, no `kit-ia`, a marca pt sem o "vazia" e a en com o "vazia" no lugar do "empty"; o conferidor espera o mesmo (`site/verifica-mapa.mjs`, `partesDaMarca`). As duas linhas novas ficam vermelhas hoje pela guarda, que aceitaria as cópias (causa a); o gerador escreve as duas palavras certas. Varrido no HEAD `1dd48acd`, nas 186 entradas que o gerador monta (93 diagramas × 2 idiomas): nos 16 lugares do chat — o nó do `kit-ia`, a linha de membro dos dois `kit-resumo`, e a linha do card "Condições de disponibilidade" do `kit-ia`, dos dois `kit-resumo` e dos dois escritos à mão, o `app-chat-de-ia` e o `ia-do-prompt-ao-ledger`, em pt e em en —, a marca traz "vazia" em pt e "empty" em en: com a guarda apertada, a linha "publicados" continua verde.
- **Pelo id, não pelo nome.** O `UserResource` e o `ConviteResource` do `/admin` não têm condição; os do `/app`, sim.
- *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)* **A marca na peça sem condição se reconhece pela redação decidida inteira**: o modo, ou uma chave — a variável `KIT_*` com o valor, a lista com "vazia" / "empty", ou qualquer caminho de configuração com a fonte que falta —, seguida do "de fábrica" / "by default". Reconhecer só as formas que o mapa de hoje usa deixa passar a marca de outra chave vazada para a vizinha. **Medido no HEAD `1dd48acd`**, executando a guarda de hoje copiada do arquivo de teste: ela reconhece só o modo, `KIT_*=true|false` e o literal `command-center.sources` (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasTemMarca`), e aceita, no rótulo secundário do model `AgenteIa` do `admin-administracao`, que não tem condição, "KIT_AGENTES_IA_PAINEIS vazia de fábrica" e "command-center.enabled sem DatabaseSource de fábrica". As duas linhas novas ficam vermelhas hoje pela guarda, que aceitaria as cópias (causa a). Varrido no HEAD `1dd48acd`, nas 186 entradas que o gerador monta: nenhum dos 2.936 lugares de peça sem condição — nó com o `tag`, e linha de membro — traz marca pela definição alargada, e o nó escrito à mão foi lido pela D43: com a guarda alargada, a linha "publicados" continua verde.
- *(alterado em 2026-11-23 — step 11, Adendo 4)* **No diagrama escrito à mão, a marca da peça condicional fica só na linha dela no card "Condições de disponibilidade"** (R44), e não no nó: medido no archify (commit `4478129`), a caixa do participante do sequence chega no máximo a 190 px (`renderers/sequence/render-sequence.mjs`, `participantSpreadGeometry`), e o nó do dataflow, sem largura explícita, à largura do estágio, 168 px (`renderers/dataflow/dataflow-compiler.mjs`, `stageW`); a marca do chat pede cerca de 345 px e 381 px, e a largura explícita que a comportaria quebra o roteamento das setas *(alterado em 2026-11-23 — step 9 do Adendo, RD-05: dizia "96 px fixos" e "144 px", a medida daqueles diagramas, e não o limite do archify)*, e o archify recusa o diagrama. O nó continua a ser a peça pelo nome exato ou pela prova que declara a classe (Q30), e é por ele que o card tem de ter a linha. No diagrama construído, a marca segue no nó e na linha do membro. *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* O nó é o da D43 (Setup Global): a prova que declara a classe no arquivo de evidência da peça, mesmo com o trecho diferente do da evidência, vem antes do nome; sem ela, o nome exato, só se uma peça só tem esse nome; com homônimas e sem prova, o nó não designa peça, e nenhuma linha de card é pedida para ele. **Medido no HEAD `1dd48acd`**, executando a guarda de hoje copiada do arquivo de teste sobre entradas que o gerador monta de cópias em memória dos diagramas: com a prova do participante do chat abreviada para `class AssistenteChatWidget`, ela não acha o chat no nó (`mapaDiagramasPecasPelaProva` exige o trecho igual ao da evidência), e aceita o card sem a linha dele; no `admin-convite` com o participante "ConviteResource", ela toma as duas homônimas pelo nome — com a prova do `/admin` ou sem prova — e reprova o card sem a linha do `ConviteResource` do `/app`, e aceita o card com ela, que a R37 reprova (CT-45). As linhas novas do A5 e do A6 ficam vermelhas hoje pela guarda (causa a); no dado publicado, nada muda (Setup Global, D43).

```gherkin
  Regra: O desenho diz, de cada peça condicional, a condição que o modelo guarda, e não diz nada da peça sem condição

    @premissa
    Esquema do Cenário: [CT-57] em todo diagrama que a desenha, a peça condicional leva a marca da condição, no idioma do diagrama e à vista no carregamento, e a peça sem condição não leva marca
      Dado o `node` do ambiente, lido pela versão
      E o modelo, com a condição de cada peça, e os arquivos de diagrama "<mundo>"
      Quando o gerador monta, para o archify, a entrada de cada diagrama em pt e em en, sem baixar o archify nem renderizar
      Então o resultado é "<resultado>"
      E, nos publicados, a guarda conferiu a marca de toda peça condicional que algum diagrama desenha, como nó e como membro de agrupamento

      Exemplos:
        | mundo                                                                                                             | resultado                                                                                                                                         | # partição                          |
        | publicados                                                                                                        | aceita: em cada entrada, o nó de cada peça condicional traz a marca no rótulo secundário, e não só no `tag`; a linha dela no card "Quem está em …", quando ela é membro de agrupamento, também; a marca diz a condição que o modelo dá à peça, no idioma da entrada; e nenhum nó nem linha de peça sem condição traz marca. *(alterado em 2026-11-23 — Adendo 3)* A marca diz o modo como "só com tenant ativo" / "tenancy on only", a chave como "KIT_HUB=false de fábrica" / "KIT_HUB=false by default", a configuração de pacote pelo caminho `command-center.sources`, e a composta, as duas; o `CommandRecordResource` e o `AcessosPorPainel` estão entre as peças marcadas; *(alterado em 2026-11-23 — Adendo 4)* o chat do assistente e o checklist do tutorial também — o chat com as duas chaves *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)*: "KIT_AGENTES_IA=false" e a lista "KIT_AGENTES_IA_PAINEIS vazia" / "empty" —, inclusive nos diagramas escritos à mão, em que o nó é a peça pelo nome exato ou pela prova que declara a classe *(alterado em 2026-11-23 — step 11, Adendo 4: neles, a marca fica só na linha da peça no card "Condições de disponibilidade", pela exceção medida na R42)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*: o nó da D43 do Setup Global — a prova primeiro, e o nome só sem homônima; nenhum plugin nem middleware traz marca *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)*, na redação decidida inteira; e o card "Condições de disponibilidade" / "Availability conditions" tem uma linha por peça condicional que o diagrama desenha, com a condição e a âncora que a decide (R44) | válida (hoje reprova: nota abaixo) |
        | a entrada do `admin-administracao`, adulterada em memória: o nó do `AgenteIaResource` sem a marca                 | reprova, nomeando o diagrama e a peça                                                                                                             | nó sem marca                        |
        | a mesma entrada, com a marca do `AgenteIaResource` só no `tag`                                                    | reprova, nomeando o diagrama e a peça: o `tag` não fica à vista no carregamento                                                                    | marca na etiqueta que o zoom esconde |
        | a entrada do `admin-resumo-sem` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-resumo`)*, com a linha do `AgenteIaResource` no card do agrupamento de administração sem a marca | reprova, nomeando o diagrama, o agrupamento e a peça                                                                                              | membro sem marca                    |
        | a entrada en do `app-registro-com`, com a marca do `ProjetoResource` só com o modo                                | reprova, nomeando a peça: falta `KIT_DEMO=false` *(alterado em 2026-11-23 — Adendo 3: "KIT_DEMO=false by default", na redação decidida)* | condição composta pela metade       |
        | a entrada en do `app-registro-com`, com a marca de modo do `UserResource` do `/app` em pt, "com tenant ativo" *(alterado em 2026-11-23 — Adendo 3: "só com tenant ativo", na redação decidida)* | reprova, nomeando o diagrama e a peça | marca en com o texto pt |
        | a entrada do `admin-acesso-e-autenticacao`, com a marca de modo do `UserResource` do `/app` no nó do `UserResource` do `/admin` | reprova, nomeando a peça pelo id: a do `/admin` não tem condição                                                                   | marca dada pelo nome                |
        | a entrada do `admin-registro-com` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-registro`)*, com a marca "KIT_HUB=false de fábrica" no nó do `FilamentCardsPlugin`, que serve o hub *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando o plugin: plugin e middleware não levam marca | plugin com marca (RQ-32) |
        | a entrada do `infra-infraestrutura-2`, com a marca do `CommandRecordResource` sem o caminho `command-center.sources` *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando a peça: a marca da configuração de pacote diz o caminho dela | configuração sem o caminho (RQ-30) |
        | a entrada do `admin-resumo-sem` *(alterado em 2026-11-23 — step 11, Adendo 2: era o `admin-resumo`)*, com o card "Condições de disponibilidade" sem a linha do `AgenteIaResource`, membro do agrupamento de administração *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando o diagrama e o `AgenteIaResource` | card sem a peça desenhada (R44) |
        | a entrada do `app-registro-sem`, com o card listando o `UserResource` do `/app`, que o diagrama não desenha *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando o diagrama e a peça | card com peça não desenhada (R44) |
        | a entrada do `admin-administracao`, com a linha do `AgenteIaResource` no card trazendo a âncora da condição do `HubDeAdministracao` *(alterado em 2026-11-23 — Adendo 3)* | reprova, nomeando a peça: a âncora não é a da condição dela | prova de outra peça (R44) |
        | a entrada do `app-chat-de-ia`, com o card "Condições de disponibilidade" sem a linha do chat, que o participante — rótulo "AssistenteChat", prova `class AssistenteChatWidget extends Component` — desenha *(alterado em 2026-11-23 — Adendo 4)* *(alterado em 2026-11-23 — step 11, Adendo 4: era o participante sem a marca; no diagrama escrito à mão, a marca fica só na linha do card (R42))* | reprova, nomeando o diagrama e o chat: o nó é o chat pela prova, embora o rótulo seja abreviado | componente flutuante no diagrama escrito à mão (RQ-33) |
        | a entrada do `kit-ia`, com a marca do chat só com `KIT_AGENTES_IA=false de fábrica` *(alterado em 2026-11-23 — Adendo 4)* | reprova, nomeando o chat: falta `KIT_AGENTES_IA_PAINEIS` | o chat com uma das duas chaves (RQ-33) |
        | a entrada do `kit-ia`, com a marca do chat sem o "vazia": "KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS de fábrica" *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* | reprova, nomeando o chat: falta "KIT_AGENTES_IA_PAINEIS vazia" | a chave que é lista sem o valor de fábrica (RQ-33) |
        | a entrada en do `kit-ia`, com a marca do chat "KIT_AGENTES_IA=false · KIT_AGENTES_IA_PAINEIS vazia by default" *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* | reprova, nomeando o chat: falta "KIT_AGENTES_IA_PAINEIS empty" | a chave que é lista com o valor de fábrica em pt, na entrada en |
        | a entrada do `admin-administracao`, com "KIT_AGENTES_IA_PAINEIS vazia de fábrica" no rótulo secundário do nó do model `AgenteIa`, que não tem condição *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)* | reprova, nomeando o `AgenteIa`: a peça sem condição não leva marca | marca de chave que é lista em peça sem condição |
        | a mesma entrada, com "command-center.enabled sem DatabaseSource de fábrica" no rótulo secundário do nó do model `AgenteIa` *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)* | reprova, nomeando o `AgenteIa`: a peça sem condição não leva marca | marca de outro caminho de configuração em peça sem condição |
        | a entrada de uma cópia em memória do `app-chat-de-ia` com a prova do participante "AssistenteChat" abreviada para `class AssistenteChatWidget`, trecho que está no arquivo da evidência do chat, e o card "Condições de disponibilidade" sem a linha do chat *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* | reprova, nomeando o diagrama e o chat: a fonte declara a classe dele no arquivo da evidência, e o nó é o chat, mesmo com o trecho diferente do da evidência | nó designado pela prova com o trecho diferente do da evidência (D43) |
        | a entrada de uma cópia em memória do `admin-convite` com o participante "ConviteResource", de prova `class ConviteResource extends Resource` no arquivo do `ConviteResource` do `/admin`, que não tem condição, e sem linha dele no card "Condições de disponibilidade" *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* | aceita: pela prova, o nó é o `ConviteResource` do `/admin`, e a homônima do `/app`, condicional, não entra pelo nome | homônima designada pela prova, sem linha no card (D43) |
        | a mesma cópia, com a linha "ConviteResource: só com tenant ativo — app/Filament/App/Resources/Convites/ConviteResource.php — …", a marca do `ConviteResource` do `/app`, no card *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* | reprova, nomeando o diagrama e o `ConviteResource`: o card traz a linha de uma peça condicional que o diagrama não desenha — o mesmo mundo e o mesmo veredito da linha do CT-45 | homônima designada pela prova, com a marca da outra (D43) |
        | a entrada de uma cópia em memória do `admin-convite` com o participante "ConviteResource" sem fonte que declare classe de peça, e sem linha dele no card *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* | aceita: com homônimas e sem prova, o nó não designa peça, e nenhuma linha de card é pedida para ele | homônimas sem prova (D43) |
        | a mesma cópia — o participante "ConviteResource" sem prova —, com a linha "ConviteResource: só com tenant ativo — app/Filament/App/Resources/Convites/ConviteResource.php — …" no card *(alterado em 2026-11-23 — step 9 do Adendo, D44)* | reprova, nomeando o diagrama e a linha: ela afirma a condição de uma peça que o diagrama não designa (R44) | linha de card de nó que não designa peça (D44) |
```

- *(alterado em 2026-11-23 — step 11, Adendos 2 a 4)* **A base das linhas adulteradas.** Com a marca publicada, cada linha adulterada parte da entrada publicada sem marca nenhuma — o rótulo secundário sem os pedaços de marca, a linha do membro de volta ao nome, sem o card "Condições de disponibilidade" — e põe de volta só o que a linha pede. Adulterar por cima da entrada publicada deixaria a marca certa esconder a errada.

- **Medido no HEAD `c642c23c`.** A linha "publicados" reprova: nenhuma entrada traz marca, e as 14 peças condicionais do discriminante estão desenhadas em 70 lugares por idioma — 20 nós de mapa de arquitetura, 22 nós de árvore e 28 linhas de membro. As linhas adulteradas partem da entrada publicada e dependem da guarda nova; a do `admin-administracao` existe hoje (o `AgenteIaResource` é nó dele).
- **Fora:** as peças da Q27 e da Q28, até as respostas; *(alterado em 2026-11-23 — Adendo 4: e o componente flutuante entra, pela RQ-33 — nada da população fica fora)* e o comparativo, cuja casca não tem card e desenha as peças condicionais do resumo do `/app` só dentro das caixas de agrupamento (medido: o `app-resumo-sem` desenha 1 peça e 7 agrupamentos). *(alterado em 2026-11-23 — Adendo 3: as peças da Q27 e da Q28 entram — RQ-30 e RQ-31. Fica fora o componente flutuante ligado a chave, até a Q30)*
- *(alterado em 2026-11-23 — Adendo 3)* **Medido no HEAD `c642c23c`, com a população do Adendo 3.** A linha "publicados" reprova: nenhuma entrada traz marca nem o card "Condições de disponibilidade", e as 16 peças da população estão desenhadas em 76 lugares por idioma — 22 nós de mapa, 23 nós de árvore e 31 linhas de membro. Nenhum plugin nem middleware traz marca hoje: o `FilamentCardsPlugin` está em 11 lugares e o `ExigeTutorialGuiadoLigado` em 11, todos sem marca. As linhas adulteradas do Adendo 3 partem da entrada publicada; a do `FilamentCardsPlugin` discrimina já, e as três do card e a do `CommandRecordResource` dependem do card e da marca que o gerador ainda não monta.
- *(alterado em 2026-11-23 — Adendo 4)* **Medido no HEAD `c642c23c`, com os componentes flutuantes.** As 18 peças da população estão desenhadas em 85 lugares por idioma, de 28 arquivos de diagrama — 25 nós de mapa, 23 nós de árvore, 35 linhas de membro e 2 nós de diagrama escrito à mão —, nenhum com marca. Os dois nós escritos à mão são do chat: o participante "AssistenteChat" do `app-chat-de-ia` e o nó `AssistenteChatWidget` do `ia-do-prompt-ao-ledger`; os dois têm rótulo secundário, onde a marca cabe, e a marca deles é escrita no arquivo, e não gerada. A linha do `app-chat-de-ia` discrimina já, pela identificação pela prova; a do `kit-ia` adultera a entrada com meia marca, e discrimina já.
- **Por que não no navegador.** A entrada é o que o archify desenha; o que só o navegador prova — a marca à vista, com opacidade 1 no carregamento — é o passo 4 do CT-B01, pelo R24-M5.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o gerador não leva a condição do modelo ao desenho, e a peça condicional sai sem marca — o caso de hoje | CT-57 | publicado e linha "nó sem marca": reprova; o mutante aceita |
| M2 | a marca vai para o `tag`, como a F-nn, e o nível de leitura do carregamento a esconde | CT-57 | linha "só no `tag`": reprova; o mutante aceita |
| M3 | só o nó próprio leva a marca, e a peça que é membro de agrupamento aparece sem ela no card | CT-57 | linha "membro sem marca": reprova; o mutante aceita |
| M4 | a marca da condição composta leva só uma parte | CT-57 | linha do `ProjetoResource` só com o modo: reprova; o mutante aceita |
| M5 | a marca en sai com o termo pt | CT-57 | linha da marca en com "só com tenant ativo" *(alterado em 2026-11-23 — Adendo 3: era "com tenant ativo", antes da redação decidida)*: reprova; o mutante aceita |
| M6 | o gerador marca a peça pelo nome, e o `UserResource` do `/admin`, sem condição, ganha a marca do `UserResource` do `/app` | CT-57 | linha "marca dada pelo nome", e o publicado ("nenhum nó de peça sem condição traz marca"): reprova; o mutante aceita |
| M7 — step 9 do Adendo (A3) | a marca da chave que é lista leva só o nome da variável, sem o valor de fábrica: "KIT_AGENTES_IA_PAINEIS de fábrica" — o que o oráculo de hoje espera | CT-57 | linha do `kit-ia` com a marca sem o "vazia": reprova, nomeando o chat; o mutante aceita |
| M8 — step 9 do Adendo (A3) | a marca en da chave que é lista leva o "vazia" do pt | CT-57 | linha en do `kit-ia` com "vazia by default": reprova, nomeando o chat; o mutante aceita |
| M9 — step 9 do Adendo (A4) | o desenho de uma peça sem condição sai com a marca de uma chave que é lista — a marca da vizinha vazada —, e a guarda, que reconhece marca só pelas formas fixas no código, não a vê | CT-57 | linha do model `AgenteIa` com "KIT_AGENTES_IA_PAINEIS vazia de fábrica": reprova; o mutante aceita |
| M10 — step 9 do Adendo (A4) | o desenho de uma peça sem condição sai com a marca de um caminho de configuração que não é o `command-center.sources`, e a guarda não a vê | CT-57 | linha do model `AgenteIa` com "command-center.enabled sem DatabaseSource de fábrica": reprova; o mutante aceita |
| M11 — step 9 do Adendo (A6) | no nó escrito à mão, a guarda toma toda peça do nome do rótulo, além da que a prova designa, e pede a linha da homônima condicional para o participante que a prova faz ser a outra | CT-57 | linha do "ConviteResource" de prova do `/admin` sem linha no card: aceita, e o mutante reprova; a mesma cópia com a linha da marca do `/app`: reprova, e o mutante aceita — o veredito que a R37 (CT-45) dá à mesma cópia |
| M12 — step 9 do Adendo (A6) | sem prova, a guarda toma toda peça do nome do rótulo, e o nó de rótulo homônimo pede a linha da homônima condicional | CT-57 | linha do "ConviteResource" sem prova e sem linha no card: aceita; o mutante reprova, pedindo a linha do `ConviteResource` do `/app` |

---

## Regra R43 — A população da condição: a peça que a configuração de pacote deixa fechada de fábrica conta como peça de chave; a peça que painel nenhum registra e só aparece dentro de uma tela condicional herda a condição dela; e plugin e middleware nunca guardam condição nem levam marca — e o componente flutuante ligado a chave leva a condição das chaves que decidem a renderização dele

> `RQ-30`, `RQ-31`, `RQ-32`, `RQ-33`, `P-16`, `RQ-27` · perfil **padrão**; teto de mutantes do completo (6) nos do Adendo 3, e os do Adendo 4 fora do teto (Perfil de Derivação) · técnica: **EP** da população (peça registrada; peça fora do registro que herda; componente flutuante; plugin e middleware) + controles em cópia · cenários: linhas do Adendo 3 no CT-32, no CT-33, no CT-56 e no CT-57 *(alterado em 2026-11-23 — Adendo 3: regra nova)* *(alterado em 2026-11-23 — Adendo 4)*

A R30 diz o que a condição de uma peça tem de ser; esta diz de quais peças. A guarda é a mesma, e por isso a regra não tem cenário próprio: as linhas dela moram nos cenários que já avaliam a condição (CT-32, CT-56), o lugar (CT-33) e a marca (CT-57).

| Partição | O que a RQ pede | Medido no HEAD `c642c23c` | Linhas |
|---|---|---|---|
| peça registrada fechada por configuração de pacote (RQ-30) | conta como chave: condição com o caminho, o valor de fábrica e o valor que a liga; marca com o caminho; entra nos modos em que abre com ela ligada | o `CommandRecordResource`: com a `DatabaseSource` em `command-center.sources`, abre nos dois modos; nenhuma chave `kit.*` o abre | CT-32 (sem a condição; a configuração vizinha `command-center.enabled`), CT-56, CT-33 (aceita nos dois modos), CT-57 (marca sem o caminho), CT-37 e CT-38 |
| peça fora do registro que só aparece dentro de tela condicional (RQ-31) | herda a condição da tela: marca e só nos diagramas do modo em que a tela abre | o `AcessosPorPainel`, dentro da lista do `TenantResource`: o `canView()` dele chama o do `TenantResource`; indisponível sem tenant e disponível com, nos três painéis. Das 11 peças do modelo fora do registro com `canAccess()` ou `canView()` estático, a única indisponível num modo | CT-32 (sem a condição), CT-56, CT-33 (membro no `kit-resumo-sem`), CT-57 |
| plugin e middleware (RQ-32) | sem condição e sem marca, mesmo o que serve a tela condicional | o `FilamentCardsPlugin` (hubs, `KIT_HUB`) e o `ExigeTutorialGuiadoLigado` (`KIT_ONBOARDING`), desenhados em 11 lugares cada, sem marca; nenhum plugin nem middleware tem condição no modelo | CT-32 (plugin com condição), CT-57 (plugin com marca) |
| componente flutuante ligado a chave (RQ-33) *(alterado em 2026-11-23 — Adendo 4)* | leva a condição das chaves que decidem a renderização dele — o chat, as duas —: marca com a chave e o valor de fábrica em todo diagrama que o desenha, inclusive o escrito à mão *(alterado em 2026-11-23 — step 11)*: nele, na linha do card "Condições de disponibilidade" (P-17); nunca aparece como sempre disponível | o chat do assistente: fechado de fábrica e com cada chave sozinha, aberto com `KIT_AGENTES_IA` e o painel em `KIT_AGENTES_IA_PAINEIS` juntas; o checklist do tutorial: aberto com `KIT_ONBOARDING`. Nos três painéis e nos dois modos, igual. Desenhados em 9 lugares, sem marca | CT-32 (sem a condição do checklist; o chat com uma chave só), CT-56, CT-33 (o chat no resumo sem tenant, com as duas chaves juntas), CT-57 (o participante do `app-chat-de-ia`; a meia marca no `kit-ia`; *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* a prova abreviada do participante; *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* a lista sem o "vazia" / "empty"), CT-B01 |

- **Como a peça que herda é achada.** Pelo `canAccess()` ou `canView()` próprio, avaliado em cada painel do registro. É o limite mecânico: a peça que só aparecesse dentro de tela condicional sem método próprio não seria vista. Medido: entre as peças de tela do modelo fora do registro, nenhuma sem método próprio fica só dentro de tela condicional — lacuna declarada, sem peça hoje (M6).
- **Como a configuração de pacote é ligada.** O modelo diz o valor que a liga, porque "ligada" não é o oposto de uma lista; a guarda põe esse valor e avalia a peça. O modelo só propõe: a peça que não abre com o valor proposto reprova, e o valor proposto ligado de fábrica também (`command-center.enabled`, `config/command-center.php` linha 11, vem `true`). *(alterado em 2026-11-23 — Adendo 4)* A sessão confirmou: o modelo guarda o valor que liga a configuração. Vale igual para a chave que é lista (`KIT_AGENTES_IA_PAINEIS`): ligada é a lista com o painel em que a guarda avalia. *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* A configuração de pacote liga ao menos uma fonte nova (decisão da sessão): a `ligada` que não acrescenta nada ao `padrao` deixaria a marca sem a fonte que falta de fábrica, e o gerador a recusa (R40, CT-50). *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)* "Nova" se decide pelo nome curto da classe, sem namespace e sem `::class` — o que a marca mostra —, e a chave do kit é a variável `KIT_*`: a `ConfigSource` de outro namespace ou com `::class` não é fonte nova, e o `APP_DEBUG` não é chave; o gerador e o conferidor recusam as duas (R40: CT-50 e CT-B08).
- **A âncora.** O `canAccess()` do `CommandRecordResource` está em `vendor/`, fora do commit, e não serve de prova (R9); a âncora da condição é o trecho do kit que a decide, o `'sources'` de `config/command-center.php:'sources':69`.
- **Fora, e perguntado.** O componente flutuante que painel nenhum registra, que não fica dentro de tela e que só aparece com uma chave — o chat do assistente e o checklist do tutorial —: nenhuma das três RQ o alcança (Q30). Até a resposta, fica fora da população, e o lugar dele nos diagramas não muda. *(alterado em 2026-11-23 — Adendo 4)* Respondido pela RQ-33 (Q30): entra na população, pelo predicado de renderização do mapa fechado do Setup Global — o `AgentesDeIa::assistenteNoPainel()` do painel para o chat, o `TutorialGuiado::visivel()` para o checklist. O componente flutuante novo, fora do mapa, reprova a guarda até ganhar o predicado dele.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | a guarda e o modelo só conhecem as chaves booleanas de `kit.*`, e a peça fechada pela configuração de pacote fica sem condição, desenhada como sempre disponível | CT-32, CT-56 | o publicado de cada um ("inclusive o `CommandRecordResource`") e a linha "sem a condição do `CommandRecordResource`": reprova; o mutante aceita |
| M2 | a condição da peça de pacote nomeia uma configuração vizinha do mesmo pacote, ligada de fábrica (`command-center.enabled`) | CT-32 | linha `command-center.enabled`: vem ligada de fábrica e não abre a peça; reprova; o mutante aceita |
| M3 | a peça que painel nenhum registra fica fora da população: o `AcessosPorPainel` sem condição, desenhado nos diagramas sem tenant | CT-32, CT-33 | CT-32, publicado e linha "sem a condição do `AcessosPorPainel`"; CT-33, publicado e linha do membro no `kit-resumo-sem`: reprova; o mutante aceita |
| M4 | o plugin ou o middleware que serve a tela condicional ganha a condição dela — no modelo, ou herdada pelo gerador | CT-32, CT-57 | CT-32, linha do `FilamentCardsPlugin` com `KIT_HUB`; CT-57, linha da marca no nó dele e o publicado ("nenhum plugin nem middleware traz marca"): reprova; o mutante aceita |
| M5 | a marca da configuração de pacote sai no formato das chaves do kit, sem o caminho da configuração | CT-57 | linha do `CommandRecordResource` sem `command-center.sources`: reprova; o mutante aceita |
| M6 | a peça que só aparece dentro de tela condicional, sem `canAccess()` nem `canView()` próprio, fica sem a condição da tela | ⚠️ **sem matador**: o discriminante vivo só vê a peça pelo método próprio, e o modelo não guarda a ligação "a tela contém a peça". Medido: nenhuma peça assim no modelo do HEAD; a primeira que aparecer passa calada | — (lacuna declarada) |
| M7 *(alterado em 2026-11-23 — Adendo 4: Q30)* | o componente flutuante fica fora da população, sem condição, e é desenhado sem marca nos diagramas do kit inteiro — o caso de hoje | CT-32, CT-56, CT-57 | CT-32 e CT-56, o publicado ("o chat do assistente e o checklist do tutorial") e a linha sem a condição do checklist; CT-57, o publicado: reprova; o mutante aceita |
| M8 *(alterado em 2026-11-23 — Adendo 4: Q30)* | a condição do chat guarda uma chave só, `KIT_AGENTES_IA`, e a marca diz só ela | CT-32, CT-57 | CT-32, linha "o chat só com `KIT_AGENTES_IA`": ligada sozinha, ele continua fechado; CT-57, linha da meia marca no `kit-ia`: reprova; o mutante aceita |
| M9 *(alterado em 2026-11-23 — Adendo 4: Q30)* | a guarda reconhece a peça do diagrama escrito à mão só pelo nome exato, e o chat do `app-chat-de-ia`, de rótulo "AssistenteChat", passa sem a linha no card | CT-57 | linha do card do `app-chat-de-ia` sem a linha do chat *(alterado em 2026-11-23 — step 11, Adendo 4: era "linha do participante sem a marca")*: o nó é o chat pela prova da classe, e o card tem de ter a linha dele; reprova; o mutante não o vê, e aceita |
| M10 *(alterado em 2026-11-23 — Adendo 4: Q30)* | a guarda viva liga as chaves uma de cada vez, e o chat, que pede as duas juntas, fica "indisponível em todo modo": a guarda o barra do resumo sem tenant, onde ele tem de estar | CT-33 | linha do chat como membro do `kit-resumo-sem`, com as duas chaves na condição: aceita; o mutante reprova |
| M11 — step 9 do Adendo (A5) | a guarda reconhece o nó escrito à mão pela prova só quando o trecho da fonte é igual ao da evidência da peça no modelo, e o participante do chat cuja prova abreviada — `class AssistenteChatWidget` — declara a classe no arquivo dela passa sem a linha no card | CT-57 | linha do `app-chat-de-ia` com a prova abreviada e o card sem a linha do chat: reprova, nomeando o diagrama e o chat; o mutante não vê o chat no nó, e aceita |

---

## Regra R44 — Todo diagrama que desenha peça condicional traz o card "Condições de disponibilidade" ("Availability conditions"), com uma linha por peça condicional desenhada, a condição dela e a âncora do código que a decide, inteira e à vista sem foco

> `RQ-27`, `P-03`, `P-02`, `P-17` *(alterado em 2026-11-23 — step 11)* · perfil **padrão** · técnica: **EP** (peça do card: desenhada como nó, desenhada como membro, não desenhada no diagrama) + controles em memória · cenários: linhas do card no CT-57 e o passo 4 do CT-B01 (no `05`) *(alterado em 2026-11-23 — Adendo 3: regra nova; o card é decisão de desenho da sessão, na Q?3)*

- **De onde vem.** A sessão decidiu o card ao responder a Q?3: ele lista cada peça condicional desenhada, com a prova da condição. O oráculo de conteúdo vem da RQ-27 — a marca da condição em todo diagrama que desenha a peça — e da P-03 — o que o mapa afirma tem a âncora no código, conferida pela R30. O card é o lugar; os cenários vão `@premissa` nessa decisão.
- **Uma linha por peça desenhada**, nó ou membro, e nenhuma por peça que o diagrama não desenha — inclusive a que a definição do Setup Global tira do modo do diagrama. *(alterado em 2026-11-23 — step 11, Adendo 4)* No diagrama escrito à mão, a linha do card é o único lugar da marca (R42). *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* Nele, "a peça desenhada" é a que o nó designa pela D43 (Setup Global): a linha da homônima que a prova não designa não é pedida, e a que vem mesmo assim reprova (o CT-57 e o CT-45, na mesma cópia); a linha com o rótulo de um nó homônimo sem prova é a Q?6.
- **A prova é a âncora da condição no modelo**, a mesma que o CT-32 resolve: o arquivo e o trecho do kit.
- **Medido no HEAD `c642c23c`.** Nenhum diagrama tem o card; os que desenham peça condicional são os 76 lugares da R42, em 23 arquivos de diagrama. *(alterado em 2026-11-23 — Adendo 4)* Com os componentes flutuantes, 85 lugares em 28 arquivos; o card lista também o chat e o checklist, e, nos dois diagramas escritos à mão, o card é escrito no arquivo.

#### Mutantes previstos

| # | Implementação errada plausível | Cenário que mata | Asserção que mata |
|---|---|---|---|
| M1 | o card lista só as peças com nó próprio, e esquece a que é membro de agrupamento | CT-57 | linha do card do `admin-resumo` sem o `AgenteIaResource`: reprova; o mutante aceita |
| M2 | o card lista as peças condicionais do painel, e não as que o diagrama desenha — inclusive a que o modo do diagrama tirou | CT-57 | linha do card do `app-registro-sem` com o `UserResource` do `/app`: reprova; o mutante aceita |
| M3 | a linha do card traz a âncora da condição de outra peça | CT-57 | linha do `AgenteIaResource` com a âncora do `HubDeAdministracao`: reprova; o mutante aceita |
| M4 | o card só aparece com a peça em foco, ou sai cortado | CT-B01 (passo 4) | "o card 'Condições de disponibilidade' traz, à vista sem foco e inteira, a linha de cada peça condicional que o diagrama desenha": reprova; o mutante aceita |
| M5 — step 9 do Adendo (D44) | a guarda do card confere só as linhas das peças desenhadas, e a linha a mais, de um nó que não designa peça, passa | CT-57 | linha da D44 no `admin-convite`: reprova; o mutante aceita |

---

## Checklist de Taxonomia

| Item | Cenário que mata | Grupo |
|---|---|---|
| IDOR / autorização horizontal | CT-09 (linhas `bootstrap/cache/packages.php` e `vendor/`). O site é público e estático, e nenhuma rota recebe `{id}`. A única saída de conteúdo para fora do repositório público seria a prova citar arquivo fora do commit, e essas linhas a matam | G2 |
| Autorização exercida na ação | não se aplica: nenhuma ação, policy ou permissão nova. A Superfície Livewire do `02` não cria nada em `app/`, e o gate confere o diff (RQ-15) | — |
| Idempotência (ancorada no agregado) | não se aplica: o build regenera o mapa do zero a partir do commit (P-07), e nada acumula entre uma execução e outra | — |
| Concorrência | não se aplica: não há contador nem limite | — |
| **Fronteira no ponto de entrada** (o modelo, o catálogo e as referências, conferidos a cada PR) | CT-01, CT-03, CT-04, CT-08, CT-09, CT-10, CT-21, CT-28, CT-29, CT-31, CT-35, CT-36, CT-39, CT-40, CT-41, CT-44, CT-45; *(step 9)* CT-53 e CT-54 (as âncoras e os ids dos arquivos de diagrama), CT-49 e CT-50 (os argumentos e os arquivos de entrada das ferramentas de autor), CT-B08 (os do conferidor); *(alterado em 2026-11-23 — Adendo 2)* CT-32 e CT-56 (a condição de cada peça, contra o código, a cada PR); *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* CT-50 (a condição de cada peça e a ponta de cada ligação de registro, conferidas pelo gerador antes de qualquer efeito) | G1, G2, G3, G5, G4 |
| **Domínio condicionado** | CT-06, CT-36 e CT-44 (origem × peça; F-nn × pacote nomeado × pacote da peça; rota do "onde fica" × peça; caminho × modo), CT-08 (classe × tipo), CT-10 (forma da prova × arquivo; trait × origem que o usa; método × lista de contrato; par de mapeamento × pontas), CT-21 (tipo × painel × modo; escopo do diagrama × peça registrada), CT-37 e CT-38 (registro × modo × arquitetura), CT-45 (marca × chave × valor de fábrica × modo); *(alterado em 2026-11-23 — Adendo 2)* CT-32 e CT-56 (peça × modo × chave ligada), CT-33 (condição da peça × modos do diagrama), CT-57 (condição × forma do desenho × idioma) | G2, G3, G5 |
| **Estado × operação de escrita** (número removido × reaproveitar; removido × ainda entregue; removido × idioma) | CT-03, CT-04 (matriz da R3, 15 células), CT-36 (a removida que uma peça ainda entrega), CT-01 (removida num idioma só) | G1, G2 |
| Ausente ≠ null ≠ vazio | CT-09 (trecho ausente, vazio, só de espaços), CT-03, CT-21, CT-28, CT-29, CT-35 e CT-41 (motivo ausente; motivo "—"; "não se aplica" sem porquê), CT-31 (tempo sem fonte; sem data), CT-39 (transição e estado sem âncora); *(step 9)* CT-35 e CT-B07 (motivo "N/A", "not applicable" e o valor 0, que não é texto), CT-49 (argumento sem valor); *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* CT-50 (a condição sem âncora); *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)* CT-57 (a lista vazia de fábrica, que a marca diz "vazia" / "empty", e não omite) | G1, G2, G4, G5 |
| Paginação / ordenação | não se aplica: não há listagem paginada, e a ordem das linhas do catálogo não é cláusula. A ordem dos números é, e o CT-04 a cobre | — |
| Timezone / DST | não se aplica: nenhuma data é calculada. As datas da linha do tempo são comparadas como texto com o cabeçalho do CHANGELOG (CT-31) | — |
| Unicode / texto livre | CT-B01 (acentos intactos no `<h1>`; rótulo igual ao da fonte), CT-B02 (item de card inteiro), CT-B04 (interface pt e en, inclusive com a busca e o passaporte abertos), CT-B07 (catálogo pt sem o texto en), CT-16 e CT-21 (termo do glossário; palavra funcional do pt em frase en); *(step 9)* CT-B06 (caminho do conferidor com espaço e acento), CT-47 (singular e plural na caixa, nos dois idiomas); *(alterado em 2026-11-23 — Adendo 2)* CT-57 (a marca no idioma de cada entrada) | G4, G2, G5 |
| Unicidade (+ soft delete) | CT-01 (F-nn em duas linhas), CT-08 (classe em duas peças). Soft delete não se aplica: não há banco | G1, G2 |
| CRUD combinado | não se aplica: não há CRUD | — |
| Mass assignment | não se aplica: não há formulário nem payload | — |
| Upload | não se aplica: não há upload | — |
| Precisão monetária | não se aplica: não há valor monetário | — |
| **Superfície Livewire** | não se aplica: o `02` declara que nenhuma página, widget ou componente é criado ou alterado, e o plano não tem path em `app/` | — |
| **Estado do framework usado sem validar** | lacuna declarada: o hash da URL do visualizador (`#focus=`, `#route=`) é entrada do leitor, mas quem o lê é o código do archify, não a entrega. Nenhum cenário da entrega o falsifica. Fica registrado para a dimensão de segurança do quality gate | G4 |
| **IDOR e mass assignment por entidade** | não se aplica: a entrega não persiste entidade | — |
| **Escopo com discriminante nulo** (o modo da peça, da ligação e do diagrama) | CT-11, CT-12, CT-19, CT-37, CT-38, CT-43 e CT-44: o modelo não dá a um modo o que o painel não registra nele, a ligação desenhada vale no modo do diagrama, os diagramas de arquitetura de cada modo desenham o registro desse modo, a célula não leva a diagrama do outro modo, e o caminho com organização resolve com tenant. A condição da peça está estacionada na Q11 (ADV-31); a marca publicada é conferida pela R37. *(alterado em 2026-11-23 — Adendo 2: destravada — a condição da peça é conferida nos dois modos (CT-32, CT-56), a peça só entra no diagrama do modo em que abre (CT-33), e a marca está em todo diagrama que a desenha (CT-57); a peça sem condição não ganha marca, e a que só pede permissão ou esconde o menu não ganha condição. A peça fora do registro de painel e a configuração que não é interruptor ficam com a Q28 e a Q27)* *(alterado em 2026-11-23 — Adendo 3: as duas entram — o `CommandRecordResource` pela configuração de pacote e o `AcessosPorPainel` pela herança, nos CT-32, CT-33, CT-56 e CT-57 —, e plugin e middleware ficam sem condição nem marca (CT-32, CT-57). Ficam fora o componente flutuante ligado a chave (Q30) e a peça que herdaria sem método próprio — lacuna declarada, R43-M6)* *(alterado em 2026-11-23 — Adendo 4)* O componente flutuante entra, pela RQ-33 (CT-32, CT-33, CT-56, CT-57, CT-B01); fica fora só a lacuna R43-M6. Nas ligações de uso, o modo vem da varredura: lacuna declarada, vinculada à P-05 ("sem promessa de que a varredura achou todas") | G2, G3, G5 |
| **Saída do estado de erro** | não se aplica: nenhum cenário termina em 4xx nem em redirect. Os links da página ficam com `site/verifica-links.mjs` e com o CT-15 | — |
| Cardinalidade 0/1/N | CT-06 (feature sem peça), CT-14 (ligação sem diagrama), CT-17 (célula sem diagrama), CT-29 (peça registrada sem F-nn), CT-39 (transição sem âncora), CT-41 (classe de `app/` sem F-nn), CT-B02 (diagrama de ligações sem prova), CT-B05 (amostra vazia); *(step 9)* CT-46 (seta de um verbo e de vários), CT-47 (caixa de 0, 1 e N membros no modo do diagrama); *(alterado em 2026-11-23 — Adendo 2)* CT-32 (condição sem parte nenhuma, com uma e com duas) | G2, G4, G5 |
| Leitura de `docs/`, README, `CHANGELOG.md` ou `site/` fora da árvore do kit (item vivo do projeto, `.ai/rules/testes.md`) | sentinela `naArvoreDoKit()` no `beforeEach` dos dois arquivos (Setup Global), vigiada pelo `[CT-10]`/`[CT-11]` herdados de `RedeDeDocumentacaoTest` | G1, G2, G3 |
| *(linha nova em 2026-11-21 — step 9)* Ferramenta de linha de comando que apaga, sobrescreve ou sai com 0 calada (item vivo do projeto: os achados A2, A4 e A6) | CT-48 (a pasta do autor não some), CT-49 (o arquivo do diagrama não muda de bytes), CT-51 (pelo link, o mesmo código do caminho real) | G5 |

## Cogitado e cortado

| Cenário cogitado | Por que foi cortado |
|---|---|
| rota entre nós, lente e exportação no visualizador | são recursos do archify; testá-los é testar o archify. O "não interativo" já morre no CT-B03 |
| troca de tema com o diagrama já aberto | a P-11 pede a amostra por tema, e o CT-B05 abre cada tema pelo mecanismo do visualizador. A troca com o diagrama aberto não tem cláusula |
| o comparativo no tema claro, na amostra de acessibilidade | a P-11 pede o comparativo só no tema escuro |
| volta do diagrama para a página do mapa | o `00` não pede, e nenhum mutante de requisito morre aí |
| "o build não roda o script de posicionamento" | posição não é oráculo, e nenhum mutante de requisito morre aí. *(alterado em 2026-11-21 — step 9)* O argumento e a entrada do posicionador têm CT pela P-14 (CT-49, CT-50); a posição continua sem |
| guarda de F-nn em `tests/` e `app/` | ali "F-nn" é nome sobrecarregado (`.ai/rules/kit.md`: "F-01 da conformidade Blueprint"), e o falso positivo é certo |
| "`app/` não mudou" (RQ-15) | não sobrevive ao merge; o gate confere o diff |
| o delta do comparativo no HTML (o `/app` mostra `DefinirTenantDePermissoes` como acrescentado) | seria mais um CT-B. O par certo de antes e depois (CT-22) já mata a inversão e a mistura, e o delta é cálculo do archify |
| busca sem acento acha peça com acento | recurso do archify |
| o README mostra uma imagem do mapa | o `00` só pede o lugar, e o link basta (CT-26) |
| todo F-nn citado em `tests/Browser/RoteiroDoKitTest.php` existe no catálogo | mata o mesmo mutante que o CT-03: a F-03, a F-13, a F-45, a F-51 e a F-52 da base não podem trocar de feature |
| a marca da peça condicional e o diagrama de modo em que ela entra (a R30, o CT-32, o CT-33, a metade de condição do CT-B01 e o R24-M5) | estacionado na Q11 (ADV-17, ADV-31): nenhuma afirmação sobre a marca vale nas três leituras. Volta com a resposta. *(alterado em 2026-11-23 — Adendo 2: voltou — R30, R41, R42, o passo 4 do CT-B01 e o R24-M5; já não é corte)* |
| peça que só some do menu (`shouldRegisterNavigation()`) tratada como condicional | a peça que só some do menu continua aberta pela URL, e por isso não é de disponibilidade condicional. Vale quando a R30 voltar da Q11. *(alterado em 2026-11-23 — Adendo 2: vale — a P-16 decide, e o CT-32 tem a linha do `Dashboard` do `/admin`, que some do menu sem `KIT_DASHBOARD_DINAMICO` e abre)* |
| *(alterado em 2026-11-23 — Adendo 2)* partir em dois todo diagrama do `/admin`, inclusive o igual nos dois modos | a P-09, no que a RQ-29 não substituiu, manda publicar uma vez o que é igual; a R18 reprova o igual duplicado (CT-19). A RQ-29 se cumpre com o `/admin` tendo diagramas só sem e só com tenant, e o `TenantResource` só nos de com (CT-33) |
| *(alterado em 2026-11-23 — Adendo 2)* a marca no comparativo | a casca do archify não tem card, e desenha as peças condicionais do resumo do `/app` só dentro das caixas de agrupamento (medido: o `app-resumo-sem` desenha 1 peça e 7 agrupamentos). A marca está nos dois diagramas que ele compara, publicados ao lado (CT-57) |
| *(alterado em 2026-11-23 — Adendo 2)* a disponibilidade com tenant avaliada na suíte do kit, ligando `kit.tenancy.enabled` em tempo de execução | o modo é decidido no boot (o `AppPanelProvider` registra o `/app` com ou sem `/{tenant}`); ligar a chave depois do boot só imita o modo nas peças cujo `canAccess()` lê a chave na hora. A metade "com" mora na suíte `Tenancy` (CT-56) |
| *(alterado em 2026-11-23 — Adendo 2)* a peça que só abre com duas chaves ligadas juntas | não existe no HEAD (medido: toda peça condicional pela chave abre com uma só). Se aparecer, o CT-32 a reprova com a chave da condição ligada sozinha — vermelho, e não silêncio —, e a sessão decide. *(alterado em 2026-11-23 — Adendo 4)* Apareceu, pela RQ-33: o chat do assistente. A guarda liga juntas as chaves da condição, e a linha do CT-32 com uma chave só é a que reprova; já não é corte |
| *(alterado em 2026-11-23 — Adendo 2)* o plugin e o middleware de feature desligada como peça condicional | o discriminante que a sessão fixou não os vê; perguntado (Q29), com a recomendação de que não são condicionais. *(alterado em 2026-11-23 — Adendo 3: respondido — não, pela RQ-32; o CT-32 recusa a condição neles e o CT-57, a marca)* |
| *(alterado em 2026-11-23 — Adendo 3)* um cenário próprio para a R43 | a população é a entrada da mesma guarda: as linhas da R43 moram no CT-32, no CT-33, no CT-56 e no CT-57, que já avaliam a condição, o lugar e a marca. Um cenário à parte repetiria a guarda inteira para mudar só a entrada |
| *(alterado em 2026-11-23 — Adendo 3)* o componente flutuante ligado a chave — o chat do assistente e o checklist do tutorial — na população | nenhuma das RQ-27, RQ-31 e RQ-32 o alcança; perguntado (Q30). As duas leituras o desenham nos dois modos, e por isso o lugar dele não muda até a resposta. *(alterado em 2026-11-23 — Adendo 4)* Já não é corte: respondido pela RQ-33 (Q30), entra na R43 |
| a ordem entre transições ancoradas em arquivos ou métodos diferentes | a guarda estática não reconstrói a chamada em tempo de execução (R35-M4, lacuna declarada). A ordem no mesmo método tem guarda (CT-39) |
| o oráculo do "onde fica" que não traz caminho sem "→" (34 linhas na base: 4 com "→" e 30 sem caminho) | não há peça que a guarda resolva sem julgamento (R6-M8, lacuna declarada); fica para o quality gate |
| a contagem "N de saída · M de entrada" do passaporte | é cálculo do archify, a partir das arestas que o diagrama já tem; a R36 confere as arestas |
| um diagrama de arquitetura por modo para todo painel | a P-09 manda publicar uma vez o que é igual nos dois modos; a R34 cobra o registro de cada modo, e só o `/app` muda *(alterado em 2026-11-23 — Adendo 2: o que muda é o registro disponível, e ele muda no `/app` e no `/admin`; o `/infra` fica com os compartilhados)* |
| o executor não faz merge nem tag sem o OK do mantenedor (ADV-34) | controle de processo da RQ-26, conferido no quality gate e no PR (Cláusulas sem regra) |
| o `archify check` como oráculo da biblioteca embutida | medido pela sessão: ele só acusa alteração do HTML com o recibo `.delivery.json`, que o `render` não grava (D32) |
| "sem reticências" no rótulo de peça | o esqueleto escreve "…" à mão em 33 rótulos para caber na caixa; o rótulo é conferido por igualdade com o do arquivo (CT-B01), e o "…" do item de card, contra o modelo (CT-B02) |
| uma peça de pacote em cada `architecture` da amostra do CT-B03 | o `app-resumo-sem` e o `app-resumo-com` do esqueleto não têm peça de pacote, nem, no commit `4adaf9a`, o `app-organizacoes`, o `app-resumo-sem` e o `app-resumo-com`; a amostra pede uma de pacote no conjunto dos `architecture` |
| contar como "lista de features" os nós de diagrama cujo rótulo é nome de F-nn | o `infra-registro` tem 3 nós assim, e são peças; o ADV2-21 conta item de lista, linha de tabela e item de card |
| a F-nn à vista no carregamento do mapa de arquitetura, sem zoom | o archify desenha o `tag` como detalhe fino, com opacidade 0 no nível de leitura do carregamento (medido no commit `4adaf9a`). O CT-B01 confere no nível cheio, como a D29 decidiu; se a F-nn tiver de aparecer sem zoom, muda a D29, e o ponto foi ao coordenador |
| o título no `document.title` | é a moldura do archify ("Diagrama {título}", "{title} Diagram"); o título que o leitor vê é o `<h1>`, e é ele que o CT-B01 confere |
| a forma do desenho sem JavaScript | o archify grava o SVG pronto no HTML; o que depende do script é a interface do visualizador, e o CT-B01 confere que ela montou |
| token obrigatório na linha nova do catálogo | medido no commit `0f38e2f`: a F-118 ("Voltar ao topo") não tem token e está certa; a troca entre linhas novas sem token é a lacuna R1-M8 |
| *(step 9)* a linha do `#L` "fora de comentário" no CT-B03, para o achado A3 | o CT-52 mata o mesmo mutante na suíte do kit, que roda em todo PR, sobre toda prova que o gerador projeta, e não sobre a amostra do CT-B03 |
| *(step 9)* o traço da seta e a caixa de agrupamento conferidos no HTML construído (achados A1 e A5) | o desenho leva a variante que o gerador entrega como a classe `a-{variante}` (medido), e a caixa é texto da entrada: o CT-46 e o CT-47 provam o mesmo na suíte do kit, mais barato e em todo PR |
| *(step 9)* o conferidor rodado de ponta a ponta de uma cópia numa pasta com espaço e acento (achado A12) | dobra o tempo do job `site`; o controle de caminho do CT-B06 mata o mesmo mutante pela mesma função. Decidido pela sessão (Q25) |
| *(step 9)* os achados A2, A6 e A11 rodados na árvore do kit, sem clone | sob o defeito, o gerador apaga a pasta e o posicionador regrava o arquivo do diagrama; e um JSON quebrado plantado em `site/mapa/diagramas/` derrubaria as outras guardas da suíte, que leem a pasta em paralelo. O clone descartável da G5 isola os três |
| *(step 9)* controles para o CT-30 e o CT-44 da suíte `Tenancy`, que o docblock do arquivo promete | decidido pela sessão (Q26): o docblock muda para dizer o que os datasets têm; o CT-55 e a linha nova do CT-38 cobrem o achado A15 |
| *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* a linha do lado válido no CT-50 — a condição certa aceita pelo gerador | no clone da G5, a condição válida faz o gerador baixar o archify, e a linha não sairia do teste rápido; a recusa a mais morre na linha "publicados" do CT-57, que monta a entrada de todo diagrama e reprova a que volta com erro (R40-M25) |
| *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)* a ligação de um modo só na árvore compartilhada como linha do CT-37 ou do CT-38 (R34) | o CT-37 e o CT-38 montam os diagramas pelo mesmo construtor da guarda e comparam com o registro vivo, que não tem a ligação da cópia: a linha mataria os mesmos dois mutantes da linha do CT-14 (R12-M9 e M10), com um veredito que depende do registro vivo, e não do modo da ligação *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02: vale para o CT-37; o CT-38 monta com a própria função da suíte `Tenancy`, `mapaTenancyLigacoesDesenhadas`, que tem o defeito à parte, e ganhou a linha — com uma peça que o `/infra` registra com tenant, para o veredito depender só do modo da ligação; R34-M6)* |
| *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* uma linha no CT-19 (R18) para o mapa compartilhado com a ligação de um modo só | a guarda do CT-19 (`tests/Kit/MapaInterativoDiagramasTest.php`, `mapaDiagramasConferirModos`) já decide o diagrama de dois modos por cada modo dele — a ligação desenhada tem de valer nos dois, e a peça, ser registrada nos dois —, e não tem cópia do primeiro modo; o que ela lê como desenhado vem do construtor da guarda, cujo defeito morre no CT-14 (R12-M12 e M13). Com o construtor de hoje, ela reprovaria o mundo da ligação de um modo só, falha fechado, e não aberto: a linha não discriminaria a correção |
| *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* uma linha no CT-B02 (`05`) para o `esperadoDoMapa` do conferidor, que espera no mapa compartilhado a ligação que vale em algum modo dele | o defeito falha fechado: com o gerador certo, o CT-B02 ficaria vermelho na primeira ligação de um modo só num mapa compartilhado, e hoje não há nenhuma (0 de 281). O gerador que a desenhasse, com o conferidor igual a ele, morre no CT-14 (R12-M11). O `05` registra o alinhamento na nota do esperado, como no A2 |
| *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* linhas da D43 no CT-33 | o CT-33 e o CT-57 reconhecem o nó escrito à mão pelo mesmo código (o ramo do escrito à mão de `mapaDiagramasConstruir`, com `pecasDoNoEscritoAMao` do `tests/Pest.php`, a mesma da R37 *(alterado em 2026-11-23 — step 9 do Adendo, a função de prova do arquivo saiu, e a D43 mora num helper só)*), e as linhas do CT-57 matam os mutantes dele; no dado publicado, nenhum nó escrito à mão muda de peça condicional com a D43 (Setup Global) |
| *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)* a linha do card com o rótulo de um nó homônimo sem prova | a D43 não decide o que se aceita a mais: pergunta Q?6, de raia desenho, com a recomendação de reprovar; a célula fica sem linha até a decisão *(alterado em 2026-11-23 — step 9 do Adendo, D44: decidida pela sessão; a célula tem a linha no CT-57 e no CT-45, e deixou de ser corte)* |
| *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* o rótulo secundário da raiz da árvore em linhas do CT-14 (R12) ou do CT-43 (R17) | o CT-14 procura ligações, e a raiz não é ligação: a linha seria exemplo fora da regra; o CT-43 lê a grade da página, e o rótulo da raiz é texto do diagrama, que só a entrada do gerador traz. A guarda do CT-19 confere o modo de cada diagrama, e as linhas moram nele |
| *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* o rótulo secundário da raiz conferido no HTML construído (CT-B01, no `05`) | o rótulo secundário que o gerador entrega é o texto que o archify desenha, e o CT-19 o prova na entrada, na suíte do kit, mais barato e em todo PR. Na árvore, o rótulo secundário dos nós de peça fica à vista no carregamento, com opacidade 1 (medido com a F-nn, R6 e CT-B01); o da raiz não foi medido à parte |

## Índice de Cenários

*(alterado em 2026-11-20 — guardas do passo 8: os testes do G1 e do G2 ficaram em quatro arquivos por área, um dono por arquivo; os CT são os mesmos)*

| ID | Cenário | Regra | Técnica | Grupo | Costura | Arquivo | Mata |
|----|---------|-------|---------|-------|---------|---------|------|
| CT-01 | catálogo em paridade, a mesma feature e o mesmo estado em cada número (tokens nas linhas novas), uma linha por F-nn, origem do glossário | R1 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R1-M1 a M7 |
| CT-02 | só se cita F-nn que o catálogo tem, e ninguém lista feature por fora dele, nem só pelos nomes | R2 | EP + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R2-M1 a M5 |
| CT-03 | número da base fica com a feature, ou removido com motivo, e não volta | R3 | estado × operação | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R3-M1 a M6 |
| CT-04 | os novos começam na F-72, em sequência, sem sufixo | R4 | BVA 3-valores | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R4-M1, M2, M3 |
| CT-05 | README da base e o publicado, e todas as páginas do site, chegam ao catálogo; os seis itens novos e os itens novos do README, pelo termo | R5 | EP por fonte | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R5-M1, M2, M4, M6, M9 |
| CT-06 | toda F-nn é entregue por peça desenhada da sua origem, e todo caminho do "onde fica" resolve sem tenant | R6 | EP por origem | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R6-M1, M2 |
| CT-07 | a rule está no índice, alcança onde a feature nasce e manda o número só crescer | R7 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R7-M1 a M4 |
| CT-08 | toda peça existe, tem o tipo da sua classe e aparece uma vez | R8 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R8-M1 a M6 |
| CT-09 | toda prova resolve no commit que publica, fora de comentário pelo tipo de arquivo, sem número de linha; todas as ligações conferidas | R9 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R9-M1 a M8, R9-M11 *(step 9: A10)*, R13-M3 |
| CT-10 | a prova é a da ligação: arquivo da origem, trait que ela usa, provider, método da lista de contrato, par de mapeamento ou arquivo do kit que liga a peça de pacote | R10 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R10-M1 a M12, R10-M13 a M16 *(step 9: A9)* |
| CT-11 | sem tenant: registro (com as páginas da configuração) → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M4, M5, M6 |
| CT-12 | com tenant: registro → modelo, e o modo do modelo → registro | R11 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R11-M3, M4 |
| CT-13 | a guarda do registro reprova esquecer (inclusive a página da configuração), sobrar e trocar de modo | R11 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R11-M1, M2, M6 |
| CT-14 | toda ligação desenhada em diagrama publicado; só aresta conta, e o agrupamento e a porta nomeiam os membros; a ponta conta só no modo em que fica disponível *(alterado em 2026-11-23 — Adendo 2)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)*: e a árvore compartilhada só desenha a ligação de registro que vale nos dois modos, montada pelo gerador e pela guarda *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)*: e o mapa compartilhado, só a ligação que vale nos dois modos, entre pontas registradas nos dois | R12 | EP + controles | G2 (as duas linhas da árvore compartilhada também na forma de leitura da G5) | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R12-M1 a M6, M8 *(alterado em 2026-11-23 — Adendo 2: M8)*, R41-M4, R12-M9, M10 *(alterado em 2026-11-23 — step 9 do Adendo, achado A2)*, R12-M11 a M13 *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* |
| CT-15 | a página de cada idioma leva, por `<a href>` relativo, a todo diagrama, e só aos que existem | R15 | EP | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R15-M1 a M5 |
| CT-16 | textos de diagrama em pt e en, traduzidos: termo do glossário com o en dele, frase sem palavra do pt; título igual só se é nome de peça ou de comando, marcado | R16 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R16-M1 a M7 |
| CT-17 | grade painel × modo inteira, compartilhado marcado e só ele, sem diagrama do outro modo nem de outro painel | R17 | tabela de decisão | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R17-M1, M3, M4, M5, M7 |
| CT-18 | painel novo sem diagrama reprova a grade | R17 | controle | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R17-M2 |
| CT-19 | só se compartilha o que é igual, o igual não se duplica, e a ligação desenhada vale no modo do diagrama; a disponibilidade é diferença de estrutura *(alterado em 2026-11-23 — Adendo 2)* *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)*: e a raiz da árvore de registro diz o modo do diagrama, "os dois modos" na compartilhada, montada pelo gerador | R18 | tabela de decisão; EP do `modos` da árvore *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | G2 (as três linhas da raiz da árvore também na forma de leitura da G5) *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R18-M1 a M4, M7 *(alterado em 2026-11-23 — Adendo 2: M7)*, R18-M8 a M11 *(alterado em 2026-11-23 — step 9 do Adendo, S-AD-01)* |
| CT-20 | cada tipo e o comparativo têm uso ou motivo | R19 | produto cartesiano | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R19-M1 |
| CT-21 | toda célula tipo × painel × modo tem diagrama ou motivo traduzido, e só o diagrama sem peça registrada é do kit inteiro | R19 | produto cartesiano + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R19-M2 a M8 |
| CT-22 | comparativo do mesmo painel, de sem para com tenant, entre diagramas do mapa publicados | R20 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R20-M1, M2, M3 |
| CT-23 | nenhuma dependência nova de visualização nos quatro campos do `site/package.json`, e `site/mapa/` só com os arquivos do mapa | R21 | EP (lista fechada) + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R21-M1, M2, M4, M6, M7 |
| CT-24 | há fluxo publicado, e todo passo de `workflow`, `sequence` e `dataflow` é peça do modelo | R22 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R22-M1, M2 |
| CT-25 | o mapa nasce no build, o conferidor do mapa roda depois do de acessibilidade e antes do envio, e o build do Pages só roda na `main` | R23 | EP por workflow | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R23-M1 a M4 |
| CT-26 | o README de cada idioma leva ao mapa do mesmo idioma | R25 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R25-M1, M2 |
| CT-27 | os blocos Mermaid da base ficam onde estavam | R26 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R26-M1, M2, M3 |
| CT-28 | todo pacote instalado está na referência e chega ao catálogo, ou à lista dos sem feature; a referência só lista pacote instalado | R27 | EP por partição de pacote + controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R27-M1 a M6 |
| CT-29 | sem tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M1, M2, M3, M6 |
| CT-30 | com tenant, toda peça registrada entrega F-nn ou está na lista com o motivo | R28 | tabela de decisão | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R28-M2 |
| CT-31 | marcos da linha do tempo batem com o CHANGELOG, com o título visível na seção da versão, e todo tempo tem data e fonte | R29 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R29-M1 a M7 |
| CT-32 | sem tenant, toda peça registrada indisponível guarda no modelo a condição que a explica, com a âncora que resolve, e a peça disponível não guarda condição *(alterado em 2026-11-23 — Adendo 2: destravado; estava estacionado na Q11, ADV-17 e ADV-31)* *(alterado em 2026-11-23 — Adendo 3)*: inclusive o `CommandRecordResource`, pela configuração de pacote, e o `AcessosPorPainel`, fora do registro; plugin e middleware sem condição *(alterado em 2026-11-23 — Adendo 4)*: e o chat do assistente, com as duas chaves juntas, e o checklist do tutorial | R30, R43 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R30-M1 a M5, M7, R37-M3, R43-M1 a M4 *(alterado em 2026-11-23 — Adendo 3)*, R43-M7, M8 *(alterado em 2026-11-23 — Adendo 4)* |
| CT-33 | nenhum diagrama desenha a peça condicional num modo em que ela fica indisponível, e o `/admin` tem diagramas separados, com o `TenantResource` só nos de com *(alterado em 2026-11-23 — Adendo 2: destravado; era da R30 e juntava a marca, que foi para o CT-57)* *(alterado em 2026-11-23 — Adendo 3)*: inclusive o `AcessosPorPainel`, só com tenant, e o `CommandRecordResource`, nos dois modos *(alterado em 2026-11-23 — Adendo 4)*: o chat fica nos dois modos, com as duas chaves juntas | R41, R43 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R41-M1, M2, M4, M5, R43-M3 *(alterado em 2026-11-23 — Adendo 3)*, R43-M10 *(alterado em 2026-11-23 — Adendo 4)* |
| CT-34 | a página do mapa declara as duas exceções da P-11, a do comparativo e o limite da varredura, nos dois idiomas | R33 | EP | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R33-M1 a M5 |
| CT-35 | a guarda das fontes reprova o item casado com a feature errada, o item novo sem F-nn, a página escondida na lista e o motivo de enfeite | R5 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoCatalogoTest.php` | R5-M3 a M10 *(M10, step 9: A13)* |
| CT-36 | a guarda da origem reprova a F-nn entregue pela peça errada, a removida que ainda é entregue e o caminho que não resolve | R6 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R6-M3 a M7, M9 |
| CT-37 | sem tenant, os diagramas de arquitetura e a árvore de registro de cada painel desenham o registro dele que fica disponível nesse modo, com a chave ligada *(alterado em 2026-11-23 — Adendo 2: era "fora a peça condicional")* | R34 | tabela de decisão + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R34-M3, M5, R41-M2, M3 *(alterado em 2026-11-23 — Adendo 2: M5, R41-M2 e M3)* |
| CT-38 | com tenant, os diagramas de arquitetura e a árvore de registro de cada painel desenham o registro dele que fica disponível nesse modo, inclusive o `TenantResource` *(alterado em 2026-11-23 — Adendo 2)* *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)*: e o compartilhado não conta a ligação de registro de um modo só | R34 | tabela de decisão + controles | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R34-M1, M2, M4 *(M4, step 9: A15)*, R41-M4, R34-M6 *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-02)* |
| CT-39 | toda transição de fluxo tem âncora que resolve, todo estado de `lifecycle` também, e a ordem no mesmo método é a do código | R35 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R35-M1, M2, M3, R22-M3 |
| CT-40 | toda aresta de mapa é ligação do modelo no mesmo sentido, e o diagrama de painel só desenha, como peça, o que é dele, do kit inteiro ou porta | R36 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R36-M1 a M5 |
| CT-41 | toda classe de `app/` fora de `app/Filament` entrega F-nn ou está na lista com o motivo, e comando `kit:*` nunca está na lista | R28 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R28-M4 a M7 |
| CT-42 | a falha do conferidor do mapa reprova o job, e o build do Pages só roda na `main` | R23 | controles | G1 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R23-M5, M7 |
| CT-43 | a guarda da grade reprova a célula com diagrama do outro modo ou de outro painel, e a marca de compartilhado num diagrama de um modo só | R17 | controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R17-M3, M6, M7 |
| CT-44 | com tenant, todo caminho do "onde fica" com organização resolve no roteador | R6 | EP (idioma) | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R6-M9 |
| CT-45 | toda marca de disponibilidade publicada é a do código, conferida contra o `canAccess()` ou o `canView()` da peça: chave, valor de fábrica, modo ou os dois *(alterado em 2026-11-23 — Adendo 2: o `canView()` e a marca dupla)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*: com o nó do card designado pela D43 *(alterado em 2026-11-23 — step 9 do Adendo, achado A7)*: nas duas colunas do card | R37 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R37-M1, M2, M3, R37-M4 *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*, M5 *(alterado em 2026-11-23 — step 9 do Adendo, achado A7)* |
| CT-46 | cada seta do mapa de arquitetura leva ligações de um verbo só, com o traço desse verbo, e cada par de pontas tem uma seta por verbo *(step 9: A1; Q22)* | R38 | EP + controles | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R38-M1, M2, M6 |
| CT-47 | a caixa de agrupamento diz quantas peças nomeia no modo do diagrama, no número certo, e a caixa vazia não é desenhada *(step 9: A5)* | R38 | BVA (0, 1, N) + controles | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R38-M3, M4, M5 |
| CT-48 | com `--saida` e sem slug, o gerador recusa, antes de qualquer efeito, a pasta que tem o que ele não gerou, e não apaga nada *(step 9: A2)* | R39 | EP (pasta de saída) | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R39-M1, M2 |
| CT-49 | o argumento sem valor válido, desconhecido, a mais ou ausente é recusado antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o argumento *(step 9: A6; Q24; step 11: QA-04)* | R40 | EP | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R40-M1, M2, M5, M6, M15 a M18 |
| CT-50 | o arquivo de entrada que a ferramenta não pode usar — JSON quebrado, peça que não está no modelo, ou prova que não resolve — é recusado antes de qualquer efeito, com a mensagem `[mapa]` que nomeia o culpado *(step 9: A11; re-revisão: RR-01, RR-02; step 10: a P-14 sobre a projeção)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)*: e a condição do modelo que o gerador não pode usar, e a ligação de registro com a ponta fora do modelo *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achados AD2-01 e AD2-03)*: inclusive o modo em lista, a fonte "nova" que só muda o namespace ou ganha `::class` e a chave fora de `KIT_*`; e o arquivo de diagrama com `modos` vazio, repetido ou fora de `sem`/`com` | R40 | EP | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R40-M3, M4, M5, M7 a M14, R40-M19 a M24 *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)*, R40-M26 a M29, M33 a M35 *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achados AD2-01 e AD2-03)* |
| CT-51 | o gerador chamado por um caminho que passa por um link faz o mesmo que pelo caminho real, e não termina com 0 calado *(step 9: A4)* | R39 | EP (caminho da chamada), relativo ao caminho real | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoPublicacaoTest.php` | R39-M3 |
| CT-52 | a linha que o build dá a cada prova é a do código: o trecho está fora de comentário nela *(step 9: A3; re-revisão: RR-03)* | R9 | EP + controles | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R9-M10, M13 |
| CT-53 | toda âncora do mapa resolve na suíte do kit: a evidência de cada peça e toda âncora dos diagramas escritos à mão *(step 9: A7)* | R9 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R9-M9, M12 |
| CT-54 | todo id que um arquivo de diagrama construído nomeia — peça ou membro de agrupamento — é peça do modelo *(step 9: A8)* | R36 | EP + controles | G2 | Pest feature HTTP | `tests/Kit/MapaInterativoTest.php` | R36-M6 |
| CT-55 | com tenant, a guarda do registro reprova a sobra de qualquer tipo, a falta e o painel trocado *(step 9: A15)* | R11 | controles | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R11-M7 |
| CT-56 | com tenant, toda peça registrada indisponível guarda no modelo a condição que a explica, e a peça que a condição dá ao modo com tenant está disponível nele *(alterado em 2026-11-23 — Adendo 2: ADV-17)* *(alterado em 2026-11-23 — Adendo 3)*: inclusive o `CommandRecordResource` e o `AcessosPorPainel` *(alterado em 2026-11-23 — Adendo 4)*: e os dois componentes flutuantes | R30, R43 | tabela de decisão + controles | G3 | Pest feature HTTP | `tests/Tenancy/MapaInterativoTenancyTest.php` | R30-M1, M5, M6, R43-M1 *(alterado em 2026-11-23 — Adendo 3)*, R43-M7 *(alterado em 2026-11-23 — Adendo 4)* |
| CT-57 | em todo diagrama que a desenha, a peça condicional leva a marca da condição, no idioma do diagrama e à vista no carregamento, e a peça sem condição não leva marca *(alterado em 2026-11-23 — Adendo 2)* *(alterado em 2026-11-23 — Adendo 3)*: na redação decidida pela sessão, sem marca em plugin e middleware, e com o card "Condições de disponibilidade" *(alterado em 2026-11-23 — Adendo 4)*: e no chat, com as duas chaves, e no checklist, inclusive nos diagramas escritos à mão, pela prova da classe *(alterado em 2026-11-23 — step 11)*: neles, na linha do card "Condições de disponibilidade" (P-17) *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)*: a lista com "vazia" / "empty"; *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)*: a marca reconhecida pela redação decidida inteira; *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)* *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*: o nó escrito à mão pela D43 | R42, R43, R44 | EP + controles | G5 | Pest feature HTTP | `tests/Kit/MapaInterativoDiagramasTest.php` | R42-M1 a M6, R43-M4, M5, R44-M1 a M3 *(alterado em 2026-11-23 — Adendo 3)*, R43-M7 a M9 *(alterado em 2026-11-23 — Adendo 4)*, R42-M7, M8 *(alterado em 2026-11-23 — step 9 do Adendo, achado A3)*, R42-M9, M10 *(alterado em 2026-11-23 — step 9 do Adendo, achado A4)*, R42-M11, M12, R37-M4 *(alterado em 2026-11-23 — step 9 do Adendo, achado A6)*, R43-M11 *(alterado em 2026-11-23 — step 9 do Adendo, achado A5)*, R40-M25 *(alterado em 2026-11-23 — step 9 do Adendo, achado A1)* |
| CT-B01 | cada diagrama, inclusive o comparativo, abre com as peças e os rótulos da fonte, a interface do visualizador montada, a F-nn no desenho da peça e a marca da peça condicional à vista *(alterado em 2026-11-23 — Adendo 2)*, sem erro nem exceção, sem recurso de fora (no `05`) *(alterado em 2026-11-23 — Adendo 3)*: e o card "Condições de disponibilidade" à vista *(alterado em 2026-11-23 — Adendo 4)*: e a marca dos dois componentes flutuantes | R24, R21, R6, R44 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R24-M1 a M6, M8 *(M8, step 9: A14)* *(alterado em 2026-11-23 — Adendo 2: M5 destravado)*, R21-M5, R6-M10, R44-M4 *(alterado em 2026-11-23 — Adendo 3)* |
| CT-B02 | cada ligação desenhada tem o seu item de prova inteiro num card do painel lateral, contado no DOM, e as arestas do DOM são as da fonte; fora o comparativo (no `05`) | R13, R12 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R13-M1, M2, M4 a M9 *(M9, step 9: A14)*, R12-M7 |
| CT-B03 | o leitor busca a peça, e o passaporte mostra as provas dela, com a linha do trecho no commit do build, numa amostra por `architecture`; sem erro na interação (no `05`) | R14, R24 | EP + amostra | G4 | browser | `site/verifica-mapa.mjs` | R14-M1 a M6, R24-M7 |
| CT-B04 | a interface do visualizador fala o idioma da página, com critério no pt e no en, também com a busca e o passaporte abertos e sem erro; só a casca do comparativo fica em inglês (no `05`) | R31, R24 | rastreio exaustivo | G4 | browser | `site/verifica-mapa.mjs` | R31-M1 a M6, M11 *(M11, step 9: A16)*, R24-M7 |
| CT-B05 | a amostra de acessibilidade cobre cada tipo, idioma e tema, e o comparativo no escuro (no `05`) | R32 | amostra estratificada | G4 | browser | `site/verifica-mapa.mjs` | R32-M4, M5, M6 |
| CT-B06 | a tolerância reconhece os dois defeitos pelo alvo, reprova o resto e a violação de alvos mistos, e a reprovação vira código de saída (no `05`) | R32, R23 | controle sintético | G4 | browser | `site/verifica-mapa.mjs` | R32-M1, M2, M3, M7, R23-M6, M8 *(M8, step 9: A12)* |
| CT-B07 | o catálogo pt que o kit entrega — as 390 chaves embutidas em cada HTML pt e as 96 de render, na fonte — não copia o texto en, fora a lista fechada das 14 chaves medidas e das só de marcadores (no `05`) | R31 | controles | G4 | browser | `site/verifica-mapa.mjs` | R31-M7 a M10 *(M10, step 9: A13)* |
| CT-B08 | o conferidor, rodado como processo sobre uma pasta de diagramas com um JSON quebrado, reprova com a mensagem `[mapa]` que nomeia o arquivo e termina com código diferente de zero (no `05`) *(step 9: A11)* *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2, achado AD2-01)*: e recusa, pela função que lê o modelo real, a condição com o modo em lista, com a fonte "nova" que só muda o namespace ou ganha `::class`, e com chave fora de `KIT_*` | R40 | EP + controle sintético | G4 | browser | `site/verifica-mapa.mjs` | R40-M3, R40-M30 a M32 *(alterado em 2026-11-24 — step 9 do Adendo, rodada 2)* |
