<template>
  <NButton text @click="$emit('update:locale', locale === 'lt' ? 'en' : 'lt')"
    ><template #icon>{{ locale === "lt" ? "🇱🇹" : "🇬🇧" }}</template></NButton
  >
</template>

<script setup lang="ts">
import { NButton } from "naive-ui";
import type { LocaleEnum } from "@/Types/enums";

defineEmits<{
  (event: "update:locale", locale: LocaleEnum): void;
}>();

defineProps<{
  locale: string;
}>();
</script>
