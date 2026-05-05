<template>
  <!-- PWA Update Available Banner -->
  <Transition
    enter-active-class="transition-transform duration-300 ease-out"
    enter-from-class="translate-y-full"
    enter-to-class="translate-y-0"
    leave-active-class="transition-transform duration-200 ease-in"
    leave-from-class="translate-y-0"
    leave-to-class="translate-y-full"
  >
    <div
      v-if="needRefresh && isPWA"
      class="fixed bottom-20 left-4 right-4 md:left-auto md:right-4 md:bottom-4 md:w-80 z-50 bg-primary text-primary-foreground rounded-lg shadow-lg p-4"
    >
      <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <RefreshCwIcon class="h-5 w-5" />
          <span class="text-sm font-medium">{{ $t('Nauja versija prieinama') }}</span>
        </div>
        <div class="flex gap-2">
          <Button size="sm" variant="secondary" @click="dismissUpdate">
            {{ $t('Vėliau') }}
          </Button>
          <Button size="sm" variant="default" class="bg-white text-primary hover:bg-white/90" @click="updateApp">
            {{ $t('Atnaujinti') }}
          </Button>
        </div>
      </div>
    </div>
  </Transition>
</template>

<script setup lang="ts">
import { RefreshCwIcon } from 'lucide-vue-next';

import { usePWA } from '@/Composables/usePWA';
import { Button } from '@/Components/ui/button';

const { isPWA, needRefresh, updateApp, dismissUpdate } = usePWA();
</script>
