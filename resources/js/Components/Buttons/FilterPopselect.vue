<template>
  <NPopselect
    v-model:value="value"
    :options="popselectOptions ?? []"
    @update:value="$emit('click', value)"
  >
    <NButton
      :type="options?.[0] !== value ? 'primary' : 'default'"
      icon-placement="right"
      round
      size="small"
      >{{ value || "Nepasirinkta" }}
      <template #icon>
        <NIcon :component="ChevronDown24Regular"></NIcon>
      </template>
    </NButton>
  </NPopselect>
</template>

<script setup lang="tsx">
import { ChevronDown24Regular } from "@vicons/fluent";
import { NButton, NIcon, NPopselect } from "naive-ui";
import { computed, ref } from "vue";

const emit = defineEmits<{
  (e: "click", value: string | null): void;
}>();

const props = defineProps<{
  options: Array<string | null>;
}>();

const value = ref<string | null>(props.options?.[0] ?? null);

const popselectOptions = computed(() => {
  return props.options.map((option) => {
    return {
      label: option,
      value: option,
    };
  });
});
</script>
