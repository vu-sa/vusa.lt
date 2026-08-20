<template>
  <div data-slot="cadence-list" class="space-y-3">
    <p v-if="cadences.length === 0 && !adding" class="py-4 text-center text-sm text-muted-foreground">
      {{ emptyMessage }}
    </p>

    <ul v-else-if="cadences.length" class="divide-y divide-border rounded-md border border-border">
      <li v-for="cadence in cadences" :key="cadence.id" class="p-3">
        <CadenceRowForm
          v-if="editingId === cadence.id"
          :model-value="{ start_date: cadence.start_date, end_date: cadence.end_date }"
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
        :processing="processing"
        @save="value => emit('create', value)"
        @cancel="emit('cancel-add')"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { Pencil, Trash2 } from 'lucide-vue-next';

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

import CadenceRowForm, { type CadenceDraft } from './CadenceRowForm.vue';

export interface CadenceRow {
  id: string;
  institution_id: string | null;
  institution_name?: string | null;
  start_date: string;
  end_date: string;
  /** Derived server-side from the dates — see Cadence::getLabelAttribute(). */
  label: string;
}

withDefaults(defineProps<{
  cadences: CadenceRow[];
  emptyMessage: string;
  editingId?: string | null;
  adding?: boolean;
  processing?: boolean;
  /** Inherited cadences are shown for reference, not for editing. */
  readonly?: boolean;
  prefill?: CadenceDraft | null;
}>(), {
  editingId: null,
  adding: false,
  processing: false,
  readonly: false,
  prefill: null,
});

const emit = defineEmits<{
  edit: [id: string];
  'cancel-edit': [];
  'cancel-add': [];
  create: [value: CadenceDraft];
  update: [cadence: CadenceRow, value: CadenceDraft];
  delete: [cadence: CadenceRow];
}>();
</script>
