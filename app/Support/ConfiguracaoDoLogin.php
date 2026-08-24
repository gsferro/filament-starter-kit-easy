<?php

namespace App\Support;

/**
 * O ÚNICO ponto do código que lê configuração da tela de login.
 *
 * Este é o contrato, e ele é o motivo de a classe existir: a tela de Settings do kit —
 * que é quem vai editar estas três coisas — está sendo criada em outra branch. Enquanto
 * ela não existe, os três métodos abaixo leem de `config()`. No dia em que ela existir,
 * **só o corpo destes três métodos muda**: nem o controller, nem as rotas, nem os dois
 * blades, nem um único caso de teste é tocado.
 *
 * Por isso nada mais no kit consulta `config('kit.login...')` nem `config('services.google')`
 * direto. Espalhar essas leituras pelo controller, pelas rotas e pelos blades transformaria
 * a migração numa caça a chamadas de `config()` e deixaria a auditoria sem um lugar para
 * olhar. Aqui há um lugar.
 *
 * O que ela deliberadamente NÃO é: uma interface com uma implementação, nem uma fábrica de
 * provedores. `Socialite::driver($nome)` já é a abstração de provedor, e um provedor só não
 * justifica outra. Ver ADR-02 e ADR-10 em
 * `wikis/specs/feat/login-social-google/login-social-google/`.
 */
final class ConfiguracaoDoLogin
{
    /**
     * O provedor social desta entrega.
     *
     * Uma constante e não um enum, porque enum de um caso é abstração sem segundo caso.
     * Quando o GitHub (ou outro) entrar, a decisão de extrair se toma com DOIS casos na
     * mão — feita com um, ela adivinha a forma. Ver ADR-10.
     */
    public const PROVEDOR_GOOGLE = 'google';

    /**
     * O botão e as rotas do Google entram no ar?
     *
     * Duas condições, em conjunção, porque o requisito pede as duas e porque elas falham
     * por motivos diferentes: o interruptor desligado é escolha de quem instalou, a
     * credencial vazia é descuido de quem configurou.
     *
     * `filled()` e não `isset()`: chave presente com valor vazio é o caso REAL, e é o que
     * sobra quando alguém apaga o valor do `.env` e deixa o `=`. Com `isset()` o botão
     * apareceria apontando para um OAuth que não existe.
     *
     * As três chaves são conferidas, `client_secret` incluído. Conferir duas e esquecer
     * uma é o mutante mais provável aqui, e há caso de teste com o secret vazio só para
     * ele.
     */
    public static function googleDisponivel(): bool
    {
        if (! config('kit.login.google.habilitado')) {
            return false;
        }

        /** @var array<string, mixed> $credenciais */
        $credenciais = config('services.'.self::PROVEDOR_GOOGLE, []);

        return filled($credenciais['client_id'] ?? null)
            && filled($credenciais['client_secret'] ?? null)
            && filled($credenciais['redirect'] ?? null);
    }

    /**
     * Texto do rodapé da tela de login, ou null quando não há rodapé.
     *
     * `filled()` trata string de espaços como vazia, então rodapé com um espaço não
     * renderiza uma faixa vazia na tela.
     *
     * É TEXTO. Quem renderiza escapa, porque o valor vem de campo editável e a tela de
     * login é pública e não autenticada — HTML cru ali é XSS armazenado com o pior alcance
     * possível. Ver ADR-09.
     */
    public static function rodapeDoLogin(): ?string
    {
        $rodape = config('kit.login.rodape');

        return filled($rodape) ? trim((string) $rodape) : null;
    }

    /**
     * O registro aberto está ligado? Default false.
     *
     * **A chave que este método lê ainda não existe.** Quem a cria é a feature de registro
     * e aprovação, em outra branch. Ausente, `config()` devolve o default e a resposta é
     * `false` — que é exatamente o default que o requisito pede ("o default é false para
     * register e do socialite").
     *
     * Por que o login social pergunta isto: com o registro fechado, e-mail do Google que
     * não tem conta no sistema é RECUSADO, não cadastrado. O kit é por convite obrigatório
     * (`App\Filament\Pages\Auth\RegistroPorConvite`), e um callback de OAuth que faz
     * `updateOrCreate` — o exemplo da própria documentação do Socialite — transformaria
     * qualquer pessoa com uma conta Google em usuária do sistema. Isso é furo de
     * autorização, não conveniência. Ver ADR-06.
     */
    public static function registroAberto(): bool
    {
        return (bool) config('kit.registro.aberto', false);
    }
}
