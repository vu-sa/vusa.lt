<template>
  <PageContent title="Naujas įvykis">
    <UpsertModelLayout :errors="$page.props.errors" :model="calendar">
      <CalendarForm
        model-route="calendar.store"
        :calendar="calendar"
        :categories="categories"
        :assignable-tenants
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import CalendarForm from "@/Components/AdminForms/CalendarForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: App.Entities.Category[];
  assignableTenants: App.Entities.Tenant[];
}>();

const calendar = {
  title: "",
  date: null,
  end_date: null,
  description: "",
  location: "",
  category: null,
  url: "",
  extra_attributes: {
    en: {},
  },
};
</script>
