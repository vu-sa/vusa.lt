<template>
  <div class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
    <a v-if="$page.props.auth?.can.create.meeting" :href="route('dashboard.atstovavimas')">
      <button
        class="flex cursor-pointer w-full flex-col gap-3 rounded-md border border-zinc-100/80 bg-linear-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-xs duration-500 hover:shadow-lg hover:shadow-vusa-red/20 dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
        <div class="inline-flex items-center gap-2">
          <IFluentHatGraduation24Filled width="28" height="28" />
          <span class="text-xl font-bold">{{ $t('Atstovavimas') }}</span>
        </div>
        <p class="text-sm leading-4 text-zinc-500">
          {{ $t('Informuok apie posėdžius ir įkelk dokumentus atstovavimo organuose') }}
        </p>
      </button>
    </a>
    <a v-if="$page.props.auth?.can.create.page" :href="route('dashboard.svetaine')">
    <button
      class="flex w-full cursor-pointer flex-col gap-3 rounded-md border border-zinc-100/80 bg-linear-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-xs duration-500 hover:shadow-lg hover:shadow-vusa-yellow/20 dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
      <div class="inline-flex items-center gap-2">
        <IFluentGlobe24Filled width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Svetainė') }}</span>
      </div>
      <p class="text-sm leading-4 text-zinc-500">
        {{ $t('Valdyk savo padalinio svetainės naujienas, puslapius ir kt.') }}
      </p>
    </button>
    </a>
    <a :href="route('dashboard.reservations')">
    <button
      class="flex w-full cursor-pointer flex-col gap-3 rounded-md border border-zinc-100/80 bg-linear-to-br from-white to-white p-4 text-left text-base text-zinc-700 shadow-xs duration-500 hover:shadow-lg hover:shadow-white/10 dark:border-zinc-800 dark:from-zinc-900 dark:to-neutral-800 dark:text-zinc-300">
      <div class="inline-flex items-center gap-2">
        <IFluentBookmark24Filled width="28" height="28" />
        <span class="text-xl font-bold">{{ $t('Rezervacijos') }}</span>
      </div>
      <p class="text-sm leading-4 text-zinc-500">
        {{ $t('Rezervuokis daiktus iš visų VU SA padalinių') }}
      </p>
    </button>
    </a>
  </div>
</template>
