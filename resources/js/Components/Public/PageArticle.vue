<template>
  <article class="grid grid-cols-3 ml-[5vw] pt-8 px-8 lg:px-32 gap-y-4 last:pb-2">
    <h1 class="col-span-3 lg:col-span-2"><slot name="title"></slot></h1>
    <div class="col-span-3 lg:col-span-2 col-start-1"><slot></slot></div>
    <hr class="col-span-3 lg:col-span-2 col-start-1" />
    <!-- <h3 class="col-start-1 mt-4">{{ __("Kiti puslapiai") }}:</h3>
    <div class="col-span-3 col-start-1">
      <slot name="randomPages"></slot>
    </div> -->
  </article>
</template>
