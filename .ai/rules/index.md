# Project Rules Index

Before planning or editing, find the row whose globs match the file's path and read that rule file.

Rows are ordered from most specific to most general — quando dois globs casam com o mesmo arquivo,
leia os dois.

| Applies to | Rule file |
| --- | --- |
| app/Filament/Pages/Auth/** | .ai/rules/auth.md |
| app/Filament/** | .ai/rules/filament.md |
| app/Models/** | .ai/rules/models.md |
| resources/views/** | .ai/rules/views.md |
| tests/Browser/** | .ai/rules/testes-browser.md |
| tests/** | .ai/rules/testes.md |
