<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ageBetween, relativeTime } from '@/lib/relativeTime';
import { dashboard } from '@/routes';

type RecentError = {
    id: number;
    project_id: number;
    project_name: string;
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

defineProps<{
    recentErrors: RecentError[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
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
</script>

<template>
    <Head title="Dashboard" />

    <div class="boot-in flex h-full flex-1 flex-col gap-6 p-6">
        <section>
            <div class="mb-4 flex items-baseline justify-between">
                <h2
                    class="font-mono text-xs uppercase tracking-[0.32em] text-phosphor"
                >
                    <span class="label-bracket">RECENT_ISSUES</span>
                </h2>
                <span class="text-[10px] uppercase tracking-[0.22em] text-muted-foreground">
                    stream &middot; all projects
                </span>
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
                            <th class="px-3 py-2.5 text-left font-medium">
                                Last Seen
                            </th>
                            <th class="px-3 py-2.5 text-left font-medium">Age</th>
                            <th class="px-3 py-2.5 text-right font-medium">Events</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="(error, index) in recentErrors"
                            :key="error.id"
                            class="group border-b border-sidebar-border/60 transition last:border-b-0 hover:bg-muted/40"
                            :class="{ 'opacity-50': error.resolved_at }"
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
                                            <Link
                                                :href="`/projects/${error.project_id}`"
                                                class="inline-flex items-center gap-1.5 border border-cyan-glow/40 bg-cyan-glow/5 px-1.5 py-0.5 text-[10px] uppercase tracking-[0.18em] text-cyan-glow hover:bg-cyan-glow/15"
                                                @click.stop
                                            >
                                                <span class="size-1 bg-cyan-glow" />
                                                {{ error.project_name }}
                                            </Link>
                                            <span
                                                v-if="error.url"
                                                class="truncate text-muted-foreground"
                                                :title="error.url"
                                            >
                                                {{ error.url }}
                                            </span>
                                            <span v-else>
                                                {{ shortFile(error.file) }}:{{
                                                    error.line
                                                }}
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
                        <tr v-if="!recentErrors.length">
                            <td
                                colspan="5"
                                class="px-4 py-12 text-center font-mono text-xs uppercase tracking-[0.2em] text-muted-foreground"
                            >
                                &gt; no errors reported. system nominal.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
