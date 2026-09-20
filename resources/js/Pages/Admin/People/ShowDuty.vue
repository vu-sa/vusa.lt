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
        <span v-if="duty.institution.tenant?.shortname" class="border border-border bg-secondary px-1.5 py-0.5 text-xs font-medium text-muted-foreground">
          {{ duty.institution.tenant.shortname }}
        </span>
      </div>
    </template>

    <template #fact-places>
      <div class="flex items-center gap-2">
        <span>{{ currentHolders.length }} / {{ duty.places_to_occupy || '—' }}</span>
        <span
          v-if="isVacant"
          class="border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] px-1.5 py-0.5 text-[11px] font-medium text-[var(--status-attention)]"
        >
          {{ $t('Neužimta') }}
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
        class="flex flex-col gap-3 border border-[var(--status-attention-border)] bg-[var(--status-attention-surface)] p-4 sm:flex-row sm:items-center sm:justify-between"
      >
        <div class="flex items-start gap-3 text-[var(--status-attention)]">
          <UserX class="mt-0.5 size-5 shrink-0" />
          <div>
            <p class="text-sm font-semibold">
              {{ $t('Pareigos neužimtos') }}
            </p>
            <p class="mt-0.5 text-xs text-muted-foreground">
              {{ $t('Šiuo metu niekas neeina šių pareigų. Priskirkite narį, kad atnaujintumėte sudėtį.') }}
            </p>
          </div>
        </div>
        <Button
          v-if="canAssignMembers"
          size="sm"
          variant="outline"
          class="u-touch shrink-0"
          @click="openAssignSheet()"
        >
          <UserPlus class="mr-2 size-4" />
          {{ $t('Priskirti narį') }}
        </Button>
      </div>
    </template>

    <!-- Members Section -->
    <template #members>
      <div class="space-y-8">
        <!-- Current Members -->
        <div>
          <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-semibold text-foreground">
                {{ $t('Dabartiniai nariai') }}
              </h3>
              <span class="border border-border bg-secondary px-2 py-0.5 text-xs font-semibold text-muted-foreground">
                {{ currentHolders.length }}
              </span>
            </div>
            <Button
              v-if="canAssignMembers"
              size="sm"
              variant="outline"
              class="u-touch"
              @click="openAssignSheet()"
            >
              <UserPlus class="mr-2 size-4" />
              {{ $t('Priskirti narį') }}
            </Button>
          </div>

          <div v-if="currentHolders.length === 0" class="border-b border-border py-8 text-center text-sm text-muted-foreground">
            <UserX class="mx-auto size-8 text-muted-foreground/60" />
            <p class="mt-2 font-medium">
              {{ $t('Šiuo metu nėra priskirtų narių.') }}
            </p>
          </div>

          <div v-else class="divide-y divide-border border-b border-border">
            <div
              v-for="user in currentHolders"
              :key="user.id"
              class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex min-w-0 items-start gap-3">
                <UserAvatar :user :size="40" class="shrink-0" />
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <Link
                      :href="route('users.show', user.id)"
                      class="truncate text-sm font-semibold text-foreground hover:underline"
                    >
                      {{ user.name }}
                    </Link>
                    <span
                      v-if="user.pivot?.via_dutiable_id"
                      class="border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                    >
                      {{ $t('Ex-officio') }}
                    </span>
                    <span
                      v-if="user.pivot?.tenant_id"
                      class="border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                    >
                      {{ $t('Deleguota') }}
                    </span>
                  </div>
                  <p v-if="user.email" class="truncate text-xs text-muted-foreground">
                    {{ user.pivot?.additional_email || user.email }}
                  </p>
                  <p class="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                    <Calendar class="size-3 shrink-0" />
                    <span>{{ formatTenure(user) }}</span>
                  </p>
                </div>
              </div>

              <div v-if="canAssignMembers" class="flex shrink-0 items-center gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  class="u-touch"
                  @click="openAssignSheet(user.pivot, user)"
                >
                  <Edit3 class="mr-1.5 size-3.5" />
                  {{ $t('Redaguoti') }}
                </Button>
                <Button
                  v-if="!user.pivot?.via_dutiable_id"
                  variant="ghost"
                  size="sm"
                  class="u-touch"
                  @click="endTenure(user.pivot)"
                >
                  <CalendarCheck class="mr-1.5 size-3.5" />
                  {{ $t('Baigti kadenciją') }}
                </Button>
              </div>
            </div>
          </div>
        </div>

        <!-- Historical Members -->
        <div v-if="historicalHolders.length > 0">
          <div class="flex items-center justify-between border-b border-border pb-3">
            <div class="flex items-center gap-2">
              <h3 class="text-base font-semibold text-foreground">
                {{ $t('Kadencijų istorija') }}
              </h3>
              <span class="border border-border bg-secondary px-2 py-0.5 text-xs font-semibold text-muted-foreground">
                {{ historicalHolders.length }}
              </span>
            </div>
          </div>

          <div class="divide-y divide-border border-b border-border">
            <div
              v-for="user in historicalHolders"
              :key="`${user.id}-${user.pivot?.id}`"
              class="flex flex-col gap-4 py-3 sm:flex-row sm:items-center sm:justify-between"
            >
              <div class="flex min-w-0 items-start gap-3">
                <UserAvatar :user :size="32" class="shrink-0" />
                <div class="min-w-0">
                  <div class="flex flex-wrap items-center gap-2">
                    <Link
                      :href="route('users.show', user.id)"
                      class="truncate text-sm font-medium text-foreground hover:underline"
                    >
                      {{ user.name }}
                    </Link>
                    <span
                      v-if="user.pivot?.via_dutiable_id"
                      class="border border-border bg-secondary px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                    >
                      {{ $t('Ex-officio') }}
                    </span>
                  </div>
                  <p class="mt-0.5 flex items-center gap-1 text-xs text-muted-foreground">
                    <Calendar class="size-3 shrink-0" />
                    <span>{{ formatTenure(user) }}</span>
                  </p>
                </div>
              </div>

              <div v-if="canAssignMembers" class="flex shrink-0 items-center gap-2">
                <Button
                  variant="ghost"
                  size="sm"
                  class="u-touch"
                  @click="openAssignSheet(user.pivot, user)"
                >
                  <Edit3 class="mr-1.5 size-3.5" />
                  {{ $t('Redaguoti') }}
                </Button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <!-- About Section -->
    <template #about>
      <div class="space-y-8 max-w-2xl">
        <div v-if="dutyDescriptionHtml">
          <h3 class="mb-3 text-base font-semibold text-foreground">
            {{ $t('Aprašymas') }}
          </h3>
          <!-- eslint-disable-next-line vue/no-v-html -->
          <div class="prose prose-sm dark:prose-invert max-w-none" v-html="dutyDescriptionHtml" />
        </div>
        <div v-else class="text-sm text-muted-foreground">
          {{ $t('Ši pareigybė neturi aprašymo.') }}
        </div>

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
      </div>
    </template>

    <!-- Files Section (if applicable) -->
    <template #files>
      <div class="space-y-6">
        <div v-if="duty.sharepointPath">
          <h3 class="mb-4 text-lg font-medium text-foreground">
            {{ $t('Pareigybės failai') }}
          </h3>
          <FileManager :starting-path="duty.sharepointPath" :fileable="{ id: duty.id, type: 'Duty' }" />
        </div>

        <div v-if="hasTypeFiles">
          <h3 class="mb-4 text-lg font-medium text-foreground">
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

        <div v-if="!duty.sharepointPath && !hasTypeFiles" class="py-8 text-center text-sm text-muted-foreground">
          <FolderOpen class="mx-auto size-8 text-muted-foreground/60" />
          <p class="mt-2 font-medium">
            {{ $t('Failų nėra') }}
          </p>
        </div>
      </div>
    </template>

    <!-- Activity Slot -->
    <template #activity>
      <RecordActivity
        subject-type="duty"
        :subject-id="duty.id"
        commentable-type="duty"
        :commentable-id="duty.id"
      />
    </template>
  </RecordPage>

  <!-- Sheets & Dialogs -->
  <AssignDutyUserSheet
    v-model:open="assignSheetOpen"
    :duty-id="duty.id"
    :duty
    :dutiable="selectedDutiable"
    :user="selectedUserForSheet"
    @success="handleAssignSuccess"
  />

  <DutiableTimelineDialog
    v-model:open="timelineOpen"
    scope-type="duty"
    :scope-id="duty.id"
  />
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Calendar,
  CalendarCheck,
  CalendarRange,
  ChevronRight,
  CircleCheck,
  Edit3,
  FolderOpen,
  Trash2,
  UserPlus,
  UserX,
} from 'lucide-vue-next';

import RecordPage, { type RecordFact, type RecordPageSection } from '@/Components/Layouts/RecordPage.vue';
import type { ActionDescriptor } from '@/Components/Layouts/RecordPageAction.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';
import RecordActivity from '@/Features/Admin/ActivityLogViewer/RecordActivity.vue';
import { AssignDutyUserSheet } from '@/Features/Admin/Occupancy';
import { DutiableTimelineDialog } from '@/Features/Admin/DutiableTimeline';
import FileManager from '@/Features/Admin/SharepointFileManager/SharepointFileManager.vue';
import SimpleFileViewer from '@/Features/Admin/SharepointFileManager/Viewer/SimpleFileViewer.vue';
import { Button } from '@/Components/ui/button';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { DutyIconFilled, InstitutionIconFilled } from '@/Components/icons';
import { ModelEnum } from '@/Types/enums';
import type { StatusPresentation } from '@/Constants/statuses';
import { formatStaticTime } from '@/Utils/IntlTime';

interface TranslatableText {
  lt?: string;
  en?: string;
}

const props = defineProps<{
  duty: App.Entities.Duty & {
    sharepointPath?: string | null;
    other_duties?: App.Entities.Duty[];
    next_meeting?: Record<string, unknown> | null;
    last_meeting?: Record<string, unknown> | null;
  };
  can?: { update: boolean; managePeople: boolean };
}>();

const currentSection = ref('members');
const assignSheetOpen = ref(false);
const timelineOpen = ref(false);
const selectedDutiable = ref<(App.Entities.Dutiable & Record<string, unknown>) | null>(null);
const selectedUserForSheet = ref<App.Entities.User | null>(null);

const canAssignMembers = computed(() => props.can?.managePeople ?? false);
const canManageDuty = computed(() => props.can?.update ?? false);

// Members split
const currentHolders = computed(() => {
  if (!props.duty.users) return [];
  return props.duty.users.filter((user: App.Entities.User) => {
    if (!user.pivot) return false;
    const end = user.pivot.end_date;
    return end === null || end === undefined || new Date(end) >= new Date();
  });
});

const historicalHolders = computed(() => {
  if (!props.duty.users) return [];
  return props.duty.users.filter((user: App.Entities.User) => {
    if (!user.pivot) return false;
    const end = user.pivot.end_date;
    return end !== null && end !== undefined && new Date(end) < new Date();
  });
});

const allMembers = computed(() => props.duty.users ?? []);
const isVacant = computed(() => currentHolders.value.length === 0);

const dutyTitle = computed(() => {
  if (typeof props.duty.name === 'string') return props.duty.name;
  const nameObj = props.duty.name as TranslatableText | undefined;
  return nameObj?.lt || nameObj?.en || '';
});

const dutyStatus = computed<StatusPresentation>(() => {
  if (isVacant.value) {
    return {
      label: $t('Neužimta'),
      role: 'attention',
      icon: UserX,
    };
  }
  return {
    label: $t('Užimta'),
    role: 'success',
    icon: CircleCheck,
  };
});

const otherDuties = computed(() => props.duty.other_duties ?? []);
const hasTypeFiles = computed(() => (props.duty.types?.length ?? 0) > 0);
const hasFiles = computed(() => !!props.duty.sharepointPath || hasTypeFiles.value);

const dutyDescriptionHtml = computed(() => {
  if (typeof props.duty.description === 'string') return props.duty.description;
  const descObj = props.duty.description as TranslatableText | undefined;
  return descObj?.lt || descObj?.en || null;
});

// Facts Strip
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
    label: $t('Vietų skaičius'),
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
      label: $t('Kategorija / Tipai'),
      value: props.duty.types.map(t => t.title).join(', '),
    });
  }

  return facts;
});

// Tabs
const tabs = computed<RecordPageSection[]>(() => {
  const sections: RecordPageSection[] = [
    { value: 'members', label: $t('Nariai'), count: allMembers.value.length },
    { value: 'about', label: $t('Apie pareigybę') },
  ];

  if (hasFiles.value) {
    sections.push({ value: 'files', label: $t('Failai') });
  }

  return sections;
});

// Primary & Overflow Actions
const primaryAction = computed<ActionDescriptor | undefined>(() => {
  if (canAssignMembers.value) {
    return {
      key: 'assign',
      label: $t('Priskirti narį'),
      icon: UserPlus,
    };
  }
  if (canManageDuty.value) {
    return {
      key: 'edit',
      label: $t('Redaguoti pareigybę'),
      icon: Edit3,
    };
  }
  return undefined;
});

const overflowActions = computed<ActionDescriptor[]>(() => {
  const actions: ActionDescriptor[] = [
    {
      key: 'timeline',
      label: $t('dutiables.timeline.open'),
      icon: CalendarRange,
    },
  ];

  if (canManageDuty.value) {
    actions.push({
      key: 'edit',
      label: $t('Redaguoti pareigybę'),
      icon: Edit3,
    });
    actions.push({
      key: 'delete',
      label: $t('Ištrinti pareigybę'),
      icon: Trash2,
      destructive: true,
    });
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
      if (confirm($t('Ar tikrai norite ištrinti šią pareigybę?'))) {
        router.delete(route('duties.destroy', props.duty.id));
      }
      break;
  }
};

const openAssignSheet = (dutiable?: (App.Entities.Dutiable & Record<string, unknown>) | null, user?: App.Entities.User | null) => {
  selectedDutiable.value = dutiable ?? null;
  selectedUserForSheet.value = user ?? null;
  assignSheetOpen.value = true;
};

const handleAssignSuccess = () => {
  router.reload({ only: ['duty'] });
};

const endTenure = (dutiable: (App.Entities.Dutiable & { id: string | number }) | null) => {
  if (!dutiable) return;
  if (confirm($t('Ar tikrai norite baigti šio nario kadenciją šiandien?'))) {
    const today = new Date().toISOString().split('T')[0];
    router.patch(route('dutiables.update', dutiable.id), {
      end_date: today,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        router.reload({ only: ['duty'] });
      },
    });
  }
};

const formatTenure = (user: App.Entities.User) => {
  const start = user.pivot?.start_date;
  if (!start) return '';
  const startLabel = formatStaticTime(new Date(start), { year: 'numeric', month: 'short' });
  const end = user.pivot?.end_date;
  if (!end || new Date(end) >= new Date()) {
    return `${startLabel} – ${$t('dabar')}`;
  }
  return `${startLabel} – ${formatStaticTime(new Date(end), { year: 'numeric', month: 'short' })}`;
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
