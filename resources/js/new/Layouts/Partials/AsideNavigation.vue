<template>
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
      grid grid-cols-3 gap-3
    "
  >
    <div
      class="
        flex
        items-center
        text-sm text-gray-700
        hover:text-gray-900
        transition col-span-3
        m-4
      "
    >
      <Link
        class="flex items-center"
        :href="route('profile.show')"
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
	<slot></slot>
  </aside>
</template>

<script setup>
import { Link } from "@inertiajs/inertia-vue3";
</script>