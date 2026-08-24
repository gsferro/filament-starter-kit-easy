<?php

use App\Listeners\AuditarConfiguracoesDoKit;
use App\Settings\ConfiguracoesDoKit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Apaga o `client_secret` do Google que ficou EM CLARO na tabela `audits`.
 *
 * ## O terceiro sintoma do defeito do ADR-06
 *
 * `App\Listeners\AuditarConfiguracoesDoKit` decide se mascara um valor com uma linha só:
 *
 *     $ehSegredo = in_array($propriedade, ConfiguracoesDoKit::encrypted(), true);
 *
 * Uma lista, três consumidores — o decifrador da leitura, o cifrador da gravação e esta máscara.
 * E `login_google_client_secret` estava fora da lista desde que o campo nasceu (v0.19.2), então
 * **toda** vez que alguém salvou o segredo do Google pela tela de configurações, o valor foi
 * gravado em claro nas colunas `old_values` e `new_values` desta tabela.
 *
 * Este é o sintoma mais desagradável dos três, e o menos visível: o segredo não vazava só para
 * quem tem acesso ao banco — vazava para a **tela de auditoria**, que é justamente onde ele é
 * exibido para leitura. Os outros dois (leitura devolvendo ciphertext, gravação em texto claro)
 * estão descritos no docblock de `ConfiguracoesDoKit::encrypted()` e no ADR-06.
 *
 * Consertar `encrypted()` fecha o vazamento **daqui para a frente**, e de graça — a máscara lê a
 * mesma lista. O que não se conserta sozinho é o que **já está gravado**, e é isso que esta
 * migration faz.
 *
 * ## Por que mascarar e não apagar a linha
 *
 * A linha da trilha é o registro de que alguém alterou a configuração, e ela tem valor: quem,
 * quando, de qual IP. Apagá-la para remover o segredo destruiria a auditoria para consertar um
 * vazamento — o que é trocar um problema por outro. O que sai é **só o valor que nunca deveria
 * ter entrado**, substituído pela mesma máscara que o listener usa hoje.
 *
 * A máscara vem da constante do listener, e não de um literal repetido aqui: duas cópias de uma
 * máscara divergem, e a divergência faria a tela mostrar dois "segredos escondidos" diferentes.
 *
 * ## Escopo estreito de propósito
 *
 * Só a chave `login_google_client_secret`, e só nas linhas do grupo de configurações do kit. Os
 * três `client_secret` novos nunca foram gravados em claro, porque nasceram dentro de
 * `encrypted()`; e `mail_password` sempre esteve lá. Varrer mais do que o necessário numa
 * migration que reescreve dado é como se perde dado alheio.
 *
 * Idempotente: rodar duas vezes não muda nada na segunda, porque a máscara não casa com a
 * condição de busca.
 *
 * `down()` é deliberadamente vazio — ver o método.
 *
 * Ver ADR-06 de wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/.
 */
return new class extends Migration
{
    private const PROPRIEDADE = 'login_google_client_secret';

    public function up(): void
    {
        if (! Schema::hasTable('audits')) {
            return;
        }

        $mascarados = 0;

        /*
         * Consulta crua e não o model: `Audit` do pacote de auditoria tem casts, escopos e
         * eventos, e uma migration que reescreve dado não deve depender de nenhum dos três — o
         * model pode mudar, a tabela gravada não. O `LIKE` é a peneira grossa (barata, com o
         * índice de `tags`); quem decide é o `json_decode` de cada linha.
         */
        DB::table('audits')
            ->where('tags', 'configuracoes-do-kit')
            ->where(function ($consulta): void {
                $consulta
                    ->where('old_values', 'like', '%'.self::PROPRIEDADE.'%')
                    ->orWhere('new_values', 'like', '%'.self::PROPRIEDADE.'%');
            })
            ->orderBy('id')
            ->each(function (object $linha) use (&$mascarados): void {
                $novo = [];

                foreach (['old_values', 'new_values'] as $coluna) {
                    $valores = json_decode((string) $linha->{$coluna}, associative: true);

                    if (! is_array($valores) || ! array_key_exists(self::PROPRIEDADE, $valores)) {
                        continue;
                    }

                    $atual = $valores[self::PROPRIEDADE];

                    // `null` é ausência de segredo, e a máscara mentiria dizendo que havia um.
                    if ($atual === null || $atual === AuditarConfiguracoesDoKit::SEGREDO_MASCARADO) {
                        continue;
                    }

                    $valores[self::PROPRIEDADE] = AuditarConfiguracoesDoKit::SEGREDO_MASCARADO;

                    $novo[$coluna] = json_encode($valores);
                }

                if ($novo === []) {
                    return;
                }

                DB::table('audits')->where('id', $linha->id)->update($novo);

                $mascarados++;
            });

        if ($mascarados === 0) {
            return;
        }

        /*
         * Registrado, e no channel de configurações: quem administra a instalação precisa saber
         * que houve exposição, por quanto tempo e o que fazer a seguir. Migration que conserta
         * vazamento em silêncio deixa quem opera sem a informação de que a credencial precisa ser
         * ROTACIONADA — mascarar a trilha não desfaz o fato de o valor ter estado legível.
         */
        Log::channel('configuracoes')->warning(
            '[migration@mascarar_segredo_do_google_na_trilha] Segredo do Google mascarado na trilha de auditoria'
            ." | linhas: {$mascarados}",
            [
                'linhas'      => $mascarados,
                'propriedade' => self::PROPRIEDADE,
                'grupo'       => ConfiguracoesDoKit::group(),
                'motivo'      => 'segredo_em_claro_na_trilha',
                'acao'        => 'rotacione o GOOGLE_CLIENT_SECRET no console do provedor',
            ],
        );
    }

    /**
     * Sem volta, e é a decisão certa.
     *
     * O valor original não é guardado em lugar nenhum — guardá-lo para poder "reverter" seria
     * manter o vazamento com outro nome. Um `down()` que não restaura é honesto; um que restaura
     * seria o defeito.
     */
    public function down(): void
    {
        //
    }
};
