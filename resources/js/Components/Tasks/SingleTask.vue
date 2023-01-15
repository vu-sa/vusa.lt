<template>
  <NCheckbox v-model:checked="checked" @update:checked="updateCompletionStatus">
    <!-- strikethrough -->
    <component
      :is="!checked ? 'span' : 's'"
      class="transition"
      :class="{ 'text-zinc-400': checked }"
      >{{ task.name }}</component
    >
  </NCheckbox>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import { NCheckbox } from "naive-ui";
import { ref } from "vue";

const props = defineProps<{
  task: Record<string, any>;
}>();

const checked = ref(!!props.task.completed_at);

const updateCompletionStatus = (checkedBox: boolean) => {
  router.post(
    route("tasks.updateCompletionStatus", { task: props.task.id }),
    {
      completed: checkedBox,
    },
    {
      preserveScroll: true,
      onError: () => {
        checked.value = !checked.value;
      },
    }
  );
};
</script>
