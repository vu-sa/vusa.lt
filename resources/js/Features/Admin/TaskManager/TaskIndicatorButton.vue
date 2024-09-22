<template>
  <NBadge type="info" :offset="[0, -4]" :value="$page.props.auth?.user.tasks_count">
    <NButton v-bind="$attrs" :loading @click="handleClick">
      <template #icon>
          <IFluentTaskListSquare24Regular />
      </template>
    </NButton>
  </NBadge>
</template>

<script setup lang="tsx">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";

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
