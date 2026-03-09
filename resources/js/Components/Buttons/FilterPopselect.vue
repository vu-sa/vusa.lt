<template>
  <NPopselect v-model:value="value" :disabled="disabled" :options="popselectOptions ?? []"
    @update:value="$emit('select:value', value)">
    <Button :disabled="disabled" :variant="options?.[0] !== value ? 'default' : 'outline'" size="sm" class="rounded-full">
      <NEllipsis class="py-1" style="max-width: 200px">{{ 
        $t(value ?? "") || "Nepasirinkta" 
      }}</NEllipsis>
      <IFluentChevronDown20Regular />
    </Button>
  </NPopselect>
</template>

<script setup lang="tsx">
import { computed, ref } from "vue";
import { Button } from "@/Components/ui/button";

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
