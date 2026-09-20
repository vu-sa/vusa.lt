<template>
  <Sheet :open @update:open="emit('update:open', $event)">
    <SheetContent
      :side="isMobile ? 'bottom' : 'right'"
      :class="[
        'flex flex-col p-0',
        isMobile ? 'h-[92vh] max-h-[92vh]' : 'sm:max-w-xl w-full',
      ]"
    >
      <form class="flex h-full flex-col" @submit.prevent="emit('submit')">
        <!-- Header -->
        <SheetHeader class="border-b border-border px-6 py-4">
          <SheetTitle class="text-xl font-semibold tracking-tight text-foreground">
            {{ title }}
          </SheetTitle>
          <SheetDescription v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
          </SheetDescription>
        </SheetHeader>

        <!-- Body -->
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">
          <slot />
        </div>

        <!-- Footer -->
        <div class="border-t border-border bg-card px-6 py-4 flex items-center justify-between gap-3">
          <div>
            <slot name="footer-extra" />
          </div>

          <div class="flex items-center gap-3">
            <Button
              variant="ghost"
              type="button"
              class="u-touch"
              @click="handleCancel"
            >
              {{ $t('Atšaukti') }}
            </Button>

            <Button
              variant="brand"
              type="submit"
              class="u-touch uppercase"
              :disabled="processing || disabled"
            >
              <Loader2 v-if="processing" class="mr-2 size-4 animate-spin" />
              {{ saveLabel ?? $t('Išsaugoti') }}
            </Button>
          </div>
        </div>
      </form>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/Components/ui/sheet';
import { useIsMobile } from '@/Composables/useIsMobile';

withDefaults(defineProps<{
  open: boolean;
  title: string;
  description?: string;
  saveLabel?: string;
  processing?: boolean;
  disabled?: boolean;
}>(), {
  description: undefined,
  saveLabel: undefined,
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'submit'): void;
  (e: 'cancel'): void;
}>();

const isMobile = useIsMobile();

const handleCancel = () => {
  emit('cancel');
  emit('update:open', false);
};
</script>
