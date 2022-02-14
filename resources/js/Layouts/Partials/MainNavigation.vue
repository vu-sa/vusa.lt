<template>
  <nav
    class="
      md:mr-4
      drop-shadow-lg
      md:rounded-xl
      col-span-1
      md:sticky
      top-0
      left-0 md:left-auto
      md:top-4 w-screen md:w-auto
      mb-auto bg-gray-50
      transition fixed
      flex md:flex-col
      items-center
      text-center text-gray-800
      z-20
      overflow-x-scroll md:overflow-auto justify-evenly
    "
  >
    <slot></slot>
  </nav>
</template>