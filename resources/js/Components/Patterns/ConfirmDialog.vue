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
        <AlertDialogAction
          :class="[
            'u-touch',
            // The default variant carries `dark:` twins, which beat unprefixed overrides.
            destructive && 'bg-destructive text-white hover:bg-destructive/90 dark:bg-destructive dark:text-white dark:hover:bg-destructive/90',
          ]"
          @click="emit('confirm')"
        >
          {{ confirmLabel }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';

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
</script>
