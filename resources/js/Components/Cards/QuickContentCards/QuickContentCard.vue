<template>
  <NCard class="max-w-sm" :segmented="{
    footer: 'soft',
  }">
    <template #header>
      <slot name="header" />
    </template>
    <template #header-extra>
      <slot name="header-extra" />
    </template>
    <slot />
    <template #footer>
      <div class="flex flex-row items-center">
        <slot name="action-button" />
      </div>
    </template>
  </NCard>
</template>

<script setup lang="tsx">
</script>

<style>
.n-card>.n-card-header {
  padding-bottom: 0.5rem;
}
</style>
