<template>
  <div class="space-y-4">
    <!-- Quantity selector (for partial approval) -->
    <div v-if="showQuantity && maxQuantity > 1" class="space-y-2">
      <Label for="approval-quantity">{{ $t("Kiekis") }}</Label>
      <div class="flex items-center gap-2">
        <NumberField
          id="approval-quantity"
          v-model="selectedQuantity"
          :min="1"
          :max="maxQuantity"
          class="w-fit"
        />
        <span class="text-sm text-muted-foreground">/ {{ maxQuantity }}</span>
      </div>
      <p v-if="selectedQuantity < maxQuantity" class="text-sm text-muted-foreground">
        {{ $t("Patvirtinsite") }} {{ selectedQuantity }} {{ $t("iš") }} {{ maxQuantity }}
      </p>
    </div>

    <!-- Notes input (optional) -->
    <div v-if="showNotes" class="space-y-2">
      <Label for="approval-notes">{{ $t("Pastabos") }} ({{ $t("neprivaloma") }})</Label>
      <Textarea id="approval-notes" v-model="notes" :placeholder="$t('Įveskite pastabas...')" rows="3" />
    </div>

    <!-- Action buttons -->
    <div class="flex flex-wrap items-center gap-2">
      <!-- Cancel button (for reservation owners) -->
      <Button v-if="allowCancel" :disabled="processing" variant="outline" size="sm" @click="handleDecision('cancelled')">
        <Spinner v-if="processing && currentDecision === 'cancelled'" />
        <IFluentArrowUndo24Regular v-else class="text-warning" />
        {{ $t("Atšaukti") }}
      </Button>

      <!-- Reject button -->
      <Button v-if="allowReject" :disabled="processing" variant="outline" size="sm" @click="handleDecision('rejected')">
        <Spinner v-if="processing && currentDecision === 'rejected'" />
        <IFluentDismiss24Regular v-else class="text-destructive" />
        {{ $t("Atmesti") }}
      </Button>

      <!-- Approve button -->
      <Button v-if="allowApprove" :disabled="processing" variant="default" size="sm" @click="handleDecision('approved')">
        <Spinner v-if="processing && currentDecision === 'approved'" />
        <IFluentCheckmark24Regular v-else />
        {{ $t("Patvirtinti") }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { router } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { NumberField } from '@/Components/ui/number-field';
import { Spinner } from '@/Components/ui/spinner';
import { Textarea } from '@/Components/ui/textarea';

type ApprovalDecision = 'approved' | 'rejected' | 'cancelled';

const props = withDefaults(
  defineProps<{
    approvableType: string;
    approvableId: string;
    allowApprove?: boolean;
    allowReject?: boolean;
    allowCancel?: boolean;
    showNotes?: boolean;
    showQuantity?: boolean;
    maxQuantity?: number;
    step?: number;
  }>(),
  {
    allowApprove: true,
    allowReject: true,
    allowCancel: false,
    showNotes: true,
    showQuantity: false,
    maxQuantity: 1,
    step: 1,
  },
);

const emit = defineEmits<{
  (e: 'success'): void;
  (e: 'error', error: string): void;
}>();

const notes = ref('');
const selectedQuantity = ref(props.maxQuantity);
const processing = ref(false);
const currentDecision = ref<ApprovalDecision | null>(null);

// Reset quantity when maxQuantity changes
watch(() => props.maxQuantity, (newMax) => {
  selectedQuantity.value = newMax;
});

const handleDecision = (decision: ApprovalDecision) => {
  processing.value = true;
  currentDecision.value = decision;

  const payload: Record<string, unknown> = {
    approvable_type: props.approvableType,
    approvable_id: props.approvableId,
    decision,
    notes: notes.value || null,
    step: props.step,
  };

  // Include quantity only for approve decisions with partial quantity
  if (decision === 'approved' && props.showQuantity && selectedQuantity.value !== props.maxQuantity) {
    payload.quantity = selectedQuantity.value;
  }

  router.post(
    route('approvals.store'),
    payload,
    {
      preserveScroll: true,
      onSuccess: () => {
        notes.value = '';
        selectedQuantity.value = props.maxQuantity;
        emit('success');
      },
      onError: (errors) => {
        emit('error', Object.values(errors).flat().join(', '));
      },
      onFinish: () => {
        processing.value = false;
        currentDecision.value = null;
      },
    },
  );
};
</script>
