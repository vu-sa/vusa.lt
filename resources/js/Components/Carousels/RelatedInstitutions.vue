<template>
  <!-- Outgoing Direct -->
  <div class="grid grid-cols-ramFill gap-4">
    <InstitutionCard
      v-for="relationship in institution.relatedInstitutions.outgoingDirect"
      :key="relationship.pivot.related_model_id"
      :institution="relationship.pivot.related_model"
      @click="handleClick(relationship.pivot.related_model)"
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
        @click="handleClick(typeInstitution)"
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
      @click="handleClick(relationship.pivot.relationshipable)"
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
        @click="handleClick(typeInstitution)"
      >
        <template #header-extra>
          <NIcon :size="24" :component="PersonArrowLeft24Regular"></NIcon>
        </template>
        <!-- <NTag> {{ relationship.name }}</NTag> -->
      </InstitutionCard>
    </template>
  </div>
</template>

<script setup lang="tsx">
import { NIcon } from "naive-ui";
import {
  PersonArrowLeft24Regular,
  PersonArrowRight24Regular,
} from "@vicons/fluent";
import { router } from "@inertiajs/vue3";
import InstitutionCard from "../Cards/InstitutionCard.vue";

defineProps<{
  institution: App.Entities.Institution;
}>();

const handleClick = (institution: App.Entities.Institution) => {
  router.visit(route("institutions.show", institution.id));
};
</script>
