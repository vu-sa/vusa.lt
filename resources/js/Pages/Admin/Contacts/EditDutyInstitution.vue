<template>
  <AdminLayout
    :title="dutyInstitution.name ?? dutyInstitution.short_name"
    :back-url="route('dutyInstitutions.index')"
  >
    <template #header>
      {{ dutyInstitution.name ?? dutyInstitution.short_name }}
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

    <template #aside-navigation-options>
      <div v-if="duties" class="col-span-3">
        <NDivider></NDivider>
        <strong>Šiuo metu institucijai priklauso šios pareigos:</strong>
        <ul class="list-inside">
          <li v-for="duty in duties" :key="duty.id">
            <Link :href="route('duties.edit', { id: duty.id })">{{
              duty.name
            }}</Link>
          </li>
        </ul>
      </div>
      <p v-else>Ši institucija <strong>neturi</strong> pareigų.</p>
    </template>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Link } from "@inertiajs/inertia-vue3";
import { NDivider } from "naive-ui";
import route from "ziggy-js";

import AdminLayout from "@/components/Admin/Layouts/AdminLayout.vue";
import DutyInstitutionForm from "@/Components/Admin/Forms/DutyInstitutionForm.vue";
import PreviewModelButton from "@/components/Admin/Buttons/PreviewModelButton.vue";

import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  duties: Array<App.Models.Duty>;
  padaliniai: Array<App.Models.Padalinys>;
}>();
</script>
