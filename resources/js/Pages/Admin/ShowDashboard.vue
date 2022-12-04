<template>
  <PageContent title="Pradinis">
    <div class="mb-4 max-w-3xl">
      <h2 class="inline-flex items-center gap-2">
        <NIcon size="16" :component="ArrowForwardDownLightning20Regular"></NIcon
        ><span>Greitieji veiksmai</span>
      </h2>
      <div class="flex gap-4">
        <QuickActionButton
          :icon="PeopleTeamAdd24Filled"
          text="Pranešti apie artėjantį posėdį"
        />
        <QuickActionButton
          :icon="DocumentAdd24Filled"
          text="Įkelti posėdžio protokolą"
        />
      </div>
    </div>
    <h2>Tavo institucijos</h2>
    <div class="flex gap-2">
      <NButton
        v-for="institution in dutyInstitutions"
        :key="institution.id"
        :tag="Link"
        :href="route('dutyInstitutions.show', institution.id)"
        round
        size="large"
        tertiary
        class="w-56 rounded-lg p-2"
      >
        {{ institution.name }}
      </NButton>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import {
  ArrowForwardDownLightning20Regular,
  DocumentAdd24Filled,
  PeopleTeamAdd24Filled,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import route from "ziggy-js";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import QuickActionButton from "@/Components/Admin/Buttons/QuickActionButton.vue";

defineProps<{
  dutyInstitutions: Record<string, any>[];
}>();
</script>
