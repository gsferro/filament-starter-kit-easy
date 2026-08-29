# Requisito — Adotar `ddr/filament-captcha` como pacote de captcha

## Fonte

- **Origem**: conversa no chat (avaliação do pacote `ddr/filament-captcha` + decisão do usuário)
- **Data**: 2026-08-31
- **Autor / solicitante**: usuário do projeto
- **Fidelidade**: alta (decisão tomada após avaliação profunda documentada)

## Texto Original

<!-- IMUTÁVEL. Não editar, não corrigir ortografia, não resumir, não reordenar. -->

> "avalia profundamente este pacote: 'https://packagist.org/packages/ddr/filament-captcha'"
>
> "veja se ele se adequea melhor ao projeto, para usarmos, inclusive, o recaptcha v3 ao inves
> do v2, dentre outras possibilidades"
>
> Decisão: "Adotar ddr/filament-captcha com adapters" — "Instalar o pacote, criar adapters
> para Settings/logging/reset e reescrever testes. Mais trabalho agora mas centraliza
> manutenção no pacote."

## Decomposição em Cláusulas

| ID | Cláusula | Trecho literal de origem | Tipo |
|----|----------|--------------------------|------|
| RQ-01 | Instalar o pacote `ddr/filament-captcha` como dependência do projeto | "Adotar ddr/filament-captcha com adapters" | funcional |
| RQ-02 | Substituir `CampoAntiRobo` pelo componente `Captcha::make()` do pacote nas três telas públicas (login, registro, recuperação de senha) | "Instalar o pacote, criar adapters [...] e reescrever testes" | funcional |
| RQ-03 | Manter a integração com Spatie Settings (banco vence `.env`) via adapter/bridge que alimente `config('captcha.*')` em runtime | "criar adapters para Settings" | funcional |
| RQ-04 | Adicionar suporte a reCAPTCHA v3 como driver disponível, incluindo o score/limiar na tela de Settings | "usarmos, inclusive, o recaptcha v3 ao inves do v2" | funcional |
| RQ-05 | Manter os 4 provedores funcionais: hCaptcha, reCAPTCHA v2, reCAPTCHA v3, Turnstile | "dentre outras possibilidades" | funcional |
| RQ-06 | Criar adapter de logging para registrar falhas de verificação no canal `autenticacao` com o padrão `[Classe@Método]` do projeto | "criar adapters para [...] logging" | não-funcional |
| RQ-07 | Criar adapter de reset do token após cada verificação (o pacote não faz isso nativamente) | "criar adapters para [...] reset" | funcional |
| RQ-08 | Manter falha fechada: se o provedor estiver indisponível, o envio é recusado (não aberto) | Consequência de ADR-04 da wiki ancestral | não-funcional |
| RQ-09 | Remover os artefatos da implementação antiga (`CampoAntiRobo.php`, `ProvedorAntiRobo.php`, `campo-anti-robo.blade.php`) após a migração | Implícito: "substituir" e "centraliza manutenção no pacote" | funcional |
| RQ-10 | Reescrever os testes existentes (56 Kit + 5 browser) para cobrir a nova implementação com o pacote | "reescrever testes" | funcional |
| RQ-11 | Manter as variáveis de ambiente existentes (`KIT_ANTI_ROBO*`) compatíveis ou migrar para o formato do pacote (`CAPTCHA_DRIVER`, `RECAPTCHA_V2_SITEKEY`, etc.) | Implícito: retrocompatibilidade de deploy | não-funcional |
| RQ-12 | Adicionar campo `login_anti_robo_score` ao Settings para o limiar do reCAPTCHA v3 (default 0.5) | "usarmos [...] o recaptcha v3" + score é configurável no pacote | funcional |
| RQ-13 | Publicar e customizar as views do pacote para manter detecção de dark mode e `data-anti-robo` | Consequência de manter UX atual | funcional |

## Ambiguidades e Perguntas Abertas

- **RQ-04**: reCAPTCHA v3 deve ser o **default** ou apenas uma **opção adicional** ao lado de v2?
  - **Assumido**: opção adicional (o enum na tela de Settings oferece 4 provedores; o default continua `recaptcha_v2` para manter compatibilidade)
  - **Se negado**: default muda para `recaptcha_v3` e a tela de Settings precisa de UX diferente (sem checkbox visível, score configurável)
- **RQ-11**: as env vars mudam de nome (`KIT_ANTI_ROBO_CHAVE_DO_SITE` → `RECAPTCHA_V2_SITEKEY`, etc.) ou mantemos as nossas?
  - **Assumido**: manter as nossas e criar bridge no `aplicarNaConfig()` que mapeia para `captcha.*`
  - **Se negado**: migration de `.env` necessária + atualizar `.env.example` + documentar breaking change

## Fora de Escopo (declarado)

- Captcha de imagem (GD/Imagick) — fora do protocolo e do pacote
- Honeypot / rate limiting — complementar, não substituição
- Customizações avançadas do widget (tamanho, idioma explícito) — podem ser adicionadas depois
