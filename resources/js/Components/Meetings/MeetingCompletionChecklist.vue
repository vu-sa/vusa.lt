<template>
  <section
    v-if="actions.length"
    id="meeting-completion"
    class="border border-status-attention-border bg-status-attention-surface p-4"
    aria-labelledby="meeting-completion-title"
  >
    <div class="flex items-start gap-3">
      <TriangleAlert class="mt-0.5 size-5 shrink-0 text-status-attention" aria-hidden="true" />
      <div class="min-w-0 flex-1">
        <h2 id="meeting-completion-title" class="font-semibold text-foreground">
          {{ $t('Papildyk posėdžio įrašą') }}
        </h2>
        <p class="mt-1 text-sm text-muted-foreground">
          {{ $t('Užbaik šiuos veiksmus, kad posėdžio įrašas būtų pilnas.') }}
        </p>

        <div class="mt-4 divide-y divide-status-attention-border border-y border-status-attention-border">
          <button
            v-for="action in actions"
            :key="actionKey(action)"
            type="button"
            class="u-touch flex w-full items-center gap-3 py-3 text-left text-sm font-medium text-foreground hover:text-primary"
            @click="$emit('select', action)"
          >
            <span class="flex size-7 shrink-0 items-center justify-center border border-status-attention-border text-xs font-semibold text-status-attention">
              {{ action.position ?? 1 }}
            </span>
            <span class="min-w-0 flex-1">
              <span class="block">{{ actionTitle(action) }}</span>
              <span v-if="action.title" class="block truncate text-xs font-normal text-muted-foreground">
                {{ action.title }}
              </span>
            </span>
            <ChevronRight class="size-4 shrink-0 text-muted-foreground" />
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, TriangleAlert } from 'lucide-vue-next';

export type MeetingMissingAction
  = | { type: 'agenda_missing' }
    | { type: 'agenda_item_type_missing'; agenda_item_id: string; title: string; position: number }
    | {
      type: 'agenda_item_vote_missing';
      agenda_item_id: string;
      title: string;
      position: number;
      missing_fields: Array<'decision' | 'student_vote' | 'student_benefit'>;
    };

defineProps<{
  actions: MeetingMissingAction[];
}>();

defineEmits<{
  select: [action: MeetingMissingAction];
}>();

const actionKey = (action: MeetingMissingAction) => `${action.type}-${'agenda_item_id' in action ? action.agenda_item_id : 'meeting'}`;

const actionTitle = (action: MeetingMissingAction): string => {
  if (action.type === 'agenda_missing') {
    return $t('Pridėti darbotvarkę');
  }
  if (action.type === 'agenda_item_type_missing') {
    return $t('Nurodyti klausimo tipą');
  }
  return $t('Užfiksuoti balsavimo rezultatą');
};
</script>
