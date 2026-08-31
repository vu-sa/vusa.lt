<template>
  <!-- flex-1, not h-full: the dialog gets its height from min-height, and a
       percentage height against that resolves to auto. -->
  <div data-slot="action-window-body" class="flex min-h-0 flex-1 flex-col">
    <header class="flex shrink-0 items-center gap-2 px-3 pb-2 pt-4 sm:px-4">
      <Button
        v-if="canGoBack"
        variant="ghost"
        size="icon-sm"
        :aria-label="$t('action_window.common.back')"
        @click="back"
      >
        <ChevronLeft class="size-5" />
      </Button>
      <span v-else class="size-8" aria-hidden="true" />

      <!-- The action's own icon says which job you are in the middle of; the dots say
           how far along. Progress dots rather than a numbered stepper, because the
           question is "a few more taps?", not which named step this is. -->
      <div v-if="identity" class="flex flex-1 items-center justify-center gap-2.5">
        <span
          :class="[
            'flex size-6 shrink-0 items-center justify-center rounded-md bg-gradient-to-br text-foreground/70',
            identity.gradient,
          ]"
        >
          <component :is="identity.icon" class="size-3.5" :stroke-width="2" />
        </span>
        <span v-if="progress" class="flex items-center gap-1.5">
          <span
            v-for="step in progress.total"
            :key="step"
            :class="[
              'h-1.5 rounded-full transition-all duration-300',
              step === progress.step ? 'w-5 bg-primary' : 'w-1.5',
              step < progress.step ? 'bg-primary/40' : step !== progress.step ? 'bg-border' : '',
            ]"
          />
        </span>
      </div>
      <span v-else class="flex-1" />

      <Button
        variant="ghost"
        size="icon-sm"
        :aria-label="$t('action_window.common.close')"
        @click="close"
      >
        <X class="size-5" />
      </Button>
    </header>

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
