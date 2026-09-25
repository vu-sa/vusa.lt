<template>
  <div class="space-y-6">
    <!-- Loading state while the step's lazily-loaded data arrives -->
    <div v-if="isLoadingStepData" class="flex flex-col items-center justify-center py-12 space-y-3">
      <Loader2 class="size-6 animate-spin text-brand" />
      <div class="text-center">
        <p class="font-bold text-sm text-foreground">
          {{ $t('Kraunami duomenys...') }}
        </p>
        <p class="text-xs text-muted-foreground">
          {{ $t('Prašome palaukti') }}
        </p>
      </div>
    </div>

    <template v-else>
      <!-- Selected duty header -->
      <div class="flex flex-wrap items-center justify-between gap-3 p-3.5 sm:p-4 border border-border bg-muted/40">
        <div class="flex items-center gap-3 min-w-0">
          <div class="flex size-9 items-center justify-center border border-border bg-background shrink-0">
            <DutyIcon class="size-4 text-foreground" />
          </div>
          <div class="min-w-0">
            <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
              {{ $t('Pasirinkta pareigybė') }}
            </p>
            <p class="min-w-0 font-semibold text-sm text-foreground truncate">
              <InflectedDutyName v-if="wizard.state.duty" :name="wizard.state.duty.name" />
            </p>
            <p class="text-xs text-muted-foreground truncate">
              {{ wizard.state.institution?.name }}
            </p>
          </div>
        </div>

        <div class="flex items-center gap-4">
          <div class="text-right">
            <p class="text-[11px] text-muted-foreground font-medium">
              {{ $t('Vietų skaičius') }}
            </p>
            <p class="font-bold text-sm text-foreground">
              {{ currentUsers.length }} / {{ wizard.state.duty?.places_to_occupy || '?' }}
            </p>
            <p v-if="isExternalDuty" class="text-[11px] text-muted-foreground">
              {{ $t('forms.fields.tenant_quota') }}: {{ tenantQuota ?? '∞' }}
            </p>
          </div>
          <Button variant="outline" size="sm" @click="wizard.previousStep()">
            <Edit3 class="size-3.5 mr-1.5" />
            {{ $t('Keisti') }}
          </Button>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Left: Current members -->
        <div class="space-y-3.5">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-2">
              <UserMinus class="size-4 text-muted-foreground" />
              {{ $t('Dabartiniai nariai') }}
              <span class="inline-flex items-center border border-border bg-muted px-1.5 py-0.5 text-[10px] font-bold text-muted-foreground">
                {{ currentUsers.length }}
              </span>
            </h3>
          </div>

          <ScrollArea class="h-[240px] sm:h-[280px] pr-2">
            <div class="space-y-2">
              <!-- Remaining users -->
              <div
                v-for="user in remainingCurrentUsers"
                :key="user.id"
                class="flex items-center gap-3 p-3 border border-border bg-card hover:bg-muted/40 transition-colors group pointer-coarse:py-3.5"
              >
                <div class="size-8 border border-border bg-muted flex items-center justify-center overflow-hidden shrink-0">
                  <img
                    v-if="user.profile_photo_path"
                    :src="user.profile_photo_path"
                    :alt="user.name"
                    class="size-full object-cover"
                  >
                  <span v-else class="text-xs font-bold text-muted-foreground">{{ user.name?.charAt(0) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm truncate text-foreground">
                    {{ user.name }}
                  </p>
                  <p class="text-xs text-muted-foreground truncate">
                    {{ user.email }}
                  </p>
                </div>
                <!-- Remove button: visible on touch devices, hover-only on desktop -->
                <Button
                  variant="ghost"
                  size="icon"
                  class="size-8 text-destructive hover:text-destructive hover:bg-destructive/10 transition-opacity sm:opacity-0 sm:group-hover:opacity-100 pointer-coarse:opacity-100 pointer-coarse:size-11"
                  :title="$t('Pašalinti narį')"
                  @click="handleRemoveUser(user)"
                >
                  <UserMinus class="size-4" />
                </Button>
              </div>

              <!-- Users being removed -->
              <div
                v-for="change in usersToRemove"
                :key="change.userId"
                class="flex items-center gap-3 p-3 border border-destructive/30 bg-destructive/5"
              >
                <div class="size-8 border border-destructive/30 bg-destructive/10 flex items-center justify-center overflow-hidden shrink-0">
                  <img
                    v-if="change.userPhoto"
                    :src="change.userPhoto"
                    :alt="change.userName"
                    class="size-full object-cover opacity-50"
                  >
                  <span v-else class="text-xs font-bold text-destructive">{{ change.userName?.charAt(0) }}</span>
                </div>
                <div class="flex-1 min-w-0">
                  <p class="font-semibold text-sm text-destructive line-through truncate">
                    {{ change.userName }}
                  </p>
                  <div class="flex items-center gap-2 mt-1">
                    <Label class="text-[11px] text-muted-foreground font-medium">{{ $t('Pabaigos data:') }}</Label>
                    <Input
                      type="date"
                      :model-value="change.endDate"
                      class="h-7 w-32 text-xs"
                      @update:model-value="(v) => updateChangeDate(change.userId, 'endDate', String(v))"
                    />
                  </div>
                </div>
                <Button
                  variant="ghost"
                  size="icon"
                  class="size-8 pointer-coarse:size-11"
                  :title="$t('Atšaukti pašalinimą')"
                  @click="cancelRemoval(change.userId)"
                >
                  <X class="size-4" />
                </Button>
              </div>

              <!-- Empty state -->
              <div v-if="currentUsers.length === 0" class="text-center py-8 text-muted-foreground border border-dashed border-border p-4">
                <p class="text-xs">
                  {{ $t('Ši pareigybė neturi narių') }}
                </p>
              </div>
            </div>
          </ScrollArea>
        </div>

        <!-- Right: Add new members -->
        <div class="space-y-3.5">
          <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-2">
              <UserPlus class="size-4 text-muted-foreground" />
              {{ $t('Pridėti narius') }}
              <span v-if="usersToAdd.length > 0" class="inline-flex items-center border border-status-success-border bg-status-success-surface px-1.5 py-0.5 text-[10px] font-bold text-status-success">
                {{ usersToAdd.length }}
              </span>
            </h3>
          </div>

          <p v-if="quotaReached" class="text-xs text-status-attention flex items-center gap-1.5 border border-status-attention-border bg-status-attention-surface/40 p-2">
            <AlertTriangle class="size-3.5 shrink-0" />
            <span>{{ $t('forms.fields.tenant_quota') }}: {{ tenantQuota }} — {{ $t('Pasiekta padalinio kvota') }}</span>
          </p>

          <!-- User search -->
          <div class="space-y-1">
            <div class="relative h-10">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-muted-foreground pointer-events-none" />
              <Input
                v-model="userSearchQuery"
                :disabled="quotaReached"
                :placeholder="$t('Įveskite vardą arba el. paštą...')"
                class="pl-9 pr-8 h-10"
                @focus="showUserSearch = true"
              />
              <Button
                v-if="userSearchQuery"
                variant="ghost"
                size="icon"
                class="absolute right-1 top-1/2 -translate-y-1/2 size-8"
                @click="userSearchQuery = ''"
              >
                <X class="size-3.5" />
              </Button>

              <!-- Search dropdown -->
              <div
                v-if="showUserSearch && userSearchQuery.length >= 2"
                class="absolute top-full left-0 right-0 z-50 mt-1 max-h-52 overflow-y-auto border border-border bg-popover divide-y divide-border"
              >
                <button
                  v-for="user in availableUsers"
                  :key="user.id"
                  type="button"
                  class="w-full flex items-center gap-3 p-3 text-left hover:bg-accent transition-colors pointer-coarse:py-3.5"
                  @click="handleAddUser(user)"
                >
                  <div class="size-8 border border-border bg-muted flex items-center justify-center overflow-hidden shrink-0">
                    <img
                      v-if="user.profile_photo_path"
                      :src="user.profile_photo_path"
                      :alt="user.name"
                      class="size-full object-cover"
                    >
                    <span v-else class="text-xs font-bold text-muted-foreground">{{ user.name?.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="flex items-center gap-1.5 font-semibold text-sm truncate text-foreground">
                      {{ user.name }}
                      <Badge v-if="user.duties_count === 0" variant="outline" class="text-[10px] shrink-0">
                        {{ $t('users.no_tenant') }}
                      </Badge>
                      <Badge v-else-if="user.tenants?.length" variant="secondary" class="text-[10px] shrink-0">
                        {{ user.tenants.join(', ') }}
                      </Badge>
                    </p>
                    <p class="text-xs text-muted-foreground truncate">
                      {{ user.email }}
                    </p>
                  </div>
                  <Plus class="size-4 text-foreground shrink-0" />
                </button>

                <div v-if="isSearchingUsers" class="p-4 text-center text-xs text-muted-foreground flex items-center justify-center gap-2">
                  <Loader2 class="size-3.5 animate-spin" />
                  {{ $t('Ieškoma...') }}
                </div>
                <div v-else-if="availableUsers.length === 0" class="p-4 text-center text-xs text-muted-foreground">
                  {{ $t('Nerasta naudotojų') }}
                </div>
              </div>
            </div>
            <p v-if="!userSearchQuery && !showUserSearch" class="text-xs text-muted-foreground">
              {{ $t('Pradėkite rašyti, kad rastumėte naudotoją') }}
            </p>
          </div>

          <!-- Batch date setting -->
          <Collapsible v-model:open="showBatchDateSetter" class="border border-border bg-muted/20">
            <CollapsibleTrigger as-child>
              <Button variant="ghost" size="sm" class="w-full justify-between h-9 px-3">
                <span class="flex items-center gap-2 text-xs font-semibold">
                  <Calendar class="size-3.5" />
                  {{ $t('Datos nustatymai') }}
                </span>
                <ChevronDown class="size-4 transition-transform" :class="{ 'rotate-180': showBatchDateSetter }" />
              </Button>
            </CollapsibleTrigger>
            <CollapsibleContent class="p-3 pt-0 space-y-3 border-t border-border mt-1">
              <div class="grid grid-cols-2 gap-3 pt-2">
                <div>
                  <Label class="text-[11px] font-medium">{{ $t('Pradžios data') }}</Label>
                  <div class="flex gap-1 mt-1">
                    <Input v-model="batchStartDate" type="date" class="h-8 text-xs" />
                    <Button size="sm" variant="secondary" class="h-8 px-2" @click="applyBatchStartDate">
                      <Check class="size-3.5" />
                    </Button>
                  </div>
                </div>
                <div>
                  <Label class="text-[11px] font-medium">{{ $t('Pabaigos data') }}</Label>
                  <div class="flex gap-1 mt-1">
                    <Input v-model="batchEndDate" type="date" class="h-8 text-xs" />
                    <Button size="sm" variant="secondary" class="h-8 px-2" @click="applyBatchEndDate">
                      <Check class="size-3.5" />
                    </Button>
                  </div>
                </div>
              </div>
              <p class="text-xs text-muted-foreground">
                {{ $t('Siūloma pabaigos data: ') }}
                <strong>{{ formatDateForDisplay(getSuggestedEndDate()) }}</strong>
                {{ $t('(liepos 1 d.)') }}
              </p>
            </CollapsibleContent>
          </Collapsible>

          <!-- Users being added -->
          <ScrollArea class="h-[150px] sm:h-[180px] pr-2">
            <div class="space-y-2">
              <div
                v-for="change in usersToAdd"
                :key="change.userId"
                class="p-3 border border-status-success-border bg-status-success-surface/20"
              >
                <div class="flex items-center gap-3">
                  <div class="size-8 border border-status-success-border bg-status-success-surface flex items-center justify-center overflow-hidden shrink-0">
                    <img
                      v-if="change.userPhoto"
                      :src="change.userPhoto"
                      :alt="change.userName"
                      class="size-full object-cover"
                    >
                    <span v-else class="text-xs font-bold text-status-success">{{ change.userName?.charAt(0) }}</span>
                  </div>
                  <div class="flex-1 min-w-0">
                    <p class="font-semibold text-sm truncate text-foreground">
                      {{ change.userName }}
                      <Badge v-if="change.isNewUser" variant="outline" class="ml-1 text-[10px]">
                        {{ $t('Naujas') }}
                      </Badge>
                    </p>
                    <p class="text-xs text-muted-foreground truncate">
                      {{ change.userEmail }}
                    </p>
                  </div>
                  <Button
                    variant="ghost"
                    size="icon"
                    class="size-8 pointer-coarse:size-11"
                    :title="$t('Atšaukti pridėjimą')"
                    @click="cancelAddition(change.userId)"
                  >
                    <X class="size-4" />
                  </Button>
                </div>

                <!-- Date inputs -->
                <div class="mt-3 grid grid-cols-2 gap-2">
                  <div>
                    <Label class="text-[11px] text-muted-foreground font-medium">{{ $t('Pradžia') }}</Label>
                    <Input
                      type="date"
                      :model-value="change.startDate"
                      class="h-7 text-xs mt-0.5"
                      :class="{ 'border-destructive focus-visible:ring-destructive': hasDateError(change) }"
                      @update:model-value="(v) => updateChangeDate(change.userId, 'startDate', String(v))"
                    />
                  </div>
                  <div>
                    <Label class="text-[11px] text-muted-foreground font-medium">{{ $t('Pabaiga') }}</Label>
                    <Input
                      type="date"
                      :model-value="change.endDate"
                      class="h-7 text-xs mt-0.5"
                      :class="{ 'border-destructive focus-visible:ring-destructive': hasDateError(change) }"
                      @update:model-value="(v) => updateChangeDate(change.userId, 'endDate', String(v))"
                    />
                  </div>
                </div>
                <!-- Date error message -->
                <p v-if="hasDateError(change)" class="text-xs text-destructive mt-1 flex items-center gap-1">
                  <AlertTriangle class="size-3.5" />
                  {{ $t('Pabaigos data negali būti ankstesnė nei pradžios data') }}
                </p>
              </div>

              <!-- Empty state -->
              <div v-if="usersToAdd.length === 0" class="text-center py-6 text-muted-foreground border border-dashed border-border p-4">
                <UserPlus class="size-6 mx-auto mb-1.5 opacity-50" />
                <p class="text-xs">
                  {{ $t('Ieškokite ir pridėkite narius') }}
                </p>
              </div>
            </div>
          </ScrollArea>

          <!-- Create new user inline -->
          <div v-if="canCreateUser" class="pt-2">
            <Collapsible v-model:open="showNewUserForm" class="border border-border">
              <CollapsibleTrigger as-child>
                <Button variant="outline" size="sm" class="w-full">
                  <Plus class="size-3.5 mr-1.5" />
                  {{ $t('Sukurti naują naudotoją') }}
                </Button>
              </CollapsibleTrigger>
              <CollapsibleContent class="p-3.5 space-y-3 border-t border-border mt-1 bg-muted/20">
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">{{ $t('Vardas ir pavardė') }} *</Label>
                  <Input v-model="newUser.name" :placeholder="$t('Jonas Jonaitis')" class="h-8" />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">{{ $t('El. paštas') }} *</Label>
                  <Input v-model="newUser.email" type="email" placeholder="jonas@stud.vu.lt" class="h-8" />
                </div>
                <div class="space-y-1.5">
                  <Label class="text-xs font-semibold">{{ $t('Telefonas') }}</Label>
                  <Input v-model="newUser.phone" placeholder="+370 600 00000" class="h-8" />
                </div>
                <DuplicateUserWarning :matches="duplicateMatches" show-use-action @use="useExistingProfile" />
                <div class="flex gap-2 pt-1">
                  <Button
                    size="sm"
                    variant="brand"
                    :disabled="!newUser.name || !newUser.email"
                    @click="createNewUser"
                  >
                    <Check class="size-3.5 mr-1" />
                    {{ $t('Pridėti') }}
                  </Button>
                  <Button variant="ghost" size="sm" @click="showNewUserForm = false">
                    {{ $t('Atšaukti') }}
                  </Button>
                </div>
              </CollapsibleContent>
            </Collapsible>
          </div>
        </div>
      </div>

      <!-- Summary footer -->
      <div v-if="wizard.hasChanges" class="flex flex-wrap items-center justify-between gap-3 p-3.5 border border-border bg-muted/40">
        <div class="flex items-center gap-4 text-xs font-semibold">
          <span v-if="usersToAdd.length > 0" class="text-status-success flex items-center gap-1">
            <UserPlus class="size-3.5" />
            +{{ usersToAdd.length }}
          </span>
          <span v-if="usersToRemove.length > 0" class="text-destructive flex items-center gap-1">
            <UserMinus class="size-3.5" />
            -{{ usersToRemove.length }}
          </span>
          <Separator orientation="vertical" class="h-4" />
          <span class="text-muted-foreground font-normal">
            {{ $t('Naujas vietų skaičius:') }}
            <strong class="text-foreground ml-1">{{ wizard.projectedUserCount.value }}</strong>
            <span v-if="wizard.capacityMismatch.value" class="text-status-attention ml-1.5 inline-flex items-center">
              <AlertTriangle class="size-3.5 inline" />
            </span>
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, inject, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { useDebounceFn } from '@vueuse/core';
import {
  Search,
  UserPlus,
  UserMinus,
  X,
  Edit3,
  Calendar,
  ChevronDown,
  Plus,
  Check,
  AlertTriangle,
  Loader2,
} from 'lucide-vue-next';

import { Input } from '@/Components/ui/input';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { ScrollArea } from '@/Components/ui/scroll-area';
import { Separator } from '@/Components/ui/separator';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { Label } from '@/Components/ui/label';
import DuplicateUserWarning from '@/Components/AdminForms/DuplicateUserWarning.vue';
import { useApi } from '@/Composables/useApi';
import { useDuplicateUserCheck } from '@/Composables/useDuplicateUserCheck';
import { getSuggestedEndDate, getTodayDate, formatDateForDisplay } from '@/Composables/useDutyUserWizard';
import type { useDutyUserWizard, UserChange, NewUserData } from '@/Composables/useDutyUserWizard';
import { DutyIcon } from '@/Components/icons';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!;

const page = usePage();
const auth = page.props.auth as any;

const isLoadingStepData = computed(() => wizard.state.loading.stepData);

const userSearchQuery = ref('');
const showUserSearch = ref(false);

interface UserSearchResult {
  id: string;
  name: string;
  email: string;
  profile_photo_path: string | null;
  duties_count: number;
  tenants: string[];
}

const userSearchUrl = ref('');
const { data: searchedUsers, isFetching: isSearchingUsers, execute: executeUserSearch } = useApi<UserSearchResult[]>(
  userSearchUrl,
  { immediate: false, showErrorToast: false },
);

const runUserSearch = useDebounceFn(() => {
  if (userSearchQuery.value.trim().length < 2) {
    userSearchUrl.value = '';
    return;
  }

  const params = new URLSearchParams({
    search: userSearchQuery.value.trim(),
    permission: 'duties.update.padalinys',
    scope: 'all',
  });
  userSearchUrl.value = `${route('api.v1.admin.users.search')}?${params.toString()}`;
  executeUserSearch();
}, 300);

watch(userSearchQuery, runUserSearch);

const showNewUserForm = ref(false);
const newUser = ref<NewUserData>({
  name: '',
  email: '',
  phone: '',
});

const batchStartDate = ref(getTodayDate());
const batchEndDate = ref(getSuggestedEndDate());
const showBatchDateSetter = ref(false);

const currentUsers = computed(() => {
  return wizard.state.duty?.current_users || [];
});

const assignableTenantPivot = computed<{ quota?: number | null } | null>(() => {
  const list = (wizard.state.duty as { assignable_tenants?: Array<{ pivot?: { quota?: number | null } }> } | undefined)?.assignable_tenants;
  return list && list.length > 0 ? (list[0].pivot ?? null) : null;
});
const isExternalDuty = computed(() => assignableTenantPivot.value !== null);
const tenantQuota = computed<number | null>(() => assignableTenantPivot.value?.quota ?? null);

const availableUsers = computed(() => {
  const currentUserIds = currentUsers.value.map(u => u.id);
  const addedUserIds = wizard.state.userChanges
    .filter(c => c.action === 'add')
    .map(c => c.userId);

  const excludeIds = new Set([...currentUserIds, ...addedUserIds]);

  return (searchedUsers.value ?? []).filter(u => !excludeIds.has(u.id));
});

const usersToAdd = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'add');
});

const projectedTenantMemberCount = computed(() => {
  const removeIds = new Set(wizard.state.userChanges.filter(c => c.action === 'remove').map(c => c.userId));
  const remaining = currentUsers.value.filter(u => !removeIds.has(u.id)).length;
  return remaining + usersToAdd.value.length;
});
const quotaReached = computed(() =>
  isExternalDuty.value && tenantQuota.value !== null && projectedTenantMemberCount.value >= tenantQuota.value,
);

const usersToRemove = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'remove');
});

const remainingCurrentUsers = computed(() => {
  const removeIds = new Set(usersToRemove.value.map(c => c.userId));
  return currentUsers.value.filter(u => !removeIds.has(u.id));
});

const handleAddUser = (user: UserSearchResult) => {
  if (quotaReached.value) return;
  wizard.addUserToAdd({
    id: user.id,
    name: user.name,
    profile_photo_path: user.profile_photo_path,
  } as App.Entities.User, {
    startDate: batchStartDate.value,
    endDate: batchEndDate.value,
  });
  userSearchQuery.value = '';
  showUserSearch.value = false;
};

const handleRemoveUser = (user: any) => {
  wizard.addUserToRemove(user as App.Entities.User, getTodayDate());
};

const cancelRemoval = (userId: string) => {
  wizard.removeUserChange(userId);
};

const cancelAddition = (userId: string) => {
  wizard.removeUserChange(userId);
};

const updateChangeDate = (userId: string, field: 'startDate' | 'endDate', value: string) => {
  wizard.updateUserChange(userId, { [field]: value });
};

const applyBatchStartDate = () => {
  wizard.setAllAddedUsersStartDate(batchStartDate.value);
};

const applyBatchEndDate = () => {
  wizard.setAllAddedUsersEndDate(batchEndDate.value);
};

const createNewUser = () => {
  if (!newUser.value.name || !newUser.value.email) return;

  const tempId = `new-${Date.now()}`;

  wizard.addNewUserToCreate({ ...newUser.value, temp_id: tempId });

  const tempUser = {
    id: tempId,
    name: newUser.value.name,
    email: newUser.value.email,
    profile_photo_path: null,
  } as App.Entities.User;

  wizard.addUserToAdd(tempUser, {
    startDate: batchStartDate.value,
    endDate: batchEndDate.value,
  });

  wizard.updateUserChange(tempId, { isNewUser: true });

  newUser.value = { name: '', email: '', phone: '' };
  showNewUserForm.value = false;
};

const { matches: duplicateMatches } = useDuplicateUserCheck(
  () => newUser.value.name ?? '',
  () => newUser.value.email ?? '',
);

const useExistingProfile = (match: { id: string; name: string }) => {
  if (quotaReached.value) return;

  wizard.addUserToAdd({ id: match.id, name: match.name, profile_photo_path: null } as App.Entities.User, {
    startDate: batchStartDate.value,
    endDate: batchEndDate.value,
  });

  newUser.value = { name: '', email: '', phone: '' };
  showNewUserForm.value = false;
};

const canCreateUser = computed(() => Boolean(auth?.can?.create?.user));

const hasDateError = (change: UserChange) => {
  if (change.action !== 'add' || !change.startDate || !change.endDate) return false;
  return new Date(change.endDate) < new Date(change.startDate);
};
</script>
