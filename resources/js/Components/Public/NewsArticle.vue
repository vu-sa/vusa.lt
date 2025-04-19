<template>
  <article class="mt-4 grid grid-cols-4 justify-items-center gap-y-4">
    <!-- Tags section -->
    <div class="col-span-4 flex w-full flex-wrap justify-center gap-2 text-gray-700 dark:text-zinc-100">
      <slot name="tags" />
    </div>
    
    <!-- Author and time metadata -->
    <div class="col-span-4 flex flex-row items-center text-zinc-500 dark:text-zinc-400 text-sm">
      <slot name="author" />
      <div class="mx-2">
        @
      </div>
      <slot name="time" />
    </div>
    
    <!-- Article title with improved typography -->
    <h1 class="col-span-4 mb-4 mt-2 text-center text-3xl md:text-4xl font-bold leading-tight">
      <slot name="title" />
    </h1>
    
    <!-- Main image with improved responsive behavior -->
    <div class="col-span-4 my-6 h-auto max-w-prose">
      <slot name="image" />
    </div>
    
    <!-- Main content -->
    <div class="col-span-4 typography max-w-none">
      <slot />
    </div>
  </article>
</template>
