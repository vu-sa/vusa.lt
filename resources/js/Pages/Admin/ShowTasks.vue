<template>
  <AdminContentPage :title="$t('Užduotys')">
    <TaskManager :tasks="tasks" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import { BreadcrumbHelpers, usePageBreadcrumbs } from "@/Composables/useBreadcrumbsUnified";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";
import { trans as $t } from "laravel-vue-i18n";
import Icons from "@/Types/Icons/regular";

defineProps<{
  tasks: App.Entities.Task[];
}>();

// Generate breadcrumbs automatically with new simplified API
usePageBreadcrumbs([
  { label: $t('Užduotys'), icon: Icons.TASK }
]);
</script>
