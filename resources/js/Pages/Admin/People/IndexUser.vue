<template>
  <IndexPageLayout
    title="Nariai"
    model-name="users"
    :can-use-routes="canUseRoutes"
    :columns="columns"
    :paginated-models="users"
  >
  </IndexPageLayout>
</template>

<script setup lang="tsx">
import IndexPageLayout from "@/Components/Layouts/IndexModel/IndexPageLayout.vue";

defineProps<{
  users: PaginatedModels<App.Entities.User>;
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
    render(row: App.Entities.User) {
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
    render(row: App.Entities.User) {
      return (
        <a href={`mailto:${row.phone}`} class="transition hover:text-vusa-red">
          {row.phone}
        </a>
      );
    },
  },
];
</script>
