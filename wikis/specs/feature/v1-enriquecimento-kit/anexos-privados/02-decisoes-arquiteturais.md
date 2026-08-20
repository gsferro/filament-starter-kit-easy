# Decisões Arquiteturais — Anexos privados

## ADR-01 — Trocar o default da media library, não criar disco novo

**Contexto.** O vazamento vem de `media-library.disk_name = public` discordar de
`filament.default_filesystem_disk = local`. Cinco caminhos foram levantados:

| Opção | O que faz | Esforço |
|---|---|---|
| A | `useDisk('local')` só na coleção `anexos` | 30 min |
| B | `MEDIA_DISK` default vira `local` | 1-2 h, quase tudo documentação |
| C | disco dedicado `media`, com `url` próprio | ~2 h |
| D | rota autorizada própria + `UrlGenerator` custom | 1-2 dias |
| E | só corrigir a documentação | 1 h |

**Decisão: B.**

**Por quê.**

**A não basta.** Fecha `anexos` e deixa o default do kit vazando. O próximo model do usuário
nasce com o mesmo defeito, e o kit existe justamente para que ele não precise saber disso.

**C resolve um problema que o passo 3 já resolve.** O argumento a favor era a colisão de URI: a
rota `storage.local` nasce em `/storage/{path}`, mesmo caminho do symlink `public/storage`, e
arquivo físico ganha da rota. Mas a colisão só morde se houver mídia em `storage/app/public/{id}/`
— exatamente o que o comando de migração esvazia. O que sobra ali é `organizacoes/`, que não
colide com id numérico. Disco novo seria mais um conceito na config de um starter-kit para
comprar uma garantia que a migração já dá.

**D é a única com autorização real, e é outra wiki.** Ver ADR-03.

**E é obrigatória em qualquer caminho**, e está no plano como passo 5 — não como alternativa.

**Consequência.** Nenhum disco novo, nenhuma rota nova, nenhum `UrlGenerator`. O `local` já tem
`'serve' => true` e já registra `storage.local`. A coluna já está em `visibility=private` e passa
a assinar sozinha. **A correção é uma palavra em um arquivo de config** — e todo o resto do
trabalho é migração, teste e documentação.

**O que invalidaria.** Aparecer uma segunda coleção que precise ser pública de verdade e outra
privada no mesmo model. Aí o default deixa de ser suficiente e `useDisk()` por coleção passa a
ser a regra, não a defesa extra.

---

## ADR-02 — Manter `useDisk()` explícito mesmo sendo redundante

**Contexto.** Com o ADR-01 aplicado, `addMediaCollection('anexos')` sem `useDisk()` já cairia em
`local`. A linha do passo 2 é, tecnicamente, redundante.

**Decisão: manter.**

**Por quê.** É defesa em profundidade para uma propriedade de **segurança**, e o custo é uma
linha. Um projeto nascido do kit que coloque `MEDIA_DISK=public` no `.env` — por engano, ou
copiando de um tutorial — reabre o vazamento em toda coleção que dependa só do default. Com
`useDisk()`, esta não reabre.

O segundo motivo é pedagógico e vale tanto quanto: o `Projeto` é o resource de demonstração. Ele
**ensina** o padrão para quem lê o kit. Uma coleção que declara o disco ensina "declare o disco";
uma que depende do default ensina que dá para não pensar nisso.

**Contra-argumento considerado.** A regra do projeto é não escrever o que já é default. Ela vale
para configuração de conveniência, não para o único ponto onde a diferença entre privado e
público é decidida.

---

## ADR-03 — Aceitar link assinado sem autorização, e dizer isso em voz alta

**Contexto.** A rota `storage.local` tem `middleware: []` e `ServeFile.php:55-61` valida **só a
assinatura**. Não há sessão, usuário nem organização no caminho. O kit não tem `MediaPolicy`.

Consequência: um link assinado copiado de um usuário da Acme abre para qualquer anônimo até
expirar — 5 minutos pelo gerador do spatie, até 30 pelo da coluna do Filament.

**Decisão: aceitar, documentar como limite conhecido, e não implementar autorização nesta wiki.**

**Por quê.**

O salto de proteção já é grande e é o que o requisito pede: de **URL permanente, adivinhável por
id sequencial, sem expiração** para **URL assinada com HMAC e vida de minutos**. Enumerar
`/storage/1`, `/storage/2`, `/storage/3` deixa de funcionar. Esse era o vazamento medido.

Autorização real exige `UrlGenerator` próprio, controller autenticado, `MediaPolicy` e cobertura
de conversões e responsive images — a opção D, de um a dois dias. Ela não é adiada por preguiça:
é uma decisão de arquitetura de mídia, com efeito em cache, CDN e no visualizador do lightbox, e
merece a sua própria wiki com o seu próprio requisito.

**O que precisa ficar escrito**, e é a parte não-negociável desta ADR: quem lê a documentação
precisa saber que **link de anexo compartilhado é acesso concedido** durante a validade. Sem
isso, a documentação volta a prometer mais do que entrega — que é o erro que originou esta wiki.

**O que invalidaria.** Requisito de compliance que exija trilha de quem baixou o quê. Assinatura
HMAC não produz trilha: ninguém sabe quem abriu.

---

## ADR-04 — Migrar as mídias legadas por comando, não por migration

**Contexto.** Trocar o default não move nada do que já existe. Neste repositório são 3 linhas; num
projeto instalado, podem ser milhares, com arquivos grandes.

**Decisão: comando artisan idempotente com `--dry-run`, não migration.**

**Por quê.**

**Migration é o lugar errado.** Ela roda dentro do deploy, sem supervisão, e move arquivo — I/O
que pode levar minutos, falhar no meio e não ter rollback trivial. Uma migration que move 40 GB
de mídia trava o deploy, e `migrate:rollback` não devolve o arquivo.

**Comando é reexecutável.** Falhou na metade, roda de novo e continua de onde parou, porque a
linha do banco só é atualizada **depois** que o arquivo chega ao destino. Essa ordem é a
propriedade que torna a reexecução segura: um arquivo movido com a linha ainda apontando para o
disco antigo é detectável e corrigível; o contrário perde a mídia.

**`--dry-run` porque o operador precisa ver antes.** Mover arquivo de um projeto em produção sem
saber quantos e quais é o tipo de operação que ninguém quer descobrir depois.

**O que fica de fora de propósito.** Mídia de coleção que declare `useDisk('public')`
explicitamente. Se alguém escreveu isso, foi escolha — e o comando não desfaz escolha alheia.

**O que invalidaria.** Se o volume típico fosse sempre pequeno, uma migration seria mais simples e
garantiria que ninguém esquece de rodar. Justamente por causa do "esquecer", o aviso de release
precisa citar o comando — está no plano.
