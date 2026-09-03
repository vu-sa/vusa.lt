<template>
  <section class="relative z-5 border-t border-border bg-secondary/50">
    <div class="mx-auto grid h-11 max-w-7xl grid-cols-[min-content__1fr] items-center px-5 sm:px-6 lg:px-8">
      <SmartLink prefetch href="/"
        class="mr-6 whitespace-nowrap text-xs font-bold uppercase tracking-[0.2em] text-brand transition-colors hover:text-foreground">
        {{
          $page.props.tenant?.shortname
            ? $t($page.props.tenant?.shortname)
            : "VU SA"
        }}
      </SmartLink>
      <nav class="grid grid-cols-[1fr_auto_auto] items-center gap-5 whitespace-nowrap text-sm">
        <!-- The overflow hint is a mask, not a gradient overlay. An overlay has to be painted in
             the bar's own colour to disappear, and the bar is a translucent tint over the page
             background — so the overlay showed up as a pale smudge instead of a fade. A mask
             fades the content itself and needs to know nothing about what is behind it. -->
        <div
          class="flex w-full items-center gap-5 overflow-hidden"
          style="mask-image: linear-gradient(to right, black calc(100% - 2.5rem), transparent)"
        >
          <QuickLink v-for="link in $page.props.tenant?.links" :key="link?.id" :quick-link="link" />
        </div>
        <div class="inline-flex gap-1">
          <DropdownMenu>
            <DropdownMenuTrigger as-child>
              <Button
                variant="ghost"
                size="sm"
                class="h-7 px-1.5"
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
          <LocaleButton :locale="$page.props.app.locale" size="sm" class="h-7 px-2 text-xs" />
          <span class="h-4 w-px bg-border" aria-hidden="true" />
          <a href="/login"
            class="inline-flex h-7 items-center justify-center gap-1.5 px-2 text-xs font-medium text-muted-foreground transition-colors hover:text-foreground"
            :title="$page.props.auth?.user ? $page.props.auth.user?.name : $t('auth.login')">
            <template v-if="$page.props.auth?.user">
              <!-- `rounded-lg` overrides the primitive's base `rounded-full`. Keep it: the radius
                   token resolves to 0 on the public surface (the design has no circular avatars),
                   and dropping the class would fall back to a circle rather than a square. -->
              <Avatar class="h-4 w-4 rounded-lg ring-1 ring-border">
                <AvatarImage v-if="$page.props.auth.user.profile_photo_path"
                  :src="$page.props.auth.user.profile_photo_path" :alt="$page.props.auth.user.name" />
                <AvatarFallback class="bg-secondary text-[0.4375rem] font-semibold text-foreground">
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
