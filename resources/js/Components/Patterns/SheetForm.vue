<template>
  <div>
    <Sheet :open @update:open="requestOpenChange">
      <SheetContent
        data-slot="sheet-form"
        :side="isMobile ? 'bottom' : 'right'"
        :class="[
          'flex flex-col p-0',
          isMobile ? 'h-[92dvh] max-h-[92dvh]' : 'w-full sm:max-w-xl',
        ]"
      >
        <form class="flex h-full min-h-0 flex-col" @submit.prevent="emit('submit')">
          <SheetHeader class="border-b border-border px-6 py-4">
            <SheetTitle class="text-xl font-semibold tracking-tight text-foreground">
              {{ title }}
            </SheetTitle>
            <SheetDescription v-if="description" class="text-sm text-muted-foreground">
              {{ description }}
            </SheetDescription>
          </SheetHeader>

          <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
            <slot />

            <div v-if="$slots['danger-zone']" class="border-t border-border pt-5">
              <slot name="danger-zone" />
            </div>
          </div>

          <div
            :class="[
              'flex items-center justify-end gap-3 border-t border-border bg-card px-6 py-4',
              'pb-[max(1rem,env(safe-area-inset-bottom))]',
            ]"
          >
            <Button
              variant="ghost"
              type="button"
              class="u-touch"
              @click="requestOpenChange(false)"
            >
              {{ $t('Atšaukti') }}
            </Button>

            <Button
              variant="brand"
              type="submit"
              class="u-touch uppercase"
              :disabled="processing || disabled"
            >
              <Loader2 v-if="processing" class="size-4 animate-spin" />
              {{ saveLabel ?? $t('Išsaugoti') }}
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>

    <ConfirmDialog
      v-model:open="discardOpen"
      :title="$t('Uždaryti neišsaugojus?')"
      :description="$t('Tai, ką įvedei šiame lange, nebus išsaugota.')"
      :cancel-label="$t('Grįžti į redagavimą')"
      :confirm-label="$t('Uždaryti neišsaugojus')"
      destructive
      @confirm="close"
    />
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/Components/ui/sheet';
import { useIsMobile } from '@/Composables/useIsMobile';

const props = withDefaults(defineProps<{
  open: boolean;
  title: string;
  description?: string;
  saveLabel?: string;
  processing?: boolean;
  disabled?: boolean;
  /** Closing by the overlay, Esc or Atšaukti then asks before throwing edits away. */
  dirty?: boolean;
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
const discardOpen = ref(false);

const close = () => {
  emit('cancel');
  emit('update:open', false);
};

const requestOpenChange = (next: boolean) => {
  if (next) {
    emit('update:open', true);
    return;
  }

  if (props.dirty && !props.processing) {
    discardOpen.value = true;
    return;
  }

  close();
};
</script>
