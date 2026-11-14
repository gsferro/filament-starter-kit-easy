# Requisito — feat: tela de login personalizável

## Fonte

- **Origem**: pedido no chat (`/feature-wiki`), 2026-11-13
- **Autor / solicitante**: gsferro (dono do kit)
- **Fidelidade**: **alta** — texto escrito pelo solicitante
- **Máscara**: o pedido cita uma URL pública e o caminho local de um projeto usados só como
  referência de estudo. O próprio pedido proíbe citar esse projeto ou essa URL em qualquer lugar da
  documentação ou do site; por isso os dois trechos aparecem abaixo como
  `[referência externa omitida a pedido do solicitante]`. É a única alteração do texto, e ela é
  exigida pelo próprio texto (RQ-12)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> vamos melhorar a tela de login
> - avalise a url: "[referência externa omitida a pedido do solicitante]"
> - analise rigorosamente o projeto base: "[referência externa omitida a pedido do solicitante]" e veja como foi implementado a tela de login, usando o filament v5
> - reflita, antes de implementar, se isso poderia ser um novo pacote baseado no auth design (que já esta implementado) ou se vale a pena fazer um wrapper direto dentro do kit para customização
> - use o "codex --yolo" como auxiliar e subagent
> - crie uma branch exclusiva para essa evolução
> - a nova melhoria, não deve quebrar os sistemas que já usam o kit, rode os cenarios ao finalizar
> - pense se vale a pena criar no admin, um settings de configuração e customização, que já renderiza as telas de auth como preview, para ver como fica conforme se customiza, deixando a criterio do admin da aplicação a forma de uso
> - podemos extender essa customização para quando o tenant estiver ligado, podemos usar ou não um exclusivo por tenant (como é feito com outras features) tendo a opção de usar padrão ou customizar por tenant, dando uma melhor experiencia UX
> - lembre-se de documentar muito bem detalhado na page e nos readmes do projeto
> - NUNCA FAÇA REFERENCIA a outros projetos, se não for EXPLICITAMENTE DITO para ser feito. Logo, não faça referencia ao projeto da url passada em nenhum lugar da documentação ou dentro do site
> - use o "codex --yolo" como um juiz cego e peça a ele ideias e seja completamente rigido e imparcial na analise das sugestões entre voces, escolhendo o melhor caminho e layout para uso

## Decomposição em Cláusulas

<!-- Derivada e revisável. Estado: fechada · aberta — Qn · substituída por RQ-nn (Adendo N) · decomposta em RQ-nn, RQ-mm -->

| ID | Cláusula | Trecho literal de origem | Tipo | Estado |
|----|----------|--------------------------|------|--------|
| RQ-01 | A tela de login do kit fica melhor do que a atual (layout, hierarquia visual, responsividade, acessibilidade) | "vamos melhorar a tela de login" | funcional | fechada |
| RQ-02 | A página de referência é avaliada antes do desenho | "avalise a url" | processo | fechada |
| RQ-03 | A implementação de referência (Filament v5) é analisada antes do desenho | "analise rigorosamente o projeto base … e veja como foi implementado a tela de login, usando o filament v5" | processo | fechada |
| RQ-04 | Antes de implementar, a escolha entre pacote novo sobre o auth designer e camada dentro do kit é decidida e justificada | "reflita, antes de implementar, se isso poderia ser um novo pacote baseado no auth design … ou se vale a pena fazer um wrapper direto dentro do kit para customização" | processo | fechada |
| RQ-05 | O Codex com `--yolo` participa como auxiliar/sub-agente | "use o \"codex --yolo\" como auxiliar e subagent" | processo | fechada |
| RQ-06 | A evolução tem branch própria | "crie uma branch exclusiva para essa evolução" | processo | fechada |
| RQ-07 | Projetos que já usam o kit continuam funcionando depois de atualizar | "a nova melhoria, não deve quebrar os sistemas que já usam o kit" | restrição | fechada |
| RQ-08 | Os cenários de atualização do kit são executados ao final | "rode os cenarios ao finalizar" | processo | fechada |
| RQ-09 | O admin tem uma tela de configuração da aparência das telas de autenticação, se a avaliação concluir que vale | "pense se vale a pena criar no admin, um settings de configuração e customização" | funcional | fechada |
| RQ-10 | Essa tela mostra um preview das telas de autenticação que acompanha a customização | "que já renderiza as telas de auth como preview, para ver como fica conforme se customiza" | funcional | fechada |
| RQ-11 | Usar ou não a customização fica a critério do admin da aplicação | "deixando a criterio do admin da aplicação a forma de uso" | funcional | fechada |
| RQ-12 | Nenhuma documentação nem o site citam outros projetos, nem o projeto/URL de referência | "NUNCA FAÇA REFERENCIA a outros projetos … não faça referencia ao projeto da url passada em nenhum lugar da documentação ou dentro do site" | restrição | fechada |
| RQ-13 | Com tenancy ligada, cada organização pode usar a aparência padrão ou uma própria | "quando o tenant estiver ligado, podemos usar ou não um exclusivo por tenant … tendo a opção de usar padrão ou customizar por tenant" | funcional | fechada |
| RQ-14 | A escolha padrão × própria por organização segue o padrão que o kit já usa em outras features | "(como é feito com outras features)" | restrição | fechada |
| RQ-15 | A feature é documentada em detalhe na página de documentação e nos READMEs | "lembre-se de documentar muito bem detalhado na page e nos readmes do projeto" | não-funcional | fechada |
| RQ-16 | O Codex com `--yolo` atua como juiz cego das ideias, e a escolha final de caminho e layout é imparcial | "use o \"codex --yolo\" como um juiz cego e peça a ele ideias e seja completamente rigido e imparcial na analise das sugestões entre voces, escolhendo o melhor caminho e layout para uso" | processo | fechada |

Cláusulas de tipo `processo` não geram CT: a evidência delas é registro no `03` (despachos,
auditoria) e no `02` (RQ-04).

## Perguntas ao Solicitante

| ID | Pergunta | Afeta | Recomendação | Estado |
|----|----------|-------|--------------|--------|
| Q1 | Projeto que já usa o kit e roda `kit:update`: as telas de autenticação passam para o layout novo sozinhas, ou continuam como estão até o admin escolher o novo? | RQ-01, RQ-07, RQ-11 | ➡️ continuam como estão (preset "atual" gravado para instalações existentes); instalação nova nasce no layout novo. Falha fechado: nenhuma tela muda sem decisão de quem administra | aberta |
| Q2 | A customização vale para todas as telas de autenticação (login, recuperar senha, cadastro, dois fatores, bloqueio, escolha de painel) ou só para o login? | RQ-09, RQ-10 | ➡️ todas, com uma aparência só; o preview alterna entre elas | respondida pelo próprio texto original (QA-03 do gate): "um settings de configuração e customização, que já renderiza **as telas de auth** como preview" — a customização e o preview são das telas de autenticação, no plural; P-02 fixa o recorte (sem a escolha de painel). Confirmação explícita pedida ao solicitante no fechamento |
| Q3 | Valor inválido digitado na tela (opção fora da lista, cor fora de `#RRGGBB`, texto acima do teto) é recusado ou normalizado? | P-03, P-05 | ➡️ recusado com erro no campo, sem salvar nada | respondida pela sessão como P-10 (autorização do pedido; devolvida pela derivação como Q?1) |
| Q4 | "Caracteres" do teto são pontos de código ou grafemas? Espaços nas bordas contam? | P-03 | ➡️ pontos de código (como a validação de texto do Laravel); espaços nas bordas e repetidos são removidos | respondida pela sessão como P-11 (Q?2) |
| Q5 | Quais os padrões de cada campo e o que é "ausente"? | P-03, P-04 | ➡️ formulário à direita, fundo de cor, cor primária, sem frases, logo e alternador ligados; vazio ou só espaços = ausente | respondida pela sessão como P-11 (Q?3) |
| Q6 | Voltar à padrão e depois sair dela de novo recupera a aparência própria antiga? Organização inativa pode ser editada? | P-07 | ➡️ não recupera: voltar à padrão descarta a própria, e sair de novo começa de uma cópia nova da padrão; inativa edita como os demais campos dela | respondida pela sessão como P-12 (Q?4) |
| Q7 | Preview sem rascunho, expirado, de outra pessoa ou com tela desconhecida responde o quê? | P-08 | ➡️ rascunho ausente/expirado/alheio: 404; escopo sem permissão: 403; tela desconhecida: login | respondida pela sessão como P-13 (Q?5) |
| Q8 | Qual referência prova que o clássico ficou idêntico e que a faixa do celular cabe? | RQ-01, RQ-07, P-09 | ➡️ clássico: a mesma configuração de antes entregue ao pacote (mesmo objeto) e os testes de aparência existentes verdes; celular 390×844 e computador 1440×900 com os textos no teto | respondida pela sessão como P-14 (Q?6) |
| Q9 | Como provar que nada citou o projeto de referência sem escrever o nome dele num teste? | RQ-12 | ➡️ teste sem os nomes: as páginas novas não têm links externos fora dos domínios do próprio kit; a busca pelos nomes é feita pela sessão e registrada no `03`, sem os nomes | respondida pela sessão (Q?8) |
| Q10 | Quais fluxos provam a regressão de RQ-07? | RQ-07 | ➡️ as suítes Kit e Tenancy existentes e os quatro cenários do checklist de release | respondida pela sessão (Q?7) |
| Q11 | Há upload novo, idioma ou README além do literal? | RQ-14, RQ-15 | ➡️ sem upload novo (arte e logo da identidade); docs pt e en; READMEs pt e en | respondida pela sessão (Q?9) |

## Premissas

| ID | Premissa | Origem | Data | Afeta | Estado |
|----|----------|--------|------|-------|--------|
| P-01 | Enquanto Q1 não é respondida, instalação existente mantém a aparência atual depois do `kit:update` | recomendação de Q1 | 2026-11-13 | RQ-07 | vigente |
| P-02 | Enquanto Q2 não é respondida, a aparência configurada vale para todas as telas de autenticação vestidas com a arte do kit — login, cadastro (aberto e por convite), recuperação e redefinição de senha, confirmação de e-mail, dois fatores e bloqueio de sessão. A escolha de painel, que aparece depois de entrar e usa cartões, continua como está *(alterado em 2026-11-13: ADV-10 da revisão adversarial)* | recomendação de Q2 | 2026-11-13 | RQ-09, RQ-10 | vigente |
| P-03 | Há dois layouts: "clássico" (o de hoje, idêntico) e "painel" (formulário numa coluna e, na outra metade, um painel de marca arredondado com fundo de cor ou da arte da identidade, logo opcional e duas frases opcionais — destaque com até 80 caracteres e apoio com até 160); no "painel" escolhe-se o lado do formulário, a cor (a primária, ou uma cor `#RRGGBB`) e se o alternador de tema aparece | decisão de desenho (step 4, juiz cego) | 2026-11-13 | RQ-01, RQ-09 | vigente |
| P-04 | Valor gravado fora desse vocabulário nunca derruba uma tela de autenticação: campo inválido volta ao seu padrão, layout inválido volta ao clássico | decisão de desenho (falha fechado) | 2026-11-13 | RQ-07 | vigente |
| P-05 | Textos da aparência saem escapados; nenhum campo aceita HTML, CSS ou script | decisão de desenho (segurança) | 2026-11-13 | RQ-09 | vigente |
| P-06 | A aparência de uma organização só vale com tenancy ligada, nas telas do painel da organização (e nas páginas únicas `/login` e `/cadastro`), quando a organização é identificada (login com `?org=` de organização ativa, cadastro da organização, bloqueio de sessão dentro dela) e continua valendo depois de um envio com erro; nas demais telas (recuperar e redefinir senha, verificação de e-mail, dois fatores) e nos painéis sem organização vale a aparência padrão. A aparência nunca concede acesso | decisão de desenho (step 4) | 2026-11-13 | RQ-13 | vigente |
| P-07 | A organização usa a aparência padrão inteira (padrão) ou a sua inteira; ao escolher a própria, ela começa como cópia da padrão e deixa de acompanhar mudanças posteriores da padrão | decisão de desenho (ADR-02) | 2026-11-13 | RQ-13, RQ-14 | vigente |
| P-08 | O preview mostra login, recuperar senha e cadastro com valores ainda não salvos, em largura de computador e de celular; não executa autenticação, não envia e-mail, não segue para provedor social e só é aberto por quem pode salvar aquele escopo (configurações para a padrão; editar ou criar a organização para a da organização), e só com rascunho da própria pessoa | decisão de desenho (ADR-04) | 2026-11-13 | RQ-10 | vigente |
| P-09 | No layout "painel", em telas estreitas (celular) o painel de marca não some: vira uma faixa curta acima do formulário, e o rodapé da tela continua dentro da dobra | decisão de desenho (lição da referência) | 2026-11-13 | RQ-01 | vigente |
| P-10 | Na tela de configurações e no formulário da organização, valor fora do vocabulário é recusado com erro no campo e nada é salvo | Q3 | 2026-11-13 | P-03, P-05 | vigente |
| P-11 | Tetos contam pontos de código, medidos depois de remover os espaços das bordas e os repetidos; vazio ou só espaços é ausente. Padrões: formulário à direita, fundo de cor, cor primária, sem frases, logo e alternador ligados | Q4, Q5 | 2026-11-13 | P-03, P-04 | vigente |
| P-12 | Voltar a "usar a padrão" descarta a aparência própria; sair dela de novo começa de uma cópia nova da padrão vigente. Organização inativa tem a aparência editada como os demais campos dela *(alterado em 2026-11-13: ADV-23)* | Q6 | 2026-11-13 | P-07 | vigente |
| P-13 | Preview com rascunho ausente, expirado ou de outra pessoa responde 404; com escopo sem permissão, 403; com tela desconhecida mostra o login | Q7 | 2026-11-13 | P-08 | vigente |
| P-14 | Com o clássico, as telas recebem exatamente a configuração de antes; as medidas de computador e celular são 1440×900 e 390×844, com os textos no teto | Q8 | 2026-11-13 | RQ-01, RQ-07, P-09 | vigente |
| P-15 | Com o fundo "Arte da identidade", toda tela de autenticação — inclusive o bloqueio de sessão — usa a arte da identidade como fundo do painel; a logo fica só no selo | step 9 — RD-01 (fw-revisor-diff) e CX-02 (Codex) | 2026-11-13 | P-03 | vigente |
| P-16 | Uma tela de autenticação não entrega ao navegador cor nem logo de organização que ela não mostra: fora do layout "painel" (ou com a logo desligada) essas informações não saem nem por chamada direta ao componente | step 9 — SEC-01 (filament-security-audit) e RD-02 | 2026-11-13 | P-06, RQ-13 | vigente |
| P-17 | O preview só abre para quem entra no painel admin, e a autorização do escopo vale a cada atualização da página, não só na abertura | step 9 — SEC-02/SEC-03, BP-35, CX-01 | 2026-11-13 | P-08 | vigente |
| P-18 | Com uma cor de painel clara, o texto do painel passa a escuro, para continuar legível | step 9 — BP-23 (reviewing-filament-plans) | 2026-11-13 | P-03 | vigente |

## Fora de Escopo (declarado)

- Mudar o fluxo de autenticação (credenciais, login social, 2FA, captcha, login unificado): só a
  aparência muda
- Textos jurídicos/termos de uso
