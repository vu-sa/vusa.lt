<template>
  <PageContent :title="`${$page.props.auth.user?.name}`">
    <NCard class="subtle-gray-gradient">
      <!-- <p>{{ salutation }}</p> -->
      <div class="mb-4">
        <p>Tavo rolės:</p>
        <ul class="list-inside">
          <li v-for="(role, index) in user.roles" :key="role.id">
            <strong>{{ $t(role.name) }}</strong>
          </li>
          <template v-for="duty in user.duties">
            <li v-for="role in duty.roles" :key="role.id">
              <strong>{{ $t(role.name) }}</strong> ({{
                `iš pareigybės „${duty.name}“, kuri yra iš ${
                  duty.institution?.padalinys?.shortname ?? "nežinomo padalinio"
                }`
              }})
            </li>
          </template>
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
  user: App.Models.User;
}>();
</script>
