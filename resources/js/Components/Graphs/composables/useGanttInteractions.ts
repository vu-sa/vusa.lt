/**
 * useGanttInteractions - Gantt chart scroll, zoom, and navigation logic
 * 
 * Extracted from MeetingsGantt.vue to reduce component complexity and improve
 * maintainability. Handles:
 * - Infinite scroll (extending date range on scroll)
 * - Scale/zoom changes with viewport preservation
 * - Year navigation
 * - Initial viewport calculation for low zoom levels
 * - Scroll synchronization between panels
 */
import { ref, computed, nextTick, type Ref, type ComputedRef } from 'vue'
import * as d3 from 'd3'

export interface GanttInteractionOptions {
  /** Days to show before today */
  daysBefore: number
  /** Days to show after today */
  daysAfter: number
  /** Enable infinite scroll */
  infiniteScroll: boolean
  /** Threshold in pixels to trigger scroll extension */
  extendThresholdPx: number
  /** Number of days to add when extending */
  extendStepDays: number
  /** Optional start date (overrides daysBefore calculation) */
  startDate?: Date | null
  /** Center date timestamp ref from persisted settings (for restoration) */
  centerDateTimestamp?: Ref<number | null>
}

export interface GanttInteractionRefs {
  /** Main scrollable container */
  rightScroll: Ref<HTMLElement | null>
  /** Left labels container */
  leftLabels: Ref<HTMLElement | null>
  /** Current D3 time scale (set by render function) */
  curX: Ref<d3.ScaleTime<number, number> | null>
  /** Layout rows for tenant scrolling */
  layoutRows: ComputedRef<Array<{ key: string | number; top: number }>>
  /** External day width ref (from useGanttSettings) */
  dayWidthPx: Ref<number>
}

export interface GanttInteractionCallbacks {
  /** Called when day width changes (for persistence) */
  onDayWidthChange: (width: number) => void
}

const MIN_DAY_WIDTH = 3
const MAX_DAY_WIDTH = 36

export function useGanttInteractions(
  options: GanttInteractionOptions,
  refs: GanttInteractionRefs,
  callbacks: GanttInteractionCallbacks
) {
  // State - dayWidthPx comes from external ref (useGanttSettings)
  const dayWidthPx = refs.dayWidthPx
  const extraBefore = ref(0) // extra days added to the past
  const extraAfter = ref(0) // extra days added to the future
  const extending = ref(false) // prevents concurrent extensions
  const didInitialAutoScroll = ref(false)
  const currentYear = ref<number | null>(null)

  // Computed date range
  const today = () => {
    const d = new Date()
    d.setHours(0, 0, 0, 0)
    return d
  }

  const minTime = computed(() => {
    if (options.startDate) {
      return d3.timeDay.offset(options.startDate, -extraBefore.value)
    }
    return d3.timeDay.offset(today(), -(options.daysBefore + extraBefore.value))
  })

  const maxTime = computed(() => {
    return d3.timeDay.offset(today(), options.daysAfter + extraAfter.value)
  })

  /**
   * Calculate initial viewport extension needed at low zoom levels
   * Also extends timeline if stored center date is outside the initial range
   * Should be called after mount, before first render
   */
  function calculateInitialExtension(): { extraBefore: number; extraAfter: number } {
    const el = refs.rightScroll.value
    let neededBefore = 0
    let neededAfter = 0

    const storedTimestamp = options.centerDateTimestamp?.value
    const hasStoredDate = !!storedTimestamp

    // Calculate base timeline range (without extensions)
    const baseMinTime = options.startDate 
      ? options.startDate 
      : d3.timeDay.offset(today(), -options.daysBefore)
    const baseMaxTime = d3.timeDay.offset(today(), options.daysAfter)

    // If we have a stored center date, extend timeline to include it
    if (hasStoredDate) {
      const storedDate = new Date(storedTimestamp)

      // If stored date is before the initial range, extend backwards
      if (storedDate < baseMinTime) {
        neededBefore = d3.timeDay.count(storedDate, baseMinTime) + 30 // +30 buffer
      }
      // If stored date is after the initial range, extend forwards
      if (storedDate > baseMaxTime) {
        neededAfter = d3.timeDay.count(baseMaxTime, storedDate) + 30 // +30 buffer
      }
    }

    if (!el || !options.infiniteScroll) {
      return { extraBefore: neededBefore, extraAfter: neededAfter }
    }

    const currentDayWidth = dayWidthPx.value
    const viewportWidth = el.clientWidth
    const viewportDays = Math.ceil(viewportWidth / currentDayWidth)
    const totalLoadedDays = options.daysBefore + options.daysAfter

    // When we have a stored center date, we need to ensure there's enough timeline
    // on BOTH sides of that date to fill the viewport (half viewport each side)
    if (hasStoredDate) {
      const storedDate = new Date(storedTimestamp)
      const halfViewportDays = Math.ceil(viewportDays / 2) + 10 // +10 buffer for smooth scrolling

      // Calculate how many days we need before/after the stored date
      const effectiveMinTime = d3.timeDay.offset(baseMinTime, -neededBefore)
      const effectiveMaxTime = d3.timeDay.offset(baseMaxTime, neededAfter)

      // Days from stored date to current min/max
      const daysBeforeStoredDate = d3.timeDay.count(effectiveMinTime, storedDate)
      const daysAfterStoredDate = d3.timeDay.count(storedDate, effectiveMaxTime)

      // If not enough days before stored date for half viewport, extend
      if (daysBeforeStoredDate < halfViewportDays) {
        neededBefore += (halfViewportDays - daysBeforeStoredDate)
      }
      // If not enough days after stored date for half viewport, extend
      if (daysAfterStoredDate < halfViewportDays) {
        neededAfter += (halfViewportDays - daysAfterStoredDate)
      }

      return { extraBefore: neededBefore, extraAfter: neededAfter }
    }

    // No stored date - use symmetric extension to keep today centered
    // If viewport can show more days than we have loaded, extend the range
    if (viewportDays > totalLoadedDays) {
      const extraDaysNeeded = Math.ceil(viewportDays - totalLoadedDays)
      // Split extra days EQUALLY between before and after to keep today centered
      const extraEach = Math.ceil(extraDaysNeeded / 2)
      neededBefore = Math.max(neededBefore, extraEach)
      neededAfter = Math.max(neededAfter, extraEach)
    }

    // Even at normal zoom, ensure we have some buffer for smooth scrolling
    // Add buffer symmetrically to keep today centered
    const totalWidth = totalLoadedDays * currentDayWidth
    if (totalWidth < viewportWidth + 200) {
      const bufferDays = Math.ceil(200 / currentDayWidth) + 30
      neededBefore = Math.max(neededBefore, Math.ceil(bufferDays / 2))
      neededAfter = Math.max(neededAfter, Math.ceil(bufferDays / 2))
    }

    return { extraBefore: neededBefore, extraAfter: neededAfter }
  }

  /**
   * Apply initial extension - call this in onMounted before render
   */
  function applyInitialExtension() {
    const extension = calculateInitialExtension()
    extraBefore.value = extension.extraBefore
    extraAfter.value = extension.extraAfter
  }

  /**
   * Apply initial scroll position to center on stored date or today
   * Call this after render() when the x-scale is available
   * 
   * @param x - The D3 time scale created during render
   * @param marginLeft - Left margin offset for SVG positioning
   * @returns true if scroll was applied, false otherwise
   */
  function applyInitialScrollPosition(x: d3.ScaleTime<number, number>, marginLeft: number = 0): boolean {
    const el = refs.rightScroll.value
    if (!el || didInitialAutoScroll.value) return false

    // Ensure viewport has valid dimensions
    const viewportWidth = el.clientWidth
    if (viewportWidth <= 0) {
      // Viewport not ready yet, skip for now
      return false
    }

    // Use stored center date if available, otherwise use today
    const storedTimestamp = options.centerDateTimestamp?.value
    const targetDate = storedTimestamp ? new Date(storedTimestamp) : new Date()

    // Get the timeline range from x-scale domain
    const domain = x.domain()
    const domainMin = domain[0]
    const domainMax = domain[1]
    
    // Safety check for valid domain
    if (!domainMin || !domainMax) {
      return false
    }
    
    // Clamp target date to the current timeline range
    const clampedDate = new Date(
      Math.max(domainMin.getTime(), Math.min(domainMax.getTime(), targetDate.getTime()))
    )

    // Calculate SVG position (x-scale returns position within the g element)
    const svgPosition = x(clampedDate) + marginLeft
    
    // Calculate scroll position so the target date is centered in the viewport
    el.scrollLeft = Math.max(0, svgPosition - viewportWidth / 2)
    didInitialAutoScroll.value = true

    // Update current year badge
    updateCurrentYear()

    return true
  }

  /**
   * Update the current year indicator based on viewport center
   */
  function updateCurrentYear() {
    const el = refs.rightScroll.value
    const x = refs.curX.value
    if (!el || !x) return

    const center = el.scrollLeft + el.clientWidth / 2
    const d = x.invert(center)
    currentYear.value = d.getFullYear()
  }

  /**
   * Infinite scroll handler - extends date range when near edges
   */
  function onScroll() {
    if (!options.infiniteScroll) return
    const el = refs.rightScroll.value
    if (!el || extending.value) return

    const threshold = options.extendThresholdPx
    const stepDays = options.extendStepDays
    const atLeft = el.scrollLeft <= threshold
    const atRight = (el.scrollLeft + el.clientWidth) >= (el.scrollWidth - threshold)

    if (atLeft) {
      extending.value = true
      const prev = el.scrollLeft
      extraBefore.value += stepDays
      nextTick(() => {
        // Preserve viewport by shifting right by added width
        el.scrollLeft = prev + stepDays * dayWidthPx.value
        extending.value = false
      })
    } else if (atRight) {
      extending.value = true
      extraAfter.value += stepDays
      nextTick(() => {
        extending.value = false
      })
    }

    // Update year badge on scroll
    updateCurrentYear()
  }

  /**
   * Handle scale/zoom changes while preserving viewport center
   */
  function onScaleChange(values?: number[]) {
    const newWidth = Math.max(MIN_DAY_WIDTH, Math.min(MAX_DAY_WIDTH, values?.[0] ?? dayWidthPx.value))
    const el = refs.rightScroll.value
    const x = refs.curX.value

    let anchorDate: Date | null = null
    let centerOffset = 0

    if (el && x) {
      centerOffset = el.clientWidth / 2
      const center = el.scrollLeft + centerOffset
      anchorDate = x.invert(center)
    }

    dayWidthPx.value = newWidth
    callbacks.onDayWidthChange(newWidth)

    nextTick(() => {
      const newX = refs.curX.value
      if (el && newX && anchorDate) {
        const target = newX(anchorDate) - centerOffset
        el.scrollLeft = Math.max(0, target)
      }
    })
  }

  /**
   * Navigate to a specific year offset from current view
   */
  function navigateYears(years: number) {
    const el = refs.rightScroll.value
    if (!el || currentYear.value === null) return

    const targetYear = currentYear.value + years
    const targetDate = new Date(targetYear, 6, 1) // July 1st (middle of year)

    // Check if we need to extend the timeline
    if (targetDate < minTime.value) {
      const daysNeeded = d3.timeDay.count(targetDate, minTime.value) + 30
      extraBefore.value += daysNeeded
    } else if (targetDate > maxTime.value) {
      const daysNeeded = d3.timeDay.count(maxTime.value, targetDate) + 30
      extraAfter.value += daysNeeded
    }

    // Scroll after render
    nextTick(() => {
      const x = refs.curX.value
      if (!x) return
      const centerOffset = el.clientWidth / 2
      const target = x(targetDate) - centerOffset
      el.scrollLeft = Math.max(0, target)
      currentYear.value = targetYear
    })
  }

  /**
   * Navigate to today's date
   */
  function navigateToToday() {
    const el = refs.rightScroll.value
    const x = refs.curX.value
    if (!el || !x) return

    const todayDate = today()
    const centerOffset = el.clientWidth / 2
    const target = x(todayDate) - centerOffset

    el.scrollLeft = Math.max(0, target)
    currentYear.value = todayDate.getFullYear()
  }

  /**
   * Scroll vertically to show a specific tenant's section
   */
  function scrollToTenant(tenantId: string | number) {
    const el = refs.rightScroll.value
    if (!el) return

    const tenantRowKey = `__tenant__:${tenantId}`
    const tenantRow = refs.layoutRows.value.find(r => r.key === tenantRowKey)

    if (tenantRow) {
      el.scrollTop = tenantRow.top
    }
  }

  /**
   * Setup vertical scroll synchronization between labels and timeline
   * Should be called in onMounted
   */
  function setupVerticalScrollSync(isFullscreen: boolean) {
    const timelineScroll = refs.rightScroll.value
    const labelsContainer = refs.leftLabels.value

    if (!timelineScroll || !labelsContainer) return

    let isSyncing = false

    const syncVerticalScroll = () => {
      if (isSyncing) return
      isSyncing = true

      const { scrollTop } = timelineScroll

      if (isFullscreen) {
        labelsContainer.scrollTop = scrollTop
      } else {
        const labelsContent = labelsContainer.querySelector('.grid') as HTMLElement | null
        if (labelsContent) {
          labelsContent.style.transform = `translateY(-${scrollTop}px)`
        }
      }

      requestAnimationFrame(() => { isSyncing = false })
    }

    const syncFromLabels = () => {
      if (isSyncing || !isFullscreen) return
      isSyncing = true
      timelineScroll.scrollTop = labelsContainer.scrollTop
      requestAnimationFrame(() => { isSyncing = false })
    }

    timelineScroll.addEventListener('scroll', syncVerticalScroll, { passive: true })
    labelsContainer.addEventListener('scroll', syncFromLabels, { passive: true })

    // Return cleanup function
    return () => {
      timelineScroll.removeEventListener('scroll', syncVerticalScroll)
      labelsContainer.removeEventListener('scroll', syncFromLabels)
    }
  }

  /**
   * Attach infinite scroll handler
   * Should be called in onMounted
   */
  function attachScrollHandler() {
    const el = refs.rightScroll.value
    if (!el || !options.infiniteScroll) return

    el.addEventListener('scroll', onScroll, { passive: true })

    // Return cleanup function
    return () => {
      el.removeEventListener('scroll', onScroll)
    }
  }

  /**
   * Navigate by a specific number of days (negative = left, positive = right)
   */
  function navigateDays(days: number) {
    const el = refs.rightScroll.value
    if (!el) return
    
    const pixelsToScroll = days * dayWidthPx.value
    el.scrollLeft = Math.max(0, el.scrollLeft + pixelsToScroll)
    updateCurrentYear()
  }

  /**
   * Navigate by weeks (negative = left, positive = right)
   */
  function navigateWeeks(weeks: number) {
    navigateDays(weeks * 7)
  }

  /**
   * Scroll vertically by a given number of pixels (negative = up, positive = down)
   */
  function scrollVertically(pixels: number) {
    const el = refs.rightScroll.value
    if (!el) return
    
    el.scrollTop = Math.max(0, el.scrollTop + pixels)
  }

  /**
   * Zoom in (increase day width) by a step amount
   */
  function zoomIn(step: number = 3) {
    const newWidth = Math.min(MAX_DAY_WIDTH, dayWidthPx.value + step)
    onScaleChange([newWidth])
  }

  /**
   * Zoom out (decrease day width) by a step amount
   */
  function zoomOut(step: number = 3) {
    const newWidth = Math.max(MIN_DAY_WIDTH, dayWidthPx.value - step)
    onScaleChange([newWidth])
  }

  /**
   * Navigate by months (negative = left, positive = right)
   */
  function navigateMonths(months: number) {
    navigateDays(months * 30)
  }

  /**
   * Attach keyboard navigation handler
   * Should be called in onMounted
   * 
   * Keyboard shortcuts:
   * - Left/Right arrows: Navigate by 1 week
   * - Shift + Left/Right arrows: Navigate by 1 month
   * - Up/Down arrows: Scroll vertically by ~3 rows
   * - Shift + Up/Down arrows: Scroll vertically by ~10 rows
   * - +/= : Zoom in (increase day width)
   * - -/_ : Zoom out (decrease day width)
   * - Home: Go to today
   * - Page Up/Down: Navigate by 3 months
   */
  function attachKeyboardHandler(containerEl: HTMLElement | null) {
    if (!containerEl) return

    // Track if mouse is over the container
    let isHovering = false
    
    const handleMouseEnter = () => { isHovering = true }
    const handleMouseLeave = () => { isHovering = false }
    
    containerEl.addEventListener('mouseenter', handleMouseEnter)
    containerEl.addEventListener('mouseleave', handleMouseLeave)

    const handleKeyDown = (event: KeyboardEvent) => {
      // Handle if:
      // 1. Focus is within the container, OR
      // 2. Focus is on body AND mouse is hovering over container
      const focusInContainer = containerEl.contains(document.activeElement)
      const focusOnBodyAndHovering = document.activeElement === document.body && isHovering
      
      if (!focusInContainer && !focusOnBodyAndHovering) {
        return
      }

      // Skip if user is typing in an input
      const target = event.target as HTMLElement
      if (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable) {
        return
      }

      switch (event.key) {
        case 'ArrowLeft':
          event.preventDefault()
          if (event.shiftKey) {
            navigateMonths(-1)
          } else {
            navigateWeeks(-1)
          }
          break
        case 'ArrowRight':
          event.preventDefault()
          if (event.shiftKey) {
            navigateMonths(1)
          } else {
            navigateWeeks(1)
          }
          break
        case 'ArrowUp':
          event.preventDefault()
          scrollVertically(event.shiftKey ? -300 : -100)
          break
        case 'ArrowDown':
          event.preventDefault()
          scrollVertically(event.shiftKey ? 300 : 100)
          break
        case '+':
        case '=':
          event.preventDefault()
          zoomIn()
          break
        case '-':
        case '_':
          event.preventDefault()
          zoomOut()
          break
        case 'Home':
          event.preventDefault()
          navigateToToday()
          break
        case 'PageUp':
          event.preventDefault()
          navigateMonths(-3)
          break
        case 'PageDown':
          event.preventDefault()
          navigateMonths(3)
          break
      }
    }

    // Add listener to document to catch keys when container or its children are focused
    document.addEventListener('keydown', handleKeyDown)

    // Return cleanup function
    return () => {
      document.removeEventListener('keydown', handleKeyDown)
      containerEl.removeEventListener('mouseenter', handleMouseEnter)
      containerEl.removeEventListener('mouseleave', handleMouseLeave)
    }
  }

  return {
    // State
    dayWidthPx,
    extraBefore,
    extraAfter,
    extending,
    didInitialAutoScroll,
    currentYear,

    // Computed
    minTime,
    maxTime,

    // Methods
    applyInitialExtension,
    applyInitialScrollPosition,
    updateCurrentYear,
    onScroll,
    onScaleChange,
    zoomIn,
    zoomOut,
    navigateYears,
    navigateToToday,
    navigateDays,
    navigateWeeks,
    navigateMonths,
    scrollVertically,
    scrollToTenant,
    setupVerticalScrollSync,
    attachScrollHandler,
    attachKeyboardHandler,
  }
}
