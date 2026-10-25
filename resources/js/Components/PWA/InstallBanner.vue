<template>
  <Transition
    enter-active-class="transition-[opacity,translate] duration-300 ease-out motion-reduce:transition-none"
    enter-from-class="translate-y-4 opacity-0"
    leave-active-class="transition-[opacity,translate] duration-200 ease-in motion-reduce:transition-none"
    leave-to-class="translate-y-4 opacity-0"
  >
    <div
      v-if="shouldShow"
      data-slot="pwa-install-banner"
      :class="[
        'fixed inset-x-4 z-50 mx-auto max-w-md border border-border bg-background p-4 text-foreground md:hidden',
        'bottom-[calc(3.5rem_+_env(safe-area-inset-bottom,0px)_+_0.75rem)]',
      ]"
    >
      <div class="flex items-start gap-3">
        <Download class="mt-0.5 size-5 shrink-0 text-brand" aria-hidden="true" />
        <div class="flex-1 space-y-1">
          <p class="font-semibold">
            {{ $t('pwa.install_title') }}
          </p>
          <p class="text-sm text-muted-foreground">
            {{ $t('pwa.install_description') }}
          </p>
        </div>
      </div>
      <div class="mt-3 flex justify-end gap-2">
        <Button variant="ghost" size="sm" @click="dismiss">
          {{ $t('Vėliau') }}
        </Button>
        <Button variant="brand" voice="sentence" size="sm" @click="install">
          {{ $t('Įdiegti') }}
        </Button>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { useStorage, useTimeoutFn } from '@vueuse/core';
import { Download } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';

import { Button } from '@/Components/ui/button';
import { usePWA } from '@/Composables/usePWA';

const MIN_VISITS = 3;
const MIN_SESSION_SECONDS = 120;

const { canInstall, promptInstall, isPWA } = usePWA();

const promptDismissed = useStorage<string | null>('pwa_install_prompt', null);
const visitCount = useStorage<number>('pwa_visit_count', 0);
const sessionDismissed = ref(false);
const sessionLongEnough = ref(false);

useTimeoutFn(() => {
  sessionLongEnough.value = true;
}, MIN_SESSION_SECONDS * 1000);

const shouldShow = computed(() => !isPWA.value
  && canInstall.value
  && !sessionDismissed.value
  && promptDismissed.value !== 'dismissed'
  && visitCount.value >= MIN_VISITS
  && sessionLongEnough.value);

const dismiss = () => {
  sessionDismissed.value = true;
  promptDismissed.value = 'dismissed';
};

const install = async () => {
  const accepted = await promptInstall();
  if (accepted) {
    sessionDismissed.value = true;
    promptDismissed.value = null;
  }
};

onMounted(() => {
  visitCount.value = (Number.isFinite(visitCount.value) ? visitCount.value : 0) + 1;
});
</script>
