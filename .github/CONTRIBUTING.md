# Contribuindo

Obrigado pelo interesse. Este arquivo cobre o essencial para trabalhar **no kit**; se você derivou
um projeto dele, o que interessa está em [`wikis/`](../wikis/README.md) e no
[site de documentação](https://gsferro.github.io/filament-starter-kit-easy/pt/).

## Requisitos

- PHP **8.4+** e Composer 2
- Node 22+ (para os assets e os testes de navegador)
- Git

## Rodando o projeto

```bash
composer install
npm install && npm run build
php artisan kit:install
composer dev          # servidor, fila, logs e Vite juntos
```

## Testes

```bash
composer test:kit     # a fundação do kit (Kit + Tenancy), em paralelo
composer test         # a suíte inteira, com Pint, PHPStan e FilaCheck antes
composer test:browser # os testes de navegador (exige npm run build antes)
```

Antes de abrir PR:

```bash
vendor/bin/pint --dirty     # formatação
vendor/bin/phpstan analyse  # level 8, zero erros
vendor/bin/filacheck        # nada de Filament depreciado
```

## Padrões

As decisões que o código não consegue contar sozinho estão em
[`wikis/convencoes.md`](../wikis/convencoes.md) e em `.ai/rules/` — leia antes de mexer numa área que
não conhece. Elas existem porque cada uma já custou um defeito.

Features novas seguem a esteira da skill `feature-wiki`: o requisito é capturado verbatim antes do
plano, o plano antes do código, e os casos de teste derivam do **requisito**, não do plano. O
histórico de cada feature fica em `wikis/specs/`.

## Antes de lançar uma tag

> **Obrigatório**: rodar os quatro cenários de
> **[`wikis/checklist-de-release.md`](../wikis/checklist-de-release.md)** — duas instalações limpas
> (com e sem multi-tenancy) e dois `kit:update` a partir da versão anterior, nos mesmos dois
> cenários.

Suíte verde na árvore do kit **não basta**, e o checklist explica por quê com um caso real: um
teste que viaja para o projeto instalado lendo um arquivo que **não** viaja passou por três ciclos
de quality gate, um `/code-review` e dois gates independentes — e quebrava em toda instalação nova.
Ele só é observável de dentro de um projeto instalado.

## Abrindo um PR

- Uma feature por PR, com a wiki dela em `wikis/specs/{branch}/{feature}/`
- Mensagem de commit com gitmoji + escopo: `:sparkles: feat(tenancy): …`
- O `CHANGELOG.md` recebe a entrada na seção `[Unreleased]`
- Se a mudança é visível ao usuário, as docs em `docs/pt/` **e** `docs/en/` acompanham
