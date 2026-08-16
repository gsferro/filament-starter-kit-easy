<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Como um papel é EXIBIDO.
 *
 * O nome do papel é chave: `master_global`, `admin_organizacao`, `panel_user`. Ele é
 * assim de propósito — é o que vai em `assignRole()`, em `hasRole()`, nos seeders e nas
 * policies, e mudá-lo quebraria tudo isso de uma vez. O que não serve é mostrar a chave
 * para quem opera: "admin_organizacao" é identificador, não rótulo.
 *
 * Então a chave fica, e a exibição vira Title Case. Aqui, e não repetido em cada tabela,
 * porque a regra aparece em sete telas — a tabela de papéis, o cadastro de usuários, os
 * dois de convites, o vínculo por organização e as caixas de entrada. Sete cópias
 * divergem, e a que divergir vai mostrar `panel_user` numa tela e "Panel User" na de
 * baixo.
 */
final class Papeis
{
    /** `master_global` → "Master Global". */
    public static function rotulo(?string $nome): string
    {
        return blank($nome) ? '—' : Str::headline($nome);
    }

    /**
     * O painel que o papel abre, dito por extenso.
     *
     * `painel` é a coluna que DÁ o acesso (`User::canAccessPanel()` a lê), e mostrá-la
     * como "app" fazia parecer categoria, não permissão. Vazio não é coringa: papel sem
     * painel não abre painel nenhum, e o rótulo precisa dizer isso.
     */
    public static function rotuloDoPainel(?string $painel): string
    {
        return blank($painel) ? 'Não abre painel' : 'Acesso ao painel /'.$painel;
    }
}
