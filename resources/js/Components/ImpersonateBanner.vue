<template>
  <!-- Active impersonation status -->
  <div
    v-if="isImpersonating"
    data-slot="impersonation-status"
    role="status"
    :class="[
      'rounded-xl border border-amber-200 bg-amber-50 text-amber-950 shadow-sm print:hidden',
      'dark:border-amber-900/70 dark:bg-amber-950/50 dark:text-amber-100',
      props.class,
    ]"
  >
    <div class="flex min-h-11 items-center justify-between gap-3 px-3 py-2 text-sm sm:px-4">
      <div class="flex min-w-0 items-center gap-3">
        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900/60">
          <UserCog class="h-4 w-4 text-amber-700 dark:text-amber-300" />
        </div>
        <p class="min-w-0 truncate font-medium">
          {{ $t('Impersonating as') }} <strong>{{ currentUserName }}</strong>
          <span class="hidden text-amber-700 sm:inline dark:text-amber-300">
            ({{ $t('logged in by') }} {{ impersonatorName }})
          </span>
        </p>
      </div>
      <Button
        size="sm"
        variant="outline"
        class="h-8 shrink-0 gap-1.5 rounded-lg border-amber-300 bg-white/70 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/60 dark:hover:bg-amber-900/60"
        :disabled="stopping"
        @click="stopImpersonating"
      >
        <LogOut class="h-4 w-4" />
        <span class="hidden sm:inline">{{ $t('Stop Impersonating') }}</span>
        <span class="sr-only sm:hidden">{{ $t('Stop Impersonating') }}</span>
      </Button>
    </div>
  </div>

  <Teleport to="body">
    <!-- Impersonate selector (super admin, non-impersonating) -->
    <div
      v-if="canImpersonate && !isImpersonating && !dismissed"
      data-slot="impersonation-launcher"
      class="fixed bottom-4 right-4 z-[9998] print:hidden"
    >
      <Popover v-model:open="popoverOpen">
        <PopoverTrigger as-child>
          <Button
            size="icon"
            variant="outline"
            class="h-10 w-10 rounded-full shadow-lg bg-background"
          >
            <UserCog class="h-5 w-5" />
            <span class="sr-only">{{ $t('Impersonate User') }}</span>
          </Button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-0" align="end" side="top">
          <div class="p-3 border-b">
            <div class="flex items-center justify-between mb-2">
              <h4 class="text-sm font-medium">
                {{ $t('Impersonate User') }}
              </h4>
              <button
                type="button"
                class="p-1 hover:bg-muted rounded-md transition-colors"
                @click="dismissed = true; popoverOpen = false"
              >
                <X class="h-3.5 w-3.5 text-muted-foreground" />
              </button>
            </div>
            <Input
              v-model="searchQuery"
              :placeholder="$t('Search by name or email...')"
              class="h-8 text-sm"
              @input="debouncedSearch"
            />
          </div>
          <div class="max-h-48 overflow-y-auto">
            <div v-if="searching" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('Searching...') }}
            </div>
            <div v-else-if="searchResults.length === 0 && searchQuery.length >= 2" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('No users found') }}
            </div>
            <div v-else-if="searchQuery.length < 2 && searchQuery.length > 0" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('Type at least 2 characters') }}
            </div>
            <button
              v-for="user in searchResults"
              :key="user.id"
              type="button"
              class="w-full flex flex-col gap-0.5 px-3 py-2 text-left hover:bg-muted transition-colors text-sm"
              :disabled="starting"
              @click="startImpersonating(user.id)"
            >
              <span class="font-medium">{{ user.name }}</span>
              <span class="text-xs text-muted-foreground">{{ user.email }}</span>
            </button>
          </div>
        </PopoverContent>
      </Popover>
    </div>
  </Teleport>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useFetch } from '@vueuse/core';
import { LogOut, UserCog, X } from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';

defineOptions({
  inheritAttrs: false,
});

const props = defineProps<{
  class?: HTMLAttributes['class'];
}>();

interface SearchUser {
  id: string;
  name: string;
  email: string;
}

const dismissed = ref(false);
const popoverOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref<SearchUser[]>([]);
const searching = ref(false);
const starting = ref(false);
const stopping = ref(false);

const page = usePage();

const auth = computed(() => page.props.auth as {
  user: { name: string; isSuperAdmin: boolean };
  impersonating: { impersonator_name: string } | null;
} | null);

const isImpersonating = computed(() => !!auth.value?.impersonating);
const impersonatorName = computed(() => auth.value?.impersonating?.impersonator_name ?? '');
const currentUserName = computed(() => auth.value?.user?.name ?? '');

const canImpersonate = computed(() => {
  const env = page.props.app as { env: string } | undefined;
  const isDevEnv = env?.env === 'local' || env?.env === 'staging';
  return isDevEnv && auth.value?.user?.isSuperAdmin && !isImpersonating.value;
});

const debouncedSearch = useDebounceFn(async () => {
  if (searchQuery.value.length < 2) {
    searchResults.value = [];
    return;
  }

  searching.value = true;

  try {
    const url = `${route('api.v1.admin.impersonate.search')}?search=${encodeURIComponent(searchQuery.value)}`;
    const { data } = await useFetch(url, {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': String(page.props.csrf_token ?? ''),
      },
      credentials: 'same-origin',
    }).json<{ success: boolean; data: SearchUser[] }>();

    searchResults.value = data.value?.data ?? [];
  }
  finally {
    searching.value = false;
  }
}, 300);

async function startImpersonating(userId: string) {
  starting.value = true;

  try {
    const url = route('api.v1.admin.impersonate.start');
    await useFetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': String(page.props.csrf_token ?? ''),
      },
      credentials: 'same-origin',
      body: JSON.stringify({ user_id: userId }),
    }).json();

    popoverOpen.value = false;
    router.reload();
  }
  finally {
    starting.value = false;
  }
}

async function stopImpersonating() {
  stopping.value = true;

  try {
    const url = route('api.v1.admin.impersonate.stop');
    await useFetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': String(page.props.csrf_token ?? ''),
      },
      credentials: 'same-origin',
    }).json();

    router.reload();
  }
  finally {
    stopping.value = false;
  }
}
</script>
