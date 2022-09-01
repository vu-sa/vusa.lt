<template>
  <PageContent title="Pradinis">
    <Transition name="fade" appear>
      <div class="main-card max-w-3xl">
        <p>{{ salutation }}</p>
        <p><strong>Rolė:</strong> {{ role.name }}</p>
        <p class="my-4">{{ role.description }}</p>
        <p>
          Jeigu kiltų klausimų, rašykite
          <a href="mailto:it@vusa.lt">it@vusa.lt</a>
        </p>
      </div>
    </Transition>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { Head, usePage } from "@inertiajs/inertia-vue3";
import { computed } from "vue";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineProps<{
  role: App.Models.Role;
}>();

const salutation = computed(() => {
  // change name word ending to salutation
  const name = usePage().props.value.user.name;
  return `Sveiki prisijungę, ${name}`;
});
</script>
