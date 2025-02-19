<template>
  <PageContent title="Naujas pradinio puslapio mygtukas" :back-url="route('quickLinks.index')">
    <UpsertModelLayout>
      <QuickLinkForm remember-key="CreateQuickLink" :tenant-options :type-options :quick-link
        @submit:form="(form) => form.post(route('quickLinks.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import QuickLinkForm from "@/Components/AdminForms/QuickLinkForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  tenantOptions: Record<string, any>[];
  typeOptions: Record<string, any>[];
}>();

const quickLink = {
  text: "",
  link: "",
  lang: "lt",
  icon: "",
  is_important: false,
};
</script>
