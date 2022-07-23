<template>
  <h2>Ieškoti kontakto:</h2>
  <PublicLayout title="Kontaktų paieška">
    <div class="grid gap-8 px-16 md:grid-cols-4 lg:px-32">
      <NInput
        class="mt-2 md:col-span-4"
        type="text"
        size="large"
        round
        placeholder="Ieškoti pagal vardą..."
        :loading="loadingNameInput"
        @input="handleNameInput"
      />

      <TransitionGroup name="list">
        <ContactWithPhoto
          v-for="contact in searchContacts"
          :key="contact.id"
          :class="{ 'md:col-span-2': contact.profile_photo_path }"
          :image-src="contact.profile_photo_path"
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
      </TransitionGroup>
    </div>
  </PublicLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Mail20Regular, Phone20Regular } from "@vicons/fluent";
import { NIcon, NInput, createDiscreteApi } from "naive-ui";
import { debounce } from "lodash";
import { ref } from "vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

interface contactUserInterface
  extends Array<
    Pick<
      App.Models.User,
      "id" | "name" | "email" | "phone" | "profile_photo_path" | "duties"
    >
  > {
  // TODO: type only returns strings of institution and type. Need to fix interface
  duties: Array<
    Pick<
      App.Models.Duty,
      "id" | "name" | "email" | "description" | "institution" | "type"
    >
  >;
}

const props = defineProps<{
  searchContacts: contactUserInterface;
}>();

const searchContacts = ref(props.searchContacts);

const loadingNameInput = ref(false);
const { message } = createDiscreteApi(["message"]);

// handleNameInput with half second delay and then update contacts with inertia request
const handleNameInput = debounce((input: string) => {
  const name = input;
  if (name.length > 2) {
    loadingNameInput.value = true;
    Inertia.reload({
      only: ["searchContacts"],
      data: { name: name },
      onSuccess: () => {
        loadingNameInput.value = false;
        searchContacts.value = props.searchContacts;

        if (searchContacts.value.length === 0) {
          message.info("Nerasta kontaktų su tokiu vardu.");
        }
      },
    });
  }
}, 500);
</script>

<style>
.list-move,
.list-enter-active,
.list-leave-active {
  transition: opacity 0.5s ease-out;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateX(30px);
}

/* .list-leave-active {
  position: absolute;
} */
</style>
