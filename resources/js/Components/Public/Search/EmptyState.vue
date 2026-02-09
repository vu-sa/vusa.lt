<template>
  <div class="empty-state p-8 text-center">
    <div class="icon-container mx-auto mb-4 w-12 h-12 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
      <component :is="icon" class="text-zinc-400" />
    </div>
    <p class="text-muted-foreground">
      {{ message }}
    </p>
    <Button
      variant="ghost"
      size="sm"
      class="mt-4"
      @click="$emit('clear')"
    >
      {{ $t('Clear search') }}
    </Button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { defineAsyncComponent, type Component } from 'vue';

import { Button } from '@/Components/ui/button';

interface EmptyStateProps {
  message: string;
  icon?: Component;
}

defineProps<EmptyStateProps>();
defineEmits<(e: 'clear') => void>();
</script>
