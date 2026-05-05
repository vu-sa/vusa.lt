<template>
  <Button class="rounded-full" :variant="active ? 'default' : 'outline'">
    <component :is="icon" v-if="icon" class="size-4" />
    <div class="inline-flex items-center gap-2">
      <span>{{ name }}</span>
      <Badge v-if="count !== undefined" variant="secondary" class="rounded-full px-1.5 py-0 text-xs">
        {{ count }}
      </Badge>
    </div>
  </Button>
</template>

<script setup lang="ts">
import type { Component } from 'vue';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';

defineProps<{
  name: string;
  icon?: Component;
  count?: number | string;
  active?: boolean;
}>();
</script>
