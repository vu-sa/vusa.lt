<template>
  <PageContent title="Naujas puslapis" :back-url="route('pages.index')">
    <UpsertModelLayout>
      <template #card-header>
        Puslapio informacija
      </template>
      <PageForm
        remember-key="CreatePage"
        :page
        :categories
        :assignable-tenants
        :submit-url="route('pages.store')"
        submit-method="post"
        @submit:form="(form) => form.post(route('pages.store'))"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import PageForm from '@/Components/AdminForms/PageForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

defineProps<{
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();

const page = {
  title: '',
  category_id: null,
  permalink: '',
  lang: 'lt',
  other_lang_page: null,
  tenant_id: null,
  is_active: true,
  content: {
    parts: [
      {
        type: 'tiptap',
        json_content: {},
      },
    ],
  },
};
</script>
