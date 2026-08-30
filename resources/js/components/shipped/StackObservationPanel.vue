<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { CheckCircle2, RefreshCw } from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Field, FieldDescription, FieldError } from '@/components/ui/field';
import { store } from '@/routes/projects/stack-observation';

const props = defineProps<{
    projectSlug: string;
    githubUrl: string | null;
    observedAt: string | null;
    observedSlugs: string[];
}>();

// Nothing to submit: the observation reads the repository the project
// already advertises. The field only carries the server's error key.
const form = useForm({ github: '' });

const hasGithubUrl = computed(() => Boolean(props.githubUrl));

const observedLabel = computed(() => {
    if (!props.observedAt) {
        return '';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(props.observedAt));
});

function observe(): void {
    form.post(store(props.projectSlug).url, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success(
                'Observed the repository and updated the observed technologies.',
            );
        },
    });
}
</script>

<template>
    <div class="max-w-2xl">
        <Alert
            v-if="observedSlugs.length"
            class="rounded-none border-foreground bg-secondary"
            data-test="stack-observation-state"
        >
            <CheckCircle2 class="size-4" />
            <AlertTitle
                >{{ observedSlugs.length }} technology(s) observed in the
                repository</AlertTitle
            >
            <AlertDescription>
                Last read
                {{ observedLabel ? ` on ${observedLabel}` : '' }}. Observed
                technologies are marked publicly as "Observed by Shipped"
                alongside your declarations. The repository is re-read when you
                ask, and daily while the project is publicly discoverable.
            </AlertDescription>
        </Alert>

        <Field class="mt-6">
            <FieldDescription>
                Shipped reads composer.json and package.json from the public
                repository on the project record and marks the technologies the
                code confirms. Nothing is written to the repository, and
                observation never changes verification or visibility.
            </FieldDescription>
            <FieldError v-if="form.errors.github">
                {{ form.errors.github }}
            </FieldError>
            <div class="flex flex-wrap gap-3">
                <Button
                    type="button"
                    :disabled="form.processing || !hasGithubUrl"
                    data-test="observe-stack"
                    @click="observe"
                >
                    <RefreshCw
                        class="size-4"
                        :class="{ 'animate-spin': form.processing }"
                    />
                    {{
                        observedSlugs.length
                            ? 'Observe again'
                            : 'Observe stack from GitHub'
                    }}
                </Button>
            </div>
            <FieldDescription v-if="!hasGithubUrl">
                Add a public GitHub repository URL to the project record, save,
                then observe the stack.
            </FieldDescription>
        </Field>
    </div>
</template>
