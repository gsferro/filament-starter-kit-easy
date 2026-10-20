# Decisões Arquiteturais — Desbloqueio da sessão volta para a página em que a pessoa estava

Nenhuma decisão passou nos três portões. A troca do middleware do pacote por uma subclasse ligada
no container é fácil de reverter (um bind) e repete a técnica já usada para a tela de bloqueio; as
escolhas da feature estão em `## Decisões de Desenho` do `01`.

## Superfície Livewire

A feature não cria página, widget nem componente; altera o `mount()` da `TelaBloqueio`. Varredura
sobre o código que a feature toca, para confirmar que nenhum ponto novo fica ao alcance do cliente:

| Origem | Ponto | Alcançável pelo cliente | O que a feature muda | Fronteira |
|---|---|---|---|---|
| projeto | `app/Filament/Pages/Auth/TelaBloqueio.php:mount():286` | não — gancho de ciclo de vida, o Livewire recusa chamada por `$wire` | grava `url.intended` com a raiz do painel quando vazia; a guarda passa a ler o valor da marca | servidor: painel corrente, sessão |
| projeto | `public ?array $data` (herdada de `LockerScreen`) | sim — escrita entre requests | nada | `authenticate()` do pacote só lê `data.password` |
| projeto | `app/Filament/Pages/Auth/TelaBloqueio.php:authenticate():321` (sobrescreve a do pacote) | sim — ação do formulário, sem argumento | a cada envio: a página da sessão vence a propriedade (RD-03); a raiz marcada é recalculada com `canAccessTenant` (RD-04); grava `url.intended`, delega a `parent::authenticate()` e limpa as chaves ao destravar ou deslogar (P-09) *(alterado em 2026-10-20: QA-02 do quality gate)* | rate limit do pacote; nenhum valor do cliente entra no destino |
| projeto | `#[Locked] public ?string $destinoDoDesbloqueio` | não para escrita — `#[Locked]` recusa o update do cliente; o valor vai ao navegador no snapshot (é a URL da própria pessoa) | nova — preenchida no `mount()` e atualizada a cada envio, guardando o destino quando o `intended()` do pacote já consumiu a chave *(alterado em 2026-10-20: CR-04 do step 9; QA-02)* | servidor: `url.intended` da sessão ou a raiz do painel |
| projeto | `urlsDasLogos()`, `getAuthDesignerConfig()`, `content()` | sim — `public function` | nada | sem argumento do cliente |
| middleware | `App\Http\Middleware\BloquearSessaoComRetorno` | não — não é componente; a URL guardada é a do próprio request (`fullUrl()`), nunca um valor do formulário | novo | só GET sem `expectsJson()` |

Greps (código que a feature escreve):

```
grep -n "public function\|public ?\|public \$" app/Filament/Pages/Auth/TelaBloqueio.php
```

O valor que vira destino de redirect é derivado do request interceptado no servidor — não de
propriedade pública nem de array de estado do framework. Nenhum valor do cliente vira índice,
`parse`, coluna ou operador.
