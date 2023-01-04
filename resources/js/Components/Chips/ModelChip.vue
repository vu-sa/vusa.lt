<template>
  <NTag class="mx-2" :bordered="false" round size="small">
    <template #icon>
      <slot name="icon" />
    </template>
    <strong>
      <slot />
    </strong>
  </NTag>
</template>

<script setup lang="tsx">
import { NTag } from "naive-ui";
</script>
