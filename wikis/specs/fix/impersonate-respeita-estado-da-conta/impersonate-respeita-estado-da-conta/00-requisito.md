# Requisito — Fix: conta indisponível não pode ser personificada

## Fonte

- **Origem**: invocação `/feature-wiki` no chat, pelo mantenedor do kit
- **Data**: 2026-09-02
- **Autor / solicitante**: mantenedor do kit
- **Fidelidade**: **baixa** — uma linha, com o nome da funcionalidade escrito em inglês
  aproximado. A identificação do alvo está resolvida (ver abaixo), mas o **alcance** dos estados
  é premissa a confirmar (A1).

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> precisamos de um fix quando o usuário estiver o status desativado, não liberar a opção de "personality"

## O que é "personality", resolvido antes do plano

O termo **não existe** no kit — confirmado por varredura em `app/`, `config/`, `resources/` e no
vendor do Filament e dos plugins. O que existe, e é inequívoco:

`stechstudio/filament-impersonate` (`composer.json:70`, `^5.5`) fornece a ação **Impersonate**
("Personificar"), registrada na lista de usuários do `/admin`
(`app/Filament/Admin/Resources/Users/UserResource.php:222`, importada em `:36`). O `/app` **não**
a tem, de propósito (`app/Filament/App/Resources/Users/UserResource.php:279`: *"Sem Impersonate (é
privilégio do master_global)"*).

**"personality" = a ação Impersonate.** É a única funcionalidade do kit cujo nome contém a
raiz do termo (`personate`), é uma **opção** que se libera ou não por registro, e é exatamente o
tipo de opção que faz sentido negar sobre uma conta desativada.

## O defeito, verificado no código

`User::canBeImpersonated()` (`app/Models/User.php:709-713`) é:

```php
public function canBeImpersonated(): bool
{
    // Master global nunca é alvo de impersonação.
    return ! $this->isMasterGlobal();
}
```

Ela **não consulta o estado da conta**. Já `User::canAccessPanel()` recusa explicitamente três
estados, nesta ordem e com esse cuidado documentado no próprio método:

| Estado | Coluna | `canAccessPanel()` | `canBeImpersonated()` hoje |
|---|---|---|---|
| Inativa (desativada) | `ativo = false` | **recusa** (via `motivoDeIndisponibilidade()`) | **libera** ← o defeito pedido |
| Pendente de aprovação | `aprovacao_pendente = true` | **recusa** | **libera** ← mesmo defeito, outro estado |
| Excluída (lógica) | `deleted_at` | **recusa** (via `motivoDeIndisponibilidade()`) | libera, mas o **vendor** barra — ver abaixo |
| Master global | papel | libera | recusa |

`motivoDeIndisponibilidade()` (`app/Models/User.php:~640`) devolve `'conta_excluida'`,
`'conta_inativa'` ou `null`, e é a pergunta única que o login por senha, o login social e o
middleware do painel já fazem (ADR-01 da wiki `status-e-exclusao-logica-de-usuario`).

**Consequência**: um `master_global` entra no painel **como** uma conta que o próprio kit acabou
de barrar no login. A pessoa desativada não consegue entrar; o administrador consegue entrar por
ela. É contorno de uma decisão de fronteira de acesso, não conveniência de UI.

**A conta excluída está protegida por acidente de configuração, não por decisão do kit.** Quem
barra é o vendor: `Impersonate::canImpersonate()` recusa quando
`isSoftDeleted($target) && ! config('filament-impersonate.allow_soft_deleted')`
(`vendor/stechstudio/filament-impersonate/src/Actions/Impersonate.php:157-159`), e o default do
pacote é `false` (`vendor/stechstudio/filament-impersonate/config/filament-impersonate.php:9`).
O kit **não publicou** essa config — não existe `config/filament-impersonate.php`. Basta alguém
escrever `FILAMENT_IMPERSONATE_ALLOW_SOFT_DELETED=true` no `.env` para a conta excluída voltar a
ser personificável, **e o `canBeImpersonated()` do kit não seguraria**.

**A correção no model fecha os dois lados, e isso é verificado**: o vendor consulta
`canBeImpersonated()` no `visible()` da ação (`:37`) **e** dentro do `impersonate()` antes de
executar (`:112` → `:167`). Não é barreira só de tela — é a régua que `.ai/rules/filament.md:19-29`
exige.

**Não há um único teste de impersonate no kit** (`grep -rln -i impersonat tests/` devolve vazio).
A funcionalidade está registrada e sem cobertura nenhuma.

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Conta com `ativo = false` não pode ser personificada — a ação não aparece na lista de usuários | "quando o usuário estiver o status desativado, não liberar a opção" | autorização |
| RQ-02 | A recusa vale também quando a ação é disparada fora da tela, não só no `visible()` | "não liberar a opção" (a opção é a ação, não o botão) | autorização / restrição |
| RQ-03 | A conta volta a ser personificável depois de reativada | implícito em "quando estiver com o status desativado" | funcional |
| RQ-04 | O comportamento atual preservado: `master_global` nunca é alvo | — (regra existente, não pode regredir) | restrição |

## Ambiguidades e Perguntas Abertas

- **A1 — o fix cobre só a conta desativada, ou todo estado que barra o acesso ao painel?**
  O texto diz "status desativado". Mas `aprovacao_pendente` e `deleted_at` são o mesmo tipo de
  estado, e os três são recusados juntos por `canAccessPanel()`.
  - **Assumido**: **todo estado que barra o painel** — a régua passa a ser a mesma do
    `canAccessPanel()`, via `motivoDeIndisponibilidade()` mais `aprovacao_pendente`. Motivo: as
    três contas têm em comum exatamente a propriedade que torna a personificação incoerente
    (não conseguem entrar sozinhas), e escolher só uma deixaria duas portas abertas com o mesmo
    argumento por trás.
  - **Se negado** (só `ativo = false`): a implementação encolhe para uma condição, e os cenários
    de pendente e de excluída saem do `04`. **A conta excluída volta a depender do default de
    uma config do vendor**, o que fica registrado como débito.
- **A2 — a pessoa que tenta personificar uma conta indisponível deve ver uma mensagem dizendo
  por quê, ou a opção simplesmente não aparece?**
  - **Assumido**: **a opção não aparece** (é o comportamento do `visible()` do vendor, e é como
    as outras ações de estado do kit se comportam). Nenhuma mensagem nova.
  - **Se negado**: entra uma notificação e um cenário para ela.
- **A3 — publicar `config/filament-impersonate.php` para fixar `allow_soft_deleted = false`?**
  - **Assumido**: **não**. Com A1 assumida, o `canBeImpersonated()` do kit já recusa a conta
    excluída por conta própria, e a config do vendor deixa de ser a única guarda. Publicar um
    arquivo de config a mais para reafirmar um default é ruído.
  - **Se negado**: um passo a mais, publicando a config.

## Fora de Escopo (declarado)

- Mudar **quem pode** personificar (`canImpersonate()`, hoje `isMasterGlobal()`) — o requisito
  fala do alvo, não do operador.
- Levar a ação Impersonate para o `/app` — é decisão contrária já registrada em
  `app/Filament/App/Resources/Users/UserResource.php:279`.
- Encerrar automaticamente uma sessão de personificação em curso quando a conta-alvo for
  desativada durante ela. É um caso real e adjacente, mas o requisito pede o **não liberar**, não
  o **interromper**. Registrado como achado para decisão sua.
- O banner e as rotas de saída da personificação.
