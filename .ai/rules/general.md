---
paths:
  - composer.json
---

# General

## Dependência privada não pode viajar no pacote — o gancho de instalação é tarde demais
`composer create-project` instala `require-dev` por DEFAULT (o `--help` diz "enabled by default") e resolve tudo ANTES de rodar `post-create-project-cmd`. Então "instalo e o gancho limpa" não funciona para dependência de repositório privado: quem não tem licença leva 403 na resolução e o kit fica não-instalável. O gancho nunca roda.

A separação que vale: ARQUIVO inerte (o `.snyk`, o `seguranca.yml`) pode sair no gancho — ver `App\Support\VinculoDoSnyk`. DEPENDÊNCIA não; ela nunca pode estar no `composer.json` nem no `composer.lock` commitados. Entra e sai por script (`composer bp:on` / `bp:off`), estado commitado sempre desligado, e uma guarda em `tests/Kit/BlueprintForaDoPacoteTest.php` reprova se escapar.

Duas armadilhas medidas ao construir isso: (1) o flag `--create-project` foi para o script `setup` por engano, porque `kit:install --ansi` aparece duas vezes no composer.json e a primeira é a do `setup` — que roda em clone do kit e apagaria os arquivos da própria fonte; (2) checar o texto bruto do composer.json por `packages.filamentphp.com` reprova por causa do próprio script `bp:on`. O oráculo é a chave `repositories`, estruturalmente.
