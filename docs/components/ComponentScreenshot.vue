<script setup>
/**
 * ComponentScreenshot - Display annotated screenshots of app components
 * 
 * Use this instead of <ComponentDemo> when a static image is sufficient
 * to convey the component's appearance and behavior.
 */
defineProps({
  /** Path to the screenshot image (relative to /docs/public/) */
  src: {
    type: String,
    required: true,
  },
  /** Title shown above the screenshot */
  title: String,
  /** Description of what the screenshot shows */
  description: String,
  /** Alt text for accessibility */
  alt: {
    type: String,
    default: 'Component screenshot',
  },
  /** Optional caption below the image */
  caption: String,
})
</script>

<template>
  <div class="screenshot-wrapper">
    <div v-if="title || description" class="screenshot-header">
      <h4 v-if="title" class="screenshot-title">{{ title }}</h4>
      <p v-if="description" class="screenshot-description">{{ description }}</p>
    </div>
    <div class="screenshot-container">
      <img :src="src" :alt="alt" class="screenshot-img" loading="lazy" />
    </div>
    <p v-if="caption" class="screenshot-caption">{{ caption }}</p>
  </div>
</template>

<style scoped>
.screenshot-wrapper {
  margin: 1.5rem 0;
  border-radius: 8px;
  border: 1px solid var(--vp-c-divider);
  overflow: hidden;
  background: var(--vp-c-bg);
}

.screenshot-header {
  padding: 12px 16px;
  background: var(--vp-c-bg-soft);
  border-bottom: 1px solid var(--vp-c-divider);
}

.screenshot-title {
  margin: 0 !important;
  padding: 0 !important;
  border: none !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  color: var(--vp-c-text-1) !important;
  line-height: 1.4 !important;
}

.screenshot-description {
  margin: 4px 0 0 0 !important;
  padding: 0 !important;
  font-size: 12px !important;
  color: var(--vp-c-text-2) !important;
  line-height: 1.4 !important;
}

.screenshot-container {
  padding: 16px;
  display: flex;
  justify-content: center;
  background: var(--vp-c-bg-soft);
}

.screenshot-img {
  max-width: 100%;
  height: auto;
  border-radius: 6px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
}

.screenshot-caption {
  margin: 0 !important;
  padding: 8px 16px !important;
  font-size: 12px !important;
  color: var(--vp-c-text-3) !important;
  text-align: center !important;
  border-top: 1px solid var(--vp-c-divider);
  background: var(--vp-c-bg-soft);
}
</style>
