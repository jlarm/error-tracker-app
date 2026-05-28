<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import CodeBlock from '@/components/CodeBlock.vue';

const props = defineProps<{
    ingestUrl: string;
}>();

const installBlock = `composer require armp/error-tracker`;

const envBlock = computed(
    () =>
        `ERROR_TRACKER_URL=${props.ingestUrl}\nERROR_TRACKER_KEY=replace-with-your-project-api-key\nERROR_TRACKER_RELEASE=v1.0.0   # optional`,
);

const publishBlock = `php artisan vendor:publish --tag=error-tracker-config`;

const handlerBlock = `// bootstrap/app.php

use Armp\\ErrorTracker\\Facades\\ErrorTracker;
use Illuminate\\Foundation\\Configuration\\Exceptions;
use Throwable;

return Application::configure(basePath: dirname(__DIR__))
    // ... withRouting, withMiddleware
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            ErrorTracker::capture($e, request());
        });
    })
    ->create();`;

const verifyBlock = `php artisan error-tracker:test

# A RuntimeException should appear on this dashboard within seconds.`;

const usageBlock = `use Armp\\ErrorTracker\\Facades\\ErrorTracker;

// Capture an exception manually
ErrorTracker::capture($e);

// Capture a free-form message
ErrorTracker::message('Background job took longer than expected', 'warning');

// Identify the current user (stored in the "user" context block)
ErrorTracker::user([
    'id'    => $user->id,
    'email' => $user->email,
]);

// Add tags and arbitrary context
ErrorTracker::tag('feature', 'checkout');
ErrorTracker::context('feature_flags', ['new_checkout' => true]);

// Record breadcrumbs leading up to an error
ErrorTracker::breadcrumb('db.sql.query', 'select * from users', ['duration_ms' => 1.2]);`;

const payloadBlock = `POST ${props.ingestUrl}
Authorization: Bearer <project_api_key>
Content-Type: application/json

{
    "exception_class": "RuntimeException",        // required, string
    "message":         "Cannot connect to db",    // required, string
    "file":            "/app/Http/.../Foo.php",   // required, string
    "line":            120,                       // required, int
    "url":             "https://site.com/foo",    // optional
    "environment":     "production",              // optional
    "level":           "error",                   // optional (default: error)
    "release":         "v1.2.3",                  // optional
    "stack_trace":     [ /* array of frames */ ], // required
    "request_payload": { /* method, input, headers */ },  // optional
    "breadcrumbs":     [ /* recent events */ ],   // optional
    "tags":            { /* key/value pairs */ }, // optional
    "context":         { /* nested blocks */ }    // optional
}

// Response: 202 Accepted
// { "id": 42, "occurrences": 1 }`;
</script>

<template>
    <Head title="Docs" />

    <div class="boot-in mx-auto flex h-full w-full max-w-5xl flex-col gap-8 p-6">
        <header class="border-b border-sidebar-border pb-4">
            <p
                class="font-mono text-[10px] uppercase tracking-[0.32em] text-muted-foreground"
            >
                <span class="label-bracket">SETUP</span>
            </p>
            <h1
                class="mt-1 font-mono text-2xl font-semibold uppercase tracking-wider"
            >
                Integration Guide
            </h1>
            <p
                class="mt-1 font-mono text-xs uppercase tracking-[0.18em] text-muted-foreground"
            >
                &gt; install the
                <span class="text-phosphor">armp/error-tracker</span> SDK and
                pipe a Laravel app's exceptions into this console
            </p>
        </header>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">01_CREATE_PROJECT</span>
            </h2>
            <ol
                class="ml-4 list-decimal space-y-1 font-mono text-sm marker:text-muted-foreground"
            >
                <li>
                    Open the
                    <span class="label-bracket text-phosphor">PROJECT</span>
                    selector in the top-left of the sidebar.
                </li>
                <li>
                    Choose
                    <span class="text-phosphor">&gt; Add a project</span>
                    at the bottom of the menu.
                </li>
                <li>
                    Give the site a recognizable name (e.g.
                    <span class="text-foreground">Marketing Site</span>) and
                    submit.
                </li>
                <li>
                    You'll land on the project page. Copy the
                    <span class="label-bracket text-phosphor">API_KEY</span>
                    shown in the top-right card &mdash; you'll need it in step
                    03.
                </li>
            </ol>
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">02_INGEST_ENDPOINT</span>
            </h2>
            <p class="font-mono text-sm">
                Errors are POSTed to a single endpoint, authenticated with a
                project's API key as a bearer token.
            </p>
            <div
                class="grid gap-3 border border-sidebar-border bg-card/40 p-4 font-mono text-xs sm:grid-cols-[max-content_1fr] sm:gap-x-6"
            >
                <span class="text-muted-foreground">URL</span>
                <code class="break-all text-phosphor">{{ ingestUrl }}</code>
                <span class="text-muted-foreground">Method</span>
                <code class="text-foreground">POST</code>
                <span class="text-muted-foreground">Auth</span>
                <code class="break-all text-foreground">
                    Authorization: Bearer &lt;project_api_key&gt;
                </code>
                <span class="text-muted-foreground">Response</span>
                <code class="text-foreground">202 Accepted</code>
            </div>
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">03_INSTALL_SDK</span>
            </h2>
            <p class="font-mono text-sm">
                Pull the
                <span class="text-foreground">armp/error-tracker</span> package
                into the Laravel app you want to monitor. Laravel auto-discovers
                its service provider and facade &mdash; no manual registration.
            </p>
            <CodeBlock :code="installBlock" language="bash" filename="" />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">04_CONFIGURE_ENV</span>
            </h2>
            <p class="font-mono text-sm">
                Set the URL and key in
                <span class="text-foreground">.env</span>. The release tag is
                optional but recommended &mdash; it's what appears in the
                <span class="text-foreground">rel:</span> pill on issues.
            </p>
            <CodeBlock :code="envBlock" language=".env" filename="" />
            <p class="font-mono text-xs text-muted-foreground">
                &gt; optionally publish the config to tweak scrub lists,
                breadcrumb limits, and ignored exception classes:
            </p>
            <CodeBlock :code="publishBlock" language="bash" filename="" />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">05_WIRE_EXCEPTION_HANDLER</span>
            </h2>
            <p class="font-mono text-sm">
                Add one line to
                <span class="text-foreground">bootstrap/app.php</span> so every
                reported throwable is forwarded.
            </p>
            <CodeBlock
                :code="handlerBlock"
                language="php"
                filename="bootstrap/app.php"
            />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">06_VERIFY</span>
            </h2>
            <p class="font-mono text-sm">
                Fire the bundled test command from the client app and refresh
                this dashboard:
            </p>
            <CodeBlock :code="verifyBlock" language="bash" filename="" />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">07_API</span>
            </h2>
            <p class="font-mono text-sm">
                Beyond auto-capture, the SDK exposes a small fluent API for
                attaching context, identifying users, and recording
                breadcrumbs:
            </p>
            <CodeBlock
                :code="usageBlock"
                language="php"
                filename="anywhere in your app"
            />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">PAYLOAD_REFERENCE</span>
            </h2>
            <p class="font-mono text-sm">
                Full payload shape the ingestion API accepts. Only the first
                five fields are required &mdash; everything else is optional
                context that enriches the issue page.
            </p>
            <CodeBlock :code="payloadBlock" language="http" filename="" />
        </section>

        <section class="space-y-4">
            <h2
                class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
            >
                <span class="label-bracket">DEDUPLICATION</span>
            </h2>
            <p class="font-mono text-sm leading-relaxed">
                The ingestion endpoint deduplicates events by
                <span class="text-foreground">
                    md5(exception_class + file + line)
                </span>
                scoped per project. Identical errors update the existing issue's
                <span class="text-foreground">occurrences</span> count and
                <span class="text-foreground">last_seen_at</span> timestamp, and
                replace the stored context with the latest event's data. You'll
                never get spammed with duplicate issues.
            </p>
        </section>
    </div>
</template>
