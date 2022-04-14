<template>
  <div class="border border-red-500 mt-32 px-8 py-4 mx-16 mb-8 rounded-lg text-gray-800">
    <h1 class="mb-0">Naujienos</h1>
    <div class="flex justify-between mb-4">
      <p>Karščiausios universiteto naujienos tiesiai iš studentų lūpų!</p>
      <p>Daugiau →</p>
    </div>
    <div class="grid sm:grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-8">
      <slot></slot>
    </div>
  </div>
</template>
