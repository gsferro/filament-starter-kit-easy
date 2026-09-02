---
title: "Desenvolvendo o próprio kit"
parent: "Operação"
grand_parent: "Português"
nav_order: 5
---

# Desenvolvendo o próprio kit

Esta seção é para quem **mexe no kit**, não para quem o instalou. Nada aqui é necessário num
projeto que nasceu do `create-project`.

## Ferramentas privadas ficam FORA do pacote publicado

O `filament/blueprint` é pago e vive num repositório privado. Ele ajuda a evoluir o kit, e por
isso **nunca** entra no estado commitado. O motivo é mais duro que "boa prática":

`composer create-project` instala as dependências de **dev** por default — o próprio `--help`
diz *"Enables installation of require-dev packages (enabled by default)"* — e faz isso **antes**
de rodar o `post-create-project-cmd`. Com o Blueprint no `composer.json` ou no `composer.lock`
publicados, quem não tem licença leva **403** durante a resolução das dependências, e o kit fica
**não-instalável**. O gancho que limparia nunca chega a rodar.

Por isso o Blueprint não é "removido na instalação", ao contrário do vínculo com o Snyk (que é
arquivo inerte e sai no gancho). Ele entra e sai por script, e o estado commitado é sempre
"desligado":

```bash
composer bp:on    # declara o repositório e faz o require --dev
composer bp:off   # remove o pacote e o repositório
```

A credencial vai no `auth.json` **global**, que não existe dentro do projeto:

```bash
composer config --global --auth http-basic.packages.filamentphp.com "<seu-email>" "<seu-token>"
```

O token vem da sua conta do Filament. O `/auth.json` local está no `.gitignore` como última
linha de defesa, mas o global é melhor: o arquivo nem existe ali para alguém commitar com
`git add -f`.

`tests/Kit/BlueprintForaDoPacoteTest.php` guarda isso. **Com o Blueprint ligado, esses casos
ficam vermelhos** — de propósito: é o lembrete de rodar `composer bp:off` antes de commitar.


## Como o site de documentação é publicado

Este site — <https://gsferro.github.io/filament-starter-kit-easy/> — é o conteúdo de `docs/`
construído pelo **Jekyll embutido do GitHub Pages**. O ciclo de atualização inteiro é:

1. editar o markdown em `docs/pt/` e `docs/en/` — **sempre nos dois idiomas**, no mesmo commit;
2. commitar e fazer push para a branch padrão (`main`);
3. o GitHub constrói e publica sozinho, em cerca de um minuto.

**Não há workflow de Actions nem build local a rodar.** Não procure um `docs.yml` em
`.github/workflows/` nem um `npm run docs:build`: eles não existem, de propósito — o build nativo
do Pages resolve as gems no servidor dele, e um workflow seria uma segunda publicação competindo
com a primeira (ADR-01 da wiki `site-de-documentacao`).

A única parte que **não** está em arquivo nenhum é a origem do site, que é configuração do
repositório: **Settings → Pages → Build and deployment → Source: Deploy from a branch →
branch `main` → pasta `/docs`**. É o único passo que um `git revert` não desfaz e que nenhum
teste alcança — se o site sumir com todos os arquivos no lugar, é ali que se olha.

O build nativo roda em modo `--safe`: só as gems da lista do Pages funcionam, o tema vem por
`remote_theme` e nenhum plugin de i18n é permitido — por isso as duas árvores de idioma são
mantidas à mão, no front matter de cada página. E o Liquid processa chaves duplas **até dentro
de bloco de código**: exemplo de Blade numa página precisa ficar dentro do bloco `raw` do
Liquid, senão o trecho some da página publicada sem erro em lugar nenhum.

`docs/` é `export-ignore`: o site é material do kit e não chega ao projeto que nasce do
`create-project`. As guardas disso ficam em `tests/Kit/SiteDeDocumentacaoTest.php`.
