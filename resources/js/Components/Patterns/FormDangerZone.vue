<template>
  <div data-slot="form-danger-zone" class="contents">
    <Button type="button" variant="destructive-outline" :disabled @click="open = true">
      <Trash2 aria-hidden="true" />
      {{ label }}
    </Button>
    <ConfirmDialog
      v-model:open="open"
      :title
      :description
      :confirm-label="confirmLabel ?? $t('Ištrinti')"
      destructive
      @confirm="emit('confirm')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2 } from '@lucide/vue';

import { Button } from '@/Components/ui/button';

import ConfirmDialog from './ConfirmDialog.vue';

/** A form's `#danger-zone` action: an outlined destructive button behind a confirmation. */
withDefaults(defineProps<{
  /** The button's verb ("Ištrinti pareigybę"). */
  label: string;
  title: string;
  description?: string;
  confirmLabel?: string;
  disabled?: boolean;
}>(), {
  description: undefined,
  confirmLabel: undefined,
});

const emit = defineEmits<{ confirm: [] }>();

const open = ref(false);
</script>
