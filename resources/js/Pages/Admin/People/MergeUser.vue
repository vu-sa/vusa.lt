<template>
  <PageContent title="Duplikatų suliejimas" :heading-icon="UserIcon">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Sulieti duplikatus
          </template>
          <template #description>
            <div class="typography">
              <p>
                Pasirinkus narius, <strong>tik</strong> egzistuojantys ryšiai bus perduoti vienam vartotojui, t.y.:
              </p>
              <ul>
                <li> Pareigos </li>
                <li> Užduotys </li>
                <li> Veiklos </li>
                <li> Reservacijos </li>
                <li> Narystės </li>
              </ul>
              Šią funkciją naudoti <strong>tik tada, kai duombazėje atsirado to paties žmogaus duplikatai.</strong> Nenaudoti, kai norima perduoti pareigybes kitam žmogui.
              <p> Šis veiksmas yra <strong>neatstatomas</strong>! </p>
            </div>
          </template>
          <FormFieldWrapper id="merged_user_id" label="Prijungiamas vartotojas" required>
            <SingleSelect
              v-model="selectedMergedUser"
              :options="users"
              label-field="name"
              value-field="id"
              placeholder="Pasirinkite vartotoją"
              empty-text="Nerasta"
            >
              <template #option="{ item }">
                <div class="flex items-center gap-2 w-full">
                  <UserAvatar :user="item" :size="24" />
                  <span class="truncate">{{ item.name }}</span>
                  <a :href="route('users.edit', item.id)" target="_blank" class="ml-auto shrink-0" @click.stop>
                    <Button variant="ghost" size="icon-xs">
                      <IconEye />
                    </Button>
                  </a>
                </div>
              </template>
            </SingleSelect>
          </FormFieldWrapper>
          <FormFieldWrapper id="kept_user_id" label="Prijungti prie..." required>
            <SingleSelect
              v-model="selectedKeptUser"
              :options="users"
              label-field="name"
              value-field="id"
              placeholder="Pasirinkite vartotoją"
              empty-text="Nerasta"
            >
              <template #option="{ item }">
                <div class="flex items-center gap-2 w-full">
                  <UserAvatar :user="item" :size="24" />
                  <span class="truncate">{{ item.name }}</span>
                  <a :href="route('users.edit', item.id)" target="_blank" class="ml-auto shrink-0" @click.stop>
                    <Button variant="ghost" size="icon-xs">
                      <IconEye />
                    </Button>
                  </a>
                </div>
              </template>
            </SingleSelect>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import IconEye from '~icons/fluent/eye16-regular';
import { Button } from '@/Components/ui/button';
import { SingleSelect } from '@/Components/ui/single-select';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { UserIcon } from '@/Components/icons';

const { users } = defineProps<{
  users: App.Entities.Role[];
}>();

const form = useForm({
  kept_user_id: null as number | null,
  merged_user_id: null as number | null,
});

const selectedMergedUser = ref<App.Entities.Role | null>(null);
const selectedKeptUser = ref<App.Entities.Role | null>(null);

watch(selectedMergedUser, (user) => {
  form.merged_user_id = user?.id ?? null;
});

watch(selectedKeptUser, (user) => {
  form.kept_user_id = user?.id ?? null;
});

function handleFormSubmit() {
  form.post(route('users.mergeUsers'), {
    onSuccess: () => {
      form.reset();
      selectedMergedUser.value = null;
      selectedKeptUser.value = null;
    },
  });
}
</script>
