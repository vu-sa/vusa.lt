<template>
  <!-- Dashed: an empty slot that would only enrich the record, never a required step. -->
  <Button
    type="button"
    variant="outline"
    size="sm"
    voice="sentence"
    :class="cn('border-dashed border-muted-foreground/50 text-muted-foreground pointer-coarse:h-11', props.class)"
    data-tier="fill"
    data-slot="fill-action-button"
  >
    <component :is="icon ?? Plus" class="size-4" aria-hidden="true" />
    {{ label }}
  </Button>
</template>

<script setup lang="ts">
import { Plus } from '@lucide/vue';
import type { Component, HTMLAttributes } from 'vue';

import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';

const props = defineProps<{
  label: string;
  icon?: Component;
  class?: HTMLAttributes['class'];
}>();
</script>
