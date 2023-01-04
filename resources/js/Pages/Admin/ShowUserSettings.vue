<template>
  <PageContent :title="`${$page.props.auth.user.name}`">
    <div class="main-card max-w-3xl">
      <!-- <p>{{ salutation }}</p> -->
      <div class="mb-4">
        <p>Tavo rolės:</p>
        <ul v-for="(role, index) in roles" :key="index" class="list-inside">
          <li class="font-bold">{{ $t(role) }}</li>
        </ul>
      </div>
      <p>
        Jeigu kiltų klausimų, rašykite
        <a href="mailto:it@vusa.lt">it@vusa.lt</a>
      </p>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineProps<{
  roles: Record<string, any>[];
}>();

// const salutation = computed(() => {
//   // change name word ending to salutation
//   const name = usePage().props.value.user.name;
//   return `Sveiki prisijungę, ${name}`;
// });
</script>
