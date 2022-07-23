<template>
  <!-- <NConfigProvider :theme-overrides="themeOverrides"> -->

  <Head :title="title" />
  <MetaIcons />

  <div
    class="grid min-h-screen grid-cols-9 bg-gradient-to-tr from-vusa-red/20 to-vusa-yellow/20 px-4 pb-4"
  >
    <!-- Main Navigation -->
    <nav
      class="fixed top-0 left-0 z-20 col-span-1 mb-auto flex w-screen items-center justify-evenly overflow-x-scroll bg-stone-50 text-center text-gray-800 shadow-lg transition md:sticky md:left-auto md:top-4 md:mr-4 md:w-auto md:flex-col md:overflow-auto md:rounded-xl"
    >
      <a class="w-full md:rounded-t-lg md:bg-white" href="/">
        <AppLogo class="mx-auto my-2" />
      </a>
      <MenuButton :menu-content="['dashboard']">
        <template #icon>
          <Home48Regular />
        </template>
        Pradinis
      </MenuButton>
      <MenuButton
        v-if="can.content"
        :menu-content="['pages.*', 'news.*', 'mainPage.*', 'banners.*']"
      >
        <template #icon>
          <SlideText48Regular />
        </template>
        Turinys
      </MenuButton>
      <MenuButton
        v-if="can.users"
        :menu-content="['dutyInstitutions.*', 'duties.*', 'users.*']"
      >
        <template #icon>
          <Person48Regular />
        </template>
        Kontaktai
      </MenuButton>
      <MenuButton v-if="can.navigation" :menu-content="['navigation.*']">
        <template #icon>
          <Navigation24Regular />
        </template>
        Navigacija
      </MenuButton>
      <MenuButton
        v-if="can.calendar"
        :menu-content="['calendar.*', 'agenda.*']"
      >
        <template #icon>
          <CalendarLtr48Regular />
        </template>
        Kalendorius
      </MenuButton>
      <MenuButton v-if="can.files" :menu-content="['files.*']">
        <template #icon>
          <Folder48Regular />
        </template>
        Failų tvarkyklė
      </MenuButton>
      <MenuButton
        v-if="can.saziningai"
        :menu-content="['saziningaiExams.*', 'saziningaiExamObservers.*']"
      >
        <template #icon>
          <BookOpen48Regular />
        </template>
        Sažiningai
      </MenuButton>
      <div class="py-2">
        <NButton
          style="font-size: 8pt"
          class="text-gray-600 hover:text-vusa-red"
          text
          @click="showModal = true"
        >
          v0.2.0 (2022-07-23)
        </NButton>
      </div>
      <NModal v-model:show="showModal">
        <Changelog />
      </NModal>
    </nav>
    <!-- Main Navigation End -->
    <!-- Page Content -->
    <PageContent :create-url="createUrl" :back-url="backUrl">
      <template #header>
        <slot name="header">
          {{ title }}
        </slot>
      </template>

      <template #aside-header>
        <slot name="aside-header" />
      </template>

      <div class="col-span-2 ml-12 mt-4 mb-5" />
      <Transition name="fade" appear>
        <main class="col-span-full md:col-span-4">
          <slot />
        </main>
      </Transition>
      <!-- Aside Navigation -->
      <AsideNavigation>
        <slot name="aside-navigation-options" />
      </AsideNavigation>
      <!-- Aside Navigation End -->
    </PageContent>
    <!-- Page Content End -->
  </div>
  <!-- </NConfigProvider> -->
</template>

<script setup lang="ts">
import {
  BookOpen48Regular,
  CalendarLtr48Regular,
  Folder48Regular,
  Home48Regular,
  Navigation24Regular,
  Person48Regular,
  SlideText48Regular,
} from "@vicons/fluent";
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { NButton, NModal } from "naive-ui";
import { ref } from "vue";

import AppLogo from "@/components/AppLogo.vue";
import AsideNavigation from "@/components/Admin/Layouts/AsideNavigation.vue";
import Changelog from "@/components/Admin/Misc/ChangelogCard.vue";
import MenuButton from "@/components/Admin/MenuButton.vue";
import MetaIcons from "@/components/MetaIcons.vue";
import PageContent from "@/components/Admin/Layouts/PageContent.vue";

defineProps<{
  title?: string;
  createUrl?: string | null;
  backUrl?: string | null;
}>();

const { can } = usePage<InertiaProps>().props.value;

const showModal = ref(false);
</script>

<style>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.5s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
