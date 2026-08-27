@php
    /*
    | A tela de aviso de quem tentou entrar com uma conta desativada ou excluída.
    |
    | Reaproveita o layout do Sentinel publicado em `errors/sentinel-layout.blade.php` — mesma
    | cara, mesmo número de mensagem — em vez do 403 dele, que só exibe o motivo fora de
    | produção. Aqui o texto é o ponto: a pessoa precisa saber POR QUE não entrou e a quem
    | recorrer. Quem chega até aqui já provou ser dona da conta (senha certa, ou e-mail
    | verificado no provedor) — ver ADR-03 da wiki status-e-exclusao-logica-de-usuario.
    |
    | @var string $motivo      'conta_inativa' | 'conta_excluida'
    | @var \Carbon\CarbonInterface|null $excluidaEm
    | @var string $voltarPara  URL do login de onde a pessoa veio
    */
    $excluida = $motivo === 'conta_excluida';

    $titulo = $excluida ? 'Conta excluída' : 'Conta desativada';

    $corpo = $excluida
        ? 'Sua conta foi excluída em '.($excluidaEm?->format('d/m/Y') ?? 'data não registrada').'. Entre em contato com o administrador para restaurá-la.'
        : 'Sua conta está desativada. Entre em contato com o administrador para reativá-la.';
@endphp

@extends('errors.sentinel-layout', [
    'code' => 403,
    'tone' => 'warning',
    'title' => $titulo,
    'body' => $corpo,
    'exception' => null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"/></svg>
@endsection

@section('content')
    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="{{ $voltarPara }}">Voltar ao login</a>
    </div>

    <div class="sn-note">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z"/></svg>
        <span>Quem administra a aplicação pode {{ $excluida ? 'restaurar' : 'reativar' }} a sua conta. Nada foi apagado.</span>
    </div>
@endsection
