<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use SensitiveParameter;

/**
 * O registro aberto do painel /app: a configuração dele, e o ato de registrar.
 *
 * ## Esta classe é o PONTO ÚNICO DE LIGAÇÃO com o Settings do kit
 *
 * As três primeiras funções — `habilitado()`, `exigirAprovacao()` e
 * `exigirVerificacaoDeEmail()` — são as **únicas** leituras de `config('kit.registro.*')` em
 * todo o projeto, e isso é enforçado por caso de teste, não por disciplina.
 *
 * O requisito pede a opção "como um settings". Quando a página de Settings do /admin existir,
 * trocar a fonte é reescrever o corpo **destes três métodos** e nada mais: a página de registro,
 * a tela de login, o provider do painel e o formulário de organização perguntam aqui. A
 * alternativa — cinco arquivos lendo `config()` direto — transforma essa troca em cinco edições,
 * com a chance de sobrar uma; e sobrar uma significa a feature metade no Settings e metade no
 * `.env`, sem erro nenhum para acusar.
 *
 * Quando ligar no Settings, lembre que a leitura passa a tocar o BANCO em todo request (o
 * `AppPanelProvider` chama `exigirVerificacaoDeEmail()` durante o boot, para decidir se registra
 * as rotas de confirmação de e-mail). Ou seja: a implementação nova precisa tolerar tabela
 * inexistente — durante `migrate`, num clone novo, no CI — em vez de derrubar todo comando
 * artisan. É o mesmo tipo de armadilha que `.ai/rules/providers-filament.md` documenta para
 * plugin resolvido no boot.
 *
 * ## E por que `registrar()` mora aqui, e não na página
 *
 * Porque barreira que só existe na tela não é barreira. `.ai/rules/filament.md` registra o
 * padrão com nome e endereço: enquanto a página for o único chamador funciona, e o primeiro job,
 * comando, action em massa, seeder ou rota de API chama o método direto e passa por cima **sem
 * nada acusar**. As duas guardas de `registrar()` (a opção ligada, e a organização exigida com
 * tenancy) são reafirmadas aqui dentro justamente para valer para esse chamador.
 */
class RegistroAberto
{
    /*
    |--------------------------------------------------------------------------
    | O ponto único de ligação com o Settings
    |--------------------------------------------------------------------------
    | ponytail: três leituras de config e nada mais. Não vire isto numa interface
    | com uma implementação — o que se quer é UM lugar para editar, não uma
    | camada. Ver o docblock da classe.
    */

    /** O registro aberto está liberado nesta instalação? */
    public static function habilitado(): bool
    {
        return (bool) config('kit.registro.habilitado', false);
    }

    /** O cadastro nasce pendente até alguém aprovar? */
    public static function exigirAprovacao(): bool
    {
        return (bool) config('kit.registro.aprovacao_manual', false);
    }

    /** O painel /app exige e-mail validado? */
    public static function exigirVerificacaoDeEmail(): bool
    {
        return (bool) config('kit.registro.verificar_email', false);
    }

    /**
     * O ÚNICO papel que o registro aberto concede.
     *
     * O requisito é literal: *"a pessoa recebe somente o perfil de acesso ao /app e nenhuma
     * outro perfil ou acesso além disso"*. Dos dois papéis do painel `app`, este é o que não
     * administra nada — `admin_app` administra a organização, o que contradiz a cláusula.
     *
     * Vem da config do Shield, e não de uma string literal, porque o nome do papel é
     * configurável e o `PapeisSeeder` lê a mesma chave.
     */
    public static function papel(): string
    {
        return (string) config('filament-shield.panel_user.name', 'panel_user');
    }

    /**
     * A organização de destino do registro, ou `null` quando não há uma utilizável.
     *
     * Três condições, e as três importam: a organização existe, está **ativa** e **habilitou** o
     * registro. Falhar em qualquer uma devolve `null`, e quem chama trata as três do mesmo jeito
     * — o visitante não descobre qual delas falhou, pela mesma razão que os três motivos de
     * convite inválido devolvem a mesma mensagem.
     *
     * Sem tenancy devolve `null` e isso NÃO é falha: não existe organização a resolver. Quem
     * distingue os dois `null` é `config('kit.tenancy.enabled')`, no chamador.
     */
    public static function organizacao(?string $slug): ?Tenant
    {
        if (blank($slug)) {
            return null;
        }

        return Tenant::query()
            ->where('slug', $slug)
            ->where('ativo', true)
            ->where('registro_habilitado', true)
            ->first();
    }

    /**
     * Cria a conta pela porta aberta — com o papel único, a pendência e o vínculo.
     *
     * Roda dentro da transação que `Register::register()` já abriu
     * (`vendor/filament/filament/src/Auth/Pages/Register.php:84-102`), do mesmo jeito que
     * `Convite::aceitar()`: se qualquer passo falhar, não sobra usuário órfão.
     *
     * @param  array<string, mixed>  $dados  o estado do formulário, já validado e com a senha hasheada
     *
     * @throws RuntimeException quando a porta está fechada, ou quando falta a organização
     */
    public static function registrar(#[SensitiveParameter] array $dados, ?Tenant $organizacao = null): User
    {
        self::exigirPortaAberta($organizacao);

        $pendente = self::exigirAprovacao();

        $user = User::create($dados);

        /*
         * O que NÃO é `$fillable`, e por que cada um está aqui.
         *
         * `email_verified_at`: gravado quando a verificação está DESLIGADA, e é isto que
         * condiciona o e-mail de confirmação sem sobrescrever método nenhum do vendor.
         * `Register::sendEmailVerificationNotification()` retorna cedo para quem já tem o
         * endereço validado (`Register.php:167-169`), então a opção desligada vira "nasce
         * validado, vendor não envia" e a ligada vira "nasce sem validar, vendor envia". É o
         * mesmo mecanismo que `Convite::aceitar()` usa em `Convite.php:591`, e é por isso que
         * ligar a verificação não alcança quem vem de convite.
         *
         * `aprovacao_pendente`: estado de fronteira de acesso. Fora do `$fillable` para que
         * nenhum formulário possa escrevê-lo, aqui ou em qualquer lugar futuro.
         */
        $foraDoFillable = ['aprovacao_pendente' => $pendente];

        if (! self::exigirVerificacaoDeEmail()) {
            $foraDoFillable['email_verified_at'] = now();
        }

        $user->forceFill($foraDoFillable)->save();

        /*
         * O vínculo com a organização vem ANTES da aprovação, de propósito.
         *
         * `UserResource::getEloquentQuery()` do /app filtra por `whereHas('tenants')`. Sem o
         * vínculo, o cadastro pendente não aparece na listagem da organização — e ninguém tem
         * como aprovar quem não consegue ver. Pertencer à organização sem papel nenhum não dá
         * acesso a nada: quem decide entrada é `canAccessPanel()`, que exige papel do painel.
         */
        if ($organizacao instanceof Tenant) {
            $user->tenants()->syncWithoutDetaching([$organizacao->getKey()]);
        }

        /*
         * O papel só existe depois da aprovação.
         *
         * Isto é o que torna "pendente não entra em painel nenhum" verdadeiro por CONSTRUÇÃO, e
         * não só por causa da guarda em `canAccessPanel()`: sem papel do painel, nenhum painel
         * abre — a guarda é a segunda barreira, não a única.
         */
        if (! $pendente) {
            self::atribuirPapel($user, $organizacao);
        }

        Log::channel('autenticacao')->info(
            "[RegistroAberto@registrar] Registro aberto concluido | user: {$user->id}",
            [
                'user_id'           => $user->id,
                'email'             => Str::mask($user->email, '*', 3),
                'tenant_id'         => $organizacao?->getKey(),
                'pendente'          => $pendente,
                'papel'             => $pendente ? null : self::papel(),
                'verificacao_email' => self::exigirVerificacaoDeEmail(),
            ],
        );

        return $user;
    }

    /**
     * As duas fronteiras, reafirmadas para o chamador que não passou pela tela.
     *
     * @throws RuntimeException
     */
    private static function exigirPortaAberta(?Tenant $organizacao): void
    {
        $motivo = match (true) {
            ! self::habilitado() => 'desabilitado',
            // Com tenancy, usuário sem organização nenhuma não tem /app para entrar: o Filament
            // procura o tenant de destino e não acha. Registrar alguém num estado inalcançável é
            // pior que recusar.
            (bool) config('kit.tenancy.enabled') && ! $organizacao instanceof Tenant => 'sem_organizacao',
            default                                                                  => null,
        };

        if ($motivo === null) {
            return;
        }

        Log::channel('autenticacao')->warning(
            "[RegistroAberto@registrar] Registro aberto recusado | motivo: {$motivo}",
            [
                'motivo' => $motivo,
                'ip'     => request()->ip(),
            ],
        );

        throw new RuntimeException('Registro aberto indisponível.');
    }

    /**
     * O papel, no CONTEXTO certo.
     *
     * A rota de registro é do PAINEL, não do tenant, então nenhum middleware fixou a organização
     * — e papel gravado em `Tenant::CONTEXTO_GLOBAL` fica **invisível dentro do /app**, porque o
     * `wherePivot` do spatie filtra pelo team do request. A pessoa autentica e não vê nada. Ver
     * ADR-10 da wiki `admin-da-organizacao`.
     *
     * O mecanismo (fixar, restaurar no `finally`, limpar o cache da relação nas duas pontas) é
     * `App\Support\ContextoDePapeis`, compartilhado com o convite, o gerenciador de usuários da
     * organização e o seeder da demo. Sem organização, o contexto é o global — que o spatie
     * ignora quando `permission.teams` está desligado.
     */
    private static function atribuirPapel(User $user, ?Tenant $organizacao): void
    {
        ContextoDePapeis::em(
            $organizacao?->getKey() ?? Tenant::CONTEXTO_GLOBAL,
            $user,
            function () use ($user): void {
                // assignRole(), NUNCA sync() na relação: o sync escreve só as colunas da chave e
                // estoura `NOT NULL constraint failed: model_has_roles.team_id`.
                $user->assignRole(self::papel());
            },
        );
    }
}
