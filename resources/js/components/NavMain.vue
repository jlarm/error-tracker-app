<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';

defineProps<{
    items: NavItem[];
}>();

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel
            class="text-[10px] uppercase tracking-[0.22em] text-muted-foreground"
        >
            <span class="label-bracket">NAV</span>
        </SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    class="group/nav data-[active=true]:text-phosphor"
                >
                    <Link :href="item.href" class="font-mono">
                        <span
                            class="mr-1 text-muted-foreground group-data-[active=true]/nav:text-phosphor"
                        >
                            <span v-if="isCurrentUrl(item.href)">▾</span>
                            <span v-else>▸</span>
                        </span>
                        <component
                            :is="item.icon"
                            class="text-muted-foreground group-data-[active=true]/nav:text-phosphor"
                        />
                        <span class="uppercase tracking-[0.14em]">
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
