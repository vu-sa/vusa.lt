<template>
  <div>
    <Head :title="title" />

    <div
      class="
        min-h-screen
        grid grid-cols-9
        bg-gradient-to-b
        from-red-200
        to-orange-200
      "
    >
      <nav
        class="
          bg-white
          mt-4
          mx-4
          drop-shadow-lg
          rounded-xl
          col-span-1
          sticky
          top-4
          mb-auto
          duration-100
          flex flex-col
          items-center
          text-center text-gray-800
        "
      >
        <button class="w-full" @click="logout">
          <AppLogo class="mx-auto my-2" />
        </button>
        <div class="bg-gray-50 w-full h-full rounded-b-xl">
          <Link
            :href="route('dashboard')"
            class="
              block
              p-3
              overflow-hidden
              hover:bg-gray-100
              duration-100
              last:hover:rounded-b-xl
            "
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
            class="
              block
              p-3
              hover:bg-gray-100
              duration-100
              last:hover:rounded-b-xl
            "
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
          <slot name="header"></slot>
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
          "
        >
          <Link
            :href="route('jetstream.profile.show')"
            v-if="$page.props.jetstream.managesProfilePhotos"
            class="
              flex
              items-center
              text-sm
              rounded-full
              focus:outline-none focus:border-gray-300
              transition
              m-4
            "
          >
            <img
              class="h-8 w-8 rounded-full object-cover"
              :src="$page.props.user.profile_photo_url"
              :alt="$page.props.user.name"
            />
            <span class="ml-2">{{ $page.props.user.name }}</span>
          </Link>
        </aside>
      </div>
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
};
</script>
