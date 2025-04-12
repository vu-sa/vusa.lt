<template>
  <div class="relative">
    <Badge v-if="$page.props.auth?.user.tasks_count > 0" class="absolute -top-3 -right-3 z-10">
      <span> {{ $page.props.auth?.user.tasks_count }}</span>
    </Badge>
    <NButton v-bind="$attrs" :loading @click="handleClick">
      <template #icon>
        <IFluentTaskListSquare24Regular />
      </template>
    </NButton>
  </div>
</template>

<script setup lang="tsx">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import { Badge } from '@/Components/ui/badge';

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
