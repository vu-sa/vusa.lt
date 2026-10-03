<template>
  <Dialog v-model:open="dialogOpen">
    <DialogContent>
      <DialogHeader>
        <DialogTitle>{{ title }}</DialogTitle>
        <DialogDescription>
          {{ description }}
        </DialogDescription>
      </DialogHeader>

      <div v-if="requiresTypedConfirmation" class="space-y-3">
        <p class="text-sm text-muted-foreground">
          {{ $t('trash.type_to_confirm') }}
          <code class="rounded bg-muted px-1.5 py-0.5 font-mono text-sm text-foreground">
            {{ normalizedConfirmationText }}
          </code>
        </p>
        <div class="space-y-2">
          <Label for="dangerous-action-confirmation">
            {{ $t('trash.confirmation_label') }}
          </Label>
          <Input
            id="dangerous-action-confirmation"
            v-model="typedConfirmation"
            :placeholder="$t('trash.confirmation_placeholder')"
            autocomplete="off"
          />
        </div>
      </div>

      <DialogFooter>
        <Button variant="outline" @click="dialogOpen = false">
          {{ $t('trash.cancel') }}
        </Button>
        <Button
          variant="destructive"
          :disabled="!canConfirm"
          @click="confirmAction"
        >
          {{ confirmLabel }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';

const props = defineProps<{
  open: boolean;
  title: string;
  description: string;
  confirmationText?: string | null;
  confirmLabel: string;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
}>();

const typedConfirmation = ref('');

const dialogOpen = computed({
  get: () => props.open,
  set: (value: boolean) => emit('update:open', value),
});

const normalizedConfirmationText = computed(() => props.confirmationText?.trim() ?? '');

const requiresTypedConfirmation = computed(() => normalizedConfirmationText.value.length > 0);

const canConfirm = computed(() => {
  if (!requiresTypedConfirmation.value) {
    return true;
  }

  return typedConfirmation.value.trim() === normalizedConfirmationText.value;
});

const resetTypedConfirmation = () => {
  typedConfirmation.value = '';
};

const confirmAction = () => {
  if (!canConfirm.value) {
    return;
  }

  emit('confirm');
  dialogOpen.value = false;
};

watch(() => props.open, resetTypedConfirmation);
</script>
