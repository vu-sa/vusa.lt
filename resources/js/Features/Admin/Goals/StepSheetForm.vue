<template>
  <SheetForm
    v-model:open="open"
    :title="step ? $t('goals.steps.edit') : $t('goals.steps.add')"
    :description="$t('goals.steps.sheet_description')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @cancel="reset"
    @submit="submit"
  >
    <div class="space-y-2">
      <Label for="step-title">{{ capitalize($t('entities.step.title')) }}</Label>
      <MultiLocaleInput id="step-title" v-model:input="form.title" />
      <p v-if="form.errors['title.lt']" class="text-sm text-status-danger">
        {{ form.errors['title.lt'] }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="step-happened-on">{{ capitalize($t('entities.step.happened_on')) }}</Label>
      <DatePicker id="step-happened-on" :model-value="form.happened_on" @update:model-value="setDate" />
      <p v-if="form.errors.happened_on" class="text-sm text-status-danger">
        {{ form.errors.happened_on }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="step-performers">{{ $t('goals.steps.done_by') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span></Label>
      <MultiCollectionSelectDialog
        v-model:open="performersOpen"
        multiple
        allow-empty
        :collections="['users']"
        :title="$t('goals.steps.done_by')"
        :initial-hits="performerHits"
        @confirm="hits => (performerHits = hits)"
      >
        <template #trigger>
          <Button id="step-performers" type="button" variant="outline" voice="sentence" :class="['w-full justify-between font-normal', fieldSurfaceClass]">
            <span class="truncate" :class="{ 'text-muted-foreground': !performerHits.length }">
              {{ performerHits.length ? performerHits.map(hit => hit.title).join(', ') : $t('goals.steps.pick_people') }}
            </span>
            <Users class="size-4 shrink-0 opacity-50" aria-hidden="true" />
          </Button>
        </template>
      </MultiCollectionSelectDialog>
      <p class="text-xs text-muted-foreground">
        {{ $t('goals.steps.done_by_hint') }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="step-description">
        {{ capitalize($t('entities.step.description')) }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span>
      </Label>
      <MultiLocaleInput id="step-description" v-model:input="form.description" input-type="textarea" />
    </div>

    <div class="space-y-2">
      <Label for="step-reference">{{ $t('goals.steps.reference') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span></Label>
      <div class="flex gap-2">
        <MultiCollectionSelectDialog
          v-model:open="referenceOpen"
          allow-empty
          :collections="['agenda_items', 'documents']"
          :title="$t('goals.steps.reference')"
          :initial-hits="referenceHit ? [referenceHit] : []"
          @confirm="hits => setReference(hits[0] ?? null)"
        >
          <template #trigger>
            <Button id="step-reference" type="button" variant="outline" voice="sentence" :class="['min-w-0 flex-1 justify-between font-normal', fieldSurfaceClass]">
              <span class="truncate" :class="{ 'text-muted-foreground': !referenceLabel }">
                {{ referenceLabel || $t('goals.steps.pick_reference') }}
              </span>
              <Link2 class="size-4 shrink-0 opacity-50" aria-hidden="true" />
            </Button>
          </template>
        </MultiCollectionSelectDialog>
        <Button v-if="referenceLabel" type="button" variant="ghost" size="icon" :aria-label="$t('Išvalyti')" class="pointer-coarse:size-11" @click="setReference(null)">
          <X class="size-4" />
        </Button>
      </div>
      <p v-if="form.errors.agenda_item_id || form.errors.document_id" class="text-sm text-status-danger">
        {{ form.errors.agenda_item_id || form.errors.document_id }}
      </p>
    </div>

    <div class="space-y-2">
      <Label for="step-url">{{ $t('goals.steps.url') }} <span class="text-muted-foreground">({{ $t('neprivaloma') }})</span></Label>
      <Input id="step-url" v-model="form.url" type="url" inputmode="url" placeholder="https://" :class="fieldSurfaceClass" />
      <p v-if="form.errors.url" class="text-sm text-status-danger">
        {{ form.errors.url }}
      </p>
    </div>

    <div v-if="linkOptions.length" class="space-y-2">
      <Label for="step-link">{{ parent.type === 'goal' ? $t('goals.steps.also_counts_for_problem') : $t('goals.steps.also_counts_for_goal') }}</Label>
      <Select v-model="linkIdString">
        <SelectTrigger id="step-link">
          <SelectValue />
        </SelectTrigger>
        <SelectContent>
          <SelectItem :value="NONE">
            {{ $t('goals.steps.none') }}
          </SelectItem>
          <SelectItem v-for="option in linkOptions" :key="option.id" :value="option.id">
            {{ option.title }}
          </SelectItem>
        </SelectContent>
      </Select>
      <p v-if="form.errors[linkField]" class="text-sm text-status-danger">
        {{ form.errors[linkField] }}
      </p>
    </div>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Link2, Users, X } from 'lucide-vue-next';
import { capitalize, computed, ref, toRaw, watch } from 'vue';

import type { GoalStep, LinkOption, StepParent, Translated } from './types';

import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import SheetForm from '@/Components/Patterns/SheetForm.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import MultiCollectionSelectDialog from '@/Features/Admin/AdminSearch/Components/Select/MultiCollectionSelectDialog.vue';
import { normalizeHit, type NormalizedSearchHit } from '@/Features/Admin/AdminSearch/Utils/searchHitMappers';
import { formatDate, todayIso } from '@/Utils/dateTime';

const props = defineProps<{
  open: boolean;
  parent: StepParent;
  step?: GoalStep | null;
  /** The other side the step may also count towards: the goal's problems, or the problem's goals. */
  linkOptions: LinkOption[];
}>();

const emit = defineEmits<{ 'update:open': [value: boolean] }>();

const NONE = '__none__';

const open = computed({ get: () => props.open, set: (value: boolean) => emit('update:open', value) });
const linkField = computed(() => (props.parent.type === 'goal' ? 'problem_id' : 'goal_id'));
const performersOpen = ref(false);
const referenceOpen = ref(false);
const performerHits = ref<NormalizedSearchHit[]>([]);
const referenceHit = ref<NormalizedSearchHit | null>(null);
/** A reference the reader cannot open still belongs to the step; its id is kept, its name is not shown. */
const hiddenReference = ref(false);

interface StepInput {
  title: Translated;
  description: Translated;
  happened_on: string;
  problem_id: string | null;
  goal_id: string | null;
  agenda_item_id: string | null;
  document_id: number | null;
  url: string;
}

const blank = (): StepInput => ({
  title: { lt: '', en: '' },
  description: { lt: '', en: '' },
  happened_on: todayIso(),
  problem_id: null,
  goal_id: null,
  agenda_item_id: null,
  document_id: null,
  url: '',
});

const form = useForm<StepInput>(blank());

const currentUser = computed(() => usePage().props.auth?.user as { id: string; name: string } | undefined);

watch(() => [props.step, props.open] as const, ([step, isOpen]) => {
  if (!isOpen) {
    return;
  }
  const next: StepInput = step
    ? {
        title: structuredClone(toRaw(step.title)),
        description: step.description ? structuredClone(toRaw(step.description)) : { lt: '', en: '' },
        happened_on: step.happened_on,
        problem_id: step.problem_id,
        goal_id: step.goal_id,
        agenda_item_id: step.agenda_item_id ?? null,
        document_id: step.document_id ?? null,
        url: step.url ?? '',
      }
    : blank();
  form.defaults(next);
  form.reset();
  form.clearErrors();

  // A new step is usually done by whoever records it.
  const people = step ? step.performers ?? [] : currentUser.value ? [currentUser.value] : [];
  performerHits.value = people.map(person => normalizeHit('users', { id: person.id, name: person.name }));

  if (step?.agenda_item) {
    referenceHit.value = normalizeHit('agendaItems', { id: step.agenda_item.id, title: step.agenda_item.title });
  }
  else if (step?.document) {
    referenceHit.value = normalizeHit('documents', { id: step.document.id, title: step.document.title });
  }
  else {
    referenceHit.value = null;
  }
  hiddenReference.value = Boolean(step && !referenceHit.value && (step.agenda_item_id || step.document_id));
}, { immediate: true });

const referenceLabel = computed(() => {
  if (referenceHit.value) {
    return referenceHit.value.title;
  }
  return hiddenReference.value ? $t('goals.steps.reference_hidden') : '';
});

function setReference(hit: NormalizedSearchHit | null): void {
  referenceHit.value = hit;
  hiddenReference.value = false;
  form.agenda_item_id = hit?.collection === 'agendaItems' ? hit.recordId : null;
  form.document_id = hit?.collection === 'documents' ? Number(hit.recordId) : null;
}

const linkIdString = computed({
  get: () => form[linkField.value] ?? NONE,
  set: (value: string) => {
    form[linkField.value] = value === NONE ? null : value;
  },
});

function setDate(value: Date | undefined): void {
  form.happened_on = value ? formatDate(value, { format: 'iso' }) : '';
}

function reset(): void {
  form.reset();
  form.clearErrors();
}

function submit(): void {
  const base = props.parent.type === 'goal' ? 'goals.steps' : 'problems.steps';
  const parentParam = props.parent.type === 'goal' ? { goal: props.parent.id } : { problem: props.parent.id };
  const options = {
    preserveScroll: true,
    onSuccess: () => {
      form.defaults();
      open.value = false;
    },
  };

  // Only the field for the other side is sent; the parent comes from the URL.
  form.transform(({ goal_id, problem_id, url, ...data }) => ({
    ...data,
    url: url.trim() || null,
    performers: performerHits.value.map(hit => hit.recordId),
    [linkField.value]: linkField.value === 'problem_id' ? problem_id : goal_id,
  }));

  if (props.step) {
    form.patch(route(`${base}.update`, { ...parentParam, step: props.step.id }), options);
  }
  else {
    form.post(route(`${base}.store`, parentParam), options);
  }
}
</script>
