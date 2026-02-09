<template>
  <Button
    data-sidebar="trigger"
    variant="ghost"
    size="icon"
    :class="cn('h-7 w-7', props.class)"
    @click="toggleSidebar"
  >
    <PanelLeft />
    <span class="sr-only">Toggle Sidebar</span>
  </Button>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { PanelLeft } from 'lucide-vue-next';

import { useSidebar } from './utils';

import { cn } from '@/Utils/Shadcn/utils';
import { Button } from '@/Components/ui/button';

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

const { toggleSidebar } = useSidebar();
</script>
