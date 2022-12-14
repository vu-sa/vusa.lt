<template>
  <Head title="Kontaktų paieška"></Head>
  <!-- <PublicLayout title="Kontaktų paieška"> -->
  <FadeTransition appear>
    <div class="mt-16 grid gap-8 px-16 md:grid-cols-4 lg:px-32">
      <h2 class="text-gray-900 dark:invert">
        Kontaktų paieška šiuo metu neveikia.
      </h2>

      <!-- <NInput
        class="mt-2 md:col-span-4"
        type="text"
        size="large"
        round
        placeholder="Ieškoti pagal vardą..."
        :loading="loadingNameInput"
        @input="handleNameInput"
      />
      <TransitionGroup name="list">
        <ContactWithPhotoForUsers
          v-for="contact in searchContacts"
          :key="contact.id"
          :contact="contact"
          :class="{ 'md:col-span-2': contact.profile_photo_path }"
        >
        </ContactWithPhotoForUsers>
      </TransitionGroup> -->
    </div>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NInput, createDiscreteApi } from "naive-ui";
import { debounce } from "lodash";
import { ref } from "vue";

import ContactWithPhotoForUsers from "@/Components/Public/ContactWithPhotoForUsers.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

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
