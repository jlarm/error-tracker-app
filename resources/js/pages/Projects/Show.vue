<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { toast } from 'vue-sonner';
import { relativeTime, ageBetween } from '@/lib/relativeTime';
import { dashboard } from '@/routes';

type ErrorRow = {
    id: number;
    exception_class: string;
    message: string;
    file: string;
    line: number;
    url: string | null;
    occurrences: number;
    last_seen_at: string;
    created_at: string;
    resolved_at: string | null;
};

type PaginatedErrors = {
    data: ErrorRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
    from: number | null;
    to: number | null;
    total: number;
};

type Status = 'unresolved' | 'resolved' | 'all';

const props = defineProps<{
    project: {
        id: number;
        name: string;
        api_key: string;
    };
    errors: PaginatedErrors;
    status: Status;
    counts: Record<Status, number>;
}>();

const filterTabs: Array<{ key: Status; label: string }> = [
    { key: 'unresolved', label: 'UNRESOLVED' },
    { key: 'resolved', label: 'RESOLVED' },
    { key: 'all', label: 'ALL' },
];

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
        ],
    },
});

const now = new Date().toISOString();

const shortClass = (full: string): string => {
    const segments = full.split('\\');

    return segments[segments.length - 1] ?? full;
};

const shortFile = (file: string): string => {
    const segments = file.split('/');

    return segments.slice(-2).join('/');
};

const formatCount = (n: number): string => {
    if (n >= 1000) {
        return `${(n / 1000).toFixed(1).replace(/\.0$/, '')}K`;
    }

    return String(n);
};

const apiKeyCopied = ref(false);

const copyApiKey = async () => {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(props.project.api_key);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = props.project.api_key;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        }

        apiKeyCopied.value = true;
        toast.success('API key copied');
        setTimeout(() => (apiKeyCopied.value = false), 1500);
    } catch (error) {
        console.error(error);
        toast.error('Copy failed');
    }
};

const range = computed(() => {
    if (!props.errors.from || !props.errors.to) {
        return '0 of 0';
    }

    return `${props.errors.from}-${props.errors.to} of ${props.errors.total}`;
});
</script>

<template>
    <Head :title="`${project.name} issues`" />

    <div class="boot-in flex h-full flex-1 flex-col gap-6 p-6">
        <header
            class="flex flex-wrap items-start justify-between gap-4 border-b border-sidebar-border pb-4"
        >
            <div>
                <p
                    class="font-mono text-[10px] uppercase tracking-[0.32em] text-muted-foreground"
                >
                    <span class="label-bracket">PROJECT</span>
                </p>
                <h1
                    class="mt-1 font-mono text-2xl font-semibold uppercase tracking-wider"
                >
                    {{ project.name }}
                </h1>
                <p
                    class="mt-1 font-mono text-xs uppercase tracking-[0.18em] text-muted-foreground"
                >
                    &gt; {{ counts.all }} issue<span v-if="counts.all !== 1">s</span> tracked
                    <span class="ml-3 text-muted-foreground/70">
                        ({{ counts.unresolved }} open / {{ counts.resolved }}
                        resolved)
                    </span>
                </p>
            </div>
            <button
                type="button"
                @click="copyApiKey"
                :title="apiKeyCopied ? 'Copied!' : 'Click to copy API key'"
                class="group cursor-pointer border border-sidebar-border bg-card/60 px-3 py-2 text-left text-xs transition hover:border-phosphor/50 hover:bg-phosphor/5 hover:shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_18%,transparent)]"
            >
                <div
                    class="flex items-center justify-between gap-3 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground"
                >
                    <span class="label-bracket">API_KEY</span>
                    <span
                        class="inline-flex items-center gap-1 text-phosphor opacity-0 transition group-hover:opacity-100"
                    >
                        <template v-if="apiKeyCopied">
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16"
                                fill="currentColor"
                                class="size-3"
                            >
                                <path
                                    fill-rule="evenodd"
                                    d="M13.78 4.22a.75.75 0 0 1 0 1.06l-7.25 7.25a.75.75 0 0 1-1.06 0L2.22 9.28a.75.75 0 1 1 1.06-1.06L6 10.94l6.72-6.72a.75.75 0 0 1 1.06 0Z"
                                    clip-rule="evenodd"
                                />
                            </svg>
                            COPIED
                        </template>
                        <template v-else>
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                viewBox="0 0 16 16"
                                fill="currentColor"
                                class="size-3"
                            >
                                <path
                                    d="M11 0H4a2 2 0 0 0-2 2v9h1.5V2a.5.5 0 0 1 .5-.5h7V0Z"
                                />
                                <path
                                    d="M5 4a2 2 0 0 1 2-2h7a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4Zm2-.5a.5.5 0 0 0-.5.5v10a.5.5 0 0 0 .5.5h7a.5.5 0 0 0 .5-.5V4a.5.5 0 0 0-.5-.5H7Z"
                                />
                            </svg>
                            COPY
                        </template>
                    </span>
                </div>
                <code class="mt-1 block font-mono text-phosphor">
                    {{ project.api_key }}
                </code>
            </button>
        </header>

        <div
            class="flex flex-wrap items-center gap-2 font-mono text-[10px] uppercase tracking-[0.22em]"
        >
            <span class="text-muted-foreground">
                <span class="label-bracket">FILTER</span>
            </span>
            <Link
                v-for="tab in filterTabs"
                :key="tab.key"
                :href="`/projects/${project.id}?status=${tab.key}`"
                preserve-scroll
                class="inline-flex items-center gap-2 border px-2.5 py-1 transition"
                :class="
                    status === tab.key
                        ? 'border-phosphor/50 bg-phosphor/10 text-phosphor shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_18%,transparent)]'
                        : 'border-sidebar-border bg-muted/30 text-muted-foreground hover:border-phosphor/30 hover:text-foreground'
                "
            >
                <span
                    class="size-1.5"
                    :class="{
                        'bg-phosphor': tab.key === 'resolved',
                        'bg-crimson-glow': tab.key === 'unresolved',
                        'bg-muted-foreground': tab.key === 'all',
                    }"
                />
                {{ tab.label }}
                <span class="tabular-nums text-muted-foreground/80">
                    {{ counts[tab.key] }}
                </span>
            </Link>
        </div>

        <div
            class="overflow-hidden border border-sidebar-border bg-card/50"
        >
            <table class="w-full text-sm">
                <thead
                    class="border-b border-sidebar-border bg-muted/50 text-[10px] uppercase tracking-[0.22em] text-muted-foreground"
                >
                    <tr>
                        <th class="w-12 px-3 py-2.5 text-left font-medium">#</th>
                        <th class="px-3 py-2.5 text-left font-medium">Issue</th>
                        <th class="px-3 py-2.5 text-left font-medium">Last Seen</th>
                        <th class="px-3 py-2.5 text-left font-medium">Age</th>
                        <th class="px-3 py-2.5 text-right font-medium">Events</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(error, index) in errors.data"
                        :key="error.id"
                        class="group cursor-pointer border-b border-sidebar-border/60 transition last:border-b-0 hover:bg-muted/40"
                        :class="{ 'opacity-50': error.resolved_at }"
                        @click="router.visit(`/errors/${error.id}`)"
                    >
                        <td
                            class="px-3 py-3 align-top font-mono text-[10px] tabular-nums text-muted-foreground"
                        >
                            {{ String(index + 1).padStart(3, '0') }}
                        </td>
                        <td class="px-3 py-3 align-top">
                            <div class="flex items-start gap-3">
                                <span
                                    class="mt-1.5 inline-block size-1.5 shrink-0"
                                    :class="
                                        error.resolved_at
                                            ? 'bg-phosphor shadow-[0_0_8px_var(--phosphor)]'
                                            : 'bg-crimson-glow shadow-[0_0_8px_var(--crimson-glow)]'
                                    "
                                    aria-hidden="true"
                                />
                                <div class="min-w-0 flex-1">
                                    <Link
                                        :href="`/errors/${error.id}`"
                                        class="block font-mono text-sm font-semibold uppercase tracking-wide text-foreground group-hover:text-phosphor"
                                    >
                                        {{ shortClass(error.exception_class) }}
                                    </Link>
                                    <div
                                        class="mt-0.5 truncate font-mono text-xs text-muted-foreground"
                                    >
                                        <span class="text-muted-foreground/60">&gt;</span>
                                        {{ error.message }}
                                    </div>
                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-xs text-muted-foreground"
                                    >
                                        <span
                                            v-if="error.resolved_at"
                                            class="inline-flex items-center gap-1.5 border border-phosphor/40 bg-phosphor/10 px-1.5 py-0.5 text-[10px] uppercase tracking-[0.18em] text-phosphor"
                                        >
                                            <span class="size-1 bg-phosphor" />
                                            RESOLVED
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1.5 border border-crimson-glow/40 bg-crimson-glow/10 px-1.5 py-0.5 text-[10px] uppercase tracking-[0.18em] text-crimson-glow"
                                        >
                                            <span class="size-1 bg-crimson-glow" />
                                            ERR-{{ error.id }}
                                        </span>
                                        <span
                                            v-if="error.url"
                                            class="truncate text-muted-foreground"
                                            :title="error.url"
                                        >
                                            {{ error.url }}
                                        </span>
                                        <span v-else>
                                            {{ shortFile(error.file) }}:{{ error.line }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-3 align-top text-xs uppercase tracking-[0.1em] text-muted-foreground"
                        >
                            {{ relativeTime(error.last_seen_at) }}
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-3 align-top text-xs uppercase tracking-[0.1em] text-muted-foreground"
                        >
                            {{ ageBetween(error.created_at, now) }}
                        </td>
                        <td
                            class="whitespace-nowrap px-3 py-3 align-top text-right font-mono text-sm font-semibold tabular-nums text-phosphor"
                        >
                            {{ formatCount(error.occurrences) }}
                        </td>
                    </tr>
                    <tr v-if="!errors.data.length">
                        <td
                            colspan="5"
                            class="px-4 py-12 text-center font-mono text-xs uppercase tracking-[0.2em] text-muted-foreground"
                        >
                            &gt;
                            <template v-if="status === 'unresolved'">
                                no open issues. all clear.
                            </template>
                            <template v-else-if="status === 'resolved'">
                                no resolved issues yet.
                            </template>
                            <template v-else>
                                no issues recorded. project nominal.
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <nav
            v-if="errors.total > 0"
            class="flex flex-wrap items-center justify-between gap-2"
        >
            <p class="text-xs text-muted-foreground">{{ range }}</p>
            <div class="flex gap-1">
                <template v-for="link in errors.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs transition hover:bg-muted dark:border-sidebar-border"
                        :class="{ 'bg-muted font-semibold': link.active }"
                    >
                        <span v-html="link.label" />
                    </Link>
                    <span
                        v-else
                        class="rounded-md border border-sidebar-border/70 px-3 py-1 text-xs text-muted-foreground dark:border-sidebar-border"
                        v-html="link.label"
                    />
                </template>
            </div>
        </nav>
    </div>
</template>


