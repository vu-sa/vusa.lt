---
title: Blog
---

<script setup>
import BlogPostCard from '../../blog/components/BlogPostCard.vue'

const posts = [
  {
    title: 'mano.vusa.lt v1.0: Platform Modernization',
    url: '/en/blog/2026-02-07-v1-modernization',
    date: 'February 7, 2026',
    author: 'Justinas Kavoliūnas',
    excerpt: 'The biggest mano.vusa.lt update — new representation management system, public meetings, guided tours, content updates and much more.',
    tags: ['update', 'v1.0'],
  },
]
</script>

# Blog

Latest news and guides about the mano.vusa.lt platform.

<div class="blog-grid">
  <BlogPostCard
    v-for="post in posts"
    :key="post.url"
    v-bind="post"
  />
</div>

<style>
.blog-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
  margin-top: 24px;
}
</style>
