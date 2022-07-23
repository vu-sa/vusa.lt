<template>
  <aside
    class="main-card sticky top-4 col-span-2 ml-12 mb-auto hidden grid-flow-row gap-5 md:grid"
  >
    <div
      class="col-span-3 flex flex-col items-center justify-center gap-3 transition hover:text-gray-900 xl:flex-row"
    >
      <div class="flex items-center">
        <img
          class="h-8 w-8 rounded-full object-cover"
          :src="profilePhotoPath(inertiaProps.user)"
          :alt="inertiaProps.user.name"
        />
        <span class="ml-2"
          >{{ inertiaProps.user.name }} ({{
            inertiaProps.user.padalinys ?? "Be padalinio"
          }})</span
        >
      </div>
      <div class="xl:ml-auto">
        <NButton
          icon-placement="right"
          :loading="loading"
          secondary
          size="small"
          @click="handleClick"
        >
          <template #icon>
            <NIcon>
              <DoorArrowRight28Regular />
            </NIcon>
          </template>
          Atsijungti
        </NButton>
      </div>
    </div>
    <slot></slot>
  </aside>
</template>

<script setup lang="ts">
import { DoorArrowRight28Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

const inertiaProps = usePage<InertiaProps>().props.value;
const loading = ref(false);

// compute a profile photo path
const profilePhotoPath = (user: App.Models.User) => {
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

const handleClick = () => {
  loading.value = true;
  Inertia.post(route("logout"));
};
</script>
