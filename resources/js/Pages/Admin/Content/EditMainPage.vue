<template>
  <PageContent :title="mainPage.text" :back-url="route('mainPage.index')" :heading-icon="Icons.MAIN_PAGE">
    <UpsertModelLayout>
      <MainPageForm :main-page :tenant-options :type-options enable-delete
        @submit:form="(form) => form.patch(route('mainPage.update', mainPage.id), { preserveScroll: true })"
        @delete="() => router.delete(route('mainPage.destroy', mainPage.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import Icons from "@/Types/Icons/regular";
import MainPageForm from "@/Components/AdminForms/MainPageForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  mainPage: App.Entities.MainPage;
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();
</script>
