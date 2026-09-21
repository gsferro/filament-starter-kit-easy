# Requisito — Host local no final do `kit:install`

## Fonte

- **Origem**: pedido do usuário no chat, invocando a skill `feature-wiki`
- **Data**: 2026-09-21
- **Autor / solicitante**: guilhermeferro@fiotec.fiocruz.br (mantenedor do kit)
- **Fidelidade**: **alta** — texto escrito, colado diretamente

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos adicionar a opção no final do comando "kit:install" de colocar no host do windowns o dns sugerido para quando for rodar local
> - veja se precisa do paramentro "--force" ou se numa instalação normal também faz sentindo
> - pergunta se deseja adicionar o host para rodar local, colocando um exemplo: "http://staterkit.test"
> - se sim, pergunte qual url ele gostaria, mas deixe como sugestão de acordo com o nome que ele escolheu anteriormente
> - rode, conforme na documentação no @..\README.md a parte de criar o host no windows
> - faça o ajuste no .env em APP_URL com a nova url que foi rodado no host
> - adicione essa informação na documentação de instalação
> - crie uma branch especifica para rodar essa wiki
> - use subagents e faça commits individualizados
> - abra o PR e quando ele estiver aprovado, faça o merge na main + tag + release

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | O `kit:install` ganha uma etapa **no final** do fluxo que oferece cadastrar o host local | "adicionar a opção **no final** do comando \"kit:install\" de colocar no host do windowns o dns sugerido" | funcional |
| RQ-02 | A etapa **pergunta** se o usuário deseja adicionar o host, e essa pergunta exibe um **exemplo** de URL | "pergunta se deseja adicionar o host para rodar local, colocando um exemplo: \"http://staterkit.test\"" | funcional |
| RQ-03 | Se a resposta for **não**, o fluxo termina sem tocar em hosts nem em `APP_URL` | "**se sim**, pergunte qual url" (o condicional implica o ramo negativo) | funcional |
| RQ-04 | Se a resposta for **sim**, uma segunda pergunta captura a URL desejada | "se sim, pergunte qual url ele gostaria" | funcional |
| RQ-05 | A segunda pergunta traz um **valor sugerido derivado do nome que o usuário escolheu antes** no mesmo comando | "deixe como sugestão de acordo com o nome que ele escolheu anteriormente" | funcional |
| RQ-06 | O cadastro do host executa **o procedimento já documentado no `README.md`** para criar host no Windows | "rode, conforme na documentação no @..\\README.md a parte de criar o host no windows" | restrição |
| RQ-07 | Após o cadastro, `APP_URL` no `.env` é ajustado para a URL efetivamente cadastrada | "faça o ajuste no .env em APP_URL com a nova url que foi rodado no host" | funcional |
| RQ-08 | A documentação de instalação passa a descrever esta etapa | "adicione essa informação na documentação de instalação" | não-funcional |
| RQ-09 | Avaliar e **decidir** se a etapa exige a opção `--force` ou se cabe também na instalação normal | "veja se precisa do paramentro \"--force\" ou se numa instalação normal também faz sentindo" | restrição |

> **RQ-09 é uma cláusula de investigação com entrega de decisão**, não de código. Ela é atendida
> por uma ADR que registra a escolha e a justificativa — e pelo comportamento que daí decorre.

## Instruções de Processo (não são RQ do produto)

Não descrevem o comportamento do sistema; governam **como** a entrega acontece. Ficam registradas
aqui porque são verificáveis e foram pedidas explicitamente.

| ID | Instrução | Trecho literal |
|----|-----------|----------------|
| PR-01 | Branch específica para esta wiki | "crie uma branch especifica para rodar essa wiki" |
| PR-02 | Usar subagents | "use subagents" |
| PR-03 | Commits individualizados | "faça commits individualizados" |
| PR-04 | Abrir PR; merge na main + tag + release **somente após aprovação** | "abra o PR e quando ele estiver aprovado, faça o merge na main + tag + release" |

> **PR-04 esclarecido pelo usuário em 2026-09-21**: "aprovado" = **aprovação explícita do usuário
> nesta conversa**. Não é CI verde e não é review no GitHub. O agente para no PR.

## Ambiguidades e Perguntas Abertas

- **RQ-02 — `"http://staterkit.test"`**: o texto original grafa `staterkit`. O nome do pacote é
  `starter-kit-easy` e o CHANGELOG usa `starterkit`. Tratado como **erro de digitação do
  solicitante**, e o Texto Original é preservado como veio (regra da skill). O exemplo exibido
  pelo comando deve usar a grafia correta.
  **Confirmar com o usuário antes de fixar a string.**

- **RQ-05 — "o nome que ele escolheu anteriormente"**: qual pergunta do `kit:install` guarda esse
  nome? Há mais de um candidato plausível (nome da aplicação / `APP_NAME`, nome do projeto, nome
  do tenant). Depende do fluxo real do comando — levantamento delegado a subagente.
  **Enquanto não confirmado, RQ-05 fica sem passo fechado.**

- **RQ-06 — elevação de privilégio**: escrever em `C:\Windows\System32\drivers\etc\hosts` exige
  execução como Administrador, e o `kit:install` roda em terminal comum. "Rode conforme a
  documentação" pode significar (a) o comando tenta escrever e falha sem admin, (b) o comando
  **emite** o comando pronto para o usuário colar num terminal elevado, ou (c) o comando eleva
  sozinho via UAC. As três têm consequências muito diferentes para quem instala.
  **Pergunta aberta ao usuário — ver ADR proposta no `02`.**

- **RQ-06 — escopo de plataforma**: o texto diz "host do **windowns**". O kit também roda em
  Linux/Mac (`/etc/hosts`) e em Docker. A etapa é **só Windows** ou cross-platform?
  **Pergunta aberta ao usuário.**

- **RQ-07 — ordem contra o `--force`**: se a etapa rodar depois de o `.env` já estar escrito, o
  ajuste de `APP_URL` é uma segunda escrita. Confirmar que não há cache de config (`config:cache`)
  entre as duas, senão o valor gravado não vale na primeira execução.

### Levantadas pela derivação dos casos de teste (`04-casos-de-teste.md`, 2026-09-21)

> A skill `feature-test-design` fixou uma **premissa por falha fechado** para cada uma, para que os
> cenários existam desde já. Cada premissa está marcada `@premissa` no `04`, com a linha "se negado,
> CT-nn inverte" e com o **invariante das duas leituras** afirmado no mesmo cenário.

- **P1 — RQ-04/RQ-06: validação do domínio digitado.** O requisito não menciona validar a URL que a
  pessoa informa. Quais entradas são recusadas (esquema `http://`, barra, espaço, quebra de linha,
  aspas/`;` que fechariam o argumento do comando de elevação, sufixo `.local` da RFC 6762)? E
  recusar significa **reperguntar**, encerrar a etapa ou normalizar em silêncio?
  **Premissa adotada**: recusa e repergunta. **Invariante afirmado junto**: seja qual for a decisão,
  nenhuma dessas entradas chega ao `hosts` nem ao `APP_URL` como foi digitada (CT-09, CT-11).

- **P2 — RQ-07 × RQ-06: `APP_URL` quando a elevação não se confirma.** RQ-07 diz "a nova url que foi
  **rodado** no host". Com o UAC negado no Windows — e a releitura do arquivo não encontrando o
  domínio —, o `.env` é ajustado mesmo assim? (No Unix a ADR-03 ajusta o `.env` sem executar nada,
  o que torna a resposta menos óbvia.)
  **Premissa adotada**: não ajusta sem a confirmação. **Invariante afirmado junto**: seja qual for a
  decisão, a falha vira aviso com o comando pronto para colar e o comando termina em sucesso
  (CT-20, CT-21).

- **P3 — RQ-06: o domínio já está no `hosts`.** Instalação anterior, ou Herd/Valet que já resolvem
  `*.test`. A etapa encerra sem tocar em nada, ou segue e ajusta `APP_URL`?
  **Premissa adotada**: não reescreve o `hosts` e ajusta o `APP_URL`, porque o domínio de fato
  resolve. **Invariante afirmado junto**: o arquivo termina com exatamente uma linha ativa do
  domínio, qualquer que seja a decisão (CT-16).

- **P4 — RQ-07: a URL gravada leva porta?** `FORWARD_APP_PORT` está fora de escopo, então o
  `php artisan serve` segue em 8000 e o `banner()` passará a imprimir `http://meu-projeto.test/app`,
  que não abre. Gravar `http://meu-projeto.test:8000` contraria o exemplo de RQ-02; gravar sem porta
  deixa a tela final mostrando um endereço que não responde.
  **Premissa adotada**: sem porta, pela literalidade do exemplo de RQ-02. **Invariante afirmado
  junto**: o valor gravado em `APP_URL` é exatamente a URL que a pergunta confirmou (CT-18).

- **P6 — RQ-06: o domínio pode já resolver sem linha no `hosts`.** Laravel Herd e Valet resolvem
  `*.test` por dnsmasq, e um DNS corporativo pode responder pelo domínio escolhido — nos dois casos
  o arquivo `hosts` não tem linha nenhuma. A etapa sonda a **resolução de verdade**, ou só lê o
  arquivo? Ler só o arquivo faz a etapa pedir elevação e sujar o `hosts` de quem já tinha a máquina
  configurada.
  **Premissa adotada**: sonda a resolução e **não eleva** quando o domínio já responde.
  **Invariante afirmado junto**: seja qual for a decisão, o `hosts` nunca ganha uma linha para um
  domínio que já resolve (CT-32).

  > Levantada pela **revisão adversarial** dos casos de teste: o espaço de estados da primeira
  > versão fora derivado do artefato observado (o arquivo) e não do fato observável (resolve ou
  > não), e por isso este estado inteiro ficara fora da matriz.

- **P4b — o aviso sobre login social e Vite.** Trocar `APP_URL` invalida os callbacks
  `APP_URL + /auth/{provider}/callback` já registrados no console do provedor. A etapa deve avisar
  antes de executar, ou isso é ruído no fim da instalação? Não bloqueia regra nenhuma; nenhum caso
  de teste depende da resposta.

## Fora de Escopo (declarado)

- Configurar servidor web (nginx/apache/Herd/Valet) para responder no domínio — o requisito fala
  em **hosts** e **`APP_URL`**, não em virtual host
- Emitir certificado TLS para `https://` — o exemplo dado é `http://`
- Remover ou editar entradas de host já existentes de instalações anteriores (a menos que a
  investigação do `--force` conclua o contrário — ver RQ-09)
