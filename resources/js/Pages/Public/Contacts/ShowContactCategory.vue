<template>
  <div class="mt-12 flex flex-col gap-4">
    <AdAstraBanner />
    <h1 class="mb-8">
      {{ $t("Kontaktai") }}: {{ $t(type.title) }}
    </h1>
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution>
        <template #more>
          <div v-if="institution.alias === institution.tenant?.alias" class="mt-3 flex flex-wrap gap-2">
            <NButtonGroup rounded size="small">
              <NButton v-for="section in padaliniaiSections" :key="section.alias" :tag="SmartLink" :href="route('contacts.alias', {
                institution: section.alias,
                subdomain:
                  institution.alias === 'vusa' ? 'www' : institution.alias,
                lang: $page.props.app.locale,
              })">
                {{ $t(section.title) }}
              </NButton>
            </NButtonGroup>
          </div>
        </template>
      </InstitutionFigure>
      <NDivider v-if="institution.id !== institutions[institutions.length - 1].id" />
    </template>
  </div>
</template>

<script setup lang="ts">
import { NButton, NDivider } from "naive-ui";

import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import AdAstraBanner from "@/Components/Temp/AdAstraBanner.vue";

defineProps<{
  institutions: App.Entities.Institution[];
  type: App.Entities.Type;
}>();

const padaliniaiSections = [
  {
    title: "Koordinatoriai",
    alias: "koordinatoriai",
  },
  {
    title: "Kuratoriai",
    alias: "kuratoriai",
  },
  {
    title: "Studentų atstovai",
    alias: "studentu-atstovai",
  },
];

</script>
