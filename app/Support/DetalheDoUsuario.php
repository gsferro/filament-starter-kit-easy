<?php

namespace App\Support;

/**
 * O que vai abaixo do nome no bloco do usuário do cabeçalho dos painéis.
 *
 * Enum de lista fechada, como `DensidadeDoLayout`: a chave `kit.cabecalho.detalhe_do_usuario`
 * chega de três fontes (`.env`, banco via Settings, formulário) e nenhuma delas garante o
 * vocabulário. `coagir()` é a única regra de normalização ("fora da lista vira `perfil`"), e
 * quem a chama é cada fronteira por onde o valor entra ou sai: `config/kit.php` (o `.env`), a
 * migration de settings (a semente), o `mutateFormDataBeforeFill()` da tela (o banco editado à
 * mão não pode travar o formulário) e `CabecalhoDoPainel::usuario()` (o consumidor). A regra é
 * uma; os pontos de chamada são os quatro lugares por onde um valor cru consegue chegar.
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
