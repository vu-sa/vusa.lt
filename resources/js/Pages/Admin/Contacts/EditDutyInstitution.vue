<template>
  <AdminLayout
    :title="dutyInstitution.name"
    :back-url="route('dutyInstitution.index')"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="dutyInstitution">
      <DutyInstitutionForm
        :padaliniai="padaliniai"
        model-route="dutyInstitutions.update"
        delete-model-route="dutyInstitutions.destroy"
        :duty-institution="dutyInstitution"
      />
    </UpsertModelLayout>

    <template #aside-navigation-options>
      <div v-if="duties">
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
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import DutyInstitutionForm from "@/Components/Admin/Forms/DutyInstitutionForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

defineProps<{
  dutyInstitution: App.Models.DutyInstitution;
  duties: Array<App.Models.Duty>;
  padaliniai: Array<App.Models.Padalinys>;
}>();
</script>
