<template>
  <AlertDialog :open @update:open="emit('update:open', $event)">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ title }}</AlertDialogTitle>
        <AlertDialogDescription v-if="description">
          {{ description }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel class="u-touch">
          {{ cancelLabel ?? $t('Atšaukti') }}
        </AlertDialogCancel>
        <!-- Not AlertDialogAction: its built-in close runs before a passed @click, so callers that
             clear their target on close (`@update:open`) saw nothing left to confirm. -->
        <button
          type="button"
          data-slot="confirm-dialog-action"
          :class="cn(buttonVariants({ voice: 'brand' }), [
            'u-touch',
            // The default variant carries `dark:` twins, which beat unprefixed overrides.
            destructive && 'bg-destructive text-white hover:bg-destructive/90 dark:bg-destructive dark:text-white dark:hover:bg-destructive/90',
          ])"
          @click="confirm"
        >
          {{ confirmLabel }}
        </button>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import {
  AlertDialog,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';
import { buttonVariants } from '@/Components/ui/button';
import { cn } from '@/Utils/Shadcn/utils';

withDefaults(defineProps<{
  open: boolean;
  title: string;
  description?: string;
  /** A verb naming the result ("Ištrinti", "Baigti kadenciją"), not "Taip". */
  confirmLabel: string;
  cancelLabel?: string;
  destructive?: boolean;
}>(), {
  description: undefined,
  cancelLabel: undefined,
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
}>();

const confirm = () => {
  emit('confirm');
  emit('update:open', false);
};
</script>
