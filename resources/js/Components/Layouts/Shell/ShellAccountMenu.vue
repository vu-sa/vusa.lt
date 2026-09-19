<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <Button variant="ghost" size="icon" class="u-touch" :aria-label="$t('shell.chrome.account')">
        <Avatar class="size-8 rounded-none">
          <AvatarImage v-if="user?.profile_photo_path" :src="user.profile_photo_path" :alt="user.name" />
          <AvatarFallback class="rounded-none text-xs font-semibold">
            {{ initials }}
          </AvatarFallback>
        </Avatar>
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-64 shadow-none">
      <DropdownMenuLabel class="font-normal">
        <span class="block truncate text-sm font-semibold">{{ user?.name }}</span>
        <span class="block truncate text-xs text-muted-foreground">{{ user?.email }}</span>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />
      <DropdownMenuItem as-child>
        <Link :href="route('profile')" prefetch>
          {{ $t('shell.chrome.account') }}
        </Link>
      </DropdownMenuItem>
      <DropdownMenuCheckboxItem :model-value="newShellEnabled" @select.prevent="toggleNewShell">
        {{ $t('shell.chrome.new_design') }}
      </DropdownMenuCheckboxItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @select="logout">
        <LogOut class="size-4" />
        {{ $t('auth.logout') }}
      </DropdownMenuItem>
      <DropdownMenuItem @select="logoutMicrosoft">
        <ISimpleIconsMicrosoft class="size-4" />
        {{ $t('auth.logout_microsoft') }}
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { LogOut } from 'lucide-vue-next';
import { computed } from 'vue';

import ISimpleIconsMicrosoft from '~icons/simple-icons/microsoft';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import { Button } from '@/Components/ui/button';
import {
  DropdownMenu,
  DropdownMenuCheckboxItem,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { useLogout } from '@/Composables/useLogout';
import { useNewShellToggle } from '@/Composables/useNewShellToggle';

const page = usePage<PageProps>();
const user = computed(() => page.props.auth?.user);
const initials = computed(() => (user.value?.name ?? '').split(/\s+/).filter(Boolean).slice(0, 2).map(part => part[0]).join('').toUpperCase());

const { logout, logoutMicrosoft } = useLogout();
const { enabled: newShellEnabled, toggle: toggleNewShell } = useNewShellToggle();
</script>
