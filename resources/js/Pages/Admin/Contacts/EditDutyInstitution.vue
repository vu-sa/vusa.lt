<template>
  <PageContent
    :title="dutyInstitution.name ?? dutyInstitution.short_name"
    :back-url="route('dutyInstitutions.index')"
  >
    <template #aside-header>
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
      />
    </UpsertModelLayout>

    <template #aside-card>
      <div v-if="duties" class="main-card h-fit">
        <strong>Šiuo metu institucijai priklauso šios pareigos:</strong>
        <ul class="list-inside">
          <li v-for="duty in duties" :key="duty.id">
            <Link :href="route('duties.edit', { id: duty.id })">{{
              duty.name
            }}</Link>
          </li>
        </ul>
      </div>
      <p v-else class="main-card col-span-3 h-fit">
        Ši institucija <strong>neturi</strong> pareigų.
      </p>
    </template>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { ref } from "vue";
import route from "ziggy-js";

import DutyInstitutionForm from "@/Components/Admin/Forms/DutyInstitutionForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import PreviewModelButton from "@/Components/Admin/Buttons/PreviewModelButton.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  duties: Array<App.Models.Duty>;
  padaliniai: Array<App.Models.Padalinys>;
}>();

const dutyInstitution = ref(props.dutyInstitution);

// check if dutyInstitution.en is null, then create it

if (!props.dutyInstitution.attributes) {
  dutyInstitution.value.attributes = {};
}

if (!props.dutyInstitution.attributes.en) {
  dutyInstitution.value.attributes.en = {};
}
</script>
