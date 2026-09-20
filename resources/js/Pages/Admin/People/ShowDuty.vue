<template>
  <RecordPage
    v-model:section="currentSection"
    :title="dutyTitle"
    :entity-type="ModelEnum.DUTY"
    :status="dutyStatus"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    @action="handleRecordAction"
  >
    <template #subtitle>
      <div v-if="duty.institution" class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
        <Link
          :href="route('institutions.show', duty.institution.id)"
          class="hover:text-foreground hover:underline"
        >
          {{ duty.institution.name }}
        </Link>
        <span v-if="duty.institution.tenant?.shortname" :class="chipClass">
          {{ duty.institution.tenant.shortname }}
        </span>
      </div>
    </template>

    <template #fact-email>
      <a v-if="duty.email" :href="`mailto:${duty.email}`" class="underline underline-offset-4">
        {{ duty.email }}
      </a>
      <span v-else>—</span>
    </template>

    <template #alert>
      <div
        v-if="isVacant"
        class="flex items-start gap-3 border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-4 text-[var(--status-attention)]"
        data-testid="duty-vacancy-alert"
      >
        <UserX class="mt-0.5 size-5 shrink-0" />
        <div>
          <p class="text-sm font-semibold">
            {{ $t('Pareigos neužimtos') }}
          </p>
          <p class="mt-0.5 text-xs text-muted-foreground">
            {{ $t('Šiuo metu šių pareigų niekas neina. Priskirk narį, kad atnaujintum sudėtį.') }}
          </p>
        </div>
      </div>
    </template>

    <template #members>
      <div class="space-y-8">
        <section>
          <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-semibold text-foreground">
                {{ $t('Dabartiniai nariai') }}
              </h3>
              <span :class="countChipClass">{{ currentHolders.length }}</span>
            </div>
            <Button v-if="canAssignMembers" size="sm" variant="outline" class="u-touch" @click="openAssignSheet()">
              <UserPlus class="size-4" />
              {{ $t('Priskirti narį') }}
            </Button>
          </div>

          <p v-if="currentHolders.length === 0" class="border-b border-border py-6 text-sm text-muted-foreground">
            {{ $t('Šiuo metu nėra priskirtų narių.') }}
          </p>
          <div v-else class="divide-y divide-border border-b border-border">
            <MemberTermRow
              v-for="user in currentHolders"
              :key="`${user.id}-${user.pivot?.id}`"
              :user
              :can-manage="canAssignMembers"
              @edit="openAssignSheet(user.pivot, user)"
              @end="endTenureTarget = user"
            />
          </div>
        </section>

        <section v-if="upcomingHolders.length > 0">
          <div class="flex items-center gap-2 border-b border-border pb-3">
            <h3 class="text-base font-semibold text-foreground">
              {{ $t('Būsimi nariai') }}
            </h3>
            <span :class="countChipClass">{{ upcomingHolders.length }}</span>
          </div>
          <div class="divide-y divide-border border-b border-border">
            <MemberTermRow
              v-for="user in upcomingHolders"
              :key="`${user.id}-${user.pivot?.id}`"
              :user
              :can-manage="canAssignMembers"
              @edit="openAssignSheet(user.pivot, user)"
            />
          </div>
        </section>

        <section v-if="historicalHolders.length > 0">
          <div class="flex items-center gap-2 border-b border-border pb-3">
            <h3 class="text-base font-semibold text-foreground">
              {{ $t('Kadencijų istorija') }}
            </h3>
            <span :class="countChipClass">{{ historicalHolders.length }}</span>
          </div>
          <div class="divide-y divide-border border-b border-border">
            <MemberTermRow
              v-for="user in historicalHolders"
              :key="`${user.id}-${user.pivot?.id}`"
              :user
              :can-manage="canAssignMembers"
              @edit="openAssignSheet(user.pivot, user)"
            />
          </div>
        </section>
      </div>
    </template>

    <template #about>
      <div class="max-w-2xl space-y-8">
        <div v-if="dutyDescriptionHtml">
          <h3 class="mb-3 text-base font-semibold text-foreground">
            {{ $t('Aprašymas') }}
          </h3>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="prose prose-sm dark:prose-invert max-w-none" v-html="dutyDescriptionHtml" />
        </div>
        <p v-else class="text-sm text-muted-foreground">
          {{ $t('Ši pareigybė neturi aprašymo.') }}
        </p>

        <Deferred data="otherDuties">
          <template #fallback>
            <div class="space-y-2" data-testid="other-duties-skeleton">
              <div class="h-5 w-56 animate-pulse bg-secondary" />
              <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                <div v-for="n in 2" :key="n" class="h-14 animate-pulse border border-border bg-secondary/60" />
              </div>
            </div>
          </template>

          <div v-if="otherDuties.length > 0" class="border-t border-border pt-6">
            <h3 class="mb-4 text-base font-semibold text-foreground">
              {{ $t('Kitos pareigybės šioje institucijoje') }}
            </h3>
            <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
              <Link
                v-for="sibling in otherDuties"
                :key="sibling.id"
                :href="route('duties.show', sibling.id)"
                class="flex items-center justify-between border border-border bg-card p-3 transition-colors hover:bg-accent"
              >
                <div class="min-w-0">
                  <p class="truncate text-sm font-medium text-foreground">
                    <InflectedDutyName :name="sibling.name" />
                  </p>
                  <p class="text-xs text-muted-foreground">
                    {{ sibling.current_users?.length ?? 0 }} {{ $t('narių') }}
                  </p>
                </div>
                <ChevronRight class="size-4 shrink-0 text-muted-foreground" />
              </Link>
            </div>
          </div>
        </Deferred>
      </div>
    </template>

    <template #files>
      <div class="space-y-6">
        <div v-if="duty.sharepointPath">
          <h3 class="mb-4 text-base font-semibold text-foreground">
            {{ $t('Pareigybės failai') }}
          </h3>
          <FileManager :starting-path="duty.sharepointPath" :fileable="{ id: duty.id, type: 'Duty' }" />
        </div>

        <div v-if="hasTypeFiles">
          <h3 class="mb-2 text-base font-semibold text-foreground">
            {{ $t('Susiję failai pagal tipą') }}
          </h3>
          <p class="mb-4 text-sm text-muted-foreground">
            {{ $t('Šie failai yra susiję su pareigybės tipais ir yra bendrinami tarp visų tos kategorijos pareigybių.') }}
          </p>
          <Suspense>
            <SimpleFileViewer :fileable="{ id: duty.id, type: 'Duty' }" />
            <template #fallback>
              <div class="flex h-24 items-center justify-center text-sm text-muted-foreground">
                {{ $t('Kraunami susiję failai...') }}
              </div>
            </template>
          </Suspense>
        </div>
      </div>
    </template>

    <template #activity>
      <RecordActivity
        subject-type="duty"
        :subject-id="duty.id"
        commentable-type="duty"
        :commentable-id="duty.id"
      />
    </template>
  </RecordPage>

  <AssignDutyUserSheet
    v-model:open="assignSheetOpen"
    :duty
    :dutiable="selectedDutiable"
    :user="selectedUserForSheet"
    :study-programs="studyPrograms ?? []"
    :taken-ids="currentHolderIds"
    :occupied-places="currentHolders.length"
  />

  <DutiableTimelineDialog
    v-model:open="timelineOpen"
    scope-type="duty"
    :scope-id="duty.id"
  />

  <ConfirmDialog
    :open="endTenureTarget !== null"
    :title="$t('Baigti kadenciją šiandien?')"
    :description="endTenureTarget ? $t(':name nebebus laikomas šių pareigų nariu, bet įrašas liks istorijoje.', { name: endTenureTarget.name }) : undefined"
    :confirm-label="$t('Baigti kadenciją')"
    @update:open="!$event && (endTenureTarget = null)"
    @confirm="endTenure"
  />

  <ConfirmDialog
    v-model:open="deleteDutyOpen"
    :title="$t('Ištrinti pareigybę?')"
    :description="$t('Pareigybė bus pašalinta iš sistemos.')"
    :confirm-label="$t('Ištrinti')"
    destructive
    @confirm="deleteDuty"
  />

  <AccessChangeWarningDialog
    :open="accessWarningOpen"
    :report="accessWarningReport"
    @update:open="accessWarningOpen = $event"
    @confirm="accessWarningConfirm"
    @cancel="accessWarningCancel"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  CalendarRange,
  ChevronRight,
  Edit3,
  Trash2,
  UserPlus,
  UserX,
} from 'lucide-vue-next';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { DutyIconFilled, InstitutionIconFilled } from '@/Components/icons';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import { ConfirmDialog } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import type { StatusPresentation } from '@/Constants/statuses';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import { AssignDutyUserSheet, MemberTermRow, termStatus } from '@/Features/Admin/Occupancy';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import { ModelEnum } from '@/Types/enums';
import { todayIso } from '@/Utils/dateTime';

interface TranslatableText {
  lt?: string;
  en?: string;
}

const props = defineProps<{
  duty: App.Entities.Duty & {
    sharepointPath?: string | null;
  };
  can?: { update: boolean; managePeople: boolean };
  /** Deferred (`dutyPanels`): only the About tab needs siblings. */
  otherDuties?: App.Entities.Duty[];
  /** Deferred (`dutyPanels`): only the assignment sheet's picker needs them. */
  studyPrograms?: App.Entities.StudyProgram[];
}>();

const chipClass = 'border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground';
const countChipClass = 'border border-border bg-secondary px-2 py-0.5 text-xs font-semibold text-muted-foreground';

const currentSection = ref('members');
const assignSheetOpen = ref(false);
const timelineOpen = ref(false);
const deleteDutyOpen = ref(false);
const endTenureTarget = ref<App.Entities.User | null>(null);
const selectedDutiable = ref<(App.Entities.Dutiable & Record<string, unknown>) | null>(null);
const selectedUserForSheet = ref<App.Entities.User | null>(null);

const { report: accessWarningReport, open: accessWarningOpen, guardedSubmit, confirm: accessWarningConfirm, cancel: accessWarningCancel } = useAccessChangeGuard();

const canAssignMembers = computed(() => props.can?.managePeople ?? false);
const canManageDuty = computed(() => props.can?.update ?? false);

const holdersWhere = (wanted: ReturnType<typeof termStatus>) => {
  const today = todayIso();

  return (props.duty.users ?? []).filter((user: App.Entities.User) =>
    !!user.pivot && termStatus(user.pivot, today) === wanted);
};

const currentHolders = computed(() => holdersWhere('current'));
const upcomingHolders = computed(() => holdersWhere('upcoming'));
const historicalHolders = computed(() => holdersWhere('ended'));
const currentHolderIds = computed(() => currentHolders.value.map(user => String(user.id)));
const isVacant = computed(() => currentHolders.value.length === 0);

const dutyTitle = computed(() => {
  if (typeof props.duty.name === 'string') return props.duty.name;
  const nameObj = props.duty.name as TranslatableText | undefined;
  return nameObj?.lt || nameObj?.en || '';
});

// The healthy state (occupied) gets no badge — only what needs attention is painted.
const dutyStatus = computed<StatusPresentation | undefined>(() =>
  isVacant.value ? { label: $t('Neužimta'), role: 'attention', icon: UserX } : undefined);

const otherDuties = computed(() => props.otherDuties ?? []);
const hasTypeFiles = computed(() => (props.duty.types?.length ?? 0) > 0);
const hasFiles = computed(() => !!props.duty.sharepointPath || hasTypeFiles.value);

const dutyDescriptionHtml = computed(() => {
  if (typeof props.duty.description === 'string') return props.duty.description;
  const descObj = props.duty.description as TranslatableText | undefined;
  return descObj?.lt || descObj?.en || null;
});

const recordFacts = computed<RecordFact[]>(() => {
  const facts: RecordFact[] = [];

  if (props.duty.institution) {
    facts.push({
      key: 'institution',
      label: $t('Institucija'),
      value: props.duty.institution.name,
      href: route('institutions.show', props.duty.institution.id),
    });
  }

  facts.push({
    key: 'places',
    label: $t('Vietos'),
    value: `${currentHolders.value.length} / ${props.duty.places_to_occupy || '—'}`,
  });

  facts.push({
    key: 'email',
    label: $t('El. paštas'),
    value: props.duty.email ?? '—',
  });

  if (props.duty.types && props.duty.types.length > 0) {
    facts.push({
      key: 'types',
      label: $t('Kategorijos'),
      value: props.duty.types.map(t => t.title).join(', '),
    });
  }

  return facts;
});

const tabs = computed<RecordPageSection[]>(() => {
  const sections: RecordPageSection[] = [
    { value: 'members', label: $t('Nariai'), count: currentHolders.value.length },
    { value: 'about', label: $t('Apie pareigybę') },
  ];

  if (hasFiles.value) {
    sections.push({ value: 'files', label: $t('Failai') });
  }

  return sections;
});

const primaryAction = computed<ActionDescriptor | undefined>(() => {
  if (canAssignMembers.value) {
    return { key: 'assign', label: $t('Priskirti narį'), icon: UserPlus };
  }

  return undefined;
});

const overflowActions = computed<ActionDescriptor[]>(() => {
  const actions: ActionDescriptor[] = [
    { key: 'timeline', label: $t('dutiables.timeline.open'), icon: CalendarRange },
  ];

  if (canManageDuty.value) {
    actions.push({ key: 'edit', label: $t('Redaguoti pareigybę'), icon: Edit3 });
    actions.push({ key: 'delete', label: $t('Ištrinti pareigybę'), icon: Trash2, destructive: true });
  }

  return actions;
});

const handleRecordAction = (actionKey: string) => {
  switch (actionKey) {
    case 'assign':
      openAssignSheet();
      break;
    case 'timeline':
      timelineOpen.value = true;
      break;
    case 'edit':
      router.get(route('duties.edit', props.duty.id));
      break;
    case 'delete':
      deleteDutyOpen.value = true;
      break;
  }
};

const openAssignSheet = (dutiable?: (App.Entities.Dutiable & Record<string, unknown>) | null, user?: App.Entities.User | null) => {
  selectedDutiable.value = dutiable ?? null;
  selectedUserForSheet.value = user ?? null;
  assignSheetOpen.value = true;
};

const deleteDuty = () => {
  router.delete(route('duties.destroy', props.duty.id));
};

const endTenure = () => {
  const dutiable = endTenureTarget.value?.pivot as { id: string | number } | undefined;
  endTenureTarget.value = null;

  if (!dutiable) {
    return;
  }

  // The redirect back re-renders this page, so no manual reload is needed.
  guardedSubmit((acknowledge) => {
    router.patch(route('dutiables.update', dutiable.id), {
      end_date: todayIso(),
      acknowledge_access_change: acknowledge,
    }, { preserveScroll: true });
  });
};

usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    props.duty.institution?.name,
    'institutions.show',
    { institution: props.duty?.institution?.id },
    dutyTitle.value,
    InstitutionIconFilled,
    DutyIconFilled,
  ),
);
</script>
