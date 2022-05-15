<template>
  <AdminLayout :title="contact.name">
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
            v-model:value="contact.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">El. paštas</label>
          <n-input
            v-model:value="contact.email"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Tel. nr</label>
          <n-input
            v-model:value="contact.phone"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Rolė</label>
          <n-input
            disabled
            v-model:value="contact.role.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <div class="lg:col-span-4">
          <label class="font-bold">Pareigybės</label>
          <n-select
            v-model:value="contact.duties"
            multiple
            filterable
            placeholder="Pasirinkti pareigybes..."
            :options="duties"
            clearable
            remote
            :clear-filter-after-select="false"
            @search="getDutyOptions"
          />
        </div>

        <div class="lg:col-span-2">
          <label class="font-bold">Logotipas</label>
          <UploadImage
            v-model="contact.profile_photo_path"
            :path="'contacts'"
          ></UploadImage>
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
import { usePage } from "@inertiajs/inertia-vue3";
import UploadImage from "@/Components/Admin/UploadImage.vue";

const message = useMessage();

const props = defineProps({
  contact: Object,
  errors: Object,
});

const contact = reactive(props.contact);
const duties = ref([]);
// const selectedDuties = ref(null);
const showSpin = ref(false);

const getDutyOptions = _.debounce((input) => {
  // get other lang
  if (input.length > 2) {
    message.loading("Ieškoma...");
    Inertia.post(
      route("duties.search"),
      {
        data: {
          name: input,
        },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          duties.value = usePage().props.value.search.other.map((duty) => {
            return {
              value: duty.id,
              label: `${duty.name} (${duty.institution})`,
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
  Inertia.patch(route("users.update", contact.id), contact, {
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
//   Inertia.delete(route("users.destroy", calendar.id), {
//     onSuccess: () => {
//       message.success("Vartotojo įrašas ištrintas!");
//     },
//     preserveScroll: true,
//   });
// };

onMounted(() => {
  duties.value = props.contact.duties.map((duty) => {
    return {
      value: duty.id,
      label: `${duty.name} (${duty.institution.alias})`,
    };
  });
  contact.duties = props.contact.duties.map((duty) => {
    return duty.id;
  });
});
</script>
