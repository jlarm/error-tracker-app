<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { dashboard } from '@/routes';
import { relativeTime } from '@/lib/relativeTime';

type StackFrame = {
    file?: string;
    line?: number;
    function?: string;
    class?: string;
    type?: string;
    [key: string]: unknown;
};

type Breadcrumb = {
    category?: string;
    level?: string;
    message?: string;
    timestamp?: string;
    data?: Record<string, unknown>;
};

type ErrorLog = {
    id: number;
    project: { id: number; name: string };
    exception_class: string;
    message: string;
    file: string;
    line: number;
    url: string | null;
    environment: string | null;
    level: string | null;
    release: string | null;
    stack_trace: StackFrame[] | Record<string, unknown> | null;
    request_payload: Record<string, unknown> | null;
    breadcrumbs: Breadcrumb[] | null;
    tags: Record<string, string | number | boolean | null> | null;
    context: Record<string, Record<string, unknown>> | null;
    occurrences: number;
    fingerprint: string;
    last_seen_at: string;
    created_at: string;
    resolved_at: string | null;
};

const props = defineProps<{
    errorLog: ErrorLog;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});

const shortClass = (full: string): string => {
    const segments = full.split('\\');
    return segments[segments.length - 1] ?? full;
};

const namespace = (full: string): string => {
    const segments = full.split('\\');
    if (segments.length <= 1) {
        return '';
    }
    return segments.slice(0, -1).join('\\');
};

const stackFrames = computed<StackFrame[]>(() => {
    const trace = props.errorLog.stack_trace;
    if (Array.isArray(trace)) {
        return trace as StackFrame[];
    }
    return [];
});

const stackTraceJson = computed(() =>
    JSON.stringify(props.errorLog.stack_trace ?? [], null, 2),
);

const breadcrumbs = computed<Breadcrumb[]>(() =>
    Array.isArray(props.errorLog.breadcrumbs) ? props.errorLog.breadcrumbs : [],
);

const requestBody = computed(() => {
    const payload = props.errorLog.request_payload;
    if (!payload) {
        return null;
    }
    const {
        method: _m,
        headers: _h,
        ...body
    } = payload as Record<string, unknown>;
    return Object.keys(body).length ? body : null;
});

const requestHeaders = computed<Record<string, string> | null>(() => {
    const payload = props.errorLog.request_payload;
    if (payload && typeof payload === 'object' && 'headers' in payload) {
        const h = payload.headers;
        if (h && typeof h === 'object') {
            return h as Record<string, string>;
        }
    }
    return null;
});

const requestMethod = computed(() => {
    const payload = props.errorLog.request_payload;
    if (payload && typeof payload === 'object' && 'method' in payload) {
        return String(payload.method);
    }
    return null;
});

const tagEntries = computed(() => {
    const t = props.errorLog.tags;
    if (!t || typeof t !== 'object') {
        return [];
    }
    return Object.entries(t).filter(([, v]) => v !== null && v !== undefined);
});

const contextBlocks = computed(() => {
    const c = props.errorLog.context;
    if (!c || typeof c !== 'object') {
        return [];
    }
    return Object.entries(c)
        .filter(([, v]) => v && typeof v === 'object')
        .map(([name, values]) => ({
            name,
            entries: Object.entries(values as Record<string, unknown>),
        }));
});

const formatLocal = (iso: string) =>
    new Date(iso).toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
    });

const formatTime = (iso: string) =>
    new Date(iso).toLocaleString(undefined, {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        fractionalSecondDigits: 3,
        hour12: false,
    });

const formatCount = (n: number): string => {
    if (n >= 1000) {
        return `${(n / 1000).toFixed(1).replace(/\.0$/, '')}K`;
    }
    return String(n);
};

const formatValue = (value: unknown): string => {
    if (value === null || value === undefined) {
        return '—';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
};

const levelClasses = (level: string | null | undefined): string => {
    switch (level) {
        case 'error':
        case 'fatal':
            return 'border-crimson-glow/40 bg-crimson-glow/10 text-crimson-glow';
        case 'warning':
            return 'border-amber-glow/40 bg-amber-glow/10 text-amber-glow';
        case 'info':
            return 'border-cyan-glow/40 bg-cyan-glow/10 text-cyan-glow';
        case 'debug':
            return 'border-sidebar-border bg-muted/40 text-muted-foreground';
        default:
            return 'border-sidebar-border bg-muted/40 text-muted-foreground';
    }
};

const formatPath = (path: string): string => {
    const segments = path.split('/');
    if (segments.length <= 3) {
        return path;
    }
    return '…/' + segments.slice(-2).join('/');
};

const flattenValue = (value: unknown): string => {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'object') {
        return JSON.stringify(value);
    }
    return String(value);
};

const buildMarkdown = (): string => {
    const e = props.errorLog;
    const lines: string[] = [];

    lines.push(`# ${shortClass(e.exception_class)}`);
    lines.push('');
    lines.push(`**Issue ID:** ERR-${e.id}`);
    lines.push(`**Project:** ${e.project.name}`);
    if (e.environment) lines.push(`**Environment:** ${e.environment}`);
    if (e.level) lines.push(`**Level:** ${e.level}`);
    if (e.release) lines.push(`**Release:** ${e.release}`);
    lines.push(`**Occurrences:** ${e.occurrences}`);
    lines.push(`**First seen:** ${e.created_at}`);
    lines.push(`**Last seen:** ${e.last_seen_at}`);
    lines.push(`**Fingerprint:** ${e.fingerprint}`);
    lines.push('');

    lines.push('## Exception');
    lines.push('');
    lines.push(`**Class:** \`${e.exception_class}\``);
    lines.push(`**Message:** ${e.message}`);
    lines.push(`**Location:** \`${e.file}:${e.line}\``);
    if (e.url) lines.push(`**URL:** ${e.url}`);
    lines.push('');

    const tagEntries = e.tags
        ? Object.entries(e.tags).filter(
              ([, v]) => v !== null && v !== undefined,
          )
        : [];
    if (tagEntries.length) {
        lines.push('## Tags');
        lines.push('');
        for (const [key, value] of tagEntries) {
            lines.push(`- **${key}:** ${flattenValue(value)}`);
        }
        lines.push('');
    }

    if (e.context && typeof e.context === 'object') {
        const blocks = Object.entries(e.context).filter(
            ([, v]) => v && typeof v === 'object',
        );
        if (blocks.length) {
            lines.push('## Contexts');
            lines.push('');
            for (const [name, values] of blocks) {
                lines.push(`### ${name}`);
                lines.push('');
                for (const [key, value] of Object.entries(
                    values as Record<string, unknown>,
                )) {
                    lines.push(`- **${name}.${key}:** ${flattenValue(value)}`);
                }
                lines.push('');
            }
        }
    }

    if (Array.isArray(e.breadcrumbs) && e.breadcrumbs.length) {
        lines.push('## Breadcrumbs');
        lines.push('');
        for (const crumb of e.breadcrumbs) {
            const timestamp = crumb.timestamp ?? '';
            const category = crumb.category ?? 'event';
            const level = crumb.level ?? 'info';
            lines.push(
                `- \`${timestamp}\` **[${level}]** \`${category}\` — ${crumb.message ?? ''}`,
            );
            if (crumb.data && Object.keys(crumb.data).length) {
                for (const [key, value] of Object.entries(crumb.data)) {
                    lines.push(`  - ${key}: ${flattenValue(value)}`);
                }
            }
        }
        lines.push('');
    }

    if (stackFrames.value.length) {
        lines.push('## Stack Trace');
        lines.push('');
        lines.push('```');
        stackFrames.value.forEach((frame, index) => {
            const location = `${frame.file ?? '[internal]'}:${frame.line ?? '?'}`;
            const fn = frame.class
                ? `${frame.class}${frame.type ?? '::'}${frame.function ?? ''}`
                : (frame.function ?? '');
            lines.push(`#${index} ${location}${fn ? ` in ${fn}()` : ''}`);
        });
        lines.push('```');
        lines.push('');
    }

    if (requestMethod.value || requestHeaders.value || requestBody.value) {
        lines.push('## HTTP Request');
        lines.push('');
        if (requestMethod.value || e.url) {
            lines.push(`**${requestMethod.value ?? 'GET'}** ${e.url ?? ''}`);
            lines.push('');
        }
        if (requestHeaders.value) {
            lines.push('### Headers');
            lines.push('');
            for (const [name, value] of Object.entries(requestHeaders.value)) {
                lines.push(`- **${name}:** ${flattenValue(value)}`);
            }
            lines.push('');
        }
        if (requestBody.value) {
            lines.push('### Body');
            lines.push('');
            lines.push('```json');
            lines.push(JSON.stringify(requestBody.value, null, 2));
            lines.push('```');
            lines.push('');
        }
    }

    return (
        lines
            .join('\n')
            .replace(/\n{3,}/g, '\n\n')
            .trimEnd() + '\n'
    );
};

const copied = ref(false);

const copyAsMarkdown = async () => {
    const markdown = buildMarkdown();
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(markdown);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = markdown;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        }
        copied.value = true;
        toast.success('Copied as Markdown');
        setTimeout(() => (copied.value = false), 2000);
    } catch (error) {
        console.error(error);
        toast.error('Failed to copy to clipboard');
    }
};

const isResolved = computed(() => props.errorLog.resolved_at !== null);

const toggleResolved = () => {
    const action = isResolved.value ? 'unresolve' : 'resolve';
    router.post(
        `/errors/${props.errorLog.id}/${action}`,
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="shortClass(errorLog.exception_class)" />

    <div class="boot-in flex h-full flex-1 flex-col gap-6 p-6">
        <header
            class="flex flex-wrap items-start justify-between gap-4 border-b border-sidebar-border pb-4"
        >
            <div class="min-w-0 flex-1">
                <Link
                    :href="`/projects/${errorLog.project.id}`"
                    class="inline-flex items-center gap-1 font-mono text-[10px] tracking-[0.22em] text-muted-foreground uppercase hover:text-phosphor"
                >
                    &lt;-- {{ errorLog.project.name }}
                </Link>
                <h1
                    class="mt-2 font-mono text-2xl font-semibold tracking-wider text-foreground uppercase"
                >
                    {{ shortClass(errorLog.exception_class) }}
                </h1>
                <p
                    v-if="namespace(errorLog.exception_class)"
                    class="font-mono text-xs text-muted-foreground"
                >
                    {{ namespace(errorLog.exception_class) }}
                </p>
                <p class="mt-2 font-mono text-sm text-foreground">
                    <span class="text-muted-foreground/60">&gt;</span>
                    {{ errorLog.message }}
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-2 font-mono">
                    <span
                        class="inline-flex items-center gap-2 border px-2 py-1 text-[10px] font-medium tracking-[0.22em] uppercase"
                        :class="
                            isResolved
                                ? 'border-phosphor/40 bg-phosphor/10 text-phosphor shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_20%,transparent)]'
                                : 'border-crimson-glow/40 bg-crimson-glow/10 text-crimson-glow shadow-[0_0_12px_color-mix(in_oklch,var(--crimson-glow)_20%,transparent)]'
                        "
                    >
                        <span
                            class="size-1.5"
                            :class="
                                isResolved ? 'bg-phosphor' : 'bg-crimson-glow'
                            "
                        />
                        {{ isResolved ? 'RESOLVED' : 'UNRESOLVED' }}
                    </span>
                    <span
                        class="inline-flex items-center gap-2 border border-sidebar-border bg-muted/40 px-2 py-1 text-[10px] font-medium tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        ERR-{{ errorLog.id }}
                    </span>
                    <span
                        v-if="errorLog.level"
                        class="border px-2 py-1 text-[10px] font-medium tracking-[0.22em] uppercase"
                        :class="levelClasses(errorLog.level)"
                    >
                        {{ errorLog.level }}
                    </span>
                    <span
                        v-if="errorLog.environment"
                        class="border border-sidebar-border bg-muted/40 px-2 py-1 text-[10px] tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        env:{{ errorLog.environment }}
                    </span>
                    <span
                        v-if="errorLog.release"
                        class="border border-sidebar-border bg-muted/40 px-2 py-1 text-[10px] tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        rel:{{ errorLog.release }}
                    </span>
                </div>
            </div>
            <div class="flex flex-col items-end gap-3 text-right">
                <div>
                    <div
                        class="font-mono text-[10px] tracking-[0.22em] text-muted-foreground uppercase"
                    >
                        <span class="label-bracket">EVENTS</span>
                    </div>
                    <div
                        class="mt-1 font-mono text-3xl font-semibold text-phosphor tabular-nums"
                    >
                        {{ formatCount(errorLog.occurrences) }}
                    </div>
                </div>
                <button
                    type="button"
                    @click="toggleResolved"
                    class="inline-flex items-center gap-1.5 border px-2.5 py-1.5 font-mono text-[10px] font-medium tracking-[0.22em] uppercase transition"
                    :class="
                        isResolved
                            ? 'border-muted-foreground/40 bg-muted/30 text-muted-foreground hover:border-crimson-glow/40 hover:bg-crimson-glow/10 hover:text-crimson-glow'
                            : 'border-phosphor/40 bg-phosphor/5 text-phosphor hover:bg-phosphor/15 hover:shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_25%,transparent)]'
                    "
                    :title="
                        isResolved
                            ? 'Reopen this issue'
                            : 'Mark this issue as resolved'
                    "
                >
                    <svg
                        v-if="isResolved"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 16 16"
                        fill="currentColor"
                        class="size-3.5"
                    >
                        <path
                            d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2Zm0 1.5a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Zm0 2.25a.75.75 0 0 0-.75.75v2a.75.75 0 0 0 1.5 0V6.5A.75.75 0 0 0 8 5.75Zm0 5a.875.875 0 1 0 0 1.75.875.875 0 0 0 0-1.75Z"
                        />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 16 16"
                        fill="currentColor"
                        class="size-3.5"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.75.75 0 1 1 1.06-1.06L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    {{ isResolved ? 'Reopen' : 'Mark Resolved' }}
                </button>
                <button
                    type="button"
                    @click="copyAsMarkdown"
                    class="inline-flex items-center gap-1.5 border border-phosphor/40 bg-phosphor/5 px-2.5 py-1.5 font-mono text-[10px] font-medium tracking-[0.22em] text-phosphor uppercase transition hover:bg-phosphor/15 hover:shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_25%,transparent)]"
                    :title="
                        copied
                            ? 'Copied!'
                            : 'Copy a markdown summary you can paste into an AI chat'
                    "
                >
                    <svg
                        v-if="copied"
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 16 16"
                        fill="currentColor"
                        class="size-3.5 text-emerald-500"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.75.75 0 1 1 1.06-1.06L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg
                        v-else
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 16 16"
                        fill="currentColor"
                        class="size-3.5"
                    >
                        <path
                            d="M11 0H4a2 2 0 0 0-2 2v9h1.5V2a.5.5 0 0 1 .5-.5h7V0Z"
                        />
                        <path
                            d="M5 4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4Zm2-.5a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V4a.5.5 0 0 0-.5-.5H7Z"
                        />
                    </svg>
                    {{ copied ? 'Copied' : 'Copy as Markdown' }}
                </button>
            </div>
        </header>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <section class="border border-sidebar-border bg-card/50">
                    <header
                        class="border-b border-sidebar-border bg-muted/40 px-4 py-2 font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                    >
                        <span class="label-bracket">HIGHLIGHTS</span>
                    </header>
                    <dl class="grid gap-x-6 gap-y-2 p-4 text-sm sm:grid-cols-2">
                        <div class="flex items-baseline gap-3">
                            <dt
                                class="w-24 shrink-0 text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                Exception
                            </dt>
                            <dd class="min-w-0 font-mono text-xs break-all">
                                {{ errorLog.exception_class }}
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <dt
                                class="w-24 shrink-0 text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                Location
                            </dt>
                            <dd class="min-w-0 font-mono text-xs break-all">
                                {{ errorLog.file }}:{{ errorLog.line }}
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-3 sm:col-span-2">
                            <dt
                                class="w-24 shrink-0 text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                URL
                            </dt>
                            <dd class="min-w-0 text-xs break-all">
                                <a
                                    v-if="errorLog.url"
                                    :href="errorLog.url"
                                    target="_blank"
                                    rel="noreferrer"
                                    class="text-primary hover:underline"
                                >
                                    {{ errorLog.url }}
                                </a>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <dt
                                class="w-24 shrink-0 text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                Fingerprint
                            </dt>
                            <dd class="min-w-0 font-mono text-xs break-all">
                                {{ errorLog.fingerprint }}
                            </dd>
                        </div>
                        <div class="flex items-baseline gap-3">
                            <dt
                                class="w-24 shrink-0 text-xs tracking-wide text-muted-foreground uppercase"
                            >
                                Occurrences
                            </dt>
                            <dd class="min-w-0 text-xs tabular-nums">
                                {{ errorLog.occurrences }}
                            </dd>
                        </div>
                    </dl>
                </section>

                <section class="border border-sidebar-border bg-card/50">
                    <header
                        class="flex items-center justify-between border-b border-sidebar-border bg-muted/40 px-4 py-2"
                    >
                        <h2
                            class="font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                        >
                            <span class="label-bracket">STACK_TRACE</span>
                            <span
                                class="ml-2 font-normal text-muted-foreground"
                            >
                                {{ stackFrames.length }} frames
                            </span>
                        </h2>
                    </header>

                    <ol
                        v-if="stackFrames.length"
                        class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                    >
                        <li
                            v-for="(frame, index) in stackFrames"
                            :key="index"
                            class="px-4 py-3 text-xs"
                        >
                            <div class="flex flex-wrap items-baseline gap-x-2">
                                <span
                                    class="font-mono text-[10px] text-muted-foreground tabular-nums"
                                >
                                    #{{ index }}
                                </span>
                                <span
                                    class="font-mono break-all text-foreground"
                                >
                                    {{ frame.file ?? '[internal]' }}
                                    <span
                                        v-if="frame.line"
                                        class="text-muted-foreground"
                                    >
                                        :{{ frame.line }}
                                    </span>
                                </span>
                            </div>
                            <div
                                v-if="frame.class || frame.function"
                                class="mt-1 pl-7 font-mono text-[11px] break-all text-muted-foreground"
                            >
                                in
                                <span class="text-foreground">
                                    <template v-if="frame.class">
                                        {{ frame.class
                                        }}<span class="text-muted-foreground">{{
                                            frame.type ?? '::'
                                        }}</span>
                                    </template>
                                    <template v-if="frame.function">
                                        {{ frame.function }}()
                                    </template>
                                </span>
                            </div>
                        </li>
                    </ol>
                    <p
                        v-else
                        class="px-4 py-6 text-center text-xs text-muted-foreground"
                    >
                        No structured stack trace available.
                    </p>

                    <details
                        class="border-t border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <summary
                            class="cursor-pointer px-4 py-2 text-xs font-medium text-muted-foreground hover:bg-muted/40"
                        >
                            Raw JSON
                        </summary>
                        <pre
                            class="max-h-[40vh] overflow-auto rounded-b-xl bg-zinc-950 p-4 font-mono text-xs leading-relaxed text-zinc-100"
                        ><code>{{ stackTraceJson }}</code></pre>
                    </details>
                </section>

                <section
                    v-if="breadcrumbs.length"
                    class="border border-sidebar-border bg-card/50"
                >
                    <header
                        class="flex items-center justify-between border-b border-sidebar-border bg-muted/40 px-4 py-2"
                    >
                        <h2
                            class="font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                        >
                            <span class="label-bracket">BREADCRUMBS</span>
                            <span
                                class="ml-2 font-normal text-muted-foreground"
                            >
                                {{ breadcrumbs.length }} events
                            </span>
                        </h2>
                    </header>
                    <ol
                        class="divide-y divide-sidebar-border/70 dark:divide-sidebar-border"
                    >
                        <li
                            v-for="(crumb, index) in breadcrumbs"
                            :key="index"
                            class="grid grid-cols-[auto_1fr_auto] items-baseline gap-x-3 px-4 py-2.5 text-xs"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    v-if="crumb.level"
                                    class="rounded px-1.5 py-0.5 font-mono text-[10px] font-medium uppercase"
                                    :class="levelClasses(crumb.level)"
                                >
                                    {{ crumb.level }}
                                </span>
                                <span
                                    class="font-mono text-[11px] text-muted-foreground"
                                >
                                    {{ crumb.category ?? 'event' }}
                                </span>
                            </div>
                            <div class="min-w-0 break-all">
                                <div class="font-mono text-foreground">
                                    {{ crumb.message ?? '—' }}
                                </div>
                                <div
                                    v-if="
                                        crumb.data &&
                                        Object.keys(crumb.data).length
                                    "
                                    class="mt-1 font-mono text-[10px] text-muted-foreground"
                                >
                                    <span
                                        v-for="[key, value] in Object.entries(
                                            crumb.data,
                                        )"
                                        :key="key"
                                        class="mr-3"
                                    >
                                        {{ key }}={{ formatValue(value) }}
                                    </span>
                                </div>
                            </div>
                            <time
                                v-if="crumb.timestamp"
                                class="font-mono text-[10px] whitespace-nowrap text-muted-foreground"
                                :title="crumb.timestamp"
                            >
                                {{ formatTime(crumb.timestamp) }}
                            </time>
                        </li>
                    </ol>
                </section>

                <section class="border border-sidebar-border bg-card/50">
                    <header
                        class="flex flex-wrap items-center gap-2 border-b border-sidebar-border bg-muted/40 px-4 py-2"
                    >
                        <h2
                            class="font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                        >
                            <span class="label-bracket">HTTP_REQUEST</span>
                        </h2>
                        <span
                            v-if="requestMethod"
                            class="rounded-md bg-emerald-500/10 px-2 py-0.5 font-mono text-[10px] font-bold text-emerald-600 uppercase dark:text-emerald-400"
                        >
                            {{ requestMethod }}
                        </span>
                        <span
                            v-if="errorLog.url"
                            class="truncate font-mono text-xs text-muted-foreground"
                        >
                            {{ errorLog.url }}
                        </span>
                    </header>

                    <div
                        v-if="requestHeaders"
                        class="border-b border-sidebar-border/70 dark:border-sidebar-border"
                    >
                        <h3
                            class="px-4 pt-3 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Headers
                        </h3>
                        <dl
                            class="grid grid-cols-[max-content_1fr] gap-x-4 gap-y-1 p-4 text-xs"
                        >
                            <template
                                v-for="[name, value] in Object.entries(
                                    requestHeaders,
                                )"
                                :key="name"
                            >
                                <dt class="font-mono text-muted-foreground">
                                    {{ name }}
                                </dt>
                                <dd class="min-w-0 font-mono break-all">
                                    {{ value }}
                                </dd>
                            </template>
                        </dl>
                    </div>

                    <template v-if="requestBody">
                        <h3
                            class="px-4 pt-3 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            Body
                        </h3>
                        <pre
                            class="max-h-[40vh] overflow-auto rounded-b-xl bg-zinc-950 p-4 font-mono text-xs leading-relaxed text-zinc-100"
                        ><code>{{ JSON.stringify(requestBody, null, 2) }}</code></pre>
                    </template>
                    <p
                        v-else-if="!requestHeaders"
                        class="px-4 py-6 text-center text-xs text-muted-foreground"
                    >
                        No request payload was captured.
                    </p>
                </section>

                <section
                    v-if="contextBlocks.length"
                    class="border border-sidebar-border bg-card/50"
                >
                    <header
                        class="border-b border-sidebar-border bg-muted/40 px-4 py-2 font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                    >
                        <span class="label-bracket">CONTEXTS</span>
                    </header>
                    <div class="grid gap-4 p-4 sm:grid-cols-2">
                        <article
                            v-for="block in contextBlocks"
                            :key="block.name"
                            class="rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
                        >
                            <h3
                                class="border-b border-sidebar-border/70 bg-muted/30 px-3 py-1.5 text-xs font-semibold capitalize dark:border-sidebar-border"
                            >
                                {{ block.name }}
                            </h3>
                            <dl
                                class="grid grid-cols-[max-content_1fr] gap-x-3 gap-y-1 p-3 text-xs"
                            >
                                <template
                                    v-for="[key, value] in block.entries"
                                    :key="key"
                                >
                                    <dt class="font-mono text-muted-foreground">
                                        {{ key }}
                                    </dt>
                                    <dd class="min-w-0 font-mono break-all">
                                        {{ formatValue(value) }}
                                    </dd>
                                </template>
                            </dl>
                        </article>
                    </div>
                </section>
            </div>

            <aside class="space-y-6">
                <section class="border border-sidebar-border bg-card/50">
                    <header
                        class="border-b border-sidebar-border bg-muted/40 px-4 py-2 font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                    >
                        <span class="label-bracket">TIMELINE</span>
                    </header>
                    <dl class="space-y-3 p-4 text-xs">
                        <div>
                            <dt
                                class="font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                Last seen
                            </dt>
                            <dd class="mt-0.5">
                                {{ relativeTime(errorLog.last_seen_at) }}
                                <span class="block text-muted-foreground">
                                    {{ formatLocal(errorLog.last_seen_at) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt
                                class="font-medium tracking-wide text-muted-foreground uppercase"
                            >
                                First seen
                            </dt>
                            <dd class="mt-0.5">
                                {{ relativeTime(errorLog.created_at) }}
                                <span class="block text-muted-foreground">
                                    {{ formatLocal(errorLog.created_at) }}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </section>

                <section
                    v-if="tagEntries.length"
                    class="border border-sidebar-border bg-card/50"
                >
                    <header
                        class="border-b border-sidebar-border bg-muted/40 px-4 py-2 font-mono text-[10px] tracking-[0.22em] text-phosphor uppercase"
                    >
                        <span class="label-bracket">TAGS</span>
                    </header>
                    <dl
                        class="divide-y divide-sidebar-border/70 text-xs dark:divide-sidebar-border"
                    >
                        <div
                            v-for="[key, value] in tagEntries"
                            :key="key"
                            class="flex items-baseline gap-3 px-4 py-2"
                        >
                            <dt
                                class="w-28 shrink-0 font-mono text-muted-foreground"
                            >
                                {{ key }}
                            </dt>
                            <dd class="min-w-0 font-mono break-all">
                                {{
                                    key === 'transaction'
                                        ? formatPath(String(value))
                                        : value
                                }}
                            </dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</template>
