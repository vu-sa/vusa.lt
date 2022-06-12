<template>
  <!-- <NConfigProvider :theme-overrides="themeOverrides"> -->

  <Head :title="title" />
  <MetaIcons />

  <FullWindow>
    <!-- Main Navigation -->
    <MainNavigation>
      <a class="w-full md:bg-white md:rounded-t-lg" href="/">
        <AppLogo class="mx-auto my-2" />
      </a>
      <MenuButton :menuContent="['dashboard']">
        <template #icon>
          <HomeIcon class="admin-navigation-icon" />
        </template>
        Pradžia
      </MenuButton>
      <MenuButton
        v-if="$page.props.can.content"
        :menuContent="['pages.*', 'news.*', 'mainPage.*', 'banners.*']"
      >
        <template #icon>
          <DocumentTextIcon class="admin-navigation-icon" />
        </template>
        Turinys
      </MenuButton>
      <MenuButton
        v-if="$page.props.can.users"
        :menuContent="['users.*', 'duties.*', 'dutyInstitutions.*', 'roles.*']"
      >
        <template #icon>
          <UserIcon class="admin-navigation-icon" />
        </template>
        Kontaktai
      </MenuButton>
      <MenuButton v-if="$page.props.can.navigation" :menuContent="['navigation.*']">
        <template #icon>
          <MenuAlt2Icon class="admin-navigation-icon" />
        </template>
        Navigacija
      </MenuButton>
      <MenuButton
        v-if="$page.props.can.calendar"
        :menuContent="['calendar.*', 'agenda.*']"
      >
        <template #icon>
          <CalendarIcon class="admin-navigation-icon" />
        </template>
        Kalendorius
      </MenuButton>
      <MenuButton v-if="$page.props.can.files" :menuContent="['files.*']">
        <template #icon>
          <FolderIcon class="admin-navigation-icon" />
        </template>
        Failų tvarkyklė
      </MenuButton>
      <MenuButton
        v-if="$page.props.can.saziningai"
        :menuContent="['saziningaiExams.*', 'saziningaiExamObservers.*']"
      >
        <template #icon>
          <BookOpenIcon class="admin-navigation-icon" />
        </template>
        Sažiningai
      </MenuButton>
      <div class="py-2">
        <NButton
          style="font-size: 8pt"
          class="hover:text-vusa-red text-gray-600"
          text
          @click="showModal = true"
        >
          v0.1.1 (2022-06-12)
        </NButton>
      </div>
      <NModal v-model:show="showModal">
        <Changelog></Changelog>
      </NModal>
    </MainNavigation>
    <!-- Main Navigation End -->
    <!-- Page Content -->
    <PageContent :createURL="createURL">
      <template #header>
        <slot name="header">{{ title }}</slot>
      </template>

      <template #aside-header>
        <slot name="aside-header"></slot>
      </template>

      <div class="col-span-2 ml-12 mt-4 mb-5"></div>
      <transition name="fade">
        <main v-if="animated" class="md:col-span-4 col-span-full">
          <slot />
        </main>
      </transition>
      <!-- Aside Navigation -->
      <AsideNavigation>
        <slot name="aside-navigation-options"></slot>
      </AsideNavigation>
      <!-- Aside Navigation End -->
    </PageContent>
    <!-- Page Content End -->
  </FullWindow>
  <!-- </NConfigProvider> -->
</template>

<script setup>
import { onMounted, ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import {
  HomeIcon,
  BookOpenIcon,
  DocumentTextIcon,
  UserIcon,
  MenuAlt2Icon,
  CalendarIcon,
  FolderIcon,
} from "@heroicons/vue/outline";
import MetaIcons from "@/Components/MetaIcons.vue";
import MenuButton from "@/Components/Admin/MenuButton.vue";
import MainNavigation from "@/Layouts/Partials/Admin/MainNavigation.vue";
import FullWindow from "@/Layouts/Partials/Admin/FullWindow.vue";
import PageContent from "@/Layouts/Partials/Admin/PageContent.vue";
import AsideNavigation from "@/Layouts/Partials/Admin/AsideNavigation.vue";
import { useMessage, NModal, NButton } from "naive-ui";
import Changelog from "@/Components/Admin/Changelog.vue";
// import { NConfigProvider } from "naive-ui";

const props = defineProps({
  title: String,
  createURL: String,
});

const animated = ref(false);
const showModal = ref(false);

// const themeOverrides = {
//   common: {
//     baseColor: "#fafafa",
//     },
// };

onMounted(() => {
  animated.value = true;
});

const logout = () => {
  Inertia.post(route("logout"));
};
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
