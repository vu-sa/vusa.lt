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
      <div v-if="contact.duties.length > 0" class="col-span-3">
        <NDivider></NDivider>
        <strong>Šiuo metu {{ contact.name }} užima šias pareigas:</strong>
        <ul class="list-inside">
          <li
            v-for="duty in contact.duties"
            :key="duty.id"
            class="flex-inline gap-2"
          >
            <Link :href="route('duties.edit', { id: duty.id })"
              >{{ duty.name }}
              {{ duty.email ? ` (${duty.email})` : "" }}

              <NButton
                v-if="hasFillableAttributes(duty)"
                secondary
                circle
                size="tiny"
                @click.prevent="
                  Inertia.visit(
                    route('dutyUsers.edit', { dutyUser: duty.pivot.id })
                  )
                "
              >
                <NIcon>
                  <PersonEdit24Regular />
                </NIcon>
              </NButton>

              <!-- duty.attributes?.study_program
                  ? `(${duty.attributes?.study_program})`
                  : "" -->
            </Link>
          </li>
        </ul>
      </div>
      <p v-else>Šis žmogus neturi pareigų.</p>
    </template>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NDivider, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/Components/Admin/Forms/UserForm.vue";

defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();

const hasFillableAttributes = (duty: App.Models.Duty) => {
  // return true if duty.name includes "kurator"
  return duty.name.toLowerCase().includes("kurator");
};
</script>
