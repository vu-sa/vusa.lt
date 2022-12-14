<template>
  <PageContent
    :title="dutyInstitution.name ?? dutyInstitution.short_name"
    :back-url="route('dutyInstitutions.index')"
  >
    <template #after-heading>
      <PreviewModelButton
        main-route="contacts.category"
        padalinys-route="contacts.category"
        :main-props="{ alias: dutyInstitution.alias }"
        :padalinys-props="{ alias: dutyInstitution.alias }"
        :padalinys-shortname="dutyInstitution.padalinys?.shortname"
      ></PreviewModelButton>
    </template>
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyInstitution">
      <DutyInstitutionForm
        :padaliniai="padaliniai"
        model-route="dutyInstitutions.update"
        delete-model-route="dutyInstitutions.destroy"
        :duty-institution="dutyInstitution"
        :duty-institution-types="dutyInstitutionTypes"
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

<script lang="ts">
import { Inertia } from "@inertiajs/inertia";
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import {
  ArrowCircleDown24Regular,
  ArrowCircleUp24Regular,
} from "@vicons/fluent";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import DutyInstitutionForm from "@/Components/AdminForms/DutyInstitutionForm.vue";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import PreviewModelButton from "@/Components/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  dutyInstitutionTypes: Array<App.Models.DutyInstitutionType>;
  duties: Array<App.Models.Duty>;
  padaliniai: Array<App.Models.Padalinys>;
}>();

const dutyInstitution = ref(props.dutyInstitution);

if (!props.dutyInstitution.attributes) {
  dutyInstitution.value.attributes = {};
}

dutyInstitution.value.attributes = checkForEmptyArray(
  dutyInstitution.value.attributes
);

if (!props.dutyInstitution.attributes.en) {
  dutyInstitution.value.attributes.en = {};
}

dutyInstitution.value.attributes.en = checkForEmptyArray(
  dutyInstitution.value.attributes.en
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
    route("dutyInstitutions.reorderDuties"),
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
