<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="u-touch" :aria-label="$t('shell.chrome.account')">
        <Avatar class="size-8 rounded-none">
          <AvatarImage v-if="user?.profile_photo_path" :src="user.profile_photo_path" :alt="user.name" />
          <AvatarFallback class="rounded-none text-xs font-semibold">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-72 shadow-none">
      <DropdownMenuLabel class="font-normal">
        <span class="block truncate text-sm font-semibold">{{ user?.name }}</span>
        <span class="block truncate text-xs text-muted-foreground">{{ user?.email }}</span>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem as-child>
        <Link :href="route('profile')" prefetch>
          <UserRound class="size-4" />
          {{ $t('shell.chrome.account') }}
        </Link>
      </DropdownMenuItem>
      <DropdownMenuItem as-child>
        <Link :href="route('profile.roles')" prefetch>
          <ShieldCheck class="size-4" />
          {{ $t('shell.account.roles') }}
        </Link>
      </DropdownMenuItem>
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <Palette class="size-4" />
          {{ $t('shell.account.appearance') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent class="w-64 p-1">
          <DropdownMenuItem @select="toggleDarkMode">
            <Sun v-if="isDark" class="size-4" />
            <Moon v-else class="size-4" />
            {{ $t(isDark ? 'shell.account.light' : 'shell.account.dark') }}
          </DropdownMenuItem>
          <DropdownMenuItem @select="changeLocale">
            <Languages class="size-4" />
            {{ $t('shell.account.language', { language: page.props.app?.locale === 'en' ? 'Lietuvių' : 'English' }) }}
          </DropdownMenuItem>
          <div class="flex items-center justify-between px-2 py-1 text-sm text-foreground">
            <span class="text-xs font-medium text-muted-foreground">
              {{ $t('accessibility.menu_title') }}
            </span>
            <AccessibilityMenu class="size-7" />
          </div>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuCheckboxItem :model-value="newShellEnabled" @select.prevent="toggleNewShell">
        {{ $t('shell.chrome.new_design') }}
      </DropdownMenuCheckboxItem>
      <DropdownMenuSeparator />
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <CircleHelp class="size-4" />
          {{ $t('shell.account.help') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent class="w-64">
          <DropdownMenuItem as-child>
            <a :href="docsBase" target="_blank" rel="noopener noreferrer">
              <BookOpen class="size-4" />
              {{ $t('shell.account.docs') }}
            </a>
          </DropdownMenuItem>
          <DropdownMenuItem :disabled="!hasTour" @select="startTour">
            <Map class="size-4" />
            {{ $t('shell.account.tour') }}
          </DropdownMenuItem>
          <DropdownMenuItem as-child>
            <Link :href="reportProblemHref" prefetch>
              <Bug class="size-4" />
              {{ $t('shell.account.report_problem') }}
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem as-child>
            <Link :href="route('mySupportRequests.index')" prefetch>
              <MessagesSquare class="size-4" />
              {{ $t('shell.account.my_requests') }}
            </Link>
          </DropdownMenuItem>
          <DropdownMenuItem as-child>
            <a :href="`${docsBase}/changelog/`" target="_blank" rel="noopener noreferrer">
              <Sparkles class="size-4" />
              {{ $t('shell.account.whats_new') }}
            </a>
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuItem @select="startFm.open">
        <Radio class="size-4" />
        {{ $t('shell.account.start_fm') }}
      </DropdownMenuItem>
      <DropdownMenuSub>
        <DropdownMenuSubTrigger>
          <Info class="size-4" />
          {{ $t('shell.account.about') }}
        </DropdownMenuSubTrigger>
        <DropdownMenuSubContent class="w-56">
          <DropdownMenuLabel>{{ latestVersion ?? 'VU SA' }}</DropdownMenuLabel>
          <DropdownMenuItem as-child>
            <a href="https://github.com/vu-sa/vusa.lt" target="_blank" rel="noopener noreferrer">
              <Info class="size-4" />
              GitHub
            </a>
          </DropdownMenuItem>
          <DropdownMenuItem as-child>
            <a :href="`${docsBase}/changelog/`" target="_blank" rel="noopener noreferrer">
              <Sparkles class="size-4" />
              {{ $t('shell.account.whats_new') }}
            </a>
          </DropdownMenuItem>
        </DropdownMenuSubContent>
      </DropdownMenuSub>
      <DropdownMenuItem @select="logout">
        <LogOut class="size-4" />
        {{ $t('auth.logout') }}
      </DropdownMenuItem>
      <DropdownMenuItem @select="logoutMicrosoft">
        <ISimpleIconsMicrosoft class="size-4" />
        {{ $t('auth.logout_microsoft') }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { useDark } from '@vueuse/core';
import { BookOpen, Bug, CircleHelp, Info, Languages, LogOut, Map, MessagesSquare, Moon, Palette, Radio, ShieldCheck, Sparkles, Sun, UserRound } from 'lucide-vue-next';
import { computed } from 'vue';

import ISimpleIconsMicrosoft from '~icons/simple-icons/microsoft';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
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
import { useNewShellToggle } from '@/Composables/useNewShellToggle';
import { useStartFm } from '@/Composables/useStartFm';
import { useTour } from '@/Composables/useTourProvider';
import { useDocsUpdateIndicator } from '@/Composables/useDocsUpdateIndicator';
import AccessibilityMenu from '@/Components/Public/Base/AccessibilityMenu.vue';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const initials = computed(() => (user.value?.name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase());

const { logout, logoutMicrosoft } = useLogout();
const { enabled: newShellEnabled, toggle: toggleNewShell } = useNewShellToggle();
const { hasTour, startTour } = useTour();
const startFm = useStartFm();
const isDark = useDark();
const { latestVersion } = useDocsUpdateIndicator();
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
</script>
