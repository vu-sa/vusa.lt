<template>
  <div class="inline-flex items-center gap-1">
    <Button v-if="index > 0" size="icon-xs" variant="ghost" @click="$emit('moveUp')">
      <Icon icon="fluent:arrow-up-16-regular" class="size-3" />
    </Button>
    <Button v-if="index < length - 1" size="icon-xs" variant="ghost" @click="$emit('moveDown')">
      <Icon icon="fluent:arrow-down-16-regular" class="size-3" />
    </Button>
    <Link :href="editRoute">
      <Button size="icon-xs" variant="ghost">
        <Icon icon="fluent:edit-16-regular" class="size-3" />
      </Button>
    </Link>
    <AlertDialog>
      <AlertDialogTrigger as-child>
        <Button size="icon-xs" variant="destructive">
          <Icon icon="fluent:delete-16-regular" class="size-3" />
        </Button>
      </AlertDialogTrigger>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{{ $t('Ar tikrai?') }}</AlertDialogTitle>
          <AlertDialogDescription>
            {{ $t('Ar tikrai norite ištrinti šį elementą?') }}
          </AlertDialogDescription>
        </AlertDialogHeader>
        <AlertDialogFooter>
          <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
          <AlertDialogAction @click="$emit('delete')">
            {{ $t('forms.delete') }}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';

defineProps<{
  index: number;
  length: number;
  editRoute: string;
}>();

defineEmits(['moveUp', 'moveDown', 'delete']);
</script>
