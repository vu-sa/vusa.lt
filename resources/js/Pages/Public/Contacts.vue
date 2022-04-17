<template>
  <PublicLayout>
    <div class="px-16 lg:px-32">
      <NTabs type="line" animated>
        <NTabPane name="padalinys" tab="VU SA"></NTabPane>
        <NTabPane
          class="grid grid-cols-2 md:grid-cols-3 gap-8"
          name="search"
          tab="Ieškoti kontakto..."
          style="padding: 1em 0 2em 0"
        >
          <!-- <h2>Ieškoti kontakto:</h2> -->
          <NInput
            class="col-span-3"
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
              v-for="contact in contacts"
              :key="contact.id"
              class="text-neutral-50 p-6 shadow-lg rounded-lg grid-cols-2 col-span-2 gap-8 grid"
            >
              <template #image></template>
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
  contacts: Array,
});

const contacts = ref([]);
contacts.value = props.contacts;

const loadingNameInput = ref(false);
const message = useMessage();

// handleNameInput with half second delay and then update contacts with inertia request
const handleNameInput = _.debounce((input) => {
  const name = input;
  if (name.length > 2) {
    loadingNameInput.value = true;
    Inertia.reload({
      only: ["contacts"],
      data: { name: name },
      onSuccess: () => {
        loadingNameInput.value = false;
        contacts.value = props.contacts;

        if (contacts.value.length === 0) {
          message.info("Nerasta kontaktų su tokiu vardu.");
        }
      },
    });
  } else {
    // contacts.value = props.contacts;
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
