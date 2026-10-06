<template>
  <!-- Everything else — other workspaces, account, help — sits behind Meniu. -->
  <nav
    data-slot="mobile-bottom-bar"
    :aria-label="$t('shell.chrome.main_nav')"
    :class="[
      'flex h-(--shell-bottom-bar) shrink-0 items-stretch border-t border-border bg-background pb-[env(safe-area-inset-bottom,0px)] md:hidden',
      'touch-manipulation select-none [-webkit-tap-highlight-color:transparent]',
    ]"
  >
    <Link
      :href="route('dashboard')"
      prefetch
      :cache-for="SHELL_PREFETCH_CACHE_FOR"
      :data-active="activeTab === 'pradzia'"
      :class="tabClass(activeTab === 'pradzia')"
      @click="press('pradzia', route('dashboard'))"
    >
      <span :class="markerClass(activeTab === 'pradzia')" aria-hidden="true" />
      <span :class="iconClass">
        <component :is="workspaceIcon('pradzia')" class="size-5" />
      </span>
      <span>{{ $t('shell.workspaces.pradzia.title') }}</span>
    </Link>

    <Link
      :href="route('tasks.index')"
      prefetch
      :cache-for="SHELL_PREFETCH_CACHE_FOR"
      :data-active="activeTab === 'uzduotys'"
      :class="tabClass(activeTab === 'uzduotys')"
      @click="press('uzduotys', route('tasks.index'))"
    >
      <span :class="markerClass(activeTab === 'uzduotys')" aria-hidden="true" />
      <span :class="['relative', iconClass]">
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
        class="u-touch transition-transform duration-150 ease-out active:scale-90"
        :aria-label="$t('shell.chrome.create')"
        @click="emit('create')"
      >
        <Plus class="size-5" />
      </Button>
    </div>

    <Link
      :href="route('notifications.index')"
      prefetch
      :cache-for="SHELL_PREFETCH_CACHE_FOR"
      :data-active="activeTab === 'pranesimai'"
      :class="tabClass(activeTab === 'pranesimai')"
      @click="press('pranesimai', route('notifications.index'))"
    >
      <span :class="markerClass(activeTab === 'pranesimai')" aria-hidden="true" />
      <span :class="['relative', iconClass]">
        <Bell class="size-5" />
        <span
          v-if="unreadCount > 0"
          :key="unreadPops"
          data-slot="notification-count"
          :class="[
            'absolute -top-1.5 left-3.5 flex h-4 min-w-4 items-center justify-center bg-brand-fill px-0.5 text-[11px] font-bold leading-none tabular-nums text-brand-foreground',
            unreadPops > 0 && 'animate-badge-pop',
          ]"
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
      :data-active="menuActive"
      :class="tabClass(menuActive)"
      :aria-expanded="menuOpen"
      @click="emit('menu')"
    >
      <span :class="markerClass(menuActive)" aria-hidden="true" />
      <span :class="iconClass">
        <Menu class="size-5" />
      </span>
      <span>{{ $t('shell.chrome.menu') }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Bell, ClipboardCheck, Menu, Plus } from 'lucide-vue-next';
import { computed, onUnmounted, ref, watch } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import { SHELL_PREFETCH_CACHE_FOR, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { useUnreadNotificationCount } from '@/Composables/useUnreadNotificationCount';

type Tab = 'pradzia' | 'uzduotys' | 'pranesimai' | 'menu';

const props = defineProps<{
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  canCreate?: boolean;
  menuOpen?: boolean;
}>();

const emit = defineEmits<{ create: []; menu: [] }>();

const unreadCount = useUnreadNotificationCount();

const routeTab = computed<Tab | null>(() => {
  if (props.activeWorkspace?.key !== 'pradzia') {
    return props.activeWorkspace === undefined ? null : 'menu';
  }

  const section = props.activeSection?.key;

  return section === 'uzduotys' || section === 'pranesimai' ? section : 'pradzia';
});

// Light the tapped tab straight away rather than after the server answers; a slow phone otherwise feels ignored.
const pending = ref<{ tab: Tab; path: string } | null>(null);
const activeTab = computed(() => pending.value?.tab ?? routeTab.value);
const menuActive = computed(() => props.menuOpen || activeTab.value === 'menu');

const press = (tab: Tab, href: string) => {
  if (tab !== routeTab.value) {
    pending.value = { tab, path: new URL(href, window.location.origin).pathname };
  }
};

const page = usePage();

watch(() => page.url, () => {
  pending.value = null;
});

// Prefetches and cancelled deferred-prop reloads finish too; only the tapped visit failing may un-light the tab.
const stopFinish = router.on('finish', (event) => {
  const { visit } = event.detail;

  if (!visit.prefetch && visit.url.pathname === pending.value?.path) {
    pending.value = null;
  }
});

onUnmounted(stopFinish);

// Re-keyed only on a rise, so the badge pops for new notifications but not on every page it reappears on.
const unreadPops = ref(0);

watch(unreadCount, (next, previous) => {
  if (next > previous) {
    unreadPops.value++;
  }
});

const tabClass = (active: boolean) => [
  'group u-touch relative flex flex-1 flex-col items-center justify-center gap-0.5 text-xs transition-colors active:bg-muted',
  active ? 'font-semibold text-foreground' : 'text-muted-foreground',
];

const markerClass = (active: boolean) => [
  'absolute inset-x-0 top-0 h-0.5 origin-center bg-brand-fill transition-transform duration-200 ease-out',
  active ? 'scale-x-100' : 'scale-x-0',
];

const iconClass = 'transition-transform duration-150 ease-out group-active:scale-90';
</script>
