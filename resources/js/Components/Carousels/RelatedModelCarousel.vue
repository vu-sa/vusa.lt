<template>
  <!-- <NCarousel :slides-per-view="4" :space-between="20"> -->
  <!-- Outgoing Direct -->
  <div class="flex flex-row gap-2">
    <InstitutionCard
      v-for="relationship in institution.relatedInstitutions.outgoingDirect"
      :key="relationship.pivot.related_model_id"
      :institution="relationship.pivot.related_model"
    >
      <template #header-extra>
        <NIcon :size="24" :component="PersonArrowLeft24Regular"></NIcon>
      </template>
      <!-- <NTag> {{ relationship.name }}</NTag> -->
    </InstitutionCard>
    <!-- Outgoing Type -->
    <template
      v-for="relationship in institution.relatedInstitutions.outgoingByType"
      :key="relationship.id"
    >
      <InstitutionCard
        v-for="typeInstitution in relationship.pivot.related_model.institutions"
        :key="typeInstitution.id"
        :institution="typeInstitution"
      >
        <template #header-extra>
          <NIcon :size="24" :component="PersonArrowLeft24Regular"></NIcon>
        </template>
        <!-- <NTag> {{ relationship.name }}</NTag> -->
      </InstitutionCard>
    </template>
    <!-- Incoming Direct -->

    <InstitutionCard
      v-for="relationship in institution.relatedInstitutions.incomingDirect"
      :key="relationship.pivot.relationshipable_id"
      :institution="relationship.pivot.relationshipable"
      ><template #header-extra>
        <NIcon :size="24" :component="PersonArrowRight24Regular"></NIcon>
      </template>
      <!-- <NTag>{{ relationship.name }}</NTag> -->
    </InstitutionCard>
    <!-- Incoming Type -->
    <template
      v-for="relationship in institution.relatedInstitutions.incomingByType"
      :key="relationship.id"
    >
      <InstitutionCard
        v-for="typeInstitution in relationship.pivot.relationshipable
          .institutions"
        :key="typeInstitution.id"
        :institution="typeInstitution"
      >
        <template #header-extra>
          <NIcon :size="24" :component="PersonArrowLeft24Regular"></NIcon>
        </template>
        <!-- <NTag> {{ relationship.name }}</NTag> -->
      </InstitutionCard>
    </template>
  </div>
  <!-- </NCarousel> -->
</template>

<script setup lang="tsx">
import { NCarousel, NIcon, NTag } from "naive-ui";
import {
  PersonArrowLeft24Regular,
  PersonArrowRight24Regular,
} from "@vicons/fluent";
import InstitutionCard from "../Cards/InstitutionCard.vue";

defineProps<{
  institution: App.Entities.Institution;
}>();
</script>
