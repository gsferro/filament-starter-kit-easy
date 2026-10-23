# Roteiros de atualização — o contrato

> **Este documento é do processo de quem MANTÉM o kit.** Quem atualiza um projeto lê só o roteiro da
> versão destino, `wikis/atualizacoes/vX.Y.Z.md`, e nada mais.

Toda publicação do kit (release ou hotfix) tem um **roteiro de atualização**: um prompt em português
que um agente segue para levar um projeto derivado da versão anterior para a nova, usando o
`php artisan kit:update` como ferramenta. Cada roteiro mora nesta pasta, num arquivo por versão
(`v0.52.0.md`, `v0.53.0.md`…), e é escrito à mão na etapa pré-release obrigatória de
`wikis/checklist-de-release.md`, a partir da seção da versão no `CHANGELOG.md` e da wiki da feature,
reconciliadas com o que foi entregue. A guarda `tests/Kit/RoteiroDeAtualizacaoTest.php` confere este
contrato no CI da `main`, antes da tag; o workflow de release confere de novo no push da tag.

## As nove seções

Todo roteiro tem estas seções, com estes títulos exatos, nesta ordem, cada uma com corpo próprio da
versão:

1. `## Identificação` — as linhas `Origem: vX.Y.Z` (a versão anterior publicada), `Destino: vX.Y.Z`
   (a versão do marcador em `config/kit.php`), `Data: AAAA-MM-DD` e `Link:` (o link raw do próprio
   roteiro, abaixo), cada uma no início da linha; a linha `Padrão do link:` da próxima versão; e as
   três instruções de leitura: ler também o arquivo na `main`, preferi-lo quando ele trouxer a marca
   de revisão, e parar na dúvida.
2. `## Antes de começar` — o pedido que dispara o roteiro e o pré-voo: git limpo, versão instalada
   igual à origem (outra versão encerra o roteiro sem alterar nada), banco acessível, o `--dry-run` e
   a lista dos arquivos do kit que o dono editou.
3. `## O que muda nesta versão` — o que a versão entrega, dito para quem não leu mais nada.
4. `## Cuidados` — o que pode dar errado e o que não fazer. É a única seção que pode citar comando
   destrutivo do banco, e só como proibição.
5. `## Decisões do dono do projeto` — um `###` por decisão, com as linhas `Pergunta:` (termina em
   `?`), `Recomendação da release:` e `Sem resposta:` (o default seguro), todas com valor. Vem antes de
   `## Passos`: a pergunta é feita antes do passo que ela governa.
6. `## Passos` — numerados, cada um com condição, ação e evidência. A atualização é feita por um bloco
   de código cuja linha começa por `php artisan kit:update`, com `--from` da origem e `--tag` do
   destino.
7. `## Como conferir` — o estado esperado, o mesmo dos cenários 3 e 4 do checklist de release:
   `kit.version`, multi-organização, arquivos novos, migrations, suíte do kit, e o que é do dono
   preservado (arquivos, `.env`, canais de log, dados do banco).
8. `## Quando parar` — as condições de parada, cada uma com o que dizer ao dono.
9. `## Relatório final` — o que o agente entrega, inclusive cada decisão do dono com a resposta ou o
   default aplicado.

## Decisões obrigatórias

- **Todo default que a versão mudou vira pergunta.** A lista fica na subseção `### Defaults mudados`
  da seção da versão no `CHANGELOG.md`, um item por linha (`- frase`, sem crase); versão sem default
  mudado traz o item `- nenhum`. Cada frase da lista aparece, literal, numa linha `Pergunta:` do
  roteiro.
- **Conflito do `kit:update` num arquivo do kit que o dono editou é sempre decisão**, em todo roteiro:
  o `###` dela pergunta antes de aplicar, e o `Sem resposta:` mantém a versão do dono e registra. Versão
  sem nenhuma outra decisão traz, ao lado dela, a frase "Nenhuma outra decisão do dono nesta versão.".

## Regras de escrita

- **Autossuficiente.** O roteiro não remete o agente a nenhum outro documento: nem ao `CHANGELOG.md`,
  nem a `docs/`, nem à wiki de spec, nem a `wikis/agentes-e-skills.md`, nem ao `README.md`, nem ao
  roteiro anterior, nem a PR ou issue. O único endereço que ele traz é o dele mesmo (o `Link:` e o
  `Padrão do link:`). O que o agente precisa saber, o roteiro diz.
- **Sem citação `arquivo:símbolo:linha`.** A linha envelhece, e a cópia do roteiro em
  `wikis/agentes-e-skills.md` passa pela conferência de citações.
- **Sem "idem" nem "como na versão anterior".** `## O que muda nesta versão` e `## Cuidados` são da
  versão; corpo igual ao do roteiro anterior reprova.
- **Nenhum comando destrutivo do banco fora de `## Cuidados`.** `artisan` seguido de `migrate:fresh`,
  `db:wipe`, `migrate:refresh`, `migrate:reset`, `migrate:rollback`, `kit:tenancy` ou `kit:install` (os
  dois últimos recriam o banco) não aparece em nenhuma outra seção, nem em comentário `<!-- -->`, nem
  com prefixo `sail` ou `docker`: o agente lê o arquivo cru. Em `## Cuidados` o comando só aparece numa
  frase de proibição, nunca em bloco de código.
- **Comandos para Git Bash e PowerShell quando diferem.** Quando o comando é o mesmo nos dois, um bloco
  só basta.
- **Versão confirmada pela versão e pelos arquivos.** O `kit:update` marca a versão nova assim que
  aplica qualquer arquivo; o roteiro confere também os arquivos novos.
- **Decisões que dependem uma da outra dizem isso na pergunta.** Se manter um arquivo do dono impede
  cumprir outra decisão (ligar um recurso que depende dele), a pergunta avisa, e o roteiro diz o que
  registrar quando o "sim" não pode ser cumprido.
- **Revisar e desfazer pelo índice.** O `kit:update` aplica com `git checkout <tag> -- <caminho>`, então
  as mudanças ficam no índice: revisar é `git status` e `git diff --cached`; desfazer, só com o pedido
  do dono, é `git reset --hard HEAD` mais os arquivos não rastreados um a um, e não desfaz o banco.
- **A suíte roda com o banco descartável forçado no próprio comando** (`DB_CONNECTION=sqlite`,
  `DB_DATABASE=:memory:`, `DB_URL=sqlite:///:memory:`, depois de `php artisan config:clear`): o `phpunit.xml` não
  força o banco, e uma variável de ambiente do dono venceria.
- **O pré-voo confere os nomes que o comando reserva** (o remote `kit`, as tags `kit-*` e o branch
  `kit-update/vX.Y.Z`) e para se algum já existir: o comando apaga o remote e as tags com esses nomes.
- **Sem promessa de funcionar em todo agente ou host.** O roteiro é conferido por um agente em sessão
  nova a cada publicação, e por outros na cadência do checklist de release.

## Encadeamento de versões

Cada roteiro cobre só a passagem da versão anterior publicada (a seção `[x.y.z]` logo abaixo da do
marcador no `CHANGELOG.md`) para a versão nova. Um salto longo é o encadeamento manual dos roteiros
intermediários, em ordem, conferindo a versão instalada entre um e outro. A v0.51.2 e as anteriores
não têm roteiro: o primeiro é o da v0.52.0. Roteiro publicado não sai desta pasta, porque o link dele
continua valendo.

## O padrão do link

O agente lê o roteiro pela URL raw do GitHub, fixada na tag destino:

```text
https://raw.githubusercontent.com/gsferro/filament-starter-kit-easy/v{versão}/wikis/atualizacoes/v{versão}.md
```

`{versão}` é a versão destino, nas duas posições, com o `v` da tag (`v0.53.0`, nunca `0.53.0`). Quem
monta o link pelo padrão lê também o mesmo arquivo na `main` e prefere o da `main` quando ele traz a
marca de revisão; na dúvida, para e pergunta ao dono.

Duas exceções, e só elas:

- **a v0.52.0**, cujo roteiro nasceu depois da tag: o link dela aponta para a `main`;
- **uma versão com o roteiro revisado depois da tag** (abaixo): o link aponta para a `main` ou para o
  commit da correção, pelo SHA completo de 40 caracteres hexadecimais, com o mesmo host, repositório e
  caminho.

## O registro de revisão

Quando o cenário pós-release com agente reprova o roteiro, a tag não é reescrita. O roteiro é
corrigido na `main`, com a evidência nova, e três coisas andam juntas:

1. na seção `[x.y.z]` da própria versão no `CHANGELOG.md`, uma linha que começa por
   `Roteiro de atualização revisado`, com a data e o motivo;
2. no `## Identificação` do roteiro corrigido, a linha `Roteiro revisado em {data}: {motivo}`, que é o
   que o agente vê na `main` quando o padrão do link manda conferi-la;
3. o `Link:` do roteiro passa a apontar para a `main` ou para o commit da correção.

A marca sem o registro, ou o registro sem a marca, reprova a guarda. Falha do **código** não é revisão
de roteiro: vira hotfix, com roteiro próprio.

## A cópia local

O roteiro da versão marcada em `config/kit.php` é copiado, inteiro, para `wikis/agentes-e-skills.md`,
entre os marcadores `<!-- roteiro-de-atualizacao:inicio -->` e `<!-- roteiro-de-atualizacao:fim -->`.
Esse arquivo viaja pelo `create-project` e pelo `kit:update`, e é por ele que o projeto da versão
seguinte aprende o padrão do link. A tag v0.52.0 saiu antes deste mecanismo e não tem a cópia: o
primeiro projeto com cópia local é o da v0.53.0, e o pedido sem link, montado pelo padrão da cópia,
vale a partir do roteiro da v0.54.0. Até lá, o pedido traz o link. A guarda compara a cópia com o arquivo, só normalizando o fim de
linha.
