<template>
  <div class="relative min-w-0 flex-1" data-slot="public-search-input">
    <IFluentSearch16Regular class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-muted-foreground" aria-hidden="true" />
    <input
      :id
      v-model="model"
      type="search"
      :placeholder
      :aria-label="ariaLabel ?? placeholder"
      :class="[
        'h-11 w-full border border-border bg-background pr-9 pl-10 text-sm text-foreground',
        'transition-colors placeholder:text-muted-foreground/70 focus:border-brand focus:outline-none',
        '[&::-webkit-search-cancel-button]:hidden',
      ]"
      @keydown.enter="emit('submit', model)"
    >
    <button
      v-if="model"
      type="button"
      class="absolute top-1/2 right-1 flex size-9 -translate-y-1/2 items-center justify-center text-muted-foreground hover:text-foreground pointer-coarse:size-11"
      @click="clear"
    >
      <IFluentDismiss16Regular class="size-3.5" aria-hidden="true" />
      <span class="sr-only">{{ $t('Išvalyti') }}</span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import IFluentSearch16Regular from '~icons/fluent/search-16-regular';
import IFluentDismiss16Regular from '~icons/fluent/dismiss-16-regular';

/** The text field above a public listing: search icon, a 44px field and a clear button. */
defineProps<{
  placeholder: string;
  /** Defaults to the placeholder, which names what is searched. */
  ariaLabel?: string;
  id?: string;
}>();

const model = defineModel<string>({ required: true });

const emit = defineEmits<{
  submit: [query: string];
  clear: [];
}>();

function clear(): void {
  model.value = '';
  emit('clear');
}
</script>
