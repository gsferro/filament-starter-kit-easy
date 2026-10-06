<?php

namespace App\Support;

/**
 * Quem o Laravel pode acreditar quando lê `X-Forwarded-*` — a chave `TRUSTED_PROXIES` do `.env`.
 *
 * Atrás de um proxy que termina o TLS (o Traefik do deploy multiambiente, um load balancer), a
 * aplicação recebe HTTP na porta interna e só sabe que o navegador está em `https://` pelos
 * cabeçalhos que o proxy acrescenta. O `TrustProxies` do framework nasce **sem** ninguém confiável
 * e só passa a honrar os cabeçalhos quando alguém chama `TrustProxies::at(…)`. Esta classe é o que
 * transforma a string do `.env` no argumento dessa chamada, feita em `KitServiceProvider::boot()` a
 * partir de `config('kit.proxies_confiaveis')` — **depois** de o `.env` ter sido carregado. Não é
 * no `bootstrap/app.php`: o closure de `withMiddleware()` roda antes do `LoadEnvironmentVariables`,
 * e `env()` ali só enxerga o ambiente do processo, nunca o arquivo `.env` (medido pelo revisor do
 * diff, RD-01 da wiki). Com a configuração em cache, vale o valor que estava no `.env` na hora do
 * `config:cache`, como qualquer outra chave.
 *
 * A direção do erro é a regra (`.ai/rules/config.md`): chave **ausente, vazia ou só espaços** não
 * confia em ninguém, que é o comportamento que o kit sempre teve. `*` confia em qualquer origem —
 * seguro só quando a porta do container não é alcançável de fora da rede do proxy. O resto é lista
 * por vírgula de IPs ou CIDRs, e **só** isso:
 *
 * - os coringas que o framework expande — `*` e `**` (todos), `REMOTE_ADDR` (o chamador) e
 *   `PRIVATE_SUBNETS`/`private_ranges` (toda faixa privada — no Docker, todo container;
 *   `vendor/symfony/http-foundation/Request.php:setTrustedProxies():647-665`) — ficam de fora de
 *   uma lista: um erro de digitação não pode abrir confiança larga. `*` só vale **sozinho**;
 * - item que não é IP nem CIDR é descartado também: o Symfony **não** valida — `172.18.0.0/16x`
 *   derruba todo request com `TypeError` em `substr_compare()`, e `17x.18.0.1` fica em silêncio
 *   (`vendor/symfony/http-foundation/IpUtils.php:checkIp4():98-117`). Descartar aqui, e logar o
 *   descartado no provider, é o que torna o erro visível sem tirar a aplicação do ar.
 *
 * Ver ADR-02 de `wikis/specs/feat/deploy-multiambiente-docker/`.
 */
final class ProxiesConfiaveis
{
    /** Tokens que o framework trata como "confiar no chamador" ou "toda faixa privada": nunca entram numa lista. */
    private const CORINGAS = ['*', '**', 'REMOTE_ADDR', 'PRIVATE_SUBNETS', 'private_ranges'];

    /**
     * @return '*'|list<string>|null `null` = nenhum proxy confiável; `'*'` = todos; senão a lista limpa
     */
    public static function doEnv(mixed $bruto): array|string|null
    {
        $texto = self::texto($bruto);

        if ($texto === null) {
            return null;
        }

        if ($texto === '*') {
            return '*';
        }

        $lista = self::separar($texto)['aceitos'];

        return $lista === [] ? null : $lista;
    }

    /**
     * O que foi escrito e NÃO entrou: coringas dentro de lista e itens que não são IP nem CIDR.
     * Vazio quando tudo foi aceito (ou quando não havia nada).
     *
     * @return list<string>
     */
    public static function descartados(mixed $bruto): array
    {
        // `TRUSTED_PROXIES=true` chega como bool pelo `env()`: não é lista, e quem escreveu merece o aviso.
        if ($bruto !== null && ! is_string($bruto)) {
            return [(string) json_encode($bruto)];
        }

        $texto = self::texto($bruto);

        if ($texto === null || $texto === '*') {
            return [];
        }

        return self::separar($texto)['descartados'];
    }

    private static function texto(mixed $bruto): ?string
    {
        if (! is_string($bruto)) {
            return null;
        }

        $texto = trim($bruto);

        return $texto === '' ? null : $texto;
    }

    /**
     * @return array{aceitos: list<string>, descartados: list<string>}
     */
    private static function separar(string $texto): array
    {
        $aceitos     = [];
        $descartados = [];

        foreach (array_map(trim(...), explode(',', $texto)) as $item) {
            if ($item === '') {
                continue;
            }

            if (in_array($item, self::CORINGAS, true) || ! self::ehIpOuCidr($item)) {
                $descartados[] = $item;

                continue;
            }

            $aceitos[] = $item;
        }

        return ['aceitos' => $aceitos, 'descartados' => $descartados];
    }

    /** IPv4/IPv6, com ou sem `/prefixo` dentro do tamanho do endereço. */
    private static function ehIpOuCidr(string $item): bool
    {
        $partes   = explode('/', $item, 2);
        $endereco = $partes[0];
        $prefixo  = $partes[1] ?? null;

        if (filter_var($endereco, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        if ($prefixo === null) {
            return true;
        }

        $maximo = str_contains($endereco, ':') ? 128 : 32;

        return ctype_digit($prefixo) && (int) $prefixo <= $maximo;
    }
}
