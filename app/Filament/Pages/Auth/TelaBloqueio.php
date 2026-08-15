<?php

namespace App\Filament\Pages\Auth;

use App\Models\Tenant;
use Caresome\FilamentAuthDesigner\Concerns\HasAuthDesignerLayout;
use Caresome\FilamentAuthDesigner\Data\AuthDesignerConfig;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Support\Icons\Heroicon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use lockscreen\FilamentLockscreen\Http\Livewire\LockerScreen;

/**
 * Tela de bloqueio de sessão com o MESMO layout do login (mídia à esquerda, formulário à
 * direita — o split que os três painéis configuram via `AuthDesignerPlugin`).
 *
 * O pacote entrega a tela como `SimplePage` do Filament, que ignora o layout do
 * `caresome/filament-auth-designer`. São necessários os dois: a trait, porque o blade do
 * layout chama `$livewire->getAuthDesignerConfig()`, e a redeclaração de `$layout`, porque a
 * atribuição da trait é em propriedade estática (ver a nota nela). A chave de config é a do
 * login de propósito: a tela é a mesma barreira, com a mesma mídia e o mesmo alternador de
 * tema.
 *
 * Quem coloca esta classe no lugar da do pacote é o bind em `AppServiceProvider::register()`
 * — a rota do pacote resolve `LockerScreen::class` pelo container.
 */
class TelaBloqueio extends LockerScreen
{
    /*
     * Alias, e não `use` simples: a classe declara o próprio `getAuthDesignerConfig()` abaixo, e
     * método da classe tem precedência sobre método de trait — sem o alias, o da trait ficaria
     * inalcançável. `parent::` NÃO serve aqui: o método é da trait, não de `LockerScreen`.
     */
    use HasAuthDesignerLayout {
        getAuthDesignerConfig as configBaseDoAuthDesigner;
    }

    /**
     * **Não remover por parecer redundante com a trait.** `$layout` é estático e a trait faz
     * `static::$layout = ...` no `boot()`; sem esta redeclaração, a subclasse não tem storage
     * próprio e a atribuição cai no estático herdado de `Filament\Pages\Page` — ou seja, a
     * primeira renderização da tela de bloqueio passa a vestir o layout de login em **toda**
     * página Filament do processo (a página de 2FA do Breezy morre em
     * `getAuthDesignerConfig does not exist`). É por isso que a `Login.php` do próprio
     * auth-designer também declara a propriedade.
     */
    protected static string $layout = 'filament-auth-designer::components.layouts.auth';

    protected function getAuthDesignerPageKey(): string
    {
        return 'login';
    }

    /**
     * O config do Auth Designer com a logo da organização no lugar da mídia base.
     *
     * Sobrescreve o método da trait `HasAuthDesignerLayout` — `public` e não-final lá. Funciona
     * porque o blade do layout lê o config no RENDER, na primeira linha
     * (`filament-auth-designer::components.layouts.auth`, `$livewire->getAuthDesignerConfig()`),
     * e não no boot do plugin.
     *
     * ## Por que reconstruir o objeto em vez de escrever no repositório
     *
     * `AuthDesignerConfigRepository::setPageConfig()` **substitui** o `AuthPageConfig` inteiro,
     * sem merge. Usá-lo apagaria `mediaPosition`, `mediaSize` e o alternador de tema que o
     * `AppPanelProvider` configura — a tela perderia o alternador sem nenhum erro. E o repositório
     * é singleton: a escrita valeria para o request todo, não só para esta página.
     *
     * `AuthDesignerConfig` é `final readonly`, então não há `with()`: o caminho é construir um
     * novo copiando o resolvido e trocando só a mídia. Verboso e explícito — que é o ponto, porque
     * é justamente a perda silenciosa de configuração que se quer evitar aqui.
     *
     * ## Por que a guarda checa o painel
     *
     * Esta mesma classe serve os três painéis (o bind está em `AppServiceProvider::register()`).
     * `/admin` e `/infra` não têm organização, e mostrar a logo de um cliente ao administrador da
     * instalação seria vazamento de identidade visual. Falha para a mídia base sempre que houver
     * dúvida — logo do cliente errado é pior que a genérica.
     *
     * Ver ADR-03 e ADR-04 da wiki `identidade-visual-da-organizacao`.
     */
    public function getAuthDesignerConfig(): AuthDesignerConfig
    {
        $config = $this->configBaseDoAuthDesigner();
        $painel = Filament::getCurrentOrDefaultPanel()?->getId();

        $motivo = match (true) {
            $painel !== 'app'                 => 'painel_sem_tenancy',
            blank(session('tenant_corrente')) => 'sem_tenant',
            default                           => null,
        };

        $organizacao = $motivo === null
            ? Tenant::find(session('tenant_corrente'))
            : null;

        $logo = $organizacao?->urlDaLogo();

        if (blank($logo)) {
            Log::channel('tenancy')->debug(
                '[TelaBloqueio@getAuthDesignerConfig] Sem logo de organização, usando a mídia base | motivo: '.($motivo ?? 'sem_logo'),
                [
                    'motivo'    => $motivo ?? 'sem_logo',
                    'painel'    => $painel,
                    'tenant_id' => $organizacao?->getKey(),
                ],
            );

            return $config;
        }

        Log::channel('tenancy')->debug(
            '[TelaBloqueio@getAuthDesignerConfig] Logo da organização aplicada na tela de bloqueio | tenant: '.$organizacao->getKey(),
            [
                'tenant_id'   => $organizacao->getKey(),
                'tenant_slug' => $organizacao->slug,
                'painel'      => $painel,
            ],
        );

        return new AuthDesignerConfig(
            position: $config->position,
            media: $logo,
            mediaSize: $config->mediaSize,
            blur: $config->blur,
            mediaAlt: $organizacao->nome,
            showThemeSwitcher: $config->showThemeSwitcher,
            themePosition: $config->themePosition,
            // Logo é imagem: `isVideo` falso e sem mime de vídeo. O `FileUpload` do form
            // restringe a imagem com `->image()`.
            isVideo: false,
            mediaMimeType: null,
            renderHooks: $config->renderHooks,
        );
    }

    /**
     * O item "Bloquear sessão" do menu do usuário, com posição própria.
     *
     * Substitui o que o pacote registra em `Lockscreen::boot()`
     * (`vendor/marjose123/filament-lockscreen/src/Lockscreen.php`), que **não** define `sort`.
     * Sem sort, `CanBeSorted::getSort()` devolve 0 e a view do menu — que agrupa por
     * `getSort() < 0` — joga o item para DEPOIS do alternador de tema, colado em "Sair". Com
     * `sort(-1)` ele entra no mesmo grupo do "Meu perfil" (`sort(-1)`), e o perfil continua em
     * primeiro porque a view faz `prepend` explícito dele.
     *
     * Quem chama isto tem de ser um `bootUsing()`, nunca o corpo de `panel()`: os plugins
     * bootam ANTES dos callbacks de boot (`Panel::boot()`) e, na normalização por `getName()`,
     * o último `lockSession` registrado é o que vence.
     *
     * `label` e rota são as do pacote — nenhuma string de UI nova nasce aqui. A `url` fica em
     * closure porque a rota só existe com o plugin ligado.
     */
    public static function itemDeMenu(string $panelId): Action
    {
        return Action::make('lockSession')
            ->label(fn (): string => __('filament-lockscreen::default.user_menu_title'))
            ->icon(Heroicon::OutlinedLockClosed)
            ->url(fn (): string => route("lockscreen.{$panelId}.lock-session"))
            ->postToUrl()
            ->sort(-1);
    }

    /**
     * Mesmas duas guardas do pacote — sessão não autenticada vai ao login, sessão não travada
     * volta ao painel —, mas saindo por exception em vez de `redirect()` solto.
     *
     * O `mount()` do pacote chama `redirect()` **sem `return`**
     * (`vendor/marjose123/filament-lockscreen/src/Http/Livewire/LockerScreen.php`). Num
     * processo onde o Livewire já instalou o Redirector dele, esse objeto chega onde o Laravel
     * espera um código HTTP e o request morre em 500 — `ErrorException: Object of class
     * Livewire\...\Redirector could not be converted to int`. A URL `/{painel}/screen/lock`
     * fica nas mãos do usuário (favorito, histórico), então abrir a tela destravada não é
     * hipótese remota.
     */
    public function mount(): void
    {
        $panel = Filament::getCurrentOrDefaultPanel();

        if (! Filament::auth()->check()) {
            $this->sairPara($panel->getLoginUrl());
        }

        if (! session()->has('lockscreen')) {
            $this->sairPara(url($panel->getPath()));
        }
    }

    private function sairPara(string $url): never
    {
        throw new HttpResponseException(new RedirectResponse($url));
    }
}
