<template>
  <Button
    :variant
    :size
    type="button"
    :class="cn('gap-2', props.class)"
    data-slot="share-button"
    @click="share({ title, url })"
  >
    <IFluentShare24Regular class="size-4" />
    <span :class="labelClass">
      <slot>{{ $t('common.share') }}</slot>
    </span>
  </Button>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import IFluentShare24Regular from '~icons/fluent/share-24-regular';
import { Button } from '@/Components/ui/button';
import type { ButtonVariants } from '@/Components/ui/button';
import { useShareLink } from '@/Composables/useShareLink';
import { cn } from '@/Utils/Shadcn/utils';

/**
 * Share this page — the native share sheet where the browser has one, a copied link where it
 * doesn't. All of that lives in `useShareLink`; this is only the control.
 */
const props = withDefaults(defineProps<{
  /** What the share sheet announces. Usually the article or event title. */
  title: string;
  /** Defaults to the current page. */
  url?: string;
  variant?: ButtonVariants['variant'];
  size?: ButtonVariants['size'];
  /** Hide the label below `sm`, for rows that are tight on a phone. */
  labelClass?: HTMLAttributes['class'];
  class?: HTMLAttributes['class'];
}>(), {
  url: undefined,
  variant: 'brand-outline',
  size: 'public-sm',
  labelClass: undefined,
  class: undefined,
});

const { share } = useShareLink();
</script>
