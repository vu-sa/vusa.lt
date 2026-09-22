<template>
  <FormPage
    :title="$t('Redaguoti pagrindinį puslapį')"
    :head-title="$t('Redaguoti pagrindinį puslapį')"
    :lead="tenant.shortname"
    :back-href="route('pages.index')"
    :back-label="$t('Puslapiai')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    mode="edit"
    max-width="4xl"
    :locale
    :available-locales="['lt', 'en']"
    @update:locale="switchLocale"
    @submit="handleFormSubmit"
  >
    <template #header-actions>
      <ActivityLogSheet subject-type="tenant" :subject-id="String(tenant.id)" />
    </template>

    <FormSection
      :title="$t('Turinys')"
      :description="$t('Pagrindinio puslapio informacija')"
    >
      <RichContentFormElement
        v-model="form.parts"
        :tenant-id="tenant.id"
        @save="handleFormSubmit"
      />
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import RichContentFormElement from '@/Components/RichContent/RichContentFormElement.vue';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { PageIcon } from '@/Components/icons';

const props = defineProps<{
  tenant: App.Entities.Tenant;
  content: App.Entities.Content | null;
  locale: 'lt' | 'en';
}>();

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminForm('Puslapiai', 'pages.index', `${$t('Pagrindinis puslapis')} (${props.tenant.shortname})`, PageIcon),
);

const form = useForm({
  locale: props.locale,
  parts: props.content?.parts ?? [],
});

function switchLocale(nextLocale: string): void {
  if (!nextLocale || nextLocale === props.locale) {
    return;
  }

  form.defaults();
  router.get(route('tenants.editMainPage', props.tenant.id), { locale: nextLocale }, { preserveScroll: true });
}

function handleFormSubmit(): void {
  form.defaults();
  form.post(route('tenants.updateMainPage', props.tenant.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
