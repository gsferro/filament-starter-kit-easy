<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AdministradorDaInstalacao;
use Illuminate\Database\Seeder;

/**
 * Usuário inicial do kit. TROQUE A SENHA antes de qualquer ambiente exposto.
 *
 * Sem factory/fake de propósito (faker é require-dev e a imagem Docker roda
 * `composer install --no-dev`).
 *
 * **A busca é pelo PAPEL, não pelo e-mail, e isso conserta um defeito.** A versão anterior fazia
 * `User::firstOrCreate(['email' => config('kit.admin.email')], …)`: trocar `KIT_ADMIN_EMAIL` no
 * `.env` e semear de novo criava um **segundo** `master_global`, com o primeiro vivo e a senha
 * antiga. Dois administradores da instalação, sem erro nenhum, e o primeiro esquecido — que é o
 * pior lugar para uma credencial esquecida ficar.
 *
 * O seeder **não sincroniza credencial**, e a decisão é deliberada: ele roda em todo
 * `kit:install` e em todo `db:seed`, então atualizar senha aqui reverteria, em silêncio, a troca
 * que alguém fez pela tela de perfil. Trocar senha é na tela; trocar e-mail também. O trabalho
 * deste seeder é **garantir que exista um administrador**, não mantê-lo igual ao `.env`.
 *
 * Por isso o caminho novo é curto: existe `master_global`? Não faz nada. Não existe? Cria.
 *
 * O `firstOrCreate` foi mantido no ramo de criação de propósito — ele cobre o caso de reparo, em
 * que a conta do e-mail configurado existe mas perdeu o papel (alguém removeu na tela de
 * papéis): ali o certo é devolver o papel à conta que já está lá, não criar outra.
 */
class UsuarioAdminSeeder extends Seeder
{
    public function run(): void
    {
        if (AdministradorDaInstalacao::existe()) {
            return;
        }

        User::firstOrCreate(
            ['email' => config('kit.admin.email')],
            [
                'name'              => config('kit.admin.name'),
                'password'          => config('kit.admin.password'),
                'email_verified_at' => now(),
            ],
        )->assignRole(AdministradorDaInstalacao::papel());
    }
}
