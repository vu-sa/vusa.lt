<template>
  <PageContent :title="user.name" :back-url="route('users.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="user">
      <UserForm
        :user="user"
        :roles="roles"
        :duties="duties"
        model-route="users.update"
        delete-model-route="users.destroy"
      />
    </UpsertModelLayout>
    <template #aside-card>
      <div v-if="user.duties.length > 0" class="main-card h-fit max-w-sm">
        <strong>Šiuo metu {{ user.name }} užima šias pareigas:</strong>
        <ul class="list-inside">
          <li
            v-for="duty in user.duties"
            :key="duty.id"
            class="flex-inline gap-2"
          >
            <Link :href="route('duties.edit', { id: duty.id })"
              >{{ duty.name }}
              {{
                `(nuo ${duty.pivot.start_date} iki ${
                  duty.pivot.end_date ?? "dabar"
                })`
              }}
              {{ duty.email ? ` (${duty.email})` : "" }}

              <NButton
                secondary
                circle
                size="tiny"
                @click.prevent="
                  Inertia.visit(
                    route('dutiables.edit', { dutiable: duty.pivot.id })
                  )
                "
              >
                <NIcon>
                  <PersonEdit24Regular />
                </NIcon>
              </NButton>
            </Link>
          </li>
        </ul>
      </div>
      <p v-else>Šis žmogus neturi pareigų.</p>
    </template>
  </PageContent>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";
import route from "ziggy-js";

import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineOptions({
  layout: AdminLayout,
});

defineProps<{
  user: App.Models.User;
  roles: App.Models.Role[];
  // TODO: don't return all duties from the controller immediately
  duties: App.Models.Duty[];
}>();
</script>
