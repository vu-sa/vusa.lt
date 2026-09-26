<template>
  <button
    type="button"
    class="u-touch flex shrink-0 items-center justify-center text-muted-foreground transition-colors hover:text-foreground"
    :class="{ 'text-foreground': pinned }"
    :aria-pressed="pinned"
    :aria-label="pinned ? $t('shell.palette.unpin') : $t('shell.palette.pin')"
    :title="pinned ? $t('shell.palette.unpin') : $t('shell.palette.pin')"
    @click.stop="emit('toggle')"
  >
    <Star class="size-4" :fill="pinned ? 'currentColor' : 'none'" />
  </button>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Star } from 'lucide-vue-next';

defineProps<{ pinned: boolean }>();

const emit = defineEmits<{ toggle: [] }>();
</script>
