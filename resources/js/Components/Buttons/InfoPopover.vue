<template>
  <NTooltip :style="{ maxWidth: '600px' }" trigger="hover" :show-arrow="false" :show-delay="500" :hide-delay="100">
    <template #trigger>
      <NButton text type="tertiary" size="tiny"><template #icon>
          <IFluentInfo24Regular />
        </template></NButton>
    </template>
    <slot />
  </NTooltip>
</template>

<script setup lang="tsx">
defineProps<{
  color?: string;
}>();
</script>
