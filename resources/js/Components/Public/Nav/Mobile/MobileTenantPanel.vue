<template>
  <ul class="flex flex-col pb-2">
    <li v-for="option in options" :key="option.key">
      <button
        type="button"
        class="flex min-h-11 w-full items-center gap-3 border-l-2 border-t border-border/50 px-4 py-2.5 text-left transition-colors focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-ring"
        :class="isActive(option.key) ? 'border-l-brand bg-secondary/60' : 'border-l-transparent'"
        @click="switchTenant(option.key)"
      >
        <Avatar class="size-9 shrink-0 rounded-none">
          <AvatarImage v-if="option.primary_institution?.image_url" :src="option.primary_institution.image_url" />
          <!-- `rounded-none` explicitly: the primitive's base is `rounded-full`, a literal that
               survives the public surface's zeroed radius scale. -->
          <AvatarFallback class="rounded-none bg-secondary text-[11px] font-bold uppercase tracking-wide text-foreground">
            {{ option.key.substring(0, 2).toUpperCase() }}
          </AvatarFallback>
        </Avatar>

        <span class="min-w-0 flex-1">
          <span class="block truncate text-sm font-bold text-foreground">{{ option.label }}</span>
          <span v-if="secondaryLabel(option)" class="block truncate text-xs text-muted-foreground">
            {{ secondaryLabel(option) }}
          </span>
        </span>

        <IFluentCheckmark24Regular v-if="isActive(option.key)" class="size-4 shrink-0 text-brand" />
      </button>
    </li>
  </ul>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';

import type { TenantOption } from '@/Composables/useTenantOptions';
import { useTenantOptions } from '@/Composables/useTenantOptions';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import IFluentCheckmark24Regular from '~icons/fluent/checkmark-24-regular';

const { options, isActive, switchTenant } = useTenantOptions();

/** The short name below a unit's name, unless it just repeats it (the main office). */
function secondaryLabel(option: TenantOption): string {
  const shortName = $t(option.primary_institution?.short_name ?? '');

  return shortName.trim().toLowerCase() === (option.label ?? '').trim().toLowerCase() ? '' : shortName;
}
</script>
