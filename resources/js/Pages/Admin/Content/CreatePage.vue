<template>
  <PageForm
    remember-key="CreatePage"
    :page
    :available-tags
    :assignable-tenants
    :submit-url="route('pages.store')"
    submit-method="post"
    @submit:form="submitForm"
  />
</template>

<script setup lang="ts">
import type { InertiaForm } from '@inertiajs/vue3';

import PageForm from '@/Components/AdminForms/PageForm.vue';

defineProps<{
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
}>();

const page = {
  title: '',
  parent_id: null,
  permalink: '',
  lang: 'lt',
  other_lang_page: null,
  tenant_id: null,
  is_active: true,
  tags: [],
  content: {
    parts: [
      {
        type: 'tiptap',
        json_content: {},
      },
    ],
  },
};

function submitForm(form: unknown): void {
  (form as InertiaForm<Record<string, unknown>>).post(route('pages.store'));
}
</script>
