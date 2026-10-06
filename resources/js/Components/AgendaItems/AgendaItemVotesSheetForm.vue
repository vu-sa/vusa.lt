<template>
  <SheetForm
    :open
    :title="$t('meetings.item.manage_votes')"
    :description="$t('meetings.item.manage_votes_description')"
    :processing="form.processing"
    :dirty
    @update:open="emit('update:open', $event)"
    @cancel="load"
    @submit="save"
  >
    <div class="flex flex-wrap items-center gap-3">
      <div :class="segmentGroupClass" role="group" :aria-label="$t('Kalba')">
        <button
          v-for="loc in LOCALES"
          :key="loc"
          type="button"
          :class="segmentVariants({ active: locale === loc })"
          :aria-pressed="locale === loc"
          :data-testid="`votes-locale-${loc}`"
          @click="locale = loc"
        >
          <LocaleFlag :locale="loc" />
          <span>{{ loc.toUpperCase() }}</span>
        </button>
      </div>
      <p v-if="locale === 'en'" class="flex items-center gap-1.5 text-xs font-medium text-status-attention">
        <Languages class="size-3.5 shrink-0" />
        {{ $t('meetings.agenda.editing_english') }}
      </p>
    </div>

    <p v-if="draft.length === 0" class="border-y border-border py-3 text-sm text-muted-foreground">
      {{ $t('Neaptarta') }}
    </p>

    <ol class="space-y-3">
      <li
        v-for="(vote, index) in draft"
        :key="vote.source"
        class="space-y-3 border border-border p-3"
        data-slot="manage-vote"
      >
        <div class="flex items-center gap-1">
          <span class="mr-auto text-sm font-semibold text-foreground">
            {{ $t('Balsavimas') }} {{ index + 1 }}
          </span>
          <Button
            v-if="draft.length > 1"
            variant="ghost"
            size="sm"
            voice="sentence"
            :aria-pressed="vote.is_main"
            :class="['pointer-coarse:h-11', vote.is_main ? 'text-brand' : 'text-muted-foreground']"
            data-testid="manage-vote-main"
            @click="setMain(index)"
          >
            <Star :class="['size-4', vote.is_main ? 'fill-current' : undefined]" />
            {{ vote.is_main ? $t('meetings.item.main_vote') : $t('meetings.item.make_main') }}
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="u-touch"
            :disabled="index === 0"
            :aria-label="$t('Perkelti aukštyn')"
            @click="move(index, -1)"
          >
            <ChevronUp class="size-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="u-touch"
            :disabled="index === draft.length - 1"
            :aria-label="$t('Perkelti žemyn')"
            @click="move(index, 1)"
          >
            <ChevronDown class="size-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            class="u-touch text-muted-foreground hover:text-destructive"
            :aria-label="$t('meetings.item.remove_vote')"
            @click="remove(index)"
          >
            <Trash2 class="size-4" />
          </Button>
        </div>

        <FormFieldWrapper :id="`manage-vote-title-${vote.source}`" :label="$t('meetings.item.vote_title')">
          <Input
            :id="`manage-vote-title-${vote.source}`"
            v-model="vote.title[locale]"
            maxlength="200"
            :class="['h-11', fieldSurfaceClass]"
          />
        </FormFieldWrapper>
        <FormFieldWrapper :id="`manage-vote-note-${vote.source}`" :label="$t('meetings.item.vote_note')">
          <Textarea
            :id="`manage-vote-note-${vote.source}`"
            v-model="vote.note[locale]"
            rows="2"
            maxlength="2000"
            :class="fieldSurfaceClass"
          />
        </FormFieldWrapper>
      </li>
    </ol>
  </SheetForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, ChevronUp, Languages, Star, Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { SheetForm } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { fieldSurfaceClass, segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { Input } from '@/Components/ui/input';
import { Textarea } from '@/Components/ui/textarea';
import type { AgendaItemFormData, TranslatedField } from '@/Composables/useAgendaItemAutosave';

const props = defineProps<{
  open: boolean;
  /** The live, autosaving form: the sheet edits a draft and writes it back on save. */
  form: InertiaForm<AgendaItemFormData>;
  /** Saves the form, then calls back once the server accepted it. */
  saveThen: (callback: () => void) => void;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const LOCALES = ['lt', 'en'] as const;

interface VoteDraft {
  /** Index into `form.votes` when the sheet opened, so answers and ids travel with the vote. */
  source: number;
  is_main: boolean;
  title: TranslatedField;
  note: TranslatedField;
}

const fromForm = (): VoteDraft[] => props.form.votes.map((vote, source) => ({
  source,
  is_main: vote.is_main ?? false,
  title: { ...vote.title },
  note: { ...vote.note },
}));

const draft = ref<VoteDraft[]>(fromForm());
const baseline = ref(JSON.stringify(draft.value));
const locale = ref<'lt' | 'en'>('lt');

const load = () => {
  draft.value = fromForm();
  baseline.value = JSON.stringify(draft.value);
  locale.value = 'lt';
};

watch(() => props.open, (open) => {
  if (open) {
    load();
  }
});

const dirty = computed(() => JSON.stringify(draft.value) !== baseline.value);

/** Exactly one vote is the item's outcome — the backend enforces the same invariant. */
const setMain = (index: number) => {
  draft.value.forEach((vote, position) => {
    vote.is_main = position === index;
  });
};

const move = (index: number, direction: -1 | 1) => {
  const destination = index + direction;
  if (destination < 0 || destination >= draft.value.length) {
    return;
  }
  const [vote] = draft.value.splice(index, 1);
  draft.value.splice(destination, 0, vote!);
};

const remove = (index: number) => {
  const [removed] = draft.value.splice(index, 1);
  if (removed?.is_main && draft.value.length > 0) {
    draft.value[0]!.is_main = true;
  }
};

const save = () => {
  const sources = [...props.form.votes];

  // `order` follows array position on save (useAgendaItemAutosave), so rebuilding the array is enough.
  props.form.votes = draft.value.map((vote) => {
    const target = sources[vote.source]!;
    target.is_main = vote.is_main;
    target.title = { ...vote.title };
    target.note = { ...vote.note };
    return target;
  });

  // A rejected save never calls back, so the sheet stays open on its errors.
  props.saveThen(() => {
    baseline.value = JSON.stringify(draft.value);
    emit('update:open', false);
  });
};
</script>
