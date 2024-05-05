<template>
  <div class="inline-flex items-center gap-2">
    <NButton v-if="index > 0" size="tiny" circle tertiary class="mr-1" @click="$emit('moveUp')">
      <template #icon>
        <Icon icon="fluent:arrow-up-16-regular" width="12" height="12" />
      </template>
    </NButton>
    <NButton v-if="index < length - 1" size="tiny" circle tertiary class="mr-1" @click="$emit('moveDown')">
    <template #icon>
        <Icon icon="fluent:arrow-down-16-regular" width="12" height="12" />
      </template>
    </NButton>
    <Link :href="editRoute">
    <NButton size="tiny" circle tertiary>
      <template #icon>
        <Icon icon="fluent:edit-16-regular" width="12" height="12" />
      </template>
    </NButton>
    </Link>
    <NButton size="tiny" circle type="error" @click="deleteRoute">
      <template #icon>
        <Icon icon="fluent:delete-16-regular" width="12" height="12" />
      </template>
    </NButton>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import { NButton } from 'naive-ui';

defineProps<{
  index: number;
  length: number;
  editRoute: string;
  deleteRoute: string;
}>()

defineEmits(['moveUp', 'moveDown']);
</script>
