<template>
  <PageContent :title="`${$page.props.auth.user.name}`">
    <NCard class="subtle-gray-gradient">
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
    </NCard>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NCard } from "naive-ui";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  roles: string[];
}>();
</script>
