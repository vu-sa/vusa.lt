<template>
  <AdminLayout :title="dutyInstitution.name">
    <div class="main-card">
      <h3 class="mb-4">Bendra informacija</h3>
      <ul v-if="errors" class="mb-4 text-red-700">
        <li v-for="error in errors">{{ error }}</li>
      </ul>
      <form
        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-4 grid-flow-row-dense"
      >
        <div class="lg:col-span-2">
          <label class="font-bold">Vardas ir pavardė</label>
          <n-input
            v-model:value="dutyInstitution.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Trumpas pavadinimas</label>
          <n-input
            v-model:value="dutyInstitution.short_name"
            type="text"
            placeholder="Įrašyti trumpą pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Nuorodos trumpinys</label>
          <!-- TODO: disable input in the future -->
          <n-input
            v-model:value="dutyInstitution.alias"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Padalinys</label>
          <n-select
            v-model:value="dutyInstitution.padalinys_id"
            placeholder="Pasirinkti padalinį..."
            :options="padaliniai_options"
          />
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Aprašymas</label>
          <TipTap
            v-model="dutyInstitution.description"
            :searchFiles="$page.props.search.other"
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
          <!-- <n-popconfirm
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
          </n-popconfirm> -->
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
      <div v-if="duties">
        <strong>Šiuo metu institucijai priklauso šios pareigos:</strong>
        <ul class="list-inside">
          <li v-for="duty in duties">
            <Link :href="route('duties.edit', { id: duty.id })">{{ duty.name }}</Link>
          </li>
        </ul>
      </div>
      <p v-else>Ši institucija <strong>neturi</strong> pareigų.</p>
    </template>
  </AdminLayout>
</template>

<script setup>
import { ref, reactive, onMounted } from "vue";
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
  dutyInstitution: Object,
  duties: Object,
  padaliniai: Array,
  errors: Object,
});

console.log(props);

const dutyInstitution = reactive(props.dutyInstitution);

// const duties = ref([]);
// const selectedDuties = ref(null);
const showSpin = ref(false);

const padaliniai_options = props.padaliniai.map((padalinys) => ({
  value: padalinys.id,
  label: padalinys.shortname,
}));

// const getDutyOptions = _.debounce((input) => {
//   // get other lang
//   if (input.length > 2) {
//     message.loading("Ieškoma...");
//     Inertia.post(
//       route("duties.search"),
//       {
//         data: {
//           name: input,
//         },
//       },
//       {
//         preserveState: true,
//         preserveScroll: true,
//         onSuccess: () => {
//           duties.value = usePage().props.value.search.other.map((duty) => {
//             return {
//               value: duty.id,
//               label: `${duty.name} (${duty.institution})`,
//             };
//           });
//         },
//       }
//     );
//   }
// }, 500);

////////////////////////////////////////////////////////////////////////////////

const updateModel = () => {
  showSpin.value = !showSpin.value;
  Inertia.patch(route("dutyInstitutions.update", dutyInstitution.id), dutyInstitution, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      message.success("Sėkmingai atnaujinta!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};

// const destroyModel = () => {
//   Inertia.delete(route("calendar.destroy", calendar.id), {
//     onSuccess: () => {
//       message.success("Kalendoriaus įrašas ištrintas!");
//     },
//     preserveScroll: true,
//   });
// };

// onMounted(() => {
//   duties.value = props.dutyInstitution.duties.map((duty) => {
//     return {
//       value: duty.id,
//       label: `${duty.name} (${duty.institution.alias})`,
//     };
//   });
//   dutyInstitution.duties = props.dutyInstitution.duties.map((duty) => {
//     return duty.id;
//   });
// });
</script>
