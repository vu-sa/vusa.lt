<template>
  <aside
    class="bg-gray-50 rounded-xl ml-12 shadow-lg duration-200 sticky top-4 mb-auto col-span-2 md:grid grid-flow-row gap-5 hidden text-sm text-gray-700 p-6"
  >
    <div class="flex items-center hover:text-gray-900 transition col-span-3">
      <div class="flex items-center">
        <img
          class="h-8 w-8 rounded-full object-cover"
          :src="profilePhotoPath($page.props.user)"
          :alt="$page.props.user.name"
        />
        <span class="ml-2"
          >{{ $page.props.user.name }} ({{
            $page.props.user.padalinys ?? "Be padalinio"
          }})</span
        >
      </div>
      <Link
        as="button"
        method="post"
        class="ml-auto mr-2"
        :href="route('logout')"
        >Atsijungti</Link
      >
    </div>
    <slot></slot>
  </aside>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";

// compute a profile photo path
const profilePhotoPath = (user) => {
  // user type is object with photo_path property

  if (user.profile_photo_path) {
    return user.profile_photo_path;
  }

  // replace user name spaces with plus and return ui avatars url
  return `https://ui-avatars.com/api/?name=${user.name.replace(
    /\s/g,
    "+"
  )}?background=random`;
};
</script>
