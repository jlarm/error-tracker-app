<script setup lang="ts">
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Check,
    ChevronsUpDown,
    FolderKanban,
    LayoutGrid,
    Plus,
} from 'lucide-vue-next';
import { computed, onUnmounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { Spinner } from '@/components/ui/spinner';
import { dashboard } from '@/routes';

type ProjectOption = { id: number; name: string };

const page = usePage();
const { isMobile, state } = useSidebar();

const projects = computed<ProjectOption[]>(
    () => (page.props.allProjects as ProjectOption[] | undefined) ?? [],
);

const currentProjectId = computed<number | null>(() => {
    const projectMatch = page.url.match(/^\/projects\/(\d+)/);

    if (projectMatch) {
        return Number(projectMatch[1]);
    }

    const errorLog = page.props.errorLog as
        | { project?: { id: number } }
        | undefined;

    if (errorLog?.project?.id) {
        return errorLog.project.id;
    }

    return null;
});

const activeProject = computed<ProjectOption | null>(() => {
    if (!currentProjectId.value) {
        return null;
    }

    return projects.value.find((p) => p.id === currentProjectId.value) ?? null;
});

const filter = ref('');
const filteredProjects = computed(() => {
    const q = filter.value.trim().toLowerCase();

    if (!q) {
        return projects.value;
    }

    return projects.value.filter((p) => p.name.toLowerCase().includes(q));
});

const selectProject = (project: ProjectOption) => {
    router.visit(`/projects/${project.id}`);
};

const createDialogOpen = ref(false);

const createForm = useForm({ name: '' });

const removeNavigateListener = router.on('navigate', () => {
    if (createDialogOpen.value) {
        createDialogOpen.value = false;
        createForm.reset();
        createForm.clearErrors();
    }
});

onUnmounted(removeNavigateListener);

const openCreateDialog = () => {
    createForm.reset();
    createForm.clearErrors();
    createDialogOpen.value = true;
};

const submitCreate = () => {
    createForm.post('/projects');
};
</script>

<template>
    <div class="contents">
        <SidebarMenu>
            <SidebarMenuItem>
            <DropdownMenu>
                <DropdownMenuTrigger as-child>
                    <SidebarMenuButton
                        size="lg"
                        class="border border-sidebar-border bg-sidebar-accent/40 font-mono data-[state=open]:bg-sidebar-accent"
                    >
                        <div
                            class="flex aspect-square size-8 items-center justify-center border border-sidebar-border bg-background text-phosphor"
                        >
                            <svg
                                viewBox="0 0 16 16"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="1.25"
                                stroke-linecap="square"
                                class="size-4"
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
                        </div>
                        <div class="grid flex-1 text-left leading-tight">
                            <span
                                class="text-[10px] uppercase tracking-[0.2em] text-muted-foreground"
                            >
                                <span class="label-bracket">PROJECT</span>
                            </span>
                            <span class="truncate text-sm font-semibold uppercase tracking-wide">
                                {{ activeProject?.name ?? 'All' }}
                            </span>
                        </div>
                        <ChevronsUpDown class="ml-auto size-3.5 text-muted-foreground" />
                    </SidebarMenuButton>
                </DropdownMenuTrigger>

                <DropdownMenuContent
                    class="w-(--reka-dropdown-menu-trigger-width) min-w-64 rounded-lg"
                    :side="
                        isMobile
                            ? 'bottom'
                            : state === 'collapsed'
                              ? 'right'
                              : 'bottom'
                    "
                    align="start"
                    :side-offset="4"
                >
                    <DropdownMenuLabel class="text-xs text-muted-foreground">
                        Switch project
                    </DropdownMenuLabel>

                    <div v-if="projects.length > 5" class="px-2 pb-2">
                        <input
                            v-model="filter"
                            type="text"
                            placeholder="Search projects..."
                            class="w-full rounded-md border border-input bg-background px-2 py-1 text-sm outline-none focus:ring-2 focus:ring-ring"
                            @keydown.stop
                        />
                    </div>

                    <DropdownMenuItem as-child>
                        <Link
                            :href="dashboard()"
                            class="flex w-full items-center gap-2"
                        >
                            <LayoutGrid class="size-4 text-muted-foreground" />
                            <span>Dashboard</span>
                        </Link>
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <template v-if="filteredProjects.length">
                        <DropdownMenuItem
                            v-for="project in filteredProjects"
                            :key="project.id"
                            class="flex items-center gap-2"
                            @select="selectProject(project)"
                        >
                            <FolderKanban
                                class="size-4 text-muted-foreground"
                            />
                            <span class="flex-1 truncate">
                                {{ project.name }}
                            </span>
                            <Check
                                v-if="project.id === currentProjectId"
                                class="size-4 text-primary"
                            />
                        </DropdownMenuItem>
                    </template>
                    <DropdownMenuItem
                        v-else
                        disabled
                        class="text-xs text-muted-foreground"
                    >
                        No matching projects
                    </DropdownMenuItem>

                    <DropdownMenuSeparator />

                    <DropdownMenuItem
                        class="flex items-center gap-2 text-muted-foreground"
                        @select="openCreateDialog"
                    >
                        <Plus class="size-4" />
                        <span>Add a project</span>
                    </DropdownMenuItem>

                    <DropdownMenuItem as-child>
                        <Link
                            href="/docs"
                            class="flex w-full items-center gap-2 text-muted-foreground"
                        >
                            <BookOpen class="size-4" />
                            <span>Integration docs</span>
                        </Link>
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </SidebarMenuItem>
        </SidebarMenu>

        <Dialog v-model:open="createDialogOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add a new project</DialogTitle>
                <DialogDescription>
                    Give your site a recognizable name. We'll generate an API
                    key you can use to send errors here.
                </DialogDescription>
            </DialogHeader>

            <form
                class="flex flex-col gap-4"
                @submit.prevent="submitCreate"
            >
                <div class="grid gap-2">
                    <Label for="dialog-project-name">Project name</Label>
                    <Input
                        id="dialog-project-name"
                        v-model="createForm.name"
                        placeholder="e.g. My Marketing Site"
                        required
                        autocomplete="off"
                        autofocus
                    />
                    <InputError :message="createForm.errors.name" />
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="ghost"
                        @click="createDialogOpen = false"
                        :disabled="createForm.processing"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" :disabled="createForm.processing">
                        <Spinner v-if="createForm.processing" />
                        Create project
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
        </Dialog>
    </div>
</template>
