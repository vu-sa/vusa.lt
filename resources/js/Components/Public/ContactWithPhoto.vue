<template>
  <div
    class="text-neutral-50 p-6 shadow-lg rounded-lg grid-cols-2 gap-8 grid min-w-[10em] w-auto bg-white"
  >
    <slot name="image"></slot>
    <div class="text-gray-800">
      <h2><slot name="name"></slot></h2>
      <p class="mb-2"><slot name="duty"></slot></p>
      <p class="mb-2"><slot name="description"></slot></p>
      <div class="mb-2 flex flex-row items-center gap-2 flex-wrap">
        <slot name="phone"></slot>
        <slot name="email"></slot>
      </div>
      <slot></slot>
    </div>
  </div>
</template>
