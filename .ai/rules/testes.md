---
paths:
  - 'tests/**'
---

# Testes

## Helper de teste usado por mais de um arquivo vive em `tests/Pest.php`

Em PHP função é global no processo. Quando o Pest carrega **todos** os arquivos, um helper declarado em `AlgumTest.php` vaza para o vizinho e tudo passa — o acoplamento fica invisível. Ele só aparece quando algo carrega um **subconjunto**, que é o que fazem os três comandos mais usados: `--parallel` (cada worker leva um subconjunto), `--tia` (só o afetado pelo diff) e `pest tests/Kit/AlgumTest.php` (um arquivo). O sintoma é `Call to undefined function`, que não aponta a causa.

Helper usado por **um** arquivo continua nele. O defeito é o uso **cruzado**.

Enforçado por `tests/Kit/HelpersDeTesteTest.php`, que usa `token_get_all()` para ignorar menções em docblock — não invente um regex, ele conta comentário como chamada.

Nunca crie um clone com outro nome para escapar da colisão de redeclaração (`pivotDePapeisDaOrganizacao()` ao lado de `pivotDePapeis()`, `entrarNoPainelDa()` ao lado de `noPainelDa()`). Isso troca um erro que estoura por duas funções idênticas que ninguém percebe. Mova para `tests/Pest.php` e use uma só.

## Uma tela aberta não é uma tela que grava

`GET /admin/users` seguiu verde com o salvamento quebrado por `Select::make('roles')`. Cubra em par: a visita **e** a gravação por componente Livewire. Ver `tests/Kit/PaginasInfraTest.php:86-104`.

## Teste de componente de painel

`Filament::setCurrentPanel()` não boota o painel: quem chama `Panel::boot()` é o middleware `SetUpPanel`, que teste de componente não atravessa. Tela que depende de algo registrado no `boot()` de plugin precisa de `noPainelBootado()`. E toda tabela do kit carrega adiada (`deferLoading` global) — sem `->loadTable()` o HTML testado é o do esqueleto.


## Nem todo papel do kit existe em toda suíte de teste
O `PapeisSeeder` cria papéis diferentes conforme a tenancy esteja ligada ou não. Escolher a suíte errada faz o caso morrer no arranjo, com `Spatie\Permission\Exceptions\RoleDoesNotExist: There is no role named 'X' for guard 'web'` — que parece defeito de código e é defeito de suíte.

| Papel | `roles.painel` | Onde existe |
|---|---|---|
| `master_global` | nulo (entra pelo `Gate::before`) | ambas |
| `admin` | `admin` | ambas |
| `infra` | `infra` | ambas |
| `panel_user` | `app` | ambas |
| `admin_app` | `app` | **só `tests/Tenancy`** |

`admin_app` é o papel de quem administra UMA organização — o `PapeisSeeder` só o cria dentro do ramo de tenancy. Caso que precise dele vai para `tests/Tenancy`, nunca `tests/Kit`.

Lembre também que `Tests\TenancyTestCase` fixa `permission.teams` em `createApplication()`, antes das migrations: ligar a flag num `beforeEach` é tarde demais.
