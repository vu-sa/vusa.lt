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
          <NSelect
            v-model:value="contact.role.id"
            :disabled="$page.props.user.role.alias !== 'admin'"
            :options="rolesOptions"
            clearable
            type="text"
            placeholder="Be rolės..."
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
          <label class="font-bold">Nuotrauka</label>
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
          <UpsertModelButton :model="contact" model-route="users.update" />
        </div>
      </form>
    </div>
  </AdminLayout>
</template>

<script setup lang="ts">
import { Inertia } from "@inertiajs/inertia";
import { NInput, NSelect } from "naive-ui";
import { onMounted, reactive, ref } from "vue";
import { usePage } from "@inertiajs/inertia-vue3";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import UploadImage from "@/Components/Admin/UploadImage.vue";
import UpsertModelButton from "@/Components/Admin/UpsertModelButton.vue";

const props = defineProps({
  contact: Object,
  errors: Object,
  roles: Array,
});

const contact = reactive(props.contact);

contact.role = {
  id: contact.role?.id,
  name: contact.role?.name,
};

const duties = ref([]);

// map roles to options
const rolesOptions = props.roles.map((role) => ({
  label: role.name,
  value: role.id,
}));

const getDutyOptions = debounce((input) => {
  // get other lang
  if (input.length > 2) {
    // message.loading("Ieškoma...");
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

onMounted(() => {
  duties.value = props.contact.duties.map((duty) => {
    return {
      value: duty.id,
      label: `${duty.name} - ${duty.institution.short_name} (${duty.institution.alias})`,
    };
  });
  contact.duties = props.contact.duties.map((duty) => {
    return duty.id;
  });
});
</script>
