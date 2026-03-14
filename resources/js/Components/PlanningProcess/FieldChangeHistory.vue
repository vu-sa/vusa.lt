<template>
  <Button size="icon-sm" :disabled="!changes?.length" variant="ghost" class="rounded-full" @click="showModal = true">
    <IFluentDocumentOnePage24Regular />
  </Button>
  <CardModal v-model:show="showModal" :segmented="{ content: 'soft' }" class="max-w-xl" :title="$t('Pakeitimų istorija')"
    @close="showModal = false">
    <div v-if="changes.length > 0" class="flex flex-col gap-4 max-h-[60vh] overflow-y-auto">
      <div v-for="change in changes" :key="change.id"
        class="border-b last:border-0 pb-4 last:pb-0 border-zinc-300 dark:border-zinc-700">
        <div class="space-y-2">
          <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
              <div
                :class="[
                  'flex items-center justify-center h-6 w-6 rounded-full shrink-0',
                  change.description === 'created'
                    ? 'bg-green-100 dark:bg-green-900/30'
                    : 'bg-blue-100 dark:bg-blue-900/30',
                ]"
              >
                <Plus v-if="change.description === 'created'" class="h-3.5 w-3.5 text-green-600 dark:text-green-400" />
                <Pencil v-else class="h-3.5 w-3.5 text-blue-600 dark:text-blue-400" />
              </div>
              <span class="text-sm font-medium">
                {{ change.description === 'created' ? $t('Sukurta') : $t('Atnaujinta') }}
              </span>
            </div>
            <span v-if="change.causer_name" class="text-sm text-zinc-500 dark:text-zinc-400 shrink-0">
              {{ change.causer_name }}
            </span>
          </div>

          <div v-if="getChangedFields(change).length > 0" class="ml-8 space-y-1.5">
            <div
              v-for="field in getChangedFields(change)"
              :key="field.key"
              class="rounded-md bg-zinc-50 dark:bg-zinc-800/50 px-3 py-2 text-sm"
            >
              <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ field.label }}</span>
              <div class="mt-0.5 text-zinc-500 dark:text-zinc-400">
                <template v-if="field.isRichText">
                  <span class="italic">{{ $t('Turinys atnaujintas') }}</span>
                </template>
                <template v-else-if="field.isDate">
                  {{ formatDateValue(field.oldValue, field.key) || '—' }}
                  <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
                  {{ formatDateValue(field.newValue, field.key) || '—' }}
                </template>
                <template v-else-if="field.isStage">
                  <Badge variant="outline" class="mr-1">{{ formatStage(field.oldValue) }}</Badge>
                  <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
                  <Badge variant="outline" class="ml-1">{{ formatStage(field.newValue) }}</Badge>
                </template>
                <template v-else>
                  {{ truncateText(field.oldValue) || '—' }}
                  <ArrowRight class="inline h-3.5 w-3.5 mx-1 text-zinc-400" />
                  {{ truncateText(field.newValue) || '—' }}
                </template>
              </div>
            </div>
          </div>

          <p :title="change.created_at" class="ml-8 text-xs text-zinc-400 dark:text-zinc-500">
            {{ formatRelativeTime(change.created_at) }}
          </p>
        </div>
      </div>
    </div>
    <div v-else>
      <p class="text-zinc-500">
        {{ $t("Pakeitimų nėra.") }}
      </p>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { trans as $t } from "laravel-vue-i18n";
import { ArrowRight, Plus, Pencil } from "lucide-vue-next";

import { formatRelativeTime } from "@/Utils/IntlTime";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import CardModal from "@/Components/Modals/CardModal.vue";

defineProps<{
  changes: App.Entities.FieldChange[];
}>();

const showModal = ref(false);

const HIDDEN_KEYS = ['updated_at', 'locked_at', 'tip_approved_media_id', 'mvp_approved_media_id'];

const DATE_KEYS = ['meeting_1_date', 'meeting_2_date', 'goal_approved_at', 'tip_approved_at', 'mvp_approved_at', 'expectations_submitted_at', 'reflection_submitted_at'];

const RICH_TEXT_KEYS = ['expectations_text', 'goal_text', 'meeting_1_notes', 'meeting_2_notes', 'reflection_text'];

const fieldLabels: Record<string, string> = {
  expectations_text: "Lūkesčiai",
  expectations_submitted_at: "Lūkesčių pateikimas",
  goal_text: "Tikslas",
  meeting_1_notes: "1 susitikimo pastabos",
  meeting_1_date: "1 susitikimo data",
  meeting_2_notes: "2 susitikimo pastabos",
  meeting_2_date: "2 susitikimo data",
  reflection_text: "Refleksija",
  reflection_submitted_at: "Refleksijos pateikimas",
  current_stage: "Etapas",
  goal_approved_at: "Tikslo patvirtinimas",
  tip_approved_at: "TĮP patvirtinimas",
  tip_approved_by: "TĮP patvirtino",
  mvp_approved_at: "MVP patvirtinimas",
  mvp_approved_by: "MVP patvirtino",
  moderator_user_id: "Moderatorius",
};

interface ChangedField {
  key: string;
  label: string;
  oldValue: string;
  newValue: string;
  isDate: boolean;
  isRichText: boolean;
  isStage: boolean;
}

const getChangedFields = (change: App.Entities.FieldChange): ChangedField[] => {
  return Object.keys(change.new)
    .filter((key) => !HIDDEN_KEYS.includes(key))
    .map((key) => ({
      key,
      label: fieldLabels[key] ?? key.replace(/_/g, ' '),
      oldValue: String(change.old?.[key] ?? ''),
      newValue: String(change.new[key] ?? ''),
      isDate: DATE_KEYS.includes(key),
      isRichText: RICH_TEXT_KEYS.includes(key),
      isStage: key === 'current_stage',
    }));
};

const formatStage = (value: string): string => {
  if (!value || value === 'null') return '—';
  return `${$t('Etapas')} ${value}`;
};

const DATE_ONLY_KEYS = ['meeting_1_date', 'meeting_2_date'];

const formatDateValue = (value: string | null, key?: string): string => {
  if (!value || value === 'null') return '';
  try {
    const date = new Date(value);
    if (key && DATE_ONLY_KEYS.includes(key)) {
      return date.toLocaleDateString('lt-LT');
    }
    return date.toLocaleDateString('lt-LT', {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  } catch {
    return value;
  }
};

const stripHtml = (html: string): string => html.replace(/<[^>]*>/g, '').trim();

const truncateText = (value: string | null): string => {
  if (!value || value === 'null') return '';
  const text = stripHtml(String(value));
  return text.length > 80 ? text.substring(0, 80) + '…' : text;
};
</script>
