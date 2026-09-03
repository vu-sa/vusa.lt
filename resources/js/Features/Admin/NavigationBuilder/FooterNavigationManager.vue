<template>
  <div>
    <p class="mb-4 text-sm text-muted-foreground">
      {{ $t('navigation.builder.footer_description') }}
    </p>

    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      <div v-for="column in columns" :key="column.id" class="rounded-lg border bg-background">
        <div class="flex items-center gap-2 border-b p-3">
          <span class="min-w-0 flex-1 truncate text-sm font-semibold" :class="[!column.is_active && 'opacity-60']">
            {{ column.name || `#${column.id}` }}
          </span>
          <Badge v-if="!column.url || column.url === '#'" variant="secondary" size="tiny">
            {{ $t('navigation.builder.footer_column_text_only') }}
          </Badge>
          <Badge v-if="!column.is_active" variant="secondary" size="tiny">
            {{ $t('navigation.builder.inactive') }}
          </Badge>

          <div class="ml-auto flex items-center gap-1">
            <Link :href="route('navigation.edit', { navigation: column.id })">
              <Button size="icon-xs" variant="ghost">
                <Pencil class="size-3.5" />
              </Button>
            </Link>
            <AlertDialog>
              <AlertDialogTrigger as-child>
                <Button size="icon-xs" variant="ghost" class="text-destructive hover:text-destructive">
                  <Trash2 class="size-3.5" />
                </Button>
              </AlertDialogTrigger>
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>{{ $t('navigation.builder.delete_confirm_title') }}</AlertDialogTitle>
                  <AlertDialogDescription>{{ $t('navigation.builder.delete_confirm_description') }}</AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>{{ $t('forms.cancel') }}</AlertDialogCancel>
                  <AlertDialogAction @click="$emit('delete-column', column)">
                    {{ $t('forms.delete') }}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>
          </div>
        </div>

        <div class="flex flex-col gap-1.5 p-2" :class="[!column.is_active && 'opacity-60']">
          <NavigationLinkCard
            v-for="link in column.links"
            :key="link.id"
            :link
            @toggle-active="val => $emit('toggle-link-active', link, val)"
            @delete="$emit('delete-link', link)"
          />
          <p v-if="column.links.length === 0" class="p-2 text-center text-xs text-muted-foreground">
            {{ $t('navigation.builder.empty_column') }}
          </p>
        </div>

        <div class="border-t p-2">
          <Button :as="Link" variant="ghost" size="sm" class="gap-1.5" :href="route('navigation.create', { parent_id: column.id, lang })">
            <Plus class="size-3.5" />
            {{ $t('navigation.builder.add_link') }}
          </Button>
        </div>
      </div>

      <Button
        v-if="columns.length < maxColumns"
        :as="Link"
        variant="outline"
        class="min-h-24 gap-1.5 border-dashed"
        :href="route('navigation.create', { parent_id: 0, lang, location: 'footer' })"
      >
        <Plus class="size-4" />
        {{ $t('navigation.builder.add_footer_column') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from 'lucide-vue-next';

import NavigationLinkCard from './NavigationLinkCard.vue';
import type { AdminFooterColumn, AdminNavigationLink } from './types';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from '@/Components/ui/alert-dialog';

defineProps<{
  columns: AdminFooterColumn[];
  lang: 'lt' | 'en';
  maxColumns: number;
}>();

defineEmits<{
  (event: 'toggle-link-active', link: AdminNavigationLink, value: boolean): void;
  (event: 'delete-link', link: AdminNavigationLink): void;
  (event: 'delete-column', column: AdminFooterColumn): void;
}>();
</script>
