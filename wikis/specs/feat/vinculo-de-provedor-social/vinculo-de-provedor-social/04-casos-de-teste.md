# Casos de Teste — Vínculo de provedor social

> Derivados do `00-requisito.md`. Arquivo: `tests/Kit/VinculoDeProvedorSocialTest.php`.
>
> **Desvio declarado**: escritos pelo mesmo agente que escreveu o plano, inline, durante a rodada
> de validação real (o solicitante pediu "implemente os 3 enquanto eu configuro o LinkedIn"). A
> skill `feature-test-design` não foi invocada. O que mitiga: cada caso cita a cláusula RQ que o
> origina, não o passo do plano; e dois casos existem só para **refutar** uma implementação
> plausível (CT-V03 e CT-V08).

## Perfil de risco

- **Autorização** — o vínculo decide QUEM entra. Erro aqui é conta trocada.
- **Fronteira externa** — o `sub` e o e-mail vêm do provedor; a confirmação vem de um link
  portador.
- Volume irrelevante; concorrência só no CT-V08.

## Varredura SFDIPOT (resumo)

| Perspectiva | O que importa aqui |
|---|---|
| Structure | tabela nova, FK em cascata, chave única (`provedor`, `sub`) |
| Function | reconhecer pelo vínculo; avisar; confirmar; criar vinculado |
| Data | `sub` vazio; `sub` de outra conta; e-mail trocado no provedor |
| Interfaces | callback do OAuth; link assinado; duas notificações; Settings |
| Platform | fila (notificações `ShouldQueue`) |
| Operations | modo padrão × estrito; conta pendente |
| Time | link de 30 minutos (a expiração é do `signed`; o caso cobre assinatura inválida) |

## Mapa de regras → casos

| Regra | RQ | Casos |
|---|---|---|
| R1 — primeira entrada em conta existente vincula, entra e avisa (modo padrão) | RQ-01, RQ-03, RQ-04 | CT-V01 |
| R2 — entradas seguintes não avisam e registram acesso | RQ-05 | CT-V02 |
| R3 — o vínculo vence o e-mail | RQ-05 | CT-V03 |
| R4 — modo estrito: link antes da sessão; o link vincula e entra | RQ-06 | CT-V04 |
| R5 — link sem assinatura válida não faz nada | RQ-06 | CT-V05 |
| R6 — conta nova nasce vinculada, sem aviso, em qualquer modo | RQ-03 | CT-V06 |
| R7 — apagar a conta apaga vínculos | RQ-03 (estrutura) | CT-V07 |
| R8 — identidade já de outra conta não se re-vincula na confirmação | RQ-03 | CT-V08 |
| R9 — conta existente pendente não abre sessão pelo provedor | (regressão do fix `8c92658`) | CT-V09 |
| R10 — o interruptor vive na tela e governa `kit.login.vinculo_confirmar` | RQ-07 | `ConfiguracoesDoKitTest` CT-01 (o mapa é percorrido inteiro) e o caso da tabela semeada |

## Casos

Técnica: **partição de equivalência** sobre (vínculo existe?, conta existe?, modo) mais **análise
de valor limite** em `sub` (vazio) e **caso adversarial** em identidade duplicada.

### CT-V01 — vincula, entra e avisa
- **Dado** `ja.tem@example.com` existe, Google ligado, sem vínculo
- **Quando** o callback volta com `sub-1` e esse e-mail verificado
- **Então** sessão da conta; linha em `vinculos_sociais` (`google`, `sub-1`) → conta;
  `PrimeiroAcessoSocial` enviada para ela
- **Mutantes previstos**: sem `vincular()` → sem linha; sem `notify()` → sem notificação

### CT-V02 — segunda entrada
- **Dado** vínculo (`google`, `sub-1`) já existe
- **Quando** o callback volta com `sub-1`
- **Então** sessão; **nenhuma** notificação; `ultimo_acesso_em` preenchido; continua UMA linha
- **Mutantes**: notificar sempre → reprova; `create` em vez de `firstOrCreate` → duplica

### CT-V03 — o vínculo vence o e-mail (endereço reciclado)
- **Dado** `dona` vinculada a `sub-1`; existe **outra** conta com o e-mail que o provedor passou
  a devolver
- **Quando** o callback volta com `sub-1` e o e-mail da outra
- **Então** sessão da `dona`; e-mail da `dona` inalterado; a outra sem vínculo
- **Mutante**: `contaCom($email)` antes do vínculo → loga a outra. **Verificado**: com
  `$vinculo = null` fixo, CT-V02 e CT-V03 reprovam

### CT-V04 — modo estrito
- **Dado** `vinculo_confirmar = true`, conta existe, sem vínculo
- **Quando** o callback volta
- **Então** redirect ao login, `guest`, sem vínculo, sem `PrimeiroAcessoSocial`;
  `ConfirmarVinculoSocial` enviada com URL contendo `/auth/google/confirmar`, `signature=`,
  `expires=`. **E** ao abrir a URL: sessão da conta e vínculo criado
- **Mutantes**: logar antes de confirmar → `assertGuest` reprova; URL sem assinatura → o `signed`
  do CT-V05 pega

### CT-V05 — link sem assinatura
- **Quando** `GET auth.social.confirmar` com os parâmetros mas sem assinatura
- **Então** 403; `guest`; zero vínculos
- **Verificado**: sem `->middleware('signed')` na rota, reprova

### CT-V06 — conta nova nasce vinculada (dataset: modo padrão / estrito)
- **Dado** registro aberto ligado, papéis semeados
- **Quando** o callback volta com e-mail sem conta e `sub-novo`
- **Então** conta criada, sessão, vínculo (`google`, `sub-novo`) → conta nova; **nenhuma**
  notificação (não havia conta a avisar)

### CT-V07 — cascata
- **Dado** conta com dois vínculos (google, github)
- **Quando** a conta é apagada
- **Então** zero vínculos

### CT-V08 — identidade já de outra conta
- **Dado** modo estrito; `sub-1` vinculado à `dona`
- **Quando** um link **assinado** pede confirmar `sub-1` para `outra`
- **Então** redirect ao login com recusa; `guest`; `sub-1` continua da `dona`

### CT-V09 — conta pendente
- **Dado** conta existente com `aprovacao_pendente = true`
- **Quando** o callback volta com o e-mail dela
- **Então** redirect ao login; `guest`

## Sem CT-B

Nada aqui só o navegador prova: são redirects, sessão, linhas de tabela e notificações. As
capturas do passo 9 do plano são **arte para o README**, não evidência.

## Regressão (natureza "evolução" + infra compartilhada)

`LoginSocialGoogleTest`, `LoginSocialProvedoresTest`, `LoginSocialGoogleTenancyTest` (suíte
Tenancy), `BloqueioDeSessaoTest`, `ConfiguracoesDoKitTest`, `SegredosDoSettingsTest`,
`TextoDoEnvTest`, `BooleanoDoEnvTest`, `ConfiguracoesDoKitDocumentacaoTest` — 416 + 4 casos,
todos verdes em 2026-08-26.
