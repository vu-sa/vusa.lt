<template>
  <div v-if="inputLang === 'lt'" class="w-full">
    <NInput v-model:value="input.lt" :type="inputType" :placeholder="placeholders.lt">
      <template #suffix>
        <SimpleLocaleButton v-model:locale="inputLang" />
      </template>
    </NInput>
    <div class="ml-2 mt-0.5">
      <button class="text-zinc-400" @click="inputLang = LocaleEnum.EN">
        🇬🇧<span v-if="input.en" class="ml-1 font-semibold">{{ input.en }}</span>
        <span v-else class="ml-1 italic">None</span>
      </button>
    </div>
  </div>
  <div v-else-if="inputLang === 'en'" class="w-full">
    <NInput v-model:value="input.en" :placeholder="placeholders.en" :type="inputType">
      <template #suffix>
        <SimpleLocaleButton v-model:locale="inputLang" />
      </template>
    </NInput>
    <div class="ml-2 mt-0.5">
      <button class="text-zinc-400" @click="inputLang = LocaleEnum.LT">
        🇱🇹<span v-if="input.lt" class="ml-1 font-semibold">{{ input.lt }}</span>
        <span v-else class="ml-1 italic">Nėra</span>
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import { LocaleEnum } from "@/Types/enums";

const props = defineProps<{
  inputType?: "text" | "textarea";
  placeholder?: string | { lt: string; en: string };
}>();

// Need an instance of the current locale, not sync
const inputLang = ref(usePage().props.app.locale)

const input = defineModel<{ lt: string; en: string }>("input", {
  default: { lt: "", en: "" },
});

if (Array.isArray(input.value)) {
  input.value = { lt: "", en: "" };
}

const placeholders = computed(() => {
  if (!props.placeholder) {
    return { lt: "", en: "" };
  }

  return typeof props.placeholder === "string"
    ? { lt: props.placeholder, en: props.placeholder }
    : props.placeholder;
});
</script>
