<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button :size="small ? 'icon-xs' : 'icon-sm'" :disabled variant="ghost" class="rounded-full" @click.stop>
        <IFluentMoreHorizontal24Filled />
      </Button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end">
      <DropdownMenuItem v-if="edit" @click="$emit('editClick')">
        <IFluentEdit24Filled class="mr-2 size-4" />
        {{ $t("forms.edit") }}
      </DropdownMenuItem>
      <template v-for="option in moreOptions" :key="option.key">
        <DropdownMenuItem @click="$emit('moreOptionClick', option.key)">
          <component :is="option.icon" v-if="option.icon" class="mr-2 size-4" />
          {{ option.label }}
        </DropdownMenuItem>
      </template>
      <DropdownMenuSeparator v-if="$props.delete && (edit || moreOptions?.length)" />
      <DropdownMenuItem v-if="$props.delete" class="text-destructive focus:text-destructive" @click="showDeleteModal = true">
        <IFluentDelete24Filled class="mr-2 size-4" />
        {{ $t("forms.delete") }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>

  <AlertDialog :open="showDeleteModal" @update:open="showDeleteModal = $event">
    <AlertDialogContent>
      <AlertDialogHeader>
        <AlertDialogTitle>{{ $t('Ištrinti įrašą?') }}</AlertDialogTitle>
        <AlertDialogDescription>
          {{ $t('Šis įrašas bus ištrintas negrįžtamai...') }}
        </AlertDialogDescription>
      </AlertDialogHeader>
      <AlertDialogFooter>
        <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
        <AlertDialogAction @click="$emit('deleteClick')">
          {{ $t('forms.delete') }}
        </AlertDialogAction>
      </AlertDialogFooter>
    </AlertDialogContent>
  </AlertDialog>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import type { Component } from 'vue';

import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
} from '@/Components/ui/alert-dialog';

interface MoreOption {
  label: string;
  key: string;
  icon?: Component;
}

defineEmits<{
  (event: 'editClick'): void;
  (event: 'deleteClick'): void;
  (event: 'moreOptionClick', key: string): void;
}>();

defineProps<{
  disabled?: boolean;
  small?: boolean;
  edit?: boolean;
  delete?: boolean;
  moreOptions?: MoreOption[];
}>();

const showDeleteModal = ref(false);
</script>
