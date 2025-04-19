<script setup lang="ts">
import NavMain from './NavMain.vue'
import NavSecondary from './NavSecondary.vue'
import AppLogo from './AppLogo.vue'

import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarGroupLabel,
  SidebarRail,
  type SidebarProps,
} from '@/Components/ui/sidebar'

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog'

import { Button } from '@/Components/ui/button'
import { Input } from '@/Components/ui/input'
import { Label } from '@/Components/ui/label'
import { Checkbox } from '@/Components/ui/checkbox'
import { Textarea } from '@/Components/ui/textarea'

import {
  Avatar,
  AvatarFallback,
  AvatarImage,
} from '@/Components/ui/avatar'

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu'

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
  Clock,
  type LucideIcon
} from 'lucide-vue-next'

import { Link, router, usePage, useForm } from '@inertiajs/vue3'
import { loadLanguageAsync, trans as $t } from 'laravel-vue-i18n'
import { capitalize } from '@/Utils/String'
import { computed, ref } from 'vue'
import { useDark } from '@vueuse/core'

const props = withDefaults(defineProps<SidebarProps>(), {
  variant: 'inset',
})

const isDark = useDark()

// Toggle dark mode function
const toggleDarkMode = () => {
  isDark.value = !isDark.value
}

// Change locale function
const changeLocale = () => {
  const toLocale = usePage().props.app.locale === "en" ? "lt" : "en"
  router.reload({ data: { lang: toLocale }, onSuccess: () => loadLanguageAsync(toLocale) })
}

// Current user data
const currentUser = computed(() => {
  return usePage().props.auth?.user ?? {
    name: '',
    email: '',
    profile_photo_path: '',
  }
})

// Primary navigation items
const navMainItems = computed(() => {
  const items = []

  // Representation (Atstovavimas)
  if (usePage().props.auth?.can.create.meeting) {
    items.push({
      title: $t("Atstovavimas"),
      url: route('dashboard.atstovavimas'),
      icon: GraduationCap,
      isActive: route().current('dashboard.atstovavimas*'),
    })
  }

  // Website (Svetainė)
  if (usePage().props.auth?.can.create.page) {
    items.push({
      title: $t("Svetainė"),
      url: route('dashboard.svetaine'),
      icon: Globe,
      isActive: route().current('dashboard.svetaine*'),
    })
  }

  // Reservations
  items.push({
    title: $t('Rezervacijos'),
    url: route('dashboard.reservations'),
    icon: Bookmark,
    isActive: route().current('dashboard.reservations*'),
  })

  // Settings/Admin (Administravimas)
  items.push({
    title: $t('Administravimas'),
    url: route('administration'),
    icon: Settings,
    isActive: route().current('administration*'),
  })

  return items
})

// Secondary navigation items (bottom)
const navSecondaryItems = computed(() => {
  return [
    {
      title: $t('Dokumentacija'),
      url: 'https://www.vusa.lt/docs',
      icon: BookOpen,
    },
    {
      title: $t('Palik atsiliepimą'),
      url: '#feedback',
      icon: MessageSquare,
    },
    // {
    //   title: usePage().props.app.locale === 'en' ? 'Help' : 'Pagalba',
    //   url: route('dashboard'),
    //   icon: LifeBuoy,
    // },
  ]
})

// Handle secondary nav clicks
const handleSecondaryNavClick = (url: string) => {
  if (url === '#feedback') {
    showFeedbackDialog.value = true
  } else if (url.startsWith('http')) {
    window.open(url, '_blank')
  } else {
    router.visit(url)
  }
}

// Feedback dialog state and form
const showFeedbackDialog = ref(false)
const feedbackLoading = ref(false)

const feedbackForm = useForm({
  feedback: null as string | null,
  anonymous: false,
  href: typeof window !== 'undefined' ? window.location.href : '',
  selectedText: null as string | null,
})

// Handle feedback submission
const handleSendFeedback = () => {
  feedbackLoading.value = true
  feedbackForm.post(route('feedback.send'), {
    onSuccess: () => {
      showFeedbackDialog.value = false
      feedbackLoading.value = false
      feedbackForm.reset()
    },
    onError: () => {
      feedbackLoading.value = false
    }
  })
}

// Handle logout
const handleLogout = () => {
  router.post(route('logout'), {}, {
    onSuccess: () => {
      window.location.href = route('home', {
        lang: usePage().props.app.locale,
        subdomain: usePage().props.tenant?.subdomain ?? 'www'
      })
    },
    onError: () => {
      console.error('Logout failed.')
    }
  });
}
</script>

<template>
  <Sidebar v-bind="props">
    <SidebarHeader>
      <SidebarMenu>
        <SidebarMenuItem>
          <Link :href="route('dashboard')">
          <SidebarMenuButton size="lg"
            class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
            <div
              class="flex aspect-square size-12 items-center justify-center rounded-lg text-sidebar-primary-foreground">
              <AppLogo />
            </div>
            <div class="grid flex-1 text-left text-sm leading-tight">
              <span class="truncate font-semibold">
                {{ $t("Mano VU SA") }}
              </span>
              <span class="truncate text-xs" />
            </div>
            <!-- <ChevronsUpDown class="ml-auto" /> -->
          </SidebarMenuButton>
          </Link>
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarHeader>
    <SidebarContent>
      <NavMain :items="navMainItems" />

      <!-- Secondary navigation -->
      <div class="group-data-[collapsible=icon]:hidden mt-auto">
        <NavSecondary :items="navSecondaryItems" @item-click="handleSecondaryNavClick" />
      </div>
    </SidebarContent>
    <SidebarFooter>
      <SidebarMenu>
        <!-- User account dropdown -->
        <SidebarMenuItem>
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <SidebarMenuButton size="lg"
                class="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground">
                <Avatar class="h-8 w-8 rounded-lg">
                  <AvatarImage v-if="currentUser.profile_photo_path" :src="currentUser.profile_photo_path"
                    :alt="currentUser.name" />
                  <AvatarFallback class="rounded-lg">
                    {{ currentUser.name ? currentUser.name.substring(0, 2).toUpperCase() : 'VU' }}
                  </AvatarFallback>
                </Avatar>
                <div class="grid flex-1 text-left text-sm leading-tight">
                  <span class="truncate font-medium">{{ currentUser.name }}</span>
                  <span class="truncate text-xs">{{ currentUser.email }}</span>
                </div>
                <ChevronsUpDown class="ml-auto size-4" />
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
                <DropdownMenuItem @click="router.visit(route('profile'))">
                  <UserIcon class="mr-2 h-4 w-4" />
                  <span>{{ $t('Nustatymai') }}</span>
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
        </SidebarMenuItem>
      </SidebarMenu>
    </SidebarFooter>
    <!-- <SidebarRail /> -->
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
          <Checkbox id="anonymous" v-model:checked="feedbackForm.anonymous" />
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
</template>
