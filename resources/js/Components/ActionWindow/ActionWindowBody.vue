<template>
  <!-- flex-1, not h-full: the dialog gets its height from min-height, and a
       percentage height against that resolves to auto. -->
  <div data-slot="action-window-body" class="flex min-h-0 flex-1 flex-col">
    <header class="flex shrink-0 items-start justify-between gap-3 border-b border-border p-4 sm:p-5">
      <div class="flex min-w-0 flex-1 items-center gap-3">
        <!-- Back button (when stack > 1) -->
        <button
          v-if="canGoBack"
          type="button"
          class="flex size-8 shrink-0 items-center justify-center border border-border text-muted-foreground transition-colors hover:border-brand hover:text-brand"
          :aria-label="$t('action_window.common.back')"
          @click="back"
        >
          <ChevronLeft class="size-4" />
        </button>

        <!-- Brand Icon Tile (design reference style) -->
        <span
          v-if="identity"
          class="flex size-10 shrink-0 items-center justify-center bg-brand-fill text-brand-foreground"
          aria-hidden="true"
        >
          <component :is="identityIcon" class="size-5" />
        </span>

        <!-- Title & Eyebrow Stack -->
        <div class="min-w-0 flex-1">
          <p
            v-if="progress"
            class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand"
          >
            {{ $t('action_window.common.step') }} {{ progress.step }} / {{ progress.total }}
          </p>
          <p
            v-else-if="isReview"
            class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand"
          >
            {{ $t('action_window.common.review') }}
          </p>
          <p
            v-else-if="identity"
            class="text-[10px] font-bold uppercase tracking-[0.2em] text-brand"
          >
            {{ $t(identity.label) }}
          </p>
          <EntityTypeMark
            v-if="identity"
            :type="identity.entity"
            :label="$t(identity.label)"
            class="min-w-0 truncate text-base font-bold text-foreground sm:text-lg [&>span:first-child]:hidden"
          />
          <h2 v-else class="min-w-0 truncate text-base font-bold text-foreground sm:text-lg">
            {{ $t('action_window.personas.title') }}
          </h2>
        </div>
      </div>

      <!-- Close button -->
      <button
        type="button"
        class="flex size-8 shrink-0 items-center justify-center border border-border text-muted-foreground transition-colors hover:border-brand hover:text-brand"
        :aria-label="$t('action_window.common.close')"
        @click="close"
      >
        <X class="size-4" />
      </button>
    </header>

    <div
      v-if="progress"
      data-slot="action-window-progress"
      class="flex shrink-0 gap-1 px-4 pt-3 sm:px-5"
      aria-hidden="true"
    >
      <span
        v-for="step in progress.total"
        :key="step"
        :class="[
          'h-1 flex-1 transition-colors',
          step === progress.step ? 'bg-brand-fill' : step < progress.step ? 'bg-brand-fill/50' : 'bg-border',
        ]"
      />
    </div>
    <div v-else class="h-1 shrink-0" aria-hidden="true" />

    <!-- flex, not just flex-1: the screen's sticky footer needs a parent whose height
         is resolved, or `h-full` on it collapses to its content. -->
    <div class="flex min-h-0 flex-1 flex-col">
      <FadeTransition mode="out-in">
        <component :is="screen" :key="current.id" :params="current.params" />
      </FadeTransition>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { ChevronLeft, ClipboardList, X } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { ACTION_WINDOW_SCREENS, flowIdentity, flowProgress } from './screenRegistry';

import { useActionWindow } from '@/Composables/useActionWindow';
import { getEntityTypeDefinition } from '@/Constants/entityTypes';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import FadeTransition from '@/Components/Transitions/FadeTransition.vue';

const { stack, current, canGoBack, back, close, skippedScreens } = useActionWindow();

const screen = computed(() => ACTION_WINDOW_SCREENS[current.value.id]);

// A screen opened from the review to change one answer is an amendment, not progress —
// showing "2 of 5" there would claim the user had gone backwards.
const progress = computed(() => current.value.params?.returnTo
  ? null
  : flowProgress(current.value.id, stack.map(frame => frame.id), skippedScreens.value));

const isReview = computed(() => current.value.id.endsWith('.review'));

const identity = computed(() => flowIdentity(current.value.id));

const identityIcon = computed(() => {
  if (!identity.value) return ClipboardList;
  return getEntityTypeDefinition(identity.value.entity)?.icon ?? ClipboardList;
});
</script>
