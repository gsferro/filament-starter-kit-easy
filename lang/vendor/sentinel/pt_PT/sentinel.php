<?php

return [

    'labels' => [
        'message_no' => 'Mensagem nº',
        'request_id' => 'ID do pedido',
        'retry' => 'Tentar novamente',
        'reload' => 'Recarregar página',
        'check_status' => 'Verificar estado',
        'go_home' => 'Voltar em segurança',
        'go_back' => 'Voltar',
        'go_dashboard' => 'Ir para o painel',
        'contact_support' => 'Contactar o suporte',
        'copy_detail' => 'Copiar detalhes',
        'copied' => 'Copiado',
        'view_more' => 'Ver mais',
        'view_less' => 'Ver menos',
        'technical_detail' => 'Detalhe técnico',
        'stack_trace' => 'Rastreio',
        'diagnosis' => 'Diagnóstico',
        'system_response' => 'Resposta do sistema',
        'procedure' => 'O que fazer',
        'note' => 'Nota',
        'security' => 'Segurança',
        'status' => 'Estado',
        'guard' => 'Guarda',
        'account' => 'Conta',
        'roles' => 'Os seus papéis',
        'missing_permissions' => 'Permissão em falta|Permissões em falta',
        'missing_roles' => 'Papel em falta|Papéis em falta',
        'permission_kind' => 'Protege',
        'reason' => 'Motivo',
        'support_prompt' => 'Precisa de ajuda? Contacte o',
        'support_word' => 'suporte',
    ],

    'kinds' => [
        'resource' => 'Recurso',
        'page' => 'Página',
        'widget' => 'Widget',
        'custom' => 'Permissão personalizada',
    ],

    '500' => [
        'title' => 'Algo correu mal',
        'body' => 'Um erro inesperado interrompeu o seu pedido.',
        'diagnosis' => 'A aplicação encontrou um erro inesperado ao processar o seu pedido.',
        'response' => 'Nada foi gravado — a operação foi revertida em segurança.',
        'procedure' => 'Tente novamente dentro de momentos. Se persistir, copie o número da mensagem abaixo e envie-o ao suporte.',
    ],

    '403' => [
        'title' => 'Acesso negado',
        'body' => 'Não tem permissão para aceder a este recurso. O acesso foi negado por uma política de segurança ou por falta de privilégios.',
        'note' => 'Precisa de acesso a este recurso? Solicite as permissões adequadas ao seu administrador.',
    ],

    '404' => [
        'title' => 'Página não encontrada',
        'body' => 'A página que procura foi movida, eliminada ou o endereço está incorreto. Verifique o endereço ou volte em segurança.',
    ],

    '419' => [
        'title' => 'A sua sessão expirou',
        'body' => 'A sessão terminou por motivos de segurança após um período de inatividade. Inicie sessão novamente para continuar onde estava.',
        'relogin' => 'Iniciar sessão novamente',
        'note' => 'As sessões expiram automaticamente para proteger a sua conta contra acessos não autorizados.',
    ],

    '503' => [
        'title' => 'Em manutenção',
        'body' => 'O sistema está temporariamente indisponível por manutenção ou sobrecarga. Estamos a trabalhar para restaurar o serviço o mais rápido possível.',
        'eta' => 'Previsão de regresso em :seconds s',
        'note' => 'Manutenção programada em curso. Os seus dados estão seguros e o serviço regressa em breve.',
    ],

];
