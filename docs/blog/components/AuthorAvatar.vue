<script setup>
defineProps({
  name: String,
  avatar: String,
})
</script>

<template>
  <span class="author-avatar">
    <img v-if="avatar" :src="avatar" :alt="name" class="author-avatar-img" />
    <span v-else class="author-avatar-initials">{{ name?.split(' ').map(n => n[0]).join('') }}</span>
    <span class="author-avatar-name">{{ name }}</span>
  </span>
</template>

<style scoped>
.author-avatar {
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.author-avatar-img {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  object-fit: cover;
}

.author-avatar-initials {
  width: 24px;
  height: 24px;
  border-radius: 50%;
  background: linear-gradient(135deg, oklch(0.5 0.12 25), oklch(0.75 0.12 65));
  color: white;
  font-size: 11px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.author-avatar-name {
  font-size: 13px;
  color: var(--vp-c-text-2);
  font-weight: 500;
}
</style>
