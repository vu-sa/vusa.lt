<template>
  <div
    :class="['flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between', status === 'ended' && 'opacity-70']"
    data-slot="user-term-row"
  >
    <div class="min-w-0 space-y-1">
      <div class="flex flex-wrap items-center gap-2">
        <Link :href="route('duties.show', duty.id)" class="min-w-0 hover:underline">
          <DutyLabel :duty="{ name: duty.name, institution: duty.institution }" :holder :use-original-duty-name="Boolean(term.use_original_duty_name)" />
        </Link>
        <span v-if="term.via_dutiable_id" :class="chipClass">{{ $t('Ex-officio') }}</span>
        <span v-if="term.tenant_id" :class="chipClass">{{ $t('Deleguota') }}</span>
        <span v-if="status === 'upcoming'" :class="chipClass">{{ $t('Būsima') }}</span>
      </div>
      <p class="flex items-center gap-1 text-xs text-muted-foreground tabular-nums">
        <Calendar class="size-3 shrink-0" aria-hidden="true" />
        {{ tenure }}
      </p>
      <p v-if="term.additional_email" class="truncate text-xs text-muted-foreground">
        {{ $t('Kontaktinis') }}: <span class="text-foreground">{{ term.additional_email }}</span>
      </p>
      <p v-if="missingStudyProgram" class="flex items-center gap-1 text-xs text-status-attention" data-testid="missing-study-program">
        <TriangleAlert class="size-3 shrink-0" aria-hidden="true" />
        {{ $t('Ši pareigybė grupuoja kontaktus pagal studijų programą, bet priskyrimui ji nenurodyta.') }}
      </p>
    </div>

    <div v-if="canManage" class="flex shrink-0 items-center gap-2">
      <Button variant="ghost" size="sm" class="u-touch" @click="emit('edit')">
        <Edit3 class="size-3.5" aria-hidden="true" />
        {{ $t('Redaguoti') }}
      </Button>
      <Button v-if="canEnd" variant="ghost" size="sm" class="u-touch" @click="emit('end')">
        <CalendarCheck class="size-3.5" aria-hidden="true" />
        {{ $t('Baigti kadenciją') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Calendar, CalendarCheck, Edit3, TriangleAlert } from 'lucide-vue-next';
import { computed } from 'vue';

import { termStatus } from './occupancy';

import DutyLabel, { type DutyLabelHolder } from '@/Components/Duties/DutyLabel.vue';
import { Button } from '@/Components/ui/button';
import { formatStaticTime } from '@/Utils/IntlTime';

interface Term {
  id?: string;
  start_date?: string | null;
  end_date?: string | null;
  additional_email?: string | null;
  use_original_duty_name?: boolean | null;
  via_dutiable_id?: string | null;
  tenant_id?: number | null;
  study_program_id?: string | null;
}

const props = defineProps<{
  /** A duty of the person, with its institution and the term (`pivot`) they hold it in. */
  duty: {
    id: string | number;
    name: string;
    contacts_grouping?: string | null;
    institution?: { name: string; tenant?: { shortname?: string | null } | null } | null;
    pivot?: Term | null;
  };
  /** Whose duty it is: the name ending follows their pronouns. */
  holder?: DutyLabelHolder | null;
  canManage: boolean;
}>();

const emit = defineEmits<{
  (e: 'edit'): void;
  (e: 'end'): void;
}>();

const chipClass = 'border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground';

const term = computed<Term>(() => props.duty.pivot ?? {});
const status = computed(() => termStatus(term.value));

// Ex-officio terms follow their source, and a term that is over has nothing left to end.
const canEnd = computed(() => status.value === 'current' && !term.value.via_dutiable_id);

// A duty that groups its public contacts by programme has nothing to group this term under.
const missingStudyProgram = computed(() =>
  props.duty.contacts_grouping === 'study_program' && !term.value.study_program_id && status.value !== 'ended');

const tenure = computed(() => {
  const start = term.value.start_date;

  if (!start) {
    return '';
  }

  const label = (value: string) => formatStaticTime(new Date(value), { year: 'numeric', month: 'short' });
  const end = term.value.end_date;

  return `${label(start)} – ${end ? label(end) : $t('dabar')}`;
});
</script>
