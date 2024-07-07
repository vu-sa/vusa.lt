<template>
  <NBadge type="info" :offset="[0, -4]" :value="$page.props.auth?.user.tasks_count">
    <NButton :loading="loading" text circle @click="handleClick">
      <template #icon>
        <NIcon size="20">
          <IFluentTaskListSquare24Filled />
        </NIcon>
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
