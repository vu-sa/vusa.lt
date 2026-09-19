<template>
  <!-- flex-1, not h-full: the dialog gets its height from min-height, and a
       percentage height against that resolves to auto. -->
  <div data-slot="action-window-body" class="flex min-h-0 flex-1 flex-col">
    <header class="flex shrink-0 items-center gap-2 px-3 py-2 sm:px-4">
      <Button
        v-if="canGoBack"
        variant="ghost"
        size="icon-sm"
        class="pointer-coarse:size-11"
        :aria-label="$t('action_window.common.back')"
        @click="back"
      >
        <ChevronLeft class="size-5" />
      </Button>
      <span v-else class="size-8 pointer-coarse:size-11" aria-hidden="true" />

      <!-- The entity's own mark says which job you are in the middle of; the bar below
           says how far along. Segments rather than a numbered stepper, because the
           question is "a few more taps?", not which named step this is. -->
      <div class="flex min-w-0 flex-1 items-center justify-center gap-3">
        <EntityTypeMark
          v-if="identity"
          :type="identity.entity"
          :label="$t(identity.label)"
          class="min-w-0 truncate text-xs font-semibold uppercase tracking-wider"
        />
        <span v-if="progress" class="shrink-0 text-xs tabular-nums text-muted-foreground">
          <span class="sr-only">{{ $t('action_window.common.step') }}</span>
          {{ progress.step }} / {{ progress.total }}
        </span>
      </div>

      <Button
        variant="ghost"
        size="icon-sm"
        class="pointer-coarse:size-11"
        :aria-label="$t('action_window.common.close')"
        @click="close"
      >
        <X class="size-5" />
      </Button>
    </header>

    <div
      v-if="progress"
      data-slot="action-window-progress"
      class="flex shrink-0 gap-1 px-3 sm:px-4"
      aria-hidden="true"
    >
      <span
        v-for="step in progress.total"
        :key="step"
        :class="[
          'h-0.5 flex-1 transition-colors',
          step === progress.step ? 'bg-brand-fill' : step < progress.step ? 'bg-foreground/40' : 'bg-border',
        ]"
      />
    </div>
    <div v-else class="h-0.5 shrink-0" aria-hidden="true" />

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
import { ChevronLeft, X } from 'lucide-vue-next';

import { ACTION_WINDOW_SCREENS, flowIdentity, flowProgress } from './screenRegistry';

import { useActionWindow } from '@/Composables/useActionWindow';
import EntityTypeMark from '@/Components/EntityTypeMark.vue';
import FadeTransition from '@/Components/Transitions/FadeTransition.vue';
import { Button } from '@/Components/ui/button';

const { stack, current, canGoBack, back, close, skippedScreens } = useActionWindow();

const screen = computed(() => ACTION_WINDOW_SCREENS[current.value.id]);

// A screen opened from the review to change one answer is an amendment, not progress —
// showing "2 of 5" there would claim the user had gone backwards.
const progress = computed(() => current.value.params?.returnTo
  ? null
  : flowProgress(current.value.id, stack.map(frame => frame.id), skippedScreens.value));

const identity = computed(() => flowIdentity(current.value.id));
</script>
