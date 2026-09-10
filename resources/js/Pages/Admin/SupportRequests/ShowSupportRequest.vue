<template>
  <ShowPageLayout :title="supportRequest.title" :subtitle :model="supportRequest" audit-subject-type="support_request">
    <template #badge>
      <DropdownMenu v-if="permissions.can_update_status">
        <DropdownMenuTrigger as-child>
          <Button variant="ghost" size="sm" class="h-auto cursor-pointer p-0 hover:bg-transparent">
            <Badge :class="[statusBadgeClass, 'gap-1 transition-transform hover:scale-105']">
              {{ currentStatusLabel }}
              <ChevronDown class="size-3 opacity-70" />
            </Badge>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
          <DropdownMenuLabel class="text-xs text-muted-foreground">
            {{ $t('Keisti būseną') }}
          </DropdownMenuLabel>
          <DropdownMenuItem v-for="status in availableStatuses" :key="status.value" :disabled="status.value === currentStatus" @click="handleStatusChange(status.value)">
            <span class="flex items-center gap-2"><span :class="['size-2 rounded-full', statusDotClass(status.value)]" />{{ status.label }}</span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
      <Badge v-else :class="statusBadgeClass">
        {{ currentStatusLabel }}
      </Badge>
    </template>

    <template #info>
      <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-xs text-muted-foreground sm:text-sm">
        <div class="flex items-center gap-1.5">
          <UserAvatar v-if="supportRequest.creator" :user="supportRequest.creator" :size="20" />
          <span class="font-medium text-foreground">{{ supportRequest.creator?.name ?? supportRequest.reporter_name ?? $t('Svečias') }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <CalendarDays class="size-3.5" /><span>{{ formatDate(supportRequest.created_at) }}</span>
        </div>
        <div v-if="supportRequest.resolved_at" class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
          <CheckCircle2 class="size-3.5" /><span>{{ formatDate(supportRequest.resolved_at) }}</span>
        </div>
        <div class="flex items-center gap-1.5">
          <span>{{ $t('Priskirta:') }}</span>
          <DropdownMenu v-if="permissions.can_assign">
            <DropdownMenuTrigger as-child>
              <Button variant="ghost" size="sm" class="h-6 px-1.5 text-xs font-medium text-foreground hover:bg-muted">
                {{ supportRequest.assignedTo?.name ?? $t('Nepriskirta') }}<ChevronDown class="ml-1 size-3 opacity-60" />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="max-h-60 overflow-y-auto">
              <DropdownMenuItem @click="handleAssign(null)">
                {{ $t('Nepriskirta') }}
              </DropdownMenuItem>
              <DropdownMenuItem v-for="assignee in assignees" :key="assignee.id" @click="handleAssign(assignee.id)">
                <div class="flex items-center gap-2">
                  <UserAvatar :user="assignee" :size="18" />{{ assignee.name }}
                </div>
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
          <span v-else class="font-medium text-foreground">{{ supportRequest.assignedTo?.name ?? $t('Nepriskirta') }}</span>
        </div>
      </div>
    </template>

    <template #actions>
      <Button v-if="permissions.can_update" variant="outline" size="sm" class="h-9 gap-1.5" as-child>
        <Link :href="route('supportRequests.edit', supportRequest.id)">
          <Edit class="size-4" /><span class="hidden sm:inline">{{ $t('Redaguoti') }}</span>
        </Link>
      </Button>
      <DropdownMenu v-if="permissions.can_delete">
        <DropdownMenuTrigger as-child>
          <Button variant="outline" size="icon" class="size-9">
            <MoreHorizontal class="size-4" />
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem class="text-destructive focus:text-destructive" @click="handleDelete">
            <Trash2 class="mr-2 size-4" />{{ $t('Šalinti pranešimą') }}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </template>

    <div class="space-y-6">
      <div v-if="supportRequest.context_url || supportRequest.selected_text" class="rounded-xl border bg-muted/30 p-4 text-sm">
        <div v-if="supportRequest.context_url" class="flex items-start gap-2">
          <span class="shrink-0 font-medium text-muted-foreground">{{ $t('Puslapis:') }}</span>
          <a
            :href="supportRequest.context_url"
            target="_blank"
            rel="noopener noreferrer"
            class="inline-flex break-all font-mono text-xs text-primary hover:underline"
          >
            {{ supportRequest.context_url }}
            <ExternalLink class="ml-1 size-3 shrink-0" />
          </a>
        </div>
        <blockquote v-if="supportRequest.selected_text" class="mt-3 border-l-2 border-primary/40 pl-3 text-sm italic text-foreground">
          „{{ supportRequest.selected_text }}“
        </blockquote>
      </div>

      <SectionCard :title="$t('Aprašymas')">
        <p class="whitespace-pre-wrap text-sm leading-relaxed text-foreground">
          {{ supportRequest.description }}
        </p>
      </SectionCard>

      <SectionCard v-if="supportRequest.media?.length" :title="$t('Prisegtos ekrano nuotraukos ir failai')" :count="supportRequest.media.length">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
          <button
            v-for="file in supportRequest.media"
            :key="file.id"
            type="button"
            class="group relative overflow-hidden rounded-xl border bg-muted/20 p-0 text-left transition-all hover:border-primary/60 hover:shadow-md"
            @click="openLightbox(file)"
          >
            <img :src="file.thumb_url || file.original_url" :alt="file.name" class="h-36 w-full object-cover transition-transform duration-200 group-hover:scale-105">
            <div class="truncate p-2 text-[11px] text-muted-foreground">
              {{ file.file_name }}
            </div>
          </button>
        </div>
      </SectionCard>

      <section class="border-t pt-6 dark:border-zinc-800">
        <DiscussionPanel commentable-type="supportRequest" :commentable-id="supportRequest.id" />
      </section>
    </div>

    <Dialog v-model:open="isLightboxOpen">
      <DialogContent class="max-w-4xl bg-background/95 p-2 backdrop-blur-md">
        <DialogHeader class="px-3 pt-2">
          <DialogTitle class="truncate text-sm">
            {{ activeLightboxImage?.file_name }}
          </DialogTitle>
        </DialogHeader>
        <div class="flex items-center justify-center p-2">
          <img
            v-if="activeLightboxImage"
            :src="activeLightboxImage.preview_url || activeLightboxImage.original_url"
            :alt="activeLightboxImage.name"
            class="max-h-[80vh] max-w-full rounded-lg object-contain"
          >
        </div>
      </DialogContent>
    </Dialog>
  </ShowPageLayout>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, ChevronDown, Edit, ExternalLink, MoreHorizontal, Trash2 } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import ShowPageLayout from '@/Components/Layouts/ShowPageLayout.vue';
import { SectionCard } from '@/Components/Patterns';
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/Components/ui/dropdown-menu';
import { BreadcrumbHelpers, usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import { getTranslatedValue } from '@/Composables/useTranslatedTitle';
import type { SupportRequestItem, SupportRequestMediaFile } from '@/Types/supportRequests';

const props = defineProps<{
  supportRequest: SupportRequestItem;
  availableStatuses: Array<{ value: string; label: string; badgeVariant: string }>;
  assignees: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  permissions: { can_update: boolean; can_update_status: boolean; can_assign: boolean; can_delete: boolean; can_restore: boolean };
}>();

const page = usePage();
const locale = computed(() => (page.props as { app?: { locale?: string } }).app?.locale ?? 'lt');
const currentStatus = computed(() => typeof props.supportRequest.status === 'object' ? props.supportRequest.status.value : props.supportRequest.status);
const subtitle = computed(() => [getTranslatedValue(props.supportRequest.area?.name, locale.value, ''), getTranslatedValue(props.supportRequest.type?.name, locale.value, '')].filter(Boolean).join(' · '));
const currentStatusLabel = computed(() => props.availableStatuses.find(status => status.value === currentStatus.value)?.label ?? currentStatus.value);
const statusBadgeClass = computed(() => ({
  new: 'bg-sky-100 text-sky-800 border-sky-200 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800',
  reviewing: 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
  planned: 'bg-zinc-100 text-zinc-800 border-zinc-200 dark:bg-zinc-900 dark:text-zinc-300 dark:border-zinc-700',
  in_progress: 'bg-indigo-100 text-indigo-800 border-indigo-200 dark:bg-indigo-950 dark:text-indigo-300 dark:border-indigo-800',
  done: 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
  declined: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-950 dark:text-red-300 dark:border-red-800',
}[currentStatus.value] ?? ''));

usePageBreadcrumbs(() => [BreadcrumbHelpers.createRouteBreadcrumb($t('vusa.lt pagalba'), 'mySupportRequests.index'), BreadcrumbHelpers.createBreadcrumbItem(props.supportRequest.title)]);

const formatDate = (date: string) => new Intl.DateTimeFormat(locale.value, { day: 'numeric', month: 'short', year: 'numeric' }).format(new Date(date));
const statusDotClass = (status: string) => ({ new: 'bg-sky-500', reviewing: 'bg-amber-500', planned: 'bg-zinc-500', in_progress: 'bg-indigo-500', done: 'bg-emerald-500', declined: 'bg-red-500' }[status] ?? 'bg-zinc-400');
const handleStatusChange = (status: string) => router.patch(route('supportRequests.status.update', props.supportRequest.id), { status }, { preserveScroll: true });
const handleAssign = (assignedTo: string | null) => router.patch(route('supportRequests.assign', props.supportRequest.id), { assigned_to: assignedTo }, { preserveScroll: true });
const handleDelete = () => {
  if (confirm($t('Ar tikrai norite pašalinti šį pranešimą?'))) {
    router.delete(route('supportRequests.destroy', props.supportRequest.id));
  }
};
const isLightboxOpen = ref(false);
const activeLightboxImage = ref<SupportRequestMediaFile | null>(null);
const openLightbox = (file: SupportRequestMediaFile) => {
  activeLightboxImage.value = file;
  isLightboxOpen.value = true;
};
</script>
