@extends('errors.sentinel-layout', [
    'code' => 419,
    'tone' => 'info',
    'title' => 'Sua sessão expirou',
    'body' => 'Sua sessão terminou por segurança após um período de inatividade. Entre novamente para continuar de onde parou.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
@endsection

@section('content')
    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="/login">Sign in again</a>
        <a class="sn-btn sn-btn-gray" href="#" onclick="window.location.reload(); return false;">Reload page</a>
    </div>

    <div class="sn-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Sessões expiram automaticamente para proteger sua conta contra acesso não autorizado.</span>
    </div>
@endsection
