<template>
  <NDropdown placement="bottom-end" :options @select="handleAction">
    <NButton quaternary size="small">
      <IFluentMoreVertical24Filled />
    </NButton>
  </NDropdown>
</template>

<script setup lang="ts" generic="T extends { id: number | string, deleted_at: string | undefined | null }">
import { trans as $t } from 'laravel-vue-i18n';
import { h } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDialog } from 'naive-ui';

import IFluentArrowCounterclockwise28Regular from "~icons/fluent/arrow-counterclockwise28-regular";
import IFluentArrowForward20Filled from "~icons/fluent/arrow-forward20-filled";
import IFluentCopy24Filled from "~icons/fluent/copy24-filled";
import IFluentDelete20Filled from "~icons/fluent/delete20-filled";
import IFluentEdit20Filled from "~icons/fluent/edit20-filled";

import { type DropdownOption } from 'naive-ui';

const props = defineProps<{
  routes: {
    show?: string;
    duplicate?: string;
    edit?: string;
    destroy?: string;
  };
  model: T;
  modelName: string;
}>()

const dialog = useDialog();

const options: DropdownOption[] = [
  {
    title: $t('Peržiūrėti'),
    key: 'show',
    show: !!props.routes.show && !props.model.deleted_at,
    icon: () => h(IFluentArrowForward20Filled),
  },
  {
    title: $t('Duplikuoti'),
    key: 'duplicate',
    show: !!props.routes.duplicate && !props.model.deleted_at,
    icon: () => h(IFluentCopy24Filled),
  },
  {
    title: $t('Redaguoti'),
    key: 'edit',
    show: !!props.routes.edit && !props.model.deleted_at,
    icon: () => h(IFluentEdit20Filled),
  },
  {
    title: $t('Ištrinti'),
    key: 'destroy',
    show: !!props.routes.destroy && !props.model.deleted_at,
    icon: () => h(IFluentDelete20Filled, { color: 'red' }),
  },
  {
    title: $t('Atkurti'),
    key: 'restore',
    show: !!props.model.deleted_at,
    icon: () => h(IFluentArrowCounterclockwise28Regular),
  },
];

const handleAction = (key: string) => {
  switch (key) {
    case 'edit':
      if (props.routes.edit) {
        router.visit(route(props.routes.edit, props.model.id));
      }
      break;
    case 'duplicate':
      if (props.routes.duplicate) {
        router.post(route(props.routes.duplicate, props.model.id));
      }
      break;
    case 'show':
      if (props.routes.show) {
        router.visit(route(props.routes.show, props.model.id));
      }
      break;
    case 'destroy':
      // NOTE: Duplicated in AdminForm.vue
      dialog.warning({
        title: $t('Ištrinti įrašą'),
        content: $t('Ar tikrai norite ištrinti šį įrašą?'),
        positiveText: $t('Ištrinti'),
        negativeText: $t('Atšaukti'),
        onPositiveClick: () => {
          if (props.routes.destroy) {
            router.delete(route(props.routes.destroy, props.model.id));
          }
        },
      });
      break;
    case 'restore':
      if (props.model.deleted_at) {
        router.patch(route(`${props.modelName}.restore`, props.model.id));
      }
      break;
  }
};

</script>
