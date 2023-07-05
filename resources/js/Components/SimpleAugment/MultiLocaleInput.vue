<template>
  <NInput
    v-if="inputLang === 'lt'"
    v-model:value="input.lt"
    :type="inputType"
    :placeholder="placeholder"
  >
    <template #suffix
      ><SimpleLocaleButton v-model:locale="inputLang"></SimpleLocaleButton
    ></template>
  </NInput>
  <NInput
    v-else-if="inputLang === 'en'"
    v-model:value="input.en"
    :placeholder="placeholder"
    :type="inputType"
  >
    <template #suffix
      ><SimpleLocaleButton v-model:locale="inputLang"></SimpleLocaleButton
    ></template>
  </NInput>
</template>

<script setup lang="ts">
import { NInput } from "naive-ui";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";

defineProps<{
  inputType?: "text" | "textarea";
  placeholder?: string;
}>();

const inputLang = defineModel<"lt" | "en">("lang", {
  default: "lt",
});

const input = defineModel<{ lt: string; en: string }>("input", {
  default: { lt: "", en: "" },
});
</script>
