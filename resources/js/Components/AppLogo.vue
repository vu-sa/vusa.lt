<template>
  <img
    class="max-w-lg dark:invert"
    :src="
      $page.props.locale !== 'en'
        ? '/logos/vusa.lin.hor.svg'
        : '/logos/vusa.lin.hor.en.svg'
    "
  />
</template>
