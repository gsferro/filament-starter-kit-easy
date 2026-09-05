<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * O ÚNICO ponto do código que lê configuração da tela de login.
 *
 * Este é o contrato, e ele é o motivo de a classe existir: nada mais no kit consulta
 * `config('kit.login...')` nem `config('services.{provedor}')` direto.
 *
 * A tela de Settings chegou, e a ligação custou menos do que o docblock original previa: as
 * chaves entraram no `mapaDeConfiguracao()` das `ConfiguracoesDoKit`, que sobrepõe a config do
 * processo com o que está gravado, no boot. Os predicados seguem lendo `config()` e passam a
 * receber o valor do banco — nem o controller, nem as rotas, nem os blades, nem um caso de
 * teste foi tocado.
 *
 * Isso funciona aqui porque as duas são lidas POR REQUEST: o `abort_unless()` do controller e
 * a closure do render hook do botão. Ler por request é o que separa uma chave editável de um
 * toggle que mente — e quando a leitura é de boot, como era `verificar_email`, o caminho é
 * trocar a decisão fixada na rota por um decisor que pergunta a cada request (foi o que
 * `App\Http\Middleware\ExigirEmailVerificado` fez).
 *
 * `registroAberto()` é o caso especial: ela não lê config nenhuma, delega para a dona da
 * pergunta. Ver o docblock dela.
 *
 * Espalhar essas leituras pelo controller, pelas rotas e pelos blades deixaria a auditoria sem
 * um lugar para olhar. Aqui há um lugar.
 *
 * ## O que mudou quando o segundo provedor entrou
 *
 * `PROVEDOR_GOOGLE` e `googleDisponivel()` sumiram, e no lugar delas há `disponivel()` e
 * `disponiveis()`, com o provedor por parâmetro. Quem nomeia provedor agora é
 * `App\Support\ProvedorSocial`, e o docblock dele explica por que a abstração é um enum e por
 * que ela guarda a verificação de e-mail em vez do redirect. Ver ADR-01 desta wiki, e ADR-10
 * da wiki `login-social-google`, que é a decisão que esta substitui.
 *
 * O que esta classe deliberadamente continua NÃO sendo: uma interface com implementações, nem
 * uma fábrica de provedores. `Socialite::driver($nome)` já é a fábrica.
 *
 * Wiki: `wikis/specs/feat/mais-provedores-sociais/mais-provedores-sociais/`.
 */
final class ConfiguracaoDoLogin
{
    /**
     * O botão e as rotas DESTE provedor entram no ar?
     *
     * Duas condições, em conjunção, porque o requisito pede as duas e porque elas falham por
     * motivos diferentes: o interruptor desligado é escolha de quem instalou, a credencial
     * vazia é descuido de quem configurou.
     *
     * A pergunta é por provedor, e isso é requisito: ligar o GitHub não pode ligar o X, e
     * desligar o Google não pode derrubar os outros três.
     *
     * `filled()` e não `isset()`: chave presente com valor vazio é o caso REAL, e é o que
     * sobra quando alguém apaga o valor do `.env` e deixa o `=`. Com `isset()` o botão
     * apareceria apontando para um OAuth que não existe.
     *
     * As três chaves são conferidas, `client_secret` incluído. Conferir duas e esquecer uma é
     * o mutante mais provável aqui, e há caso de teste com o secret vazio — por provedor — só
     * para ele.
     *
     * ## A terceira condição: em quais painéis
     *
     * `$painel` nulo significa "não estou perguntando por painel", e é o que a tela de Settings
     * quer: ela pergunta se o provedor está CONFIGURADO, não se vale num painel. O default nulo é
     * o que preserva todo chamador anterior a esta wiki.
     *
     * O painel não autorizado é o TERCEIRO motivo possível para a mesma resposta, e ele é escolha
     * de quem configurou — como o interruptor, e diferente da credencial vazia.
     */
    public static function disponivel(ProvedorSocial $provedor, ?string $painel = null): bool
    {
        if (! config("kit.login.{$provedor->value}.habilitado")) {
            return false;
        }

        /*
         * A inversão de `filled()` para `blank()` é para caber a terceira condição sem uma
         * expressão de quatro termos. MESMA semântica — há caso de teste por provedor com cada
         * credencial vazia, e eles são a guarda desta reescrita.
         */
        /** @var array<string, mixed> $credenciais */
        $credenciais = config('services.'.$provedor->value, []);

        if (blank($credenciais['client_id'] ?? null)
            || blank($credenciais['client_secret'] ?? null)
            || blank($credenciais['redirect'] ?? null)) {
            return false;
        }

        return $painel === null || self::painelAutorizado($provedor, $painel);
    }

    /**
     * Este provedor vale NESTE painel?
     *
     * **Lista vazia = todos os painéis**, não nenhum. É a tradução que faz a feature nascer
     * inerte: provedor recém-semeado, ou `.env` com a chave apagada, continua valendo onde valia.
     * Ver ADR-04 de `wikis/specs/feat/login-social-por-painel/login-social-por-painel/`.
     *
     * `in_array` ESTRITO: a lista vem de config e de settings, e comparação frouxa casaria
     * `0 == 'admin'`.
     *
     * Pública porque `LoginSocialController` a chama direto — lá a resposta precisa distinguir
     * "provedor desligado" de "painel não autorizado" para o log da recusa.
     */
    public static function painelAutorizado(ProvedorSocial $provedor, string $painel): bool
    {
        /** @var array<int, string> $paineis */
        $paineis = (array) config("kit.login.{$provedor->value}.paineis", []);

        return $paineis === [] || in_array($painel, $paineis, true);
    }

    /**
     * Os provedores que estão no ar agora, na ordem do enum.
     *
     * É o que o blade dos botões percorre. Lista vazia é o estado normal do kit recém
     * instalado, e o blade não renderiza nem o divisor "ou" nesse caso.
     *
     * @return array<int, ProvedorSocial>
     */
    public static function disponiveis(?string $painel = null): array
    {
        return array_values(array_filter(
            ProvedorSocial::cases(),
            static fn (ProvedorSocial $provedor): bool => self::disponivel($provedor, $painel),
        ));
    }

    /**
     * Texto do rodapé da tela de login, ou null quando não há rodapé.
     *
     * `filled()` trata string de espaços como vazia, então rodapé com um espaço não renderiza
     * uma faixa vazia na tela.
     *
     * É TEXTO. Quem renderiza escapa, porque o valor vem de campo editável e a tela de login é
     * pública e não autenticada — HTML cru ali é XSS armazenado com o pior alcance possível.
     * Ver ADR-09 da wiki `login-social-google`.
     */
    public static function rodapeDoLogin(): ?string
    {
        $rodape = config('kit.login.rodape');

        return filled($rodape) ? trim((string) $rodape) : null;
    }

    /**
     * O registro aberto está ligado? Default false.
     *
     * **Delega para `RegistroAberto::habilitado()`, e a delegação corrige um defeito real.** A
     * branch do login social foi escrita antes de a feature de registro existir e leu a chave
     * que ela imaginou: `kit.registro.aberto`. A feature nasceu com outro nome —
     * `kit.registro.habilitado` — então este método lia uma chave **inexistente** e devolvia
     * `false` para sempre.
     *
     * O sintoma seria mudo e caro: ligar o registro aberto na aba "Registro" das Configurações
     * do Kit liberaria o cadastro pelo formulário e **não** pelo login social, sem erro nenhum
     * para acusar. Alguém abriria a porta e ela continuaria fechada de um lado — e o lado
     * fechado é justamente o que não tem tela para conferir.
     *
     * Duas fontes para a mesma pergunta é o defeito; a resposta tem uma dona só.
     * `RegistroAberto` é a dona da configuração de registro — ela também governa a aprovação
     * manual e é o que aquela aba edita —, então quem quer saber pergunta a ela.
     *
     * Por que o login social pergunta isto: com o registro fechado, e-mail do provedor que não
     * tem conta no sistema é RECUSADO, não cadastrado. O kit é por convite obrigatório
     * (`App\Filament\Pages\Auth\RegistroPorConvite`), e um callback de OAuth que faz
     * `updateOrCreate` — o exemplo da própria documentação do Socialite — transformaria
     * qualquer pessoa com uma conta no provedor em usuária do sistema. Isso é furo de
     * autorização, não conveniência. E vale para os QUATRO provedores. Ver ADR-06 da wiki
     * `login-social-google`.
     */
    public static function registroAberto(): bool
    {
        return RegistroAberto::habilitado();
    }

    /**
     * A primeira entrada de um provedor numa conta que já existe exige confirmação por e-mail?
     *
     * `false` (padrão): entra e avisa. `true`: recebe o link e só entra depois. Lida por request,
     * no `LoginSocialController::retorno()`, por isso pode ser governada pela tela de Settings.
     * ADR-03 da wiki `vinculo-de-provedor-social`.
     */
    public static function vinculoExigeConfirmacao(): bool
    {
        return (bool) config('kit.login.vinculo_confirmar', false);
    }

    /**
     * O desafio anti-robô das telas públicas está no ar? Se sim, com qual provedor.
     *
     * `null` = desligado, e é o default. Quatro condições em conjunção, pela mesma razão das duas
     * de `disponivel()`: interruptor desligado é escolha; chave vazia é descuido; e aqui o descuido
     * custa mais caro que no login social — um campo obrigatório que nunca se preenche trancaria o
     * login dos três painéis, inclusive o de quem administra. Provedor fora da lista também desliga,
     * em vez de cair no `recaptcha`: chave do Turnstile com widget do Google não renderiza, e o
     * resultado seria o mesmo campo impreenchível. Ver ADR-03 da wiki `recaptcha-nas-telas-publicas`.
     *
     * Lida por request — no `->visible()` do campo, na regra de validação e no
     * `App\Support\GerenciadorAntiRobo` que monta o driver do pacote —, então pode viver na tela
     * de Settings (`.ai/rules/settings.md`).
     *
     * **Em ambiente local a proteção fica desligada, a menos que `local` esteja ligado.** Chave de
     * provedor é presa ao domínio: a de produção não aceita `localhost`, e o widget renderizaria
     * "ERROR for site owner: Invalid domain" na tela de quem está desenvolvendo — com o campo
     * obrigatório e impreenchível. Quem quer ver o desafio no `APP_ENV=local` (com chaves que
     * aceitam localhost) liga `KIT_ANTI_ROBO_LOCAL` ou o toggle da tela. `app()->isLocal()` e não
     * `app()->environment('local')` — é o mesmo predicado, e é o que o requisito nomeou.
     * ADR-07 da wiki `adotar-ddr-filament-captcha`.
     *
     * Sem log nos ramos normais: isto roda em todo render das três telas. Só o provedor
     * desconhecido loga, porque é o único estado que não é escolha nem normalidade.
     */
    public static function antiRobo(): ?ProvedorAntiRobo
    {
        if (! config('kit.login.anti_robo.habilitado')) {
            return null;
        }

        if (app()->isLocal() && ! config('kit.login.anti_robo.local')) {
            return null;
        }

        if (blank(config('kit.login.anti_robo.chave_do_site')) || blank(config('kit.login.anti_robo.chave_secreta'))) {
            return null;
        }

        $valor    = (string) config('kit.login.anti_robo.provedor');
        $provedor = ProvedorAntiRobo::tryFrom($valor);

        if ($provedor === null) {
            Log::channel('autenticacao')->warning(
                "[ConfiguracaoDoLogin@antiRobo] Provedor anti-robô desconhecido — proteção tratada como desligada | provedor: {$valor}",
                [
                    'provedor'   => $valor,
                    'conhecidos' => array_column(ProvedorAntiRobo::cases(), 'value'),
                ],
            );
        }

        return $provedor;
    }

    /** A chave pública do widget — vai para o HTML. Só faz sentido com `antiRobo()` não nulo. */
    public static function chaveDoSiteAntiRobo(): string
    {
        return trim((string) config('kit.login.anti_robo.chave_do_site'));
    }

    /** A chave do servidor — SEGREDO. Só o driver do pacote a lê, e o kit nunca a loga. */
    public static function chaveSecretaAntiRobo(): string
    {
        return trim((string) config('kit.login.anti_robo.chave_secreta'));
    }

    /**
     * O limiar do reCAPTCHA v3: a pontuação (0,0 = robô, 1,0 = pessoa) abaixo da qual o envio é
     * recusado. Só o driver `recaptcha_v3` do pacote a usa (`RecaptchaV3Driver::verify()`,
     * `vendor/ddr/filament-captcha/src/Drivers/RecaptchaV3Driver.php:41-46`).
     *
     * `is_numeric()` e não `(float)`: chave presente e vazia no `.env` viraria `0.0`, que aceita
     * qualquer token — o oposto de falha fechada (`.ai/rules/config.md`). 0,5 é o sugerido pelo Google.
     */
    public static function pontuacaoMinimaAntiRobo(): float
    {
        $valor = config('kit.login.anti_robo.pontuacao_minima');

        return is_numeric($valor) ? (float) $valor : 0.5;
    }
}
