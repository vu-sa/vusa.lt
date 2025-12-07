<template>
  <NTooltip arrow-point-to-center size="tiny">
    <template v-if="locale === 'lt'"> Pildyti anglų kalbos laukelius</template>
    <template v-else> Fill in Lithuanian fields</template>
    <template #trigger>
      <Button
        variant="ghost"
        size="sm"
        @click="$emit('update:locale', locale === 'lt' ? 'en' : 'lt')"
      >
        {{ locale === "lt" ? "🇱🇹" : "🇬🇧" }}
      </Button>
    </template>
  </NTooltip>
</template>

<script setup lang="ts">
import { NTooltip } from "naive-ui";
import { Button } from "@/Components/ui/button";
import type { LocaleEnum } from "@/Types/enums";

defineEmits<{
  (event: "update:locale", locale: LocaleEnum): void;
}>();

defineProps<{
  locale: string;
}>();
</script>
