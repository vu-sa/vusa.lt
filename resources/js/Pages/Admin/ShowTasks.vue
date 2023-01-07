<template>
  <AdminContentPage title="Užduotys">
    <TaskManager :tasks="tasks" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import TaskManager from "@/Components/Tasks/TaskManager.vue";

defineProps<{
  tasks: App.Models.Task[];
}>();

defineOptions({
  layout: AdminLayout,
});
</script>
