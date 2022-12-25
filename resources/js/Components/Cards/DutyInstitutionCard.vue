<template>
  <NCard
    hoverable
    :segmented="{
      footer: 'soft',
    }"
    as="button"
    style="max-width: 400px"
    class="cursor-pointer shadow-sm"
    @click="Inertia.visit(route('dutyInstitutions.show', institution.id))"
  >
    <template #header>
      <span :class="{ 'font-bold': isPadalinys }">{{ institution.name }}</span>
    </template>
    <template #footer>
      <div class="flex gap-2">
        <NTag
          v-for="type in institution.types"
          :key="type.id"
          size="small"
          :bordered="false"
        >
          {{ type.title }}
        </NTag>
      </div>
    </template>
    <InstitutionAvatarGroup
      v-if="institution.users"
      :users="institution.users"
    />
  </NCard>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { NCard, NTag } from "naive-ui";
import route from "ziggy-js";

import InstitutionAvatarGroup from "@/Components/Avatars/UsersAvatarGroup.vue";

defineProps<{
  institution: App.Models.DutyInstitution;
  isPadalinys?: boolean;
}>();
</script>
