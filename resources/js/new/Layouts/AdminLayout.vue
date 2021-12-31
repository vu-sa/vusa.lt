<template>
  <div>
    <Head :title="title" />

    <div class="min-h-screen grid grid-cols-9 bg-gradient-to-b
          from-red-200 to-orange-200">
      <nav
        class="
          bg-white
          m-2
          drop-shadow-lg
          rounded-md
          col-span-1
          p-auto
          duration-100
          flex flex-col
          items-center
          text-center text-gray-800
        "
      >
        <button class="w-full" @click="logout"
          ><AppLogo class="m-2"
        /></button>
          <div class="bg-gray-50 w-full h-full rounded-b-md">
            <Link
              :href="route('dashboard')"
              class="block p-3 overflow-hidden hover:bg-gray-100 duration-200"
              :class="
                route().current('dashboard')
                  ? ['stroke-red-800', 'text-red-800', 'hover:text-red-900']
                  : [
                      'stroke-gray-600',
                      'hover:stroke-gray-800',
                      'text-gray-600',
                      'hover:text-gray-800',
                    ]
              "
            >
              <HomeIcon class="mx-auto w-7 h-7 mb-1" />
              <div class="w-full">Dashboard</div>
            </Link>
            <Link
              :href="route('login')"
              class="block p-3 hover:bg-gray-100 duration-200"
              :class="
                route().current('login')
                  ? ['stroke-red-800', 'text-red-800']
                  : [
                      'stroke-gray-600',
                      'hover:stroke-gray-800',
                      'text-gray-600',
                      'hover:text-gray-800',
                      'duration-200',
                    ]
              "
            >
              <NewspaperIcon class="mx-auto w-7 h-7 mb-1" />
              <div class="w-full">Articles</div>
            </Link>
          </div>
      </nav>
      <!-- Page Content -->
      <main class=" col-span-6" id="main">
        <slot></slot>
      </main>

      <aside
        class="bg-gray-50
          col-span-2
          m-4
          rounded-xl shadow-inner drop-shadow-lg
          duration-100
        "
      ></aside>
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue";
import AppLogo from "@/Components/AppLogo.vue";
import JetBanner from "@/Jetstream/Banner.vue";
import JetDropdown from "@/Jetstream/Dropdown.vue";
import JetDropdownLink from "@/Jetstream/DropdownLink.vue";
import JetNavLink from "@/Jetstream/NavLink.vue";
import JetResponsiveNavLink from "@/Jetstream/ResponsiveNavLink.vue";
import { Head, Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { HomeIcon, NewspaperIcon } from "@heroicons/vue/outline";

const props = defineProps({
  title: String,
});

const logout = () => {
  Inertia.post(route("logout"));
}

</script>
