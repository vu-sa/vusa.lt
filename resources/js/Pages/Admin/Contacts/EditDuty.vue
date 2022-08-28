<template>
  <PageContent
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
    <template #aside-card>
      <div v-if="hasUsers" class="main-card h-fit w-fit">
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
      <p v-else class="main-card h-fit w-fit">
        Šių pareigų kolkas niekas neužima.
      </p>
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
import { LinkDismiss20Filled } from "@vicons/fluent";
import {
  NAvatar,
  NButton,
  NIcon,
  NPopconfirm,
  createDiscreteApi,
} from "naive-ui";
import { computed, ref } from "vue";
import route from "ziggy-js";

import { checkForEmptyArray } from "@/Composables/checkAttributes";
import DutyForm from "@/Components/Admin/Forms/DutyForm.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  duty: App.Models.Duty;
  users: App.Models.User[];
  dutyTypes: App.Models.DutyType[];
}>();

const { message } = createDiscreteApi(["message"]);

const hasUsers = computed(() => props.users.length > 0);

const duty = ref(props.duty);

duty.value.attributes = checkForEmptyArray(duty.value.attributes);
duty.value.attributes.en = checkForEmptyArray(duty.value.attributes.en);
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
