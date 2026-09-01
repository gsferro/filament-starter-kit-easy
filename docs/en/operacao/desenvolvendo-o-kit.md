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

