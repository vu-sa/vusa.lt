<template>
  <article class="gap-y-4 pt-8 last:pb-2">
    <h1>
      {{ $t(form.name) }}
    </h1>
    <div class="typography">
      <div v-html="form.description" />
    </div>
    <!-- Show pre-filled institution name if provided -->
    <div v-if="prefilledInstitutionName" class="mt-4 rounded-lg bg-zinc-100 p-4 dark:bg-zinc-800">
      <p class="text-sm text-zinc-600 dark:text-zinc-400">
        {{ $t('Registruojatės į') }}: <strong class="text-zinc-900 dark:text-zinc-100">{{ prefilledInstitutionName }}</strong>
      </p>
    </div>
    <div class="mt-8 max-w-prose text-base">
      <RegistrationForm :form :prefilled-values="prefilledValues" @submit="handleSubmit" />
    </div>
  </article>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { router, usePage } from "@inertiajs/vue3";
import RegistrationForm from "../../Features/Registrations/RegistrationForm.vue";

const $page = usePage();

const { form } = defineProps<{
  form: Record<string, any>;
}>();

// Get institution ID from query param
const institutionId = computed(() => {
  const url = new URL(window.location.href);
  return url.searchParams.get('institution');
});

// Find the institution field and get prefilled values
const prefilledValues = computed(() => {
  if (!institutionId.value) return {};
  
  // Find the institution field in the form
  const institutionField = form.form_fields?.find((field: Record<string, any>) => 
    field.use_model_options && field.options_model === 'App\\Models\\Institution'
  );
  
  if (!institutionField) return {};
  
  return {
    [institutionField.id]: {
      value: institutionId.value,
      hidden: true, // Mark this field as hidden
    }
  };
});

// Get the institution name for display
const prefilledInstitutionName = computed(() => {
  if (!institutionId.value) return null;
  
  const institutionField = form.form_fields?.find((field: Record<string, any>) => 
    field.use_model_options && field.options_model === 'App\\Models\\Institution'
  );
  
  if (!institutionField?.options) return null;
  
  const institution = institutionField.options.find((opt: { value: string }) => 
    String(opt.value) === String(institutionId.value)
  );
  
  return institution?.label ?? null;
});

const handleSubmit = (data: Record<string, any>) => {
  router.post(route('registrations.store', { form: form.id }), { data });
};
</script>
