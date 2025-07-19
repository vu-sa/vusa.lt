<template>
  <img 
    alt="VU SA logo" 
    class="max-w-full h-auto dark:invert" 
    v-bind="$attrs" 
    :src="$page.props.app.locale !== 'en'
      ? '/logos/vusa.lin.hor.svg'
      : '/logos/vusa.lin.hor.en.svg'
    "
    loading="eager"
    width="120"
    height="40"
  >
</template>
