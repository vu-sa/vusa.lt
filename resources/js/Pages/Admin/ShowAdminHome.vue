<template>
  <PageContent>
    <Head :title="$t('Mano VU SA')" />

    <div class="space-y-8">
      <!-- Hero section with greeting -->
      <section class="relative overflow-hidden rounded-lg bg-gradient-to-br from-primary/10 to-primary/5 p-6 shadow-sm dark:from-primary/20 dark:to-background lg:p-8">
        <div class="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
          <div>
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl md:text-5xl text-primary dark:text-primary-foreground/90">
              {{ $t('Labas') }}, {{ userNameAddress }}! <span class="inline-block animate-wave origin-bottom-right">👋</span>
            </h1>
            <p class="mt-2 max-w-xl text-muted-foreground">
              {{ greeting }}
            </p>
          </div>
          <div v-if="hasNotifications" class="rounded-lg bg-muted/50 p-3 dark:bg-muted/30">
            <div class="flex items-center gap-2 font-medium text-foreground">
              <BellIcon class="h-5 w-5 text-primary" />
              <span>{{ $t('Turi') }} {{ unreadNotificationsCount }} {{ $t('neperskaitytų pranešimų') }}</span>
            </div>
            <Button variant="link" class="mt-1 p-0" @click="navigateToNotifications">
              {{ $t('Peržiūrėti pranešimus') }} →
            </Button>
          </div>
        </div>
      </section>

      <!-- Quick actions section -->
      <section>
        <div class="mb-6 flex items-center justify-between">
          <h2 class="text-xl font-semibold tracking-tight">{{ $t('Greiti veiksmai') }}</h2>
          <Link :href="route('administration')">
            <Button variant="outline" size="sm" class="gap-1">
              <LayoutGridIcon class="h-4 w-4" /> 
              {{ $t('Visi įrankiai') }}
            </Button>
          </Link>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Card v-for="action in quickActions" :key="action.title" 
                class="transition-all duration-300 hover:shadow-md hover:ring-1 hover:ring-primary/10 dark:hover:shadow-primary/5">
            <Link v-if="action.href" :href="action.href">
              <CardHeader class="pb-2">
                <div class="rounded-full bg-primary/10 p-2 w-fit">
                  <component :is="action.icon" class="h-5 w-5 text-primary" />
                </div>
              </CardHeader>
              <CardContent>
                <CardTitle class="text-lg">{{ action.title }}</CardTitle>
                <CardDescription class="text-sm">{{ action.description }}</CardDescription>
              </CardContent>
            </Link>
            <div v-else @click="action.onClick">
              <CardHeader class="pb-2">
                <div class="rounded-full bg-primary/10 p-2 w-fit">
                  <component :is="action.icon" class="h-5 w-5 text-primary" />
                </div>
              </CardHeader>
              <CardContent>
                <CardTitle class="text-lg">{{ action.title }}</CardTitle>
                <CardDescription class="text-sm">{{ action.description }}</CardDescription>
              </CardContent>
            </div>
          </Card>
        </div>
      </section>

      <!-- Recent activity and stats -->
      <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
        <!-- Recent activity - temporarily disabled -->
        <Card class="lg:col-span-4 opacity-75 relative">
          <div class="absolute inset-0 bg-background/50 backdrop-blur-[1px] flex items-center justify-center z-10">
            <div class="bg-background/90 px-4 py-2 rounded-md shadow-sm border border-border/40">
              <p class="text-sm font-medium text-muted-foreground">{{ $t('Veiksmų istorija laikinai nepasiekiama') }}</p>
            </div>
          </div>
          <CardHeader>
            <div class="flex items-center justify-between">
              <div>
                <CardTitle class="flex items-center gap-2">
                  <ActivityIcon class="h-5 w-5 text-primary" /> 
                  {{ showTenantActivities ? $t('Padalinio veiksmai') : $t('Jūsų naujausi veiksmai') }}
                </CardTitle>
                <CardDescription>
                  {{ showTenantActivities ? $t('Veiksmų istorija jūsų padalinyje') : $t('Jūsų veiksmų sistemoje istorija') }}
                </CardDescription>
              </div>
              
              <!-- Activity view type toggle - disabled -->
              <div v-if="canViewTenantActivities" class="flex items-center gap-2">
                <Button 
                  variant="outline" 
                  size="sm" 
                  disabled
                  class="text-xs"
                >
                  {{ showTenantActivities ? $t('Rodyti mano veiksmus') : $t('Rodyti padalinio veiksmus') }}
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <ActivityTimeline 
              :activities="[]" 
              :loading="false"
            />
          </CardContent>
        </Card>

        <!-- Resources & stats -->
        <Card class="lg:col-span-3">
          <CardHeader>
            <CardTitle class="flex items-center gap-2">
              <LayoutDashboardIcon class="h-5 w-5 text-primary" /> 
              {{ $t('Statistika') }}
            </CardTitle>
          </CardHeader>
          <CardContent class="space-y-8">
            <!-- Tasks stats -->
            <div>
              <div class="mb-2 flex items-center justify-between">
                <h4 class="font-medium">{{ $t('Užduotys') }}</h4>
                <Link :href="route('userTasks')">
                  <Button variant="ghost" size="sm">{{ $t('Visos') }} →</Button>
                </Link>
              </div>
              <div class="flex items-center gap-6">
                <div class="flex flex-col items-center gap-1">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                    <CheckCircle2Icon class="h-5 w-5 text-green-500 dark:text-green-400" />
                  </div>
                  <p class="text-xs text-muted-foreground">{{ $t('Atliktos') }}</p>
                  <p class="text-2xl font-bold">{{ taskStats.completed }}</p>
                </div>
                <div class="flex flex-col items-center gap-1">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900">
                    <ClockIcon class="h-5 w-5 text-amber-500 dark:text-amber-400" />
                  </div>
                  <p class="text-xs text-muted-foreground">{{ $t('Vykdomos') }}</p>
                  <p class="text-2xl font-bold">{{ taskStats.pending }}</p>
                </div>
                <div class="flex flex-col items-center gap-1">
                  <div class="flex h-12 w-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                    <AlertTriangleIcon class="h-5 w-5 text-red-500 dark:text-red-400" />
                  </div>
                  <p class="text-xs text-muted-foreground">{{ $t('Vėluoja') }}</p>
                  <p class="text-2xl font-bold">{{ taskStats.overdue }}</p>
                </div>
              </div>
            </div>

            <!-- Quick links -->
            <div>
              <h4 class="mb-2 font-medium">{{ $t('Trumposios nuorodos') }}</h4>
              <div class="space-y-2">
                <Link v-for="link in quickLinks" :key="link.title" :href="link.href">
                  <div class="group flex items-center justify-between rounded-md p-2 hover:bg-muted">
                    <div class="flex items-center gap-2">
                      <component :is="link.icon" class="h-4 w-4 text-primary" />
                      <span>{{ link.title }}</span>
                    </div>
                    <ChevronRightIcon class="h-4 w-4 opacity-0 transition-opacity group-hover:opacity-100" />
                  </div>
                </Link>
              </div>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Resources and tools -->
      <section>
        <div class="mb-6">
          <h2 class="text-xl font-semibold tracking-tight">{{ $t('Ištekliai ir įrankiai') }}</h2>
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <Link :href="route('administration')">
            <Card class="transition-all duration-200 hover:bg-muted/50 hover:shadow-md">
              <CardContent class="flex gap-6 p-6 items-center">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <SettingsIcon class="h-6 w-6 text-primary" />
                </div>
                <div class="space-y-1">
                  <CardTitle class="mb-1 text-xl">{{ $t('Administravimas') }}</CardTitle>
                  <CardDescription>
                    {{ $t('Visos informacijos administravimo įrankiai ir lentelės') }}
                  </CardDescription>
                </div>
              </CardContent>
            </Card>
          </Link>
          <a href="https://www.vusa.lt/docs">
            <Card class="transition-all duration-200 hover:bg-muted/50 hover:shadow-md">
              <CardContent class="flex gap-6 p-6 items-center">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <BookOpenIcon class="h-6 w-6 text-primary" />
                </div>
                <div class="space-y-1">
                  <CardTitle class="mb-1 text-xl">{{ $t('Dokumentacija') }}</CardTitle>
                  <CardDescription>
                    {{ $t('Instrukcijos apie vusa.lt/mano platformą ir naudotojų atsakomybes') }}
                  </CardDescription>
                </div>
              </CardContent>
            </Card>
          </a>
          <Link :href="route('dashboard.atstovavimas')">
            <Card class="transition-all duration-200 hover:bg-muted/50 hover:shadow-md">
              <CardContent class="flex gap-6 p-6 items-center">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <UsersIcon class="h-6 w-6 text-primary" />
                </div>
                <div class="space-y-1">
                  <CardTitle class="mb-1 text-xl">{{ $t('Atstovavimas') }}</CardTitle>
                  <CardDescription>
                    {{ $t('Susitikimų, tikslų ir atstovavimo veiklų stebėjimas') }}
                  </CardDescription>
                </div>
              </CardContent>
            </Card>
          </Link>
        </div>
      </section>
    </div>

    <NewMeetingModal :show-modal="showMeetingModal" @close="showMeetingModal = false" />
  </PageContent>
</template>

<script setup lang="ts">
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import { addressivize } from "@/Utils/String";
import ActivityTimeline from "@/Components/Dashboard/ActivityTimeline.vue";
import NewMeetingModal from "@/Components/Modals/NewMeetingModal.vue";

// UI components
import { 
  Card, 
  CardHeader,
  CardTitle, 
  CardDescription, 
  CardContent 
} from "@/Components/ui/card";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";

// Icons
import { 
  LayoutGridIcon,
  SettingsIcon,
  BookOpenIcon, 
  BellIcon,
  UsersIcon,
  CalendarIcon,
  ClipboardIcon,
  BuildingIcon,
  FileTextIcon,
  Activity as ActivityIcon,
  ChevronRight as ChevronRightIcon,
  LayoutDashboard as LayoutDashboardIcon,
  Clock as ClockIcon,
  CheckCircle2 as CheckCircle2Icon,
  AlertTriangle as AlertTriangleIcon,
  GraduationCap as GraduationCapIcon,
  Globe as GlobeIcon,
  Bookmark as BookmarkIcon,
} from "lucide-vue-next";

// Home page doesn't need breadcrumbs - they're cleared by AdminLayout

// Get data from props
const props = defineProps<{
  recentActivities: any[];
  taskStats: {
    completed: number;
    pending: number;
    overdue: number;
  };
  unreadNotificationsCount: number;
  hasNotifications: boolean;
  canViewTenantActivities: boolean;
}>();

// Modal state
const showMeetingModal = ref(false);

// User name with addressivization for Lithuanian
const userNameAddress = computed(() => {
  const name = usePage().props.auth?.user.name;
  const split = name?.split(" ");

  if (!split) return "";
  
  const firstName = split[0];
  return usePage().props.app.locale === 'lt' ? addressivize(firstName) : firstName;
});

// Personalized greeting based on time of day
const greeting = computed(() => {
  const hour = new Date().getHours();
  if (hour < 5) return $t('Sveiki sugrįžę į mano.vusa.lt! Vėlyvas vakaras dirbti.');
  if (hour < 12) return $t('Labas rytas! Sveiki sugrįžę į mano.vusa.lt.');
  if (hour < 18) return $t('Sveiki sugrįžę į mano.vusa.lt. Linkime produktyvios dienos!');
  return $t('Sveiki sugrįžę į mano.vusa.lt. Linkime produktyvaus vakaro!');
});

// Quick action cards
const quickActions = computed(() => {
  const actions = [];

  if (usePage().props.auth?.can.create.meeting) {
    actions.push({
      title: $t('Naujas susitikimas'),
      description: $t('Sukurti naują susitikimą su darbotvarke'),
      onClick: () => showMeetingModal.value = true,
      icon: CalendarIcon,
    });
  }

  if (usePage().props.auth?.can.create.news) {
    actions.push({
      title: $t('Nauja naujiena'),
      description: $t('Sukurti naują žinutę ar pranešimą'),
      href: route('news.index'),
      icon: FileTextIcon,
    });
  }

  if (usePage().props.auth?.can.create.reservation) {
    actions.push({
      title: $t('Nauja rezervacija'),
      description: $t('Rezervuoti išteklius'),
      href: route('reservations.create'),
      icon: BuildingIcon,
    });
  }

  if (usePage().props.auth?.can.create.goal) {
    actions.push({
      title: $t('Naujas tikslas'),
      description: $t('Sukurti naują tikslą ar uždavinį'),
      href: route('goals.index'),
      icon: ClipboardIcon,
    });
  }

  // Always include personal tasks
  actions.push({
    title: $t('Mano užduotys'),
    description: $t('Peržiūrėti ir tvarkyti užduotis'),
    href: route('userTasks'),
    icon: ClipboardIcon,
  });

  return actions.slice(0, 3); // Limit to 3 items
});

// Quick links
const quickLinks = computed(() => [
  {
    title: $t('Mano profilis'),
    href: route('profile'),
    icon: UsersIcon,
  },
  {
    title: $t('Mano užduotys'),
    href: route('userTasks'),
    icon: ClipboardIcon,
  },
  // {
  //   title: $t('Dokumentai'),
  //   href: route('documents.index'),
  //   icon: FileTextIcon,
  // }
]);

// Navigation helper
const navigateToNotifications = () => {
  router.visit(route('notifications.index'));
};

// Activity view type toggle
const showTenantActivities = computed(() => usePage().props.showTenantActivities ?? false);
const toggleActivityViewType = () => {
  router.visit(route('dashboard'), {
    data: {
      view_type: showTenantActivities.value ? 'personal' : 'tenant'
    },
    preserveState: true,
    only: ['recentActivities', 'showTenantActivities']
  });
};
</script>

<style scoped>
@keyframes wave {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(15deg); }
  50% { transform: rotate(0deg); }
  75% { transform: rotate(15deg); }
}

.animate-wave {
  animation: wave 1.5s ease-in-out;
}

h2 {
  font-weight: 700;
  font-size: 1.25rem;
  line-height: 1.75rem;
  /* more compact */
  letter-spacing: -0.02em;
}
</style>
