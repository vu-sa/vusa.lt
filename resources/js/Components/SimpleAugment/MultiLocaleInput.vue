<template>
  <NInput
    v-if="inputLang === 'lt'"
    v-model:value="input.lt"
    :type="inputType"
    :placeholder="placeholders.lt"
  >
    <template #suffix
      ><SimpleLocaleButton v-model:locale="inputLang"></SimpleLocaleButton
    ></template>
  </NInput>
  <NInput
    v-else-if="inputLang === 'en'"
    v-model:value="input.en"
    :placeholder="placeholders.en"
    :type="inputType"
  >
    <template #suffix
      ><SimpleLocaleButton v-model:locale="inputLang"></SimpleLocaleButton
    ></template>
  </NInput>
</template>

<script setup lang="ts">
import { NInput } from "naive-ui";
import { computed } from "vue";

import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";

const props = defineProps<{
  inputType?: "text" | "textarea";
  placeholder?: string | { lt: string; en: string };
}>();

const inputLang = defineModel<"lt" | "en">("lang", {
  default: "lt",
});

const input = defineModel<{ lt: string; en: string }>("input", {
  default: { lt: "", en: "" },
});

const placeholders = computed(() => {
  if (!props.placeholder) {
    return { lt: "", en: "" };
  }

  return typeof props.placeholder === "string"
    ? { lt: props.placeholder, en: props.placeholder }
    : props.placeholder;
});
</script>
