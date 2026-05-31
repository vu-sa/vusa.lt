<template>
  <Dialog :open @update:open="emit('update:open', $event)">
    <DialogContent class="sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('Neseniai aplankyta') }}</DialogTitle>
        <DialogDescription>
          {{ $t('Visi neseniai aplankyti administravimo puslapiai.') }}
        </DialogDescription>
      </DialogHeader>

      <div class="max-h-[50vh] overflow-y-auto py-2">
        <div v-if="pages.length" class="space-y-1">
          <div v-for="page in pages" :key="page.id"
            class="group/row flex items-center gap-1 rounded-md hover:bg-muted/50 transition-colors">
            <button class="flex flex-1 min-w-0 items-center gap-3 px-2 py-2 text-left" @click="handleNavigate(page)">
              <div class="flex size-8 shrink-0 items-center justify-center rounded-md bg-muted/50">
                <component :is="page.icon" class="size-4" />
              </div>
              <div class="flex-1 min-w-0">
                <span class="block text-sm font-medium truncate">{{ page.title }}</span>
                <span class="block text-xs text-muted-foreground">{{ page.relativeTime }}</span>
              </div>
            </button>
            <button type="button"
              class="shrink-0 p-2 rounded-md text-muted-foreground hover:text-foreground hover:bg-muted transition-colors"
              :class="page.pinned ? 'text-amber-500 hover:text-amber-500' : ''"
              :title="page.pinned ? $t('Atsegti') : $t('Prisegti puslapį')"
              :aria-label="page.pinned ? $t('Atsegti') : $t('Prisegti puslapį')" @click="handleTogglePin(page)">
              <Star class="size-4" :fill="page.pinned ? 'currentColor' : 'none'" />
            </button>
          </div>
        </div>
        <div v-else class="py-8 text-center text-sm text-muted-foreground">
          {{ $t('Dar nesate aplankę jokių puslapių.') }}
        </div>
      </div>

      <DialogFooter class="gap-2 sm:gap-0">
        <Button v-if="pages.length" variant="ghost" size="sm"
          class="text-destructive hover:text-destructive hover:bg-destructive/10" @click="handleClear">
          <Trash2 class="mr-1.5 h-4 w-4" />
          {{ $t('Išvalyti istoriją') }}
        </Button>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, type Component } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Trash2, Star } from 'lucide-vue-next';

import { resolvePageIcon } from '@/Composables/adminPageCatalog';
import { useUIPreferences } from '@/Composables/useUIPreferences';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Button } from '@/Components/ui/button';

const props = defineProps<{ open: boolean }>();
const emit = defineEmits<(e: 'update:open', value: boolean) => void>();

const { recentPages, clearRecent, isPinned, togglePin } = useUIPreferences();

function formatRelativeTime(timestamp: number): string {
  const now = Date.now();
  const diffMs = now - timestamp;
  const diffMin = Math.floor(diffMs / 60000);
  const diffHour = Math.floor(diffMin / 60);
  const diffDay = Math.floor(diffHour / 24);

  if (diffMin < 1) {
    return $t('Ką tik');
  }
  if (diffMin < 60) {
    return $t('Prieš :count min.', { count: diffMin });
  }
  if (diffHour < 24) {
    return $t('Prieš :count val.', { count: diffHour });
  }
  if (diffDay === 1) {
    return $t('Vakar');
  }
  if (diffDay < 7) {
    return $t('Prieš :count d.', { count: diffDay });
  }

  return new Date(timestamp).toLocaleDateString('lt-LT', {
    month: 'short',
    day: 'numeric',
  });
}

interface RecentPageRow {
  id: string;
  title: string;
  href: string;
  routeName?: string;
  icon: Component;
  relativeTime: string;
  pinned: boolean;
}

const pages = computed<RecentPageRow[]>(() => {
  return recentPages.value
    .filter(page => page.href)
    .map((page) => {
      return {
        id: page.id,
        title: page.title,
        href: page.href as string,
        routeName: page.routeName,
        icon: resolvePageIcon(page.routeName, page.href),
        relativeTime: formatRelativeTime(page.timestamp),
        pinned: isPinned({ routeName: page.routeName, href: page.href }),
      };
    });
});

const handleNavigate = (page: RecentPageRow) => {
  emit('update:open', false);
  router.visit(page.href);
};

const handleTogglePin = (page: RecentPageRow) => {
  togglePin({ routeName: page.routeName, href: page.href, title: page.title });
};

const handleClear = () => {
  clearRecent();
  emit('update:open', false);
};
</script>
