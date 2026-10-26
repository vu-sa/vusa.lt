<template>
  <div class="registration-page">
    <PageTitleBand
      :eyebrow="$t('Registracija')"
      :title="$t(form.name)"
    >
      <template #breadcrumbs>
        <PublicBreadcrumbs variant="inline" />
      </template>
    </PageTitleBand>

    <section class="mx-auto max-w-7xl px-5 py-8 sm:px-6 sm:py-12 lg:px-8">
      <div class="max-w-prose">
        <!-- Rich Description -->
        <div v-if="form.description" class="typography mb-8" v-html="form.description" />

        <!-- Show pre-filled institution name if provided -->
        <div v-if="prefilledInstitutionName" class="mb-8 border border-border bg-secondary/40 p-4">
          <p class="text-sm text-muted-foreground">
            {{ $t('Registruojatės į') }}: <strong class="text-foreground">{{ prefilledInstitutionName }}</strong>
          </p>
        </div>

        <!-- Registration Form -->
        <RegistrationForm :key="form.id" :form :prefilled-values :submit-url="route('registrations.store', { form: form.id })" />
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import PageTitleBand from '@/Components/Public/Base/PageTitleBand.vue';
import PublicBreadcrumbs from '@/Components/Public/PublicBreadcrumbs.vue';
import RegistrationForm from '@/Features/Registrations/RegistrationForm.vue';
import type { RegistrationDefinition, RegistrationFieldDefinition } from '@/Features/Registrations/types';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const { form } = defineProps<{
  form: RegistrationDefinition;
}>();

// Set breadcrumbs for registration page
usePageBreadcrumbs(() => {
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(
      $t(form.name) || 'Registracija',
    ),
  ]);
}, { placement: 'band' });

// Get institution ID from query param
const institutionId = computed(() => {
  const url = new URL(window.location.href);
  return url.searchParams.get('institution');
});

// Find the institution field and get prefilled values
const prefilledValues = computed(() => {
  if (!institutionId.value) return {};

  // Find the institution field in the form
  const institutionField = form.form_fields?.find((field: RegistrationFieldDefinition) =>
    field.option_source === 'institution',
  );

  if (!institutionField) return {};

  return {
    [institutionField.id]: {
      value: institutionId.value,
      hidden: true, // Mark this field as hidden
    },
  };
});

// Get the institution name for display
const prefilledInstitutionName = computed(() => {
  if (!institutionId.value) return null;

  const institutionField = form.form_fields?.find((field: RegistrationFieldDefinition) =>
    field.option_source === 'institution',
  );

  if (!institutionField?.options) return null;

  const institution = institutionField.options.find(opt =>
    typeof opt === 'object' && String(opt.value) === String(institutionId.value),
  );

  return institution && typeof institution === 'object' ? institution.label : null;
});
</script>
