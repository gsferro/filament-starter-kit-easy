@extends('errors.sentinel-layout', [
    'code' => 404,
    'tone' => 'gray',
    'title' => 'Página não encontrada',
    'body' => 'A página que você procura foi movida, removida ou o endereço está errado. Confira a URL ou volte para a página anterior.',
    'exception' => $exception ?? null,
])

@section('icon')
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z"/></svg>
@endsection

@section('content')
    <div class="sn-codeblock" style="text-align:center">/{{ ltrim(request()->path(), '/') }}</div>

    <div class="sn-actions">
        <a class="sn-btn sn-btn-primary" href="/">Início</a>
        <a class="sn-btn sn-btn-gray" href="#" onclick="history.back(); return false;">Voltar</a>
    </div>
@endsection
