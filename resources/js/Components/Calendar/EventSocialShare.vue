<template>
  <div class="event-social-share">
    <!-- Native Share (Mobile Priority) -->
    <Button v-if="supportsNativeShare"
      class="w-full gap-2 bg-red-600 text-white shadow-sm transition-all duration-200 hover:bg-red-700 hover:shadow-md dark:bg-red-700 dark:hover:bg-red-800"
      :disabled="isSharing" @click="handleNativeShare">
      <IFluentShare20Regular class="h-4 w-4" />
      {{ isSharing ? $t('Dalinamasi...') : $t('Dalinkis') }}
    </Button>

    <!-- Traditional Social Buttons -->
    <div v-else class="space-y-4">
      <div class="text-base font-medium text-zinc-900 dark:text-zinc-100">
        {{ $t('Dalinkis:') }}
      </div>

      <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <!-- Facebook -->
        <Button variant="outline"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          :title="$t('Dalinkis Facebook')" @click="shareToFacebook">
          <IMdiFacebook class="h-5 w-5 text-blue-600 dark:text-blue-400" />
          <span class="hidden sm:inline font-medium">Facebook</span>
        </Button>

        <!-- Twitter/X -->
        <Button variant="outline"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          :title="$t('Dalinkis X (Twitter)')" @click="shareToTwitter">
          <IMdiTwitter class="h-5 w-5 text-sky-600 dark:text-sky-400" />
          <span class="hidden sm:inline font-medium">X</span>
        </Button>

        <!-- LinkedIn -->
        <Button variant="outline"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          :title="$t('Dalinkis LinkedIn')" @click="shareToLinkedIn">
          <IMdiLinkedin class="h-5 w-5 text-blue-700 dark:text-blue-400" />
          <span class="hidden sm:inline font-medium">LinkedIn</span>
        </Button>

        <!-- Email -->
        <Button variant="outline"
          class="gap-2 border-zinc-300 text-zinc-700 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-300 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
          :title="$t('Dalinkis el. paštu')" @click="shareViaEmail">
          <IFluentMail20Regular class="h-5 w-5 text-zinc-600 dark:text-zinc-400" />
          <span class="hidden sm:inline font-medium">{{ $t('El. paštas') }}</span>
        </Button>
      </div>

      <!-- Copy Link -->
      <Button variant="ghost"
        class="w-full gap-2 text-sm border border-dashed border-zinc-300 text-zinc-600 transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-600 dark:text-zinc-400 dark:hover:border-zinc-500 dark:hover:bg-zinc-800"
        :disabled="isCopying" @click="copyToClipboard">
        <component :is="copyIcon" class="h-4 w-4" />
        {{ copyButtonText }}
      </Button>
    </div>

    <!-- Share Analytics (Optional) -->
    <div v-if="showAnalytics && shareCount > 0" class="mt-4 text-xs text-zinc-500 text-center">
      {{ $t('Pasidalinta {count} kartų', { count: shareCount.toString() }) }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'

import { formatStaticTime } from '@/Utils/IntlTime'
import { LocaleEnum } from '@/Types/enums'
import Button from '@/Components/ui/button/Button.vue'

interface Props {
  event: App.Entities.Calendar
  url?: string
  locale?: string
  variant?: 'default' | 'compact' | 'sidebar'
  showAnalytics?: boolean
  shareCount?: number
}

const props = withDefaults(defineProps<Props>(), {
  url: typeof window !== 'undefined' ? window.location.href : '',
  locale: 'lt',
  variant: 'default',
  showAnalytics: false,
  shareCount: 0
})

// Reactive state
const isSharing = ref(false)
const isCopying = ref(false)
const copySuccess = ref(false)

// Check if browser supports native share API
const supportsNativeShare = computed(() => {
  return typeof navigator !== 'undefined' && 'share' in navigator
})

// Share data preparation
const shareTitle = computed(() => {
  return Array.isArray(props.event.title) ? props.event.title.join(' ') : (props.event.title || '')
})

const shareText = computed(() => {
  const title = shareTitle.value
  const dateText = formatStaticTime(
    new Date(props.event.date),
    { dateStyle: 'medium', timeStyle: 'short' },
  // Ensure locale is one of our supported enums
  (props.locale === LocaleEnum.EN || props.locale === 'en') ? LocaleEnum.EN : LocaleEnum.LT
  )
  return `${title} - ${dateText}`
})

const shareDescription = computed(() => {
  // Strip HTML and truncate description
  const description = Array.isArray(props.event.description)
    ? props.event.description.join(' ')
    : (props.event.description || '')
  // Repeatedly remove tags to handle nested/malformed HTML without external deps
  const tagRegex = /<[^>]*>/g
  let cleanDescription = description
  let prev: string
  do {
    prev = cleanDescription
    cleanDescription = cleanDescription.replace(tagRegex, '')
  } while (cleanDescription !== prev)
  return cleanDescription.length > 200 ? `${cleanDescription.substring(0, 200)}...` : cleanDescription
})

// Copy button state
const copyIcon = computed(() => {
  if (isCopying.value) return 'IFluentArrowSync20Regular'
  if (copySuccess.value) return 'IFluentCheckmark20Regular'
  return 'IFluentCopy20Regular'
})

const copyButtonText = computed(() => {
  if (isCopying.value) return $t('Kopijuojama...')
  if (copySuccess.value) return $t('Nukopijuota!')
  return $t('Kopijuoti nuorodą')
})

// Native share functionality
const handleNativeShare = async () => {
  if (!supportsNativeShare.value) return

  isSharing.value = true

  try {
    await navigator.share({
      title: shareTitle.value,
      text: shareText.value,
      url: props.url
    })

  } catch (error) {
    console.log('Share cancelled or failed:', error)
    // Fallback to clipboard copy
    await copyToClipboard()
  } finally {
    isSharing.value = false
  }
}

// Social platform sharing
const shareToFacebook = () => {
  const url = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(props.url)}`
  openShareWindow(url, 'Facebook')
}

const shareToTwitter = () => {
  const text = `${shareText.value}\n\n${props.url}`
  const url = `https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`
  openShareWindow(url, 'Twitter')
}

const shareToLinkedIn = () => {
  const url = `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(props.url)}`
  openShareWindow(url, 'LinkedIn')
}

const shareViaEmail = () => {
  const subject = encodeURIComponent(shareTitle.value)
  const body = encodeURIComponent(`${shareText.value}\n\n${shareDescription.value}\n\n${props.url}`)
  const url = `mailto:?subject=${subject}&body=${body}`
  window.location.href = url
}

// Clipboard functionality
const copyToClipboard = async () => {
  if (isCopying.value) return

  isCopying.value = true

  try {
    await navigator.clipboard.writeText(props.url)
    copySuccess.value = true

    // Reset success state after 2 seconds
    setTimeout(() => {
      copySuccess.value = false
    }, 2000)
  } catch (error) {
    console.error('Failed to copy to clipboard:', error)
    // Fallback for older browsers
    fallbackCopyToClipboard()
  } finally {
    isCopying.value = false
  }
}

// Fallback copy method for older browsers
const fallbackCopyToClipboard = () => {
  const textArea = document.createElement('textarea')
  textArea.value = props.url
  textArea.style.position = 'fixed'
  textArea.style.opacity = '0'
  document.body.appendChild(textArea)
  textArea.select()

  try {
    document.execCommand('copy')
    copySuccess.value = true
    setTimeout(() => {
      copySuccess.value = false
    }, 2000)
  } catch (error) {
    console.error('Fallback copy failed:', error)
  } finally {
    document.body.removeChild(textArea)
  }
}

// Share window helper
const openShareWindow = (url: string, platform: string) => {
  const width = 600
  const height = 400
  const left = (window.innerWidth - width) / 2
  const top = (window.innerHeight - height) / 2

  window.open(
    url,
    `share-${platform}`,
    `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
  )
}

</script>

<style scoped>
/* Loading animation for spinner */
@keyframes spin {
  from {
    transform: rotate(0deg);
  }

  to {
    transform: rotate(360deg);
  }
}

/* Custom spinner animation */
.IFluentArrowSync20Regular {
  animation: spin 1s linear infinite;
}

/* Responsive grid adjustments */
@media (max-width: 640px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

/* Focus states for accessibility */
button:focus-visible {
  outline: 2px solid rgb(239 68 68);
  outline-offset: 2px;
}

/* Smooth transitions */
.transition-colors {
  transition-property: color, background-color, border-color;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 150ms;
}
</style>
