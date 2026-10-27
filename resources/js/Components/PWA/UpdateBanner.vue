<template>
  <Transition
    enter-active-class="transition-[opacity,translate] duration-300 ease-out motion-reduce:transition-none"
    enter-from-class="translate-y-4 opacity-0"
    leave-active-class="transition-[opacity,translate] duration-200 ease-in motion-reduce:transition-none"
    leave-to-class="translate-y-4 opacity-0"
  >
    <div
      v-if="needRefresh && isPWA"
      data-slot="pwa-update-banner"
      role="status"
      :class="[
        'fixed inset-x-4 z-50 border border-border bg-background p-4 text-foreground',
        'bottom-[calc(var(--bottom-chrome-height,3.5rem)_+_0.75rem)] md:inset-x-auto md:right-4 md:bottom-[calc(var(--start-fm-clearance,0px)_+_1rem)] md:w-80',
      ]"
    >
      <div class="flex items-center justify-between gap-3">
        <span class="flex items-center gap-2 text-sm font-semibold">
          <RefreshCw class="size-4 shrink-0 text-brand" aria-hidden="true" />
          {{ $t('Nauja versija prieinama') }}
        </span>
        <div class="flex shrink-0 gap-2">
          <Button size="sm" variant="ghost" @click="dismissUpdate">
            {{ $t('Vėliau') }}
          </Button>
          <Button size="sm" variant="brand" voice="sentence" @click="updateApp">
            {{ $t('Atnaujinti') }}
          </Button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { RefreshCw } from '@lucide/vue';

import { usePWA } from '@/Composables/usePWA';
import { Button } from '@/Components/ui/button';

const { isPWA, needRefresh, updateApp, dismissUpdate } = usePWA();
</script>
