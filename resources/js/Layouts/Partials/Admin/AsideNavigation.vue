<template>
  <aside
    class="bg-gray-50 rounded-xl ml-12 shadow-lg duration-200 sticky top-4 mb-auto col-span-2 md:grid grid-flow-row gap-5 hidden text-sm text-gray-700 p-6"
  >
    <div class="flex items-center hover:text-gray-900 transition col-span-3">
      <Link
        class="flex items-center"
        :href="route('profile.show')"
        v-if="$page.props.jetstream.managesProfilePhotos"
      >
        <img
          class="h-8 w-8 rounded-full object-cover"
          :src="
            $page.props.user.profile_photo_path
              ? $page.props.user.profile_photo_path
              : $page.props.user.profile_photo_url
          "
          :alt="$page.props.user.name"
        />
        <span class="ml-2"
          >{{ $page.props.user.name }} ({{
            $page.props.user_padalinys ?? "Be padalinio"
          }})</span
        >
      </Link>
      <button class="ml-auto mr-2" @click="logout()">Atsijungti</button>
    </div>
    <slot></slot>
  </aside>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";

const logout = () => {
  Inertia.post(route("logout"));
};
</script>
