<template>
  <AdminLayout :title="navigation.name">
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
            v-model:value="navigation.name"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div>

        <!-- <div class="lg:col-span-2">
          <label class="font-bold">Trumpinys</label>
          <n-input
            disabled
            v-model:value="navigation.alias"
            type="text"
            placeholder="Įrašyti pavadinimą..."
          />
        </div> -->

        <div class="lg:col-span-4">
          <label class="font-bold">Nuoroda</label>
          <n-input
            v-model:value="navigation.url"
            type="text"
            placeholder="Įrašyti nuorodą..."
          />
        </div>

        <div
          class="md:col-start-2 lg:col-start-3 lg:col-span-2 flex justify-end items-center"
        >
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

const message = useMessage();

const props = defineProps({
  navigation: Object,
  errors: Object,
});

const navigation = reactive(props.navigation);
const showSpin = ref(false);

////////////////////////////////////////////////////////////////////////////////

const updateModel = () => {
  showSpin.value = !showSpin.value;
  Inertia.patch(route("navigation.update", navigation.id), navigation, {
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
</script>
