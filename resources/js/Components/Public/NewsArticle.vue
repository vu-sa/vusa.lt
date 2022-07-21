<template>
  <article class="grid grid-cols-4 justify-items-center px-8 lg:px-32 gap-y-4">
    <div
      class="text-gray-700 col-span-4 flex gap-2 w-full flex-wrap justify-center"
    >
      <slot name="tags"></slot>
    </div>
    <div class="flex flex-row col-span-4 text-gray-700 items-center">
      <slot name="author"></slot>
      <div class="mx-2">@</div>
      <slot name="time"></slot>
    </div>
    <h1 class="text-center col-span-4"><slot name="title"></slot></h1>
    <slot name="image"></slot>
    <slot></slot>
  </article>
</template>
