<?php

namespace App\Support;

/**
 * A assinatura do sistema no rodapé — composta em UM lugar, exibida em dois.
 *
 * ── Por que esta classe existe ──
 *
 * Havia dois rodapés que nasceram em features diferentes e nunca foram reconciliados: o dos
 * painéis, composto por código, e o da tela de login, texto livre do admin. O pedido foi que
 * fossem coerentes.
 *
 * Coerência aqui é de **composição**, não de conteúdo: a linha é montada aqui e as duas
 * superfícies a usam. O que muda entre elas é **quem vê o quê** — e essa decisão NÃO mora nesta
 * classe.
 *
 * ── A classe não decide a audiência, e isso é deliberado (ADR-02) ──
 *
 * `partes()` recebe `$comVersao` em vez de chamar `filament()->auth()->check()` por dentro. Três
 * razões, e a terceira é a que pesa:
 *
 * 1. fica testável sem painel montado, sem request e sem sessão — o argumento é o eixo do caso;
 * 2. compor uma linha e decidir quem pode ver são responsabilidades diferentes;
 * 3. **a regra de segurança fica onde alguém vai procurá-la.** Enterrada aqui, a frase que
 *    explica por que a versão não aparece para visitante ficaria a dois saltos da tela. Na blade,
 *    ela está no ponto em que a audiência é conhecida — e é lá que o próximo leitor pergunta
 *    "quem vê isto?".
 *
 * ── O ano é o corrente, e vem do fuso da aplicação ──
 *
 * `now()` respeita `config('app.timezone')`. Ler o ano em UTC daria resposta diferente por até
 * três horas na virada, para quem está a oeste de Greenwich — e é essa janela que o `[CT-22]`
 * exerce. Decisão do usuário, com a consequência aceita de o texto mudar sozinho em 1º de janeiro
 * (ADR-04).
 *
 * ── `filled()` em cada parte ──
 *
 * `empty('0')` é `true` em PHP, e uma versão `0` sumiria levando o separador junto. `filled()`
 * trata `'0'` como presente e `'   '` como ausente, que é o que se quer nos dois casos.
 */
class AssinaturaDoRodape
{
    /**
     * As partes da assinatura, na ordem de exibição.
     *
     * @param  bool  $comVersao  quem chama é quem sabe a audiência — ver o bloco acima
     * @return list<string>
     */
    public static function partes(bool $comVersao): array
    {
        $partes = [];

        if (filled($nome = config('app.name'))) {
            $partes[] = '© '.now()->year.' '.$nome;
        }

        if (! $comVersao) {
            return $partes;
        }

        if (filled($versao = config('app.version'))) {
            $partes[] = 'v'.$versao;
        }

        if (config('kit.exibir_versao') && filled($versaoDoKit = config('kit.version'))) {
            $partes[] = 'kit '.$versaoDoKit;
        }

        return $partes;
    }
}
