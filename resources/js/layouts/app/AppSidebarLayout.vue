<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import SystemStatusBar from '@/components/SystemStatusBar.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <SystemStatusBar />
            <slot />
        </AppContent>
        <Toaster />
    </AppShell>
</template>
