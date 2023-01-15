<template>
  <NBadge
    type="info"
    :offset="[0, -4]"
    :value="$page.props.auth.user.tasks_count"
  >
    <NButton :loading="loading" text circle @click="handleClick"
      ><template #icon
        ><NIcon :size="24" :component="TasksApp24Regular"></NIcon></template
    ></NButton>
  </NBadge>
</template>

<script setup lang="tsx">
import { router } from "@inertiajs/vue3";
import { NBadge, NButton, NIcon } from "naive-ui";
import { TasksApp24Regular } from "@vicons/fluent";
import { ref } from "vue";

const loading = ref(false);

const handleClick = () => {
  loading.value = true;
  router.visit(route("userTasks"), {
    preserveState: true,
    onSuccess: () => {
      loading.value = false;
    },
  });
};
</script>
