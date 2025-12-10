<template>
  <AdminContentPage :title="$t('Administravimas')">
    <!-- Search and filter bar -->
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div class="relative w-full max-w-md">
        <Input
          v-model="searchQuery"
          :placeholder="$t('Ieškoti įrankių...')"
          class="w-full"
        >
          <template #prefix>
            <SearchIcon class="h-4 w-4 text-muted-foreground" />
          </template>
          <template #suffix v-if="searchQuery">
            <Button variant="ghost" size="icon" @click="searchQuery = ''">
              <XIcon class="h-4 w-4" />
            </Button>
          </template>
        </Input>
      </div>
      <div class="flex items-center gap-2">
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <Button variant="outline" size="sm">
              <ListFilterIcon class="mr-2 h-4 w-4" />
              {{ $t('Filtrai') }}
              <ChevronDownIcon class="ml-2 h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuCheckboxItem
              v-model="showOnlyFavorites"
            >
              {{ $t('Tik mėgstamiausi') }}
            </DropdownMenuCheckboxItem>
            <DropdownMenuSeparator />
            <DropdownMenuLabel>{{ $t('Kategorijos') }}</DropdownMenuLabel>
            <DropdownMenuSeparator />
            <DropdownMenuCheckboxItem
              v-for="category in uniqueCategories"
              :key="category"
              v-model="selectedCategories[category]"
              @select.prevent
            >
              {{ category }}
            </DropdownMenuCheckboxItem>
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>

    <!-- Favorites section (if any) -->
    <Transition name="fade">
      <section v-if="showFavoritesSection" class="mb-8">
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t('Mėgstamiausi') }} 
          <Badge variant="outline" class="ml-2">{{ favoriteMenuItems.length }}</Badge>
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div v-for="item in favoriteMenuItems" 
              :key="item.title + item.href" 
              class="group relative rounded-lg transition-all duration-200">
            <Link :href="item.href" class="block h-full w-full">
              <div class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-sm leading-4 text-zinc-700 shadow-xs transition-all duration-300 group-hover:shadow-md group-hover:ring-1 group-hover:ring-primary/20 dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:group-hover:shadow-white/10">
                <component :is="item.icon" width="28" height="28" />
                {{ item.title }}
              </div>
            </Link>
            <Button 
              variant="ghost"
              size="icon" 
              class="absolute right-2 top-2 z-10 bg-background/80 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
              @click.prevent="toggleFavorite(item)"
            >
              <StarIcon v-if="isFavorite(item)" class="h-4 w-4 text-amber-500" fill="currentColor" />
              <StarIcon v-else class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </section>
    </Transition>

    <!-- Main categories -->
    <template v-for="category in filteredMenuItems" :key="category.category">
      <section v-if="category.show" class="my-8">
        <h2 class="mb-4 text-xl font-semibold flex items-center">
          {{ category.category }}
          <Badge variant="outline" class="ml-2">{{ category.visibleItems.length }}</Badge>
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div v-for="item in category.visibleItems" 
              :key="item.title + item.href" 
              class="group relative rounded-lg transition-all duration-200 hover:scale-[1.01]">
            <Link :href="item.href" class="block h-full w-full">
              <div class="flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-linear-to-br from-white to-white p-4 text-left text-sm leading-4 text-zinc-700 shadow-xs transition-all duration-300 group-hover:shadow-md group-hover:ring-1 group-hover:ring-primary/20 dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:group-hover:shadow-white/10">
                <component :is="item.icon" width="28" height="28" />
                {{ item.title }}
              </div>
            </Link>
            <Button 
              variant="ghost"
              size="icon" 
              class="absolute right-2 top-2 z-10 bg-background/80 opacity-0 transition-opacity duration-200 group-hover:opacity-100"
              @click.prevent="toggleFavorite(item)"
            >
              <StarIcon v-if="isFavorite(item)" class="h-4 w-4 text-amber-500" fill="currentColor" />
              <StarIcon v-else class="h-4 w-4" />
            </Button>
          </div>
        </div>
      </section>
    </template>

    <!-- Empty state when no items match filter -->
    <Alert v-if="!hasVisibleItems" variant="default" class="mt-8">
      <AlertCircleIcon class="h-4 w-4" />
      <AlertTitle>{{ $t('Nerasta rezultatų') }}</AlertTitle>
      <AlertDescription>
        {{ $t('Bandykite pakeisti paieškos kriterijus arba filtrus.') }}
      </AlertDescription>
    </Alert>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import Icons from '@/Types/Icons/regular';
import { capitalize } from '@/Utils/String';
import { Link, usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, type Component } from 'vue';

// Icons
import IconFlowchart from "~icons/fluent/flowchart20-regular";
import { 
  SearchIcon, 
  StarIcon, 
  XIcon, 
  ListFilterIcon, 
  ChevronDownIcon,
  AlertCircleIcon 
} from 'lucide-vue-next';

// UI components
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Badge } from '@/Components/ui/badge';
import { Alert, AlertTitle, AlertDescription } from '@/Components/ui/alert';
import { 
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuCheckboxItem,
} from '@/Components/ui/dropdown-menu';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

const { auth } = usePage().props;

// Set up breadcrumbs
usePageBreadcrumbs([
  { label: $t('Administravimas'), icon: Icons.TYPE }
]);

type MenuItemType = {
  title: string;
  icon: Component;
  href: string;
  show: boolean | undefined;
};

type MenuItemsType = {
  category: string;
  items: MenuItemType[];
  visibleItems: MenuItemType[];
  show: boolean | undefined;
};

// Search and filter states
const searchQuery = ref('');
const showOnlyFavorites = ref(false);
const favoriteItems = useStorage<string[]>('favoriteItems', []);
const selectedCategories = ref<Record<string, boolean>>({});

// Helper functions for favorites
const isFavorite = (item: MenuItemType): boolean => {
  // Use a unique identifier combining title and href
  return favoriteItems.value.includes(`${item.title}:${item.href}`);
};

const toggleFavorite = (item: MenuItemType) => {
  const identifier = `${item.title}:${item.href}`;
  const index = favoriteItems.value.indexOf(identifier);
  if (index === -1) {
    favoriteItems.value.push(identifier);
  } else {
    favoriteItems.value.splice(index, 1);
  }
};

// Filter items that match search query
const matchesSearch = (item: MenuItemType): boolean => {
  if (!searchQuery.value) return true;
  const query = searchQuery.value.toLowerCase();
  return item.title.toLowerCase().includes(query);
};

// Menu items definition - Reorganized to avoid duplications
const menuItems = computed(() => [
  {
    category: $t('Žmonės'),
    items: [
      {
        title: $t('Vartotojai'),
        icon: Icons.USER,
        href: route('users.index'),
        show: auth?.can.create.user
      },
      {
        title: $t('Pareigybės'),
        icon: Icons.DUTY,
        href: route('duties.index'),
        show: auth?.can.create.duty
      },
      {
        title: $t('Narystės'),
        icon: Icons.INSTITUTION,
        href: route('memberships.index'),
        show: auth?.can.create.membership
      },
      {
        title: $t('Mokymai'),
        icon: Icons.TRAINING,
        href: route('trainings.index'),
        show: auth?.can.create.training
      },
      {
        title: $t('Studijų programos'),
        icon: Icons.STUDY_PROGRAM,
        href: route('studyPrograms.index'),
        show: auth?.can.create.studyProgram
      },
    ],
    show: auth?.can.create.user || auth?.can.create.duty || auth?.can.create.membership || auth?.can.create.training || auth?.can.create.studyProgram,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Organizacijos'),
    items: [
      {
        title: $t('Institucijos'),
        icon: Icons.INSTITUTION,
        href: route('institutions.index'),
        show: auth?.can.create.institution
      },
      {
        title: $t('Padaliniai'),
        icon: Icons.TENANT,
        href: route('tenants.index'),
        show: auth?.can.create.tenant
      },
      {
        title: $t('Institucijų grafa'),
        icon: IconFlowchart,
        href: route('institutionGraph'),
        show: auth?.can.create.institution
      },
    ],
    show: auth?.can.create.institution || auth?.can.create.tenant,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Svetainė'),
    items: [
      {
        title: $t('Puslapiai'),
        icon: Icons.PAGE,
        href: route('pages.index'),
        show: auth?.can.create.page
      },
      {
        title: $t('Naujienos'),
        icon: Icons.NEWS,
        href: route('news.index'),
        show: auth?.can.create.news
      },
      {
        title: $t('Greitosios nuorodos'),
        icon: Icons.QUICK_LINK,
        href: route('quickLinks.index'),
        show: auth?.can.create.quickLink,
      },
      {
        title: $t('Baneriai'),
        icon: Icons.BANNER,
        href: route('banners.index'),
        show: auth?.can.create.banner
      },
      {
        title: $t('Navigacija'),
        icon: Icons.NAVIGATION,
        href: route('navigation.index'),
        show: auth?.can.create.navigation
      },
      {
        title: $t('Kalendorius'),
        icon: Icons.CALENDAR,
        href: route('calendar.index'),
        show: auth?.can.create.calendar
      },
      {
        title: $t('Kategorijos'),
        icon: Icons.CATEGORY,
        href: route('categories.index'),
        show: auth?.can.create.category
      },
      {
        title: $t('Žymos'),
        icon: Icons.TAG,
        href: route('tags.index'),
        show: auth?.can.create.tag
      },
    ],
    show: auth?.can.create.page || auth?.can.create.news || auth?.can.create.quickLink || auth?.can.create.banner || auth?.can.create.navigation || auth?.can.create.calendar || auth?.can.create.category || auth?.can.create.tag,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Failai ir dokumentai'),
    items: [
      {
        title: $t('Svetainės failai'),
        icon: Icons.SHAREPOINT_FILE,
        href: route('files.index'),
        show: auth?.can.create.news || auth?.can.create.page
      },
      {
        title: $t('Dokumentai'),
        icon: Icons.DOCUMENT,
        href: route('documents.index'),
        show: auth?.can.create.document
      },
      {
        title: $t('Sharepoint failai'),
        icon: Icons.SHAREPOINT_FILE,
        href: route('sharepointFiles.index'),
        show: auth?.can.create.sharepointFile
      },
    ],
    show: auth?.can.create.news || auth?.can.create.page || auth?.can.create.document || auth?.can.create.sharepointFile,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Atstovavimas'),
    items: [
      {
        title: $t('Susitikimai'),
        icon: Icons.MEETING,
        href: route('meetings.index'),
        show: auth?.can.create.meeting
      },
    ],
    show: auth?.can.create.meeting,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Rezervacijos'),
    items: [
      {
        title: capitalize($tChoice("entities.reservation.model", 2)),
        icon: Icons.RESERVATION,
        href: route('reservations.index'),
        show: auth?.can.create.reservation
      },
      {
        title: capitalize($tChoice("entities.resource.model", 2)),
        icon: Icons.RESOURCE,
        href: route('resources.index'),
        show: auth?.can.create.resource
      },
      {
        title: capitalize($tChoice("entities.resource_category.model", 2)),
        icon: Icons.CATEGORY,
        href: route('resourceCategories.index'),
        show: auth?.can.create.resource
      },
    ],
    show: auth?.can.create.reservation || auth?.can.create.resource,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Formos'),
    items: [
      {
        title: $t('Formos'),
        href: route('forms.index'),
        icon: Icons.FORM,
        show: auth?.can.create.form
      },
    ],
    show: auth?.can.create.form,
    visibleItems: [] as MenuItemType[]
  },
  {
    category: $t('Sistema'),
    items: [
      {
        title: $t('Rolės'),
        icon: Icons.ROLE,
        href: route('roles.index'),
        show: auth?.can.create.role
      },
      {
        title: $t('Leidimai'),
        icon: Icons.PERMISSION,
        href: route('permissions.index'),
        show: auth?.can.create.permission
      },
      {
        title: $t('Tipai'),
        icon: Icons.TYPE,
        href: route('types.index'),
        show: auth?.can.create.type
      },
      {
        title: $t('Ryšiai'),
        icon: Icons.RELATIONSHIP,
        href: route('relationships.index'),
        show: auth?.can.create.relationship
      },
      {
        title: $t('Sistemos būsena'),
        icon: Icons.NOTIFICATION,
        href: route('systemStatus'),
        show: auth?.can.create.role || auth?.can.create.permission
      },
      {
        title: $t('settings.title'),
        icon: Icons.SETTING,
        href: route('settings.index'),
        show: auth?.can.manageSettings
      },
    ],
    show: auth?.can.create.role || auth?.can.create.permission || auth?.can.create.type || auth?.can.create.relationship || auth?.can.manageSettings,
    visibleItems: [] as MenuItemType[]
  }
]);

// Get unique categories for filter dropdown
const uniqueCategories = computed(() => {
  return menuItems.value
    .filter(category => category.show === true)
    .map(category => category.category);
});

// Initialize category filters if not already set
uniqueCategories.value.forEach(category => {
  if (selectedCategories.value[category] === undefined) {
    selectedCategories.value[category] = true;
  }
});

// Filter menu items based on search, favorites and selected categories
const filteredMenuItems = computed(() => {
  return menuItems.value.map(category => {
    // Clone the category to avoid modifying the original
    const filteredCategory = { ...category };
    
    // Filter items based on search and visibility
    let filteredItems = category.items.filter(item => item.show === true && matchesSearch(item));
    
    // Apply favorites filter if needed
    if (showOnlyFavorites.value) {
      filteredItems = filteredItems.filter(item => isFavorite(item));
    }
    
    // Store filtered items for display
    filteredCategory.visibleItems = filteredItems;
    
    return filteredCategory;  }).filter(category => {
    // Keep category if it's enabled in filters and has visible items
    return category.show === true &&
           category.visibleItems.length > 0 &&
           selectedCategories.value[category.category];
  });
});

// Get all favorite menu items across categories
const favoriteMenuItems = computed(() => {
  // Collect all items across all categories
  const allItems = menuItems.value.flatMap(category => category.items);
  
  // Filter for favorites that are visible and match search
  return allItems.filter(item =>
    item.show === true &&
    isFavorite(item) &&
    matchesSearch(item)
  );
});

// Show favorites section if there are favorites and filter is not active
const showFavoritesSection = computed(() => {
  return favoriteMenuItems.value.length > 0 && !showOnlyFavorites.value;
});

// Check if any items are visible after filtering
const hasVisibleItems = computed(() => {
  return filteredMenuItems.value.some(category => category.visibleItems.length > 0);
});

</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
