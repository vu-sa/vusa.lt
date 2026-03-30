<script setup>
import { withBase } from 'vitepress'
import AuthorAvatar from './AuthorAvatar.vue'

defineProps({
  title: String,
  date: String,
  author: String,
  authorAvatar: String,
  excerpt: String,
  url: String,
  tags: {
    type: Array,
    default: () => [],
  },
})
</script>

<template>
  <a :href="withBase(url)" class="blog-post-card">
    <div class="blog-post-card-content">
      <div v-if="tags.length" class="blog-post-tags">
        <span v-for="tag in tags" :key="tag" class="blog-post-tag">{{ tag }}</span>
      </div>
      <h3 class="blog-post-title">{{ title }}</h3>
      <p v-if="excerpt" class="blog-post-excerpt">{{ excerpt }}</p>
      <div class="blog-post-footer">
        <AuthorAvatar :name="author" :avatar="authorAvatar" />
        <time class="blog-post-date">{{ date }}</time>
      </div>
    </div>
  </a>
</template>

<style scoped>
.blog-post-card {
  display: flex;
  flex-direction: column;
  border: 1px solid var(--vp-c-divider);
  border-radius: 12px;
  overflow: hidden;
  transition: all 0.2s ease;
  text-decoration: none !important;
  color: inherit !important;
  background: var(--vp-c-bg);
}

.blog-post-card:hover {
  border-color: var(--vp-c-brand-1);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.blog-post-card-content {
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  flex: 1;
}

.blog-post-tags {
  display: flex;
  gap: 6px;
  flex-wrap: wrap;
}

.blog-post-tag {
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  color: var(--vp-c-brand-1);
  background: var(--vp-c-brand-soft);
  padding: 2px 8px;
  border-radius: 9999px;
}

.blog-post-title {
  margin: 0 !important;
  padding: 0 !important;
  border: none !important;
  font-size: 18px !important;
  font-weight: 700 !important;
  line-height: 1.3 !important;
  color: var(--vp-c-text-1) !important;
}

.blog-post-excerpt {
  margin: 0 !important;
  padding: 0 !important;
  font-size: 14px !important;
  line-height: 1.5 !important;
  color: var(--vp-c-text-2) !important;
  display: -webkit-box;
  -webkit-line-clamp: 3;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.blog-post-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: auto;
  padding-top: 8px;
}

.blog-post-date {
  font-size: 13px;
  color: var(--vp-c-text-3);
}
</style>
