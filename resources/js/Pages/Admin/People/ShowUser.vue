<template>
  <ShowPageLayout
    :title="user.name"
    :subtitle
    :model="user"
    audit-subject-type="user"
    :tabs
    tab-storage-key="show-user-tab"
  >
    <template #icon>
      <div
        class="shrink-0 h-12 w-12 sm:h-14 sm:w-14 rounded-lg flex items-center justify-center border border-zinc-200 dark:border-zinc-600 overflow-hidden"
      >
        <img
          v-if="user.profile_photo_path"
          :src="user.profile_photo_path"
          :alt="user.name"
          class="h-full w-full object-cover"
          :style="focalPointStyle"
        >
        <UserIconFilled v-else class="h-6 w-6 sm:h-7 sm:w-7 text-zinc-600 dark:text-zinc-300" />
      </div>
    </template>
    <template #badge>
      <Badge v-if="pronounsBadge" variant="secondary" class="text-xs">
        {{ pronounsBadge }}
      </Badge>
    </template>
    <template #info>
      <div v-if="currentDuties.length > 0" class="flex items-center gap-2 flex-wrap">
        <span class="text-xs text-zinc-500 dark:text-zinc-400">{{ $t('Pareigos') }}:</span>
        <Badge
          v-for="duty in visibleCurrentDuties"
          :key="duty.id"
          variant="outline"
          class="text-xs"
        >
          {{ inflectedDutyName(duty) }}
          <span v-if="duty.institution?.name" class="text-muted-foreground">@ {{ duty.institution.name }}</span>
        </Badge>
        <span v-if="hiddenDutyCount > 0" class="text-xs text-muted-foreground">
          +{{ hiddenDutyCount }}
        </span>
      </div>
    </template>
    <template #actions>
      <Button v-if="canEdit" variant="outline" size="sm" class="gap-2" @click="handleEdit">
        <Pencil class="h-4 w-4" />
        {{ $t('Redaguoti') }}
      </Button>
      <Button
        v-if="canGeneratePassword && !user.has_password"
        variant="outline"
        size="sm"
        class="gap-2"
        @click="handleGeneratePassword"
      >
        <KeyRound class="h-4 w-4" />
        {{ $t('Sugeneruoti slaptažodį') }}
      </Button>
      <MoreOptionsButton edit delete @edit-click="handleEdit" @delete-click="handleDelete" />
    </template>

    <!-- Overview Tab -->
    <template #overview>
      <ShowPageGrid>
        <template #main>
          <SectionCard :title="$t('Kontaktinė informacija')" :icon="Mail">
            <div class="space-y-3">
              <div v-if="user.email" class="flex items-center gap-3">
                <Mail class="h-4 w-4 text-muted-foreground" />
                <a :href="`mailto:${user.email}`" class="text-sm hover:text-vusa-red transition">
                  {{ user.email }}
                </a>
              </div>
              <div v-if="user.phone" class="flex items-center gap-3">
                <Phone class="h-4 w-4 text-muted-foreground" />
                <a :href="`tel:${user.phone}`" class="text-sm hover:text-vusa-red transition">
                  {{ user.phone }}
                </a>
              </div>
              <div v-if="user.facebook_url" class="flex items-center gap-3">
                <Facebook class="h-4 w-4 text-muted-foreground" />
                <a :href="user.facebook_url" target="_blank" rel="noopener" class="text-sm hover:text-vusa-red transition">
                  {{ $t('Facebook') }}
                </a>
              </div>
            </div>
          </SectionCard>

          <SectionCard v-if="roles.length" :title="$t('Rolės')" :icon="Shield" :count="roles.length">
            <div class="flex flex-wrap gap-1.5">
              <Badge v-for="role in roles" :key="role.id" variant="secondary">
                {{ role.name }}
              </Badge>
            </div>
          </SectionCard>

          <SectionCard
            v-if="currentDuties.length"
            :title="$t('Dabartinės pareigos')"
            :icon="Briefcase"
            :count="currentDuties.length"
          >
            <div class="space-y-2">
              <DutySummaryCard
                v-for="duty in currentDuties"
                :key="duty.id"
                :duty
                :exclude-user-id="user.id"
                :holder="dutyHolder"
              />
            </div>
          </SectionCard>
        </template>

        <template #sidebar>
          <SectionCard :title="$t('Aktyvumas')" :icon="Activity">
            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">{{ $t('Dabartinių pareigų') }}</span>
                <span class="text-sm font-medium tabular-nums">{{ currentDuties.length }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">{{ $t('Buvusių pareigų') }}</span>
                <span class="text-sm font-medium tabular-nums">{{ previousDuties.length }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-muted-foreground">{{ $t('Užduočių') }}</span>
                <span class="text-sm font-medium tabular-nums">{{ taskStats.total }}</span>
              </div>
            </div>
          </SectionCard>
        </template>
      </ShowPageGrid>
    </template>

    <!-- Duties Tab -->
    <template #duties>
      <div class="space-y-6">
        <!-- Current Duties -->
        <div v-if="currentDuties.length">
          <h3 class="mb-3 text-lg font-medium">
            {{ $t('Dabartinės pareigos') }}
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <DutySummaryCard
              v-for="duty in currentDuties"
              :key="duty.id"
              :duty
              :exclude-user-id="user.id"
              :holder="dutyHolder"
            />
          </div>
        </div>

        <!-- Previous Duties -->
        <div v-if="previousDuties.length">
          <h3 class="mb-3 text-lg font-medium">
            {{ $t('Buvusios pareigos') }}
          </h3>
          <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
            <DutySummaryCard
              v-for="duty in previousDuties"
              :key="duty.id"
              :duty
              :exclude-user-id="user.id"
              :holder="dutyHolder"
              muted
            />
          </div>
        </div>

        <EmptyState
          v-if="!allDuties.length"
          :title="$t('Pareigų nėra')"
          :description="$t('Nėra priskirtų pareigų')"
        >
          <template #icon>
            <Briefcase class="h-10 w-10 text-muted-foreground" />
          </template>
        </EmptyState>
      </div>
    </template>

    <!-- Tasks Tab -->
    <template #tasks>
      <TaskManager
        :tasks
        :task-stats
        :disabled="false"
        @open-task-detail="handleOpenTaskDetail"
      />
    </template>
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import { computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Activity,
  Briefcase,
  Pencil,
  KeyRound,
  Mail,
  Phone,
  Facebook,
  Shield,
} from 'lucide-vue-next';

// Layout and Components
import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { EmptyState, SectionCard, ShowPageGrid } from '@/Components/Patterns';
import { DutySummaryCard } from '@/Components/Duties';

// UI Components
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';

// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { UserIconFilled } from '@/Components/icons';
import { changeDutyNameEndings } from '@/Utils/String';

const props = defineProps<{
  user: App.Entities.User & {
    current_duties: Array<App.Entities.Duty & { current_users?: App.Entities.User[]; pivot?: { start_date?: string; end_date?: string | null; additional_email?: string } }>;
    previous_duties: Array<App.Entities.Duty & { current_users?: App.Entities.User[]; pivot?: { start_date?: string; end_date?: string | null; additional_email?: string } }>;
    roles: Array<{ id: string; name: string }>;
    has_password: boolean;
  };
  tasks: Array<{
    id: string;
    name: string;
    description?: string | null;
    due_date?: string | null;
    completed_at?: string | null;
    created_at: string;
    action_type?: string | null;
    metadata?: Record<string, unknown>;
    progress?: { items_completed: number; items_total: number } | null;
    is_overdue?: boolean;
    can_be_manually_completed?: boolean;
    icon?: string;
    color?: string;
    taskable?: { id: string; name?: string | null; type?: string } | null;
    taskable_type: string;
    taskable_id: string;
    users?: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  }>;
  taskStats: {
    total: number;
    completed: number;
    pending: number;
    overdue: number;
    autoCompleting: number;
  };
}>();

// Computed
const currentDuties = computed(() => props.user.current_duties ?? []);
const previousDuties = computed(() => props.user.previous_duties ?? []);
const allDuties = computed(() => [...currentDuties.value, ...previousDuties.value]);
const roles = computed(() => props.user.roles ?? []);

const tabs = computed(() => [
  { value: 'overview', label: $t('Apžvalga') },
  { value: 'duties', label: $t('Pareigos'), count: allDuties.value.length },
  { value: 'tasks', label: $t('Užduotys'), count: props.taskStats.total },
]);

const subtitle = computed(() => {
  const parts: string[] = [];
  if (props.user.email) parts.push(props.user.email);
  if (props.user.phone) parts.push(props.user.phone);
  return parts.join(' • ') || undefined;
});

const pronounsBadge = computed(() => {
  if (!props.user.show_pronouns || !props.user.pronouns) return null;
  const p = props.user.pronouns;
  if (typeof p === 'string') return p;
  if (typeof p === 'object' && p !== null) {
    return getTranslatedValue(p) || null;
  }
  return null;
});

/**
 * The person whose duties are being listed — drives the duty-name ending
 * inflection (e.g. "Koordinatorius" → "Koordinatorė") so this profile's duties
 * read in their gender, matching the public contacts page.
 */
const dutyHolder = computed(() => ({
  name: props.user.name,
  pronouns: props.user.pronouns,
}));

// Used in the hero badges, where DutySummaryCard isn't rendered.
const inflectedDutyName = (duty: { name: string; pivot?: { use_original_duty_name?: boolean } | null }) => {
  const { locale } = usePage().props.app;
  const rawPronouns = props.user.pronouns;
  const pronouns = typeof rawPronouns === 'string'
    ? rawPronouns
    : (rawPronouns?.[locale as 'lt' | 'en'] ?? '');
  return changeDutyNameEndings(
    props.user,
    duty.name,
    locale,
    pronouns,
    duty.pivot?.use_original_duty_name ?? false,
  );
};

const VISIBLE_DUTY_LIMIT = 3;
const visibleCurrentDuties = computed(() => currentDuties.value.slice(0, VISIBLE_DUTY_LIMIT));
const hiddenDutyCount = computed(() => Math.max(0, currentDuties.value.length - VISIBLE_DUTY_LIMIT));

const focalPointStyle = computed(() => {
  if (!props.user.profile_photo_focal_point) return {};
  return { objectPosition: props.user.profile_photo_focal_point };
});

// Permissions
const page = usePage();
const canEdit = computed(() => page.props.auth?.can?.['users.update.padalinys'] || false);
const canGeneratePassword = computed(() => page.props.auth?.can?.['users.update.padalinys'] && page.props.auth?.user?.is_super_admin);

// Event handlers
const handleEdit = () => {
  router.get(route('users.edit', props.user.id));
};

const handleDelete = () => {
  router.delete(route('users.destroy', props.user.id));
};

const handleGeneratePassword = () => {
  router.post(route('users.generatePassword', props.user.id));
};

const handleOpenTaskDetail = (task: unknown) => {
  // Task detail modal could be opened here
  // For now, we just acknowledge the event
};

// Breadcrumbs
usePageBreadcrumbs(() =>
  BreadcrumbHelpers.adminShow(
    $t('Nariai'),
    'users.index',
    undefined,
    props.user.name,
    UserIconFilled,
    UserIconFilled,
  ),
);
</script>
