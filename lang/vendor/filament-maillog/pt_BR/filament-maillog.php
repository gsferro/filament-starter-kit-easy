<?php

/*
 * Trilha de e-mail em pt-BR (tapp/filament-maillog).
 *
 * O `navigation.group` é o que encaixa a tela no grupo "Trilhas" do painel /infra,
 * ao lado do log de autenticação e da auditoria. E ele SÓ pode ser definido aqui:
 * `MailLogResource::getNavigationGroup()` devolve
 * `__('filament-maillog::filament-maillog.navigation.group')` — não há chave de
 * config nem método de plugin para isso. Mudar o grupo em qualquer outro lugar não
 * tem efeito.
 *
 * Sem este arquivo a tela cairia fora dos quatro grupos declarados no
 * InfraPanelProvider, com o rótulo "Logs" em inglês.
 */
return [
    'navigation.group' => 'Trilhas',

    'navigation.maillog.label'        => 'E-mail enviado',
    'navigation.maillog.plural-label' => 'E-mails enviados',

    'table.heading' => 'E-mails enviados',

    'column.status'      => 'Situação',
    'column.subject'     => 'Assunto',
    'column.to'          => 'Para',
    'column.from'        => 'De',
    'column.cc'          => 'Cópia',
    'column.bcc'         => 'Cópia oculta',
    'column.message_id'  => 'ID da mensagem',
    'column.delivered'   => 'Entregue em',
    'column.opened'      => 'Aberto em',
    'column.bounced'     => 'Devolvido em',
    'column.complaint'   => 'Reclamação em',
    'column.body'        => 'Corpo',
    'column.headers'     => 'Cabeçalhos',
    'column.attachments' => 'Anexos',
    'column.data'        => 'Dados',
    'column.created_at'  => 'Enviado em',
    'column.updated_at'  => 'Atualizado em',

    'status.sent'       => 'enviado',
    'status.delivered'  => 'entregue',
    'status.delayed'    => 'atrasado',
    'status.complained' => 'reclamado',
    'status.bounced'    => 'devolvido',
    'status.Delivery'   => 'entregue',
    'status.Bounce'     => 'devolvido',
    'status.Complaint'  => 'reclamado',
];
