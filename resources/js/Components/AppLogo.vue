<template>
  <link v-if="$page.props.app.locale" rel="preload" href="/logos/vusa.lin.hor.svg" as="image">
  <link v-else rel="preload" href="/logos/vusa.lin.hor.en.svg" as="image">
  <img class="max-w-lg dark:invert" v-bind="$attrs" :src="$page.props.app.locale !== 'en'
      ? '/logos/vusa.lin.hor.svg'
      : '/logos/vusa.lin.hor.en.svg'
    ">
</template>
