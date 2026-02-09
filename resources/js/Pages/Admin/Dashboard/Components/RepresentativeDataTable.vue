<template>
  <Dialog :open="isOpen" @update:open="emit('update:isOpen', $event)">
    <DialogContent class="max-w-[95vw] sm:max-w-[90vw] w-full max-h-[90vh] sm:max-h-[85vh] overflow-y-auto p-4 sm:p-6">
      <DialogHeader class="pb-3">
        <DialogTitle class="text-lg sm:text-xl">
          {{ $t('Visi atstovai') }}
        </DialogTitle>
        <DialogDescription class="text-sm">
          {{ $t('Peržiūrėkite visų atstovų prisijungimo aktyvumą') }}
        </DialogDescription>
      </DialogHeader>

      <div class="space-y-4">
        <!-- Tabs for Active vs Inactive -->
        <Tabs v-model="activeTab" class="w-full">
          <TabsList class="w-full overflow-x-auto scrollbar-none">
            <TabsTrigger value="all" class="flex-1 whitespace-nowrap text-xs sm:text-sm">
              {{ $t('Visi') }} ({{ users.length }})
            </TabsTrigger>
            <TabsTrigger value="active" class="flex-1 whitespace-nowrap text-xs sm:text-sm gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-emerald-500 dark:bg-emerald-400" />
              {{ $t('Aktyvūs') }} ({{ activeUsers.length }})
            </TabsTrigger>
            <TabsTrigger value="inactive" class="flex-1 whitespace-nowrap text-xs sm:text-sm gap-1.5">
              <div class="h-1.5 w-1.5 rounded-full bg-red-500 dark:bg-red-400" />
              {{ $t('Neaktyvūs') }} ({{ inactiveUsers.length }})
            </TabsTrigger>
          </TabsList>

          <TabsContent value="all" class="space-y-4 mt-4">
            <UserListContent
              :users="filteredUsers('all')"
              :search-query
              @update:search-query="searchQuery = $event"
            />
          </TabsContent>

          <TabsContent value="active" class="space-y-4 mt-4">
            <UserListContent
              :users="filteredUsers('active')"
              :search-query
              @update:search-query="searchQuery = $event"
            />
          </TabsContent>

          <TabsContent value="inactive" class="space-y-4 mt-4">
            <UserListContent
              :users="filteredUsers('inactive')"
              :search-query
              @update:search-query="searchQuery = $event"
            />
          </TabsContent>
        </Tabs>
      </div>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="tsx">
import { computed, ref, watch, type FunctionalComponent } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Users, AlertCircle, Search } from 'lucide-vue-next';

import type { RepresentativeUser, RepresentativeActivityCategory } from '../types';
import { getActivityDotClasses, getActivityTextClasses, getActivityBadgeClasses, getActivityShortLabel } from '../Composables/useActivityStatus';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { formatRelativeTime } from '@/Utils/IntlTime';

interface Props {
  users: RepresentativeUser[];
  isOpen: boolean;
  initialTab?: 'active' | 'inactive';
}

const props = withDefaults(defineProps<Props>(), {
  initialTab: 'inactive',
});

const emit = defineEmits<{
  'update:isOpen': [value: boolean];
}>();

const activeTab = ref<'all' | 'active' | 'inactive'>(props.initialTab);
const searchQuery = ref('');

// Reset tab when modal opens with initialTab
watch(() => props.isOpen, (isOpen) => {
  if (isOpen) {
    activeTab.value = props.initialTab || 'inactive';
    searchQuery.value = '';
  }
});

// Split users by activity status
const activeUsers = computed(() => {
  return props.users.filter(u =>
    u.category === 'today' || u.category === 'week' || u.category === 'month',
  );
});

const inactiveUsers = computed(() => {
  return props.users.filter(u =>
    u.category === 'never' || u.category === 'stale',
  );
});

function filteredUsers(tab: 'all' | 'active' | 'inactive') {
  let base: RepresentativeUser[];
  switch (tab) {
    case 'active':
      base = activeUsers.value;
      break;
    case 'inactive':
      base = inactiveUsers.value;
      break;
    default:
      base = props.users;
  }

  if (!searchQuery.value.trim()) return base;

  const query = searchQuery.value.toLowerCase();
  return base.filter(u =>
    u.name.toLowerCase().includes(query)
    || u.email.toLowerCase().includes(query)
    || u.duties.some(d =>
      d.name.toLowerCase().includes(query)
      || d.institution_name?.toLowerCase().includes(query),
    ),
  );
}

function getInitials(name: string): string {
  return name
    .split(' ')
    .map(n => n[0])
    .join('')
    .toUpperCase()
    .slice(0, 2);
}

function getCategoryClasses(category: string) {
  // Use composable for consistent colors across all components
  const typedCategory = category as RepresentativeActivityCategory;
  return {
    dot: getActivityDotClasses(typedCategory),
    text: getActivityTextClasses(typedCategory),
    badge: getActivityBadgeClasses(typedCategory),
  };
}

function getCategoryLabel(category: string): string {
  // Use composable for consistent labels
  return getActivityShortLabel(category as RepresentativeActivityCategory);
}

// UserListContent as inline component for reuse across tabs
const UserListContent: FunctionalComponent<{
  users: RepresentativeUser[];
  searchQuery: string;
}> = (props, { emit }) => {
  return (
    <>
      {/* Search */}
      <div class="relative">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-zinc-400" />
        <input
          value={props.searchQuery}
          onInput={e => emit('update:search-query', (e.target as HTMLInputElement).value)}
          type="text"
          placeholder={$t('Ieškoti atstovų...')}
          class="w-full pl-9 pr-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
        />
      </div>

      {/* User list */}
      <div class="space-y-2 max-h-[60vh] sm:max-h-[500px] overflow-y-auto">
        {props.users.map((user) => {
          const classes = getCategoryClasses(user.category);
          return (
            <div
              key={user.id}
              class="flex items-center gap-3 p-3 rounded-lg bg-white dark:bg-zinc-800/50 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors"
            >
              {/* Avatar with status */}
              <div class="relative shrink-0">
                <Avatar class="h-10 w-10">
                  {user.profile_photo_path
                    ? (
                        <AvatarImage src={user.profile_photo_path} />
                      )
                    : null}
                  <AvatarFallback class="text-sm bg-zinc-200 dark:bg-zinc-700">
                    {getInitials(user.name)}
                  </AvatarFallback>
                </Avatar>
                <div
                  class={['absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-white dark:border-zinc-800', classes.dot].join(' ')}
                />
              </div>

              {/* User info */}
              <div class="flex-1 min-w-0">
                <div class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
                  {user.name}
                </div>
                <div class="text-sm text-zinc-500 dark:text-zinc-400 truncate">
                  {user.email}
                </div>
                {user.duties.length > 0 && (
                  <div class="text-xs text-zinc-400 dark:text-zinc-500 mt-1 truncate">
                    {user.duties.map(d => d.institution_name).filter(Boolean).join(', ')}
                  </div>
                )}
              </div>

              {/* Activity badge */}
              <div class="shrink-0 text-right">
                <span class={['inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium', classes.badge].join(' ')}>
                  <span class={['h-1.5 w-1.5 rounded-full', classes.dot].join(' ')} />
                  {getCategoryLabel(user.category)}
                </span>
                {user.last_action && (
                  <div class={['text-xs mt-1', classes.text].join(' ')}>
                    {formatRelativeTime(new Date(user.last_action))}
                  </div>
                )}
              </div>
            </div>
          );
        })}
      </div>

      {/* Empty state */}
      {props.users.length === 0 && (
        <div class="text-center py-8 text-zinc-500 dark:text-zinc-400">
          <Users class="h-12 w-12 mx-auto mb-4 opacity-50" />
          <p>{props.searchQuery ? $t('Atstovų nerasta pagal paiešką') : $t('Atstovų nerasta')}</p>
        </div>
      )}
    </>
  );
};
</script>
