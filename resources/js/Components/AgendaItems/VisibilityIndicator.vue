<template>
  <span
    class="inline-flex items-center gap-1 text-[11px] font-medium"
    :class="isPublic
      ? 'text-emerald-600 dark:text-emerald-400'
      : 'text-zinc-400 dark:text-zinc-500'"
  >
    <component :is="isPublic ? Globe : EyeOff" class="h-3 w-3" />
    {{ isPublic ? $t('matomas viešai') : $t('Nematomas viešai') }}
  </span>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { EyeOff, Globe } from 'lucide-vue-next';

const props = withDefaults(defineProps<{
  public?: boolean;
}>(), {
  public: false,
});

const isPublic = computed(() => props.public);
</script>
