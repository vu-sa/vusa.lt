<template>
  <AlertDialog :open @update:open="$emit('update:open', $event)">
    <!-- Neutralise the shared slide-in so the dialog simply fades/zooms in place
         (the default top-left slide reads as jittery for a centered confirm). -->
    <AlertDialogContent
      class="[--tw-enter-translate-x:0]! [--tw-enter-translate-y:0]! [--tw-exit-translate-x:0]! [--tw-exit-translate-y:0]!"
    >
      <AlertDialogHeader>
        <AlertDialogTitle class="flex items-center gap-2">
          <TriangleAlert class="h-5 w-5 text-amber-500" />
          {{ $t('access_change.title') }}
        </AlertDialogTitle>
        <AlertDialogDescription as-child>
          <div class="space-y-3 text-sm">
            <p>{{ $t('access_change.intro') }}</p>

            <ul class="list-disc space-y-1 pl-5 font-medium text-amber-600 dark:text-amber-500">
              <li v-for="role in report?.lostRoles ?? []" :key="role">
                {{ role }}
              </li>
            </ul>

            <p>{{ $t('access_change.note') }}</p>
            <p>{{ $t('access_change.confirm_prompt') }}</p>
          </div>
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel @click="$emit('cancel')">
          {{ $t('access_change.cancel') }}
        </AlertDialogCancel>
        <AlertDialogAction
          class="bg-red-500 text-white hover:bg-red-500/90 dark:bg-red-900 dark:hover:bg-red-900/90"
          @click="$emit('confirm')"
        >
          {{ $t('access_change.proceed') }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { TriangleAlert } from 'lucide-vue-next';

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
import type { AccessChangeWarning } from '@/Composables/useAccessChangeGuard';

defineProps<{
  open: boolean;
  report: AccessChangeWarning | null;
}>();

defineEmits<{
  'update:open': [value: boolean];
  'confirm': [];
  'cancel': [];
}>();
</script>
