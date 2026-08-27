# Requisito — Proteção anti-robô (reCAPTCHA) nas telas públicas

## Fonte

- **Origem**: mensagem no chat, repassada verbatim pelo coordenador ao agente implementador
- **Data**: 2026-08-26
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: alta — texto escrito pelo solicitante

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos implementar tanto na tela de login, quanto na tela de esqueci senha e register (que são telas publicas com forms) a proteção de recaptcha
> - use o filament blueprint
> - analise profundamente o pacote: "https://filamentphp.com/plugins/tallcms-registration-plugin"
> - veja se ele se adequa ao projeto e ao nosso settings para configuração dentro da aplicação para uso das proteções
> - veja se tem outros pacotes semelhantes em: "https://filamentphp.com/plugins" que atendam
> - a principio, vem desativado como default, da mesma forma do login social, mas com a config no settings no "/admin" de para habilitar as proteções
> - use sub-agentes se necessário e worktree para ir tocando em paralelo enquanto continuamos a refinar o starter-kit validando o login social + tenant

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | A tela de **login** ganha proteção anti-robô (reCAPTCHA) | "implementar tanto na tela de login (…) a proteção de recaptcha" | funcional |
| RQ-02 | A tela de **esqueci a senha** ganha a mesma proteção | "quanto na tela de esqueci senha" | funcional |
| RQ-03 | A tela de **registro** ganha a mesma proteção | "e register (que são telas publicas com forms)" | funcional |
| RQ-04 | A implementação segue as normas do **Filament Blueprint** (namespaces, `make()`, construções que a varredura do kit reprova) | "use o filament blueprint" | restrição |
| RQ-05 | O pacote `tallcms-registration-plugin` é **analisado a fundo** quanto à adequação ao projeto e ao Settings do kit | "analise profundamente o pacote (…) veja se ele se adequa ao projeto e ao nosso settings" | restrição (pesquisa) |
| RQ-06 | Outros pacotes do catálogo `filamentphp.com/plugins` que atendam são **levantados e comparados** | "veja se tem outros pacotes semelhantes (…) que atendam" | restrição (pesquisa) |
| RQ-07 | A proteção **nasce desligada**, como o login social | "a principio, vem desativado como default, da mesma forma do login social" | funcional |
| RQ-08 | A proteção é **ligada e configurada pela tela de Settings** do `/admin` (a chave vem do banco em tempo de execução) | "mas com a config no settings no '/admin' de para habilitar as proteções" | funcional |
| RQ-09 | O trabalho corre em **worktree isolado**, em paralelo à validação do login social + tenant | "use sub-agentes se necessário e worktree para ir tocando em paralelo" | processo (não é produto) |

A cláusula RQ-09 é de processo: não gera passo de implementação nem caso de teste. Está aqui para o quality gate não a acusar como omissão.

## Ambiguidades e Perguntas Abertas

- **RQ-01..03 — "recaptcha"**: reCAPTCHA **v2 (caixa "não sou um robô")** ou **v3 (invisível, com pontuação)**?
  - **Assumido**: v2 checkbox. É o único em que o servidor recebe uma resposta binária e não precisa de um limiar de pontuação nem de um nome de ação por tela; e é o que torna trivial oferecer também Turnstile e hCaptcha, cujos widgets e endpoints de verificação seguem o mesmo protocolo (ver ADR-02).
  - **Se negado**: acrescenta-se um caso `recaptcha_v3` ao enum, com `action` por tela e limiar configurável na tela de Settings; a regra de validação ganha a comparação de `score`. O blade muda (`grecaptcha.execute` no envio, em vez do widget). Os outros passos não mudam.
- **RQ-01..03 — "telas publicas com forms"**: a tela de **redefinição** de senha (a que o link do e-mail abre, com token) e a de **confirmação de e-mail** também são públicas. Entram?
  - **Assumido**: não. O requisito nomeia três telas — login, esqueci a senha e registro —, e as outras duas só se alcançam com um token assinado que já veio de um e-mail, o que um robô não tem.
  - **Se negado**: mais uma subclasse de página (a de `ResetPassword`), registrada nos três provedores com `usingResetPage()`; um caso de teste a mais por tela.
- **RQ-01 — "tela de login" no plural implícito**: os três painéis (`/app`, `/admin`, `/infra`) têm login e recuperação de senha; só o `/app` tem registro.
  - **Assumido**: a proteção vale para os três painéis, com um interruptor só — é assim que o rodapé e os botões sociais funcionam, e o defeito histórico do kit nessa área é configurar um painel e esquecer os outros dois.
  - **Se negado** (um interruptor por painel): três toggles em vez de um; o predicado ganha o painel corrente como parâmetro.
- **RQ-07 — "da mesma forma do login social"**: no login social, ligar o interruptor não põe o botão no ar sozinho — as credenciais também precisam estar preenchidas (duas condições).
  - **Assumido**: a mesma regra. Interruptor ligado sem chave do site **ou** sem chave secreta = proteção desligada, e a tela avisa. Ligar sem credencial derrubaria o login dos três painéis (o widget não renderiza e o campo obrigatório nunca é preenchido) — o oposto de uma proteção.
- **RQ-08 — a chave secreta**: cifrada no banco, como os `client_secret`?
  - **Assumido**: sim, pelo mesmo caminho (`encrypted()`, `addEncrypted`, zerada no `fill`, dehidratada só quando preenchida). Não está escrito no requisito, mas está escrito em `.ai/rules/pages.md` e `.ai/rules/settings.md`, que valem para toda propriedade nova de segredo.

## Fora de Escopo (declarado)

- Os **botões de login social** não passam pelo desafio: a barreira deles é o OAuth do provedor, que já é interativo. Pôr um captcha antes do redirect adicionaria atrito sem fechar nada.
- O **desafio de 2FA** (segunda etapa do login): a pessoa já passou por senha e captcha na primeira.
- A tela de **bloqueio de sessão** (lockscreen): exige sessão autenticada, não é pública.
- **Honeypot** e **throttle** adicionais: o Filament já limita a 5 tentativas por minuto no login (`Login::authenticate()` → `rateLimit(5)`), a 2 na recuperação e a 2 no registro. Não são substituídos nem duplicados.
- **reCAPTCHA v3** e **reCAPTCHA Enterprise** — ver a primeira ambiguidade.
- **Capturas de tela** para o README: este worktree não tem `npm run build` nem navegador; a captura entra no fluxo de `kit:arte` de quem valida no navegador.
