<template>
  <!-- PWA Status Button: Install prompt or Update available indicator for topbar -->
  <TooltipProvider v-if="canInstall || needRefresh">
    <Tooltip>
      <TooltipTrigger as-child>
        <Button
          variant="outline"
          size="icon"
          class="relative rounded-full"
          @click="handleClick"
        >
          <!-- Update available (priority over install) -->
          <template v-if="needRefresh">
            <RefreshCwIcon class="h-4 w-4" />
            <span class="absolute -top-0.5 -right-0.5 flex h-2.5 w-2.5">
              <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-destructive opacity-75" />
              <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-destructive" />
            </span>
          </template>
          <!-- Install available -->
          <template v-else-if="canInstall">
            <DownloadIcon class="h-4 w-4" />
          </template>
          <span class="sr-only">
            {{ needRefresh ? $t('Atnaujinti programėlę') : $t('Įdiegti programėlę') }}
          </span>
        </Button>
      </TooltipTrigger>
      <TooltipContent>
        {{ needRefresh ? $t('Nauja versija prieinama') : $t('Įdiegti programėlę') }}
      </TooltipContent>
    </Tooltip>
  </TooltipProvider>

  <!-- Update Dialog (shown when user clicks update button in non-PWA mode) -->
  <Dialog v-model:open="showUpdateDialog">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-2">
          <RefreshCwIcon class="h-5 w-5" />
          {{ $t('Nauja versija prieinama') }}
        </DialogTitle>
        <DialogDescription>
          {{ $t('Atnaujinkite puslapį, kad galėtumėte naudotis naujausiomis funkcijomis ir pataisymais.') }}
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="flex-row gap-2 sm:justify-end">
        <Button variant="outline" @click="showUpdateDialog = false">
          {{ $t('Vėliau') }}
        </Button>
        <Button @click="handleUpdate">
          {{ $t('Atnaujinti') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { RefreshCwIcon, DownloadIcon } from 'lucide-vue-next';

import { usePWA } from '@/Composables/usePWA';
import { Button } from '@/Components/ui/button';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/Components/ui/tooltip';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';

const { canInstall, needRefresh, promptInstall, updateApp, dismissUpdate, isPWA } = usePWA();
const showUpdateDialog = ref(false);

function handleClick() {
  if (needRefresh.value) {
    // In PWA mode, update directly; in browser mode, show dialog
    if (isPWA.value) {
      updateApp();
    }
    else {
      showUpdateDialog.value = true;
    }
  }
  else if (canInstall.value) {
    promptInstall();
  }
}

function handleUpdate() {
  showUpdateDialog.value = false;
  dismissUpdate();
  updateApp();
}
</script>
