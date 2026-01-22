<template>
  <NFormItem>
    <template #label>
      <div class="inline-flex items-center gap-2">
        {{ label }}
        <SimpleLocaleButton v-model:locale="inputLang" />
      </div>
    </template>
    <TiptapEditor v-if="inputLang === 'lt'" v-model="input.lt" preset="full" :html="true" />
    <TiptapEditor v-else v-model="input.en" preset="full" :html="true" />
  </NFormItem>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import TiptapEditor from "../TipTap/TiptapEditor.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";

defineProps<{
  label: string;
}>();

const inputLang = ref(usePage().props.app.locale)

const input = defineModel<{ lt: string; en: string }>("input", {
  default: { lt: "", en: "" },
});
</script>
