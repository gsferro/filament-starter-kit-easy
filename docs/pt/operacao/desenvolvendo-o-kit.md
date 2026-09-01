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

