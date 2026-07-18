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
      <!-- Main navigation -->
      <NavMain :items="navMainItems" />

      <SidebarSeparator />

      <!-- Customizable sections (user-defined order, toggled via the customize dialog) -->
      <template v-for="key in orderedSections" :key>
        <NavQuickActions
          v-if="key === 'quick_actions' && isSectionVisible(key)"
          @new-meeting="handleNewMeeting" @new-news="handleNewNews"
          @new-reservation="handleNewReservation"
          @open-customize="showCustomizeDialog = true" />
        <PinnedPagesSection
          v-else-if="key === 'pinned' && isSectionVisible(key)" />
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
                  <DropdownMenuItem class="cursor-pointer" @select="(e: Event) => { e.preventDefault(); setAnalytics(!analyticsAllowed); }">
                    <Activity class="mr-2 h-4 w-4" />
                    <span>{{ $t('Analitikos slapukai') }}</span>
                    <Check v-if="analyticsAllowed" class="ml-auto h-4 w-4" />
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
        <a href="https://github.com/vu-sa/vusa.lt" target="_blank" rel="noopener noreferrer" class="hover:text-muted-foreground transition-colors">
          <Github class="h-3 w-3" />
        </a>
      </div>
    </SidebarFooter>
    <SidebarRail />
  </Sidebar>

  <!-- Feedback Dialog -->
  <Dialog v-model:open="showFeedbackDialog">
    <DialogContent class="sm:max-w-md">
      <DialogHeader>
        <DialogTitle>{{ $t('Palik grįžtamąjį ryšį') }}</DialogTitle>
        <DialogDescription>
          <div v-if="usePage().props.app.locale === 'lt'">
            <p class="mb-4 text-xs">
              <strong>mano.vusa.lt</strong> yra nuolat tobulinamas studentų projektas,
              į kurio plėtimą norime įtraukti visus!
            </p>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">
              Jei turi bendrų pastebėjimų ar pasiūlymų šiai platformai, parašyk žemiau
              esančiame laukelyje. Tekstas bus nusiųstas puslapio administratoriui.
            </p>
          </div>
          <div v-else>
            <p class="mb-4 text-xs">
              <strong>mano.vusa.lt</strong> is a constantly improving student project
              that we want to involve everyone in!
            </p>
            <p class="text-xs text-zinc-600 dark:text-zinc-400">
              If you have any general comments or suggestions for this platform, write
              them in the field below. The text will be sent to the site
              administrator.
            </p>
          </div>
        </DialogDescription>
      </DialogHeader>
      <div class="grid gap-4 py-4">
        <div class="grid gap-2">
          <Textarea v-model="feedbackForm.feedback" :placeholder="$t('Parašyk pastebėjimų, pasiūlymų') + '...'"
            rows="4" />
        </div>
        <div class="flex items-center space-x-2">
          <Checkbox id="anonymous" v-model="feedbackForm.anonymous" />
          <Label for="anonymous" class="text-sm">{{ $t("Siųsti anonimiškai") }}</Label>
        </div>
      </div>
      <DialogFooter>
        <Button type="submit" :disabled="!feedbackForm.feedback || feedbackLoading" class="gap-1"
          @click="handleSendFeedback">
          <Send v-if="!feedbackLoading" class="h-4 w-4" />
          <span>{{ $t("forms.submit") }}</span>
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>

  <!-- New Meeting Modal -->
  <NewMeetingDialog :show-modal="showMeetingModal" @close="showMeetingModal = false" />

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
  LifeBuoy,
  MessageSquare,
  Moon,
  Sun,
  ChevronsUpDown,
  LogOut,
  UserIcon,
  Send,
  ExternalLink,
  Github,
  Bell,
  Search,
  SlidersHorizontal,
  Keyboard,
  Activity,
  Check,
  NotebookPen,
  type LucideIcon,
} from 'lucide-vue-next';
import { Link, router, usePage, useForm } from '@inertiajs/vue3';
import { loadLanguageAsync, trans as $t } from 'laravel-vue-i18n';
import { computed, markRaw, ref, watch } from 'vue';
import { useDark, useEventListener } from '@vueuse/core';

import NavMain from './NavMain.vue';
import NavSecondary from './NavSecondary.vue';
import NavQuickActions from './NavQuickActions.vue';
import FollowedInstitutionsHotbar from './Sidebar/FollowedInstitutionsHotbar.vue';
import PinnedPagesSection from './Sidebar/PinnedPagesSection.vue';
import RecentlyVisitedSection from './Sidebar/RecentlyVisitedSection.vue';
import SidebarCustomizeDialog from './Sidebar/SidebarCustomizeDialog.vue';
import SidebarStartFM from './SidebarStartFM.vue';
import AppLogo from './AppLogo.vue';
import KeyboardShortcutsDialog from './KeyboardShortcutsDialog.vue';

import SpotlightPopover from '@/Components/Onboarding/SpotlightPopover.vue';
import { useFeatureSpotlight } from '@/Composables/useFeatureSpotlight';
import { useCookieConsent } from '@/Composables/useCookieConsent';
import { useDocsUpdateIndicator } from '@/Composables/useDocsUpdateIndicator';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import NewMeetingDialog from '@/Components/Dialogs/NewMeetingDialog.vue';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarGroupLabel,
  SidebarSeparator,
  SidebarRail,
  useSidebar,
  type SidebarProps,
} from '@/Components/ui/sidebar';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Checkbox } from '@/Components/ui/checkbox';
import { Textarea } from '@/Components/ui/textarea';
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
  () => { if (isMobile.value) { setOpenMobile(false); } },
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

// Analytics cookie consent (opt-in/out for staff using the internal tool)
const { analyticsAllowed, setAnalytics } = useCookieConsent();

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

// Workspaces: shared meeting-preparation spaces with collaborative documents
// and discussions. Dismisses as soon as the page is opened.
const workspacesSpotlight = useFeatureSpotlight('workspaces-v1', {
  title: $t('Naujiena: darbo erdvės'),
  description: $t('Ruoškitės posėdžiams kartu — bendri dokumentai realiu laiku, diskusijos ir susieti posėdžiai vienoje vietoje.'),
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

  // Workspaces (shared meeting-preparation spaces)
  items.push({
    title: $t('workspaces.title'),
    url: route('workspaces.index'),
    icon: markRaw(NotebookPen),
    isActive: route().current('workspaces.*'),
    spotlight: {
      title: workspacesSpotlight.title,
      description: workspacesSpotlight.description,
      isDismissed: workspacesSpotlight.isDismissed.value,
      dismiss: workspacesSpotlight.dismiss,
    },
  });

  // Website (Svetainė)
  if (usePage().props.auth?.can.create.page) {
    items.push({
      title: $t('Svetainė'),
      url: route('dashboard.svetaine'),
      icon: markRaw(Globe),
      isActive: false,
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
      title: $t('Palik atsiliepimą'),
      url: '#feedback',
      icon: markRaw(MessageSquare),
      dataTour: 'nav-feedback',
    },
  ];
});

// Handle secondary nav clicks
const handleSecondaryNavClick = (url: string) => {
  if (url === '#feedback') {
    showFeedbackDialog.value = true;
  }
  else if (url.startsWith('http')) {
    window.open(url, '_blank');
  }
  else {
    if (url.startsWith('/docs')) {
      markDocsUpdatesSeen();
    }
    window.open(url, '_blank');
  }
};

// Meeting modal state
const showMeetingModal = ref(false);

// Handle quick actions
const handleNewMeeting = () => {
  showMeetingModal.value = true;
};

const handleNewNews = () => {
  router.visit(route('news.create'));
};

const handleNewReservation = () => {
  router.visit(route('reservations.create'));
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

// Feedback dialog state and form
const showFeedbackDialog = ref(false);
const feedbackLoading = ref(false);

const feedbackForm = useForm({
  feedback: null as string | null,
  anonymous: false,
  href: typeof window !== 'undefined' ? window.location.href : '',
  selectedText: null as string | null,
});

// Handle feedback submission
const handleSendFeedback = () => {
  feedbackLoading.value = true;
  feedbackForm.post(route('feedback.send'), {
    onSuccess: () => {
      showFeedbackDialog.value = false;
      feedbackLoading.value = false;
      feedbackForm.reset();
    },
    onError: () => {
      feedbackLoading.value = false;
    },
  });
};

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
