<template>
  <div>
    <slot name="image"
      ><div class="bg-gray-700 rounded-md shadow-md h-auto min-h-[14em] w-full"></div
    ></slot>
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
