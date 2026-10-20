---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Controllers Auth

## Login que troca a conta numa sessão travada descarta a página guardada
Todo `Auth::login()` que pode rodar com a sessão travada (`session('lockscreen') === true`) compara, ANTES do login, `Auth::id()` com a conta que entra: conta diferente (ou link que não é desbloqueio, como a confirmação de vínculo por e-mail) faz `session()->forget('url.intended')`. A página guardada é gravada por `App\Http\Middleware\BloquearSessaoComRetorno` ao travar, e no login unificado `urlDoPainel()` → `DestinoAposLogin::urlPara()` consome `url.intended` de qualquer um: sem o descarte, a outra pessoa cai na página de quem travou, com a query. Exceção: a mesma conta voltando é desbloqueio e honra a página (`redirect()->intended(...)`). Ver `LoginSocialController::retorno()` e `confirmarVinculo()`; origem: P-06/P-07 de `wikis/specs/feat/desbloqueio-volta-a-pagina/`.
