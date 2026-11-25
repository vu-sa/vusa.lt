<template>
  <AlertDialog :open @update:open="emit('update:open', $event)">
    <AlertDialogContent @open-auto-focus="focusTypedConfirmation">
      <AlertDialogHeader>
        <AlertDialogTitle>{{ title }}</AlertDialogTitle>
        <AlertDialogDescription v-if="description">
          {{ description }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <div v-if="typedConfirmationTarget" ref="typedConfirmationField" class="flex flex-col gap-2">
        <p class="text-sm text-muted-foreground">
          {{ $t('trash.type_to_confirm') }}
          <code class="bg-muted px-1.5 py-0.5 font-mono break-all text-foreground select-all">{{ typedConfirmationTarget }}</code>
        </p>
        <Label :for="typedConfirmationId">{{ $t('trash.confirmation_label') }}</Label>
        <Input
          :id="typedConfirmationId"
          v-model="typedConfirmation"
          :placeholder="$t('trash.confirmation_placeholder')"
          autocomplete="off"
          @keydown.enter.prevent="confirm"
        />
      </div>
      <slot />
      <AlertDialogFooter>
        <AlertDialogCancel class="u-touch">
          {{ cancelLabel ?? $t('Atšaukti') }}
        </AlertDialogCancel>
        <!-- Not AlertDialogAction: its built-in close runs before a passed @click, so callers that
             clear their target on close (`@update:open`) saw nothing left to confirm. -->
        <button
          type="button"
          :disabled="confirmDisabled || !typedConfirmationMatches"
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
import { computed, ref, useId, useTemplateRef, watch } from 'vue';
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
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { cn } from '@/Utils/Shadcn/utils';

const props = withDefaults(defineProps<{
  open: boolean;
  title: string;
  description?: string;
  /** A verb naming the result ("Ištrinti", "Užbaigti pareigas"), not "Taip". */
  confirmLabel: string;
  cancelLabel?: string;
  destructive?: boolean;
  confirmDisabled?: boolean;
  /** For irreversible deletes: confirm stays disabled until this exact text is typed. */
  confirmationText?: string | null;
}>(), {
  description: undefined,
  cancelLabel: undefined,
  confirmationText: null,
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'confirm'): void;
}>();

const typedConfirmation = ref('');
const typedConfirmationTarget = computed(() => props.confirmationText?.trim() ?? '');
const typedConfirmationMatches = computed(() => !typedConfirmationTarget.value || typedConfirmation.value.trim() === typedConfirmationTarget.value);

const typedConfirmationId = useId();
const typedConfirmationField = useTemplateRef<HTMLElement>('typedConfirmationField');

watch([() => props.open, () => props.confirmationText], () => {
  typedConfirmation.value = '';
});

/** An alert dialog focuses Cancel; a typed confirmation starts in its input instead. */
const focusTypedConfirmation = (event: Event) => {
  const input = typedConfirmationField.value?.querySelector('input');
  if (!input) return;
  event.preventDefault();
  input.focus();
};

const confirm = () => {
  if (props.confirmDisabled || !typedConfirmationMatches.value) return;
  emit('confirm');
  emit('update:open', false);
};
</script>
