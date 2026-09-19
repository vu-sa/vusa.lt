<template>
  <!-- A full-viewport panel, not a sheet — the same gesture as the public mobile menu
       (`Public/Nav/Mobile/MobileNavigation.vue`): the menu is the page itself. -->
  <Teleport to="body">
    <div
      v-if="open"
      data-slot="mobile-menu-panel"
      class="fixed inset-0 z-[60] flex flex-col bg-background text-foreground md:hidden"
      role="dialog"
      aria-modal="true"
      :aria-label="$t('shell.chrome.menu')"
    >
      <div class="flex h-14 shrink-0 items-center justify-between border-b border-border px-4">
        <span class="flex items-baseline gap-1 text-sm font-bold uppercase tracking-wide">
          <span>Mano</span>
          <span class="text-brand">VU SA</span>
        </span>
        <Button ref="closeRef" variant="ghost" size="icon" class="u-touch" :aria-label="$t('shell.chrome.close_menu')" @click="close">
          <X class="size-5" />
        </Button>
      </div>

      <div class="min-h-0 flex-1 overflow-y-auto overscroll-contain">
        <ul>
          <li v-for="workspace in workspaces" :key="workspace.key" class="border-b border-border">
            <button
              type="button"
              class="u-touch flex w-full items-center gap-3 px-4 py-3 text-left"
              :aria-expanded="openKey === workspace.key"
              @click="openKey = openKey === workspace.key ? undefined : workspace.key"
            >
              <component :is="workspaceIcon(workspace.key)" class="size-5 shrink-0 text-muted-foreground" />
              <span class="min-w-0 flex-1">
                <span class="block text-sm font-bold uppercase tracking-wide">{{ $t(workspace.label) }}</span>
                <span class="block text-sm text-muted-foreground">{{ $t(workspace.description) }}</span>
              </span>
              <TaskCountBadge v-if="workspace.key === 'pradzia' && openKey !== workspace.key" />
              <Plus class="size-4 shrink-0 transition-transform" :class="{ 'rotate-45': openKey === workspace.key }" />
            </button>

            <ul v-if="openKey === workspace.key" class="pb-2">
              <li v-for="section in workspace.sections" :key="section.key">
                <Link
                  :href="sectionHref(section)"
                  prefetch
                  v-bind="ariaCurrent(section.key === activeSection?.key && workspace.key === activeWorkspace?.key)"
                  :class="[
                    'u-touch flex items-center gap-2 border-l-2 py-3 pl-12 pr-4 text-sm',
                    section.key === activeSection?.key && workspace.key === activeWorkspace?.key
                      ? 'border-brand-fill font-semibold text-foreground'
                      : 'border-transparent text-muted-foreground',
                  ]"
                  @click="close"
                >
                  {{ $t(section.label) }}
                  <TaskCountBadge v-if="workspace.key === 'pradzia' && section.key === 'uzduotys'" />
                </Link>
              </li>
            </ul>
          </li>
        </ul>

        <ul class="py-2">
          <li v-if="showAllSections">
            <Link :href="route('administration')" prefetch class="u-touch flex items-center px-4 py-3 text-sm text-foreground" @click="close">
              {{ $t('shell.chrome.all_sections') }}
            </Link>
          </li>
          <li>
            <Link :href="route('profile')" prefetch class="u-touch flex items-center px-4 py-3 text-sm text-foreground" @click="close">
              {{ $t('shell.chrome.account') }}
            </Link>
          </li>
          <li>
            <button type="button" class="u-touch flex w-full items-center justify-between px-4 py-3 text-left text-sm" @click="toggleNewShell">
              {{ $t('shell.chrome.new_design') }}
              <Check v-if="newShellEnabled" class="size-4 text-brand" />
            </button>
          </li>
          <li>
            <button type="button" class="u-touch flex w-full items-center px-4 py-3 text-left text-sm" @click="logout">
              {{ $t('auth.logout') }}
            </button>
          </li>
        </ul>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { onKeyStroke, useScrollLock } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { Check, Plus, X } from 'lucide-vue-next';
import { nextTick, ref, watch } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import { Button } from '@/Components/ui/button';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import { sectionHref, type AdminSection, type AdminWorkspace } from '@/Composables/useAdminNavigation';
import { useLogout } from '@/Composables/useLogout';
import { useNewShellToggle } from '@/Composables/useNewShellToggle';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  showAllSections?: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage();
const closeRef = ref<{ $el: HTMLElement } | null>(null);
const openKey = ref<string | undefined>(props.activeWorkspace?.key);
const scrollLock = useScrollLock(typeof document === 'undefined' ? null : document.body);

const { logout } = useLogout();
const { enabled: newShellEnabled, toggle: toggleNewShell } = useNewShellToggle();

const close = () => {
  open.value = false;
};

watch(open, (isOpen) => {
  scrollLock.value = isOpen;

  if (isOpen) {
    openKey.value = props.activeWorkspace?.key;
    nextTick(() => closeRef.value?.$el?.focus());
  }
});

watch(() => page.url, close);

onKeyStroke('Escape', () => {
  if (open.value) {
    close();
  }
});
</script>
