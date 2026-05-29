<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard, login } from '@/routes';

const page = usePage();
const targetHref = computed(() =>
    page.props.auth?.user ? dashboard() : login(),
);
</script>

<template>
    <Head title="" />

    <div
        class="relative flex min-h-screen items-center justify-center bg-background p-6"
    >
        <div
            class="pointer-events-none absolute left-1/2 top-1/2 -z-10 size-[420px] -translate-x-1/2 -translate-y-1/2 rounded-full opacity-25 blur-3xl"
            style="background: radial-gradient(closest-side, var(--phosphor), transparent 70%);"
            aria-hidden="true"
        />

        <Link
            :href="targetHref"
            class="boot-in flex aspect-square size-16 items-center justify-center border border-sidebar-border bg-card/70 text-phosphor transition hover:border-phosphor/50 hover:shadow-[0_0_24px_color-mix(in_oklch,var(--phosphor)_30%,transparent)]"
            aria-label="Enter"
        >
            <svg
                viewBox="0 0 16 16"
                fill="none"
                stroke="currentColor"
                stroke-width="1.25"
                stroke-linecap="square"
                class="size-8"
                aria-hidden="true"
            >
                <path d="M4.5 2.5 L2 2.5 L2 13.5 L4.5 13.5" />
                <path d="M11.5 2.5 L14 2.5 L14 13.5 L11.5 13.5" />
                <rect
                    x="6.5"
                    y="6.5"
                    width="3"
                    height="3"
                    fill="currentColor"
                    stroke="none"
                />
            </svg>
        </Link>
    </div>
</template>
