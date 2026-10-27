<template>
  <form ref="formElement" class="space-y-6" novalidate @submit.prevent="submit">
    <div v-if="Object.keys(unmappedErrors).length" ref="errorSummary" data-testid="registration-errors" tabindex="-1" role="alert" class="space-y-2 border border-destructive p-4">
      <p v-for="(message, key) in unmappedErrors" :key class="text-sm text-destructive">
        {{ message }}
      </p>
    </div>
    <template v-for="field in definition.form_fields" :key="field.id">
      <RegistrationField v-if="!prefilledValues?.[field.id]?.hidden" v-model="form.responses[String(field.id)].value" :field :locale
        :error="fieldError(field.id)" @validate="validateField(field.id)" />
    </template>
    <Button type="submit" variant="brand" size="lg" :disabled="form.processing">
      {{ $t('Pateikti') }}
    </Button>
  </form>
  <ConfirmDialog :open="pendingVisit !== null" :title="$t('Neišsaugoti pakeitimai')"
    :description="$t('Tai, ką įvedei formoje, nebus išsaugota.')" :cancel-label="$t('Likti puslapyje')"
    :confirm-label="$t('Išeiti iš puslapio')" @update:open="handleDiscardDialogOpen" @confirm="continueNavigation" />
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, shallowRef } from 'vue';
import { router, useForm, usePage } from '@inertiajs/vue3';
import type { PendingVisit } from '@inertiajs/core';

import RegistrationField from './RegistrationField.vue';
import { initialValue, type PrefilledValue, type RegistrationDefinition, type RegistrationValue } from './types';

import { Button } from '@/Components/ui/button';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import type { PageProps } from '@/Types';

const props = defineProps<{
  form: RegistrationDefinition;
  submitUrl: string;
  prefilledValues?: Record<string, PrefilledValue>;
}>();
const definition = computed(() => props.form);
const currentPage = usePage<PageProps>();
const locale = computed(() => currentPage.props.app.locale);
const values: Record<string, { value: RegistrationValue }> = Object.fromEntries(
  props.form.form_fields.map(field => [
    String(field.id),
    { value: initialValue(field, locale.value, props.prefilledValues?.[field.id]) },
  ]),
);
const form = useForm({ responses: values })
  .transform(({ responses }) => ({ data: responses }))
  .withPrecognition('post', props.submitUrl).setValidationTimeout(500);
const formElement = ref<HTMLFormElement | null>(null);
const errorSummary = ref<HTMLElement | null>(null);
const unmappedErrors = computed(() => {
  const visibleKeys = new Set(props.form.form_fields
    .filter(field => !props.prefilledValues?.[field.id]?.hidden)
    .flatMap(field => [`data.${field.id}`, `data.${field.id}.value`]));
  return Object.fromEntries(Object.entries(form.errors).filter(([key]) => !visibleKeys.has(key)));
});
const pendingVisit = shallowRef<PendingVisit | null>(null);
let submitting = false;
let allowNextVisit = false;

function fieldError(id: string | number): string | undefined {
  const { errors }: { errors: Record<string, string | undefined> } = form;
  return errors[`data.${id}.value`] ?? errors[`data.${id}`];
}

async function validateField(id: string | number) {
  await nextTick();
  form.validator().validate(`data.${id}.value`, form.responses[String(id)].value);
}

async function focusErrors() {
  await nextTick();
  const target = errorSummary.value ?? formElement.value?.querySelector<HTMLElement>('[aria-invalid="true"]');
  target?.focus();
  target?.scrollIntoView({ block: 'nearest' });
}

function submit() {
  if (form.processing) return;
  submitting = true;
  form.submit({
    onSuccess: (page) => {
      const { flash } = page.props as PageProps;
      if (flash?.success && !flash.error) form.defaults();
      // Inertia otherwise marks redirects carrying application errors as saved.
      else form.defaults({});
    },
    onError: focusErrors,
    onFinish: () => { submitting = false; },
  });
}

const removeBeforeListener = router.on('before', (event) => {
  if (event.detail.visit.prefetch || submitting || !form.isDirty) return;
  if (allowNextVisit) {
    allowNextVisit = false;
    return;
  }
  event.preventDefault();
  pendingVisit.value = event.detail.visit;
});

function handleDiscardDialogOpen(open: boolean) {
  if (!open) pendingVisit.value = null;
}

function continueNavigation() {
  const visit = pendingVisit.value;
  pendingVisit.value = null;
  if (!visit) return;
  const { url, ...options } = visit;
  for (const key of ['id', 'completed', 'cancelled', 'interrupted']) Reflect.deleteProperty(options, key);
  allowNextVisit = true;
  router.visit(url, options);
}

function beforeUnload(event: BeforeUnloadEvent) {
  if (!form.isDirty || submitting) return;
  event.preventDefault();
  event.returnValue = '';
}

onMounted(() => window.addEventListener('beforeunload', beforeUnload));
onBeforeUnmount(() => {
  removeBeforeListener();
  window.removeEventListener('beforeunload', beforeUnload);
});
</script>
