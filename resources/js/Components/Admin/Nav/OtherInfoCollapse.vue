<template>
  <NCollapse>
    <template #arrow>
      <NIcon>
        <MoreHorizontal24Filled />
      </NIcon>
    </template>

    <slot />
  </NCollapse>
</template>

<script setup lang="ts">
import { MoreHorizontal24Filled } from "@vicons/fluent";
import { NCollapse, NIcon } from "naive-ui";
</script>
