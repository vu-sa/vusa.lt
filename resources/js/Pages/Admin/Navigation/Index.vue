<template>
  <PageContent title="Navigacija">
    <NCard>
      <NTree
        block-line
        :data="data"
        draggable
        :checked-keys="checkedKeys"
        :expanded-keys="expandedKeys"
        :render-suffix="renderSuffix"
        @drop="handleDrop"
        @update:checked-keys="handleCheckedKeysChange"
        @update:expanded-keys="handleExpandedKeysChange"
      />
      <div
        class="mt-4 flex items-center justify-end md:col-start-2 lg:col-span-2 lg:col-start-3"
      >
        <n-popconfirm @positive-click="updateModel()">
          <template #trigger>
            <NSpin :show="showSpin" size="small">
              <n-button>Atnaujinti tvarką</n-button>
            </NSpin>
          </template>
          Ar tikrai atnaujinti?
        </n-popconfirm>
      </div>
    </NCard>
  </PageContent>
</template>

<script setup lang="ts">
// import { Edit16Regular } from "@vicons/fluent";
import { Link, router } from "@inertiajs/vue3";
import { NButton, NCard, NPopconfirm, NSpin, NTree } from "naive-ui";
import { h, ref } from "vue";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";

const props = defineProps<{
  navigation: App.Entities.Navigation;
}>();

const parseNavigation = (array, id) => {
  const result = [];
  array.forEach((item) => {
    if (item.parent_id === id) {
      result.push({
        key: item.id,
        label: item.name,
        children: parseNavigation(array, item.id),
        url: item.url,
        // suffix: () =>
        //   h(NButton, { quartenary: true, circle: true },
        //     h('template', { slot: "icon" },
        //       h(NIcon, { size: "tiny" },
        //         h(Edit16Regular)))),
      });
      if (result[result.length - 1].children.length === 0) {
        delete result[result.length - 1].children;
      }
    }
  });
  return result;
};

const renderSuffix = ({ option }) => {
  return h(
    NButton,
    {
      size: "tiny",
    },
    h(
      Link,
      {
        class: "no-underline",
        href: route("navigation.edit", { id: option.key }),
      },
      "Atnaujinti"
    )
  );
};

const dataRef = ref(parseNavigation(props.navigation, 0));
const showSpin = ref(false);

//////////////////////
// Drag and drop START
// I don't understand how this works
function findSiblingsAndIndex(node, nodes) {
  if (!nodes) return [null, null];
  for (let i = 0; i < nodes.length; ++i) {
    const siblingNode = nodes[i];
    if (siblingNode.key === node.key) return [nodes, i];
    const [siblings, index] = findSiblingsAndIndex(node, siblingNode.children);
    if (siblings && index !== null) return [siblings, index];
  }
  return [null, null];
}

const expandedKeysRef = ref([]);
const checkedKeysRef = ref([]);

const expandedKeys = expandedKeysRef;
const checkedKeys = checkedKeysRef;
const data = dataRef;

const handleExpandedKeysChange = (expandedKeys) => {
  expandedKeysRef.value = expandedKeys;
};
const handleCheckedKeysChange = (checkedKeys) => {
  checkedKeysRef.value = checkedKeys;
};

const handleDrop = ({ node, dragNode, dropPosition }) => {
  const [dragNodeSiblings, dragNodeIndex] = findSiblingsAndIndex(
    dragNode,
    dataRef.value
  );
  if (dragNodeSiblings === null || dragNodeIndex === null) return;
  dragNodeSiblings.splice(dragNodeIndex, 1);
  if (dropPosition === "inside") {
    if (node.children) {
      node.children.unshift(dragNode);
    } else {
      node.children = [dragNode];
    }
  } else if (dropPosition === "before") {
    const [nodeSiblings, nodeIndex] = findSiblingsAndIndex(node, dataRef.value);
    if (nodeSiblings === null || nodeIndex === null) return;
    nodeSiblings.splice(nodeIndex, 0, dragNode);
  } else if (dropPosition === "after") {
    const [nodeSiblings, nodeIndex] = findSiblingsAndIndex(node, dataRef.value);
    if (nodeSiblings === null || nodeIndex === null) return;
    nodeSiblings.splice(nodeIndex + 1, 0, dragNode);
  }
  dataRef.value = Array.from(dataRef.value);
};

// Drag and drop END
////////////////////

const updateModel = () => {
  showSpin.value = !showSpin.value;
  router.post(route("navigation.store"), data, {
    onSuccess: () => {
      showSpin.value = !showSpin.value;
      // message.success("Sėkmingai atnaujinta!");
    },
    onError: () => {
      showSpin.value = !showSpin.value;
    },
    preserveScroll: true,
  });
};
</script>
