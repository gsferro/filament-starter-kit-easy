<?php

namespace App\Ai\Guardrails;

/**
 * Redator de PII: substitui dados pessoais por marcadores `[REDIGIDO:{tipo}]` antes de o
 * prompt chegar ao provider. Regex fixa, determinística e auditável — heurística assumida:
 * falsos positivos/negativos são aceitos, e o upgrade path (biblioteca dedicada de NER) fica
 * atrás desta mesma interface (`redigir`/`tiposDetectados`).
 *
 * Ordem dos padrões importa: os mais específicos/longos (e-mail, token, CNPJ, cartão) vêm
 * antes dos numéricos curtos (CPF, telefone, IP) para não fatiar o match.
 */
final class PiiRedactor
{
    /** @var array<string, string> tipo => regex */
    private const PADROES = [
        'email'    => '/[a-z0-9._%+\-]+@[a-z0-9.\-]+\.[a-z]{2,}/i',
        'token'    => '/\b(?:sk|pk|ghp|xox[baprs])[-_][A-Za-z0-9]{10,}\b|Bearer\s+[A-Za-z0-9._\-]{10,}/i',
        'cnpj'     => '/\b\d{2}\.?\d{3}\.?\d{3}\/?\d{4}-?\d{2}\b/',
        'cartao'   => '/\b\d{4}[ -]?\d{4}[ -]?\d{4}[ -]?\d{1,4}\b/',
        'cpf'      => '/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/',
        'telefone' => '/\(?\d{2}\)?[ -]?9?\d{4}[ -]?\d{4}\b/',
        'ip'       => '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
    ];

    public function redigir(string $texto): string
    {
        foreach (self::PADROES as $tipo => $padrao) {
            $texto = preg_replace($padrao, "[REDIGIDO:{$tipo}]", $texto) ?? $texto;
        }

        return $texto;
    }

    /**
     * Tipos de PII presentes no texto (para o log; vazio = nada a redigir).
     *
     * @return list<string>
     */
    public function tiposDetectados(string $texto): array
    {
        $tipos = [];

        foreach (self::PADROES as $tipo => $padrao) {
            if (preg_match($padrao, $texto) === 1) {
                $tipos[] = $tipo;
            }
        }

        return $tipos;
    }
}
