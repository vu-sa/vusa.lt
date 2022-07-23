<template>
  <AdminLayout :title="contact.name" :back-url="route('users.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm
        :user="contact"
        model-route="users.update"
        delete-model-route="users.destroy"
        :roles="roles"
      />
    </UpsertModelLayout>
    <template #aside-navigation-options>
      <div v-if="contact.duties" class="col-span-3">
        <NDivider></NDivider>
        <strong>Šiuo metu {{ contact.name }} užima šias pareigas:</strong>
        <ul class="list-inside">
          <li v-for="duty in contact.duties" :key="duty.id">
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
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/components/Admin/Forms/UserForm.vue";

defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();
</script>
