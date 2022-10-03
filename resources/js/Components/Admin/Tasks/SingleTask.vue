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
import { Inertia } from "@inertiajs/inertia";
import { NCheckbox } from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

const props = defineProps<{
  task: Record<string, any>;
}>();

const checked = ref(!!props.task.completed_at);

const updateCompletionStatus = (checkedBox: boolean) => {
  Inertia.post(
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
