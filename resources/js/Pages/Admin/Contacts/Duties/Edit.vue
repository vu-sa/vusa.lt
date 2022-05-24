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

        <div class="lg:col-span-2">
          <label class="font-bold text-red-700">Title in English</label>
          <n-input
            v-model:value="duty.attributes.en.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

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
          <TipTap v-model="duty.description" :searchFiles="$page.props.search.other" />
        </div>

        <div class="col-span-4">
          <label class="font-bold text-red-700">Description in English</label>
          <TipTap
            v-model="duty.attributes.en.description"
            :searchFiles="$page.props.search.other"
          />
        </div>

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
          <n-popconfirm
            positive-text="Ištrinti!"
            negative-text="Palikti"
            @positive-click="destroyModel()"
          >
            <template #trigger>
              <button type="button">
                <TrashIcon
                  class="w-5 h-5 mr-2 stroke-red-800 hover:stroke-red-900 duration-200"
                />
              </button>
            </template>
            Ištrinto elemento nebus galima atkurti!
          </n-popconfirm>
          <n-popconfirm @positive-click="updateModel()">
            <template #trigger>
              <NSpin :show="showSpin" size="small">
                <n-button>Atnaujinti</n-button>
              </NSpin>
            </template>
            Ar tikrai atnaujinti?
          </n-popconfirm>
        </div>
      </form>
    </div>
    <template #aside-navigation-options>
      <div v-if="duty.users">
        <strong>Šiuo metu šias pareigas užima:</strong>
        <ul class="list-inside">
          <li v-for="user in duty.users">
            <Link :href="route('users.edit', { id: user.id })">{{ user.name }}</Link>
          </li>
        </ul>
      </div>
      <p v-else>Šių pareigų <strong>niekas</strong> neužima.</p>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted, onBeforeMount } from "vue";
import {
  NSpin,
  NInput,
  NSelect,
  NInputNumber,
  NPopconfirm,
  useMessage,
  NButton,
  NDatePicker,
} from "naive-ui";
import { Inertia } from "@inertiajs/inertia";
import { TrashIcon } from "@heroicons/vue/outline";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import TipTap from "@/Components/TipTap.vue";

const message = useMessage();

const props = defineProps({
  duty: Object,
  errors: Object,
});

// const duty = reactive(props.duty);
const institutions = ref([]);
const duty = reactive(props.duty);
// const attributes = ref(duty.attributes);
const showSpin = ref(false);

const getInstitutionOptions = _.debounce((input) => {
  // get other lang
  if (input.length > 2) {
    message.loading("Ieškoma...");
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
          institutions.value = usePage().props.value.search.other.map((institution) => {
            return {
              value: institution.id,
              label: `${institution.name} (${institution.alias})`,
            };
          });
        },
      }
    );
  }
}, 500);

////////////////////////////////////////////////////////////////////////////////

const updateModel = () => {
  showSpin.value = !showSpin.value;
  // jsonify attributes
  console.log("Prieš JSON");
  duty.attributes = JSON.stringify(duty.attributes);
  console.log("Po JSON");
  console.log(duty, duty.attributes);

  Inertia.patch(route("duties.update", duty.id), duty, {
    // preserveState: true,
    onSuccess: () => {
      // console.log("Success");
      showSpin.value = !showSpin.value;
      message.success("Sėkmingai atnaujintas!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
    preserveState: true,
  });
};

// const destroyModel = () => {
//   Inertia.delete(route("duties.destroy", duty.id), {
//     onSuccess: () => {
//       message.success("Kalendoriaus įrašas ištrintas!");
//     },
//     preserveScroll: true,
//   });
// };

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

onBeforeMount(() => {
  duty.attributes = props.duty.attributes;
});
</script>
