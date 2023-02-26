<template>
  <PageContent :title="`${$page.props.auth?.user?.name}`">
    <NCard class="subtle-gray-gradient">
      <!-- <p>{{ salutation }}</p> -->
      <div class="mb-4">
        <p>{{ $t("Tavo rolės") }}:</p>
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
    </NCard>
    <p class="mt-4 flex items-center justify-center gap-2">
      <a
        target="_blank"
        href="https://github.com/vu-sa/vusa.lt/blob/main/CHANGELOG.md"
        ><NButton text size="small" quaternary> v0.4.8 </NButton></a
      >·
      <a
        class="inline-flex items-center"
        target="_blank"
        href="https://github.com/vu-sa/vusa.lt/pull/122"
        ><NButton text
          ><template #icon><NIcon :component="Github"></NIcon></template
          >{{ $t("Projekto puslapis") }}</NButton
        ></a
      >
    </p>
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
