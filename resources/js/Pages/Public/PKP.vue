<template>
  <AdAstraBanner />
  <h1 class="my-8">
    {{ $t('Programos, klubai ir projektai') }}
  </h1>
  <p class="text-lg">
    {{
      $page.props.app.locale === "lt"
        ? "Jei šiame sąraše nerandi to, ko ieškai, gali iniciatyvą įkurti pats!"
        : "Can't see something that interests you? You can create an initiative yourself!"
    }}
  </p>
  <a class="my-4" :href="$page.props.app.locale === 'lt'
    ? 'nauju-stud-org-ikurimas'
    : 'procedure-for-the-establishmen'
    ">
    <NButton type="primary" size="large" class="max-w-fit">
      {{ $t("Sužinok kaip tai padaryti!") }}
    </NButton>
  </a>
  <div class="grid max-w-7xl grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-3">
    <StaggeredTransitionGroup appear>
      <NewInstitutionCard v-for="(institution, index) in institutions" :key="institution.id" :institution :href="route('contacts.alias', {
        institution: institution.alias,
        subdomain:
          institution.tenant?.alias === 'vusa'
            ? 'www'
            : institution.tenant?.alias ?? 'www',
        lang: $page.props.app.locale,
      })" :data-index="index" />
    </StaggeredTransitionGroup>
  </div>
</template>

<script setup lang="ts">
import NewInstitutionCard from "@/Components/Cards/NewInstitutionCard.vue";
import AdAstraBanner from "@/Components/Temp/AdAstraBanner.vue";
import StaggeredTransitionGroup from "@/Components/Transitions/StaggeredTransitionGroup.vue";

defineProps<{
  institutions: Array<App.Entities.Institution>;
}>();

</script>
