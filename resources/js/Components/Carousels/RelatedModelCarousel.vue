<template>
  <div>
    <template v-if="institution.givenRelationships.length > 0">
      <h3>Suteikti ryšiai</h3>
      <div class="my-4 flex max-w-4xl flex-wrap gap-4">
        <template
          v-for="relationship in institution.givenRelationships"
          :key="relationship.pivot.related_model_id"
        >
          <InstitutionCard :institution="relationship.pivot.related_model">
            <template #header-extra> Suteiktas </template>
          </InstitutionCard>
        </template>
      </div>
    </template>
    <template v-if="institution.receivedRelationships.length > 0">
      <h3>Įgyti ryšiai</h3>
      <div class="my-4 flex max-w-4xl flex-wrap gap-4">
        <template
          v-for="relationship in institution.receivedRelationships"
          :key="relationship.pivot.related_model_id"
        >
          <InstitutionCard :institution="relationship.pivot.related_model"
            ><template #header-extra> Įgytas </template></InstitutionCard
          >
        </template>
      </div>
    </template>
  </div>
</template>

<script setup lang="tsx">
import InstitutionCard from "../Cards/InstitutionCard.vue";

defineProps<{
  institution: App.Entities.Institution;
}>();
</script>
