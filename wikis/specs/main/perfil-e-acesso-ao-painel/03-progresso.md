# Progresso — Perfil × permissão × acesso ao painel

**Concluída em 2026-08-13.** 110/110 em `composer test:kit`, PHPStan level 6 limpo.

## 1. Coluna `roles.painel`

- [x] `database/migrations/2026_08_13_000001_add_painel_to_roles_table.php`
- [x] `down()` derruba a coluna
- [x] `php artisan migrate` roda limpo nos dois modos

## 2. `App\Support\Paineis`

- [x] `app/Support/Paineis.php` criado
- [x] `opcoes()`
- [x] `permissoes(string $painel)` sobre `getEntitiesPermissions()`
- [x] `resources()` no formato do Shield
- [x] Varredura descartando a instância do Shield por painel, com restauração no `finally`
- [x] Memoização — **no container**, não em propriedade estática (ver Desvios)
- [x] `RuntimeException` quando `filament-shield.discovery.*` estiver ligado

## 3. `User` — acesso vem do papel

- [x] `papeisEmQualquerContexto()` (morphToMany sem o `wherePivot` de team)
- [x] `temPapelDoPainel()` e `isMasterGlobal()` sobre um `temPapelOnde()` privado
- [x] `colunaDeTeam()` e `contextoGlobal()`
- [x] **`temPapelGlobal()` deletado** — `grep -rn temPapelGlobal app/ tests/` não retorna nada
- [x] `canAccessPanel()` com o ramo `$panel->hasTenancy()`
- [x] `warning` no channel `autenticacao` na negativa, com `motivo`

## 4. `PapeisSeeder`

- [x] `papel()` recebe `?string $painel` e usa `updateOrCreate`
- [x] `master_global` → `null`, zero permissions
- [x] `admin` → `admin`; `infra` → `infra`; `panel_user` → `app`
- [x] Matriz recortada por `Paineis::permissoes()`, interseccionada com o que existe no banco
- [x] Casamento por substring removido

## 5. `ShieldPermissionsSeeder`

- [x] Laço sobre `Filament::getPanels()`, com instância limpa do Shield a cada volta
- [x] try/catch por painel

## 6. Tela do Shield agrupada por painel

- [x] `php artisan shield:publish --panel=admin --no-interaction`
- [x] `secaoDoResource()` extraído no `RoleResource` publicado
- [x] `getResourceEntitiesSchema()` agrupando por `Paineis::resources()`
- [x] `Select::make('painel')` com `helperText`
- [x] `'painel'` nas duas listas de `CreateRole` e `EditRole`
- [x] `info` no channel `autenticacao` ao gravar o papel
- [x] Diff contra o vendor mostra só as edições previstas

## 7. `UserResource`

- [x] `roles` → `required()`
- [x] `getOptionLabelFromRecordUsing()` mostrando o painel
- [x] `helperText` explicando de onde vem o acesso
- [x] `Select::make('tenants')` visível e obrigatório só com tenancy
- [x] `saveRelationshipsUsing()`/`syncRoles()` intocado

## 8. `DemoTenancySeeder`

- [x] Ana, Bruno e Carla recebem `panel_user` no contexto da organização de cada um
- [x] Chama o `PapeisSeeder` para ser autossuficiente

## 9. Gates de `/infra`

- [x] Os quatro gates usam `temPapelDoPainel('infra', …)`

## 10. `kit:update`

- [x] `tests/Kit/KitUpdateTest.php` falhou antes da correção, apontando `app/Models/Role.php`
- [x] `'app/Support'` e `'app/Models/Role.php'` em `CAMINHOS_DO_KIT`

## 11. Regra de IA

- [x] Duas regras novas em `.ai/rules/filament.md` (Resource/RelationManager, e papel × painel)
- [x] `CLAUDE.md` e `AGENTS.md` não editados à mão

## 12. Documentação

- [x] `wikis/arquitetura.md`, `wikis/convencoes.md`, `wikis/receitas.md`, `wikis/pacotes.md`
- [x] `README.md` e `README.en.md`

## Testes

- [x] `tests/Kit/PaineisTest.php` — os casos novos ficaram aqui, não num arquivo próprio (ver Desvios)
- [x] `tests/Tenancy/TenancyTest.php` — helper `usuarioComPapel()` e o caso do contexto global
- [x] O caso do `/app` aberto a qualquer autenticado foi invertido
- [x] `composer test:kit` → 110/110

## Verificação Final

- [x] `/ponytail:ponytail-review` no plano, antes de implementar
- [x] `vendor/bin/pint --dirty --format agent`
- [x] `php artisan test --group=kit` — 110 passando
- [x] `composer types:check` — 0 erros
- [x] `git commit` — commitado e mergeado em `main` (`git branch --no-merged main` vazio)

## Blockers

Nenhum.

## Desvios do Plano

| Passo | O que mudou | Por quê |
| --- | --- | --- |
| 2 | Memoização foi para o **container** (`app()->instance('kit.paineis.mapa')`), não uma propriedade estática | Estático sobrevive ao processo inteiro: numa suíte de testes o mapa do primeiro caso valeria para todos os outros, mesmo depois de a aplicação ser recriada |
| 2 | `Facade::clearResolvedInstance('filament-shield')` além do `forgetInstance` | Ver Notas, item 2 — sem isso a feature inteira não funciona |
| 2 | `Paineis::esquecer()` removido | O mapa deriva do CÓDIGO, não do banco; com a memoização no container, o tempo de vida já é o certo |
| 3 | Voltou um `temPapelOnde()` privado, que a auditoria do plano tinha cortado | Sem `->when()` (Notas, item 1) cada método passaria de 5 para 9 linhas — o helper voltou a ser o diff mais curto |
| 4 | `syncPermissions()` recebe a **interseção** com as permissions existentes no banco | Nome que não existe na tabela lança `PermissionDoesNotExist` e derruba o seeder. Acontece sempre que ele roda sem o `ShieldPermissionsSeeder` antes — cenário comum em teste |
| 8 | O passo virou só `DemoTenancySeeder`; o `UsuarioAdminSeeder` saiu | Não mudava nada. Passo que não muda nada não é passo |
| — | **`app/Models/Role.php` (novo)** e `config/permission.php` apontando para ele | Não estava no plano. O `painel` sem model próprio é atributo dinâmico: sem tipo, e reprovado pelo PHPStan em todo acesso |
| — | Testes ficaram em `tests/Kit/PaineisTest.php` e `tests/Tenancy/TenancyTest.php` | Arquivos novos seriam vizinhos com o mesmo `beforeEach` e os mesmos helpers dos que já existem. Dois arquivos a menos |
| — | **`Tests\TestCase::seed()` sobrescrito** | Notas, item 3 |

## Notas de Implementação

Seis armadilhas que o plano não previu. Todas foram para `wikis/convencoes.md#armadilhas-já-resolvidas`.

1. **`->when()` numa relação Eloquent entrega o `Builder`, não a relação.** `wherePivot()` dentro do closure não é aplicado e o filtro some **sem erro nenhum**: `isMasterGlobal()` respondia `false` com a pivot correta no banco. Um `if` faz a coisa certa.

2. **A facade `FilamentShield` cacheia a instância resolvida.** `app()->forgetInstance('filament-shield')` não a alcança — `Facade::$resolvedInstance` continua entregando a antiga. Sintoma: `Filament::getResources()` respondia 6/1/6 nos três painéis enquanto `FilamentShield::getResources()` respondia 6/6/6, e os três papéis nasciam com a mesma matriz de 79 permissões. Foi o bug que quase passou como "funcionou".

3. **`$this->seed()` do Laravel quebra comando aninhado — e a suíte do kit nunca teve permission no banco.** O `seed()` padrão passa por `PendingCommand`, que liga um mock de `OutputStyle` no container; o `shield:generate` chamado de dentro do seeder termina com exit 0, imprime "79 permissions generated" e grava **zero** linhas. Medido: 0 permissions por `$this->seed()`, 186 por `Artisan::call('db:seed')`. Nada acusava porque os testes autenticavam como `master_global`, que vence pelo `Gate::before` justamente sem precisar de permission. `Tests\TestCase::seed()` passou a usar `Artisan::call`.

4. **O Filament injeta parâmetro de closure por NOME, não por tipo.** `getOptionLabelFromRecordUsing(fn (Role $papel) => …)` morre em `[$papel] was unresolvable`, e só ao renderizar o campo. O parâmetro tem de se chamar `$record`.

5. **`#[Override]` em método que vem de trait aborta o request.** `getResourceEntitiesSchema()` vem de `HasShieldFormComponents`; o atributo só vale para método de classe pai.

6. **`config/` fica fora do `kit:update` de propósito**, então `permission.models.role` apontando para `App\Models\Role` **não chega** a quem já instalou. Por isso o `UserResource` tipa o papel pela classe do spatie, não pela do kit: com o type hint concreto, um projeto atualizado teria `TypeError` na tela de usuários. Vale como nota do CHANGELOG.

### Números medidos

| | Antes | Depois |
| --- | --- | --- |
| Permissions no banco | 79 (só `/admin`) | 186 |
| `/app` | 0 | 13 |
| `/infra` | 0 | 96 |
| Testes do grupo `kit` | 100 | 110 |

## Retrospectiva

- **Funcionou bem**: escrever os CTs antes fez a contradição de `painel = null` aparecer ainda na fase de plano — CT-08 nasceu contradizendo ADR-03, e a ADR foi corrigida antes de qualquer linha de código. A auditoria com `/ponytail:ponytail-review` cortou cinco itens que teriam virado código morto.
- **Faltou no plano**: nenhuma das seis armadilhas era previsível pela leitura do vendor — as três primeiras só aparecem executando. O plano teria ganhado um passo "provar o mapa por painel com números reais antes de escrever o seeder": foi assim que o cache da facade apareceu, e ele tinha passado despercebido como sucesso.
- **A herdar**: `App\Models\Role` e `Tests\TestCase::seed()` são fundação para as duas wikis irmãs. A wiki `admin-da-organizacao` registrou, em ADR-06, que `panel_user` precisa **parar** de receber a matriz inteira do painel `app` quando o `UserResource` do `/app` existir — senão todo usuário comum vira admin da organização. Aceito; a subtração pertence àquela feature.
