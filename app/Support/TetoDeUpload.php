<?php

namespace App\Support;

/**
 * A dona da pergunta "qual o tamanho máximo de um upload".
 *
 * A chave é uma (`kit.uploads.maximo_em_kb`), mas ela é consultada em duas
 * unidades e em três arquivos: o `->maxSize()` do Filament e a regra do upload
 * temporário do Livewire querem KILOBYTES, e o texto que a pessoa lê na tela
 * quer MEGABYTES. Sem esta classe, `intdiv((int) config('kit.uploads.maximo_em_kb'), 1024)`
 * apareceria copiado em cinco lugares — e cópia de conversão de unidade é como
 * um teto acaba divergindo do texto que o anuncia.
 *
 * É o mesmo motivo que `.ai/rules/config.md` registra para o login social: uma
 * pergunta, uma dona. Aqui a dona também converte.
 *
 * O cast é deliberado e não decorativo: `config()` devolve `mixed`, e o
 * `->maxSize()` do Filament tem assinatura `int|Closure|null`.
 */
class TetoDeUpload
{
    /**
     * O teto em kilobytes — a unidade que o Filament e o Livewire recebem.
     *
     * `->maxSize()` monta a regra `max:{$size}` do Laravel
     * (vendor/filament/forms/src/Components/BaseFileUpload.php:413-421), e essa
     * regra divide o tamanho do arquivo por 1024
     * (.../Illuminate/Validation/Concerns/ValidatesAttributes.php:2822). O
     * `max:12288` do `temporary_file_upload` do Livewire é KB pelo mesmo motivo.
     */
    public static function emKb(): int
    {
        return (int) config('kit.uploads.maximo_em_kb');
    }

    /**
     * O teto em megabytes, para o texto que a pessoa lê.
     *
     * `intdiv()` e não divisão comum: a mensagem "O arquivo passa de 9.5 MB" é
     * pior que um número redondo, e a chave nasce de um valor em MB — a divisão
     * só volta a ser exata.
     */
    public static function emMb(): int
    {
        return intdiv(static::emKb(), 1024);
    }
}
