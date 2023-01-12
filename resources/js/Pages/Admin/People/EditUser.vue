<template>
  <PageContent :title="user.name" :back-url="route('users.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="user">
      <UserForm
        :user="user"
        :roles="roles"
        :duties="duties"
        model-route="users.update"
        delete-model-route="users.destroy"
      />
    </UpsertModelLayout>
    <template #aside-card>
      <NCard
        v-if="user.duties.length > 0"
        class="subtle-gray-gradient h-fit max-w-sm"
      >
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
      </NCard>
      <p v-else>Šis žmogus neturi pareigų.</p>
    </template>
  </PageContent>
</template>

<script setup lang="tsx">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { NButton, NCard, NIcon } from "naive-ui";
import { PersonEdit24Regular } from "@vicons/fluent";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserForm from "@/Components/AdminForms/UserForm.vue";

defineProps<{
  user: App.Entities.User;
  roles: App.Entities.Role[];
  // TODO: don't return all duties from the controller immediately
  duties: App.Entities.Duty[];
}>();
</script>
