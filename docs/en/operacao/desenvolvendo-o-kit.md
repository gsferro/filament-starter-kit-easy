---
title: "Developing the kit itself"
parent: "Operations"
grand_parent: "English"
nav_order: 5
---

# Developing the kit itself

This section is for people who **work on the kit**, not for people who installed it. None of it
is needed in a project born from `create-project`.

## Private tooling stays OUT of the published package

`filament/blueprint` is a paid package living in a private repository. It helps evolve the kit,
which is why it **never** enters the committed state. The reason is harder than "good practice":

`composer create-project` installs **dev** dependencies by default — its own `--help` says
*"Enables installation of require-dev packages (enabled by default)"* — and it does so **before**
running `post-create-project-cmd`. With Blueprint in the published `composer.json` or
`composer.lock`, anyone without a licence gets a **403** while dependencies resolve, and the kit
becomes **uninstallable**. The hook that would clean it up never runs.

That is why Blueprint is not "removed on install", unlike the Snyk binding (an inert file the
installer deletes). It goes in and out through a script, and the committed state is always off:

```bash
composer bp:on    # declares the repository and requires it as a dev dependency
composer bp:off   # removes both the package and the repository
```

The credential goes into the **global** `auth.json`, which does not exist inside the project:

```bash
composer config --global --auth http-basic.packages.filamentphp.com "<your-email>" "<your-token>"
```

The token comes from your Filament account. The local `/auth.json` is in `.gitignore` as a last
line of defence, but global is better: the file does not even exist there for someone to commit
with `git add -f`.

`tests/Kit/BlueprintForaDoPacoteTest.php` guards this. **With Blueprint enabled those cases go
red** — deliberately: it is the reminder to run `composer bp:off` before committing.


## How the documentation site is published

This site — <https://gsferro.github.io/filament-starter-kit-easy/> — is the content of `docs/`
built by the **Jekyll bundled with GitHub Pages**. The whole update cycle is:

1. edit the markdown in `docs/pt/` and `docs/en/` — **always both languages**, in the same commit;
2. commit and push to the default branch (`main`);
3. GitHub builds and publishes on its own, in about a minute.

**There is no workflow in Actions and no local build to run.** Do not look for a `docs.yml` in
`.github/workflows/` or an `npm run docs:build`: they do not exist, deliberately — the native
Pages build resolves the gems on its own server, and a workflow would be a second publication
competing with the first (ADR-01 in the `site-de-documentacao` wiki).

The only part that is **not** in any file is the site source, which is repository configuration:
**Settings → Pages → Build and deployment → Source: Deploy from a branch → branch `main` →
folder `/docs`**. It is the one step a `git revert` cannot undo and no test can reach — if the
site disappears with every file in place, that is where to look.

The native build runs in `--safe` mode: only the gems on the Pages allowlist work, the theme
comes through `remote_theme`, and no i18n plugin is allowed — which is why the two language trees
are maintained by hand, in each page's front matter. And Liquid processes double braces **even
inside code blocks**: a Blade example on a page must sit inside Liquid's `raw` block, otherwise
the snippet vanishes from the published page with no error anywhere.

`docs/` is `export-ignore`: the site is kit material and never reaches a project born from
`create-project`. The guards for that live in `tests/Kit/SiteDeDocumentacaoTest.php`.
