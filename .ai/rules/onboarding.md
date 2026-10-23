---
paths:
  - 'app/Filament/**'
  - 'app/Providers/Filament/**'
  - 'database/seeders/Onboarding/**'
  - 'database/seeders/OnboardingSeeder.php'
  - 'app/Filament/Dock/**'
  - 'app/Filament/Spotlight/**'
  - 'app/Support/DockDeAtalhos.php'
  - 'app/Support/DashboardDinamico.php'
  - 'app/Support/AgentesDeIa.php'
  - 'app/Support/TutorialGuiado.php'
  - 'config/lockscreen.php'
---

# Onboarding

## Mexeu numa tela de feature? Revise, atualize ou crie o tutorial guiado dela
Toda tela de painel do kit tem um tutorial guiado semeado por um seeder FILHO em `database/seeders/Onboarding/`, chamado pelo seeder pai `Database\Seeders\OnboardingSeeder`. O mapa tela → filho é `OnboardingSeeder::TELAS` (por painel); tela sem tutorial está em `OnboardingSeeder::ISENTAS` com o motivo. Ao criar, renomear, remover ou mudar o que uma tela faz (campo, coluna, ação, rota, condição de visibilidade), confira o filho dela **no mesmo diff**:

- **mudou a tela** → atualize o texto e as paradas do tour no filho: a rota (`route` é o NOME da rota), o alvo (`part:table`, `part:search`, `action:submit`, `field:{nome}`, `column:{nome}` — nunca CSS do vendor) e o que o passo diz que a tela faz;
- **tela nova** → crie o filho (copie a forma de `Onboarding\UsuariosSeeder`), ponha em `OnboardingSeeder::FILHOS` (ou em `FILHOS_DE_TENANCY`, se a tela só existe com multi-tenant) e a linha em `TELAS`; se ela não merece tutorial, linha em `ISENTAS` com o motivo;
- **feature que a instalação pode desligar** → passo com TELA já some sozinho: a visibilidade padrão é `tela:{chave}`, que pergunta ao `canAccess()` da tela (permissão + interruptor da feature + multi-tenant). Passo SEM tela própria (dock, ⌘K, bloqueio, troca de organização) declara `visibility_condition` com uma chave de `App\Support\TutorialGuiado::condicoes()` — acrescente a closure lá, perguntando ao decisor que a feature já usa;
- **mexeu numa feature base sem tela** (dock, ⌘K, bloqueio de tela, dashboard dinâmico, hub, agentes de IA — os caminhos extras desta rule) → confira o passo dela e a condição que a decide;
- **tela removida** → tire a linha de `TELAS` e o filho que só existia por ela.

O filho grava por `firstOrCreate` na chave (`SeederDeOnboarding::passo()`): rodar o seeder de novo NÃO atualiza passo que já existe — é de propósito, para não apagar o que alguém editou na autoria. Mudança de texto chega a instalação nova; quem já semeou ajusta em /admin/onboarding-flows. Se a mudança precisa chegar a quem já semeou, troque a CHAVE do passo (o antigo continua até alguém apagá-lo) e diga isso no CHANGELOG — mas passo com tela tem a visibilidade `tela:{chave}`, e a condição registrada é `tela:{nome do filho em kebab-case}` (`TutorialGuiado::filhosComTela()`): chave nova sem condição registrada esconde o passo de TODO MUNDO. Então, junto da chave nova, passe `'visibility_condition' => 'tela:{chave antiga}'` no `passo()` (a condição que existe). `tests/Kit/OnboardingGuiadoSemeaduraTest.php` [CT-69] fica vermelho se esquecer.

Enforço: `tests/Kit/InventarioDeOnboardingTest.php` fica VERMELHO com tela registrada fora de `TELAS`/`ISENTAS`, com linha morta no inventário e com filho fora do pai. Documentação do que cada filho popula: `docs/pt/recursos/tutorial-guiado.md` (e `docs/en/`), que muda junto. Origem: `wikis/specs/feat/onboarding-guiado/`.
