<?php

return [

    'labels' => [
        'message_no' => 'Message no.',
        'request_id' => 'Request ID',
        'retry' => 'Try again',
        'reload' => 'Reload page',
        'check_status' => 'Check status',
        'go_home' => 'Back to safety',
        'go_back' => 'Go back',
        'go_dashboard' => 'Go to dashboard',
        'contact_support' => 'Contact support',
        'copy_detail' => 'Copy details',
        'copied' => 'Copied',
        'view_more' => 'View more',
        'view_less' => 'View less',
        'technical_detail' => 'Technical detail',
        'stack_trace' => 'Stack trace',
        'diagnosis' => 'Diagnosis',
        'system_response' => 'System response',
        'procedure' => 'What to do',
        'note' => 'Note',
        'security' => 'Security',
        'status' => 'Status',
        'guard' => 'Guard',
        'account' => 'Account',
        'roles' => 'Your roles',
        'missing_permissions' => 'Missing permission|Missing permissions',
        'missing_roles' => 'Missing role|Missing roles',
        'permission_kind' => 'Guards',
        'reason' => 'Reason',
        'support_prompt' => 'Need help? Contact',
        'support_word' => 'support',
    ],

    'kinds' => [
        'resource' => 'Resource',
        'page' => 'Page',
        'widget' => 'Widget',
        'custom' => 'Custom permission',
    ],

    '500' => [
        'title' => 'Something went wrong',
        'body' => 'An unexpected error interrupted your request.',
        'diagnosis' => 'The application hit an unexpected error while handling your request.',
        'response' => 'Nothing was saved — the operation was rolled back safely.',
        'procedure' => 'Try again in a moment. If it keeps happening, copy the message number below and send it to support.',
    ],

    '403' => [
        'title' => 'Access denied',
        'body' => 'You do not have permission to access this resource. Access was denied by a security policy or missing privileges.',
        'note' => 'Need access to this resource? Request the right permissions from your administrator.',
    ],

    '404' => [
        'title' => 'Page not found',
        'body' => 'The page you are looking for was moved, deleted, or the address is wrong. Check the URL or head back to safety.',
    ],

    '419' => [
        'title' => 'Your session expired',
        'body' => 'Your session ended for security reasons after a period of inactivity. Sign in again to continue where you left off.',
        'relogin' => 'Sign in again',
        'note' => 'Sessions expire automatically to protect your account against unauthorised access.',
    ],

    '503' => [
        'title' => 'Down for maintenance',
        'body' => 'The system is temporarily unavailable for maintenance or high load. We are working to restore service as quickly as possible.',
        'eta' => 'Estimated back in :seconds s',
        'note' => 'Scheduled maintenance in progress. Your data is safe and the service will be back shortly.',
    ],

];
