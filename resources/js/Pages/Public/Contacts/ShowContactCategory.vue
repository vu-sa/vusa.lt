<template>
  <div class="mt-12 flex flex-col gap-4">
    <h1 class="mb-8">
      {{ $t("Kontaktai") }}: {{ $t(type.title) }}
    </h1>
    <template v-for="institution in institutions" :key="institution.id">
      <InstitutionFigure :institution>
        <!-- Mostly used for tenant buttons (they have separate sections for coordinators and mentors) -->
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
      <Separator v-if="institution.id !== institutions[institutions.length - 1].id" />
    </template>
  </div>
</template>

<script setup lang="ts">
import InstitutionFigure from "@/Components/Public/InstitutionFigure.vue";
import SmartLink from "@/Components/Public/SmartLink.vue";
import { Separator } from "@/Components/ui/separator";

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
