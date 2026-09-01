{{--
    Badge do papel, terceira linha do cabeçalho do menu do usuário.

    Só é chamada pelo `filament/user-menu-header.blade.php`, por inclusão — não tem
    registro próprio em render hook. Mora separada porque a pergunta "qual papel eu
    mostro" tem regra (master global vence, painel corrente decide, papel sem painel
    não rende badge) e o cabeçalho tem layout. Misturar as duas coisas num arquivo só
    é o que faz um bloco PHP de vinte linhas nascer no meio de uma div.

    Blade puro, sem estado. Quem sabe a regra é `User::papelDoPainel()`; aqui só se
    escolhe o ícone e se pinta.

    O rótulo sai de `App\Support\Papeis::rotulo()`, nunca escrito à mão: `panel_user`
    é chave, não texto de tela, e o kit já tem sete lugares que precisam do mesmo
    rótulo. Um oitavo escrito aqui seria o que diverge.

    Sem papel para este painel, NADA é renderizado — nem badge vazio, nem traço.
    Papel sem painel é estado normal do modelo (`roles.painel` nulo não é coringa), e
    um badge dizendo "—" afirmaria que falta alguma coisa.

    Par claro/escuro explícito nas classes: `assertSee` não valida tema, então badge
    sem variante `dark:` passa no teste e fica ilegível no dark mode.

    NENHUMA diretiva do Blade escrita neste comentário, nem entre crases, nem como
    exemplo. Comentário do Blade NÃO protege o que está dentro: o compilador processa
    as diretivas primeiro e só depois remove o comentário, então uma menção a uma
    diretiva de inclusão aqui vira código no arquivo compilado e a página inteira morre
    com `ParseError` — apontando para `storage/framework/views/<hash>.php`, longe daqui.
    Foi exatamente o que aconteceu na primeira versão desta view.
--}}
@php
    $painelDoBadge = filament()->getCurrentOrDefaultPanel()?->getId();

    // A ORGANIZAÇÃO aberta entra na pergunta: a mesma pessoa pode ter papéis diferentes do
    // painel `app` em organizações diferentes, e o badge afirma como ela está NESTA. Sem
    // organização corrente (/admin, /infra, ou a tela de escolha de organização) o valor é nulo
    // e a consulta volta a ser a de antes — que é o certo ali. Ver ADR-01 da wiki
    // badge-de-papel-por-organizacao.
    $contextoDoBadge = filament()->getTenant()?->getKey();

    $papelDoBadge = $painelDoBadge
        ? filament()->auth()->user()?->papelDoPainel($painelDoBadge, $contextoDoBadge)
        : null;

    $iconeDoBadge = $papelDoBadge === config('filament-shield.super_admin.name', 'master_global')
        ? \Filament\Support\Icons\Heroicon::OutlinedShieldCheck
        : \Filament\Support\Icons\Heroicon::OutlinedUserCircle;
@endphp

@if ($papelDoBadge)
    <span
        class="fi-badge inline-flex w-fit items-center gap-1 rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-600 dark:bg-primary-400/10 dark:text-primary-400"
        title="{{ \App\Support\Papeis::rotuloDoPainel($painelDoBadge) }}"
    >
        <x-filament::icon :icon="$iconeDoBadge" class="h-4 w-4" />

        {{ \App\Support\Papeis::rotulo($papelDoBadge) }}
    </span>
@endif
