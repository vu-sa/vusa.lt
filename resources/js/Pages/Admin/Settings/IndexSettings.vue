<template>
  <PageContent :title="$t('settings.title')" :back-url="route('administration')">
    <div class="space-y-6">
      <!-- Description -->
      <p class="text-muted-foreground">
        {{ $t('settings.description') }}
      </p>

      <!-- General Settings Category -->
      <section>
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t('settings.categories.general') }}
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div
            v-for="item in generalItems"
            :key="item.href"
            class="group relative rounded-lg transition-all duration-200 hover:scale-[1.01]"
          >
            <Link :href="item.href" class="block h-full w-full">
              <div :class="[
                'relative flex w-full flex-col gap-3 rounded-md p-4 text-left',
                'text-sm leading-4 font-medium text-zinc-700',
                'border border-zinc-100 bg-linear-to-br from-white to-white',
                'transition-all duration-300 group-hover:ring-1 group-hover:ring-primary/20',
                'dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300',
              ]">
                <component :is="item.icon" width="28" height="28" />
                <span>{{ item.title }}</span>
                <span class="text-xs font-normal text-muted-foreground">
                  {{ item.description }}
                </span>
              </div>
            </Link>
          </div>
        </div>
      </section>

      <!-- Authorization Settings Category (Super Admin Only) -->
      <section v-if="isSuperAdmin">
        <h2 class="mb-4 text-xl font-semibold">
          {{ $t('settings.categories.authorization') }}
        </h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
          <div
            v-for="item in authorizationItems"
            :key="item.href"
            class="group relative rounded-lg transition-all duration-200 hover:scale-[1.01]"
          >
            <Link :href="item.href" class="block h-full w-full">
              <div :class="[
                'relative flex w-full flex-col gap-3 rounded-md p-4 text-left',
                'text-sm leading-4 font-medium text-zinc-700',
                'border border-zinc-100 bg-linear-to-br from-white to-white',
                'transition-all duration-300 group-hover:ring-1 group-hover:ring-primary/20',
                'dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300',
              ]">
                <component :is="item.icon" width="28" height="28" />
                <span>{{ item.title }}</span>
                <span class="text-xs font-normal text-muted-foreground">
                  {{ item.description }}
                </span>
              </div>
            </Link>
          </div>
        </div>
      </section>
    </div>
  </PageContent>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, type Component } from 'vue';
import { CalendarRange } from 'lucide-vue-next';

import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import { DocumentIcon, FormIcon, InstitutionIcon, MeetingIcon, PageIcon, RoleIcon } from '@/Components/icons';

defineProps<{
  isSuperAdmin: boolean;
}>();

interface SettingsItemType {
  title: string;
  description: string;
  icon: Component;
  href: string;
}

const generalItems = computed<SettingsItemType[]>(() => [
  {
    title: $t('settings.pages.forms.title'),
    description: $t('settings.pages.forms.description'),
    icon: FormIcon,
    href: route('settings.forms.edit'),
  },
  {
    title: $t('settings.pages.meetings.title'),
    description: $t('settings.pages.meetings.description'),
    icon: MeetingIcon,
    href: route('settings.meetings.edit'),
  },
  {
    title: $t('settings.pages.atstovavimas.title'),
    description: $t('settings.pages.atstovavimas.description'),
    icon: InstitutionIcon,
    href: route('settings.atstovavimas.edit'),
  },
  {
    title: $t('settings.pages.documents.title'),
    description: $t('settings.pages.documents.description'),
    icon: DocumentIcon,
    href: route('settings.documents.edit'),
  },
  {
    title: $t('settings.pages.cadences.title'),
    description: $t('settings.pages.cadences.description'),
    icon: CalendarRange,
    href: route('settings.cadences.index'),
  },
  {
    title: $t('settings.pages.site.title'),
    description: $t('settings.pages.site.description'),
    icon: PageIcon,
    href: route('settings.site.edit'),
  },
]);

const authorizationItems = computed<SettingsItemType[]>(() => [
  {
    title: $t('settings.pages.authorization.title'),
    description: $t('settings.pages.authorization.description'),
    icon: RoleIcon,
    href: route('settings.authorization.edit'),
  },
]);
</script>
