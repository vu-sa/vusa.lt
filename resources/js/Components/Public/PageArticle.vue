<template>
  <article
    class="ml-[5vw] grid grid-cols-3 gap-y-4 px-8 pt-8 last:pb-2 lg:px-32"
  >
    <slot name="breadcrumb"></slot>
    <h1 class="col-span-3 col-start-1">
      <slot name="title"></slot>
    </h1>
    <div class="col-span-3 col-start-1">
      <slot></slot>
    </div>
  </article>
</template>
