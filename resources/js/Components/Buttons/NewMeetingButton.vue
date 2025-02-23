<template>
  <NButton size="small">
    <template #icon>
      <IFluentPeopleTeamAdd24Filled />
    </template>
    <slot>
      {{ $t("Pranešti") }}
    </slot>
  </NButton>
</template>
