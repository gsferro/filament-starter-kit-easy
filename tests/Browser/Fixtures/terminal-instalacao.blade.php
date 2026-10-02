{{--
    Fixture de teste — NÃO é rota pública, só renderizada em teste (`view()->file(...)`).

    Desenha como terminal a saída REAL de `php artisan kit:install --ansi --create-project`
    (v0.41.1), transcrita de um `create-project` de validação, sem código ANSI. É a fonte do
    `art/install.gif`: cada quadro é um recorte progressivo desta MESMA transcrição, via `$ate`
    — nunca uma segunda fixture (wikis/specs/feat/diagramas-da-arquitetura/.../01-plano-acao.md,
    passo 21).

    R35 (CT-52): a senha gerada aparece MASCARADA — 24 caracteres alfanuméricos fixos, nunca o
    valor real nem a palavra "password". A linha de aviso ("Esta senha foi gerada agora e NAO
    sera mostrada de novo") é transcrita tal como o kit imprime hoje, sem os acentos que o resto
    da saída tem — não é erro de digitação desta fixture, é o texto real de
    `app/Console/Commands/KitInstall.php`.
--}}
@php
    /**
     * As linhas da transcrição, na ordem real da saída (`INFO Instalando..` até a linha final
     * do repositório). `classe` só estiliza a cor — nenhum teste depende dela.
     *
     * @var list<array{texto: string, classe: string}>
     */
    $linhasDaTranscricao = [
        ['texto' => ' INFO Instalando o starter-kit-easy..', 'classe' => 'l-info'],
        ['texto' => '', 'classe' => 'l'],
        ['texto' => ' Gerando APP_KEY .. 0.09ms DONE', 'classe' => 'l-done'],
        ['texto' => ' Criando banco SQLite .. 0.00ms DONE', 'classe' => 'l-done'],
        ['texto' => ' Rodando migrations .. 2s DONE', 'classe' => 'l-done'],
        ['texto' => ' Gerando senha do administrador .. 0.00ms DONE', 'classe' => 'l-done'],
        ['texto' => ' Populando papéis, permissões e usuário inicial .. 2s DONE', 'classe' => 'l-done'],
        ['texto' => ' Formatando o código gerado .. 853.06ms DONE', 'classe' => 'l-done'],
        ['texto' => ' Publicando assets do Filament .. 122.48ms DONE', 'classe' => 'l-done'],
        ['texto' => ' npm install .. 3s DONE', 'classe' => 'l-done'],
        ['texto' => ' npm run build .. 5s DONE', 'classe' => 'l-done'],
        ['texto' => ' Removendo o vínculo com o Snyk do kit (1 arquivo(s)) .. 0.00ms DONE', 'classe' => 'l-done'],
        ['texto' => '', 'classe' => 'l'],
        ['texto' => ' INFO Pronto! O projeto está instalado.', 'classe' => 'l-info'],
        ['texto' => '', 'classe' => 'l'],
        ['texto' => ' ⇂ Negócio: http://localhost:8000/app', 'classe' => 'l'],
        ['texto' => ' ⇂ Administração: http://localhost:8000/admin', 'classe' => 'l'],
        ['texto' => ' ⇂ Infraestrutura: http://localhost:8000/infra', 'classe' => 'l'],
        ['texto' => '', 'classe' => 'l'],
        // A senha real tem 24 caracteres alfanuméricos (medido numa instalação real) — a máscara
        // repete o comprimento, nunca o valor: nunca vira pista de senha nenhuma.
        ['texto' => ' Login inicial: admin@example.com / XXXXXXXXXXXXXXXXXXXXXXXX', 'classe' => 'l-senha'],
        ['texto' => 'Esta senha foi gerada agora e NAO sera mostrada de novo — anote.', 'classe' => 'l-aviso'],
        ['texto' => 'Para trocar: php artisan kit:admin', 'classe' => 'l'],
        ['texto' => ' ⇂ Suba o servidor com: composer dev', 'classe' => 'l'],
        ['texto' => ' ⇂ Serviços opcionais (Postgres, Redis, IA local): docker compose up -d', 'classe' => 'l'],
        ['texto' => '', 'classe' => 'l'],
        /*
         * (QA-13) O WARN "este terminal não aceitou perguntas" é ruído do ambiente em que a
         * transcrição foi colhida (Windows, Composer sem repasse de terminal), não uma saída
         * que toda instalação emite — nos terminais que aceitam as perguntas ele nem existe.
         * Uma fixture que o ensina como parte do produto documenta um caso de borda no GIF
         * final, então ela omite de propósito. Quem precisa do texto real está em
         * `avisarSePerdeuAsPerguntas()` do `KitInstall`.
         */
        ['texto' => '', 'classe' => 'l'],
        ['texto' => ' Repositório do kit .. https://github.com/gsferro/filament-starter-kit-easy', 'classe' => 'l'],
    ];

    // `$ate` recorta o quadro progressivo (índice da última linha visível). Sem parâmetro,
    // mostra a transcrição inteira — é o que a captura do quadro final usa.
    $indiceFinal = count($linhasDaTranscricao) - 1;
    $ate       ??= $indiceFinal;
    $ate         = max(0, min($indiceFinal, (int) $ate));

    $linhasVisiveis = array_slice($linhasDaTranscricao, 0, $ate + 1);
@endphp
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<title>terminal-instalacao (fixture)</title>
<style>
    :root {
        color-scheme: dark;
    }

    * {
        box-sizing: border-box;
    }

    body {
        margin: 0;
        padding: 48px;
        background: #0d1117;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .janela {
        width: 1000px;
        border-radius: 10px;
        overflow: hidden;
        background: #1e1e1e;
        box-shadow: 0 20px 60px rgba(0, 0, 0, .55);
        font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, monospace;
    }

    .barra-titulo {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 14px;
        background: #2a2a2a;
        border-bottom: 1px solid #3a3a3a;
    }

    .botao {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .botao.vermelho { background: #ff5f56; }
    .botao.amarelo  { background: #ffbd2e; }
    .botao.verde    { background: #27c93f; }

    .titulo {
        flex: 1;
        text-align: center;
        color: #9a9a9a;
        font-size: 13px;
        margin-right: 54px;
    }

    .corpo {
        padding: 18px 20px;
        min-height: 360px;
        font-size: 14px;
        line-height: 1.55;
        white-space: pre-wrap;
        word-break: break-word;
    }

    .l      { color: #d4d4d4; }
    .l-info { color: #61afef; font-weight: 600; }
    .l-done { color: #98c379; }
    .l-warn { color: #e5c07b; }
    .l-senha  { color: #e06c75; font-weight: 600; }
    .l-aviso  { color: #e5c07b; }
</style>
</head>
<body>
    <div class="janela">
        <div class="barra-titulo">
            <span class="botao vermelho"></span>
            <span class="botao amarelo"></span>
            <span class="botao verde"></span>
            <span class="titulo">meu-projeto — instalação</span>
        </div>
        <div class="corpo">
            @foreach ($linhasVisiveis as $linha)
                <div class="{{ $linha['classe'] }}">{{ $linha['texto'] !== '' ? $linha['texto'] : "\u{00A0}" }}</div>
            @endforeach
        </div>
    </div>
</body>
</html>
