<?php

namespace App\Support;

/**
 * Quem o Laravel pode acreditar quando lê `X-Forwarded-*` — a chave `TRUSTED_PROXIES` do `.env`.
 *
 * Atrás de um proxy que termina o TLS (o Traefik do deploy multiambiente, um load balancer), a
 * aplicação recebe HTTP na porta interna e só sabe que o navegador está em `https://` pelos
 * cabeçalhos que o proxy acrescenta. O `TrustProxies` do framework nasce **sem** ninguém confiável
 * e só passa a honrar os cabeçalhos quando `bootstrap/app.php` chama `trustProxies(at: …)` — que é
 * no-op com `null`. Esta classe é o que transforma a string do `.env` no argumento dessa chamada.
 *
 * A direção do erro é a regra (`.ai/rules/config.md`): chave **ausente, vazia ou só espaços** não
 * confia em ninguém, que é o comportamento que o kit sempre teve. `*` confia em qualquer origem —
 * seguro só quando a porta do container não é alcançável de fora da rede do proxy. O resto é lista
 * por vírgula de IPs ou CIDRs, sem validação aqui: o Symfony valida ao resolver o IP e lança
 * exceção legível no primeiro request, e um parser próprio só duplicaria o dele.
 *
 * Lida no bootstrap, antes do `config/` — por isso é `env()` ali e não `config('kit.…')`.
 * Ver ADR-02 de `wikis/specs/feat/deploy-multiambiente-docker/`.
 */
final class ProxiesConfiaveis
{
    /**
     * @return '*'|list<string>|null `null` = nenhum proxy confiável; `'*'` = todos; senão a lista limpa
     */
    public static function doEnv(mixed $bruto): array|string|null
    {
        if (! is_string($bruto)) {
            return null;
        }

        $texto = trim($bruto);

        if ($texto === '') {
            return null;
        }

        if ($texto === '*') {
            return '*';
        }

        $lista = array_values(array_filter(
            array_map(trim(...), explode(',', $texto)),
            static fn (string $item): bool => $item !== '',
        ));

        return $lista === [] ? null : $lista;
    }
}
