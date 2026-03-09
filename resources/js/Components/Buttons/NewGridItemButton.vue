<template>
  <Button variant="ghost"
    class="h-full min-h-32 max-w-sm flex-col justify-center gap-4 rounded-sm border px-2 py-4 text-zinc-500 duration-200 hover:shadow-inner dark:border-zinc-700 dark:shadow-zinc-900"
    @click="$emit('click')">
    <span class="size-10">
      <slot name="icon" />
    </span>
    <span class="text-center">
      <slot>{{ $t("Sukurti naują") }}?</slot>
    </span>
  </Button>
</template>

<script setup lang="ts">
import { Button } from "@/Components/ui/button";

defineEmits(["click"]);
</script>
