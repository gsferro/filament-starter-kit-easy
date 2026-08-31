<?php

declare(strict_types=1);

namespace App\Support;

use Ddr\FilamentCaptcha\CaptchaManager;
use Ddr\FilamentCaptcha\Drivers\CaptchaDriver;

/**
 * O `CaptchaManager` do `ddr/filament-captcha` lendo a configuração DO KIT, por request.
 *
 * ## O que ele substitui
 *
 * O pacote lê `config('captcha.driver')` e `config("captcha.{driver}.sitekey|secret")`
 * (`vendor/ddr/filament-captcha/src/CaptchaManager.php:27-44`), alimentados por env vars próprias
 * (`CAPTCHA_DRIVER`, `RECAPTCHA_V2_SITEKEY`, ...). O kit já tem um lugar para isso — a seção
 * anti-robô das `ConfiguracoesDoKit`, que grava no banco e chega a `kit.login.anti_robo.*` pelo
 * `mapaDeConfiguracao()` — e uma dona para a pergunta "está no ar, com qual provedor?":
 * `ConfiguracaoDoLogin::antiRobo()`. Duas fontes para a mesma resposta é o defeito que
 * `.ai/rules/config.md` registra ("uma pergunta, uma dona"), então as env vars do pacote são
 * ignoradas de propósito: este manager projeta o que o kit decidiu para o formato do pacote no
 * momento de criar o driver.
 *
 * ## Por que aqui e não no boot
 *
 * A wiki desenhou um "bridge" chamado depois de `aplicarNaConfig()`. Projetar no boot tem dois
 * problemas: precisa acontecer NA ORDEM CERTA (ADR-02), e não acompanha `config()->set()` feito
 * depois — que é como os testes ligam a proteção. Projetar dentro de `createDriver()` resolve os
 * dois: a leitura é por request, como toda chave da tela de Settings (`.ai/rules/settings.md`).
 * Sem o cache de `$drivers` do pai pelo mesmo motivo — criar um driver é um `new` sem I/O.
 *
 * ## Falha fechada e log
 *
 * Todo driver sai embrulhado em `VerificacaoAntiRobo`, que captura a exceção de rede e registra
 * no canal `autenticacao`. Ver o docblock dela. ADR-03 da wiki `adotar-ddr-filament-captcha`.
 *
 * Com a proteção desligada, as chaves projetadas são `null`: o próprio componente do pacote se
 * esconde (`Captcha::setUp()`, `->visible(fn () => $this->getSiteKey() !== null)`) e a regra dele
 * não verifica nada (`Rules\Captcha::validate()`, `if ($driver->getSiteKey() === null) return;`).
 */
final class GerenciadorAntiRobo extends CaptchaManager
{
    public function driver(?string $name = null): CaptchaDriver
    {
        return $this->createDriver($name ?? $this->getDefaultDriver());
    }

    /** O provedor do kit — ou qualquer um válido quando desligado, porque as chaves irão nulas. */
    protected function getDefaultDriver(): string
    {
        return (ConfiguracaoDoLogin::antiRobo() ?? ProvedorAntiRobo::RecaptchaV2)->value;
    }

    protected function createDriver(string $name): CaptchaDriver
    {
        $ligado = ConfiguracaoDoLogin::antiRobo()?->value === $name;

        // ponytail: escreve na config para reaproveitar o `match` do pai (e a `verify_url` que o
        // pacote guarda lá) em vez de duplicar a fábrica dos quatro drivers aqui.
        config(["captcha.{$name}" => [
            ...(array) config("captcha.{$name}", []),
            'sitekey' => $ligado ? ConfiguracaoDoLogin::chaveDoSiteAntiRobo() : null,
            'secret'  => $ligado ? ConfiguracaoDoLogin::chaveSecretaAntiRobo() : null,
            'score'   => ConfiguracaoDoLogin::pontuacaoMinimaAntiRobo(),
        ]]);

        return new VerificacaoAntiRobo(parent::createDriver($name), $name);
    }
}
