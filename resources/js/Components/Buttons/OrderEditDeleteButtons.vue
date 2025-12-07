<template>
  <div class="inline-flex items-center gap-1">
    <Button v-if="index > 0" size="icon-xs" variant="ghost" @click="$emit('moveUp')">
      <Icon icon="fluent:arrow-up-16-regular" class="size-3" />
    </Button>
    <Button v-if="index < length - 1" size="icon-xs" variant="ghost" @click="$emit('moveDown')">
      <Icon icon="fluent:arrow-down-16-regular" class="size-3" />
    </Button>
    <Link :href="editRoute">
      <Button size="icon-xs" variant="ghost">
        <Icon icon="fluent:edit-16-regular" class="size-3" />
      </Button>
    </Link>
    <NPopconfirm @positive-click="$emit('delete')">
      <template #trigger>
        <Button size="icon-xs" variant="destructive">
          <Icon icon="fluent:delete-16-regular" class="size-3" />
        </Button>
      </template>
      Ar tikrai norite ištrinti šį elementą?
    </NPopconfirm>
  </div>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import { NPopconfirm } from 'naive-ui';
import { Button } from '@/Components/ui/button';

defineProps<{
  index: number;
  length: number;
  editRoute: string;
}>()

defineEmits(['moveUp', 'moveDown', 'delete']);
</script>
