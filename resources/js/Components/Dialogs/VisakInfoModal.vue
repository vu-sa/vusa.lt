<template>
  <Dialog :open @update:open="$emit('close')">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle class="flex items-center gap-3">
          <span class="font-black text-2xl tracking-tight bg-gradient-to-r from-zinc-900 via-zinc-800 to-vusa-red dark:from-zinc-100 dark:via-zinc-200 dark:to-vusa-red bg-clip-text text-transparent">
            ViSAK
          </span>
        </DialogTitle>
        <DialogDescription as="div" class="text-left space-y-3 pt-2">
          <p class="font-medium text-foreground">
            {{ $t('visak.full_name') }}
          </p>
          <p>
            {{ $t('visak.description') }}
          </p>
          <div class="pt-2 space-y-2">
            <p class="text-sm font-medium text-foreground">
              {{ $t('visak.features_title') }}
            </p>
            <ul class="text-sm space-y-1.5 list-disc list-inside text-muted-foreground">
              <li>{{ $t('visak.feature_1') }}</li>
              <li>{{ $t('visak.feature_2') }}</li>
              <li>{{ $t('visak.feature_3') }}</li>
              <li>{{ $t('visak.feature_4') }}</li>
            </ul>
          </div>
        </DialogDescription>
      </DialogHeader>
      <DialogFooter class="sm:justify-between gap-2">
        <Button variant="outline" size="sm" class="gap-2" @click="handleStartTour">
          <PlayCircle class="h-4 w-4" />
          {{ $t('visak.start_tour') }}
        </Button>
        <DialogClose as-child>
          <Button variant="default" size="sm">
            {{ $t('Supratau') }}
          </Button>
        </DialogClose>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { PlayCircle } from 'lucide-vue-next';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

defineProps<{
  open: boolean;
}>();

const emit = defineEmits<{
  'close': [];
  'start-tour': [];
}>();

const handleStartTour = () => {
  emit('close');
  emit('start-tour');
};
</script>
