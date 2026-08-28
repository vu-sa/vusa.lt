<template>
  <div data-slot="cadence-list" class="space-y-3">
    <p v-if="cadences.length === 0 && !adding" class="py-4 text-center text-sm text-muted-foreground">
      {{ emptyMessage }}
    </p>

    <ul v-else-if="cadences.length" class="divide-y divide-border rounded-md border border-border">
      <li v-for="cadence in cadences" :key="cadence.id" class="p-3">
        <CadenceRowForm
          v-if="editingId === cadence.id"
          :model-value="{
            start_date: cadence.start_date,
            end_date: cadence.end_date,
            start_meeting_id: cadence.start_meeting?.id ?? null,
            end_meeting_id: cadence.end_meeting?.id ?? null,
          }"
          :institution-id="cadence.institution_id"
          :anchors="{ start: cadence.start_meeting ?? null, end: cadence.end_meeting ?? null }"
          :processing="processing"
          @save="value => emit('update', cadence, value)"
          @cancel="emit('cancel-edit')"
        />

        <div v-else class="flex flex-wrap items-center justify-between gap-3">
          <div class="flex min-w-0 items-center gap-3">
            <DateBadge :date="cadence.start_date" />
            <div class="min-w-0">
              <!-- Derived from the dates rather than typed: the two can never disagree. -->
              <span class="truncate font-medium">{{ cadence.label }}</span>
              <p class="truncate text-xs text-muted-foreground">
                {{ cadence.start_date }} — {{ cadence.end_date }}
              </p>
              <!-- Where the boundary came from, so a date nobody typed is not a mystery. -->
              <p v-if="cadence.start_meeting || cadence.end_meeting"
                class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-0.5 text-[11px] text-muted-foreground">
                <Link
                  v-for="anchor in [cadence.start_meeting, cadence.end_meeting].filter(Boolean)"
                  :key="anchor!.id"
                  :href="route('meetings.show', anchor!.id)"
                  class="inline-flex min-w-0 items-center gap-1 hover:underline"
                >
                  <CalendarClock class="size-3 shrink-0" />
                  <span class="truncate">{{ anchor!.title ?? $t('cadences.fields.anchor_untitled') }}</span>
                  <!-- Named only when the sitting is another body's; its own would be noise. -->
                  <span v-if="foreignInstitution(cadence, anchor!)" class="shrink-0 opacity-70">
                    ({{ foreignInstitution(cadence, anchor!) }})
                  </span>
                </Link>
              </p>
            </div>
          </div>

          <div v-if="!readonly" class="flex shrink-0 items-center gap-1">
            <Button type="button" size="icon-xs" variant="ghost" :aria-label="$t('cadences.actions.edit')"
              @click="emit('edit', cadence.id)">
              <Pencil class="size-3.5" />
            </Button>

            <AlertDialog>
              <AlertDialogTrigger as-child>
                <Button type="button" size="icon-xs" variant="ghost" :aria-label="$t('cadences.actions.delete')"
                  class="text-destructive hover:text-destructive">
                  <Trash2 class="size-3.5" />
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>{{ $t('cadences.delete.title') }}</AlertDialogTitle>
                  <AlertDialogDescription>
                    {{ $t('cadences.delete.description', { label: cadence.label }) }}
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>{{ $t('cadences.delete.cancel') }}</AlertDialogCancel>
                  <AlertDialogAction
                    class="bg-destructive text-destructive-foreground hover:bg-destructive/90"
                    @click="emit('delete', cadence)"
                  >
                    {{ $t('cadences.delete.confirm') }}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>
      </li>
    </ul>

    <div v-if="adding" class="rounded-md border border-dashed border-border p-3">
      <CadenceRowForm
        :model-value="prefill"
        :institution-id="institutionId"
        :processing="processing"
        @save="value => emit('create', value)"
        @cancel="emit('cancel-add')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { CalendarClock, Pencil, Trash2 } from 'lucide-vue-next';
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

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
import { Button } from '@/Components/ui/button';
import { DateBadge } from '@/Components/Patterns';

import CadenceRowForm, { type CadenceAnchor, type CadenceDraft } from './CadenceRowForm.vue';

export interface CadenceRow {
  id: string;
  institution_id: string | null;
  institution_name?: string | null;
  start_date: string;
  end_date: string;
  /** The sittings the boundaries were taken from, when they were not typed by hand. */
  start_meeting?: CadenceAnchor | null;
  end_meeting?: CadenceAnchor | null;
  /** Derived server-side from the dates — see Cadence::getLabelAttribute(). */
  label: string;
}

withDefaults(defineProps<{
  cadences: CadenceRow[];
  emptyMessage: string;
  /** Scope a new row belongs to; null is the global ladder, which anchors to nothing. */
  institutionId?: string | null;
  editingId?: string | null;
  adding?: boolean;
  processing?: boolean;
  /** Inherited cadences are shown for reference, not for editing. */
  readonly?: boolean;
  prefill?: CadenceDraft | null;
}>(), {
  institutionId: null,
  editingId: null,
  adding: false,
  processing: false,
  readonly: false,
  prefill: null,
});

/** A boundary may be taken from another institution's sitting — say whose when it is. */
function foreignInstitution(cadence: CadenceRow, anchor: CadenceAnchor): string | null {
  return anchor.institution_id && anchor.institution_id !== cadence.institution_id
    ? (anchor.institution_name ?? null)
    : null;
}

const emit = defineEmits<{
  edit: [id: string];
  'cancel-edit': [];
  'cancel-add': [];
  create: [value: CadenceDraft];
  update: [cadence: CadenceRow, value: CadenceDraft];
  delete: [cadence: CadenceRow];
}>();
</script>
