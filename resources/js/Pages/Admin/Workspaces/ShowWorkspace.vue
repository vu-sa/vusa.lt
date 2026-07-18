<template>
  <AdminContentPage>
    <InertiaHead :title="workspace.name" />

    <ShowPageHero
      :title="workspace.name"
      :subtitle="workspace.institution?.name"
      :icon="NotebookPen"
    >
      <template #info>
        <div class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400 sm:gap-4">
          <div v-if="workspace.institution" class="flex items-center gap-1.5">
            <Landmark class="h-4 w-4 shrink-0 text-zinc-400" />
            <span>{{ workspace.institution.name }}</span>
          </div>
          <div class="flex items-center gap-1.5">
            <Users class="h-4 w-4 shrink-0 text-zinc-400" />
            <span>{{ workspace.members.length }}</span>
          </div>
        </div>
      </template>
      <template #actions>
        <DropdownMenu v-if="canManageMembers || canDelete">
          <DropdownMenuTrigger as-child>
            <Button variant="outline" size="icon" class="h-9 w-9">
              <MoreHorizontal class="h-4 w-4" />
            </Button>
          </DropdownMenuTrigger>
          <DropdownMenuContent align="end">
            <DropdownMenuItem @click="openSettings">
              <Edit class="mr-2 h-4 w-4" />
              {{ $t('Redaguoti') }}
            </DropdownMenuItem>
            <template v-if="canDelete">
              <DropdownMenuSeparator />
              <DropdownMenuItem class="text-destructive focus:text-destructive" @click="deleteOpen = true">
                <Trash2 class="mr-2 h-4 w-4" />
                {{ $t('workspaces.delete') }}
              </DropdownMenuItem>
            </template>
          </DropdownMenuContent>
        </DropdownMenu>
      </template>
    </ShowPageHero>

    <p v-if="workspace.description" class="mt-4 max-w-2xl text-sm text-zinc-600 dark:text-zinc-400">
      {{ workspace.description }}
    </p>

    <Tabs v-model="currentTab" class="mt-6">
      <TabsList class="mb-4">
        <TabsTrigger value="documents">
          <FileText class="mr-1.5 h-4 w-4" />
          {{ $t('workspaces.tabs.documents') }}
        </TabsTrigger>
        <TabsTrigger value="discussion">
          <MessagesSquare class="mr-1.5 h-4 w-4" />
          {{ $t('workspaces.tabs.discussion') }}
        </TabsTrigger>
        <TabsTrigger value="links">
          <LinkIcon class="mr-1.5 h-4 w-4" />
          {{ $t('workspaces.tabs.links') }}
        </TabsTrigger>
        <TabsTrigger value="members">
          <Users class="mr-1.5 h-4 w-4" />
          {{ $t('workspaces.tabs.members') }}
        </TabsTrigger>
      </TabsList>

      <!-- Documents -->
      <TabsContent value="documents">
        <div class="flex justify-end">
          <Button size="sm" @click="createDocumentOpen = true">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('workspaces.documents.create') }}
          </Button>
        </div>

        <div v-if="workspace.documents.length" class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
          <Card
            v-for="document in workspace.documents"
            :key="document.id"
            class="cursor-pointer transition-shadow hover:shadow-md"
            @click="openDocument = document"
          >
            <CardHeader class="flex flex-row items-start justify-between gap-2 pb-2">
              <CardTitle class="flex items-center gap-2 text-base">
                <FileText class="h-4 w-4 shrink-0 text-zinc-400" />
                <span class="line-clamp-1">{{ document.title }}</span>
              </CardTitle>
              <DropdownMenu>
                <DropdownMenuTrigger as-child>
                  <Button variant="ghost" size="icon" class="h-7 w-7" @click.stop>
                    <MoreHorizontal class="h-4 w-4" />
                  </Button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem @click.stop="startRename(document)">
                    <Edit class="mr-2 h-4 w-4" />
                    {{ $t('workspaces.documents.rename') }}
                  </DropdownMenuItem>
                  <DropdownMenuItem class="text-destructive focus:text-destructive" @click.stop="archiveDocument(document)">
                    <Archive class="mr-2 h-4 w-4" />
                    {{ $t('workspaces.documents.archive') }}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            </CardHeader>
            <CardContent class="pt-0 text-xs text-zinc-500 dark:text-zinc-400">
              <span v-if="document.editor">
                {{ $t('workspaces.documents.last_edited') }}: {{ document.editor.name }},
              </span>
              {{ formatDateTime(document.updated_at) }}
            </CardContent>
          </Card>
        </div>

        <div v-else class="mt-8 flex flex-col items-center gap-3 text-center">
          <FileText class="h-8 w-8 text-zinc-300 dark:text-zinc-600" />
          <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('workspaces.documents.empty') }}
          </p>
        </div>
      </TabsContent>

      <!-- Discussion -->
      <TabsContent value="discussion">
        <DiscussionPanel commentable-type="workspace" :commentable-id="workspace.id" />
      </TabsContent>

      <!-- Links -->
      <TabsContent value="links">
        <div class="flex justify-end">
          <Button size="sm" @click="attachLinkOpen = true">
            <Plus class="mr-2 h-4 w-4" />
            {{ $t('workspaces.links.add') }}
          </Button>
        </div>

        <div v-if="workspace.links.length" class="mt-4 space-y-2">
          <div
            v-for="link in workspace.links"
            :key="link.id"
            class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-800"
          >
            <div class="flex min-w-0 items-center gap-3">
              <component :is="linkTypeIcon(link.type)" class="h-4 w-4 shrink-0 text-zinc-400" />
              <div class="min-w-0">
                <Link
                  v-if="linkHref(link)"
                  :href="linkHref(link)!"
                  class="block truncate text-sm font-medium text-zinc-900 hover:text-vusa-red dark:text-zinc-100"
                >
                  {{ linkLabel(link) }}
                </Link>
                <span v-else class="block truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                  {{ linkLabel(link) }}
                </span>
                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                  {{ $t(`workspaces.links.type_${link.type}`) }}
                  <template v-if="link.added_by"> · {{ $t('workspaces.links.added_by') }} {{ link.added_by.name }}</template>
                </span>
              </div>
            </div>
            <Button variant="ghost" size="icon" class="h-7 w-7 shrink-0" @click="detachLink(link)">
              <X class="h-4 w-4" />
            </Button>
          </div>
        </div>

        <div v-else class="mt-8 flex flex-col items-center gap-3 text-center">
          <LinkIcon class="h-8 w-8 text-zinc-300 dark:text-zinc-600" />
          <p class="max-w-sm text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('workspaces.links.empty') }}
          </p>
        </div>
      </TabsContent>

      <!-- Members -->
      <TabsContent value="members">
        <div v-if="workspace.institution" class="rounded-lg border border-zinc-200 bg-zinc-50/60 px-4 py-3 text-sm text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900/40 dark:text-zinc-400">
          <Landmark class="mr-1.5 inline h-4 w-4 text-zinc-400" />
          {{ $t('workspaces.members.via_institution') }}: <span class="font-medium">{{ workspace.institution.name }}</span>
        </div>

        <div class="mt-4 flex justify-end">
          <Button v-if="canManageMembers" size="sm" @click="inviteOpen = true">
            <UserPlus class="mr-2 h-4 w-4" />
            {{ $t('workspaces.members.invite') }}
          </Button>
        </div>

        <div class="mt-4 space-y-2">
          <div
            v-for="member in workspace.members"
            :key="member.id"
            class="flex items-center justify-between gap-3 rounded-lg border border-zinc-200 px-4 py-3 dark:border-zinc-800"
          >
            <div class="flex min-w-0 items-center gap-3">
              <UserAvatar :user="(member as any)" :size="32" />
              <div class="min-w-0">
                <p class="truncate text-sm font-medium text-zinc-900 dark:text-zinc-100">
                  {{ member.name }}
                </p>
                <Badge variant="secondary" class="mt-0.5">
                  {{ member.pivot?.role === 'admin' ? $t('Administratorius') : $t('Narys') }}
                </Badge>
              </div>
            </div>
            <div v-if="canManageMembers" class="flex shrink-0 items-center gap-1">
              <Button
                variant="ghost"
                size="sm"
                class="h-7 text-xs"
                @click="toggleRole(member)"
              >
                <ShieldCheck class="mr-1 h-3.5 w-3.5" />
                {{ member.pivot?.role === 'admin' ? $t('Narys') : $t('Administratorius') }}
              </Button>
              <Button variant="ghost" size="icon" class="h-7 w-7" @click="removeMember(member)">
                <X class="h-4 w-4" />
              </Button>
            </div>
          </div>
          <p v-if="!workspace.members.length" class="py-4 text-center text-sm text-zinc-500 dark:text-zinc-400">
            {{ $t('workspaces.members.empty') }}
          </p>
        </div>
      </TabsContent>
    </Tabs>

    <!-- Live collaborative document editor -->
    <WorkspaceDocumentDialog
      v-if="openDocument"
      :workspace-id="workspace.id"
      :document="openDocument"
      :current-user="currentUser"
      :mention-users="mentionUsers"
      @close="closeDocument"
    />

    <!-- Create document dialog -->
    <Dialog v-model:open="createDocumentOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.documents.create') }}</DialogTitle>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitCreateDocument">
          <div class="space-y-1.5">
            <Label for="document-title">{{ $t('workspaces.name') }}</Label>
            <Input
              id="document-title"
              v-model="newDocumentTitle"
              required
              maxlength="255"
              :placeholder="$t('workspaces.documents.title_placeholder')"
            />
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="createDocumentOpen = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button type="submit" :disabled="documentSubmitting || !newDocumentTitle.trim()">
              {{ $t('Sukurti') }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Rename document dialog -->
    <Dialog :open="!!renamingDocument" @update:open="(open: boolean) => { if (!open) renamingDocument = null; }">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.documents.rename') }}</DialogTitle>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitRename">
          <Input v-model="renameTitle" required maxlength="255" />
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="renamingDocument = null">
              {{ $t('Atšaukti') }}
            </Button>
            <Button type="submit" :disabled="documentSubmitting || !renameTitle.trim()">
              {{ $t('Išsaugoti') }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Workspace settings dialog -->
    <Dialog v-model:open="settingsOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('Redaguoti') }}</DialogTitle>
        </DialogHeader>
        <form class="space-y-4" @submit.prevent="submitSettings">
          <div class="space-y-1.5">
            <Label for="settings-name">{{ $t('workspaces.name') }}</Label>
            <Input id="settings-name" v-model="settingsForm.name" required maxlength="255" />
          </div>
          <div class="space-y-1.5">
            <Label for="settings-description">{{ $t('workspaces.description') }}</Label>
            <Textarea id="settings-description" v-model="settingsForm.description" rows="3" />
          </div>
          <div v-if="canManageMembers" class="space-y-1.5">
            <Label>{{ $t('workspaces.institution') }}</Label>
            <Select v-model="settingsForm.institution_id">
              <SelectTrigger>
                <SelectValue :placeholder="$t('workspaces.no_institution')" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem :value="NO_INSTITUTION">
                  {{ $t('workspaces.no_institution') }}
                </SelectItem>
                <SelectItem v-for="institution in settingsInstitutions" :key="institution.id" :value="institution.id">
                  {{ institution.name }}
                </SelectItem>
              </SelectContent>
            </Select>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">
              {{ $t('workspaces.institution_hint') }}
            </p>
          </div>
          <div class="flex justify-end gap-2">
            <Button type="button" variant="outline" @click="settingsOpen = false">
              {{ $t('Atšaukti') }}
            </Button>
            <Button type="submit" :disabled="settingsForm.processing">
              {{ $t('Išsaugoti') }}
            </Button>
          </div>
        </form>
      </DialogContent>
    </Dialog>

    <!-- Invite member dialog -->
    <Dialog v-model:open="inviteOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.members.invite') }}</DialogTitle>
        </DialogHeader>
        <div class="space-y-3">
          <Input
            v-model="memberSearch"
            :placeholder="$t('workspaces.members.search_placeholder')"
          />
          <div v-if="memberCandidates.length" class="max-h-56 space-y-1 overflow-y-auto">
            <button
              v-for="candidate in memberCandidates"
              :key="candidate.id"
              type="button"
              class="flex w-full items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
              @click="inviteMember(candidate)"
            >
              <UserAvatar :user="(candidate as any)" :size="24" />
              <span class="truncate">{{ candidate.name }}</span>
            </button>
          </div>
          <p v-else-if="memberSearch.length >= 3 && !memberSearchLoading" class="text-xs text-zinc-400">
            {{ $t('Naudotojų nerasta') }}
          </p>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Attach link dialog -->
    <Dialog v-model:open="attachLinkOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.links.add') }}</DialogTitle>
        </DialogHeader>
        <div class="space-y-3">
          <Select v-model="linkType">
            <SelectTrigger>
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="meeting">
                {{ $t('workspaces.links.type_meeting') }}
              </SelectItem>
              <SelectItem value="problem">
                {{ $t('workspaces.links.type_problem') }}
              </SelectItem>
            </SelectContent>
          </Select>
          <Input
            v-model="linkSearch"
            :placeholder="$t('workspaces.links.search_placeholder')"
          />
          <div v-if="linkCandidates.length" class="max-h-56 space-y-1 overflow-y-auto">
            <button
              v-for="candidate in linkCandidates"
              :key="candidate.id"
              type="button"
              class="flex w-full flex-col items-start rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-800"
              @click="attachLink(candidate)"
            >
              <span class="w-full truncate font-medium">{{ localized(candidate.label) }}</span>
              <span v-if="candidate.meta" class="w-full truncate text-xs text-zinc-400">{{ candidate.meta }}</span>
            </button>
          </div>
          <p v-else-if="linkSearch.length >= 2 && !linkSearchLoading" class="text-xs text-zinc-400">
            {{ $t('Nieko nerasta') }}
          </p>
        </div>
      </DialogContent>
    </Dialog>

    <!-- Delete confirm -->
    <Dialog v-model:open="deleteOpen">
      <DialogContent class="max-w-md">
        <DialogHeader>
          <DialogTitle>{{ $t('workspaces.delete') }}</DialogTitle>
        </DialogHeader>
        <p class="text-sm text-zinc-600 dark:text-zinc-400">
          {{ $t('workspaces.delete_confirm') }}
        </p>
        <div class="flex justify-end gap-2">
          <Button variant="outline" @click="deleteOpen = false">
            {{ $t('Atšaukti') }}
          </Button>
          <Button variant="destructive" @click="deleteWorkspace">
            {{ $t('Ištrinti') }}
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  </AdminContentPage>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head as InertiaHead, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { watchDebounced } from '@vueuse/core';
import { trans as $t, getActiveLanguage } from 'laravel-vue-i18n';
import {
  Archive, Edit, FileText, Landmark, Link as LinkIcon, MessagesSquare,
  MoreHorizontal, NotebookPen, Plus, ShieldCheck, Trash2, UserPlus, Users, X,
} from 'lucide-vue-next';

import UserAvatar from '@/Components/Avatars/UserAvatar.vue';
import DiscussionPanel from '@/Components/Discussions/DiscussionPanel.vue';
import ShowPageHero from '@/Components/Hero/ShowPageHero.vue';
import AdminContentPage from '@/Components/Layouts/AdminContentPage.vue';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/Components/ui/dialog';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/Components/ui/tabs';
import { Textarea } from '@/Components/ui/textarea';
import { usePageBreadcrumbs } from '@/Composables/useBreadcrumbsUnified';
import type { NotesMentionUser } from '@/Composables/useCollaborativeDocument';
import { useShowPageData } from '@/Composables/useShowPageData';
import WorkspaceDocumentDialog from '@/Pages/Admin/Workspaces/Components/WorkspaceDocumentDialog.vue';

interface WorkspaceDocumentItem {
  id: string;
  title: string;
  updated_at?: string | null;
  editor?: { id: string; name: string } | null;
}

interface WorkspaceMember {
  id: string;
  name: string;
  profile_photo_path?: string | null;
  pivot?: { role: string };
}

interface WorkspaceLinkItem {
  id: number;
  type: string | null;
  linkable: Record<string, unknown> | null;
  added_by?: { id: string; name: string } | null;
}

const props = defineProps<{
  workspace: {
    id: string;
    name: string;
    description?: string | null;
    institution?: { id: string; name: string } | null;
    members: WorkspaceMember[];
    documents: WorkspaceDocumentItem[];
    links: WorkspaceLinkItem[];
    institution_id?: string | null;
  };
  userInstitutions: Array<{ id: string; name: string }>;
  canManageMembers: boolean;
  canDelete: boolean;
}>();

const page = usePage();
const currentUser = computed(() => {
  const user = (page.props.auth as { user?: { id: string | number; name: string } } | undefined)?.user;
  return { id: user?.id ?? 'anonymous', name: user?.name ?? '' };
});

usePageBreadcrumbs(() => [
  { label: $t('workspaces.title'), href: route('workspaces.index'), icon: NotebookPen },
  { label: props.workspace.name },
]);

const { currentTab } = useShowPageData({
  tabKey: 'workspace',
  entityId: props.workspace.id,
  defaultTab: 'documents',
});

const NO_INSTITUTION = 'none';

// --- Localization helper (problem titles are translatable objects) ---

function localized(value: unknown): string {
  if (value && typeof value === 'object') {
    const record = value as Record<string, string>;
    const locale = getActiveLanguage() as 'lt' | 'en';
    return record[locale] || record.lt || record.en || '';
  }
  return String(value ?? '');
}

function formatDateTime(value?: string | null): string {
  return value ? new Date(value).toLocaleString('lt-LT', { dateStyle: 'medium', timeStyle: 'short' }) : '';
}

// --- Shared API fetch helper (JSON envelope, CSRF for mutations) ---

function csrfToken(): string {
  return ((page.props as { csrf_token?: string }).csrf_token) ?? '';
}

async function apiFetch(url: string, options: RequestInit = {}): Promise<any> {
  const response = await fetch(url, {
    credentials: 'same-origin',
    ...options,
    headers: {
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.method && options.method !== 'GET'
        ? { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken() }
        : {}),
      ...(options.headers ?? {}),
    },
  });
  if (!response.ok) {
    throw new Error(`Request failed: ${response.status}`);
  }
  return response.json();
}

function reloadWorkspace(): void {
  router.reload({ only: ['workspace'] });
}

// --- Mention pool for document editors (same audience as discussions) ---

const mentionUsers = ref<NotesMentionUser[]>([]);

void apiFetch(route('api.v1.admin.comments.mentionables', {
  commentableType: 'workspace',
  commentableId: props.workspace.id,
}))
  .then((json) => {
    if (Array.isArray(json?.data)) {
      mentionUsers.value = json.data;
    }
  })
  .catch(() => {});

// --- Documents ---

const openDocument = ref<WorkspaceDocumentItem | null>(null);
const createDocumentOpen = ref(false);
const newDocumentTitle = ref('');
const documentSubmitting = ref(false);
const renamingDocument = ref<WorkspaceDocumentItem | null>(null);
const renameTitle = ref('');

function closeDocument(): void {
  openDocument.value = null;
  reloadWorkspace();
}

async function submitCreateDocument(): Promise<void> {
  if (documentSubmitting.value) {
    return;
  }
  documentSubmitting.value = true;
  try {
    const json = await apiFetch(route('api.v1.admin.workspaces.documents.store', props.workspace.id), {
      method: 'POST',
      body: JSON.stringify({ title: newDocumentTitle.value.trim() }),
    });
    createDocumentOpen.value = false;
    newDocumentTitle.value = '';
    reloadWorkspace();
    if (json?.data?.id) {
      openDocument.value = { id: json.data.id, title: json.data.title };
    }
  }
  catch {
    // Validation errors surface via the disabled state; nothing else to do.
  }
  finally {
    documentSubmitting.value = false;
  }
}

function startRename(document: WorkspaceDocumentItem): void {
  renamingDocument.value = document;
  renameTitle.value = document.title;
}

async function submitRename(): Promise<void> {
  if (!renamingDocument.value || documentSubmitting.value) {
    return;
  }
  documentSubmitting.value = true;
  try {
    await apiFetch(route('api.v1.admin.workspaces.documents.update', {
      workspace: props.workspace.id,
      document: renamingDocument.value.id,
    }), {
      method: 'PATCH',
      body: JSON.stringify({ title: renameTitle.value.trim() }),
    });
    renamingDocument.value = null;
    reloadWorkspace();
  }
  catch {
    // Keep the dialog open so the user can retry.
  }
  finally {
    documentSubmitting.value = false;
  }
}

async function archiveDocument(document: WorkspaceDocumentItem): Promise<void> {
  if (!window.confirm($t('workspaces.documents.archive_confirm'))) {
    return;
  }
  try {
    await apiFetch(route('api.v1.admin.workspaces.documents.destroy', {
      workspace: props.workspace.id,
      document: document.id,
    }), { method: 'DELETE' });
    reloadWorkspace();
  }
  catch {
    // Silently keep the list; a failed archive leaves the document visible.
  }
}

// --- Workspace settings ---

const settingsOpen = ref(false);
const deleteOpen = ref(false);

const settingsForm = useForm({
  name: props.workspace.name,
  description: props.workspace.description ?? '',
  institution_id: props.workspace.institution?.id ?? NO_INSTITUTION,
});

// The currently attached institution may not be among the user's own; keep it
// selectable so saving without changes never detaches it.
const settingsInstitutions = computed(() => {
  const options = [...props.userInstitutions];
  const attached = props.workspace.institution;
  if (attached && !options.some(option => option.id === attached.id)) {
    options.unshift(attached);
  }
  return options;
});

function openSettings(): void {
  settingsForm.defaults({
    name: props.workspace.name,
    description: props.workspace.description ?? '',
    institution_id: props.workspace.institution?.id ?? NO_INSTITUTION,
  });
  settingsForm.reset();
  settingsOpen.value = true;
}

function submitSettings(): void {
  settingsForm
    .transform(data => ({
      ...data,
      institution_id: data.institution_id === NO_INSTITUTION ? null : data.institution_id,
    }))
    .patch(route('workspaces.update', props.workspace.id), {
      preserveScroll: true,
      onSuccess: () => {
        settingsOpen.value = false;
      },
    });
}

function deleteWorkspace(): void {
  router.delete(route('workspaces.destroy', props.workspace.id));
}

// --- Members ---

const inviteOpen = ref(false);
const memberSearch = ref('');
const memberSearchLoading = ref(false);
const memberCandidates = ref<Array<{ id: string; name: string; profile_photo_path?: string | null }>>([]);

watchDebounced(memberSearch, async (search) => {
  if (search.trim().length < 3) {
    memberCandidates.value = [];
    return;
  }
  memberSearchLoading.value = true;
  try {
    const json = await apiFetch(`${route('api.v1.admin.workspaces.memberCandidates', props.workspace.id)}?search=${encodeURIComponent(search.trim())}`);
    memberCandidates.value = Array.isArray(json?.data) ? json.data : [];
  }
  catch {
    memberCandidates.value = [];
  }
  finally {
    memberSearchLoading.value = false;
  }
}, { debounce: 300 });

function inviteMember(candidate: { id: string }): void {
  router.post(route('workspaces.members.add', props.workspace.id), {
    user_id: candidate.id,
    role: 'member',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      inviteOpen.value = false;
      memberSearch.value = '';
      memberCandidates.value = [];
    },
  });
}

function toggleRole(member: WorkspaceMember): void {
  router.patch(route('workspaces.members.update', { workspace: props.workspace.id, user: member.id }), {
    role: member.pivot?.role === 'admin' ? 'member' : 'admin',
  }, { preserveScroll: true });
}

function removeMember(member: WorkspaceMember): void {
  if (!window.confirm($t('workspaces.members.remove_confirm'))) {
    return;
  }
  router.delete(route('workspaces.members.remove', { workspace: props.workspace.id, user: member.id }), {
    preserveScroll: true,
  });
}

// --- Links ---

const attachLinkOpen = ref(false);
const linkType = ref<'meeting' | 'problem'>('meeting');
const linkSearch = ref('');
const linkSearchLoading = ref(false);
const linkCandidates = ref<Array<{ id: string; label: unknown; meta?: string | null }>>([]);

watchDebounced([linkSearch, linkType], async ([search]) => {
  if (String(search).trim().length < 2) {
    linkCandidates.value = [];
    return;
  }
  linkSearchLoading.value = true;
  try {
    const json = await apiFetch(`${route('api.v1.admin.workspaces.linkCandidates', props.workspace.id)}?type=${linkType.value}&search=${encodeURIComponent(String(search).trim())}`);
    linkCandidates.value = Array.isArray(json?.data) ? json.data : [];
  }
  catch {
    linkCandidates.value = [];
  }
  finally {
    linkSearchLoading.value = false;
  }
}, { debounce: 300 });

watch(attachLinkOpen, (open) => {
  if (!open) {
    linkSearch.value = '';
    linkCandidates.value = [];
  }
});

function attachLink(candidate: { id: string }): void {
  router.post(route('workspaces.links.attach', props.workspace.id), {
    linkable_type: linkType.value,
    linkable_id: candidate.id,
  }, {
    preserveScroll: true,
    onSuccess: () => {
      attachLinkOpen.value = false;
    },
  });
}

function detachLink(link: WorkspaceLinkItem): void {
  router.delete(route('workspaces.links.detach', { workspace: props.workspace.id, link: link.id }), {
    preserveScroll: true,
  });
}

function linkTypeIcon(type: string | null) {
  switch (type) {
    case 'meeting': return Users;
    case 'problem': return FileText;
    case 'institution': return Landmark;
    default: return LinkIcon;
  }
}

function linkLabel(link: WorkspaceLinkItem): string {
  const linkable = link.linkable;
  if (!linkable) {
    return '—';
  }
  return localized(linkable.title ?? linkable.name) || '—';
}

function linkHref(link: WorkspaceLinkItem): string | null {
  if (!link.linkable) {
    return null;
  }
  const id = String(link.linkable.id);
  switch (link.type) {
    case 'meeting': return route('meetings.show', id);
    case 'problem': return route('problems.show', id);
    case 'institution': return route('institutions.show', id);
    case 'agendaItem': return route('agendaItems.show', id);
    default: return null;
  }
}
</script>
