---
paths:
  - 'app/Livewire/**'
---

# Livewire

## Valor vindo do navegador nunca entra na mensagem de log
Propriedade pública e argumento de ação de componente Livewire são entrada do cliente: não são interpolados na mensagem do log, mesmo no formato `[Classe@Método] mensagem | param: valor`. Se o valor precisa ficar na trilha, vai no context sem quebra de linha e truncado: `Str::limit((string) preg_replace('/\s+/', ' ', $valor), 80)`. O formatter do Laravel (`allowInlineLineBreaks`) devolve a quebra — inclusive a do JSON do context — como linha real, e o cliente forja registros inteiros no arquivo de log. Origem: P-26 / QA-11 de `wikis/specs/feat/onboarding-guiado/`; prova: `tests/Kit/OnboardingGuiadoConsumoTest.php` [CT-75]. O `AssistenteChatWidget` ainda interpola o `$conversaId` público e precisa da mesma correção.
