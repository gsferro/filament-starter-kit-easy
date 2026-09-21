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

## Fora de Escopo (declarado)

- Configurar servidor web (nginx/apache/Herd/Valet) para responder no domínio — o requisito fala
  em **hosts** e **`APP_URL`**, não em virtual host
- Emitir certificado TLS para `https://` — o exemplo dado é `http://`
- Remover ou editar entradas de host já existentes de instalações anteriores (a menos que a
  investigação do `--force` conclua o contrário — ver RQ-09)
