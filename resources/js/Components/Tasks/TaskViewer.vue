<template>
  <template v-for="task in tasks" :key="task.id">
    <SingleTask :task="task" />
  </template>
</template>

<script setup lang="ts">
import SingleTask from "@/Components/Tasks/SingleTask.vue";

defineProps<{
  tasks: Record<string, any>[];
}>();
</script>
