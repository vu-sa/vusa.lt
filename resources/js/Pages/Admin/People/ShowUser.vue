<template>
  <AdminContentPage>
    <InertiaHead :title="user.name" />

    <!-- User Hero -->
    <ShowPageHero
      flat
      :title="user.name"
      :subtitle
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
            {{ duty.name }}
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
    </ShowPageHero>

    <!-- Main Content with Tabs -->
    <Tabs v-model="currentTab" class="mt-6">
      <TabsList class="mb-4">
        <TabsTrigger value="overview">
          {{ $t('Apžvalga') }}
        </TabsTrigger>
        <TabsTrigger value="duties">
          <span>{{ $t('Pareigos') }}</span>
          <span v-if="allDuties.length" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ allDuties.length }}
          </span>
        </TabsTrigger>
        <TabsTrigger value="tasks">
          <span>{{ $t('Užduotys') }}</span>
          <span v-if="taskStats.total > 0" class="ml-1.5 text-xs font-normal text-zinc-400 dark:text-zinc-500">
            {{ taskStats.total }}
          </span>
        </TabsTrigger>
      </TabsList>

      <!-- Overview Tab -->
      <TabsContent value="overview">
        <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
          <!-- Main column -->
          <div class="space-y-6 xl:col-span-2">
            <!-- Contact Info -->
            <Card>
              <CardHeader class="pb-3">
                <CardTitle class="text-base">
                  {{ $t('Kontaktinė informacija') }}
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
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
              </CardContent>
            </Card>

            <!-- Roles -->
            <Card v-if="roles.length">
              <CardHeader class="pb-3">
                <CardTitle class="text-base">
                  {{ $t('Rolės') }}
                </CardTitle>
              </CardHeader>
              <CardContent>
                <div class="flex flex-wrap gap-1.5">
                  <Badge v-for="role in roles" :key="role.id" variant="secondary">
                    {{ role.name }}
                  </Badge>
                </div>
              </CardContent>
            </Card>

            <!-- Current Duties Summary -->
            <Card v-if="currentDuties.length">
              <CardHeader class="pb-3">
                <CardTitle class="text-base">
                  {{ $t('Dabartinės pareigos') }}
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-2">
                <DutySummaryCard
                  v-for="duty in currentDuties"
                  :key="duty.id"
                  :duty
                  :exclude-user-id="user.id"
                />
              </CardContent>
            </Card>
          </div>

          <!-- Sidebar -->
          <div class="space-y-6 xl:sticky xl:top-6 xl:self-start">
            <!-- Activity Summary -->
            <Card>
              <CardHeader class="pb-3">
                <CardTitle class="text-base">
                  {{ $t('Aktyvumas') }}
                </CardTitle>
              </CardHeader>
              <CardContent class="space-y-3">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">{{ $t('Dabartinių pareigų') }}</span>
                  <span class="text-sm font-medium">{{ currentDuties.length }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">{{ $t('Buvusių pareigų') }}</span>
                  <span class="text-sm font-medium">{{ previousDuties.length }}</span>
                </div>
                <div class="flex items-center justify-between">
                  <span class="text-sm text-muted-foreground">{{ $t('Užduočių') }}</span>
                  <span class="text-sm font-medium">{{ taskStats.total }}</span>
                </div>
              </CardContent>
            </Card>
          </div>
        </div>
      </TabsContent>

      <!-- Duties Tab -->
      <TabsContent value="duties">
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
                muted
              />
            </div>
          </div>

          <div v-if="!allDuties.length" class="py-8 text-center">
            <p class="text-muted-foreground">
              {{ $t('Nėra priskirtų pareigų') }}
            </p>
          </div>
        </div>
      </TabsContent>

      <!-- Tasks Tab -->
      <TabsContent value="tasks">
        <TaskManager
          :tasks
          :task-stats
          :disabled="false"
          @open-task-detail="handleOpenTaskDetail"
        />
      </TabsContent>
    </Tabs>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { router, Head as InertiaHead, usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Pencil,
  KeyRound,
  Mail,
  Phone,
  Facebook,
} from 'lucide-vue-next';

// Layout and Components
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import MoreOptionsButton from '@/Components/Buttons/MoreOptionsButton.vue';
import TaskManager from '@/Features/Admin/TaskManager/TaskManager.vue';
import { DutySummaryCard } from '@/Components/Duties';

// UI Components
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
} from '@/Components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';

// Utils
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { UserIconFilled } from '@/Components/icons';

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

// Tab state
const currentTab = useStorage('show-user-tab', 'overview');
if (!['overview', 'duties', 'tasks'].includes(currentTab.value)) {
  currentTab.value = 'overview';
}

// Computed
const currentDuties = computed(() => props.user.current_duties ?? []);
const previousDuties = computed(() => props.user.previous_duties ?? []);
const allDuties = computed(() => [...currentDuties.value, ...previousDuties.value]);
const roles = computed(() => props.user.roles ?? []);

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
    return p.lt || p.en || null;
  }
  return null;
});

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
