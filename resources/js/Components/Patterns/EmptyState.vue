<template>
  <div
    data-slot="empty-state"
    :class="cn(
      'flex flex-col items-center justify-center py-12 px-4 text-center sm:py-16',
      props.class,
    )"
  >
    <!-- Icon or Mascot presentation -->
    <div class="mb-5 flex items-center justify-center">
      <!-- Supplied icon via slot -->
      <div
        v-if="$slots.icon"
        class="flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground"
      >
        <slot name="icon" />
      </div>

      <!-- Supplied icon via prop -->
      <div
        v-else-if="icon"
        class="flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground"
      >
        <component :is="icon" class="size-6" />
      </div>

      <!-- Default for no-results mode -->
      <div
        v-else-if="mode === 'no-results'"
        class="flex size-12 items-center justify-center border border-border bg-secondary text-muted-foreground"
      >
        <SearchX class="size-6" />
      </div>

      <!-- Default mascot for empty state -->
      <TurtleMascot
        v-else
        class="h-14 text-muted-foreground opacity-70"
      />
    </div>

    <!-- Title -->
    <h3 class="text-lg font-semibold tracking-tight text-foreground sm:text-xl">
      <slot name="title">
        {{ displayTitle }}
      </slot>
    </h3>

    <!-- Description / guidance -->
    <p
      v-if="displayDescription || $slots.description"
      class="mt-2 max-w-md text-sm text-muted-foreground"
    >
      <slot name="description">
        {{ displayDescription }}
      </slot>
    </p>

    <!-- Actions & Teaching aids -->
    <div
      v-if="hasActionRow || $slots.default"
      class="mt-6 flex flex-wrap items-center justify-center gap-3"
    >
      <!-- Mode: No-results clear button -->
      <template v-if="mode === 'no-results'">
        <slot name="clear">
          <Button
            variant="outline"
            size="sm"
            @click="emit('clear')"
          >
            {{ clearLabel || $t('tables.clear_filters') }}
          </Button>
        </slot>
      </template>

      <!-- Mode: Empty primary action -->
      <template v-else>
        <slot name="action">
          <Link
            v-if="actionHref && actionLabel"
            :href="actionHref"
          >
            <Button
              variant="brand"
              size="sm"
            >
              {{ actionLabel }}
            </Button>
          </Link>
          <Button
            v-else-if="actionLabel"
            variant="brand"
            size="sm"
            @click="emit('action')"
          >
            {{ actionLabel }}
          </Button>
        </slot>

        <!-- Documentation link -->
        <slot name="docs">
          <a
            v-if="docsHref"
            :href="docsHref"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex items-center gap-1.5 text-sm text-muted-foreground transition-colors hover:text-foreground underline underline-offset-4"
          >
            <span>{{ docsLabel || $t('Plačiau dokumentacijoje') }}</span>
            <ExternalLink class="size-3.5" />
          </a>
        </slot>
      </template>

      <!-- Arbitrary child elements from existing callers -->
      <slot />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Component, HTMLAttributes } from 'vue';
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ExternalLink, SearchX } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import TurtleMascot from '@/Components/Empty/TurtleMascot.vue';
import { Button } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';

interface Props {
  /**
   * 'empty' = first-use teaching state (what this is, first action, docs link).
   * 'no-results' = active filters returned no matches (guidance + clear action).
   */
  mode?: 'empty' | 'no-results';
  title?: string;
  description?: string;
  icon?: Component;
  actionLabel?: string;
  actionHref?: string;
  docsLabel?: string;
  docsHref?: string;
  clearLabel?: string;
  class?: HTMLAttributes['class'];
}

const props = withDefaults(defineProps<Props>(), {
  mode: 'empty',
  title: undefined,
  description: undefined,
  icon: undefined,
  actionLabel: undefined,
  actionHref: undefined,
  docsLabel: undefined,
  docsHref: undefined,
  clearLabel: undefined,
  class: undefined,
});

const emit = defineEmits<{
  (e: 'action'): void;
  (e: 'clear'): void;
}>();

const slots = useSlots();

const displayTitle = computed(() => {
  if (props.title) return props.title;
  return props.mode === 'no-results'
    ? $t('tables.no_results')
    : '';
});

const displayDescription = computed(() => {
  if (props.description) return props.description;
  return props.mode === 'no-results'
    ? $t('Pagal pasirinktus paieškos ar filtravimo kriterijus nieko nerasta.')
    : '';
});

const hasActionRow = computed(() => {
  if (props.mode === 'no-results') {
    return true;
  }
  return Boolean(
    props.actionLabel
    || props.actionHref
    || props.docsHref
    || slots.action
    || slots.docs
    || slots.clear,
  );
});
</script>
