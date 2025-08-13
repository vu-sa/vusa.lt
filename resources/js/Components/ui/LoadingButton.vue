<template>
  <Button v-bind="$attrs" :disabled="loading || disabled" @click="handleClick">
    <div v-if="loading" class="flex items-center gap-2">
      <div class="w-3 h-3 border border-current border-t-transparent rounded-full animate-spin"></div>
      <span v-if="$slots.default">
        <slot />
      </span>
    </div>
    <slot v-else />
  </Button>
</template>

<script setup lang="ts">
import { Button } from '@/Components/ui/button'

defineProps<{
  loading?: boolean
  disabled?: boolean
}>()

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()

const handleClick = (event: MouseEvent) => {
  emit('click', event)
}
</script>