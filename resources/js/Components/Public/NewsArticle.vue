<template>
  <article class="mt-4 grid grid-cols-4 justify-items-center gap-y-4">
    <div class="col-span-4 flex w-full flex-wrap justify-center gap-2 text-gray-700 dark:text-zinc-100">
      <slot name="tags" />
    </div>
    <div class="col-span-4 flex flex-row items-center text-gray-700 dark:text-zinc-100">
      <slot name="author" />
      <div class="mx-2">
        @
      </div>
      <slot name="time" />
    </div>
    <h1 class="col-span-4 mb-2 mt-0 text-center">
      <slot name="title" />
    </h1>
    <div class="col-span-4 my-4 h-auto max-w-prose text-center">
      <slot name="image" />
    </div>
    <slot />
  </article>
</template>
