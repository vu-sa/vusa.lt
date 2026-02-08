<template>
  <PageContent title="Duplikatų suliejimas" :heading-icon="Icons.USER">
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
            <Combobox
              :model-value="form.merged_user_id"
              :display-value="displayUserName"
              @update:model-value="onSelectMergedUser"
            >
              <ComboboxAnchor class="w-full">
                <ComboboxInput v-model="mergedSearchTerm" placeholder="Pasirinkite vartotoją" />
                <ComboboxTrigger />
              </ComboboxAnchor>
              <ComboboxList class="w-full min-w-[var(--reka-combobox-trigger-width)]">
                <ComboboxViewport class="max-h-60 p-1">
                  <ComboboxEmpty>Nerasta</ComboboxEmpty>
                  <ComboboxVirtualizer
                    v-slot="{ option }"
                    :options="filteredMergedUsers"
                    :estimate-size="40"
                    :text-content="(opt: App.Entities.Role) => opt.name"
                  >
                    <ComboboxItem :value="option.id" class="flex items-center gap-2">
                      <UserAvatar :user="option" :size="24" />
                      <span class="truncate">{{ option.name }}</span>
                      <a :href="route('users.edit', option.id)" target="_blank" class="ml-auto shrink-0" @click.stop>
                        <Button variant="ghost" size="icon-xs">
                          <IconEye />
                        </Button>
                      </a>
                    </ComboboxItem>
                  </ComboboxVirtualizer>
                </ComboboxViewport>
              </ComboboxList>
            </Combobox>
          </FormFieldWrapper>
          <FormFieldWrapper id="kept_user_id" label="Prijungti prie..." required>
            <Combobox
              :model-value="form.kept_user_id"
              :display-value="displayUserName"
              @update:model-value="onSelectKeptUser"
            >
              <ComboboxAnchor class="w-full">
                <ComboboxInput v-model="keptSearchTerm" placeholder="Pasirinkite vartotoją" />
                <ComboboxTrigger />
              </ComboboxAnchor>
              <ComboboxList class="w-full min-w-[var(--reka-combobox-trigger-width)]">
                <ComboboxViewport class="max-h-60 p-1">
                  <ComboboxEmpty>Nerasta</ComboboxEmpty>
                  <ComboboxVirtualizer
                    v-slot="{ option }"
                    :options="filteredKeptUsers"
                    :estimate-size="40"
                    :text-content="(opt: App.Entities.Role) => opt.name"
                  >
                    <ComboboxItem :value="option.id" class="flex items-center gap-2">
                      <UserAvatar :user="option" :size="24" />
                      <span class="truncate">{{ option.name }}</span>
                      <a :href="route('users.edit', option.id)" target="_blank" class="ml-auto shrink-0" @click.stop>
                        <Button variant="ghost" size="icon-xs">
                          <IconEye />
                        </Button>
                      </a>
                    </ComboboxItem>
                  </ComboboxVirtualizer>
                </ComboboxViewport>
              </ComboboxList>
            </Combobox>
          </FormFieldWrapper>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { computed, ref } from "vue";
import { ComboboxVirtualizer } from "reka-ui";
import IconEye from "~icons/fluent/eye16-regular";

import { Button } from "@/Components/ui/button";
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
} from "@/Components/ui/combobox";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import FormFieldWrapper from "@/Components/AdminForms/FormFieldWrapper.vue";
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import UserAvatar from "@/Components/Avatars/UserAvatar.vue";

const { users } = defineProps<{
  users: App.Entities.Role[];
}>();

const form = useForm({
  kept_user_id: null as number | null,
  merged_user_id: null as number | null,
});

const mergedSearchTerm = ref('');
const keptSearchTerm = ref('');

const usersById = computed(() => {
  const map = new Map<number, App.Entities.Role>();
  for (const user of users) {
    map.set(user.id, user);
  }
  return map;
});

function displayUserName(id: unknown): string {
  if (id == null) {
    return '';
  }
  return usersById.value.get(id as number)?.name ?? '';
}

const filteredMergedUsers = computed(() => {
  if (!mergedSearchTerm.value) {
    return users;
  }
  const term = mergedSearchTerm.value.toLowerCase();
  return users.filter(u => u.name.toLowerCase().includes(term));
});

const filteredKeptUsers = computed(() => {
  if (!keptSearchTerm.value) {
    return users;
  }
  const term = keptSearchTerm.value.toLowerCase();
  return users.filter(u => u.name.toLowerCase().includes(term));
});

function onSelectMergedUser(id: number) {
  form.merged_user_id = id;
  const user = users.find(u => u.id === id);
  mergedSearchTerm.value = user?.name ?? '';
}

function onSelectKeptUser(id: number) {
  form.kept_user_id = id;
  const user = users.find(u => u.id === id);
  keptSearchTerm.value = user?.name ?? '';
}

function handleFormSubmit() {
  form.post(route("users.mergeUsers"), {
    onSuccess: () => {
      form.reset();
      mergedSearchTerm.value = '';
      keptSearchTerm.value = '';
    },
  });
}
</script>
