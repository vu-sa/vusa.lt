<template>
  <AdminContentPage title="Administravimas">
    <template v-for="category in menuItems">
      <section v-if="category.show" class="my-8">
        <h2 v-if="category.show" class="mb-4 text-xl font-semibold">
          {{ category.category }}
        </h2>
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
          <template v-for="item in category.items">
            <Link v-if="item.show" :key="item.title" :href="item.href">
            <button
              class="group relative flex w-full flex-col gap-3 rounded-md border border-zinc-100 bg-gradient-to-br from-white to-white p-4 text-left text-sm leading-4 text-zinc-700 shadow-sm duration-500 hover:shadow-lg dark:border-0 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300 dark:hover:shadow-white/10">
              <component :is="item.icon" width="28" height="28" />
              {{ item.title }}
              <!-- Add favorite button -->
              <!-- <div class="absolute -right-1 -top-4 opacity-0 duration-300 group-hover:opacity-100">
                <NButton tiny circle secondary @click.prevent="changeFavorite(item)">
                  <template #icon>
                    <IFluentStar16Filled />
                  </template>
                </NButton>
</div> -->
            </button>
            </Link>
          </template>
        </div>
      </section>
    </template>
  </AdminContentPage>
</template>

<script setup lang="ts">
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import Icons from '@/Types/Icons/regular';
import { capitalize } from '@/Utils/String';
import { Link, usePage } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, type Component } from 'vue';

import IconFlowchart from "~icons/fluent/flowchart20-regular";

const { auth } = usePage().props;

type MenuItemsType = {
  category: string;
  items: {
    title: string;
    icon: Component;
    href: string;
    show: boolean;
  }[];
  show: boolean;
};

const favoriteItems = useStorage('favoriteItems', []);

const changeFavorite = (item: { title: string }) => {
  const index = favoriteItems.value.indexOf(item.title);
  if (index === -1) {
    favoriteItems.value.push(item.title);
  } else {
    favoriteItems.value.splice(index, 1);
  }
};

// TODO: use entities.ts?
const menuItems: MenuItemsType = computed(() => [
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
        title: $t('Institucijos'),
        icon: Icons.INSTITUTION,
        href: route('institutions.index'),
        show: auth?.can.create.institution
      },
      {
        title: $t('Narystės'),
        icon: Icons.INSTITUTION,
        href: route('memberships.index'),
        show: true
      },
      {
        title: $t('Mokymai'),
        icon: Icons.TRAINING,
        href: route('trainings.index'),
        show: true
      },
    ],
    show: auth?.can.create.user || auth?.can.create.duty || auth?.can.create.institution
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
        icon: Icons.MAIN_PAGE,
        href: route('mainPage.index'),
        show: auth?.can.create.mainPage,
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
    ],
    show: auth?.can.create.page || auth?.can.create.news || auth?.can.create.mainPage || auth?.can.create.document || auth?.can.create.banner || auth?.can.create.navigation || auth?.can.create.calendar || auth?.can.create.category
  },
  {
    category: $t('Atstovavimas'),
    items: [
      {
        title: $t('Institucijos'),
        icon: Icons.INSTITUTION,
        href: route('institutions.index'),
        show: auth?.can.create.institution
      },
      {
        title: $t('Susitikimai'),
        icon: Icons.MEETING,
        href: route('meetings.index'),
        show: auth?.can.create.meeting
      },
      {
        title: $t('Vartotojai'),
        icon: Icons.USER,
        href: route('users.index'),
        show: auth?.can.create.user
      },
      {
        title: $t('Veiklos'),
        icon: Icons.DOING,
        href: route('doings.index'),
        show: auth?.can.create.doing
      },
      {
        title: $t('Sharepoint failai'),
        icon: Icons.SHAREPOINT_FILE,
        href: route('sharepointFiles.index'),
        show: auth?.can.create.sharepointFile
      },
      {
        title: $t('Tikslai'),
        icon: Icons.GOAL,
        href: route('goals.index'),
        show: auth?.can.create.goal
      },
      {
        title: $t('Tikslų grupės'),
        icon: Icons.GOAL_GROUP,
        href: route('goalGroups.index'),
        show: auth?.can.create.goalGroup
      },
      {
        title: $t('Svarstomi klausimai'),
        icon: Icons.MATTER,
        href: route('matters.index'),
        show: auth?.can.create.matter
      },
      {
        title: $t('Institucijų grafa'),
        icon: IconFlowchart,
        href: route('institutionGraph'),
        show: auth?.can.create.institution
      },
    ],
    show: auth?.can.create.institution || auth?.can.create.meeting || auth?.can.create.user || auth?.can.create.doing || auth?.can.create.goal || auth?.can.create.goalGroup || auth?.can.create.matter || auth?.can.create.sharepointFile
  },
  {
    category: $t('Formos'),
    items: [
      {
        title: $t('Formos'),
        href: route('forms.index'),
        icon: Icons.FORM,
        show: true
      },
    ],
    show: true
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
    show: auth?.can.create.reservation || auth?.can.create.resource
  },
  {
    category: $t('Nustatymai'),
    // type, relationship, role, permission, tenant, changelogItem
    items: [
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
        title: $t('Padaliniai'),
        icon: Icons.TENANT,
        href: route('tenants.index'),
        show: auth?.can.create.tenant
      },
      {
        title: $t('Pakeitimai'),
        icon: Icons.CHANGELOG_ITEM,
        href: route('changelogItems.index'),
        show: auth?.can.create.changelogItem
      },
    ],
    show: auth?.can.create.type || auth?.can.create.relationship || auth?.can.create.role || auth?.can.create.permission || auth?.can.create.tenant || auth?.can.create.changelogItem
  }
])
</script>
