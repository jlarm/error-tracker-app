<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slack Notifications
    |--------------------------------------------------------------------------
    |
    | When enabled and a webhook URL is configured, a Block Kit message is
    | posted to Slack each time a new issue arrives or a previously resolved
    | issue regresses. Recurring (non-regressed) events do not notify.
    |
    */
    'slack' => [
        'enabled' => env('ERROR_SLACK_ENABLED', true),
        'webhook_url' => env('ERROR_SLACK_WEBHOOK_URL'),
    ],

];
