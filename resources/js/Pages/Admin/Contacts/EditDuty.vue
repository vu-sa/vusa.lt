<template>
  <AdminLayout
    :title="`${duty.name} (${
      duty.institution?.short_name ?? 'Neturi institucijos'
    })`"
    :back-url="route('duties.index')"
  >
    <UpsertModelLayout :errors="$attrs.errors" :model="duty">
      <DutyForm
        :duty="duty"
        :has-users="hasUsers"
        :duty-types="dutyTypes"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
    <template #aside-navigation-options>
      <div v-if="hasUsers" class="col-span-3">
        <NDivider></NDivider>

        <strong>Šiuo metu šias pareigas užima:</strong>
        <ul class="mt-2 list-none">
          <li v-for="user in users" :key="user.id" class="mb-1">
            <Link
              class="flex flex-row items-center gap-2"
              :href="route('users.edit', { id: user.id })"
              ><NAvatar round size="small" :src="user.profile_photo_path" />{{
                user.name
              }}
              <NPopconfirm @positive-click="detachUserFromDuty(user)">
                <template #trigger>
                  <NButton
                    type="error"
                    tertiary
                    size="tiny"
                    circle
                    @click.prevent
                  >
                    <NIcon>
                      <LinkDismiss20Filled />
                    </NIcon>
                  </NButton>
                </template>
                Elementas bus atsietas, tačiau nebus ištrintas. Tęsti?
              </NPopconfirm>
            </Link>
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
import {
  NAvatar,
  NButton,
  NDivider,
  NIcon,
  NPopconfirm,
  createDiscreteApi,
} from "naive-ui";
import { computed } from "vue";
import route from "ziggy-js";

import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  duty: App.Models.Duty;
  users: App.Models.User[];
  dutyTypes: App.Models.DutyType[];
}>();

const { message } = createDiscreteApi(["message"]);

const hasUsers = computed(() => props.users.length > 0);

////////////////////////////////////////////////////////////////////////////////

const detachUserFromDuty = (user: App.Models.User) => {
  Inertia.post(
    route("users.detach", {
      user: user.id,
      duty: props.duty.id,
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
