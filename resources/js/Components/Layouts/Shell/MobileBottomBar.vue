<template>
  <nav
    data-slot="mobile-bottom-bar"
    :aria-label="$t('shell.chrome.main_nav')"
    class="flex h-(--shell-bottom-bar) shrink-0 items-stretch border-t border-border bg-background pb-[env(safe-area-inset-bottom,0px)] md:hidden"
  >
    <Link :href="route('dashboard')" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR" :class="tabClass(activeWorkspace?.key === 'pradzia' && activeSection?.key !== 'uzduotys')">
      <component :is="workspaceIcon('pradzia')" class="size-5" />
      <span>{{ $t('shell.workspaces.pradzia.title') }}</span>
    </Link>

    <Link
      v-if="primary"
      :href="workspaceHref(primary) ?? '#'"
      prefetch
      :cache-for="SHELL_PREFETCH_CACHE_FOR"
      :class="tabClass(activeWorkspace?.key === primary.key)"
    >
      <component :is="workspaceIcon(primary.key)" class="size-5" />
      <span>{{ $t(primary.label) }}</span>
    </Link>

    <div v-if="canCreate" class="flex flex-1 items-center justify-center">
      <Button variant="brand" size="icon" class="u-touch" :aria-label="$t('shell.chrome.create')" @click="emit('create')">
        <Plus class="size-5" />
      </Button>
    </div>

    <Link :href="route('userTasks')" prefetch :cache-for="SHELL_PREFETCH_CACHE_FOR" :class="tabClass(activeSection?.key === 'uzduotys' && activeWorkspace?.key === 'pradzia')">
      <span class="relative">
        <ClipboardCheck class="size-5" />
        <TaskCountBadge class="absolute -top-2 left-3" />
      </span>
      <span>{{ $t('shell.sections.uzduotys') }}</span>
    </Link>

    <button type="button" :class="tabClass(false)" :aria-expanded="menuOpen" @click="emit('menu')">
      <Menu class="size-5" />
      <span>{{ $t('shell.chrome.menu') }}</span>
    </button>
  </nav>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ClipboardCheck, Menu, Plus } from 'lucide-vue-next';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import {
  SHELL_PREFETCH_CACHE_FOR,
  workspaceHref,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';

defineProps<{
  /** The workspace beside Pradžia — see `primaryWorkspace()`. */
  primary?: AdminWorkspace;
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  canCreate?: boolean;
  menuOpen?: boolean;
}>();

const emit = defineEmits<{ create: []; menu: [] }>();

const tabClass = (active: boolean) => [
  'u-touch flex flex-1 flex-col items-center justify-center gap-0.5 border-t-2 text-xs transition-colors',
  active ? 'border-brand-fill font-semibold text-foreground' : 'border-transparent text-muted-foreground',
];
</script>
