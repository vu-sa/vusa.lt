<template>
  <NInput
    v-model:value="value"
    :disabled="disabled"
    round
    placeholder="Ieškoti..."
    @input="handleInput"
  >
    <template #suffix><NIcon :component="Search20Filled"></NIcon></template>
  </NInput>
</template>

<script setup lang="tsx">
import { NIcon, NInput } from "naive-ui";
import { Search20Filled } from "@vicons/fluent";
import { computed, ref } from "vue";
import { useFuse } from "@vueuse/integrations/useFuse";

const emit = defineEmits<{
  (event: "search:results", results: Record<string, any>[]): void;
}>();

const props = defineProps<{
  data: Record<string, any>[] | [] | null;
  disabled?: boolean;
  skeleton?: boolean;
}>();

const value = ref("");

const options = computed(() => ({
  fuseOptions: {
    keys: ["name"],
    threshold: undefined,
  },
  matchAllWhenSearchEmpty: true,
}));

const { results } = useFuse(value, props.data, options);

const handleInput = () => {
  emit("search:results", results.value);
};
</script>
