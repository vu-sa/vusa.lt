<template>
  <Dialog :open="isOpen" @update:open="$emit('update:open', $event)">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          {{ message }}
        </DialogDescription>
      </DialogHeader>
      <DialogFooter>
        <DialogClose as-child>
          <Button variant="outline" @click="$emit('cancel')">
            {{ $t('Cancel') }}
          </Button>
        </DialogClose>
        <Button 
          variant="destructive" 
          :disabled="isDeleting"
          @click="$emit('confirm')"
        >
          <Loader2Icon v-if="isDeleting" class="mr-2 h-4 w-4 animate-spin" />
          {{ $t('Delete') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Loader2Icon } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogClose,
} from '@/Components/ui/dialog';

defineProps<{
  isOpen: boolean;
  title: string;
  message: string;
  isDeleting?: boolean;
}>();

defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
  (e: 'cancel'): void;
}>();
</script>