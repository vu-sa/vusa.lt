<template>
  <ol v-if="steps.length" class="divide-y divide-border border-y border-border" data-testid="goal-step-list">
    <li v-for="step in steps" :key="step.id" class="flex gap-4 px-2 py-3 sm:px-3">
      <time :datetime="step.happened_on" class="w-24 shrink-0 pt-0.5 text-sm tabular-nums text-muted-foreground">
        {{ formatDate(step.happened_on) }}
      </time>
      <div class="min-w-0 flex-1 space-y-1">
        <p class="text-sm font-medium text-foreground">
          {{ translatedText(step.title, locale) }}
        </p>
        <p v-if="translatedText(step.description, locale)" class="whitespace-pre-line text-sm text-muted-foreground">
          {{ translatedText(step.description, locale) }}
        </p>

        <ul v-if="references(step).length" class="flex flex-wrap gap-x-4 gap-y-1" data-testid="goal-step-references">
          <li v-for="reference in references(step)" :key="reference.key">
            <component
              :is="reference.external ? 'a' : Link"
              :href="reference.href"
              v-bind="reference.external ? { target: '_blank', rel: 'noopener' } : {}"
              class="inline-flex min-h-6 items-center gap-1 text-xs text-muted-foreground underline-offset-4 hover:text-foreground hover:underline pointer-coarse:min-h-11"
            >
              <component :is="reference.icon" class="size-3.5 shrink-0" aria-hidden="true" />
              <span class="truncate">{{ reference.label }}</span>
            </component>
          </li>
        </ul>

        <p v-if="step.performers?.length || step.recorder" class="text-xs text-muted-foreground" data-testid="goal-step-people">
          <template v-if="step.performers?.length">
            {{ $t('goals.steps.done_by') }}: <span class="text-foreground">{{ step.performers.map(person => person.name).join(', ') }}</span>
          </template>
          <template v-if="step.recorder && !(step.performers?.length === 1 && step.performers[0]?.id === step.recorder.id)">
            <span v-if="step.performers?.length" aria-hidden="true"> · </span>
            {{ $t('goals.steps.recorded_by') }}: {{ step.recorder.name }}
          </template>
        </p>
      </div>
      <CollectionRowActions
        v-if="canUpdate"
        :actions="[
          { key: 'edit', label: $t('Redaguoti'), icon: Pencil },
          { key: 'delete', label: $t('goals.steps.delete'), icon: Trash2, destructive: true },
        ]"
        @select="key => key === 'edit' ? emit('edit', step) : emit('delete', step)"
      />
    </li>
  </ol>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { ExternalLink, Pencil, Trash2 } from 'lucide-vue-next';
import type { Component } from 'vue';

import { translatedText, type GoalStep } from './types';

import CollectionRowActions from '@/Components/Collection/CollectionRowActions.vue';
import { AgendaItemIcon, DocumentIcon, GoalIcon, ProblemIcon } from '@/Components/icons';
import { formatDate } from '@/Utils/dateTime';

const props = defineProps<{
  steps: GoalStep[];
  canUpdate: boolean;
  /** Which other record a step may also count towards, shown under it as a link. */
  otherSide: 'goal' | 'problem';
}>();

const emit = defineEmits<{ edit: [step: GoalStep]; delete: [step: GoalStep] }>();

const locale = getActiveLanguage();

interface Reference {
  key: string;
  href: string;
  label: string;
  icon: Component;
  external?: boolean;
}

function references(step: GoalStep): Reference[] {
  const list: Reference[] = [];

  if (props.otherSide === 'problem' && step.problem) {
    list.push({ key: 'problem', href: route('problems.show', step.problem.id), label: step.problem.title, icon: ProblemIcon });
  }
  if (props.otherSide === 'goal' && step.goal) {
    list.push({ key: 'goal', href: route('goals.show', step.goal.id), label: step.goal.title, icon: GoalIcon });
  }
  if (step.agenda_item) {
    const where = [
      step.agenda_item.start_time ? formatDate(step.agenda_item.start_time) : null,
      step.agenda_item.institutions.join(', ') || null,
    ].filter(Boolean).join(' · ');
    list.push({ key: 'agenda', href: route('agendaItems.show', step.agenda_item.id), label: where ? `${step.agenda_item.title} (${where})` : step.agenda_item.title, icon: AgendaItemIcon });
  }
  if (step.document) {
    list.push({ key: 'document', href: route('documents.show', step.document.id), label: step.document.title, icon: DocumentIcon });
  }
  if (step.url) {
    list.push({ key: 'url', href: step.url, label: step.url.replace(/^https?:\/\//, ''), icon: ExternalLink, external: true });
  }

  return list;
}
</script>
