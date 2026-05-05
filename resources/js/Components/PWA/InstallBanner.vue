<template>
  <Transition
    enter-active-class="transition duration-300 ease-out"
    enter-from-class="translate-y-full opacity-0"
    enter-to-class="translate-y-0 opacity-100"
    leave-active-class="transition duration-200 ease-in"
    leave-from-class="translate-y-0 opacity-100"
    leave-to-class="translate-y-full opacity-0"
  >
    <div
      v-if="shouldShow"
      class="fixed bottom-4 left-4 right-4 z-50 mx-auto max-w-md rounded-xl border bg-background p-4 shadow-lg sm:left-auto sm:right-4 sm:w-96"
    >
      <div class="flex items-start gap-3">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-vusa-red/10">
          <IFluentArrowDownload24Regular class="h-5 w-5 text-vusa-red" />
        </div>
        <div class="flex-1 space-y-1">
          <h3 class="font-semibold text-foreground">
            {{ $t('pwa.install_title') }}
          </h3>
          <p class="text-sm text-muted-foreground">
            {{ $t('pwa.install_description') }}
          </p>
        </div>
        <Button
          variant="ghost"
          size="icon"
          class="shrink-0 -mt-1 -mr-1"
          @click="dismiss"
        >
          <IFluentDismiss24Regular class="h-4 w-4" />
        </Button>
      </div>
      <div class="mt-3 flex gap-2">
        <Button
          variant="outline"
          size="sm"
          class="flex-1"
          @click="dismiss"
        >
          {{ $t('Vėliau') }}
        </Button>
        <Button
          size="sm"
          class="flex-1 bg-vusa-red hover:bg-vusa-red/90"
          @click="install"
        >
          {{ $t('Įdiegti') }}
        </Button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';

import { Button } from '@/Components/ui/button';
import { usePWA } from '@/Composables/usePWA';
import IFluentArrowDownload24Regular from '~icons/fluent/arrow-download-24-regular';
import IFluentDismiss24Regular from '~icons/fluent/dismiss-24-regular';

const STORAGE_KEY = 'pwa_install_prompt';
const DISMISS_DURATION_DAYS = 7;
const MIN_VISITS = 3;
const MIN_SESSION_SECONDS = 120; // 2 minutes

const { canInstall, promptInstall, isPWA } = usePWA();

const dismissed = ref(false);
const sessionStartTime = ref(Date.now());
const visitCount = ref(0);

// Check if we should show the banner
const shouldShow = computed(() => {
  // Never show if already installed, can't install, or dismissed
  if (isPWA.value || !canInstall.value || dismissed.value) {
    return false;
  }

  // Check minimum visit count
  if (visitCount.value < MIN_VISITS) {
    return false;
  }

  // Check minimum session duration
  const sessionDuration = (Date.now() - sessionStartTime.value) / 1000;
  if (sessionDuration < MIN_SESSION_SECONDS) {
    return false;
  }

  return true;
});

const dismiss = () => {
  dismissed.value = true;

  // Store dismissal with expiration
  const dismissedUntil = Date.now() + (DISMISS_DURATION_DAYS * 24 * 60 * 60 * 1000);
  localStorage.setItem(STORAGE_KEY, JSON.stringify({ dismissedUntil }));
};

const install = async () => {
  const accepted = await promptInstall();
  if (accepted) {
    dismissed.value = true;
    localStorage.removeItem(STORAGE_KEY);
  }
};

onMounted(() => {
  // Check if previously dismissed
  try {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored) {
      const { dismissedUntil } = JSON.parse(stored);
      if (dismissedUntil && Date.now() < dismissedUntil) {
        dismissed.value = true;
      }
      else {
        // Dismissal expired, clean up
        localStorage.removeItem(STORAGE_KEY);
      }
    }
  }
  catch {
    // Ignore parse errors
  }

  // Track visit count
  try {
    const visits = parseInt(localStorage.getItem('pwa_visit_count') || '0', 10);
    visitCount.value = visits + 1;
    localStorage.setItem('pwa_visit_count', String(visitCount.value));
  }
  catch {
    visitCount.value = 1;
  }

  // Start session timer to trigger reactivity after MIN_SESSION_SECONDS
  setTimeout(() => {
    // Force reactivity update by touching sessionStartTime
    sessionStartTime.value = sessionStartTime.value;
  }, MIN_SESSION_SECONDS * 1000);
});
</script>
