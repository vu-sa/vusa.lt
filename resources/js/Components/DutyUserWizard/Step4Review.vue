<template>
  <div class="space-y-6">
    <!-- Summary header -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <!-- Institution -->
      <div class="p-4 rounded-xl bg-gradient-to-br from-vusa-yellow/10 to-amber-50/50 dark:from-vusa-yellow-dark/20 dark:to-amber-950/20 border border-vusa-yellow/30 dark:border-vusa-yellow-dark/30">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-lg bg-vusa-yellow/20 dark:bg-vusa-yellow-dark/30 flex items-center justify-center">
            <Building2 class="h-5 w-5 text-vusa-yellow-dark dark:text-vusa-yellow" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-vusa-yellow-dark/70 dark:text-vusa-yellow/70">
              {{ $t('Institucija') }}
            </p>
            <p class="font-medium text-vusa-yellow-dark dark:text-vusa-yellow truncate">
              {{ wizard.state.institution?.name }}
            </p>
          </div>
        </div>
      </div>

      <!-- Duty -->
      <div class="p-4 rounded-xl bg-gradient-to-br from-zinc-50 to-slate-50/50 dark:from-zinc-900/50 dark:to-slate-950/20 border border-zinc-100 dark:border-zinc-800">
        <div class="flex items-center gap-3">
          <div class="h-10 w-10 rounded-lg bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center">
            <Icons.DUTY class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-xs text-zinc-600/70 dark:text-zinc-400/70">
              {{ $t('Pareigybė') }}
            </p>
            <p class="font-medium text-zinc-900 dark:text-zinc-100 truncate">
              {{ wizard.state.duty?.name }}
            </p>
          </div>
        </div>
      </div>

      <!-- Capacity -->
      <div
        class="p-4 rounded-xl border transition-colors"
        :class="{
          'bg-gradient-to-br from-green-50 to-green-50/50 dark:from-green-950/30 dark:to-green-900/20 border-green-200 dark:border-green-900/50': !hasCapacityMismatch,
          'bg-gradient-to-br from-vusa-yellow/10 to-amber-50/50 dark:from-vusa-yellow-dark/20 dark:to-amber-950/20 border-vusa-yellow/30 dark:border-vusa-yellow-dark/30': hasCapacityMismatch
        }"
      >
        <div class="flex items-center gap-3">
          <div
            class="h-10 w-10 rounded-lg flex items-center justify-center"
            :class="{
              'bg-green-100 dark:bg-green-900/50': !hasCapacityMismatch,
              'bg-vusa-yellow/20 dark:bg-vusa-yellow-dark/30': hasCapacityMismatch
            }"
          >
            <Users
              class="h-5 w-5"
              :class="{
                'text-green-600 dark:text-green-400': !hasCapacityMismatch,
                'text-vusa-yellow-dark dark:text-vusa-yellow': hasCapacityMismatch
              }"
            />
          </div>
          <div class="flex-1">
            <p
              class="text-xs"
              :class="{
                'text-green-600/70 dark:text-green-400/70': !hasCapacityMismatch,
                'text-vusa-yellow-dark/70 dark:text-vusa-yellow/70': hasCapacityMismatch
              }"
            >
              {{ $t('Nariai') }}
            </p>
            <p
              class="font-medium"
              :class="{
                'text-green-900 dark:text-green-100': !hasCapacityMismatch,
                'text-vusa-yellow-dark dark:text-vusa-yellow': hasCapacityMismatch
              }"
            >
              {{ currentCount }} <ArrowRight class="h-3 w-3 inline mx-1" /> {{ projectedCount }} / {{ targetCapacity }}
            </p>
          </div>
          <Button
            v-if="hasCapacityMismatch && !isEditingCapacity"
            variant="ghost"
            size="sm"
            @click="startEditingCapacity"
          >
            <Edit3 class="h-4 w-4" />
          </Button>
        </div>
      </div>
    </div>

    <!-- Capacity mismatch alert -->
    <Alert v-if="hasCapacityMismatch && !isEditingCapacity" variant="default" class="border-vusa-yellow/30 dark:border-vusa-yellow-dark/30 bg-vusa-yellow/10 dark:bg-vusa-yellow-dark/20">
      <AlertTriangle class="h-4 w-4 text-vusa-yellow-dark dark:text-vusa-yellow" />
      <AlertTitle class="text-vusa-yellow-dark dark:text-vusa-yellow">
        {{ $t('Vietų skaičiaus neatitikimas') }}
      </AlertTitle>
      <AlertDescription class="text-vusa-yellow-dark/80 dark:text-vusa-yellow/80">
        {{ $t('Bus') }} {{ projectedCount }} {{ $t('nariai, bet nurodyta') }} {{ targetCapacity }}.
        <Button variant="link" class="h-auto p-0 ml-1 text-vusa-yellow-dark dark:text-vusa-yellow" @click="startEditingCapacity">
          {{ $t('Atnaujinti?') }}
        </Button>
      </AlertDescription>
    </Alert>

    <!-- Capacity editor -->
    <Card v-if="isEditingCapacity" class="border-primary/50">
      <CardContent class="p-4">
        <div class="flex items-center gap-4">
          <div class="flex-1">
            <Label>{{ $t('Vietų skaičius') }}</Label>
            <div class="flex items-center gap-2 mt-1">
              <Input
                v-model.number="newCapacity"
                type="number"
                min="1"
                class="w-24 h-9"
              />
              <Button size="sm" @click="handleUpdateCapacity">
                <CheckCircle class="h-4 w-4 mr-1" />
                {{ $t('Patvirtinti') }}
              </Button>
              <Button variant="ghost" size="sm" @click="cancelEditingCapacity">
                {{ $t('Atšaukti') }}
              </Button>
            </div>
          </div>
          <div class="text-sm text-muted-foreground">
            {{ $t('Siūloma:') }} <strong>{{ projectedCount }}</strong>
          </div>
        </div>
      </CardContent>
    </Card>

    <Separator />

    <!-- Changes summary -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Users being added -->
      <Card class="border-green-200 dark:border-green-900/50 overflow-hidden">
        <CardHeader class="bg-gradient-to-r from-green-50 to-green-50/50 dark:from-green-950/30 dark:to-green-900/20 py-3 px-4">
          <CardTitle class="text-base flex items-center gap-2 text-green-800 dark:text-green-200">
            <UserPlus class="h-4 w-4" />
            {{ $t('Pridedami nariai') }}
            <Badge variant="secondary" class="bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300">
              {{ usersToAdd.length }}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="usersToAdd.length === 0" class="p-6 text-center text-muted-foreground text-sm">
            {{ $t('Nėra pridedamų narių') }}
          </div>
          <div v-else class="divide-y">
            <div
              v-for="change in usersToAdd"
              :key="change.userId"
              class="p-3 flex items-center gap-3"
            >
              <div class="h-9 w-9 rounded-full bg-green-100 dark:bg-green-900 flex items-center justify-center overflow-hidden shrink-0">
                <img
                  v-if="change.userPhoto"
                  :src="change.userPhoto"
                  :alt="change.userName"
                  class="h-full w-full object-cover"
                >
                <span v-else class="text-sm font-medium text-green-700 dark:text-green-300">{{ change.userName?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate">
                  {{ change.userName }}
                  <Badge v-if="change.isNewUser" variant="outline" class="ml-1 text-[10px]">
                    {{ $t('Naujas') }}
                  </Badge>
                </p>
                <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                  <Calendar class="h-3 w-3" />
                  <span>{{ formatDateForDisplay(change.startDate || '') }}</span>
                  <span>→</span>
                  <span>{{ formatDateForDisplay(change.endDate || '') }}</span>
                </div>
                <p v-if="change.studyProgramName" class="text-xs text-muted-foreground mt-0.5">
                  📚 {{ change.studyProgramName }}
                </p>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>

      <!-- Users being removed -->
      <Card class="border-vusa-red/30 dark:border-vusa-red-dark/30 overflow-hidden">
        <CardHeader class="bg-gradient-to-r from-vusa-red/10 to-rose-50/50 dark:from-vusa-red-dark/20 dark:to-rose-950/20 py-3 px-4">
          <CardTitle class="text-base flex items-center gap-2 text-vusa-red dark:text-vusa-red-quaternary">
            <UserMinus class="h-4 w-4" />
            {{ $t('Šalinami nariai') }}
            <Badge variant="secondary" class="bg-vusa-red/10 dark:bg-vusa-red-dark/30 text-vusa-red dark:text-vusa-red-quaternary">
              {{ usersToRemove.length }}
            </Badge>
          </CardTitle>
        </CardHeader>
        <CardContent class="p-0">
          <div v-if="usersToRemove.length === 0" class="p-6 text-center text-muted-foreground text-sm">
            {{ $t('Nėra šalinamų narių') }}
          </div>
          <div v-else class="divide-y">
            <div
              v-for="change in usersToRemove"
              :key="change.userId"
              class="p-3 flex items-center gap-3"
            >
              <div class="h-9 w-9 rounded-full bg-vusa-red/10 dark:bg-vusa-red-dark/30 flex items-center justify-center overflow-hidden shrink-0">
                <img
                  v-if="change.userPhoto"
                  :src="change.userPhoto"
                  :alt="change.userName"
                  class="h-full w-full object-cover opacity-50"
                >
                <span v-else class="text-sm font-medium text-vusa-red dark:text-vusa-red-quaternary">{{ change.userName?.charAt(0) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <p class="font-medium text-sm truncate line-through text-vusa-red dark:text-vusa-red-quaternary">
                  {{ change.userName }}
                </p>
                <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                  <Calendar class="h-3 w-3" />
                  <span>{{ $t('Pabaiga:') }} {{ formatDateForDisplay(change.endDate || '') }}</span>
                </div>
              </div>
            </div>
          </div>
        </CardContent>
      </Card>
    </div>

    <!-- New users to create -->
    <Card v-if="newUsersToCreate.length > 0" class="border-primary/30 overflow-hidden">
      <CardHeader class="bg-gradient-to-r from-primary/5 to-primary/10 py-3 px-4">
        <CardTitle class="text-base flex items-center gap-2 text-foreground">
          <UserPlus class="h-4 w-4" />
          {{ $t('Nauji naudotojai bus sukurti') }}
          <Badge variant="secondary" class="bg-primary/10 text-primary">
            {{ newUsersToCreate.length }}
          </Badge>
        </CardTitle>
      </CardHeader>
      <CardContent class="p-0">
        <div class="divide-y">
          <div
            v-for="(user, index) in newUsersToCreate"
            :key="index"
            class="p-3 flex items-center gap-3"
          >
            <div class="h-9 w-9 rounded-full bg-primary/10 flex items-center justify-center shrink-0">
              <span class="text-sm font-medium text-primary">{{ user.name?.charAt(0) }}</span>
            </div>
            <div class="flex-1 min-w-0">
              <p class="font-medium text-sm truncate">
                {{ user.name }}
              </p>
              <p class="text-xs text-muted-foreground truncate">
                {{ user.email }}
              </p>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>

    <!-- Final confirmation -->
    <Alert class="bg-gradient-to-r from-primary/5 to-primary/10 border-primary/20">
      <CheckCircle class="h-4 w-4 text-primary" />
      <AlertTitle>{{ $t('Patvirtinkite pakeitimus') }}</AlertTitle>
      <AlertDescription>
        {{ $t('Paspaudus "Patvirtinti", pakeitimai bus išsaugoti. Šią operaciją galima atšaukti tik rankiniu būdu.') }}
      </AlertDescription>
    </Alert>
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
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
import { Separator } from '@/Components/ui/separator';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import Icons from '@/Types/Icons/regular';
import { formatDateForDisplay } from '@/Composables/useDutyUserWizard';
import type { useDutyUserWizard } from '@/Composables/useDutyUserWizard';

const wizard = inject<ReturnType<typeof useDutyUserWizard>>('dutyUserWizard')!;

// Capacity editing
const isEditingCapacity = ref(false);
const newCapacity = ref(wizard.state.duty?.places_to_occupy || 0);

// Users being added
const usersToAdd = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'add');
});

// Users being removed
const usersToRemove = computed(() => {
  return wizard.state.userChanges.filter(c => c.action === 'remove');
});

// New users being created
const newUsersToCreate = computed(() => {
  return wizard.state.newUsersToCreate;
});

// Summary calculations
const currentCount = computed(() => wizard.state.duty?.current_users?.length || 0);
const projectedCount = computed(() => wizard.projectedUserCount.value);
const targetCapacity = computed(() => wizard.targetCapacity.value);
const hasCapacityMismatch = computed(() => wizard.capacityMismatch.value);

// Handle capacity update
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
