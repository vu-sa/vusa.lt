<template>
  <AppLayout title="Pradinis">
    <div class="main-card">
      <p>{{ salutation }}</p>
      <p><strong>Rolė:</strong> {{ role.name }}</p>
      <p class="my-4">{{ role.description }}</p>
      <p>
        Jeigu kiltų klausimų, rašykite
        <a href="mailto:it@vusa.lt">it@vusa.lt</a>
      </p>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { usePage } from "@inertiajs/inertia-vue3";
import AppLayout from "@/Layouts/AdminLayout.vue";

import { computed } from "vue";

defineProps<{
  role: App.Models.Role;
}>();

const salutation = computed(() => {
  // change name word ending to salutation
  const name = usePage().props.value.user.name;
  return `Sveiki prisijungę, ${name}`;
});
</script>
