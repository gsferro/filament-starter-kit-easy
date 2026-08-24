<?php

namespace App\Support;

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
     */
    public static function disponivel(ProvedorSocial $provedor): bool
    {
        if (! config("kit.login.{$provedor->value}.habilitado")) {
            return false;
        }

        /** @var array<string, mixed> $credenciais */
        $credenciais = config('services.'.$provedor->value, []);

        return filled($credenciais['client_id'] ?? null)
            && filled($credenciais['client_secret'] ?? null)
            && filled($credenciais['redirect'] ?? null);
    }

    /**
     * Os provedores que estão no ar agora, na ordem do enum.
     *
     * É o que o blade dos botões percorre. Lista vazia é o estado normal do kit recém
     * instalado, e o blade não renderiza nem o divisor "ou" nesse caso.
     *
     * @return array<int, ProvedorSocial>
     */
    public static function disponiveis(): array
    {
        return array_values(array_filter(
            ProvedorSocial::cases(),
            static fn (ProvedorSocial $provedor): bool => self::disponivel($provedor),
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
}
