<template>
  <h2>Ieškoti kontakto:</h2>
  <PublicLayout>
    <div class="grid md:grid-cols-4 gap-8 px-16 lg:px-32">
      <NInput
        class="md:col-span-4 mt-2"
        type="text"
        size="large"
        round
        placeholder="Ieškoti pagal vardą..."
        :loading="loadingNameInput"
        @input="handleNameInput"
      />
      <!-- <NInputGroup class="md:col-span-3 mb-4">
		            <NCascader
		              size="small"
		              style="border-radius: 6em"
		              placeholder="Pasirinkti VU SA padalinį, PKP..."
		              v-model:value="selectedPadalinys"
		            ></NCascader>
		            <NSelect
		              size="small"
		              style="border-radius: 6em"
		              placeholder="Pasirinkti kontakto tipą..."
		              v-model:value="selectedContactType"
		            ></NSelect>
		            <NSelect
		              size="small"
		              style="border-radius: 6em"
		              placeholder="Pasirinkti organą..."
		              v-model:value="selectedDutyInstitution"
		            ></NSelect>
		          </NInputGroup> -->
      <transition-group name="list">
        <ContactWithPhoto
          v-for="contact in search_contacts"
          :key="contact.id"
          :class="{ 'md:col-span-2': contact.image }"
          :image-src="contact.image"
        >
          <template #name> {{ contact.name }} </template>
          <template #duty>
            <ul class="list-inside">
              <li v-for="duty in contact.duties" :key="duty.id">
                {{ duty.name }}
              </li>
            </ul>
          </template>
          <!-- <template #description> Aprašymas </template> -->
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
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import {
  NCascader,
  NIcon,
  NInput,
  NInputGroup,
  NSelect,
  NTabPane,
  NTabs,
  createDiscreteApi,
} from "naive-ui";
import { ref } from "vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";
import ShapeDivider1 from "@/Components/Public/ShapeDivider1.vue";

const props = defineProps({
  search_contacts: Array,
});

const search_contacts = ref([]);
search_contacts.value = props.search_contacts;

const loadingNameInput = ref(false);
const { message } = createDiscreteApi(["message"]);

// handleNameInput with half second delay and then update contacts with inertia request
const handleNameInput = debounce((input) => {
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
