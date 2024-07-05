<template>
  <div class="absolute bottom-8 right-12">
    <NButton type="tertiary" color="#bbbbbb" text @click="$emit('click')">
      <template #icon>
        <IFluentQuestion24Regular width="48" height="48" />
      </template>
    </NButton>
  </div>
</template>

<script setup lang="tsx">

defineEmits(["click"]);
</script>
