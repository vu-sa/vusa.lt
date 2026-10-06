<template>
  <SheetForm
    v-model:open="open"
    :title="$t('goals.problems.link')"
    :description="$t('goals.problems.link_description')"
    :processing="form.processing"
    :disabled="!form.problem_id"
    @cancel="form.reset()"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="goal-problem">{{ $t('goals.problems.pick') }}</Label>
      <div v-if="options === undefined" class="h-11 animate-pulse bg-muted" />
      <SingleSelect
        v-else
        id="goal-problem"
        v-model="selected"
        :options="labelled"
        label-field="label"
        value-field="id"
        :placeholder="$t('goals.problems.pick')"
        variant="surface"
      />
      <p v-if="form.errors.problem_id" class="text-sm text-status-danger">
        {{ form.errors.problem_id }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, watch } from 'vue';

import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Label } from '@/Components/ui/label';
import SingleSelect from '@/Components/ui/single-select/SingleSelect.vue';

const props = defineProps<{
  open: boolean;
  goalId: string;
  /** Loaded on first open (`Inertia::optional`), so undefined until then. */
  options?: { id: string; title: string; tenant: string | null }[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const form = useForm<{ problem_id: string | null }>({ problem_id: null });

watch(() => props.open, (isOpen) => {
  if (isOpen) {
    router.reload({ only: ['problemOptions'] });
  }
});

const labelled = computed(() => (props.options ?? []).map(option => ({
  ...option,
  label: option.tenant ? `${option.title} · ${option.tenant}` : option.title,
})));

const selected = computed({
  get: () => labelled.value.find(option => option.id === form.problem_id) ?? null,
  set: (option: { id: string } | null) => {
    form.problem_id = option?.id ?? null;
  },
});

function submit(): void {
  form.post(route('goals.problems.link', props.goalId), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset();
      open.value = false;
    },
  });
}
</script>
