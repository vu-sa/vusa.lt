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
                  :cache-for="SHELL_PREFETCH_CACHE_FOR"
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

        <div class="border-b border-border py-2">
          <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('shell.chrome.account') }}
          </p>
          <div v-if="user" class="px-4 py-1.5">
            <span class="block truncate text-sm font-semibold text-foreground">{{ user.name }}</span>
            <span class="block truncate text-xs text-muted-foreground">{{ user.email }}</span>
          </div>
          <ul>
            <li>
              <Link
                :href="route('profile')"
                prefetch
                :cache-for="SHELL_PREFETCH_CACHE_FOR"
                class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground"
                @click="close"
              >
                <UserRound class="size-5 text-muted-foreground" />
                {{ $t('shell.chrome.account') }}
              </Link>
            </li>
            <li>
              <Link
                :href="route('profile.roles')"
                prefetch
                :cache-for="SHELL_PREFETCH_CACHE_FOR"
                class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground"
                @click="close"
              >
                <ShieldCheck class="size-5 text-muted-foreground" />
                {{ $t('shell.account.roles') }}
              </Link>
            </li>
            <li v-if="showAllSections">
              <Link
                :href="route('administration')"
                prefetch
                :cache-for="SHELL_PREFETCH_CACHE_FOR"
                class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground"
                @click="close"
              >
                <LayoutGrid class="size-5 text-muted-foreground" />
                {{ $t('shell.chrome.all_sections') }}
              </Link>
            </li>
          </ul>
        </div>

        <div class="border-b border-border py-2">
          <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('shell.account.appearance') }}
          </p>
          <ul>
            <li>
              <button type="button" class="u-touch flex w-full items-center justify-between px-4 py-3 text-left text-sm" @click="toggleDarkMode">
                <span class="flex items-center gap-3">
                  <Sun v-if="isDark" class="size-5 text-muted-foreground" />
                  <Moon v-else class="size-5 text-muted-foreground" />
                  {{ $t(isDark ? 'shell.account.light' : 'shell.account.dark') }}
                </span>
              </button>
            </li>
            <li>
              <button type="button" class="u-touch flex w-full items-center justify-between px-4 py-3 text-left text-sm" @click="changeLocale">
                <span class="flex items-center gap-3">
                  <Languages class="size-5 text-muted-foreground" />
                  {{ $t('shell.account.language', { language: page.props.app?.locale === 'en' ? 'Lietuvių' : 'English' }) }}
                </span>
              </button>
            </li>
          </ul>
        </div>

        <div class="border-b border-border py-2">
          <p class="px-4 py-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('shell.account.help') }}
          </p>
          <ul>
            <li>
              <a :href="docsBase" target="_blank" rel="noopener noreferrer" class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground" @click="close">
                <BookOpen class="size-5 text-muted-foreground" />
                {{ $t('shell.account.docs') }}
              </a>
            </li>
            <li>
              <Link :href="reportProblemHref" prefetch class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground" @click="close">
                <Bug class="size-5 text-muted-foreground" />
                {{ $t('shell.account.report_problem') }}
              </Link>
            </li>
            <li>
              <Link :href="route('mySupportRequests.index')" prefetch class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground" @click="close">
                <MessagesSquare class="size-5 text-muted-foreground" />
                {{ $t('shell.account.my_requests') }}
              </Link>
            </li>
            <li>
              <a
                :href="`${docsBase}/changelog/`"
                target="_blank"
                rel="noopener noreferrer"
                class="u-touch flex items-center gap-3 px-4 py-3 text-sm text-foreground"
                @click="close"
              >
                <Sparkles class="size-5 text-muted-foreground" />
                {{ $t('shell.account.whats_new') }}
              </a>
            </li>
          </ul>
        </div>

        <div class="py-2">
          <ul>
            <li>
              <button type="button" class="u-touch flex w-full items-center gap-3 px-4 py-3 text-left text-sm" @click="startFm.open(); close();">
                <Radio class="size-5 text-muted-foreground" />
                {{ $t('shell.account.start_fm') }}
              </button>
            </li>
            <li>
              <button type="button" class="u-touch flex w-full items-center justify-between px-4 py-3 text-left text-sm" @click="toggleNewShell">
                <span class="flex items-center gap-3">
                  <span class="size-5" />
                  {{ $t('shell.chrome.new_design') }}
                </span>
                <Check v-if="newShellEnabled" class="size-4 text-brand" />
              </button>
            </li>
            <li>
              <button type="button" class="u-touch flex w-full items-center gap-3 px-4 py-3 text-left text-sm" @click="logout">
                <LogOut class="size-5 text-muted-foreground" />
                {{ $t('auth.logout') }}
              </button>
            </li>
            <li>
              <button type="button" class="u-touch flex w-full items-center gap-3 px-4 py-3 text-left text-sm" @click="logoutMicrosoft">
                <ISimpleIconsMicrosoft class="size-5 text-muted-foreground" />
                {{ $t('auth.logout_microsoft') }}
              </button>
            </li>
          </ul>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { onKeyStroke, useDark, useScrollLock } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import {
  BookOpen,
  Bug,
  Check,
  Languages,
  LayoutGrid,
  LogOut,
  MessagesSquare,
  Moon,
  Plus,
  Radio,
  ShieldCheck,
  Sparkles,
  Sun,
  UserRound,
  X,
} from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

import TaskCountBadge from './TaskCountBadge.vue';

import ISimpleIconsMicrosoft from '~icons/simple-icons/microsoft';
import { Button } from '@/Components/ui/button';
import { workspaceIcon } from '@/Constants/adminWorkspaces';
import {
  sectionHref,
  SHELL_PREFETCH_CACHE_FOR,
  type AdminSection,
  type AdminWorkspace,
} from '@/Composables/useAdminNavigation';
import { useLogout } from '@/Composables/useLogout';
import { useNewShellToggle } from '@/Composables/useNewShellToggle';
import { useStartFm } from '@/Composables/useStartFm';
import { ariaCurrent } from '@/Utils/ariaCurrent';

const props = defineProps<{
  workspaces: AdminWorkspace[];
  activeWorkspace?: AdminWorkspace;
  activeSection?: AdminSection;
  showAllSections?: boolean;
}>();

const open = defineModel<boolean>('open', { default: false });

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);

const closeRef = ref<{ $el: HTMLElement } | null>(null);
const openKey = ref<string | undefined>(props.activeWorkspace?.key);
const scrollLock = useScrollLock(typeof document === 'undefined' ? null : document.body);

const { logout, logoutMicrosoft } = useLogout();
const { enabled: newShellEnabled, toggle: toggleNewShell } = useNewShellToggle();
const startFm = useStartFm();
const isDark = useDark();

const docsBase = computed(() => page.props.app?.locale === 'en' ? '/docs/en' : '/docs');
const reportProblemHref = computed(() => {
  const context = typeof window === 'undefined'
    ? {}
    : {
        url: window.location.href,
        viewport: `${window.innerWidth}×${window.innerHeight}`,
        browser: navigator.userAgent,
      };

  return `${route('mySupportRequests.create')}?context=${encodeURIComponent(JSON.stringify(context))}`;
});

function toggleDarkMode(): void {
  isDark.value = !isDark.value;
}

function changeLocale(): void {
  const locale = page.props.app?.locale === 'en' ? 'lt' : 'en';
  router.reload({ data: { lang: locale } });
}

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
