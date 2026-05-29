<?php

namespace App\Jobs;

use App\Models\ErrorLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendSlackErrorNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public int $errorLogId,
        public bool $isRegression = false,
    ) {}

    public function handle(): void
    {
        if (! config('error-tracking.slack.enabled')) {
            return;
        }

        $webhookUrl = config('error-tracking.slack.webhook_url');

        if (empty($webhookUrl)) {
            return;
        }

        $errorLog = ErrorLog::with('project:id,name')->find($this->errorLogId);

        if (! $errorLog) {
            return;
        }

        $response = Http::timeout(5)
            ->asJson()
            ->post($webhookUrl, $this->buildPayload($errorLog));

        if ($response->failed()) {
            Log::warning('Slack notification rejected', [
                'status' => $response->status(),
                'body' => (string) $response->body(),
                'error_log_id' => $errorLog->id,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function buildPayload(ErrorLog $errorLog): array
    {
        $shortClass = class_basename($errorLog->exception_class);
        $projectName = $errorLog->project->name;
        $issueUrl = route('errors.show', $errorLog);

        $headerEmoji = $this->isRegression ? '🟠' : '🔴';
        $label = $this->isRegression ? 'Regression' : 'New error';
        $headerText = "{$headerEmoji} {$label} in {$projectName}";

        $message = trim($errorLog->message ?? '') !== ''
            ? $errorLog->message
            : '(no message)';

        $blocks = [
            [
                'type' => 'header',
                'text' => [
                    'type' => 'plain_text',
                    'text' => mb_strimwidth($headerText, 0, 150, '…'),
                    'emoji' => true,
                ],
            ],
            [
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => "*`{$shortClass}`*\n```".mb_strimwidth($message, 0, 2800, '…').'```',
                ],
            ],
        ];

        $fields = [
            ['type' => 'mrkdwn', 'text' => "*Location*\n`{$errorLog->file}:{$errorLog->line}`"],
        ];
        if ($errorLog->environment) {
            $fields[] = ['type' => 'mrkdwn', 'text' => "*Environment*\n{$errorLog->environment}"];
        }
        if ($errorLog->release) {
            $fields[] = ['type' => 'mrkdwn', 'text' => "*Release*\n`{$errorLog->release}`"];
        }
        if ($errorLog->url) {
            $fields[] = ['type' => 'mrkdwn', 'text' => "*URL*\n{$errorLog->url}"];
        }

        $blocks[] = [
            'type' => 'section',
            'fields' => array_slice($fields, 0, 10),
        ];

        $blocks[] = [
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => ['type' => 'plain_text', 'text' => 'View issue', 'emoji' => true],
                    'url' => $issueUrl,
                    'style' => $this->isRegression ? 'danger' : 'primary',
                ],
            ],
        ];

        return [
            'text' => $headerText.': '.$message,
            'blocks' => $blocks,
        ];
    }
}
