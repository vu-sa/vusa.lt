<template>
  <AdminContentPage title="Užduotys">
    <TaskManager :tasks="tasks" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import TaskManager from "@/Features/Admin/TaskManager/TaskManager.vue";

defineProps<{
  tasks: App.Entities.Task[];
}>();
</script>
