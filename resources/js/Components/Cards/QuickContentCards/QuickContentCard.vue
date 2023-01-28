<template>
  <NCard
    class="max-w-sm"
    :segmented="{
      footer: 'soft',
    }"
  >
    <template #header><slot name="header" /></template>
    <template #header-extra>
      <slot name="header-extra" />
    </template>
    <slot />
    <template #footer>
      <div class="flex flex-row-reverse items-center justify-between">
        <div class="ml-auto inline-flex h-fit w-fit">
          <NButton size="small" text
            ><template #icon
              ><NIcon :component="Info24Regular"></NIcon></template
          ></NButton>
        </div>
        <slot name="action-button" />
      </div> </template
  ></NCard>
</template>

<script setup lang="tsx">
import { Info24Regular } from "@vicons/fluent";
import { NButton, NCard, NIcon } from "naive-ui";
</script>

<style>
.n-card > .n-card-header {
  padding-bottom: 0.5rem;
}
</style>
