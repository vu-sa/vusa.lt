<template>
  <article class="grid grid-cols-3 ml-24 pt-8 px-32 gap-y-4">
    <h1 class="col-span-2"><slot name="title"></slot></h1>
    <div class="col-span-2 col-start-1"><slot></slot></div>
    <hr class="col-span-2 col-start-1" />
    <h3 class="col-start-1 mt-4">Kiti straipsniai:</h3>
    <div class="col-span-2 col-start-1"><slot name="randomPages"></slot></div>
  </article>
</template>
