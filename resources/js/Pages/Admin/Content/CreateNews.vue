<template>
  <NewsForm
    :news
    :available-tags
    :assignable-tenants
    remember-key="CreateNews"
    :submit-url="route('news.store')"
    submit-method="post"
  />
</template>

<script setup lang="ts">
import NewsForm from '@/Components/AdminForms/NewsForm.vue';
import { newsTemplate as news } from '@/Types/formTemplates';

defineProps<{
  availableTags?: App.Entities.Tag[];
  assignableTenants: App.Entities.Tenant[];
}>();
</script>
