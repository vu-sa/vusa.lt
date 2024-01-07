<template>
  <NMenu
    v-model:value="activeMenuKey"
    mode="vertical"
    @update:value="$emit('close:drawer')"
  />
</template>

<script setup lang="tsx">
import { NMenu } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

defineEmits<{
  (e: "close:drawer"): void;
}>();

const activeMenuKey = ref(usePage().props.navigationItemId);
</script>
