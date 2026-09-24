<template>
  <div class="space-y-3 border border-border bg-card p-3">
    <div class="flex items-center gap-2 text-sm font-medium text-foreground">
      <BarChart3 class="h-4 w-4 text-muted-foreground" />
      {{ $t('Sukurti apklausą') }}
    </div>

    <!-- Presets -->
    <div class="flex flex-wrap items-center gap-1.5">
      <span class="text-xs text-muted-foreground">{{ $t('Šablonai') }}:</span>
      <button
        v-for="preset in presets"
        :key="preset.key"
        type="button"
        class="border border-border bg-background px-2 py-0.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
        @click="applyPreset(preset.labels)"
      >
        {{ preset.label }}
      </button>
    </div>

    <!-- Options -->
    <div class="space-y-1.5">
      <div v-for="(option, index) in options" :key="index" class="flex items-center gap-1.5">
        <Input
          v-model="options[index]"
          :placeholder="$t('Variantas :n', { n: index + 1 })"
          class="h-8"
        />
        <button
          v-if="options.length > 2"
          type="button"
          class="flex size-9 shrink-0 items-center justify-center text-muted-foreground transition-colors hover:bg-accent hover:text-destructive pointer-coarse:size-11"
          :title="$t('Pašalinti')"
          @click="options.splice(index, 1)"
        >
          <X class="h-4 w-4" />
        </button>
      </div>
      <button
        v-if="options.length < 10"
        type="button"
        class="inline-flex items-center gap-1 text-xs text-brand transition-colors hover:underline"
        @click="options.push('')"
      >
        <Plus class="h-3.5 w-3.5" />
        {{ $t('Pridėti variantą') }}
      </button>
    </div>

    <!-- Settings -->
    <div class="space-y-2 text-sm">
      <label class="flex items-center gap-2">
        <Switch v-model="allowMultiple" />
        <span class="text-xs text-foreground">{{ $t('Galima rinktis kelis') }}</span>
      </label>
      <div class="space-y-1">
        <span class="text-xs text-muted-foreground">{{ $t('Uždaryti (nebūtina)') }}</span>
        <DateTimePicker
          v-model="closesAt"
          :placeholder="$t('Pasirinkite datą')"
          :min-date="minCloseDate"
        />
      </div>
    </div>

    <p v-if="error" class="text-xs text-destructive">
      {{ error }}
    </p>

    <!-- Question editor doubles as the submit control. -->
    <CommentComposer
      :mentionables
      :placeholder="$t('Apklausos klausimas…')"
      :submit-label="$t('Sukurti apklausą')"
      :submitting
      show-cancel
      @submit="onSubmit"
      @cancel="$emit('cancel')"
    />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { getLocalTimeZone, today } from '@internationalized/date';
import { trans as $t } from 'laravel-vue-i18n';
import { BarChart3, Plus, X } from 'lucide-vue-next';

import CommentComposer from '@/Components/Discussions/CommentComposer.vue';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import type { MentionableUser, PollDraft } from '@/Types/discussions';

withDefaults(defineProps<{
  mentionables?: MentionableUser[];
  submitting?: boolean;
}>(), {
  mentionables: () => [],
  submitting: false,
});

const emit = defineEmits<{ submit: [body: string, poll: PollDraft]; cancel: [] }>();

const options = ref<string[]>(['', '']);
const allowMultiple = ref(false);
const closesAt = ref<Date | null>(null);
const error = ref('');

// A poll cannot be set to close in the past.
const minCloseDate = today(getLocalTimeZone());

const presets = [
  { key: 'yesno', label: $t('Taip / Ne'), labels: [$t('Taip'), $t('Ne')] },
  { key: 'yesnoabstain', label: $t('Taip / Ne / Susilaikau'), labels: [$t('Taip'), $t('Ne'), $t('Susilaikau')] },
  { key: 'approve', label: $t('Pritarti / Atmesti'), labels: [$t('Pritarti'), $t('Atmesti'), $t('Susilaikau')] },
];

function applyPreset(labels: string[]) {
  options.value = [...labels];
}

function onSubmit(body: string) {
  const cleaned = options.value.map(label => label.trim()).filter(Boolean);

  if (cleaned.length < 2) {
    error.value = $t('Reikia bent dviejų variantų.');
    return;
  }

  error.value = '';

  emit('submit', body, {
    options: cleaned.map(label => ({ label })),
    allow_multiple: allowMultiple.value,
    closes_at: closesAt.value ? closesAt.value.toISOString() : null,
  });
}
</script>
