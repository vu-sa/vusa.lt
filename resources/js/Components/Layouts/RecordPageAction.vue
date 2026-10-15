<template>
  <a
    v-if="action.href && action.external"
    :href="action.href"
    :class="buttonClass"
    target="_blank"
    rel="noopener noreferrer"
  >
    <component :is="action.icon" v-if="action.icon" class="size-4" />
    {{ action.label }}
  </a>
  <Link v-else-if="action.href" :href="action.href" :class="buttonClass">
    <component :is="action.icon" v-if="action.icon" class="size-4" />
    {{ action.label }}
  </Link>
  <Button v-else :variant="primary ? 'brand' : 'outline'" size="default" :voice="primary ? 'brand' : 'sentence'" @click="$emit('select', action.key)">
    <component :is="action.icon" v-if="action.icon" class="size-4" />
    {{ action.label }}
  </Button>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';

import type { RecordAction } from './RecordPage.vue';

import { Button, buttonVariants } from '@/Components/ui/button';

const props = defineProps<{
  action: RecordAction;
  primary?: boolean;
}>();

defineEmits<{
  select: [key: string];
}>();

const buttonClass = computed(() => buttonVariants({
  variant: props.primary ? 'brand' : 'outline',
  size: 'default',
  voice: props.primary ? 'brand' : 'sentence',
}));
</script>
