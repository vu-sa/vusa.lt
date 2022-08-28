<template>
  <article
    class="mt-4 grid grid-cols-4 justify-items-center gap-y-4 px-8 lg:px-32"
  >
    <div
      class="col-span-4 flex w-full flex-wrap justify-center gap-2 text-gray-700"
    >
      <slot name="tags"></slot>
    </div>
    <div class="col-span-4 flex flex-row items-center text-gray-700">
      <slot name="author"></slot>
      <div class="mx-2">@</div>
      <slot name="time"></slot>
    </div>
    <h1 class="col-span-4 text-center">
      <slot name="title"></slot>
    </h1>
    <slot name="image"></slot>
    <slot></slot>
  </article>
</template>
