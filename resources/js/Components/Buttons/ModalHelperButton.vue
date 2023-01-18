<template>
  <div class="absolute bottom-8 right-12">
    <NButton type="tertiary" color="#bbbbbb" text @click="$emit('click')"
      ><template #icon
        ><NIcon size="48" :component="Question24Regular" /></template
    ></NButton>
  </div>
</template>

<script setup lang="tsx">
import { NButton, NIcon } from "naive-ui";
import { Question24Regular } from "@vicons/fluent";

defineEmits(["click"]);
</script>
