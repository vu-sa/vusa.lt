<template>
  <NFormItem label="Ikona">
    <template #label>
      Ikona. <span class="text-zinc-400"> Ikonų ieškokite <a target="_blank" class="font-bold underline"
          href="https://icon-sets.iconify.design/fluent/">čia</a>. Suradę reikiamą ikoną pasirinkite iš
        sąrašo. </span>
    </template>
    <NSelect v-model:value="iconRef" placeholder="Pasirinkti..." :render-tag filterable clearable :options="iconOptions ?? []"
      @change="($event) => $emit('update:icon', $event)">
      <template v-if="iconRef" #prefix>
        <Icon :icon="`fluent:${iconRef}`" />
      </template>
    </NSelect>
  </NFormItem>
</template>

<script setup lang="tsx">
import { Icon } from '@iconify/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
  icon: string;
}>();

defineEmits<{
  (e: 'update:icon', value: string): void
}>()

const iconRef = ref(props.icon);

const getIconOptions = async () => {
  const icons = fetch("https://api.iconify.design/collection?prefix=fluent");

  const data = await icons;
  const iconData = await data.json()

  return iconData;
};

const icons = await getIconOptions();

const iconOptions = computed(() => {
  return icons.uncategorized?.map((icon) => {
    return {
      value: icon,
      label: icon,
    };
  })
});

const renderTag = () => {
  return <span class="inline-flex items-center gap-2"><Icon icon={`fluent:${iconRef.value}`} />{iconRef.value}</span>;
}
</script>
