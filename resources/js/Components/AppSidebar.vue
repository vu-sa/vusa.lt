<template>
  <Sidebar v-bind="props">
    <SidebarHeader class="relative overflow-hidden">
      <!-- Subtle gradient accent -->
      <div
        class="absolute inset-0 bg-gradient-to-br from-primary/5 via-transparent to-transparent dark:from-primary/3 pointer-events-none" />
      <SidebarMenu class="relative">
        <SidebarMenuItem>
          <Link :href="route('dashboard')" prefetch>
            <SidebarMenuButton size="lg"
              class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground hover:bg-sidebar-accent/50 transition-colors">
              <div
                class="flex aspect-square w-14 h-14 items-center justify-center rounded-xl bg-gradient-to-br from-primary/10 to-primary/5 dark:from-primary/8 dark:to-primary/3 text-sidebar-primary-foreground shadow-sm">
                <AppLogo width="48" height="16" />
              </div>
              <div class="grid flex-1 text-left text-sm leading-tight">
                <span class="truncate font-semibold text-base">
                  {{ $t("Mano VU SA") }}
                </span>
              </div>
            </SidebarMenuButton>
          </Link>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent
      :data-density="density"
      :class="['flex flex-col group/density', density === 'compact' ? 'gap-2' : 'gap-4']">
      <!-- Replaces the old "Greiti veiksmai" list. A SidebarGroup so its padding is
           the same above and below as every other section; deliberately outside
           `orderedSections`, because this is the front door for someone who does not
           know where to look and must not be reorderable or hideable. -->
      <SidebarGroup data-tour="action-window">
        <SidebarMenu>
          <SidebarMenuItem>
            <ActionWindowTrigger
              full-width
              float
              spotlight-position="right"
              label-class="group-data-[collapsible=icon]:hidden"
              class="group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0"
            />
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarGroup>

      <!-- Main navigation -->
      <NavMain :items="navMainItems" />

      <SidebarSeparator />

      <!-- Customizable sections (user-defined order, toggled via the customize dialog) -->
      <template v-for="key in orderedSections" :key>
        <PinnedPagesSection
          v-if="key === 'pinned' && isSectionVisible(key)" />
        <RecentlyVisitedSection
          v-else-if="key === 'recently_visited' && isSectionVisible(key)" />
        <FollowedInstitutionsHotbar
          v-else-if="key === 'followed_institutions' && isSectionVisible(key)" />
        <div
          v-else-if="key === 'spacer' && isSectionVisible(key)"
          class="flex-1 min-h-0" />
        <div
          v-else-if="key === 'start_fm' && isSectionVisible(key)"
          class="group-data-[collapsible=icon]:hidden">
          <SidebarStartFM />
        </div>
        <div
          v-else-if="key === 'secondary' && isSectionVisible(key)"
          class="group-data-[collapsible=icon]:hidden">
          <NavSecondary :items="navSecondaryItems" @item-click="handleSecondaryNavClick" />
        </div>
      </template>
    </SidebarContent>
    <SidebarFooter class="border-t border-sidebar-border/50">
      <!-- Back to website link -->
      <a :href="publicWebsiteUrl" target="_blank" rel="noopener noreferrer"
        class="flex items-center gap-2 px-3 py-2 text-xs text-muted-foreground hover:text-foreground transition-colors rounded-md hover:bg-sidebar-accent/50 group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2">
        <ExternalLink class="h-3.5 w-3.5 shrink-0" />
        <span class="group-data-[collapsible=icon]:hidden">{{ $t('Eiti į vusa.lt') }}</span>
      </a>
      <SidebarMenu>
        <!-- User account dropdown -->
        <SidebarMenuItem data-tour="user-menu">
          <SpotlightPopover
            :title="$t('Pritaikyk šoninę juostą sau')"
            :description="$t('Paskyros meniu gali pritaikyti šoninę juostą, prisegti puslapius ir peržiūrėti klaviatūros trumpinius.')"
            :is-dismissed="settingsSpotlight.isDismissed.value"
            position="top-right"
            style="display: block; width: 100%;"
            @dismiss="settingsSpotlight.dismiss"
          >
            <DropdownMenu @update:open="(o: boolean) => { if (o) { settingsSpotlight.dismiss(); } }">
              <DropdownMenuTrigger as-child>
                <SidebarMenuButton size="lg"
                  class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground hover:bg-sidebar-accent/60 transition-all duration-200">
                  <Avatar class="h-9 w-9 rounded-xl ring-2 ring-primary/10 shadow-sm">
                    <AvatarImage v-if="currentUser.profile_photo_path" :src="currentUser.profile_photo_path"
                      :alt="currentUser.name" />
                    <AvatarFallback
                      class="rounded-xl bg-gradient-to-br from-primary/20 to-primary/10 text-primary font-semibold">
                      {{ currentUser.name ? currentUser.name.substring(0, 2).toUpperCase() : 'VU' }}
                    </AvatarFallback>
                  </Avatar>
                  <div class="grid flex-1 text-left text-sm leading-tight">
                    <span class="truncate font-semibold">{{ currentUser.name }}</span>
                    <span class="truncate text-xs text-muted-foreground">{{ currentUser.email }}</span>
                  </div>
                  <ChevronsUpDown class="ml-auto size-4 text-muted-foreground" />
                </SidebarMenuButton>
              </DropdownMenuTrigger>
              <!-- User dropdown menu -->
              <DropdownMenuContent class="w-[--reka-dropdown-menu-trigger-width] min-w-56 rounded-lg" align="end"
                :side-offset="4">
                <DropdownMenuLabel class="p-0 font-normal">
                  <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                    <Avatar class="h-8 w-8 rounded-lg">
                      <AvatarImage v-if="currentUser.profile_photo_path" :src="currentUser.profile_photo_path"
                        :alt="currentUser.name" />
                      <AvatarFallback class="rounded-lg">
                        {{ currentUser.name ? currentUser.name.substring(0, 2).toUpperCase() : 'VU' }}
                      </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 text-left text-sm leading-tight">
                      <span class="truncate font-semibold">{{ currentUser.name }}</span>
                      <span class="truncate text-xs">{{ currentUser.email }}</span>
                    </div>
                  </div>
                </DropdownMenuLabel>
                <DropdownMenuSeparator />
                <DropdownMenuGroup>
                  <DropdownMenuItem as-child>
                    <Link :href="route('profile')" prefetch class="flex items-center w-full cursor-pointer">
                      <UserIcon class="mr-2 h-4 w-4" />
                      <span>{{ $t('Nustatymai') }}</span>
                    </Link>
                  </DropdownMenuItem>
                  <DropdownMenuItem class="cursor-pointer" @select="showCustomizeDialog = true">
                    <SlidersHorizontal class="mr-2 h-4 w-4" />
                    <span>{{ $t('Pritaikyti šoninę juostą') }}</span>
                  </DropdownMenuItem>
                  <DropdownMenuItem class="cursor-pointer" @select="showShortcutsDialog = true">
                    <Keyboard class="mr-2 h-4 w-4" />
                    <span>{{ $t('Klaviatūros trumpiniai') }}</span>
                  </DropdownMenuItem>
                </DropdownMenuGroup>
                <DropdownMenuSeparator />
                <div class="p-2">
                  <div class="flex items-center justify-between">
                    <!-- Dark mode toggle -->
                    <div class="flex items-center">
                      <Button variant="ghost" size="icon" class="h-8 w-8" @click="toggleDarkMode">
                        <Sun v-if="isDark" class="size-4" />
                        <Moon v-else class="size-4" />
                        <span class="sr-only">{{ $t('Tamsus režimas') }}</span>
                      </Button>
                      <span class="ml-2 text-sm">{{ $t(isDark ? 'Šviesus' : 'Tamsus') }}</span>
                    </div>
                    <!-- Language toggle -->
                    <Button variant="ghost" size="icon" class="h-8 w-8" @click="changeLocale">
                      <span class="flex items-center justify-center text-xs font-medium">
                        {{ usePage().props.app.locale === 'en' ? 'LT' : 'EN' }}
                      </span>
                    </Button>
                  </div>
                </div>
                <DropdownMenuSeparator />
                <DropdownMenuItem @click="handleLogout">
                  <LogOut class="mr-2 h-4 w-4" />
                  <span>{{ $t('auth.logout') }}</span>
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </SpotlightPopover>
        </SidebarMenuItem>
      </SidebarMenu>
      <!-- Version info -->
      <div class="flex items-center justify-center gap-1.5 px-3 py-1 text-[11px] text-muted-foreground/60 group-data-[collapsible=icon]:hidden">
        <a :href="`${docsBase}/changelog/`" target="_blank" rel="noopener noreferrer" class="hover:text-muted-foreground transition-colors" @click="markDocsUpdatesSeen">
          {{ latestVersion }}{{ lastUpdateDate ? ` · ${lastUpdateDate}` : '' }}
        </a>
        <span>·</span>
        <a href="https://github.com/vu-sa/vusa.lt" target="_blank" rel="noopener noreferrer" class="hover:text-muted-foreground transition-colors" aria-label="GitHub">
          <Github class="h-3 w-3" />
        </a>
      </div>
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>

  <!-- Sidebar customization -->
  <SidebarCustomizeDialog v-model:open="showCustomizeDialog" />

  <!-- Keyboard shortcuts -->
  <KeyboardShortcutsDialog v-model:open="showShortcutsDialog" />
</template>

<script setup lang="ts">
import {
  BookOpen,
  GraduationCap,
  Globe,
  Bookmark,
  Settings,
  MessageSquare,
  Moon,
  Sun,
  ChevronsUpDown,
  LogOut,
  UserIcon,
  ExternalLink,
  Github,
  Bell,
  Search,
  SlidersHorizontal,
  Keyboard,
  type LucideIcon,
} from 'lucide-vue-next';
import { Link, router, usePage } from '@inertiajs/vue3';
import { loadLanguageAsync, trans as $t } from 'laravel-vue-i18n';
import { computed, markRaw, ref, watch } from 'vue';
import { useDark, useEventListener } from '@vueuse/core';

import NavMain from './NavMain.vue';
import NavSecondary from './NavSecondary.vue';
import FollowedInstitutionsHotbar from './Sidebar/FollowedInstitutionsHotbar.vue';
import PinnedPagesSection from './Sidebar/PinnedPagesSection.vue';
import RecentlyVisitedSection from './Sidebar/RecentlyVisitedSection.vue';
import SidebarCustomizeDialog from './Sidebar/SidebarCustomizeDialog.vue';
import SidebarStartFM from './SidebarStartFM.vue';
import AppLogo from './AppLogo.vue';
import KeyboardShortcutsDialog from './KeyboardShortcutsDialog.vue';
import { Button } from './ui/button';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { useDocsUpdateIndicator } from '@/Composables/useDocsUpdateIndicator';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import ActionWindowTrigger from '@/Components/ActionWindow/ActionWindowTrigger.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarGroup,
  SidebarGroupLabel,
  SidebarSeparator,
  SidebarRail,
  useSidebar,
  type SidebarProps,
} from '@/Components/ui/sidebar';
import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/Components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { capitalize } from '@/Utils/String';

const props = withDefaults(defineProps<SidebarProps>(), {
  variant: 'inset',
});

const { isMobile, setOpenMobile } = useSidebar();
watch(
  () => usePage().props.app.path,
  () => {
    if (isMobile.value) {
      setOpenMobile(false);
    }
  },
);

const isDark = useDark();
const { lastUpdateDate, latestVersion, markAsSeen: markDocsUpdatesSeen } = useDocsUpdateIndicator();
const { isSectionVisible, orderedSections, density } = useUIPreferences();

// Density is anchored here: `group/density` + `data-density` on SidebarContent.
// The shared sidebar primitives react via `group-data-[density=compact]/density:`
// variants (see ui/sidebar/*), so every element — including future ones —
// scales consistently in compact mode without per-selector tuning here.

const showCustomizeDialog = ref(false);
const showShortcutsDialog = ref(false);

// Draw attention to the account menu, which now hosts sidebar customization,
// pinned pages and keyboard shortcuts. Dismisses once the menu is opened.
const settingsSpotlight = useFeatureSpotlight('sidebar-settings-v1', { position: 'top-right' });

// The reservations page is now a console: review requests, approve, hand over and mark returned
// without opening each reservation. Dismisses as soon as the page is opened.
const reservationsSpotlight = useFeatureSpotlight('reservations-dashboard-v1', {
  title: $t('Rezervacijos dabar tvarkomos vienoje vietoje'),
  description: $t('Peržiūrėk laukiančias užklausas, tvirtink, išduok ir žymėk grąžintus daiktus tiesiai iš sąrašo — nebereikia atidaryti kiekvienos rezervacijos atskirai.'),
  position: 'right',
});

// Registration forms used to be buried behind cards on the forms index page. They now
// hang off Svetainė, so returning users need to be told where they went.
const registrationsSpotlight = useFeatureSpotlight('sidebar-registrations-v1', {
  title: $t('Registracijos persikėlė į šoninę juostą'),
  description: $t('Narių ir studentų atstovų registracijas dabar rasi po „Svetainė“ — nebereikia ieškoti formų sąraše.'),
  position: 'right',
});

// "?" opens the keyboard-shortcuts cheatsheet, unless the user is typing.
useEventListener('keydown', (event: KeyboardEvent) => {
  if (event.key !== '?' || event.metaKey || event.ctrlKey || event.altKey) {
    return;
  }
  const target = event.target as HTMLElement | null;
  if (target && (
    target.isContentEditable
    || ['INPUT', 'TEXTAREA', 'SELECT'].includes(target.tagName)
  )) {
    return;
  }
  event.preventDefault();
  showShortcutsDialog.value = true;
});

const docsBase = computed(() => usePage().props.app.locale === 'en' ? '/docs/en' : '/docs');

// Toggle dark mode function
const toggleDarkMode = () => {
  isDark.value = !isDark.value;
};

// Change locale function
const changeLocale = () => {
  const toLocale = usePage().props.app.locale === 'en' ? 'lt' : 'en';
  router.reload({ data: { lang: toLocale }, onSuccess: () => loadLanguageAsync(toLocale) });
};

// Current user data
const currentUser = computed(() => {
  return usePage().props.auth?.user ?? {
    name: '',
    email: '',
    profile_photo_path: '',
  };
});

// The member and student rep registration forms are the two forms admins actually work with.
// The backend only shares an id when this user is allowed to open that form, so no extra
// permission checks are needed here.
const registrationFormItems = computed(() => {
  const registrationForms = usePage().props.auth?.registrationForms;
  const children: { title: string; url: string }[] = [];

  if (registrationForms?.member) {
    children.push({
      title: $t('Narių registracija'),
      url: route('forms.show', registrationForms.member),
    });
  }

  if (registrationForms?.studentRep) {
    children.push({
      title: $t('Studentų atstovų registracija'),
      url: route('forms.show', registrationForms.studentRep),
    });
  }

  return children;
});

// Primary navigation items
const navMainItems = computed(() => {
  const items = [];

  // Representation (ViSAK - Virtualus Studentų Atstovų Koordinatorius)
  if (usePage().props.auth?.can.create.meeting) {
    items.push({
      title: 'ViSAK',
      url: route('dashboard.atstovavimas'),
      icon: markRaw(GraduationCap),
      isActive: route().current('dashboard.atstovavimas')
        || route().current('meetings.show')
        || route().current('institutions.show')
        || route().current('duties.show'),
      dataTour: 'nav-visak',
    });
  }

  // Unified search page (collection access is enforced by scoped search keys)
  items.push({
    title: $t('Paieška'),
    url: route('search.index'),
    icon: markRaw(Search),
    isActive: route().current('search.*'),
  });

  // Website (Svetainė) — with direct links to the registration forms this user may open
  const canManagePages = usePage().props.auth?.can.create.page;
  const registrationChildren = registrationFormItems.value;
  const websiteUrl = canManagePages ? route('dashboard.svetaine') : registrationChildren[0]?.url;

  if ((canManagePages || registrationChildren.length > 0) && websiteUrl) {
    items.push({
      title: $t('Svetainė'),
      url: websiteUrl,
      icon: markRaw(Globe),
      // Drives both the highlight and whether the dropdown starts open.
      isActive: route().current('dashboard.svetaine') || route().current('forms.*'),
      items: registrationChildren,
      ...(registrationFormItems.value.length > 0
        ? {
            spotlight: {
              title: registrationsSpotlight.title,
              description: registrationsSpotlight.description,
              isDismissed: registrationsSpotlight.isDismissed.value,
              dismiss: registrationsSpotlight.dismiss,
            },
          }
        : {}),
    });
  }

  // Reservations
  items.push({
    title: $t('Rezervacijos'),
    url: route('dashboard.reservations'),
    icon: markRaw(Bookmark),
    isActive: route().current('dashboard.reservations*')
      || route().current('reservations.create')
      || route().current('reservations.show'),
    spotlight: {
      // title/description are plain strings on the composable, not refs.
      title: reservationsSpotlight.title,
      description: reservationsSpotlight.description,
      isDismissed: reservationsSpotlight.isDismissed.value,
      dismiss: reservationsSpotlight.dismiss,
    },
  });

  // Settings/Admin (Administravimas) - only show if user can access administration
  if (usePage().props.auth?.can.accessAdministration) {
    items.push({
      title: $t('Administravimas'),
      url: route('administration'),
      icon: markRaw(Settings),
      isActive: route().current('administration*'),
      dataTour: 'nav-administravimas',
    });
  }

  return items;
});

// Secondary navigation items (bottom) - help section only
const navSecondaryItems = computed(() => {
  return [
    {
      title: $t('Dokumentacija'),
      url: docsBase.value,
      icon: markRaw(BookOpen),
      dataTour: 'nav-dokumentacija',
    },
    {
      title: $t('vusa.lt pagalba'),
      url: route('mySupportRequests.index'),
      icon: markRaw(MessageSquare),
      internal: true,
      isActive: route().current('mySupportRequests.*') || route().current('supportRequests.*'),
      dataTour: 'nav-support-requests',
    },
  ];
});

// Handle secondary nav clicks
const handleSecondaryNavClick = (url: string) => {
  if (url.startsWith('/docs')) {
    markDocsUpdatesSeen();
  }
};

// Notification count
const notificationCount = computed(() => {
  return usePage().props.auth?.user?.unread_notifications_count || 0;
});

// Public website URL
const publicWebsiteUrl = computed(() => {
  const page = usePage();
  return route('home', {
    lang: page.props.app.locale,
    subdomain: page.props.tenant?.subdomain ?? 'www',
  });
});

// Handle logout
const handleLogout = () => {
  router.post(route('logout'), {}, {
    onSuccess: () => {
      window.location.href = route('login');
    },
    onError: () => {
      console.error('Logout failed.');
    },
  });
};
</script>
