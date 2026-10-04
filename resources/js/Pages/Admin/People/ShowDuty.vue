<template>
  <RecordPage
    v-model:section="currentSection"
    :history-subject="{ type: 'duty', id: duty.id }"
    :title="dutyTitle"
    :entity-type="ModelEnum.DUTY"
    :facts="recordFacts"
    :sections="tabs"
    :primary-action
    :overflow-actions
    actions-beside-title
    @action="handleRecordAction"
  >
    <template #title>
      <InflectedDutyName :name="dutyTitle" />
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
      <div class="grid gap-10 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] xl:gap-16">
        <div class="flex min-w-0 flex-col gap-10">
          <OverviewSection variant="home" :title="$t('Dabartiniai nariai')" :icon="Users" :count="currentHolders.length">
            <template v-if="canAssignMembers" #actions>
              <Button size="sm" variant="outline" voice="sentence" class="u-touch" @click="openAssignSheet()">
                <UserPlus class="size-4" />
                {{ $t('Priskirti narį') }}
              </Button>
            </template>

            <p v-if="currentHolders.length === 0" class="text-sm text-muted-foreground">
              {{ $t('Šiuo metu nėra priskirtų narių.') }}
            </p>
            <div v-else>
              <MemberTermRow
                v-for="user in currentHolders"
                :key="`${user.id}-${user.pivot?.id}`"
                :user
                :can-manage="canAssignMembers"
                @edit="openAssignSheet(user.pivot, user)"
                @end="endTenureTarget = user"
              />
            </div>
          </OverviewSection>

          <OverviewSection
            v-if="upcomingHolders.length > 0"
            variant="home"
            :title="$t('Būsimi nariai')"
            :icon="CalendarClock"
            :count="upcomingHolders.length"
          >
            <div>
              <MemberTermRow
                v-for="user in upcomingHolders"
                :key="`${user.id}-${user.pivot?.id}`"
                :user
                :can-manage="canAssignMembers"
                @edit="openAssignSheet(user.pivot, user)"
              />
            </div>
          </OverviewSection>
        </div>

        <OverviewSection
          v-if="historicalHolders.length > 0"
          variant="home"
          :title="$t('Laikotarpių istorija')"
          :icon="History"
          :count="historicalHolders.length"
        >
          <div>
            <MemberTermRow
              v-for="user in historicalHolders"
              :key="`${user.id}-${user.pivot?.id}`"
              :user
              :can-manage="canAssignMembers"
              @edit="openAssignSheet(user.pivot, user)"
            />
          </div>
        </OverviewSection>
      </div>
    </template>

    <template #about>
      <div class="grid gap-10 xl:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)] xl:gap-16">
        <OverviewSection variant="home" :title="$t('Aprašymas')" :icon="FileText">
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div v-if="dutyDescriptionHtml" class="prose prose-sm dark:prose-invert max-w-none" v-html="dutyDescriptionHtml" />
          <p v-else class="text-sm text-muted-foreground">
            {{ $t('Ši pareigybė neturi aprašymo.') }}
          </p>
        </OverviewSection>

        <Deferred data="otherDuties">
          <template #fallback>
            <div class="space-y-2" data-testid="other-duties-skeleton">
              <div class="h-5 w-56 animate-pulse bg-secondary" />
              <div v-for="n in 2" :key="n" class="h-12 animate-pulse bg-secondary/60" />
            </div>
          </template>

          <OverviewSection
            v-if="otherDuties.length > 0"
            variant="home"
            :title="$t('Kitos pareigybės šioje institucijoje')"
            :icon="Briefcase"
          >
            <div class="-mx-2">
              <Link
                v-for="sibling in otherDuties"
                :key="sibling.id"
                :href="route('duties.show', sibling.id)"
                class="flex min-h-11 items-center justify-between gap-3 px-2 py-2 transition-colors hover:bg-accent"
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
          </OverviewSection>
        </Deferred>
      </div>
    </template>

    <template #responsibilities>
      <Deferred data="responsibilities">
        <template #fallback>
          <div class="grid gap-10 xl:grid-cols-2 xl:gap-16" data-testid="responsibilities-skeleton">
            <div v-for="n in 2" :key="n" class="space-y-2">
              <div class="h-5 w-40 animate-pulse bg-secondary" />
              <div class="h-11 animate-pulse bg-secondary/60" />
            </div>
          </div>
        </template>

        <DutyResponsibilitiesSection
          :duty-id="duty.id"
          :tenant-id="duty.institution?.tenant_id ?? null"
          :items="responsibilities?.items ?? []"
          :roles="responsibilities?.roles ?? []"
          :can-update="canManageDuty"
          :options="responsibilityOptions"
        />
      </Deferred>
    </template>

    <template #files>
      <FileableFilesPanel
        :fileable="{ id: duty.id, type: 'Duty' }"
        :files
        :type-files
        :can-upload="canManageDuty && !!duty.sharepointPath"
        :folder-url="duty.sharepointFolderUrl"
        :can-delete="canManageDuty"
      />
    </template>

    <template #activity>
      <RecordActivity commentable-type="duty" :commentable-id="duty.id" />
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
    :title="$t('Užbaigti pareigas šiandien?')"
    :description="endTenureTarget ? $t('dutiables.assign.still_active_today', { name: endTenureTarget.name }) : undefined"
    :confirm-label="$t('Užbaigti pareigas')"
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
import { computed, onMounted, ref } from 'vue';
import { Deferred, Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Briefcase,
  CalendarClock,
  CalendarRange,
  ChevronRight,
  Edit3,
  FileText,
  History,
  Trash2,
  UserPlus,
  Users,
  UserX,
} from 'lucide-vue-next';

import AccessChangeWarningDialog from '@/Components/AdminForms/AccessChangeWarningDialog.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import { ConfirmDialog, OverviewSection } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { useAccessChangeGuard } from '@/Composables/useAccessChangeGuard';
import type { StatusPresentation } from '@/Constants/statuses';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import { AssignDutyUserSheet, MemberTermRow, termStatus } from '@/Features/Admin/Occupancy';
import DutyResponsibilitiesSection from '@/Features/Admin/Responsibilities/DutyResponsibilitiesSection.vue';
import type { DutyResponsibilitiesPayload, DutyResponsibilityOptions } from '@/Features/Admin/Responsibilities/types';
import { FileableFilesPanel, type FileableFileItem } from '@/Components/Files';
import { ModelEnum } from '@/Types/enums';
import { todayIso } from '@/Utils/dateTime';

interface TranslatableText {
  lt?: string;
  en?: string;
}

const props = defineProps<{
  duty: App.Entities.Duty & {
    sharepointPath?: string | null;
    sharepointFolderUrl?: string | null;
  };
  can?: { update: boolean; managePeople: boolean };
  /** Deferred (`dutyPanels`): only the About tab needs siblings. */
  otherDuties?: App.Entities.Duty[];
  /** Deferred (`dutyPanels`): only the assignment sheet's picker needs them. */
  studyPrograms?: App.Entities.StudyProgram[];
  /** Deferred (`dutyPanels`): what the duty must handle, beside its roles. */
  responsibilities?: DutyResponsibilitiesPayload;
  /** Optional: only the add-responsibility sheet needs these. */
  responsibilityOptions?: DutyResponsibilityOptions;
  /** Deferred (`files`). */
  files?: FileableFileItem[];
  typeFiles?: FileableFileItem[];
}>();

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

const dutyTitle = computed(() => getTranslatedValue(props.duty.name));

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

  if (props.duty.institution?.tenant?.shortname) {
    facts.push({ key: 'tenant', label: $t('Padalinys'), value: props.duty.institution.tenant.shortname });
  }

  const places = `${currentHolders.value.length} / ${props.duty.places_to_occupy || '—'}`;

  // Vacancy rides on the places fact, as an institution's status does on its own.
  facts.push(dutyStatus.value
    ? { key: 'places', label: $t('Vietos'), status: dutyStatus.value, detail: places }
    : { key: 'places', label: $t('Vietos'), value: places });

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
    { value: 'responsibilities', label: $t('responsibilities.duty.title'), count: props.responsibilities?.items.length },
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

/** `dutiables.edit` redirects here with `?dutiable=`: open that term in the sheet, then drop the parameter. */
onMounted(() => {
  const params = new URLSearchParams(window.location.search);
  const dutiableId = params.get('dutiable');

  if (!dutiableId) {
    return;
  }

  const holder = (props.duty.users ?? []).find((user: App.Entities.User) => String(user.pivot?.id) === dutiableId);

  if (holder?.pivot && props.can?.managePeople) {
    openAssignSheet(holder.pivot, holder);
  }

  params.delete('dutiable');
  const query = params.toString();
  window.history.replaceState(window.history.state, '', `${window.location.pathname}${query ? `?${query}` : ''}`);
});

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

</script>
