<template>
  <PageContent title="Navigacija">
    <NFormItem label-placement="left" label="Rodyti redagavimą">
      <NSwitch v-model:value="showAdminEdit" />
    </NFormItem>
    <NFormItem label-placement="left" label="Rodyti stulpelių keitimo rodykles">
      <NSwitch v-model:value="showColumnChangeArrows" />
    </NFormItem>
    <TransitionGroup ref="el" tag="div">
      <div v-for="item in navigation" :key="item.id"
        class="relative grid w-full grid-cols-[24px,_1fr] gap-4 border border-zinc-300 p-3 shadow-sm first:rounded-t-lg last:rounded-b-lg dark:border-zinc-700/40 dark:bg-zinc-800/5">
        <NButton class="handle" style="height: 100%;" quaternary size="small">
          <template #icon>
            <IFluentReOrderDotsVertical24Regular />
          </template>
        </NButton>
        <div>
          <span class="text-xl font-bold">{{ item.name }}
            <Link v-if="showAdminEdit" :href="route('navigation.edit', { navigation: item.id })">
            <NButton size="tiny" circle secondary>
              <template #icon>
                <Icon icon="fluent:edit-16-regular" width="12" height="12" />
              </template>
            </NButton>
            </Link>
          </span>
          <MainNavigationMenuContent :item is-used-without-root are-links-disabled :show-edit-icons="showAdminEdit">
            <template #editIconsLink="{ index, link, links }">
              <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />
              <div v-else>
                <NButtonGroup>
                  <NButton size="tiny" circle tertiary @click="changeColumn(link, 'left')">
                    <template #icon>
                      <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                    </template>
                  </NButton>
                  <NButton size="tiny" circle tertiary @click="changeColumn(link, 'right')">
                    <template #icon>
                      <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                    </template>
                  </NButton>
                </NButtonGroup>
              </div>
            </template>
            <template #editIconsBg="{ index, link, links }">
              <OrderEditDeleteButtons v-if="!showColumnChangeArrows" :index :length="links.length"
                :edit-route="route('navigation.edit', { navigation: link.id })" @delete="handleDelete(link)"
                @move-up="moveUp(item, link)" @move-down="moveDown(item, link)" />

              <div v-else>
                <NButtonGroup>
                  <NButton size="tiny" circle tertiary @click="changeColumn(link, 'left')">
                    <template #icon>
                      <Icon icon="fluent:arrow-left-16-regular" width="12" height="12" />
                    </template>
                  </NButton>
                  <NButton size="tiny" circle tertiary @click="changeColumn(link, 'right')">
                    <template #icon>
                      <Icon icon="fluent:arrow-right-16-regular" width="12" height="12" />
                    </template>
                  </NButton>
                </NButtonGroup>
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
          <NButtonGroup class="ml-auto" size="small">
            <NButton type="primary" :tag="Link" :href="route('navigation.create', { parent_id: item.id })">
              <template #icon>
                <Icon icon="fluent:add-16-regular" />
              </template>
              Pridėti elementą
            </NButton>
            <NPopconfirm @positive-click="handleDelete(item)">
              <template #trigger>
                <NButton type="error">
                  <template #icon>
                    <Icon icon="fluent:delete-16-regular" />
                  </template>
                  Ištrinti
                </NButton>
              </template>
              Ar tikrai norite ištrinti šį elementą?
            </NPopconfirm>
          </NButtonGroup>
        </div>
      </div>
      <div class="mt-4">
        <NButtonGroup>
          <NButton :tag="Link" type="primary" :href="route('navigation.create', { parent_id: 0 })">
            <template #icon>
              <Icon icon="fluent:add-16-regular" />
            </template>
            Pridėti pagrindinį navigacijos elementą
          </NButton>
          <NButton @click="saveOrder()">
            <template #icon>
              <Icon icon="fluent:save-16-regular" />
            </template>
            Išsaugoti rikiavimą
          </NButton>
        </NButtonGroup>
      </div>
    </TransitionGroup>
  </PageContent>
</template>

<script setup lang="tsx">
import { Icon } from "@iconify/vue";
import { Link, router } from "@inertiajs/vue3";
import { ref } from "vue";
import { useSortable } from "@vueuse/integrations/useSortable";

import MainNavigationMenuContent from "@/Components/Public/Nav/MainNavigationMenuContent.vue";
import OrderEditDeleteButtons from "@/Components/Buttons/OrderEditDeleteButtons.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
}>();

const el = ref(null);
const contents = ref(props.navigation);

const showAdminEdit = ref(true);
const showColumnChangeArrows = ref(false);

useSortable(el, contents, {
  handle: ".handle", animation: 100,
});

const saveOrder = () => {
  router.post(route("navigation.updateOrder"), {
    navigation: contents.value,
  });
};

const moveUp = (parent, link) => {
  // Find contents array by parent id 
  const contentsIndex = contents.value.findIndex((item) => item.id === parent.id);

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < contents.value[contentsIndex].links.length; i++) {
    console.log(contents.value[contentsIndex].links[i]);
    if (contents.value[contentsIndex].links[i].find((item) => item.id === link.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = contents.value[contentsIndex].links[linkArrayIndex].findIndex((item) => item.id === link.id);

  // If link index is found, swap the links but not the linkArrays
  if (linkIndex !== -1) {
    const temp = contents.value[contentsIndex].links[linkArrayIndex][linkIndex];
    contents.value[contentsIndex].links[linkArrayIndex][linkIndex] = contents.value[contentsIndex].links[linkArrayIndex][linkIndex - 1];
    contents.value[contentsIndex].links[linkArrayIndex][linkIndex - 1] = temp;
  }
};

const moveDown = (parent, link) => {
  // Find contents array by parent id 
  const contentsIndex = contents.value.findIndex((item) => item.id === parent.id);

  let linkArrayIndex = -1;
  // Find links index by iterating through links array
  for (let i = 0; i < contents.value[contentsIndex].links.length; i++) {
    console.log(contents.value[contentsIndex].links[i]);
    if (contents.value[contentsIndex].links[i].find((item) => item.id === link.id)) {
      linkArrayIndex = i;
      break;
    }
  }

  // If linkArray index is not found, return
  if (linkArrayIndex === -1) {
    return;
  }

  // Find index of the link in the links array
  const linkIndex = contents.value[contentsIndex].links[linkArrayIndex].findIndex((item) => item.id === link.id);

  // If link index is found, swap the links but not the linkArrays
  if (linkIndex !== -1) {
    const temp = contents.value[contentsIndex].links[linkArrayIndex][linkIndex];
    contents.value[contentsIndex].links[linkArrayIndex][linkIndex] = contents.value[contentsIndex].links[linkArrayIndex][linkIndex + 1];
    contents.value[contentsIndex].links[linkArrayIndex][linkIndex + 1] = temp;
  }
};

const changeColumn = (link, direction) => {
  router.post(route("navigation.updateColumn"), {
    id: link.id,
    direction: direction,
  }, { preserveScroll: true });
};

const handleDelete = (link) => {
  console.log(link);
  router.delete(route("navigation.destroy", link.id));
};
</script>
