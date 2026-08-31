<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Closure;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

/**
 * A coluna, o filtro e a ação que liberam um cadastro pendente de aprovação.
 *
 * ## Por que uma trait, e por que ela NÃO contradiz a ADR-04 do `admin-da-organizacao`
 *
 * Aquela ADR recusou uma **classe-base compartilhada** entre os dois `UserResource`, e o
 * motivo é bom: o que os dois têm de igual são quatro campos de formulário, e o que têm de
 * diferente é regra de segurança (query escopada, papéis filtrados por painel, sem exclusão,
 * sem impersonate). Uma base faria uma edição pensada no /admin **alargar o /app em silêncio**.
 *
 * O que está aqui é o oposto disso: três pedaços de UI cuja regra é **idêntica nos dois
 * painéis por definição** — "aprovar um cadastro pendente é dar-lhe o papel do /app". Se essa
 * regra divergir entre os painéis, o defeito é a divergência, não o compartilhamento. Duplicar
 * as três é convidar exatamente a deriva que ninguém percebe: o filtro corrigido num painel e
 * não no outro.
 *
 * A trait também não é a barreira. Quem decide se a pessoa entra é `User::canAccessPanel()`, e
 * quem faz a transição é `User::aprovar()` — no model, chamável direto. Aqui só vive a
 * apresentação. É a distinção que `.ai/rules/filament.md` cobra.
 *
 * ## `->authorize()` não é decoração
 *
 * **Action do Filament não consulta policy sozinha.** O vendor diz isso em comentário no
 * próprio código: `$authorization` nasce `null`, "allowed for all users"
 * (`vendor/filament/actions/src/Concerns/CanBeAuthorized.php:15-22`). Sem a linha, qualquer
 * pessoa que consiga abrir a listagem aprova cadastro — e no /app essa pessoa pode ser o
 * usuário comum da organização. `update` é o método de policy certo: quem pode editar o
 * cadastro é quem pode liberá-lo, e `panel_user` não tem `Update:User` (o `PapeisSeeder`
 * subtrai a administração do painel `app`).
 */
trait AprovacaoDeCadastro
{
    /**
     * O campo de papéis é obrigatório — **exceto** enquanto o cadastro está pendente.
     *
     * Sem esta exceção o formulário de edição fica **impossível de salvar** para exatamente as
     * pessoas que esta feature cria. O campo `roles` é `->required()` nos dois `UserResource`,
     * com uma razão boa ("usuário sem papel é conta morta: autentica e leva 403 nos três
     * painéis"), mas o cadastro pendente **não tem papel por desenho** — quem o dá é
     * `User::aprovar()`. Abrir a edição de um pendente e trocar o nome devolvia
     * *"É obrigatória a indicação de um valor para o campo papéis"*, e a única saída era
     * atribuir um papel à mão — o que dá acesso sem passar pela aprovação e deixa o registro
     * num estado incoerente (com papel, ainda pendente).
     *
     * Encontrado por CT-16, que existia para outra coisa. Fica aqui, e não duplicado nos dois
     * resources, para que a regra não derive entre os painéis.
     */
    protected static function papelObrigatorioNaEdicao(): Closure
    {
        // `$record === null` é a página de CRIAÇÃO, onde papel continua obrigatório: criar
        // usuário sem papel pela tela é criar conta morta, que é o que a regra original protege.
        return static fn (?User $record): bool => $record === null || ! $record->aprovacao_pendente;
    }

    // A coluna "Situação" (Pendente/Inativo/Ativo) vive em `SituacaoDaConta::colunaDeSituacao()`:
    // o estado ganhou um terceiro valor e foi morar onde ele é decidido.

    /**
     * O filtro de pendentes — a metade operacional de "alguém aprova".
     *
     * Sem ele, achar quem está esperando numa organização com centenas de usuários é olho no
     * olho, e a aprovação deixa de acontecer por atrito em vez de por decisão.
     */
    protected static function filtroDePendentes(): Filter
    {
        return Filter::make('aprovacao_pendente')
            ->label('Somente pendentes de aprovação')
            ->query(self::recorteDePendentes(...));
    }

    /**
     * O recorte, sem a embalagem — uma definição para o filtro e para a aba.
     *
     * O filtro é para **combinar** (com busca, com a lixeira, com outro filtro); a aba é o
     * recorte de **um clique**. Os dois dizem a mesma coisa, e por isso a dizem no mesmo
     * lugar: escrever `where('aprovacao_pendente', true)` de novo dentro de cada `getTabs()`
     * criaria quatro cópias que derivam no dia em que "pendente" mudar de definição — o
     * filtro dizendo uma coisa, a aba outra, ambos verdes. ADR-01 da wiki abas-nas-listagens.
     *
     * O tipo é genérico porque o chamador é o `getEloquentQuery()` do Resource, que o
     * Filament declara como `Builder<Model>` — e é ele que carrega o escopo de organização
     * do /app. Estreitar para `Builder<User>` obrigaria a chamar `User::query()` na aba, que
     * é justamente o que `.ai/rules/filament.md` proíbe.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    public static function recorteDePendentes(Builder $query): Builder
    {
        return $query->where('aprovacao_pendente', true);
    }

    /**
     * Aprovar — visível só para quem está pendente, e autorizada sempre.
     *
     * Sem aprovação em massa, de propósito: aprovar é decidir quem entra, um a um. A versão em
     * lote é a que se usa sem ler.
     */
    protected static function acaoDeAprovar(): Action
    {
        return Action::make('aprovar')
            ->label('Aprovar')
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->authorize('update')
            ->visible(fn (User $record): bool => $record->aprovacao_pendente)
            ->requiresConfirmation()
            ->modalHeading('Aprovar este cadastro?')
            ->modalDescription('A pessoa passa a acessar o painel de negócio com o perfil básico. Você pode ajustar os papéis dela depois, na edição.')
            ->successNotificationTitle('Cadastro aprovado')
            ->action(function (User $record): void {
                $record->aprovar();
            });
    }
}
