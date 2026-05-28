<script setup lang="ts">
import { ref } from 'vue';
import { toast } from 'vue-sonner';

const props = withDefaults(
    defineProps<{
        code: string;
        language?: string;
        filename?: string;
    }>(),
    {
        language: '',
        filename: '',
    },
);

const copied = ref(false);

const copy = async () => {
    try {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(props.code);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = props.code;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            textarea.remove();
        }
        copied.value = true;
        toast.success('Copied');
        setTimeout(() => (copied.value = false), 1500);
    } catch (error) {
        console.error(error);
        toast.error('Copy failed');
    }
};
</script>

<template>
    <div class="border border-sidebar-border bg-card/40">
        <div
            v-if="filename || language"
            class="flex items-center justify-between border-b border-sidebar-border bg-muted/40 px-3 py-1.5 font-mono text-[10px] uppercase tracking-[0.22em] text-muted-foreground"
        >
            <div class="flex items-center gap-2">
                <span v-if="language" class="text-phosphor">
                    <span class="label-bracket">{{ language }}</span>
                </span>
                <span v-if="filename" class="text-foreground">
                    {{ filename }}
                </span>
            </div>
            <button
                type="button"
                @click="copy"
                class="transition hover:text-phosphor"
                :title="copied ? 'Copied!' : 'Copy code'"
            >
                {{ copied ? 'COPIED' : 'COPY' }}
            </button>
        </div>
        <pre
            class="overflow-auto bg-zinc-950 p-4 font-mono text-xs leading-relaxed text-zinc-100"
        ><code>{{ code }}</code></pre>
    </div>
</template>
