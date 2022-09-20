<template>
  <PageContent :title="contact.name" :back-url="route('users.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="contact">
      <UserForm
        :user="contact"
        :roles="roles"
        model-route="users.update"
        delete-model-route="users.destroy"
      />
    </UpsertModelLayout>
    <template #aside-card>
      <div v-if="contact.duties.length > 0" class="main-card h-fit">
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
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import route from "ziggy-js";

import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";
import UserForm from "@/Components/Admin/Forms/UserForm.vue";

defineProps<{
  contact: App.Models.User;
  roles: App.Models.Role[];
}>();
</script>
