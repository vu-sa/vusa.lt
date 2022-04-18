<template>
  <PublicLayout>
    <div class="px-16 lg:px-32">
      <NTabs default-value="search" type="line" animated>
        <NTabPane class="grid grid-cols-3 gap-4" name="padalinys" tab="VU SA">
          <ContactWithPhoto v-for="contact in alias_contacts" :key="contact.id">
            <template #image
              ><img
                loading="lazy"
                v-if="contact.image"
                :src="contact.image"
                class="rounded-sm shadow-md hover:shadow-lg duration-200 w-full mb-1 object-cover col-span-2"
            /></template>
            <template #name> {{ contact.name }} </template>
            <template #duty>
              <ul v-for="duty in contact.duties" :key="duty.id">
                <li>{{ duty.name }}</li>
              </ul>
            </template>
            <template #description> Aprašymas </template>
            <template v-if="contact.phone" #phone>
              <div class="flex flex-row items-center">
                <NIcon class="mr-2"><Phone20Regular /></NIcon>
                <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
              </div>
            </template>
            <template v-if="contact.email" #email>
              <div class="flex flex-row items-center">
                <NIcon class="mr-2"><Mail20Regular /> </NIcon
                ><a :href="`mailto:${contact.email}`">{{ contact.email }}</a>
              </div>
            </template>
          </ContactWithPhoto>
        </NTabPane>
        <NTabPane
          class="grid grid-cols-2 md:grid-cols-3 gap-6"
          name="search"
          tab="Ieškoti kontakto..."
          style="padding: 1em 0 2em 0"
        >
          <!-- <h2>Ieškoti kontakto:</h2> -->
          <NInput
            class="col-span-3 mt-2"
            type="text"
            size="large"
            round
            placeholder="Ieškoti pagal vardą..."
            @input="handleNameInput"
            :loading="loadingNameInput"
          />
          <NInputGroup class="col-span-3 mb-4">
            <NCascader
              size="small"
              style="border-radius: 6em"
              placeholder="Pasirinkti VU SA padalinį, PKP..."
            ></NCascader>
            <NSelect
              size="small"
              style="border-radius: 6em"
              placeholder="Pasirinkti kontakto tipą..."
            ></NSelect>
            <NSelect
              size="small"
              style="border-radius: 6em"
              placeholder="Pasirinkti organą..."
            ></NSelect>
          </NInputGroup>
          <transition-group name="list">
            <ContactWithPhoto
              v-for="contact in search_contacts"
              :key="contact.id"
              class="col-span-2"
            >
              <template #image
                ><img
                  loading="lazy"
                  v-if="contact.image"
                  :src="contact.image"
                  class="rounded-sm shadow-md hover:shadow-lg duration-200 w-full mb-1 object-cover"
              /></template>
              <template #name> {{ contact.name }} </template>
              <template #duty>
                <ul v-for="duty in contact.duties" :key="duty.id">
                  <li>{{ duty.name }}</li>
                </ul>
              </template>
              <template #description> Aprašymas </template>
              <template v-if="contact.phone" #phone>
                <div class="flex flex-row items-center">
                  <NIcon class="mr-2"><Phone20Regular /></NIcon>
                  <a :href="`tel:${contact.phone}`">{{ contact.phone }}</a>
                </div>
              </template>
              <template v-if="contact.email" #email>
                <div class="flex flex-row items-center">
                  <NIcon class="mr-2"><Mail20Regular /> </NIcon
                  ><a :href="`mailto:${contact.email}`">{{ contact.email }}</a>
                </div>
              </template>
            </ContactWithPhoto>
          </transition-group>
        </NTabPane>
      </NTabs>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import {
  NSelect,
  NCascader,
  NInput,
  NInputGroup,
  useMessage,
  NIcon,
  NTabs,
  NTabPane,
} from "naive-ui";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { ref } from "vue";
import { Inertia } from "@inertiajs/inertia";

const props = defineProps({
  alias_contacts: Array,
  search_contacts: Array,
});

const search_contacts = ref([]);
search_contacts.value = props.search_contacts;

const loadingNameInput = ref(false);
const message = useMessage();

// handleNameInput with half second delay and then update contacts with inertia request
const handleNameInput = _.debounce((input) => {
  const name = input;
  if (name.length > 2) {
    loadingNameInput.value = true;
    Inertia.reload({
      only: ["search_contacts"],
      data: { name: name },
      onSuccess: () => {
        loadingNameInput.value = false;
        search_contacts.value = props.search_contacts;

        if (search_contacts.value.length === 0) {
          message.info("Nerasta kontaktų su tokiu vardu.");
        }
      },
    });
  }
}, 500);
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
