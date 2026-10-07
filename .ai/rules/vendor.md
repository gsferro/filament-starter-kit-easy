---
paths:
  - 'resources/views/vendor/**'
---

# Vendor

## View editada pelo kit em resources/views/vendor exige a pasta em CAMINHOS_DO_KIT; publish cru não entra
Ao editar ou criar view em `resources/views/vendor/{pacote}`, a pasta inteira tem de estar em `KitUpdate::CAMINHOS_DO_KIT` (`app/Console/Commands/KitUpdate.php`), senão o `kit:update` nunca a entrega a quem já instalou — o `composer create-project` entrega tudo que o `.gitattributes` não exclui, o `kit:update` só o que está nessa lista (as duas rotas de entrega). Pasta cujas views são idênticas às do pacote instalado (publish cru de `vendor:publish`) NÃO entra na lista: o update sobrescreveria customização do projeto. Autoral = conteúdo diferente de toda view de mesmo caminho relativo em `vendor/*/*/resources/views`. Enforçado em `tests/Kit/KitUpdateTest.php` (CT-06: autoral fora da lista reprova; CT-07: crua dentro dela reprova) — não contornar. Issue #148; wiki `wikis/specs/fix/kit-update-views-vendor/`.
