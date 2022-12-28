<template>
  <NCard
    class="max-w-sm"
    :segmented="{
      footer: 'soft',
    }"
  >
    <slot />
    <template #footer>
      <div class="flex items-center justify-between">
        <slot name="action-button" />
        <NButton size="small" text
          ><template #icon><NIcon :component="Info24Regular"></NIcon></template
        ></NButton>
      </div> </template
  ></NCard>
</template>

<script setup lang="tsx">
import { Info24Regular } from "@vicons/fluent";
import { NButton, NCard, NIcon } from "naive-ui";
</script>
