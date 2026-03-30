---
title: Naujienos
---

<script setup>
import BlogPostCard from './components/BlogPostCard.vue'

const posts = [
  {
    title: 'mano.vusa.lt v1.0: Platformos modernizacija',
    url: '/blog/2026-02-07-v1-modernization',
    date: '2026 m. vasario 7 d.',
    author: 'Justinas Kavoliūnas',
    excerpt: 'Didžiausias mano.vusa.lt atnaujinimas — nauja atstovavimo valdymo sistema, vieši posėdžiai, gidų sistema, atnaujintas turinys ir daug daugiau.',
    tags: ['atnaujinimas', 'v1.0'],
  },
]
</script>

# Naujienos

Naujausios naujienos ir gidai apie mano.vusa.lt platformą.

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
