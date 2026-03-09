<template>
  <div class="w-full space-y-1">
    <div class="relative">
      <Input 
        v-if="inputType !== 'textarea'"
        v-model="currentValue" 
        :placeholder="currentPlaceholder"
        class="pr-12"
      />
      <Textarea
        v-else
        v-model="currentValue"
        :placeholder="currentPlaceholder"
        class="pr-12 min-h-20"
      />
      <div class="absolute right-1.5 top-1/2 -translate-y-1/2">
        <SimpleLocaleButton v-model:locale="inputLang" />
      </div>
    </div>
    <button 
      type="button"
      class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground transition-colors"
      @click="toggleLang"
    >
      <span>{{ otherLangFlag }}</span>
      <span v-if="otherLangValue" class="font-medium truncate max-w-48">{{ otherLangValue }}</span>
      <span v-else class="italic">{{ inputLang === 'lt' ? 'None' : 'Nėra' }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";

import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { LocaleEnum } from "@/Types/enums";

const props = defineProps<{
  inputType?: "text" | "textarea";
  placeholder?: string | { lt: string; en: string };
}>();

const inputLang = ref(usePage().props.app.locale);

const input = defineModel<{ lt: string; en: string }>("input", {
  default: { lt: "", en: "" },
});

if (Array.isArray(input.value)) {
  input.value = { lt: "", en: "" };
}

const currentValue = computed({
  get: () => inputLang.value === 'lt' ? input.value.lt : input.value.en,
  set: (val: string) => {
    if (inputLang.value === 'lt') {
      input.value.lt = val;
    } else {
      input.value.en = val;
    }
  }
});

const currentPlaceholder = computed(() => {
  if (!props.placeholder) return '';
  if (typeof props.placeholder === 'string') return props.placeholder;
  return inputLang.value === 'lt' ? props.placeholder.lt : props.placeholder.en;
});

const otherLangFlag = computed(() => inputLang.value === 'lt' ? '🇬🇧' : '🇱🇹');
const otherLangValue = computed(() => inputLang.value === 'lt' ? input.value.en : input.value.lt);

const toggleLang = () => {
  inputLang.value = inputLang.value === 'lt' ? LocaleEnum.EN : LocaleEnum.LT;
};
</script>
