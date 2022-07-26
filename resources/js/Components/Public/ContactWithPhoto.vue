<template>
  <div class="flex h-auto min-h-fit flex-col rounded-lg bg-white lg:flex-row">
    <div
      v-if="imageSrc"
      class="relative h-60 w-auto flex-none lg:h-auto lg:w-40"
    >
      <img
        :src="imageSrc"
        class="absolute inset-0 h-full w-full rounded-t-lg object-cover lg:rounded-t-none lg:rounded-l-lg"
        style="object-position: 50% 25%"
      />
    </div>
    <div class="flex flex-auto flex-col justify-between gap-4 p-4">
      <div class="flex flex-col flex-wrap">
        <h2 class="flex flex-auto items-center gap-2 px-2 text-slate-900">
          <span><slot name="name"></slot></span>
          <NButton
            v-if="$page.props.user"
            secondary
            circle
            size="tiny"
            @click="openEdit(contact)"
          >
            <NIcon>
              <PersonEdit24Regular />
            </NIcon>
          </NButton>
        </h2>
        <div class="w-fit p-2 text-sm font-medium text-gray-500">
          <slot name="duty"></slot>
        </div>
      </div>
      <div class="flex flex-col gap-2 text-sm text-neutral-500">
        <slot name="contactInfo"></slot>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { NButton, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import route from "ziggy-js";

defineProps<{
  contact: App.Models.User;
  imageSrc: string | null;
}>();

const openEdit = (contact: App.Models.User) => {
  window.open(route("users.edit", { user: contact.id }), "_blank");
};
</script>
