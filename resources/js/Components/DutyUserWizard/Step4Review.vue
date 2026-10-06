<template>
  <div class="space-y-6">
    <!-- Summary header -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3.5 sm:gap-4">
      <!-- Institution -->
      <div class="p-3.5 sm:p-4 border border-border bg-card flex items-center gap-3">
        <div class="flex size-9 items-center justify-center border border-border bg-muted shrink-0">
          <Building2 class="size-4 text-foreground" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('Institucija') }}
          </p>
          <p class="font-semibold text-sm text-foreground truncate">
            {{ wizard.state.institution?.name }}
          </p>
        </div>
      </div>

      <!-- Duty -->
      <div class="p-3.5 sm:p-4 border border-border bg-card flex items-center gap-3">
        <div class="flex size-9 items-center justify-center border border-border bg-muted shrink-0">
          <DutyIcon class="size-4 text-foreground" />
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-[11px] font-bold uppercase tracking-wider text-muted-foreground">
            {{ $t('Pareigybė') }}
          </p>
          <p class="min-w-0 font-semibold text-sm text-foreground truncate">
            <InflectedDutyName v-if="wizard.state.duty" :name="wizard.state.duty.name" />
          </p>
        </div>
      </div>

      <!-- Capacity -->
      <div
        class="p-3.5 sm:p-4 border flex items-center justify-between gap-3 transition-colors"
        :class="{
          'border-border bg-card': !hasCapacityMismatch,
          'border-status-attention-border bg-status-attention-surface/30': hasCapacityMismatch
        }"
      >
        <div class="flex items-center gap-3 min-w-0">
          <div
            class="flex size-9 items-center justify-center border shrink-0"
            :class="{
              'border-border bg-muted text-foreground': !hasCapacityMismatch,
              'border-status-attention-border bg-status-attention-surface text-status-attention': hasCapacityMismatch
            }"
          >
            <Users class="size-4" />
          </div>
          <div class="min-w-0">
            <p
              class="text-[11px] font-bold uppercase tracking-wider"
              :class="hasCapacityMismatch ? 'text-status-attention' : 'text-muted-foreground'"
            >
              {{ $t('Nariai') }}
            </p>
            <p class="font-semibold text-sm text-foreground truncate">
              {{ currentCount }} <ArrowRight class="size-3 inline mx-0.5" /> {{ projectedCount }} / {{ targetCapacity }}
            </p>
          </div>
        </div>

        <Button
          v-if="hasCapacityMismatch && !isEditingCapacity && !isExternalDuty"
          variant="outline"
          size="sm"
          @click="startEditingCapacity"
        >
          <Edit3 class="size-3.5 mr-1" />
          {{ $t('Keisti') }}
        </Button>
      </div>
    </div>

    <!-- Capacity mismatch alert -->
    <div
      v-if="hasCapacityMismatch && !isEditingCapacity && !isExternalDuty"
      class="border border-status-attention-border bg-status-attention-surface/40 p-3.5 text-xs text-foreground flex items-start gap-3"
    >
      <AlertTriangle class="size-4 text-status-attention shrink-0 mt-0.5" />
      <div class="flex-1">
        <p class="font-bold text-status-attention text-xs">
          {{ $t('Vietų skaičiaus neatitikimas') }}
        </p>
        <p class="mt-0.5 text-foreground">
          {{ $t('Bus') }} <strong>{{ projectedCount }}</strong> {{ $t('nariai, bet nurodyta') }} <strong>{{ targetCapacity }}</strong>.
          <button
            type="button"
            class="ml-1.5 underline font-bold text-status-attention hover:text-foreground transition-colors"
            @click="startEditingCapacity"
          >
            {{ $t('Atnaujinti vietų skaičių?') }}
          </button>
        </p>
      </div>
    </div>

    <!-- Capacity editor -->
    <div v-if="isEditingCapacity && !isExternalDuty" class="border border-border bg-muted/20 p-3.5">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-2">
          <Label class="text-xs font-semibold">{{ $t('Vietų skaičius') }}:</Label>
          <Input
            v-model.number="newCapacity"
            type="number"
            min="1"
            class="w-20 h-8 text-xs font-bold"
          />
          <Button size="sm" variant="brand" @click="handleUpdateCapacity">
            {{ $t('Patvirtinti') }}
          </Button>
          <Button variant="ghost" size="sm" @click="cancelEditingCapacity">
            {{ $t('Atšaukti') }}
          </Button>
        </div>
        <div class="text-xs text-muted-foreground">
          {{ $t('Siūloma pagal narių skaičių:') }} <strong class="text-foreground">{{ projectedCount }}</strong>
        </div>
      </div>
    </div>

    <Separator />

    <!-- Changes summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
      <!-- Users being added -->
      <div class="border border-status-success-border bg-card">
        <div class="border-b border-status-success-border bg-status-success-surface/40 px-4 py-2.5 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-status-success flex items-center gap-2">
            <UserPlus class="size-4" />
            {{ $t('Pridedami nariai') }}
          </h3>
          <span class="inline-flex items-center border border-status-success-border bg-status-success-surface px-1.5 py-0.5 text-[10px] font-bold text-status-success">
            {{ usersToAdd.length }}
          </span>
        </div>

        <div v-if="usersToAdd.length === 0" class="p-6 text-center text-muted-foreground text-xs">
          {{ $t('Nėra pridedamų narių') }}
        </div>
        <div v-else class="divide-y divide-border max-h-[240px] overflow-y-auto">
          <div
            v-for="change in usersToAdd"
            :key="change.userId"
            class="p-3 flex items-center gap-3"
          >
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
              <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                <Calendar class="size-3" />
                <span>{{ formatDateForDisplay(change.startDate || '') }}</span>
                <span>→</span>
                <span>{{ formatDateForDisplay(change.endDate || '') }}</span>
              </div>
              <p v-if="change.studyProgramName" class="text-xs text-muted-foreground mt-0.5">
                {{ change.studyProgramName }}
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Users being removed -->
      <div class="border border-destructive/30 bg-card">
        <div class="border-b border-destructive/30 bg-destructive/5 px-4 py-2.5 flex items-center justify-between">
          <h3 class="text-xs font-bold uppercase tracking-wider text-destructive flex items-center gap-2">
            <UserMinus class="size-4" />
            {{ $t('Šalinami nariai') }}
          </h3>
          <span class="inline-flex items-center border border-destructive/30 bg-destructive/10 px-1.5 py-0.5 text-[10px] font-bold text-destructive">
            {{ usersToRemove.length }}
          </span>
        </div>

        <div v-if="usersToRemove.length === 0" class="p-6 text-center text-muted-foreground text-xs">
          {{ $t('Nėra šalinamų narių') }}
        </div>
        <div v-else class="divide-y divide-border max-h-[240px] overflow-y-auto">
          <div
            v-for="change in usersToRemove"
            :key="change.userId"
            class="p-3 flex items-center gap-3"
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
              <p class="font-semibold text-sm truncate line-through text-destructive">
                {{ change.userName }}
              </p>
              <div class="flex items-center gap-1.5 text-xs text-muted-foreground mt-0.5">
                <Calendar class="size-3" />
                <span>{{ $t('Pabaiga:') }} {{ formatDateForDisplay(change.endDate || '') }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- New users to create -->
    <div v-if="newUsersToCreate.length > 0" class="border border-border bg-card">
      <div class="border-b border-border bg-muted/40 px-4 py-2.5 flex items-center justify-between">
        <h3 class="text-xs font-bold uppercase tracking-wider text-foreground flex items-center gap-2">
          <UserPlus class="size-4" />
          {{ $t('Nauji naudotojai bus sukurti') }}
        </h3>
        <span class="inline-flex items-center border border-border bg-muted px-1.5 py-0.5 text-[10px] font-bold text-foreground">
          {{ newUsersToCreate.length }}
        </span>
      </div>

      <div class="divide-y divide-border max-h-[180px] overflow-y-auto">
        <div
          v-for="(user, index) in newUsersToCreate"
          :key="index"
          class="p-3 flex items-center gap-3"
        >
          <div class="size-8 border border-border bg-muted flex items-center justify-center shrink-0">
            <span class="text-xs font-bold text-foreground">{{ user.name?.charAt(0) }}</span>
          </div>
          <div class="flex-1 min-w-0">
            <p class="font-semibold text-sm truncate text-foreground">
              {{ user.name }}
            </p>
            <p class="text-xs text-muted-foreground truncate">
              {{ user.email }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Final confirmation notice -->
    <div class="border border-border bg-muted/30 p-3.5 flex items-center gap-3 text-xs text-muted-foreground">
      <CheckCircle class="size-4 text-foreground shrink-0" />
      <span>{{ $t('Paspaudus "Patvirtinti", pakeitimai bus išsaugoti. Šią operaciją galima atšaukti tik rankiniu būdu.') }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, inject, ref } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import {
  UserPlus,
  UserMinus,
  Building2,
  Calendar,
  AlertTriangle,
  CheckCircle,
  Edit3,
  Users,
  ArrowRight,
} from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import { Separator } from '@/Components/ui/separator';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { formatDateForDisplay } from '@/Composables/useDutyUserWizard';
import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard';
import { DutyIcon } from '@/Components/icons';
import InflectedDutyName from '@/Components/Duties/InflectedDutyName.vue';

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!;

const isEditingCapacity = ref(false);
const newCapacity = ref(wizard.state.duty?.places_to_occupy || 0);

const isExternalDuty = computed(() => {
  const list = (wizard.state.duty as { assignable_tenants?: unknown[] } | undefined)?.assignable_tenants;
  return Array.isArray(list) && list.length > 0;
});

const usersToAdd = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'add');
});

const usersToRemove = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'remove');
});

const newUsersToCreate = computed(() => {
  return wizard.state.newUsersToCreate;
});

const currentCount = computed(() => wizard.state.duty?.current_users?.length || 0);
const projectedCount = computed(() => wizard.projectedUserCount.value);
const targetCapacity = computed(() => wizard.targetCapacity.value);
const hasCapacityMismatch = computed(() => wizard.capacityMismatch.value);

const handleUpdateCapacity = () => {
  wizard.setNewPlacesToOccupy(newCapacity.value);
  isEditingCapacity.value = false;
};

const startEditingCapacity = () => {
  newCapacity.value = wizard.targetCapacity.value;
  isEditingCapacity.value = true;
};

const cancelEditingCapacity = () => {
  isEditingCapacity.value = false;
  newCapacity.value = wizard.state.duty?.places_to_occupy || 0;
};
</script>
