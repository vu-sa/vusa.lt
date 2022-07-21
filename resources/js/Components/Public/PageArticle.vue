<template>
  <article
    class="grid grid-cols-3 ml-[5vw] pt-8 px-8 lg:px-32 gap-y-4 last:pb-2"
  >
    <slot name="breadcrumb"></slot>
    <h1 class="col-start-1 col-span-3">
      <slot name="title"></slot>
    </h1>
    <div class="col-start-1 col-span-3">
      <slot></slot>
    </div>
  </article>
</template>
