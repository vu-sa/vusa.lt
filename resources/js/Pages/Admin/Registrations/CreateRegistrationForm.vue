<template>
  <AdminLayout title="Nauja registracijos forma">
    <UpsertModelLayout :errors="$attrs.errors" :model="registrationForm">
      <RegistrationFormForm
        :banner="registrationForm"
        model-route="registrationForms.store"
      />
    </UpsertModelLayout>
  </AdminLayout>
</template>

<script setup lang="ts">
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import RegistrationFormForm from "@/Components/Admin/Forms/RegistrationFormForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const registrationForm = {
  data: {},
};
</script>
