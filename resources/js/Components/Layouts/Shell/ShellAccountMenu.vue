<template>
  <DropdownMenu v-model:open="isOpen">
    <DropdownMenuTrigger as-child>
      <Button
        variant="ghost"
        voice="plain"
        data-slot="account-menu-trigger"
        class="u-touch h-8 gap-2 border-l border-border pl-3 pr-2.5 transition-colors hover:bg-secondary focus-visible:bg-secondary"
        :class="{ 'bg-secondary': isOpen }"
        :aria-label="$t('shell.chrome.account')"
      >
        <Avatar class="size-6 rounded-none">
          <AvatarImage v-if="user?.profile_photo_path" :src="user.profile_photo_path" :alt="user.name" />
          <AvatarFallback class="rounded-none bg-brand-fill text-xs font-bold text-brand-foreground dark:bg-brand-fill dark:text-brand-foreground">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
        <span class="hidden max-w-28 truncate text-xs font-bold text-foreground lg:inline">{{ user?.name }}</span>
        <ChevronDown
          class="hidden size-3.5 text-muted-foreground transition-transform duration-200 lg:inline"
          :class="{ 'rotate-180': isOpen }"
          aria-hidden="true"
        />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-72 rounded-none border border-border bg-popover p-0 shadow-none">
      <!-- User Identity Header -->
      <div class="border-b border-border px-4 py-3">
        <p class="truncate text-sm font-bold text-foreground">
          {{ user?.name }}
        </p>
        <p class="truncate text-xs text-muted-foreground">
          {{ user?.email }}
        </p>
      </div>

      <!-- Account navigation -->
      <div class="p-1">
        <DropdownMenuItem as-child class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
          <Link :href="route('profile')" prefetch>
            <UserRound class="size-4 shrink-0" />
            <span>{{ $t('shell.chrome.account') }}</span>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem as-child class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
          <Link :href="route('profile.roles')" prefetch>
            <ShieldCheck class="size-4 shrink-0" />
            <span>{{ $t('shell.account.roles') }}</span>
          </Link>
        </DropdownMenuItem>
        <DropdownMenuItem as-child class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
          <Link :href="route('profile.notifications')" prefetch>
            <Bell class="size-4 shrink-0" />
            <span>{{ $t('shell.account.notifications') }}</span>
          </Link>
        </DropdownMenuItem>
      </div>

      <DropdownMenuSeparator class="my-0" />

      <!-- Tools & Preferences -->
      <div class="p-1">
        <DropdownMenuSub>
          <DropdownMenuSubTrigger data-slot="appearance-settings-trigger" class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
            <Palette class="size-4 shrink-0" />
            <span>{{ $t('shell.account.appearance') }}</span>
          </DropdownMenuSubTrigger>
          <DropdownMenuSubContent class="w-64 rounded-none border border-border bg-popover p-1 shadow-none">
            <DropdownMenuItem class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium" @select="toggleDarkMode">
              <Sun v-if="isDark" class="size-4 shrink-0" />
              <Moon v-else class="size-4 shrink-0" />
              <span>{{ $t(isDark ? 'shell.account.light' : 'shell.account.dark') }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium" @select="changeLocale">
              <Languages class="size-4 shrink-0" />
              <span>{{ $t('shell.account.language', { language: page.props.app?.locale === 'en' ? 'Lietuvių' : 'English' }) }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem data-slot="accessibility-settings-open" class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium" @select="openAccessibilitySettings">
              <IFluentAccessibility24Regular class="size-4 shrink-0" />
              <span>{{ $t('accessibility.menu_title') }}</span>
            </DropdownMenuItem>
          </DropdownMenuSubContent>
        </DropdownMenuSub>

        <DropdownMenuSub>
          <DropdownMenuSubTrigger class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
            <CircleHelp class="size-4 shrink-0" />
            <span>{{ $t('shell.account.help') }}</span>
          </DropdownMenuSubTrigger>
          <DropdownMenuSubContent class="w-64 rounded-none border border-border bg-popover p-1 shadow-none">
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <a :href="docsBase" target="_blank" rel="noopener noreferrer">
                <BookOpen class="size-4 shrink-0" />
                <span class="flex-1">{{ $t('shell.account.docs') }}</span>
                <ArrowUpRight class="size-3.5 shrink-0 text-muted-foreground/60" />
              </a>
            </DropdownMenuItem>
            <DropdownMenuItem :disabled="!hasTour" class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium" @select="startTour">
              <Map class="size-4 shrink-0" />
              <span>{{ $t('shell.account.tour') }}</span>
            </DropdownMenuItem>
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <Link :href="reportProblemHref" prefetch>
                <Bug class="size-4 shrink-0" />
                <span>{{ $t('shell.account.report_problem') }}</span>
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <Link :href="route('mySupportRequests.index')" prefetch>
                <MessagesSquare class="size-4 shrink-0" />
                <span>{{ $t('shell.account.my_requests') }}</span>
              </Link>
            </DropdownMenuItem>
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <a :href="changelogHref" target="_blank" rel="noopener noreferrer">
                <Sparkles class="size-4 shrink-0" />
                <span class="flex-1">{{ $t('shell.account.whats_new') }}</span>
                <ArrowUpRight class="size-3.5 shrink-0 text-muted-foreground/60" />
              </a>
            </DropdownMenuItem>
          </DropdownMenuSubContent>
        </DropdownMenuSub>

        <DropdownMenuItem class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium" @select="startFm.open">
          <Radio class="size-4 shrink-0" />
          <span>{{ $t('shell.account.start_fm') }}</span>
        </DropdownMenuItem>

        <DropdownMenuSub>
          <DropdownMenuSubTrigger class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium">
            <Info class="size-4 shrink-0" />
            <span>{{ $t('shell.account.about') }}</span>
          </DropdownMenuSubTrigger>
          <DropdownMenuSubContent class="w-56 rounded-none border border-border bg-popover p-1 shadow-none">
            <DropdownMenuLabel class="px-3.5 py-1.5 text-xs font-bold uppercase tracking-wider text-muted-foreground">
              {{ latestVersion ?? 'VU SA' }}
            </DropdownMenuLabel>
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <a href="https://github.com/vu-sa/vusa.lt" target="_blank" rel="noopener noreferrer">
                <ISimpleIconsGithub class="size-4 shrink-0" />
                <span class="flex-1">GitHub</span>
                <ArrowUpRight class="size-3.5 shrink-0 text-muted-foreground/60" />
              </a>
            </DropdownMenuItem>
            <DropdownMenuItem as-child class="gap-3 px-3.5 py-2 rounded-none cursor-pointer text-sm font-medium">
              <a :href="changelogHref" target="_blank" rel="noopener noreferrer">
                <Sparkles class="size-4 shrink-0" />
                <span class="flex-1">{{ $t('shell.account.whats_new') }}</span>
                <ArrowUpRight class="size-3.5 shrink-0 text-muted-foreground/60" />
              </a>
            </DropdownMenuItem>
          </DropdownMenuSubContent>
        </DropdownMenuSub>
      </div>

      <DropdownMenuSeparator class="my-0" />

      <!-- Session actions -->
      <div class="p-1">
        <DropdownMenuItem
          variant="destructive"
          class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium"
          @select="logout"
        >
          <LogOut class="size-4 shrink-0" />
          <span>{{ $t('auth.logout') }}</span>
        </DropdownMenuItem>
        <DropdownMenuItem
          class="gap-3 px-3.5 py-2.5 rounded-none cursor-pointer text-sm font-medium"
          @select="logoutMicrosoft"
        >
          <ISimpleIconsMicrosoft class="size-4 shrink-0" />
          <span>{{ $t('auth.logout_microsoft') }}</span>
        </DropdownMenuItem>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>

  <Dialog v-model:open="accessibilityOpen">
    <DialogContent class="w-80 gap-0 p-0" data-slot="admin-accessibility-dialog">
      <DialogTitle class="sr-only">{{ $t('accessibility.menu_title') }}</DialogTitle>
      <AccessibilitySettings />
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { loadLanguageAsync, trans as $t } from 'laravel-vue-i18n';
import { useDark } from '@vueuse/core';
import {
  ArrowUpRight,
  Bell,
  BookOpen,
  Bug,
  ChevronDown,
  CircleHelp,
  Info,
  Languages,
  LogOut,
  Map,
  MessagesSquare,
  Moon,
  Palette,
  Radio,
  ShieldCheck,
  Sparkles,
  Sun,
  UserRound,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

import IFluentAccessibility24Regular from '~icons/fluent/accessibility-24-regular';
import ISimpleIconsGithub from '~icons/simple-icons/github';
import ISimpleIconsMicrosoft from '~icons/simple-icons/microsoft';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogTitle } from '@/Components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuSub,
  DropdownMenuSubContent,
  DropdownMenuSubTrigger,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useLogout } from '@/Composables/useLogout';
import { useStartFm } from '@/Composables/useStartFm';
import { useTour } from '@/Composables/useTourProvider';
import { useDocsUpdateIndicator } from '@/Composables/useDocsUpdateIndicator';
import AccessibilitySettings from '@/Components/Public/Base/AccessibilitySettings.vue';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const initials = computed(() => (user.value?.name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase());

const isOpen = ref(false);
const accessibilityOpen = ref(false);

function openAccessibilitySettings(): void {
  isOpen.value = false;
  accessibilityOpen.value = true;
}

const { logout, logoutMicrosoft } = useLogout();
const { hasTour, startTour } = useTour();
const startFm = useStartFm();
const isDark = useDark();
const { latestVersion, docsBase, changelogHref } = useDocsUpdateIndicator();
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
  router.reload({ data: { lang: locale }, onSuccess: () => loadLanguageAsync(locale) });
}
</script>
