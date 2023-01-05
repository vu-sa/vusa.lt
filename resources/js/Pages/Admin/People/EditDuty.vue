<template>
  <PageContent
    :title="`${duty.name} (${
      duty.institution?.short_name ?? 'Neturi institucijos'
    })`"
    :back-url="route('duties.index')"
  >
    <UpsertModelLayout :errors="$page.props.errors" :model="duty">
      <DutyForm
        :duty="duty"
        :has-users="hasUsers"
        :duty-types="dutyTypes"
        :institutions="institutions"
        model-route="duties.update"
        delete-model-route="duties.destroy"
      />
    </UpsertModelLayout>
    <template #aside-card>
      <div v-if="hasUsers" class="main-card h-fit w-fit max-w-lg">
        <strong>Šiuo metu šias pareigas užima:</strong>
        <ul class="mt-2 list-none">
          <li v-for="user in users" :key="user.id" class="mb-1">
            <Link
              class="flex flex-row items-center gap-2"
              :href="route('users.edit', { id: user.id })"
              ><NAvatar
                object-fit="cover"
                round
                size="small"
                :src="user.profile_photo_path"
              />{{ user.name }}
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
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";
import DutyForm from "@/Components/AdminForms/DutyForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineOptions({
  layout: AdminLayout,
});

const props = defineProps<{
  duty: App.Models.Duty;
  users: App.Models.User[];
  dutyTypes: App.Models.Type[];
  institutions: App.Models.Institution[];
}>();

const { message } = createDiscreteApi(["message"]);

const hasUsers = computed(() => props.users.length > 0);

const duty = ref(props.duty);

duty.value.extra_attributes = checkForEmptyArray(duty.value.extra_attributes);
duty.value.extra_attributes.en = checkForEmptyArray(
  duty.value.extra_attributes.en
);

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
      onError: () => {
        message.error("Nepavyko atsieti!");
      },
    }
  );
};
</script>
