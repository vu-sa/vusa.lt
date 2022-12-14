<template>
  <NPopover style="max-width: 300px" :show-arrow="false">
    <template #trigger>
      <NButton text>
        <NIcon :component="QuestionCircle32Regular"></NIcon>
      </NButton>
    </template>
    <slot />
  </NPopover>
</template>

<script setup lang="ts">
import { NButton, NIcon, NPopover } from "naive-ui";
import { QuestionCircle32Regular } from "@vicons/fluent";
</script>
