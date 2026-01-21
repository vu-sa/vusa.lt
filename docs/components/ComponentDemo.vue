<script setup lang="ts">
/**
 * ComponentDemo - Wrapper for component demos in VitePress documentation
 * 
 * Provides:
 * - Full CSS reset to isolate app components from VitePress styles
 * - Suspense boundary for async components
 * - Error boundary with fallback UI
 * - Dark mode support (syncs with VitePress theme)
 */
import { ref, onErrorCaptured } from 'vue'

interface Props {
  /** Title shown above the demo */
  title?: string
  /** Description of what this demo shows */
  description?: string
  /** Background variant */
  variant?: 'default' | 'dark' | 'transparent'
  /** Whether to show padding in the demo area */
  noPadding?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
  noPadding: false
})

const error = ref<Error | null>(null)

onErrorCaptured((err) => {
  error.value = err as Error
  console.error('ComponentDemo error:', err)
  return false // Don't propagate
})

const bgClasses = {
  default: '',
  dark: 'demo-dark',
  transparent: 'demo-transparent'
}
</script>

<template>
  <div class="component-demo-wrapper">
    <!-- Header - outside reset zone, uses VitePress styles -->
    <div v-if="title || description" class="demo-header">
      <h4 v-if="title" class="demo-title">{{ title }}</h4>
      <p v-if="description" class="demo-description">{{ description }}</p>
    </div>
    
    <!-- Demo content - full style reset zone -->
    <div :class="['component-demo', 'component-demo-reset', bgClasses[variant], { 'demo-no-padding': noPadding }]">
      <!-- Error state -->
      <div v-if="error" class="demo-error">
        <p class="demo-error-title">Component Error</p>
        <pre class="demo-error-message">{{ error.message }}</pre>
      </div>
      
      <!-- Normal rendering with Suspense -->
      <Suspense v-else>
        <slot />
        <template #fallback>
          <div class="demo-loading">
            <div class="demo-spinner"></div>
            <span>Loading component...</span>
          </div>
        </template>
      </Suspense>
    </div>
  </div>
</template>

<style scoped>
/* Wrapper maintains VitePress integration */
.component-demo-wrapper {
  margin: 1.5rem 0;
  border-radius: 8px;
  border: 1px solid var(--vp-c-divider);
  overflow: hidden;
  background: var(--vp-c-bg);
}

/* Header uses VitePress styles */
.demo-header {
  padding: 12px 16px;
  background: var(--vp-c-bg-soft);
  border-bottom: 1px solid var(--vp-c-divider);
}

.demo-title {
  margin: 0 !important;
  padding: 0 !important;
  border: none !important;
  font-size: 14px !important;
  font-weight: 600 !important;
  color: var(--vp-c-text-1) !important;
  line-height: 1.4 !important;
}

.demo-description {
  margin: 4px 0 0 0 !important;
  padding: 0 !important;
  font-size: 12px !important;
  color: var(--vp-c-text-2) !important;
  line-height: 1.4 !important;
}

/* Reset container - isolates app components from VitePress */
.component-demo-reset {
  /* Full CSS reset */
  all: initial;
  display: block;
  padding: 24px;
  
  /* Re-establish base styles matching the app */
  font-family: 'Inter', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
  font-size: 14px;
  line-height: 1.5;
  color: var(--foreground);
  background-color: var(--background);
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  box-sizing: border-box;
}

.component-demo-reset.demo-no-padding {
  padding: 0;
}

/* Ensure all children use border-box */
.component-demo-reset :deep(*),
.component-demo-reset :deep(*::before),
.component-demo-reset :deep(*::after) {
  box-sizing: border-box;
}

/* Reset common elements that VitePress styles heavily */
.component-demo-reset :deep(button) {
  all: unset;
  box-sizing: border-box;
  cursor: pointer;
}

.component-demo-reset :deep(a) {
  all: unset;
  box-sizing: border-box;
  cursor: pointer;
}

.component-demo-reset :deep(h1),
.component-demo-reset :deep(h2),
.component-demo-reset :deep(h3),
.component-demo-reset :deep(h4),
.component-demo-reset :deep(h5),
.component-demo-reset :deep(h6) {
  all: unset;
  display: block;
  box-sizing: border-box;
}

.component-demo-reset :deep(p) {
  all: unset;
  display: block;
  box-sizing: border-box;
}

.component-demo-reset :deep(ul),
.component-demo-reset :deep(ol) {
  all: unset;
  display: block;
  box-sizing: border-box;
}

.component-demo-reset :deep(li) {
  all: unset;
  display: list-item;
  box-sizing: border-box;
}

/* Dark variant */
.demo-dark {
  background-color: oklch(0.141 0.005 285.823) !important;
  color: oklch(0.985 0 0) !important;
}

/* Transparent variant */
.demo-transparent {
  background-color: transparent !important;
  background-image: 
    linear-gradient(45deg, var(--vp-c-divider) 25%, transparent 25%),
    linear-gradient(-45deg, var(--vp-c-divider) 25%, transparent 25%),
    linear-gradient(45deg, transparent 75%, var(--vp-c-divider) 75%),
    linear-gradient(-45deg, transparent 75%, var(--vp-c-divider) 75%);
  background-size: 16px 16px;
  background-position: 0 0, 0 8px, 8px -8px, -8px 0px;
}

/* Error state */
.demo-error {
  padding: 16px;
  background: oklch(0.936 0.032 17.717);
  border-radius: 6px;
  color: oklch(0.396 0.141 25.723);
}

.demo-error-title {
  font-weight: 600;
  margin-bottom: 8px;
}

.demo-error-message {
  font-size: 12px;
  font-family: ui-monospace, monospace;
  overflow: auto;
  margin: 0;
  white-space: pre-wrap;
}

/* Loading state */
.demo-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 32px;
  gap: 12px;
  color: var(--muted-foreground, #666);
  font-size: 14px;
}

.demo-spinner {
  width: 20px;
  height: 20px;
  border: 2px solid var(--border, #e5e5e5);
  border-top-color: var(--primary, #333);
  border-radius: 50%;
  animation: demo-spin 0.8s linear infinite;
}

@keyframes demo-spin {
  to { transform: rotate(360deg); }
}
</style>
