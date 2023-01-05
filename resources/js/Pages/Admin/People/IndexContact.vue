<template>
  <IndexPageLayout
    title="Kontaktai"
    model-name="contacts"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="contacts"
  >
    <template #aside-header>
      <AsideHeader></AsideHeader>
    </template>
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import AsideHeader from "@/Components/AsideHeaders/AsideHeaderContacts.vue";
import IndexPageLayout from "@/Components/Layouts/IndexPageLayout.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  contacts: PaginatedModels<App.Models.Contact>;
}>();

const canUseRoutes = {
  create: true,
  show: false,
  edit: true,
  destroy: true,
};

const columns = [
  {
    title: "Vardas",
    key: "name",
  },
  {
    title: "El. paštas",
    key: "email",
    render(row: App.Models.Contact) {
      return (
        <a href={`mailto:${row.email}`} class="transition hover:text-vusa-red">
          {row.email}
        </a>
      );
    },
  },
  {
    title: "Telefonas",
    key: "phone",
    render(row: App.Models.Contact) {
      return (
        <a href={`mailto:${row.phone}`} class="transition hover:text-vusa-red">
          {row.phone}
        </a>
      );
    },
  },
];
</script>
