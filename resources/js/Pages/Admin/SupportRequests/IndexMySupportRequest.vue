<template>
  <AdminContentPage :title="$t('Mano pranešimai')">
    <template #headerActions>
      <Button as-child>
        <Link :href="route('mySupportRequests.create')">
          <Plus class="mr-2 h-4 w-4" />
          {{ $t('Naujas pranešimas') }}
        </Link>
      </Button>
    </template>

    <div class="space-y-6">
      <!-- Tabs for active vs resolved -->
      <div class="flex items-center gap-2 border-b pb-3">
        <Button
          variant="ghost"
          size="sm"
          :class="[
            'rounded-full px-4 text-xs font-medium',
            currentTab === 'all' ? 'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground' : 'text-muted-foreground',
          ]"
          @click="changeTab('all')"
        >
          {{ $t('Visi') }}
          <Badge variant="secondary" class="ml-1.5 h-5 px-1.5 text-[10px]">
            {{ counts.all }}
          </Badge>
        </Button>

        <Button
          variant="ghost"
          size="sm"
          :class="[
            'rounded-full px-4 text-xs font-medium',
            currentTab === 'active' ? 'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground' : 'text-muted-foreground',
          ]"
          @click="changeTab('active')"
        >
          {{ $t('Vykdomi') }}
          <Badge variant="secondary" class="ml-1.5 h-5 px-1.5 text-[10px]">
            {{ counts.active }}
          </Badge>
        </Button>

        <Button
          variant="ghost"
          size="sm"
          :class="[
            'rounded-full px-4 text-xs font-medium',
            currentTab === 'resolved' ? 'bg-primary text-primary-foreground hover:bg-primary/90 hover:text-primary-foreground' : 'text-muted-foreground',
          ]"
          @click="changeTab('resolved')"
        >
          {{ $t('Išspręsti') }}
          <Badge variant="secondary" class="ml-1.5 h-5 px-1.5 text-[10px]">
            {{ counts.resolved }}
          </Badge>
        </Button>
      </div>

      <!-- Requests List -->
      <div v-if="requests.data.length > 0" class="grid gap-3">
        <Link
          v-for="item in requests.data"
          :key="item.id"
          :href="route('supportRequests.show', item.id)"
          class="group block rounded-xl border bg-card p-4 transition-all hover:border-primary/50 hover:shadow-sm"
        >
          <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <Badge :class="getStatusBadgeClass(resolveStatus(item.status))">
                  {{ getStatusLabel(resolveStatus(item.status)) }}
                </Badge>
                <span class="text-xs text-muted-foreground">
                  {{ getTranslatedValue(item.type?.name, currentLocale) }} · {{ getTranslatedValue(item.area?.name, currentLocale) }}
                </span>
                <Badge v-if="resolveVisibility(item.visibility) === 'roles'" variant="outline" class="text-[10px]">
                  {{ $t('Rolėms') }}
                </Badge>
                <Badge v-else-if="resolveVisibility(item.visibility) === 'public'" variant="outline" class="text-[10px]">
                  {{ $t('Viešas') }}
                </Badge>
              </div>
              <h3 class="font-semibold text-base tracking-tight text-foreground group-hover:text-primary transition-colors">
                {{ item.title }}
              </h3>
            </div>

            <div class="flex items-center gap-3 text-xs text-muted-foreground self-start sm:self-center">
              <span v-if="item.comments_count" class="inline-flex items-center gap-1">
                <MessageSquare class="h-3.5 w-3.5" />
                {{ item.comments_count }}
              </span>
              <span v-if="item.media?.length" class="inline-flex items-center gap-1">
                <Paperclip class="h-3.5 w-3.5" />
                {{ item.media.length }}
              </span>
              <span>{{ new Date(item.created_at).toLocaleDateString('lt-LT') }}</span>
            </div>
          </div>

          <p class="mt-2 text-sm text-muted-foreground line-clamp-2">
            {{ item.description }}
          </p>

          <div v-if="item.assignedTo" class="mt-3 flex items-center gap-2 pt-2 border-t text-xs text-muted-foreground">
            <span>{{ $t('Priskirta:') }}</span>
            <span class="font-medium text-foreground">{{ item.assignedTo.name }}</span>
          </div>
        </Link>
      </div>

      <!-- Empty State -->
      <EmptyState
        v-else
        :title="$t('Pranešimų dar nėra')"
        :description="$t('Pastebėjai klaidą arba turi pasiūlymą? Sukurk naują pranešimą, kad IT komanda galėtų jį peržiūrėti.')"
      >
        <template #icon>
          <MessageSquareWarning class="h-8 w-8 text-primary" />
        </template>
        <Button as-child>
          <Link :href="route('mySupportRequests.create')">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('Sukurti pranešimą') }}
          </Link>
        </Button>
      </EmptyState>

      <!-- Pagination -->
      <div v-if="requests.last_page > 1" class="flex items-center justify-between border-t pt-4">
        <p class="text-xs text-muted-foreground">
          {{ $t('Rodoma') }} {{ requests.from }}-{{ requests.to }} {{ $t('iš') }} {{ requests.total }}
        </p>
        <div class="flex items-center gap-1">
          <Button
            v-for="(link, index) in requests.links"
            :key="index"
            :variant="link.active ? 'default' : 'outline'"
            size="sm"
            :disabled="!link.url"
            as-child
          >
            <Link v-if="link.url" :href="link.url">
              {{ cleanLabel(link.label) }}
            </Link>
            <span v-else>{{ cleanLabel(link.label) }}</span>
          </Button>
        </div>
      </div>
    </div>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { Plus, MessageSquare, MessageSquareWarning, Paperclip } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import EmptyState from '@/Components/Empty/EmptyState.vue';
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { SupportRequestItem } from '@/Types/supportRequests';

const page = usePage();
const currentLocale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale || 'lt');

defineProps<{
  requests: {
    data: SupportRequestItem[];
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
    from: number;
    to: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
  };
  counts: {
    all: number;
    active: number;
    resolved: number;
  };
  currentTab: string;
}>();

usePageBreadcrumbs(() => [
  BreadcrumbHelpers.createBreadcrumbItem($t('Mano pranešimai')),
]);

function changeTab(tab: string) {
  router.get(route('mySupportRequests.index'), { tab }, { preserveState: true });
}

function cleanLabel(label: string): string {
  return label.replace(/&laquo;/g, '«').replace(/&raquo;/g, '»').trim();
}

function resolveStatus(status: SupportRequestItem['status']): string {
  if (typeof status === 'object' && status !== null && 'value' in status) {
    return status.value;
  }
  return typeof status === 'string' ? status : 'new';
}

function resolveVisibility(visibility: SupportRequestItem['visibility']): string {
  if (typeof visibility === 'object' && visibility !== null && 'value' in visibility) {
    return visibility.value;
  }
  return typeof visibility === 'string' ? visibility : 'private';
}

function getStatusLabel(status: string): string {
  const map: Record<string, string> = {
    new: $t('Naujas'),
    reviewing: $t('Peržiūrima'),
    planned: $t('Suplanuota'),
    in_progress: $t('Vykdoma'),
    done: $t('Išspręsta'),
    declined: $t('Atmesta'),
  };
  return map[status] || status;
}

function getStatusBadgeClass(status: string): string {
  const map: Record<string, string> = {
    new: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950 dark:text-blue-300 dark:border-blue-800',
    reviewing: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
    planned: 'bg-purple-100 text-purple-800 border-purple-200 dark:bg-purple-950 dark:text-purple-300 dark:border-purple-800',
    in_progress: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800',
    done: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-950 dark:text-green-300 dark:border-green-800',
    declined: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
  };
  return map[status] || '';
}
</script>
