<?php

declare(strict_types=1);

namespace Database\Seeders\Onboarding;

/**
 * Base dos filhos que ensinam telas que só existem com multi-tenant (RQ-24 da wiki
 * `onboarding-guiado`). O pai já só os chama com `kit.tenancy.enabled`; esta guarda cobre o filho
 * rodado SOZINHO (`db:seed --class=Database\\Seeders\\Onboarding\\OrganizacoesSeeder`), que sem
 * ela criaria passo para tela que a instalação não abre (P-17).
 */
abstract class SeederDeOnboardingDeTenancy extends SeederDeOnboarding
{
    /** @param  array<string, mixed>  $parameters */
    public function __invoke(array $parameters = []): mixed
    {
        if (! config('kit.tenancy.enabled')) {
            $this->command->warn(class_basename(static::class).': multi-tenant desligado — nada a semear.');

            return null;
        }

        return parent::__invoke($parameters);
    }
}
