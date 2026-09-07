<template>
  <AdminContentPage title="Redaguoti pagrindinį puslapį">
    <template #aside-header>
      <ActivityLogSheet subject-type="tenant" :subject-id="tenant.id" />
    </template>
    <UpsertModelLayout>
      <div class="mb-6 space-y-2">
        <span class="text-sm font-medium">{{ $t('Kalba') }}</span>
        <ToggleGroup :model-value="locale" type="single" class="justify-start" @update:model-value="switchLocale">
          <ToggleGroupItem value="lt" class="gap-2">
            🇱🇹 Lietuvių
          </ToggleGroupItem>
          <ToggleGroupItem value="en" class="gap-2">
            🇬🇧 English
          </ToggleGroupItem>
        </ToggleGroup>
      </div>
      <AdminForm :model="form" label-placement="top" @submit:form="handleFormSubmit">
        <RichContentFormElement v-model="form.parts" :tenant-id="tenant.id" @save="handleFormSubmit" />
      </AdminForm>
    </UpsertModelLayout>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { router, useForm } from '@inertiajs/vue3';

import ActivityLogSheet from '@/Features/Admin/ActivityLogViewer/ActivityLogSheet.vue';
import RichContentFormElement from '@/Components/RichContent/RichContentFormElement.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { ToggleGroup, ToggleGroupItem } from '@/Components/ui/toggle-group';

const { tenant, content, locale } = defineProps<{
  tenant: App.Entities.Tenant;
  content: App.Entities.Content | null;
  locale: 'lt' | 'en';
}>();

const form = useForm({
  locale,
  parts: content?.parts ?? [],
});

function switchLocale(nextLocale: 'lt' | 'en' | undefined): void {
  if (nextLocale === undefined || nextLocale === locale) {
    return;
  }

  form.defaults();
  router.get(route('tenants.editMainPage', tenant.id), { locale: nextLocale }, { preserveScroll: true });
}

function handleFormSubmit() {
  form.defaults();
  form.post(route('tenants.updateMainPage', tenant.id), {
    preserveScroll: true,
    forceFormData: true,
  });
}
</script>
