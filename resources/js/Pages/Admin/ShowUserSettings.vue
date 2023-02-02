<template>
  <PageContent :title="`${$page.props.auth?.user?.name}`">
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
              <ul class="ml-4 list-inside">
                <li v-for="permission in role.permissions" :key="permission.id">
                  {{ $t(permission.name) }}
                </li>
              </ul>
            </li>
          </template>
        </ul>
      </div>
      <p>
        Jeigu kiltų klausimų, rašykite
        <a href="mailto:it@vusa.lt">it@vusa.lt</a>
      </p>
      <a
        class="mt-4 flex items-center"
        target="_blank"
        href="https://github.com/vu-sa/vusa.lt/pull/122"
        ><NButton text
          ><template #icon><NIcon :component="Github"></NIcon></template
          >Projekto puslapis</NButton
        ></a
      >
    </NCard>
  </PageContent>
</template>

<script setup lang="tsx">
import { trans as $t } from "laravel-vue-i18n";
import { NButton, NCard, NIcon } from "naive-ui";

import { Github } from "@vicons/fa";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

defineProps<{
  user: App.Entities.User;
}>();
</script>
