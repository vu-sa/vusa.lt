<script setup>
import AuthorAvatar from './AuthorAvatar.vue'

defineProps({
  title: String,
  date: String,
  author: String,
  authorAvatar: String,
  tags: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <div class="blog-post-header">
    <div v-if="tags.length" class="blog-post-tags">
      <span v-for="tag in tags" :key="tag" class="blog-post-tag">{{ tag }}</span>
    </div>
    <h1 class="blog-post-title">{{ title }}</h1>
    <div class="blog-post-meta">
      <AuthorAvatar :name="author" :avatar="authorAvatar" />
      <time class="blog-post-date">{{ date }}</time>
    </div>
  </div>
</template>

<style scoped>
.blog-post-header {
  margin-bottom: 32px;
  padding-bottom: 24px;
  border-bottom: 1px solid var(--vp-c-divider);
}

.blog-post-title {
  font-size: 32px !important;
  font-weight: 800 !important;
  line-height: 1.2 !important;
  margin: 8px 0 16px !important;
  padding: 0 !important;
  border: none !important;
}

.blog-post-tags {
  display: flex;
  gap: 6px;
}

.blog-post-tag {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--vp-c-brand-1);
  background: var(--vp-c-brand-soft);
  padding: 2px 10px;
  border-radius: 9999px;
}

.blog-post-meta {
  display: flex;
  align-items: center;
  gap: 16px;
  color: var(--vp-c-text-2);
  font-size: 14px;
}

.blog-post-date {
  color: var(--vp-c-text-3);
}
</style>
