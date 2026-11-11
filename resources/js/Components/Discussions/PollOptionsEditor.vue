<template>
  <div class="space-y-2 border-t border-border/70 px-3.5 py-3" data-slot="poll-options-editor">
    <ol class="space-y-1.5">
      <li v-for="(_, index) in options" :key="index" class="flex items-center gap-2">
        <span class="w-4 shrink-0 text-right text-xs tabular-nums text-muted-foreground" aria-hidden="true">{{ index + 1 }}</span>
        <Input
          v-model="options[index]"
          size="sm"
          :aria-label="$t('discussions.poll.option_placeholder', { n: String(index + 1) })"
          :placeholder="$t('discussions.poll.option_placeholder', { n: String(index + 1) })"
          data-testid="poll-option"
          @keydown.enter.prevent="addAfter(index)"
        />
        <button
          v-if="options.length > 2"
          type="button"
          class="flex size-8 shrink-0 items-center justify-center text-muted-foreground transition-colors hover:bg-accent hover:text-foreground pointer-coarse:size-11"
          :aria-label="$t('discussions.poll.remove_option')"
          @click="options.splice(index, 1)"
        >
          <X class="size-4" />
        </button>
      </li>
    </ol>

    <div class="flex flex-wrap items-center gap-x-1 gap-y-1 pl-5">
      <Button v-if="options.length < MAX_OPTIONS" type="button" variant="ghost" size="xs" class="text-muted-foreground" @click="options.push('')">
        <Plus class="size-3.5" />
        {{ $t('discussions.poll.add_option') }}
      </Button>
      <Button v-if="isBlank" type="button" variant="ghost" size="xs" class="text-muted-foreground" data-testid="poll-yes-no" @click="options = [$t('Taip'), $t('Ne')]">
        {{ $t('Taip / Ne') }}
      </Button>
      <Button
        type="button"
        variant="ghost"
        size="xs"
        class="ml-auto text-muted-foreground"
        :aria-expanded="settingsOpen"
        data-testid="poll-settings"
        @click="settingsOpen = !settingsOpen"
      >
        <Settings2 class="size-3.5" />
        {{ $t('discussions.poll.settings') }}
        <span v-if="settingsSummary" class="font-normal">· {{ settingsSummary }}</span>
      </Button>
    </div>

    <div v-if="settingsOpen" class="grid gap-3 pl-5 sm:grid-cols-[auto_minmax(0,18rem)] sm:items-center sm:justify-between" data-slot="poll-settings">
      <label class="flex items-center gap-2 text-sm">
        <Switch v-model="allowMultiple" />
        {{ $t('discussions.poll.allow_multiple') }}
      </label>
      <div class="flex min-w-0 items-center gap-2">
        <span class="shrink-0 text-xs text-muted-foreground">{{ $t('discussions.poll.closes_at') }}</span>
        <DateTimePicker
          v-model="closesAt"
          variant="popover"
          clearable
          :placeholder="$t('discussions.poll.never_closes')"
          :min-date="minCloseDate"
          class="min-w-0"
          data-testid="poll-closes-at"
        />
      </div>
    </div>

    <p v-if="error" class="pl-5 text-xs text-destructive" role="alert">{{ error }}</p>
  </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { getLocalTimeZone, today } from '@internationalized/date';
import { trans as $t } from 'laravel-vue-i18n';
import { Plus, Settings2, X } from '@lucide/vue';

import { Button } from '@/Components/ui/button';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Switch } from '@/Components/ui/switch';
import { useDateFormatter } from '@/Composables/useDateFormatter';
import type { PollDraft } from '@/Types/discussions';

const MAX_OPTIONS = 10;

defineProps<{ error?: string }>();
const emit = defineEmits<{ dirty: [dirty: boolean] }>();

const options = ref<string[]>(['', '']);
const allowMultiple = ref(false);
const closesAt = ref<Date | null>(null);
const settingsOpen = ref(false);
const minCloseDate = today(getLocalTimeZone());
const { formatNearDate } = useDateFormatter();

const isBlank = computed(() => options.value.every(option => option.trim() === ''));
watch(() => !isBlank.value || allowMultiple.value || closesAt.value !== null, dirty => emit('dirty', dirty), { immediate: true });
const settingsSummary = computed(() => [
  allowMultiple.value ? $t('discussions.poll.multiple_short') : '',
  closesAt.value ? formatNearDate(closesAt.value.toISOString()) : '',
].filter(Boolean).join(', '));

function addAfter(index: number) {
  if (index === options.value.length - 1 && options.value.length < MAX_OPTIONS) {
    options.value.push('');
  }
}

/** Null until two options are filled in. */
function draft(): PollDraft | null {
  const labels = options.value.map(option => option.trim()).filter(Boolean);
  if (labels.length < 2) return null;
  return {
    options: labels.map(label => ({ label })),
    allow_multiple: allowMultiple.value,
    closes_at: closesAt.value ? closesAt.value.toISOString() : null,
  };
}

function reset() {
  options.value = ['', ''];
  allowMultiple.value = false;
  closesAt.value = null;
  settingsOpen.value = false;
}

defineExpose({ draft, reset });
</script>
