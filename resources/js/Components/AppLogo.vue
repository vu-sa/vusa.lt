<template>
  <img
    v-if="$page.props.locale !== 'en'"
    class="max-w-lg"
    src="/logos/vusa.lin.hor.svg"
  />
  <img v-else class="max-w-lg" src="/logos/vusa.lin.hor.en.svg" />
</template>
