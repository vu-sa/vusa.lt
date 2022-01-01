<template>
  <div
    class="col-span-8 mt-10 mx-8 grid grid-flow-row auto-rows-max grid-cols-6"
  >
    <header class="mt-4 mb-5 col-span-4">
      <h1 class="font-black text-3xl text-gray-800 leading-tight">
        <slot name="header"></slot>
      </h1>
    </header>
    <slot></slot>
  </div>
</template>