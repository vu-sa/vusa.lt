<template>
  <AdminContentPage>
    <InertiaHead :title="$t('workspaces.title')" />

    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-zinc-100">
          {{ $t('workspaces.my_workspaces') }}
        </h1>
        <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('workspaces.institution_hint') }}
        </p>
      </div>
      <Button @click="createOpen = true">
        <Plus class="mr-2 h-4 w-4" />
        {{ $t('workspaces.create') }}
      </Button>
    </div>

    <div v-if="workspaces.length" class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
      <Link
        v-for="workspace in workspaces"
        :key="workspace.id"
        :href="route('workspaces.show', workspace.id)"
        class="group"
      >
        <Card class="h-full transition-shadow hover:shadow-md">
          <CardHeader class="pb-2">
            <CardTitle class="flex items-start justify-between gap-2 text-base">
              <span class="line-clamp-2">{{ workspace.name }}</span>
              <NotebookPen class="h-4 w-4 shrink-0 text-zinc-400 transition-colors group-hover:text-vusa-red" />
            </CardTitle>
            <Badge v-if="workspace.institution" variant="secondary" class="w-fit">
              <Landmark class="mr-1 h-3 w-3" />
              {{ workspace.institution.name }}
            </Badge>
          </CardHeader>
          <CardContent class="flex items-end justify-between gap-2 pt-2">
            <div class="flex flex-col gap-1 text-xs text-zinc-500 dark:text-zinc-400">
              <span>{{ $tChoice('workspaces.documents_count', workspace.documents_count ?? 0, { count: String(workspace.documents_count ?? 0) }) }}</span>
              <span v-if="workspace.description" class="line-clamp-1">{{ workspace.description }}</span>
            </div>
            <div v-if="workspace.members?.length" class="flex -space-x-2">
              <UserAvatar
                v-for="member in workspace.members.slice(0, 4)"
                :key="member.id"
                :user="(member as any)"
                :size="24"
                class="ring-2 ring-white dark:ring-zinc-900"
              />
              <span
                v-if="workspace.members.length > 4"
                class="flex h-6 w-6 items-center justify-center rounded-full bg-zinc-200 text-[10px] font-semibold text-zinc-600 ring-2 ring-white dark:bg-zinc-700 dark:text-zinc-200 dark:ring-zinc-900"
              >
                +{{ workspace.members.length - 4 }}
              </span>
            </div>
          </CardContent>
        </Card>
      </Link>
    </div>

    <div v-else class="mt-12 flex flex-col items-center gap-3 text-center">
      <NotebookPen class="h-10 w-10 text-zinc-300 dark:text-zinc-600" />
      <p class="max-w-md text-sm text-zinc-500 dark:text-zinc-400">
        {{ $t('workspaces.no_workspaces') }}
      </p>
      <Button variant="outline" @click="createOpen = true">
        <Plus class="mr-2 h-4 w-4" />
        {{ $t('workspaces.create') }}
      </Button>
    </div>

    <!-- Create dialog -->
    <Dialog v-model:open="createOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.create') }}</DialogTitle>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCreate">
          <div class="space-y-1.5">
            <Label for="workspace-name">{{ $t('workspaces.name') }}</Label>
            <Input id="workspace-name" v-model="form.name" required maxlength="255" />
            <p v-if="form.errors.name" class="text-xs text-destructive">
              {{ form.errors.name }}
            </p>
          </div>
          <div class="space-y-1.5">
            <Label for="workspace-description">{{ $t('workspaces.description') }}</Label>
            <Textarea id="workspace-description" v-model="form.description" rows="3" />
          </div>
          <div class="space-y-1.5">
            <Label>{{ $t('workspaces.institution') }}</Label>
            <Select v-model="form.institution_id">
              <SelectTrigger>
                <SelectValue :placeholder="$t('workspaces.no_institution')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="NO_INSTITUTION">
                  {{ $t('workspaces.no_institution') }}
                </SelectItem>
                <SelectItem v-for="institution in userInstitutions" :key="institution.id" :value="institution.id">
                  {{ institution.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ $t('workspaces.institution_hint') }}
            </p>
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="createOpen = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button type="submit" :disabled="form.processing">
              {{ $t('Sukurti') }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { ref } from 'vue';
import { Head as InertiaHead, Link, useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { Landmark, NotebookPen, Plus } from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';

interface WorkspaceListItem {
  id: string;
  name: string;
  description?: string | null;
  institution?: { id: string; name: string } | null;
  members?: Array<{ id: string; name: string; profile_photo_path?: string | null }>;
  documents_count?: number;
}

const props = defineProps<{
  workspaces: WorkspaceListItem[];
  userInstitutions: Array<{ id: string; name: string }>;
}>();

usePageBreadcrumbs(() => [
  { label: $t('workspaces.title'), icon: NotebookPen },
]);

const createOpen = ref(false);

const NO_INSTITUTION = 'none';

const form = useForm({
  name: '',
  description: '',
  institution_id: props.userInstitutions[0]?.id ?? NO_INSTITUTION,
});

function submitCreate() {
  form
    .transform(data => ({
      ...data,
      institution_id: data.institution_id === NO_INSTITUTION ? null : data.institution_id,
    }))
    .post(route('workspaces.store'), {
      onSuccess: () => {
        createOpen.value = false;
        form.reset();
      },
    });
}
</script>
