<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const clock = ref(new Date());

let timerId: ReturnType<typeof setInterval> | null = null;
onMounted(() => {
    timerId = setInterval(() => {
        clock.value = new Date();
    }, 1000);
});
onBeforeUnmount(() => {
    if (timerId) {
clearInterval(timerId);
}
});

const time = computed(() =>
    clock.value.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: false,
    }),
);

const date = computed(() =>
    clock.value
        .toISOString()
        .slice(0, 10), // YYYY-MM-DD
);

const path = computed(() => page.url || '/');

const appName = computed(() =>
    String(page.props.name ?? 'sentinel').toUpperCase(),
);
</script>

<template>
    <div
        class="flex items-center justify-between gap-4 border-b border-sidebar-border bg-background/60 px-4 py-1.5 font-mono text-[10px] uppercase tracking-[0.18em] text-muted-foreground backdrop-blur"
    >
        <div class="flex items-center gap-3">
            <span class="text-phosphor">●</span>
            <span class="font-semibold text-foreground">
                {{ appName }}
            </span>
            <span class="hidden sm:inline">//</span>
            <span class="hidden font-mono normal-case tracking-normal sm:inline">
                <span class="text-muted-foreground">path:</span>
                <span class="ml-1 text-foreground">{{ path }}</span>
            </span>
        </div>
        <div class="flex items-center gap-3">
            <Link
                href="/docs"
                class="inline-flex items-center border border-phosphor/40 bg-phosphor/5 px-2 py-0.5 text-phosphor transition hover:bg-phosphor/15 hover:shadow-[0_0_10px_color-mix(in_oklch,var(--phosphor)_25%,transparent)]"
            >
                DOCS
            </Link>
            <span class="hidden md:inline">
                <span class="text-muted-foreground">sys:</span>
                <span class="ml-1 text-foreground">online</span>
            </span>
            <span class="hidden sm:inline">{{ date }}</span>
            <span class="tabular-nums text-foreground cursor-blink">
                {{ time }}
            </span>
        </div>
    </div>
</template>
