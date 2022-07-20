<template>
  <AdminLayout :title="duty.name">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-4 grid-flow-row-dense"
      >
        <div class="lg:col-span-2">
          <label class="font-bold">Pavadinimas</label>
          <n-input
            v-model:value="duty.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <!-- <div class="lg:col-span-2">
          <label class="font-bold text-red-700">Title in English</label>
          <n-input
            v-model:value="duty.attributes.en.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div> -->

        <div class="lg:col-span-2">
          <label class="font-bold">El. paštas</label>
          <n-input
            v-model:value="duty.email"
            type="text"
            placeholder="Įrašyti el. paštą..."
          />
        </div>

        <div class="col-span-4">
          <label class="font-bold">Aprašymas</label>
          <TipTap
            v-model="duty.description"
            :search-files="$page.props.search.other"
          />
        </div>

        <!-- <div class="col-span-4">
          <label class="font-bold text-red-700">Description in English</label>
          <TipTap
            v-model="duty.attributes.en.description"
            :searchFiles="$page.props.search.other"
          />
        </div> -->

        <div class="lg:col-span-4">
          <label class="font-bold">Institucija</label>
          <n-select
            v-model:value="duty.institution.id"
            filterable
            placeholder="Pasirinkti instituciją..."
            :options="institutions"
            clearable
            remote
            :clear-filter-after-select="false"
            @search="getInstitutionOptions"
          />
        </div>

        <div class="col-span-full flex justify-end items-center">
          <DeleteModelButton :model="duty" model-route="duties.destroy" />
          <UpsertModelButton :model="duty" model-route="duties.update" />
        </div>
      </form>
    </div>
    <template #aside-navigation-options>
      <div v-if="users">
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
      <p v-else>Šių pareigų <strong>niekas</strong> neužima.</p>
    </template>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { LinkDismiss20Filled } from "@vicons/fluent";
import { NButton, NIcon, NInput, NPopconfirm, NSelect } from "naive-ui";
import { debounce } from "lodash";
import { onMounted, reactive, ref } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import DeleteModelButton from "@/Components/Admin/DeleteModelButton.vue";
import TipTap from "@/Components/TipTap.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  duty: Object,
  users: Array,
  errors: Object,
});

// const duty = reactive(props.duty);
const institutions = ref([]);
const duty = reactive(props.duty);

// duty.attributes = {
//   en: {
//     name: props.duty.attributes?.en?.name ?? "",
//     description: props.duty.attributes?.en?.description ?? "",
//   },
// };
// const attributes = ref(duty.attributes);

const getInstitutionOptions = debounce((input) => {
  // get other lang
  if (input.length > 2) {
    // message.loading("Ieškoma...");
    Inertia.post(
      route("dutyInstitutions.search"),
      {
        data: {
          name: input,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          institutions.value = usePage().props.value.search.other.map(
            (institution) => {
              return {
                value: institution.id,
                label: `${institution.name} (${institution.alias})`,
              };
            }
          );
        },
      }
    );
  }
}, 500);

////////////////////////////////////////////////////////////////////////////////

const detachUserFromDuty = (user) => {
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

onMounted(() => {
  institutions.value = [
    {
      value: duty.institution.id,
      label: `${duty.institution.name} (${duty.institution.alias})`,
    },
  ];

  duty.institution.id = props.duty.institution.id;
});

// JSON parse props.duty.attributes

// onBeforeMount(() => {
//   duty.attributes = props.duty.attributes;
// });
</script>
