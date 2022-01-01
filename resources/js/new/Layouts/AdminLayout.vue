<template>
    <Head :title="title" />
    <MetaIcons />

    <FullWindow>
      <MainNavigation>
        <a class="w-full" href="/">
          <AppLogo class="mx-auto my-2" />
        </a>
        <div class="bg-gray-50 w-full h-full rounded-b-xl">
          <MenuButton menuButtonRoute="dashboard">
            <template #icon>
              <HomeIcon class="mx-auto w-7 h-7 mb-1" />
            </template>
            Dashboard
          </MenuButton>
          <MenuButton menuButtonRoute="telescope">
            <template #icon
              ><NewspaperIcon class="mx-auto w-7 h-7 mb-1"
            /></template>
            Articles
          </MenuButton>
        </div>
      </MainNavigation>
      <!-- Page Content -->
      <div
        class="
          col-span-8
          mt-10
          mx-8
          grid grid-flow-row
          auto-rows-max
          grid-cols-6
        "
      >
        <header class="mt-4 mb-5 col-span-4">
          <h1 class="font-black text-3xl text-gray-800 leading-tight">
            <slot name="header">{{ title }}</slot>
          </h1>
        </header>
        <div class="col-span-2 ml-12 mt-4 mb-5"></div>
        <main class="bg-white p-8 rounded-xl drop-shadow-md col-span-4">
          <slot></slot>
        </main>

        <aside
          class="
            bg-gray-50
            rounded-xl
            ml-12
            shadow-inner
            drop-shadow-lg
            duration-100
            sticky
            top-4
            mb-auto
            col-span-2
            grid grid-flow-row
          "
        >
          <div
            class="
              flex
              items-center
              text-sm text-gray-700
              hover:text-gray-900
              transition
              m-4
            "
          >
            <Link
              class="flex items-center"
              :href="route('jetstream.profile.show')"
              v-if="$page.props.jetstream.managesProfilePhotos"
            >
              <img
                class="h-8 w-8 rounded-full object-cover"
                :src="$page.props.user.profile_photo_url"
                :alt="$page.props.user.name"
              />
              <span class="ml-2">{{ $page.props.user.name }}</span>
            </Link>
            <button class="ml-auto mr-2" @click="logout">Log out</button>
          </div>
        </aside>
      </div>
    </FullWindow>
</template>

<script setup>
import { ref } from "vue";
import AppLogo from "@/Components/Admin/AppLogo.vue";
import JetBanner from "@/Jetstream/Banner.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import JetDropdownLink from "@/Jetstream/DropdownLink.vue";
import JetNavLink from "@/Jetstream/NavLink.vue";
import JetResponsiveNavLink from "@/Jetstream/ResponsiveNavLink.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { HomeIcon, NewspaperIcon } from "@heroicons/vue/outline";
import MetaIcons from "@/Components/MetaIcons.vue";
import MenuButton from "@/Components/Admin/MenuButton.vue";
import MainNavigation from "@/Layouts/Partials/MainNavigation.vue"
import FullWindow from "@/Layouts/Partials/FullWindow.vue";

const props = defineProps({
  title: String,
});

const logout = () => {
  Inertia.post(route("logout"));
};
</script>
