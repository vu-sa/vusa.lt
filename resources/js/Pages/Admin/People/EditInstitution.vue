<template>
  <PageContent
    :title="institution.name ?? institution.short_name"
    :back-url="route('institutions.index')"
    :heading-icon="Icons.INSTITUTION"
  >
    <template #after-heading>
      <PreviewModelButton
        public-route="contacts.alias"
        :route-props="{
          alias: institution.alias,
          lang: $page.props.app.locale,
          subdomain: institution.padalinys?.alias ?? 'www',
        }"
      />
    </template>
    <UpsertModelLayout :errors="$page.props.errors" :model="institution">
      <InstitutionForm
        :padaliniai="padaliniai"
        model-route="institutions.update"
        delete-model-route="institutions.destroy"
        :institution="institution"
        :institution-types="institutionTypes"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { ref } from "vue";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import Icons from "@/Types/Icons/regular";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  institution: App.Entities.Institution;
  institutionTypes: Array<App.Entities.Type>;
  padaliniai: Array<App.Entities.Padalinys>;
}>();

const institution = ref(props.institution);

if (!props.institution.extra_attributes) {
  institution.value.extra_attributes = {};
}

institution.value.extra_attributes = checkForEmptyArray(
  institution.value.extra_attributes,
);

if (!props.institution.extra_attributes.en) {
  institution.value.extra_attributes.en = {};
}

institution.value.extra_attributes.en = checkForEmptyArray(
  institution.value.extra_attributes.en,
);

////////////////////////////////////////////////////////////////////////////////
// function to order duties on button press
</script>

<style>
.list-move {
  transition: all 0.5s ease;
}
</style>
