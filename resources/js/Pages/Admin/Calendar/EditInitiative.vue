<template>
  <PageContent title="Nauja iniciatyva">
    <UpsertModelLayout :errors="$page.props.errors" :model="initiative">
      <InitiativeForm
        model-route="initiatives.update"
        delete-model-route="initiatives.destroy"
        :initiative="initiative"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import InitiativeForm from "@/Components/AdminForms/InitiativeForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  initiative: App.Entities.Initiative;
}>();
</script>
