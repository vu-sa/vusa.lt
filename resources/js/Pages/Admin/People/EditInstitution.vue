<template>
  <PageContent
    :title="institution.name ?? institution.short_name"
    :back-url="route('institutions.index')"
  >
    <template #after-heading>
      <PreviewModelButton
        main-route="contacts.category"
        padalinys-route="contacts.category"
        :main-props="{ alias: institution.alias }"
        :padalinys-props="{ alias: institution.alias }"
        :padalinys-shortname="institution.padalinys?.shortname"
      ></PreviewModelButton>
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

    <template #aside-card>
      <div v-if="duties" class="main-card h-fit max-w-md">
        <strong>Šiuo metu institucijai priklauso šios pareigos:</strong>
        <TransitionGroup name="list" tag="ul" class="list-inside">
          <li v-for="duty in duties" :key="duty.id" class="gap-4">
            <Link :href="route('duties.edit', { id: duty.id })">{{
              duty.name
            }}</Link>
            <div class="ml-2 inline-flex gap-1">
              <NButton text @click="reorderDuties('up', duty)"
                ><NIcon :component="ArrowCircleUp24Regular"
              /></NButton>
              <NButton text @click="reorderDuties('down', duty)"
                ><NIcon :component="ArrowCircleDown24Regular"
              /></NButton>
            </div>
          </li>
        </TransitionGroup>
        <FadeTransition>
          <div v-if="dutiesWereReordered" class="mt-4">
            <NButton @click="saveReorderedDuties">Atnaujinti</NButton>
          </div>
        </FadeTransition>
      </div>
      <p v-else class="main-card col-span-3 h-fit">
        Ši institucija <strong>neturi</strong> pareigų.
      </p>
    </template>
  </PageContent>
</template>

<script setup lang="ts">
import {
  ArrowCircleDown24Regular,
  ArrowCircleUp24Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import route from "ziggy-js";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import InstitutionForm from "@/Components/AdminForms/InstitutionForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  institution: App.Models.Institution;
  institutionTypes: Array<App.Models.Type>;
  duties: Array<App.Models.Duty>;
  padaliniai: Array<App.Models.Padalinys>;
}>();

const institution = ref(props.institution);

if (!props.institution.extra_attributes) {
  institution.value.extra_attributes = {};
}

institution.value.extra_attributes = checkForEmptyArray(
  institution.value.extra_attributes
);

if (!props.institution.extra_attributes.en) {
  institution.value.extra_attributes.en = {};
}

institution.value.extra_attributes.en = checkForEmptyArray(
  institution.value.extra_attributes.en
);

////////////////////////////////////////////////////////////////////////////////
// function to order duties on button press
const duties = ref(props.duties);
const dutiesWereReordered = ref(false);

// this function only reorders the array, but does not change the order value of the duties
// the order value is assigned in the backend, using the indexes of the array, which is the one manipulated here
const reorderDuties = (direction: "up" | "down", duty: App.Models.Duty) => {
  const index = duties.value.indexOf(duty);
  if (index === -1) {
    return;
  }
  const newIndex = direction === "up" ? index - 1 : index + 1;
  if (newIndex < 0 || newIndex >= duties.value.length) {
    return;
  }

  const newDuties = [...duties.value];
  const temp = newDuties[index];
  newDuties[index] = newDuties[newIndex];
  newDuties[newIndex] = temp;
  duties.value = newDuties;

  dutiesWereReordered.value = true;
};

const saveReorderedDuties = () => {
  const newDuties = duties.value.map((duty, index) => {
    duty.order = index;
    return duty;
  });
  Inertia.post(
    route("institutions.reorderDuties"),
    {
      duties: newDuties,
    },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: () => {
        dutiesWereReordered.value = false;
      },
    }
  );
};
</script>

<style>
.list-move {
  transition: all 0.5s ease;
}
</style>
