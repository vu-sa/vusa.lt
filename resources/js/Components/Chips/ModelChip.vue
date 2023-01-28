<template>
  <!-- Component is useful for inline, standartized showings of models and their icons -->
  <NTag class="mx-1 mb-1" round size="tiny">
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
