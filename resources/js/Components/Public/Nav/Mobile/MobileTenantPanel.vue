<template>
  <ul class="flex flex-col gap-1 p-3">
    <li v-for="option in options" :key="option.key">
      <button
        type="button"
        class="flex min-h-11 w-full items-center gap-3 rounded-md px-3 py-2 text-left transition-colors hover:bg-zinc-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-zinc-500 dark:hover:bg-zinc-800 dark:focus-visible:ring-zinc-400"
        :class="{ 'bg-accent text-accent-foreground': isActive(option.key) }"
        @click="switchTenant(option.key)"
      >
        <Avatar class="size-8 shrink-0">
          <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
          <AvatarFallback>{{ option.key.substring(0, 2).toUpperCase() }}</AvatarFallback>
        </Avatar>

        <div class="min-w-0 flex-1 truncate">
          <span :class="{ 'font-bold': option.isMainOffice }">{{ option.label }}</span>
          <span v-if="option.primary_institution?.short_name" class="block truncate text-xs text-muted-foreground">
            {{ $t(option.primary_institution.short_name) }}
          </span>
        </div>

        <IFluentCheckmark24Regular v-if="isActive(option.key)" class="size-4 shrink-0" />
      </button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { useTenantOptions } from '@/Composables/useTenantOptions';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark-24-regular';

const { options, isActive, switchTenant } = useTenantOptions();
</script>
