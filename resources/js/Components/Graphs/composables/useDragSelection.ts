/**
 * useDragSelection - Shift+drag to create check-in periods
 * 
 * Composable for handling Shift+drag selection on Gantt chart rows.
 * When user holds Shift and drags on an institution row, a selection
 * rectangle is shown and on mouseup, a check-in creation event is emitted.
 */
import { ref, onUnmounted, type Ref, type ComputedRef } from 'vue'
import * as d3 from 'd3'

export interface DragSelectionState {
  /** Whether currently dragging */
  isDragging: boolean
  /** Institution ID of the row being dragged on */
  institutionId: string | number | null
  /** Drag start date */
  startDate: Date | null
  /** Current drag end date */
  endDate: Date | null
  /** Pixel X position of drag start */
  startX: number
  /** Pixel Y position (row center) */
  y: number
  /** Row height for selection rectangle */
  rowHeight: number
}

export interface LayoutRow {
  key: string | number
  type: 'tenant' | 'institution'
  tenantId?: string | number
  institutionId?: string | number
  top: number
  height: number
}

export interface DragSelectionOptions {
  /** Minimum drag distance in pixels before selection starts */
  minDragDistance?: number
}

export interface DragSelectionCallbacks {
  /** Called when drag selection completes successfully */
  onDragComplete?: (payload: { institution_id: string | number; startDate: Date; endDate: Date }) => void
}

/**
 * Create a drag selection composable for Gantt chart check-in creation
 */
export function useDragSelection(
  /** Reference to the scroll container element */
  containerRef: Ref<HTMLElement | null>,
  /** Reference to the main SVG element */
  svgRef: Ref<SVGSVGElement | null>,
  /** D3 time scale for date conversion */
  curX: Ref<d3.ScaleTime<number, number> | null>,
  /** Layout rows with position info */
  layoutRows: ComputedRef<LayoutRow[]>,
  /** Callbacks for drag events */
  callbacks: DragSelectionCallbacks = {},
  /** Options */
  options: DragSelectionOptions = {}
) {
  const { minDragDistance = 10 } = options

  // Drag state
  const state = ref<DragSelectionState>({
    isDragging: false,
    institutionId: null,
    startDate: null,
    endDate: null,
    startX: 0,
    y: 0,
    rowHeight: 0,
  })

  // Track if Shift is held
  const isShiftHeld = ref(false)

  // Track initial mouse position for distance calculation
  let initialMouseX = 0
  let dragThresholdMet = false

  /**
   * Find the layout row at the given Y position
   */
  function findRowByY(y: number): LayoutRow | undefined {
    for (const row of layoutRows.value) {
      if (y >= row.top && y < row.top + row.height) {
        return row
      }
    }
    return undefined
  }

  /**
   * Handle mousedown - start potential drag if Shift is held
   */
  function handleMouseDown(event: MouseEvent) {
    if (!event.shiftKey || !curX.value || !containerRef.value || !svgRef.value) return

    const svg = svgRef.value

    const [mx, my] = d3.pointer(event, svg)
    const row = findRowByY(my)

    // Only start drag on institution rows
    if (!row || row.type !== 'institution') return

    // Store initial position
    initialMouseX = mx
    dragThresholdMet = false
    isShiftHeld.value = true

    // Set up potential drag state (but don't mark as dragging yet)
    const date = d3.timeDay.floor(curX.value.invert(mx))
    
    state.value = {
      isDragging: false, // Will become true once threshold is met
      institutionId: row.institutionId!,
      startDate: date,
      endDate: date,
      startX: mx,
      y: row.top,
      rowHeight: row.height,
    }

    // Add document-level listeners
    document.addEventListener('mousemove', handleMouseMove)
    document.addEventListener('mouseup', handleMouseUp)
    document.addEventListener('keyup', handleKeyUp)

    // Prevent text selection during drag
    event.preventDefault()
  }

  /**
   * Handle mousemove - update selection if dragging
   */
  function handleMouseMove(event: MouseEvent) {
    if (!isShiftHeld.value || !curX.value || !containerRef.value || !svgRef.value) return

    // Check if Shift is still held
    if (!event.shiftKey) {
      cancelDrag()
      return
    }

    const svg = svgRef.value

    const [mx] = d3.pointer(event, svg)

    // Check if we've met the minimum drag distance
    if (!dragThresholdMet) {
      const distance = Math.abs(mx - initialMouseX)
      if (distance < minDragDistance) return
      
      dragThresholdMet = true
      state.value.isDragging = true
    }

    // Update end date
    const endDate = d3.timeDay.floor(curX.value.invert(mx))
    state.value.endDate = endDate
  }

  /**
   * Handle mouseup - complete or cancel drag
   */
  function handleMouseUp(event: MouseEvent) {
    // Remove listeners first
    document.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseup', handleMouseUp)
    document.removeEventListener('keyup', handleKeyUp)

    // If we were actually dragging (threshold met), emit the event
    if (state.value.isDragging && state.value.startDate && state.value.endDate && state.value.institutionId !== null) {
      // Ensure dates are in correct order
      const start = state.value.startDate < state.value.endDate ? state.value.startDate : state.value.endDate
      const end = state.value.startDate < state.value.endDate ? state.value.endDate : state.value.startDate

      callbacks.onDragComplete?.({
        institution_id: state.value.institutionId,
        startDate: start,
        endDate: end,
      })
    }

    // Reset state
    resetState()
  }

  /**
   * Handle keyup - cancel drag if Shift is released
   */
  function handleKeyUp(event: KeyboardEvent) {
    if (event.key === 'Shift') {
      cancelDrag()
    }
  }

  /**
   * Cancel the current drag operation
   */
  function cancelDrag() {
    document.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseup', handleMouseUp)
    document.removeEventListener('keyup', handleKeyUp)
    resetState()
  }

  /**
   * Reset drag state
   */
  function resetState() {
    isShiftHeld.value = false
    dragThresholdMet = false
    state.value = {
      isDragging: false,
      institutionId: null,
      startDate: null,
      endDate: null,
      startX: 0,
      y: 0,
      rowHeight: 0,
    }
  }

  /**
   * Attach event listeners to the container
   * Returns a cleanup function
   */
  function attachDragHandler(): () => void {
    const container = containerRef.value
    if (!container) return () => {}

    container.addEventListener('mousedown', handleMouseDown)

    return () => {
      container.removeEventListener('mousedown', handleMouseDown)
      // Also clean up any in-progress drag
      document.removeEventListener('mousemove', handleMouseMove)
      document.removeEventListener('mouseup', handleMouseUp)
      document.removeEventListener('keyup', handleKeyUp)
    }
  }

  // Cleanup on unmount
  onUnmounted(() => {
    document.removeEventListener('mousemove', handleMouseMove)
    document.removeEventListener('mouseup', handleMouseUp)
    document.removeEventListener('keyup', handleKeyUp)
  })

  return {
    /** Current drag selection state */
    state,
    /** Whether currently in drag mode */
    isDragging: ref(state.value.isDragging),
    /** Attach drag handler to container, returns cleanup function */
    attachDragHandler,
    /** Cancel current drag operation */
    cancelDrag,
  }
}

export type { DragSelectionState as DragState }
