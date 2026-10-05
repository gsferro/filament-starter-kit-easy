<?php

namespace App\Support;

/**
 * O que vai abaixo do nome no bloco do usuário do cabeçalho dos painéis.
 *
 * Enum de lista fechada, como `DensidadeDoLayout`: a chave `kit.cabecalho.detalhe_do_usuario`
 * chega de três fontes (`.env`, banco via Settings, formulário) e nenhuma delas garante o
 * vocabulário. `coagir()` é o único ponto de normalização, e quem o chama é o consumidor
 * (`CabecalhoDoPainel::usuario()`) — não a página nem o `aplicarNaConfig()`, para a decisão de
 * "fora da lista vira `perfil`" existir numa linha só.
 *
 * Só duas opções, porque o requisito cita duas ("email ou perfil"); quem não quer detalhe
 * nenhum desliga o bloco do usuário (D7 do `01-plano-acao.md` da feature).
 */
enum DetalheDoUsuario: string
{
    case Perfil = 'perfil';
    case Email  = 'email';

    /**
     * `tryFrom()` e não `from()`: valor desconhecido (`telefone`, `role`, vazio, inteiro) cai no
     * default em vez de estourar na renderização do layout de toda tela.
     */
    public static function coagir(mixed $bruto): self
    {
        return (is_string($bruto) ? self::tryFrom($bruto) : null) ?? self::Perfil;
    }

    /**
     * Opções do `Select` da tela de configurações — o único consumidor do rótulo.
     *
     * @return array<string, string>
     */
    public static function opcoes(): array
    {
        return [
            self::Perfil->value => 'Perfil',
            self::Email->value  => 'E-mail',
        ];
    }
}
