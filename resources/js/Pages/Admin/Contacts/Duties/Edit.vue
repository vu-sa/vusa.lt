<template>
  <AdminLayout :title="duty.name">
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm
        :duty="duty"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
    <template #aside-navigation-options>
      <div v-if="hasUsers">
        <strong>Šiuo metu šias pareigas užima:</strong>
        <ul class="list-inside">
          <li v-for="user in users" :key="user.id">
            <Link :href="route('users.edit', { id: user.id })">{{
              user.name
            }}</Link>
            <NPopconfirm @positive-click="detachUserFromDuty(user)">
              <template #trigger>
                <span class="ml-2">
                  <NButton text>
                    <NIcon>
                      <LinkDismiss20Filled />
                    </NIcon>
                  </NButton>
                </span>
              </template>
              Elementas bus atsietas, tačiau nebus ištrintas. Tęsti?
            </NPopconfirm>
          </li>
        </ul>
      </div>
      <p v-else>Šių pareigų kolkas niekas neužima.</p>
    </template>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import { LinkDismiss20Filled } from "@vicons/fluent";
import { NButton, NIcon, NPopconfirm, createDiscreteApi } from "naive-ui";
import { computed, reactive } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
import UpsertModelLayout from "@/components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  duty: App.Models.Duty;
  users: App.Models.User[];
}>();

// const duty = reactive(props.duty);
const { message } = createDiscreteApi(["message"]);
const duty = reactive(props.duty);

const hasUsers = computed(() => props.users.length > 0);

////////////////////////////////////////////////////////////////////////////////

const detachUserFromDuty = (user: App.Models.User) => {
  Inertia.post(
    route("users.detach", {
      user: user.id,
      duty: duty.id,
    }),
    {},
    {
      preserveScroll: true,
      only: ["users"],
      onSuccess: () => {
        message.success(`Sėkmingai atsieta nuo pareigos!`);
      },
      onError: () => {
        message.error("Nepavyko atsieti!");
      },
    }
  );
};
</script>
