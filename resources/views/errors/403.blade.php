@php
    use Illuminate\Support\Facades\Auth;

    $user = Auth::user();
    $e = $exception ?? null;

    /*
    | O bloco de diagnóstico só aparece FORA de produção.
    |
    | Ele existe para o desenvolvedor entender por que a policy negou — e é
    | exatamente por isso que não pode chegar ao usuário final: nomes de
    | permissão e papéis descrevem a superfície de autorização da aplicação
    | para quem acabou de ser barrado por ela.
    */
    $mostrarDiagnostico = ! app()->isProduction();

    $auth = [];

    if ($mostrarDiagnostico) {
        if ($user) {
            // E-mail em vez do id: identifica a conta para quem está depurando
            // sem expor a chave primária.
            $auth['Conta'] = $user->email ?? $user->getAuthIdentifier();
        }

        // O que falta diz mais do que o que se tem. A UnauthorizedException do
        // Spatie carrega o que o request exigia; uma AuthorizationException nua
        // não carrega — e aqui nunca se adivinha.
        $exigido = static function (?Throwable $e, string $metodo): array {
            if (! $e || ! method_exists($e, $metodo)) {
                return [];
            }

            try {
                return array_values(array_filter(array_map('strval', (array) $e->{$metodo}())));
            } catch (Throwable) {
                return [];
            }
        };

        $faltando = array_values(array_filter(
            $exigido($e, 'getRequiredPermissions'),
            fn (string $p): bool => ! $user || ! $user->can($p),
        ));

        $papeisFaltando = array_values(array_filter(
            $exigido($e, 'getRequiredRoles'),
            fn (string $r): bool => ! $user || ! method_exists($user, 'hasRole') || ! $user->hasRole($r),
        ));

        if ($faltando) {
            $auth['Permissão ausente'] = implode(', ', $faltando);

            $auth['Tipo'] = match (true) {
                str_starts_with($faltando[0], 'page_') => 'Página',
                str_starts_with($faltando[0], 'widget_') => 'Widget',
                default => 'Permissão customizada',
            };
        }

        if ($papeisFaltando) {
            $auth['Papel ausente'] = implode(', ', $papeisFaltando);
        }

        // Só cai nos papéis que o usuário tem quando a negativa não nomeou o que queria.
        if (! $faltando && ! $papeisFaltando && $user && method_exists($user, 'getRoleNames')) {
            $papeis = $user->getRoleNames()->all();

            if ($papeis) {
                $auth['Seus papéis'] = implode(', ', $papeis);
            }
        }

        if ($e && $e->getMessage() !== '') {
            $auth['Motivo'] = $e->getMessage();
        }
    }

    /*
    | "Voltar" devolve para a página anterior de verdade (o Referer), não para a
    | raiz: quem tomou 403 clicando num item de menu quer continuar de onde
    | estava, e mandar para "/" ainda joga o usuário no painel default, que pode
    | nem ser o painel em que ele trabalha.
    |
    | Só cai na raiz quando não há anterior ou quando o anterior é esta própria
    | página negada — senão o botão viraria um laço.
    */
    $anterior = url()->previous();
    $urlVoltar = ($anterior && $anterior !== url()->current() && $anterior !== url()->full())
        ? $anterior
        : url('/');
@endphp

@extends('errors.sentinel-layout', [
    'code' => 403,
    'tone' => 'warning',
    'title' => 'Acesso negado',
    'body' => 'Você não tem permissão para acessar este recurso. O acesso foi barrado por uma política de segurança ou por falta de privilégios.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
@endsection

@section('content')
    @if ($auth !== [])
        <div class="sn-detail">
            <dl class="sn-rows">
                @foreach ($auth as $rotulo => $valor)
                    <dt>{{ $rotulo }}</dt>
                    <dd class="{{ in_array($rotulo, ['Motivo', 'Tipo'], true) ? '' : 'mono' }}">{{ $valor }}</dd>
                @endforeach
            </dl>
        </div>
    @endif

    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="{{ $urlVoltar }}">Voltar</a>
    </div>

    <div class="sn-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Precisa de acesso a este recurso? Peça as permissões ao administrador da aplicação.</span>
    </div>
@endsection
