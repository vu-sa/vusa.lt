<template>
  <div
    v-if="isImpersonating"
    data-slot="impersonation-status"
    role="status"
    :class="[
      'border-b border-status-attention-border bg-status-attention-surface text-status-attention print:hidden',
      props.class,
    ]"
  >
    <div class="mx-auto flex min-h-11 w-full max-w-7xl items-center justify-between gap-3 px-4 py-1 text-sm sm:px-6 lg:px-8">
      <div class="flex min-w-0 items-center gap-3">
        <UserCog class="size-4 shrink-0" />
        <p class="min-w-0 truncate font-medium">
          {{ $t('Prisijungei kaip') }} <strong>{{ currentUserName }}</strong>
          <span class="hidden sm:inline">
            ({{ $t('Pradinė paskyra') }}: {{ impersonatorName }})
          </span>
        </p>
      </div>
      <Button
        size="xs"
        variant="outline"
        class="shrink-0 pointer-coarse:min-h-11"
        :disabled="stopping"
        @click="stopImpersonating"
      >
        <LogOut class="h-4 w-4" />
        <span class="hidden sm:inline">{{ $t('Grįžti į savo paskyrą') }}</span>
        <span class="sr-only sm:hidden">{{ $t('Grįžti į savo paskyrą') }}</span>
      </Button>
    </div>
    <p v-if="requestError" role="alert" class="mx-auto w-full max-w-7xl px-4 pb-2 text-sm sm:px-6 lg:px-8">
      {{ requestError }}
    </p>
  </div>

  <div
    v-if="canImpersonate && !isImpersonating && !dismissed"
    data-slot="impersonation-launcher"
    class="min-h-11 border-b border-border bg-secondary/50 print:hidden"
  >
    <div class="mx-auto flex min-h-11 w-full max-w-7xl items-center justify-end gap-2 px-4 py-1 sm:px-6 lg:px-8">
      <Popover v-model:open="popoverOpen">
        <PopoverTrigger as-child>
          <Button
            size="sm"
            variant="outline"
            class="min-h-11"
          >
            <UserCog class="size-4" />
            {{ $t('Apsimesti nariu') }}
          </Button>
        </PopoverTrigger>
        <PopoverContent class="w-[min(20rem,calc(100vw-2rem))] rounded-none border-border p-0 shadow-none" align="end" side="bottom">
          <div class="border-b border-border p-3">
            <div class="mb-2 flex items-center justify-between">
              <h4 class="text-sm font-semibold text-foreground">
                {{ $t('Apsimesti nariu') }}
              </h4>
              <Button
                size="icon-sm"
                variant="ghost"
                class="min-h-11 min-w-11"
                :aria-label="$t('Uždaryti')"
                @click="popoverOpen = false"
              >
                <X class="size-4" />
              </Button>
            </div>
            <Input
              v-model="searchQuery"
              :placeholder="$t('Ieškok pagal vardą ar el. paštą…')"
              @input="debouncedSearch"
            />
          </div>
          <div class="max-h-60 overflow-y-auto">
            <div v-if="searching" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('Ieškoma…') }}
            </div>
            <div v-else-if="searchResults.length === 0 && searchQuery.length >= 2" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('Narių nerasta') }}
            </div>
            <div v-else-if="searchQuery.length < 2 && searchQuery.length > 0" class="p-3 text-center text-sm text-muted-foreground">
              {{ $t('Įvesk bent 2 simbolius') }}
            </div>
            <p v-if="requestError" role="alert" class="border-b border-status-danger-border bg-status-danger-surface px-3 py-2 text-sm text-status-danger">
              {{ requestError }}
            </p>
            <button
              v-for="user in searchResults"
              :key="user.id"
              type="button"
              :class="[
                'flex min-h-11 w-full flex-col gap-0.5 border-b border-border px-3 py-2 text-left text-sm transition-colors',
                'last:border-b-0 hover:bg-accent focus-visible:bg-accent focus-visible:outline-none',
              ]"
              :disabled="starting"
              @click="startImpersonating(user.id)"
            >
              <span class="font-medium">{{ user.name }}</span>
              <span class="text-xs text-muted-foreground">{{ user.email }}</span>
            </button>
          </div>
        </PopoverContent>
      </Popover>
      <Button
        data-slot="impersonation-bar-close"
        size="icon-sm"
        variant="ghost"
        class="min-h-11 min-w-11"
        :aria-label="$t('Uždaryti')"
        @click="dismissed = true"
      >
        <X class="size-4" />
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { computed, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useDebounceFn, useFetch } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
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
const requestError = ref('');

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
  requestError.value = '';

  try {
    const url = route('api.v1.admin.impersonate.start');
    const { data, error } = await useFetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': String(page.props.csrf_token ?? ''),
      },
      credentials: 'same-origin',
      body: JSON.stringify({ user_id: userId }),
    }).json<{ success: boolean; message?: string }>();

    if (error.value || !data.value?.success) {
      requestError.value = data.value?.message ?? $t('Nepavyko prisijungti kaip šis narys.');
      return;
    }

    popoverOpen.value = false;
    router.reload();
  }
  finally {
    starting.value = false;
  }
}

async function stopImpersonating() {
  stopping.value = true;
  requestError.value = '';

  try {
    const url = route('api.v1.admin.impersonate.stop');
    const { data, error } = await useFetch(url, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': String(page.props.csrf_token ?? ''),
      },
      credentials: 'same-origin',
    }).json<{ success: boolean; message?: string }>();

    if (error.value || !data.value?.success) {
      requestError.value = data.value?.message ?? $t('Nepavyko grįžti į savo paskyrą.');
      return;
    }

    router.reload();
  }
  finally {
    stopping.value = false;
  }
}
</script>
