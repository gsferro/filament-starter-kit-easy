<?php

return [

    'labels' => [
        'message_no' => 'Mensagem nº',
        'request_id' => 'ID da requisição',
        'retry' => 'Tentar novamente',
        'reload' => 'Recarregar página',
        'check_status' => 'Verificar status',
        'go_home' => 'Voltar com segurança',
        'go_back' => 'Voltar',
        'go_dashboard' => 'Ir para o painel',
        'contact_support' => 'Falar com o suporte',
        'copy_detail' => 'Copiar detalhes',
        'copied' => 'Copiado',
        'view_more' => 'Ver mais',
        'view_less' => 'Ver menos',
        'technical_detail' => 'Detalhe técnico',
        'stack_trace' => 'Rastreamento',
        'diagnosis' => 'Diagnóstico',
        'system_response' => 'Resposta do sistema',
        'procedure' => 'O que fazer',
        'note' => 'Nota',
        'security' => 'Segurança',
        'status' => 'Status',
        'guard' => 'Guard',
        'account' => 'Conta',
        'roles' => 'Seus papéis',
        'missing_permissions' => 'Permissão faltante|Permissões faltantes',
        'missing_roles' => 'Papel faltante|Papéis faltantes',
        'permission_kind' => 'Protege',
        'reason' => 'Motivo',
        'support_prompt' => 'Precisa de ajuda? Fale com o',
        'support_word' => 'suporte',
    ],

    'kinds' => [
        'resource' => 'Recurso',
        'page' => 'Página',
        'widget' => 'Widget',
        'custom' => 'Permissão personalizada',
    ],

    '500' => [
        'title' => 'Algo deu errado',
        'body' => 'Um erro inesperado interrompeu sua solicitação.',
        'diagnosis' => 'O aplicativo encontrou um erro inesperado ao processar sua solicitação.',
        'response' => 'Nada foi salvo — a operação foi revertida com segurança.',
        'procedure' => 'Tente novamente em instantes. Se persistir, copie o número da mensagem abaixo e envie ao suporte.',
    ],

    '403' => [
        'title' => 'Acesso negado',
        'body' => 'Você não tem permissão para acessar este recurso. O acesso foi negado por uma política de segurança ou falta de privilégios.',
        'note' => 'Precisa de acesso a este recurso? Solicite as permissões adequadas ao seu administrador.',
    ],

    '404' => [
        'title' => 'Página não encontrada',
        'body' => 'A página que você procura foi movida, excluída ou o endereço está incorreto. Verifique o endereço ou volte com segurança.',
    ],

    '419' => [
        'title' => 'Sua sessão expirou',
        'body' => 'A sessão terminou por motivos de segurança após um período de inatividade. Entre novamente para continuar de onde parou.',
        'relogin' => 'Entrar novamente',
        'note' => 'As sessões expiram automaticamente para proteger sua conta contra acessos não autorizados.',
    ],

    '503' => [
        'title' => 'Em manutenção',
        'body' => 'O sistema está temporariamente indisponível por manutenção ou sobrecarga. Estamos trabalhando para restaurar o serviço o mais rápido possível.',
        'eta' => 'Previsão de retorno em :seconds s',
        'note' => 'Manutenção programada em andamento. Seus dados estão seguros e o serviço voltará em breve.',
    ],

];
