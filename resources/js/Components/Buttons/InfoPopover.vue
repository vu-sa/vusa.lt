<template>
  <NTooltip :style="{ maxWidth: '600px' }" trigger="hover" :show-arrow="false" :show-delay="500" :hide-delay="100">
    <template #trigger>
      <Button variant="ghost" size="icon-xs">
        <IFluentInfo24Regular />
      </Button>
    </template>
    <slot />
  </NTooltip>
</template>

<script setup lang="tsx">
import { Button } from "@/Components/ui/button";
defineProps<{
  color?: string;
}>();
</script>
