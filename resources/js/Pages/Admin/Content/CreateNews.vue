<template>
  <PageContent title="Nauja naujiena" :back-url="route('news.index')">
    <UpsertModelLayout>
      <template #card-header>
        Puslapio informacija
      </template>
      <NewsForm :news :available-tags remember-key="CreateNews" @submit:form="(form) => form.post(route('news.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import NewsForm from "@/Components/AdminForms/NewsForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { newsTemplate as news } from "@/Types/formTemplates";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  availableTags?: App.Entities.Tag[];
}>();
</script>
