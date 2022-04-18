<template>
  <div class="py-4 px-8 mx-16 mb-8 rounded-lg text-gray-800 bg-white shadow-lg">
    <h1 class="lg:mb-0">Naujienos</h1>
    <div class="flex justify-between mb-4 wrap gap-1">
      <p>Karščiausios universiteto naujienos tiesiai iš studentų lūpų!</p>
      <p>Daugiau →</p>
    </div>
    <div class="grid sm:grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-8">
      <slot></slot>
    </div>
  </div>
</template>
