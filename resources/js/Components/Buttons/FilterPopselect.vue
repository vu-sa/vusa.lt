<template>
  <NPopselect
    v-model:value="value"
    :disabled="disabled"
    :options="popselectOptions ?? []"
    @update:value="$emit('select:value', value)"
  >
    <NButton
      :disabled="disabled"
      :type="options?.[0] !== value ? 'primary' : 'default'"
      icon-placement="right"
      round
      size="small"
      ><NEllipsis class="py-1" style="max-width: 200px">{{
        $t(value ?? "") || "Nepasirinkta"
      }}</NEllipsis>
      <template #icon>
        <NIcon :component="ChevronDown24Regular"></NIcon>
      </template>
    </NButton>
  </NPopselect>
</template>

<script setup lang="tsx">
import { ChevronDown24Regular } from "@vicons/fluent";
import { NButton, NEllipsis, NIcon, NPopselect } from "naive-ui";
import { computed, ref } from "vue";

defineEmits<{
  (e: "select:value", value: string | null): void;
}>();

const props = defineProps<{
  disabled?: boolean;
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
