<template>
  <PublicLayout title="Kontaktai">
    <div class="px-16 lg:px-32">
      <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-8">
        <div v-if="institution.image_url" class="relative group sm:col-span-2">
          <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
          <ShapeDivider1 class="absolute rotate-180 -bottom-1 z-10"></ShapeDivider1>
          <img
            :src="institution.image_url"
            class="h-64 lg:h-96 w-full object-cover my-4 hover:opacity-90 duration-200"
            style="object-position: 0% 35%"
          />
        </div>
        <div
          :class="{ 'sm:row-span-2': !institution.image_url }"
          class="my-auto prose prose-sm"
        >
          <h1>{{ institution.name ?? institution.short_name }}</h1>
          <div class="" v-html="institution.description"></div>
        </div>
        <ContactWithPhoto
          v-for="contact in contacts"
          :key="contact.id"
          :imageSrc="contact.image"
        >
          <template #name> {{ contact.name }} </template>
          <template #duty>
            <NPopover
              v-if="contact.duty.description"
              trigger="hover"
              :style="{ maxWidth: '250px' }"
              ><template #trigger
                ><p class="cursor-pointer">{{ contact.duty.name }}</p></template
              >
              <span v-html="contact.duty.description"></span>
            </NPopover>
            <p v-else>{{ contact.duty.name }}</p>
          </template>
          <template #contactInfo>
            <div v-if="contact.phone" class="flex flex-row items-center">
              <NIcon class="mr-2"><Phone20Regular /></NIcon>
              <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
            </div>
            <div v-if="contact.duty.email" class="flex flex-row items-center">
              <NIcon class="mr-2"><Mail20Regular /> </NIcon
              ><a :href="`mailto:${contact.duty.email}`">{{ contact.duty.email }}</a>
            </div>
          </template>
        </ContactWithPhoto>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";
import {
  NSelect,
  NCascader,
  NInput,
  NInputGroup,
  useMessage,
  NIcon,
  NTabs,
  NTabPane,
  NPopover,
} from "naive-ui";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  contacts: Array,
  institution: Object,
});

const loadingNameInput = ref(false);
const message = useMessage();
</script>

<style>
.list-enter-active {
  transition: all 0.3s ease-out;
}
.list-leave-active {
  transition: all 0.3s cubic-bezier(1, 0.5, 0.8, 1);
}
.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}
</style>
