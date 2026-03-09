<template>
  <div class="absolute bottom-8 right-12">
    <Button variant="ghost" class="text-zinc-400" @click="$emit('click')">
      <IFluentQuestion24Regular class="size-12" />
    </Button>
  </div>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button';

defineEmits(["click"]);
</script>
