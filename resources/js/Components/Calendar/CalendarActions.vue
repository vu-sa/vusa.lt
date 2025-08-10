<template>
  <div class="flex items-center gap-2">
    <!-- Primary participate button -->
    <NButton v-if="eventUrl" :type="isPast ? 'default' : 'primary'" :size :secondary="isPast"
      :tag="eventUrl ? 'a' : 'button'" :href="eventUrl" target="_blank" rel="noopener noreferrer" @click.stop>
      <template v-if="showIcons" #icon>
        <IFluentPersonAdd20Regular v-if="!isPast" />
        <IFluentArrowUpRight16Regular v-else />
      </template>
      {{ participateText }}
    </NButton>

    <!-- View details button -->
    <NButton v-if="showViewButton && eventDetailUrl" :secondary="!isPast" :size tag="a" :href="eventDetailUrl">
      {{ viewText }}
    </NButton>

    <!-- Social/External actions -->
    <div v-if="showSocialActions" class="flex items-center gap-1">
      <!-- Google Calendar -->
      <NPopover v-if="googleLink" trigger="hover">
        <template #trigger>
          <NButton :size secondary circle tag="a" :href="googleLink" target="_blank" rel="noopener noreferrer"
            @click.stop>
            <template #icon>
              <IMdiGoogle />
            </template>
          </NButton>
        </template>
        {{ $t("Įsidėk į Google kalendorių") }}
      </NPopover>

      <!-- Facebook Event -->
      <NPopover v-if="facebookUrl" trigger="hover">
        <template #trigger>
          <NButton :size secondary circle tag="a" :href="facebookUrl" target="_blank" @click.stop>
            <template #icon>
              <IMdiFacebook />
            </template>
          </NButton>
        </template>
        {{ $t("Facebook renginys") }}
      </NPopover>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { trans as $t } from 'laravel-vue-i18n'
import { NButton, NPopover } from 'naive-ui'
import { usePage } from '@inertiajs/vue3'

interface Props {
  /** Event participation/registration URL */
  eventUrl?: string
  /** Facebook event URL */
  facebookUrl?: string
  /** Google Calendar link */
  googleLink?: string
  /** URL to event detail page */
  eventDetailUrl?: string
  /** Event ID for generating detail URL */
  eventId?: number | string
  /** Whether this is for a past event */
  isPast?: boolean
  /** Button size */
  size?: 'tiny' | 'small' | 'medium' | 'large'
  /** Whether to show icons in buttons */
  showIcons?: boolean
  /** Whether to show view details button */
  showViewButton?: boolean
  /** Whether to show social media actions */
  showSocialActions?: boolean
  /** Layout variant */
  layout?: 'horizontal' | 'vertical' | 'compact'
}

const props = withDefaults(defineProps<Props>(), {
  isPast: false,
  size: 'medium',
  showIcons: true,
  showViewButton: true,
  showSocialActions: true,
  layout: 'horizontal'
})

const page = usePage()

// Generate event detail URL if not provided but eventId is available
const eventDetailUrl = computed(() => {
  if (props.eventDetailUrl) return props.eventDetailUrl

  if (props.eventId) {
    return route('calendar.event', {
      calendar: props.eventId,
      lang: page.props.app.locale
    })
  }

  return undefined
})

// Dynamic text based on context
const participateText = computed(() => {
  if (props.isPast) return $t("Peržiūrėti")

  if (props.eventUrl) {
    // Special handling for specific event types
    if (props.eventUrl.includes('freshmen-camps')) {
      return `${$t("Dalyvauk")}!`
    }
    return $t("Dalyvauk renginyje")
  }

  return $t("Daugiau")
})

const viewText = computed(() => {
  return props.isPast ? $t("Peržiūrėti") : $t("Daugiau")
})
</script>

<style scoped>
/* Compact layout adjustments */
.layout-compact .flex {
  gap: 0.25rem;
}

.layout-compact .NButton {
  min-width: auto;
}

/* Vertical layout */
.layout-vertical {
  flex-direction: column;
  align-items: stretch;
}

.layout-vertical .flex {
  justify-content: center;
}
</style>
