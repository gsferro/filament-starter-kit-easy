<?php

namespace App\Ai\Agents;

use App\Ai\Exceptions\AgenteInativoException;
use App\Ai\Middleware\AiAuditMiddleware;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Stringable;

/**
 * Assistente conversacional embarcado no painel (widget `AssistenteChatWidget`).
 *
 * Instruções, provider/modelo e guardrails vêm do catálogo `agentes_ia` (slug
 * `assistente`), nunca hardcoded aqui — trocar o comportamento do assistente é editar
 * um registro no painel admin, não fazer deploy.
 *
 * Perfil ASSISTENTE: zero escrita. As tools que você registrar devem recuperar SOMENTE
 * o que o usuário logado pode ver — o filtro de permissão mora na query da tool, não na
 * instrução do modelo. O `User` é explícito no construtor porque o agente roda fora do
 * ciclo de request do painel (jobs, streaming) e não pode depender de `auth()`.
 */
final class Assistente extends AgenteBase implements Conversational, HasTools
{
    use RemembersConversations;

    public function __construct(public User $user) {}

    public function slug(): string
    {
        return 'assistente';
    }

    /**
     * Instruções do catálogo. Agente inativo → indisponibilidade honesta, nunca resposta
     * inventada. Agente ausente do catálogo → RuntimeException (classe base).
     */
    public function instructions(): Stringable|string
    {
        if (! $this->estaAtivo()) {
            Log::channel('ai')->warning(
                '[Assistente@instructions] Agente desativado no catálogo | slug: '.$this->slug(),
                ['ativo' => false, 'versao' => $this->agente()->versao],
            );

            throw new AgenteInativoException("Agente [{$this->slug()}] está indisponível no momento.");
        }

        return parent::instructions();
    }

    /**
     * Guardrails do catálogo (fail-closed se vazios — classe base) + auditoria por ÚLTIMO,
     * para que o prompt logado já esteja mascarado pelo redator de PII.
     *
     * @return list<object>
     */
    public function middleware(): array
    {
        return [...parent::middleware(), new AiAuditMiddleware];
    }

    /**
     * Tools do agente: o mapa abaixo é o que EXISTE no código; a coluna `agentes_ia.tools`
     * é a allowlist do que está LIBERADO. Só o que está nos dois roda — liberar uma tool
     * vira edição de registro no painel, não deploy.
     *
     * O kit nasce sem tool nenhuma de propósito (não há domínio a consultar ainda). Para
     * registrar a sua:
     *
     *   1. crie a classe da tool (ver `Laravel\Ai\Contracts\Tool` / atributos do SDK),
     *      recebendo o `User` e aplicando as permissões dele NA QUERY;
     *   2. acrescente a fábrica aqui:
     *        'minha_tool' => fn (): MinhaTool => new MinhaTool($this->user),
     *   3. adicione a chave `minha_tool` em `agentes_ia.tools` (painel admin ou seeder).
     *
     * @return list<object>
     */
    public function tools(): iterable
    {
        /** @var array<string, callable(): object> $fabricas */
        $fabricas = [
            // 'minha_tool' => fn (): MinhaTool => new MinhaTool($this->user),
        ];

        // `array_values()` por cima do `->values()`: o `all()` da Collection devolve
        // `array<int, T>` para o analisador, e o contrato do agente é `list<object>` — o SDK
        // itera por posição. Sem isso a promessa do PHPDoc não é verificável.
        return array_values(
            collect($this->agente()->tools ?? [])
                ->filter(fn (string $nome): bool => isset($fabricas[$nome]))
                ->map(fn (string $nome): object => $fabricas[$nome]())
                ->all()
        );
    }
}
