<template>
  <AdminContentPage :title="$t('Užduotys')" :breadcrumbs="breadcrumbs">
    <TaskManager :tasks="tasks" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import { useBreadcrumbs, type BreadcrumbItem } from "@/Composables/useBreadcrumbs";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import { computed } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import Icons from "@/Types/Icons/regular";

defineProps<{
  tasks: App.Entities.Task[];
}>();

const { createBreadcrumbItem } = useBreadcrumbs();

const breadcrumbs = computed((): BreadcrumbItem[] => [
  createBreadcrumbItem($t('Užduotys'), undefined, Icons.TASK),
]);
</script>
