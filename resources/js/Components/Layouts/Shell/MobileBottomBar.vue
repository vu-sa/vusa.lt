<template>
  <!-- Everything else — other workspaces, account, help — sits behind Meniu. -->
  <nav
    data-slot="mobile-bottom-bar"
    :aria-label="$t('shell.chrome.main_nav')"
    class="flex h-(--shell-bottom-bar) shrink-0 items-stretch border-t border-border bg-background pb-[env(safe-area-inset-bottom,0px)] md:hidden"
  >
    <Link :href="route('dashboard')" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR" :class="tabClass(onPradzia && !['uzduotys', 'pranesimai'].includes(activeSection?.key ?? ''))">
      <component :is="workspaceIcon('pradzia')" class="size-5" />
      <span>{{ $t('shell.workspaces.pradzia.title') }}</span>
    </Link>

    <Link :href="route('userTasks')" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR" :class="tabClass(onPradzia && activeSection?.key === 'uzduotys')">
      <span class="relative">
        <ClipboardCheck class="size-5" />
        <TaskCountBadge compact class="absolute -top-1.5 left-3.5" />
      </span>
      <span>{{ $t('shell.sections.uzduotys') }}</span>
    </Link>

    <div v-if="canCreate" class="flex flex-1 items-center justify-center">
      <Button
        data-tour="action-create-mobile"
        variant="brand"
        size="icon"
        class="u-touch"
        :aria-label="$t('shell.chrome.create')"
        @click="emit('create')"
      >
        <Plus class="size-5" />
      </Button>
    </div>

    <Link :href="route('notifications.index')" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR" :class="tabClass(onPradzia && activeSection?.key === 'pranesimai')">
      <span class="relative">
        <Bell class="size-5" />
        <span
          v-if="unreadCount > 0"
          data-slot="notification-count"
          class="absolute -top-1.5 left-3.5 flex h-4 min-w-4 items-center justify-center bg-brand-fill px-0.5 text-[11px] font-bold leading-none tabular-nums text-brand-foreground"
        >
          <span aria-hidden="true">{{ unreadCount > 9 ? '9+' : unreadCount }}</span>
          <span class="sr-only">{{ $t('shell.badges.notifications_unread', { count: String(unreadCount) }) }}</span>
        </span>
      </span>
      <span>{{ $t('shell.sections.pranesimai') }}</span>
    </Link>

    <button
      data-tour="mobile-menu"
      type="button"
      :class="tabClass(menuOpen || (activeWorkspace !== undefined && !onPradzia))"
      :aria-expanded="menuOpen"
      @click="emit('menu')"
    >
      <Menu class="size-5" />
      <span>{{ $t('shell.chrome.menu') }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, ClipboardCheck, Menu, Plus } from 'lucide-vue-next';
import { computed } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import { SHELL_PREFETCH_CACHE_FOR, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { useUnreadNotificationCount } from '@/Composables/useUnreadNotificationCount';

const props = defineProps<{
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  canCreate?: boolean;
  menuOpen?: boolean;
}>();

const emit = defineEmits<{ create: []; menu: [] }>();

const onPradzia = computed(() => props.activeWorkspace?.key === 'pradzia');
const unreadCount = useUnreadNotificationCount();

const tabClass = (active: boolean) => [
  'u-touch flex flex-1 flex-col items-center justify-center gap-0.5 border-t-2 text-xs transition-colors',
  active ? 'border-brand-fill font-semibold text-foreground' : 'border-transparent text-muted-foreground',
];
</script>
