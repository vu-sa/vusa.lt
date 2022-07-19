<template>
  <NSkeleton class="rounded-lg shadow-sm mb-1" height="150px"> </NSkeleton>

  <NSkeleton class="mt-1 rounded-lg shadow-sm" text :repeat="2"></NSkeleton>
  <NSkeleton class="mt-1 rounded-lg shadow-sm" text width="60%"></NSkeleton>
</template>

<script setup lang="ts">
import { NSkeleton } from "naive-ui";
</script>
