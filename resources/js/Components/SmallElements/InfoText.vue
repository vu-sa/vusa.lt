<template>
  <span class="flex items-center gap-2 text-xs text-zinc-400">
    <NIcon :component="Info24Regular"></NIcon>
    <slot />
  </span>
</template>

<script setup lang="tsx">
import { Info24Regular } from "@vicons/fluent";
import { NIcon } from "naive-ui";
</script>
