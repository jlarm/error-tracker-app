<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { toast } from 'vue-sonner';
import ApiTokenController from '@/actions/App/Http/Controllers/Settings/ApiTokenController';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { relativeTime } from '@/lib/relativeTime';
import { edit } from '@/routes/security';

type ApiToken = {
    id: number;
    name: string;
    last_used_at: string | null;
    created_at: string;
};

type Props = {
    passwordRules: string;
    apiTokens: ApiToken[];
    newApiToken: string | null;
};

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});

const revealedToken = ref<string | null>(props.newApiToken);

watch(
    () => props.newApiToken,
    (token) => {
        if (token) {
            revealedToken.value = token;
        }
    },
);

const copyToken = async () => {
    if (!revealedToken.value) {
        return;
    }

    try {
        await navigator.clipboard.writeText(revealedToken.value);
        toast.success('Token copied');
    } catch {
        toast.error('Copy failed');
    }
};

const dismissToken = () => {
    revealedToken.value = null;
};

const revokeToken = (token: ApiToken) => {
    if (!confirm(`Revoke "${token.name}"? This cannot be undone.`)) {
        return;
    }

    router.delete(ApiTokenController.destroy.url({ tokenId: token.id }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-10">
        <div class="space-y-6">
            <Heading
                variant="small"
                title="Update password"
                description="Ensure your account is using a long, random password to stay secure"
            />

            <Form
                v-bind="SecurityController.update.form()"
                :options="{
                    preserveScroll: true,
                }"
                reset-on-success
                :reset-on-error="[
                    'password',
                    'password_confirmation',
                    'current_password',
                ]"
                class="space-y-6"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="current_password">Current password</Label>
                    <PasswordInput
                        id="current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        placeholder="Current password"
                    />
                    <InputError :message="errors.current_password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">New password</Label>
                    <PasswordInput
                        id="password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="New password"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">Confirm password</Label>
                    <PasswordInput
                        id="password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        placeholder="Confirm password"
                        :passwordrules="props.passwordRules"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="flex items-center gap-4">
                    <Button
                        :disabled="processing"
                        data-test="update-password-button"
                    >
                        Save
                    </Button>
                </div>
            </Form>
        </div>

        <div class="space-y-6">
            <Heading
                variant="small"
                title="API tokens"
                description="Create personal access tokens for the menu bar app and other clients that need to read your errors."
            />

            <div
                v-if="revealedToken"
                class="border border-phosphor/50 bg-phosphor/5 p-4 text-sm shadow-[0_0_12px_color-mix(in_oklch,var(--phosphor)_18%,transparent)]"
            >
                <p class="font-mono text-[10px] uppercase tracking-[0.22em] text-phosphor">
                    <span class="label-bracket">NEW_TOKEN</span>
                </p>
                <p class="mt-2 text-xs text-muted-foreground">
                    Copy this token now &mdash; it won't be shown again.
                </p>
                <code
                    class="mt-2 block break-all border border-sidebar-border bg-card/60 p-2 font-mono text-xs text-phosphor"
                >
                    {{ revealedToken }}
                </code>
                <div class="mt-3 flex gap-2">
                    <Button type="button" size="sm" @click="copyToken">
                        Copy token
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="dismissToken"
                    >
                        Dismiss
                    </Button>
                </div>
            </div>

            <Form
                v-bind="ApiTokenController.store.form()"
                :options="{ preserveScroll: true }"
                reset-on-success
                class="flex flex-wrap items-end gap-3"
                v-slot="{ errors, processing }"
            >
                <div class="grid flex-1 gap-2 min-w-[200px]">
                    <Label for="api_token_name">Token name</Label>
                    <Input
                        id="api_token_name"
                        name="name"
                        placeholder="MacBook menu bar"
                        autocomplete="off"
                    />
                    <InputError :message="errors.name" />
                </div>
                <Button :disabled="processing">Create token</Button>
            </Form>

            <div class="overflow-hidden border border-sidebar-border bg-card/50">
                <table class="w-full text-sm">
                    <thead
                        class="border-b border-sidebar-border bg-muted/50 text-[10px] uppercase tracking-[0.22em] text-muted-foreground"
                    >
                        <tr>
                            <th class="px-3 py-2.5 text-left font-medium">Name</th>
                            <th class="px-3 py-2.5 text-left font-medium">Created</th>
                            <th class="px-3 py-2.5 text-left font-medium">Last used</th>
                            <th class="px-3 py-2.5 text-right font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="token in props.apiTokens"
                            :key="token.id"
                            class="border-b border-sidebar-border/60 last:border-b-0"
                        >
                            <td class="px-3 py-3 font-mono text-xs">{{ token.name }}</td>
                            <td
                                class="px-3 py-3 text-xs uppercase tracking-[0.1em] text-muted-foreground"
                            >
                                {{ relativeTime(token.created_at) }}
                            </td>
                            <td
                                class="px-3 py-3 text-xs uppercase tracking-[0.1em] text-muted-foreground"
                            >
                                <template v-if="token.last_used_at">
                                    {{ relativeTime(token.last_used_at) }}
                                </template>
                                <template v-else>never</template>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="ghost"
                                    class="text-crimson-glow hover:bg-crimson-glow/10 hover:text-crimson-glow"
                                    @click="revokeToken(token)"
                                >
                                    Revoke
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!props.apiTokens.length">
                            <td
                                colspan="4"
                                class="px-4 py-8 text-center font-mono text-xs uppercase tracking-[0.2em] text-muted-foreground"
                            >
                                &gt; no tokens yet
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
