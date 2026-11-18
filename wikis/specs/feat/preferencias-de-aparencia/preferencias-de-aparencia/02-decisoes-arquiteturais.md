# Decisões Arquiteturais — Roadmap item 1: preferências de aparência por usuário

Nenhuma decisão passou nos três portões. As escolhas de desenho estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

| Ponto | Origem | O que o cliente alcança | Fronteira |
|---|---|---|---|
| `App\Livewire\PreferenciasDeAparencia::$data` (`public ?array`) | código do projeto | escreve qualquer valor nas chaves `densidade`, `fonte`, `cor` e `menu` entre requests | `Select` aplica `in()` das opções; `salvar()` coage cada valor pelo enum ou pela lista fechada de cores antes de gravar (P-12); valor fora do vocabulário cai em `null` (padrão) |
| `App\Livewire\PreferenciasDeAparencia::salvar()` | código do projeto | chama a gravação a qualquer momento, inclusive com a chave da instalação desligada depois que a tela abriu | recusa com a chave desligada (falha fechada); grava só no usuário autenticado (`Filament::auth()->user()`), nunca num id do cliente |
| `_startUpload` do Livewire | framework | upload de arquivo para o componente | fechado por `RestrictsFileUploadsToSchemaComponents` (sem campo de upload no schema), como `DefinirSenhaPorEmail` |
| `ConfiguracoesDoKit` — campos `aparencia_por_usuario`, `tamanho_da_fonte`, `modo_do_menu` | código do projeto (página de settings existente) | escreve os três valores no estado do formulário | `Select` com `in()`; `mutateFormDataBeforeSave()` coage fonte e menu; tela restrita a quem já administra a instalação |
| `TenantForm` — `aparencia_por_usuario` | código do projeto (Resource existente) | escreve o booleano no estado do formulário | cast `boolean` no model; Resource restrito pela `TenantPolicy`; auditado por `AuditsFillables` |
| `users.aparencia` lido no render | model persistido | o valor gravado vira índice de `match`/`tryFrom` e valor de variável CSS | todo leitor coage pelo enum (`tryFrom() ?? padrão`) e a cor passa por `CorPrimaria::resolver()`, que só aceita constante existente da paleta; o CSS emitido sai do enum, nunca do texto gravado |

Greps sobre o código que a feature escreve (2026-11-18, antes da implementação):

```
grep -n "public function\|public \$\|public ?" app/Livewire/DefinirSenhaPorEmail.php
→ precedente: $sort, enviar(); nenhuma propriedade de estado do cliente
```
