<template>
  <AdminContentPage>
    <InertiaHead :title="pageTitle" />

    <AgendaItemNavigator
      :meeting-id="agendaItem.meeting_id"
      :meeting-title="agendaItem.meeting?.title"
      :current-id="agendaItem.id"
      :sibling-agenda-items="siblingAgendaItems"
      @navigate="navigateTo"
    />

    <!-- Plain header -->
    <header class="mt-3 space-y-2">
      <div class="flex items-start justify-between gap-4">
        <div class="min-w-0 flex-1 pt-1">
          <textarea
            v-if="editing"
            ref="titleTextarea"
            v-model="form.title"
            rows="1"
            class="w-full resize-none overflow-hidden border-0 bg-transparent p-0 text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-0"
            :placeholder="$t('Darbotvarkės punkto pavadinimas')"
          />
          <h1 v-else class="text-xl sm:text-2xl md:text-3xl font-bold tracking-tight text-foreground">
            {{ agendaItem.title }}
          </h1>
          <p v-if="form.errors.title" class="mt-1 text-sm text-destructive">
            {{ form.errors.title }}
          </p>
        </div>

        <div class="mt-2 flex shrink-0 items-center gap-3">
          <span
            :class="[
              'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium',
              statusMeta.bgClass,
              statusMeta.colorClass,
            ]"
          >
            <component :is="statusMeta.icon" class="h-3 w-3" />
            {{ statusMeta.label }}
          </span>
          <label class="flex cursor-pointer items-center gap-2 text-sm text-muted-foreground select-none">
            <Switch :model-value="editing" @update:model-value="setEditing" />
            {{ $t('Redaguoti') }}
          </label>
        </div>
      </div>
      <div class="flex min-w-0 items-center gap-2 text-sm text-muted-foreground font-medium">
        <Link
          v-if="meetingLabel && agendaItem.meeting_id"
          :href="route('meetings.show', agendaItem.meeting_id)"
          class="inline-flex shrink-0 items-center gap-1.5 hover:text-primary transition-colors"
        >
          <CalendarDays class="h-4 w-4" />
          {{ meetingLabel }}
        </Link>
        <span v-if="mainInstitution" class="flex min-w-0 items-center gap-1.5">
          <span class="text-muted-foreground/50">·</span>
          <Building2 class="h-4 w-4 shrink-0" />
          <span class="truncate" :title="mainInstitution.name">{{ mainInstitution.name }}</span>
        </span>
      </div>
    </header>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]" :class="editing ? 'pb-24' : 'pb-6'">
      <AgendaItemBody
        :form="form"
        :editing="editing"
        :meeting-is-public="meetingIsPublic"
      />
      <AgendaItemNotesSidebar :agenda-item-id="agendaItem.id" class="lg:sticky lg:top-16 lg:self-start" />
    </div>

    <!-- Sticky bottom action bar (mirrors AdminForm), edit mode only -->
    <div v-if="editing" class="fixed bottom-0 left-0 right-0 z-50 border-t bg-white/95 px-4 py-3 backdrop-blur-sm dark:border-zinc-800 dark:bg-zinc-900/95 md:left-(--sidebar-width,16rem)">
      <div class="mx-auto flex max-w-5xl items-center justify-between gap-4">
        <!-- Status -->
        <div class="flex items-center gap-2 text-sm">
          <Transition name="fade" mode="out-in">
            <div v-if="saveStatus === 'saving'" key="saving" class="flex items-center gap-2 text-muted-foreground">
              <Loader2 class="h-4 w-4 animate-spin" />
              <span class="hidden sm:inline">{{ $t('Saugoma…') }}</span>
            </div>
            <div v-else-if="saveStatus === 'dirty'" key="dirty" class="flex items-center gap-2 text-amber-600 dark:text-amber-400">
              <span class="h-2 w-2 animate-pulse rounded-full bg-amber-500" />
              <span class="hidden sm:inline">{{ $t('Neišsaugota') }}</span>
            </div>
            <div v-else-if="saveStatus === 'saved'" key="saved" class="flex items-center gap-2 text-emerald-600 dark:text-emerald-400">
              <Check class="h-4 w-4" />
              <span class="hidden sm:inline">{{ $t('Įrašyta') }}</span>
            </div>
          </Transition>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-2 sm:gap-3">
          <label class="hidden items-center gap-2 sm:flex cursor-pointer select-none">
            <Switch id="agenda-autosave" v-model="autoSaveEnabled" />
            <span class="text-xs text-muted-foreground">{{ $t('Automatinis išsaugojimas') }}</span>
          </label>

          <Separator orientation="vertical" class="hidden h-6 sm:block" />

          <Button :disabled="form.processing" @click="submit()">
            <Save class="h-4 w-4 sm:mr-2" />
            <span class="hidden sm:inline">{{ $t('Išsaugoti') }}</span>
          </Button>
        </div>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { Link, router, useForm, Head as InertiaHead } from '@inertiajs/vue3';
import { useTextareaAutosize } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Building2, CalendarDays, Check, Loader2, Save } from 'lucide-vue-next';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import AgendaItemBody from '@/Components/AgendaItems/AgendaItemBody.vue';
import AgendaItemNavigator from '@/Components/AgendaItems/AgendaItemNavigator.vue';
import AgendaItemNotesSidebar from '@/Components/AgendaItems/AgendaItemNotesSidebar.vue';
import { Button } from '@/Components/ui/button';
import { Switch } from '@/Components/ui/switch';
import { Separator } from '@/Components/ui/separator';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getAgendaItemStatusMeta } from '@/Composables/useAgendaItemStyling';
import { useAgendaItemAutosave, type AgendaItemFormData, type EditableVote, type VoteValue } from '@/Composables/useAgendaItemAutosave';
import { formatStaticTime } from '@/Utils/IntlTime';
import { InstitutionIconFilled, MeetingIconFilled } from '@/Components/icons';

const props = defineProps<{
  agendaItem: App.Entities.AgendaItem;
  siblingAgendaItems: Array<{
    id: string;
    title: string;
    type?: string | null;
    order: number;
    brought_by_students: boolean;
    main_vote?: unknown;
  }>;
}>();

const form = useForm<AgendaItemFormData>({
  title: props.agendaItem.title,
  type: (props.agendaItem.type ?? null) as AgendaItemFormData['type'],
  brought_by_students: props.agendaItem.brought_by_students ?? false,
  student_position: props.agendaItem.student_position ?? '',
  description: props.agendaItem.description ?? '',
  votes: (props.agendaItem.votes ?? []).map((vote): EditableVote => ({
    id: vote.id,
    is_main: vote.is_main ?? false,
    is_consensus: vote.is_consensus ?? false,
    title: vote.title ?? null,
    student_vote: (vote.student_vote ?? null) as VoteValue,
    decision: (vote.decision ?? null) as VoteValue,
    student_benefit: (vote.student_benefit ?? null) as VoteValue,
    order: vote.order ?? 0,
  })),
});

const { autoSaveEnabled, saveStatus, submit, saveThen } = useAgendaItemAutosave(
  form,
  props.agendaItem.id,
);

// Read-first: configured items open in view mode; unconfigured ones in edit mode.
const editing = ref(props.agendaItem.type == null);

// Auto-grow the title textarea so long titles wrap like the heading instead of
// collapsing to one line.
const titleTextarea = ref<HTMLTextAreaElement | null>(null);
const { triggerResize } = useTextareaAutosize({ element: titleTextarea });
watch([() => form.title, editing], () => nextTick(triggerResize), { immediate: true });

const setEditing = (value: boolean) => {
  editing.value = value;
  // Leaving edit mode: flush any pending change so the read view is accurate.
  if (!value) {
    saveThen(() => {});
  }
};

const navigateTo = (id: string) => {
  saveThen(() => router.visit(route('agendaItems.edit', id)));
};

const meetingIsPublic = computed(() => Boolean((props.agendaItem.meeting as any)?.is_public));

const statusMeta = computed(() => getAgendaItemStatusMeta({
  id: props.agendaItem.id,
  type: form.type,
  votes: form.votes as any,
}));

const mainInstitution = computed(() => props.agendaItem.meeting?.institutions?.[0] ?? null);

const currentPosition = computed(() => {
  const index = props.siblingAgendaItems.findIndex(item => item.id === props.agendaItem.id);
  return index >= 0 ? index + 1 : props.agendaItem.order;
});

const meetingLabel = computed(() => {
  const meeting = props.agendaItem.meeting;
  if (!meeting) { return ''; }
  if (meeting.start_time) {
    return formatStaticTime(new Date(meeting.start_time), {
      year: 'numeric',
      month: 'long',
      day: 'numeric',
    });
  }
  return meeting.title ?? '';
});

const pageTitle = computed(() => `${$t('Redaguoti')}: ${props.agendaItem.title}`);

usePageBreadcrumbs(() => {
  const items = [];

  if (mainInstitution.value) {
    items.push(BreadcrumbHelpers.createRouteBreadcrumb(
      mainInstitution.value.name,
      'institutions.show',
      { institution: mainInstitution.value.id },
      InstitutionIconFilled,
    ));
  }

  if (props.agendaItem.meeting_id) {
    items.push(BreadcrumbHelpers.createRouteBreadcrumb(
      meetingLabel.value || $t('Posėdis'),
      'meetings.show',
      { meeting: props.agendaItem.meeting_id },
      MeetingIconFilled,
    ));
  }

  items.push(BreadcrumbHelpers.createBreadcrumbItem(`${$t('Punktas')} ${currentPosition.value}`));
  return items;
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
