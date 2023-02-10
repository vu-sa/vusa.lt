<template>
  <NTooltip arrow-point-to-center size="tiny">
    <template v-if="locale === 'lt'"> Pildyti anglų kalbos laukelius</template>
    <template v-else> Fill in Lithuanian fields</template>
    <template #trigger>
      <NButton
        text
        @click="$emit('update:locale', locale === 'lt' ? 'en' : 'lt')"
        ><template #icon>{{ locale === "lt" ? "🇱🇹" : "🇬🇧" }}</template></NButton
      >
    </template>
  </NTooltip>
</template>

<script setup lang="ts">
import { NButton, NTooltip } from "naive-ui";
import type { LocaleEnum } from "@/Types/enums";

defineEmits<{
  (event: "update:locale", locale: LocaleEnum): void;
}>();

defineProps<{
  locale: string;
}>();
</script>
