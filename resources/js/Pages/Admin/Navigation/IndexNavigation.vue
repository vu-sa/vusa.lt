<template>
  <PageContent title="Navigacija">
    <div class="flex items-center gap-4 mb-4">
      <div class="flex items-center gap-2">
        <Label>Rodyti redagavimą</Label>
        <Switch :model-value="showAdminEdit" @update:model-value="val => showAdminEdit = val" />
      </div>
      <div class="flex items-center gap-2">
        <Label>Rodyti stulpelių keitimo rodykles</Label>
        <Switch :model-value="showColumnChangeArrows" @update:model-value="val => showColumnChangeArrows = val" />
      </div>
    </div>
    <TransitionGroup ref="el" tag="div">
      <div v-for="item in contents" :key="item.id"
        class="relative grid w-full grid-cols-[24px__1fr] gap-4 border border-zinc-300 p-3 first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
        <Button class="handle" style="height: 100%;" variant="ghost" size="sm">
          <IFluentReOrderDotsVertical24Regular />
        </Button>
        <div>
          <span class="text-xl font-bold">{{ item.name }}
            <Link v-if="showAdminEdit" :href="route('navigation.edit', { navigation: item.id })">
              <Button size="icon-xs" variant="secondary" class="rounded-full">
                <Icon icon="fluent:edit-16-regular" width="12" height="12" />
              </Button>
            </Link>
          </span>
          <MainNavigationMenuContent :item is-used-without-root are-links-disabled :show-edit-icons="showAdminEdit">
            <template #editIconsLink="{ index, link, links }">
              <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />
              <div v-else>
                <ButtonGroup>
                  <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'left')">
                    <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                  </Button>
                  <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'right')">
                    <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                  </Button>
                </ButtonGroup>
              </div>
            </template>
            <template #editIconsBg="{ index, link, links }">
              <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />

              <div v-else>
                <ButtonGroup>
                  <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'left')">
                    <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                  </Button>
                  <Button size="icon-xs" variant="ghost" class="rounded-full" @click="changeColumn(link, 'right')">
                    <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                  </Button>
                </ButtonGroup>
              </div>
            </template>
            <template #editIconsDivider="{ index, link, links }">
              <OrderEditDeleteButtons :index :length="links.length"
                :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />
            </template>
          </MainNavigationMenuContent>
        </div>
        <div v-if="showAdminEdit" class="col-span-full ml-auto p-2">
          <ButtonGroup class="ml-auto">
            <Button :as="Link" :href="route('navigation.create', { parent_id: item.id })">
              <Icon icon="fluent:add-16-regular" />
              Pridėti elementą
            </Button>
            <AlertDialog>
              <AlertDialogTrigger as-child>
                <Button variant="destructive">
                  <Icon icon="fluent:delete-16-regular" />
                  Ištrinti
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>Ar tikrai?</AlertDialogTitle>
                  <AlertDialogDescription>Ar tikrai norite ištrinti šį elementą?</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>Atšaukti</AlertDialogCancel>
                  <AlertDialogAction @click="handleDelete(item)">
                    Patvirtinti
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </ButtonGroup>
        </div>
      </div>
    </TransitionGroup>
    <div class="mt-4">
      <ButtonGroup>
        <Button :as="Link" :href="route('navigation.create', { parent_id: 0 })">
          <Icon icon="fluent:add-16-regular" />
          Pridėti pagrindinį navigacijos elementą
        </Button>
        <Button variant="secondary" @click="saveOrder()">
          <Icon icon="fluent:save-16-regular" />
          Išsaugoti rikiavimą
        </Button>
      </ButtonGroup>
    </div>
  </PageContent>
</template>

<script setup lang="tsx">
import { Icon } from '@iconify/vue';
import { Link, router } from '@inertiajs/vue3';
import { cloneDeep } from 'lodash-es';
import { ref } from 'vue';
import { useSortable } from '@vueuse/integrations/useSortable';

import { Button } from '@/Components/ui/button';
import { ButtonGroup } from '@/Components/ui/button-group';
import { Label } from '@/Components/ui/label';
import { Switch } from '@/Components/ui/switch';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';
import MainNavigationMenuContent from '@/Components/Public/Nav/MainNavigationMenuContent.vue';
import OrderEditDeleteButtons from '@/Components/Buttons/OrderEditDeleteButtons.vue';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';

const props = defineProps<{
  navigation: App.Entities.Navigation;
}>();

const el = ref(null);
const contents = ref(cloneDeep(props.navigation).map(item => ({
  ...item,
  links: Object.values(item.links || {}),
})));

const showAdminEdit = ref(true);
const showColumnChangeArrows = ref(false);

useSortable(el, contents, {
  handle: '.handle', animation: 100,
});

const saveOrder = () => {
  router.post(route('navigation.updateOrder'), {
    navigation: contents.value.map(item => ({
      id: item.id,
      links: item.links?.map(col => col.map(link => ({ id: link.id }))),
    })),
  });
};

const moveUp = (parent, link) => {
  // Find contents array by parent id
  const contentsIndex = contents.value.findIndex(item => item.id === parent.id);

  if (contentsIndex === -1) {
    return;
  }

  const parentLinks = contents.value[contentsIndex].links;

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < parentLinks.length; i++) {
    if (parentLinks[i]?.find(item => item?.id === link?.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = parentLinks[linkArrayIndex].findIndex(item => item?.id === link?.id);

  // If link index is found and is not the first item, swap the links but not the linkArrays
  if (linkIndex > 0) {
    const temp = parentLinks[linkArrayIndex][linkIndex];
    parentLinks[linkArrayIndex][linkIndex] = parentLinks[linkArrayIndex][linkIndex - 1];
    parentLinks[linkArrayIndex][linkIndex - 1] = temp;
  }
};

const moveDown = (parent, link) => {
  // Find contents array by parent id
  const contentsIndex = contents.value.findIndex(item => item.id === parent.id);

  if (contentsIndex === -1) {
    return;
  }

  const parentLinks = contents.value[contentsIndex].links;

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < parentLinks.length; i++) {
    if (parentLinks[i]?.find(item => item?.id === link?.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = parentLinks[linkArrayIndex].findIndex(item => item?.id === link?.id);

  // If link index is found and is not the last item, swap the links but not the linkArrays
  if (linkIndex !== -1 && linkIndex < parentLinks[linkArrayIndex].length - 1) {
    const temp = parentLinks[linkArrayIndex][linkIndex];
    parentLinks[linkArrayIndex][linkIndex] = parentLinks[linkArrayIndex][linkIndex + 1];
    parentLinks[linkArrayIndex][linkIndex + 1] = temp;
  }
};

const changeColumn = (link, direction) => {
  router.post(route('navigation.updateColumn'), {
    id: link.id,
    direction,
  }, { preserveScroll: true });
};

const handleDelete = (link) => {
  router.delete(route('navigation.destroy', link.id));
};
</script>
