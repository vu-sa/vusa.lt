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
              v-model="selectedMergedUser"
              :filter-function="filterUsers"
              :display-value="displayUserName"
            >
              <ComboboxAnchor
                class="relative flex h-10 w-full items-center gap-2 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-within:outline-none focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
              >
                <SearchIcon class="size-4 shrink-0 opacity-50" />
                <ComboboxInput
                  class="flex-1 bg-transparent placeholder:text-muted-foreground focus:outline-none"
                  placeholder="Pasirinkite vartotoją"
                />
                <ComboboxTrigger class="ml-auto shrink-0 text-muted-foreground hover:text-foreground">
                  <ChevronDownIcon class="size-4" />
                </ComboboxTrigger>
              </ComboboxAnchor>
              <ComboboxList class="min-w-[var(--reka-popper-anchor-width)]">
                <ComboboxViewport class="max-h-60 p-1">
                  <ComboboxEmpty>Nerasta</ComboboxEmpty>
                  <ComboboxVirtualizer
                    v-slot="{ option }"
                    :options="users"
                    :estimate-size="40"
                    :text-content="(opt: App.Entities.Role) => opt.name"
                  >
                    <ComboboxItem :value="option" class="flex items-center gap-2">
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
              v-model="selectedKeptUser"
              :filter-function="filterUsers"
              :display-value="displayUserName"
            >
              <ComboboxAnchor
                class="relative flex h-10 w-full items-center gap-2 rounded-md border border-input bg-background px-3 text-sm ring-offset-background focus-within:outline-none focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2"
              >
                <SearchIcon class="size-4 shrink-0 opacity-50" />
                <ComboboxInput
                  class="flex-1 bg-transparent placeholder:text-muted-foreground focus:outline-none"
                  placeholder="Pasirinkite vartotoją"
                />
                <ComboboxTrigger class="ml-auto shrink-0 text-muted-foreground hover:text-foreground">
                  <ChevronDownIcon class="size-4" />
                </ComboboxTrigger>
              </ComboboxAnchor>
              <ComboboxList class="min-w-[var(--reka-popper-anchor-width)]">
                <ComboboxViewport class="max-h-60 p-1">
                  <ComboboxEmpty>Nerasta</ComboboxEmpty>
                  <ComboboxVirtualizer
                    v-slot="{ option }"
                    :options="users"
                    :estimate-size="40"
                    :text-content="(opt: App.Entities.Role) => opt.name"
                  >
                    <ComboboxItem :value="option" class="flex items-center gap-2">
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
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import { ComboboxInput, ComboboxVirtualizer } from 'reka-ui';
import { SearchIcon, ChevronDownIcon } from 'lucide-vue-next';

import IconEye from '~icons/fluent/eye16-regular';
import { Button } from '@/Components/ui/button';
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxItem,
  ComboboxList,
  ComboboxTrigger,
  ComboboxViewport,
} from '@/Components/ui/combobox';
import AdminForm from '@/Components/AdminForms/AdminForm.vue';
import FormElement from '@/Components/AdminForms/FormElement.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';

const { users } = defineProps<{
  users: App.Entities.Role[];
}>();

const form = useForm({
  kept_user_id: null as number | null,
  merged_user_id: null as number | null,
});

const selectedMergedUser = ref<App.Entities.Role | null>(null);
const selectedKeptUser = ref<App.Entities.Role | null>(null);

function filterUsers(options: App.Entities.Role[], searchTerm: string): App.Entities.Role[] {
  if (!searchTerm) {
    return options;
  }
  const term = searchTerm.toLowerCase();
  return options.filter(u => u.name.toLowerCase().includes(term));
}

function displayUserName(val: unknown): string {
  return (val as App.Entities.Role)?.name ?? '';
}

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
