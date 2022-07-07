<template>
  <PublicLayout
    :title="`${institution.short_name ?? institution.name} kontaktai`"
  >
    <div class="px-16 lg:px-32">
      <div class="grid sm:grid-cols-2 xl:grid-cols-3 gap-8">
        <div v-if="institution.image_url" class="relative group sm:col-span-2">
          <ShapeDivider1 class="absolute -top-1 z-10"></ShapeDivider1>
          <ShapeDivider1
            class="absolute rotate-180 -bottom-1 z-10"
          ></ShapeDivider1>
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
          :image-src="contact.image"
        >
          <template #name> {{ contact.name }} </template>
          <template #duty>
            <template v-for="duty in contact.duties">
              <NPopover
                v-if="duty.description"
                trigger="hover"
                :style="{ maxWidth: '250px' }"
                ><template #trigger>
                  <p class="cursor-pointer my-1">{{ duty.name }}</p>
                </template>
                <span v-html="duty.description"></span>
              </NPopover>
              <p v-else class="my-1">{{ duty.name }}</p>
            </template>
          </template>
          <template #contactInfo>
            <div v-if="contact.phone" class="flex flex-row items-center">
              <NIcon class="mr-2">
                <Phone20Regular />
              </NIcon>
              <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
            </div>
            <template v-for="duty in contact.duties">
              <div v-if="duty.email" class="flex flex-row items-center">
                <NIcon class="mr-2"> <Mail20Regular /> </NIcon
                ><a :href="`mailto:${duty.email}`">{{ duty.email }}</a>
              </div>
            </template>
          </template>
        </ContactWithPhoto>
      </div>
    </div>
  </PublicLayout>
</template>

<script setup>
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NIcon, NPopover } from "naive-ui";
import { ref } from "vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps({
  contacts: Array,
  institution: Object,
});

const loadingNameInput = ref(false);
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
