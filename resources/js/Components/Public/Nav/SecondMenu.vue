<template>
  <section class="relative z-5 border-t border-border bg-secondary/40">
    <div class="mx-auto grid h-10 max-w-[84rem] grid-cols-[min-content__1fr] items-center px-4 lg:px-8">
      <SmartLink prefetch href="/"
        class="mr-6 whitespace-nowrap text-xs font-bold text-brand transition-colors hover:text-foreground">
        {{
          $page.props.tenant?.shortname
            ? $t($page.props.tenant?.shortname)
            : "VU SA"
        }}
      </SmartLink>
      <nav class="grid grid-cols-[1fr_auto_auto] items-center gap-5 whitespace-nowrap text-xs">
        <div class="relative flex items-center gap-4 overflow-hidden w-full">
          <QuickLink v-for="link in $page.props.tenant?.links" :key="link?.id" :quick-link="link" />
          <div
            class="pointer-events-none absolute right-0 h-8 w-10 bg-gradient-to-r from-transparent to-background" />
        </div>
        <div class="inline-flex gap-1">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                class="h-6 px-1.5"
                :title="$t('Daugiau nuorodų')"
              >
                <IFluentLineHorizontal1Dot20Filled class="h-4 w-4" />
                <span class="sr-only">{{ $t('Daugiau nuorodų') }}</span>
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-48">
              <DropdownMenuItem
                v-for="option in dropdownOptions"
                :key="option.key"
                class="cursor-pointer"
                @click="() => handleSelect(option.key)"
              >
                {{ option.label }}
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <!-- The language switcher lives here, not in the primary bar: the primary row has to
               fit the uppercase nav without wrapping, and a wordy LT/EN control is what pushed it
               onto two lines. Search is icon-only in the primary bar, so there is none here. -->
          <LocaleButton :locale="$page.props.app.locale" size="sm" class="h-6 px-2 text-xs" />
          <span class="h-4 w-px bg-border" aria-hidden="true" />
          <a href="/login"
            class="inline-flex h-6 items-center justify-center gap-1.5 px-2 text-xs transition-colors hover:bg-secondary"
            :title="$page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login')">
            <template v-if="$page.props.auth?.user">
              <!-- `rounded-lg` overrides the primitive's base `rounded-full`. Keep it: the radius
                   token resolves to 0 on the public surface (the design has no circular avatars),
                   and dropping the class would fall back to a circle rather than a square. -->
              <Avatar class="h-4 w-4 rounded-lg ring-1 ring-border">
                <AvatarImage v-if="$page.props.auth.user.profile_photo_path"
                  :src="$page.props.auth.user.profile_photo_path" :alt="$page.props.auth.user.name" />
                <AvatarFallback class="bg-secondary text-[7px] font-semibold text-foreground">
                  {{ $page.props.auth.user.name.substring(0, 2).toUpperCase() }}
                </AvatarFallback>
              </Avatar>
            </template>
            <IFluentPerson24Regular v-else class="h-4 w-4" aria-hidden="true" />
            <span class="hidden sm:inline">
              {{ $page.props.auth?.user ? $page.props.auth.user?.name : $t('Mano VU SA') }}
            </span>
            <span class="sr-only sm:hidden">
              {{ $page.props.auth?.user ? $page.props.auth.user?.name : $t('Mano VU SA') }}
            </span>
          </a>
        </div>
      </nav>
    </div>
  </section>
</template>

<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '../SmartLink.vue';

import QuickLink from './QuickLink.vue';
import LocaleButton from './LocaleButton.vue';

import { Button } from '@/Components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/Components/ui/avatar';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const dropdownOptions = (usePage().props.tenant?.links ?? [])
  .filter((link): link is NonNullable<typeof link> => link?.text != null && link?.link != null)
  .map(link => ({
    label: link.text!,
    key: link.link!,
  }));

const handleSelect = (value: string) => {
  window.location.href = value;
};
</script>
