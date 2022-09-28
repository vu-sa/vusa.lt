<template>
  <PageContent title="Pradinis">
    <div class="main-card max-w-3xl">
      <p>{{ salutation }}</p>
      <div class="my-4">
        <p>Tavo rolės:</p>
        <ul v-for="(role, index) in roles" :key="index" class="list-inside">
          <li>{{ $t(role) }}</li>
        </ul>
      </div>
      <p>
        Jeigu kiltų klausimų, rašykite
        <a href="mailto:it@vusa.lt">it@vusa.lt</a>
      </p>
    </div>
    <h2>Tavo institucijos</h2>
    <div class="flex gap-2">
      <NButton
        v-for="institution in dutyInstitutions"
        :key="institution.id"
        :tag="Link"
        :href="route('dutyInstitutions.show', institution.id)"
        round
        size="large"
        tertiary
        class="w-56 rounded-lg p-2"
      >
        {{ institution.name }}
      </NButton>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NButton } from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";

defineProps<{
  roles: Record<string, any>[];
  dutyInstitutions: Record<string, any>[];
}>();

const salutation = computed(() => {
  // change name word ending to salutation
  const name = usePage().props.value.user.name;
  return `Sveiki prisijungę, ${name}`;
});
</script>
