import { usePage, router } from '@inertiajs/vue3'
import { format } from 'date-fns'

// Import icons for content types
import IconNews from '~icons/fluent/news20-regular'
import IconPage from '~icons/fluent/document-text20-regular'
import IconDocument from '~icons/fluent/document20-regular'
import IconCalendar from '~icons/fluent/calendar20-regular'

export interface SearchItem {
  id: string | number
  title: string
  type: 'documents' | 'pages' | 'news' | 'calendar'
  description?: string
  content?: string
  summary?: string
  short?: string
  image_url?: string
  image?: string
  created_at?: string
  publish_time?: string
  document_date?: string
  date?: string
  permalink?: string
  anonymous_url?: string
  tenant_name?: string
  lang?: string
  name?: string
  [key: string]: any
}

export const useSearchUtils = () => {
  const page = usePage()

  const getItemUrl = (item: SearchItem): string => {
    const baseParams = {
      lang: item.lang || page.props.app.locale,
      subdomain: page.props.tenant?.subdomain ?? 'www'
    }

    try {
      switch (item.type) {
        case 'news':
          return route('news', { ...baseParams, news: item.permalink, newsString: 'naujiena' })
        case 'pages':
          return route('page', { ...baseParams, permalink: item.permalink })
        case 'calendar':
          return route('calendar.event', { ...baseParams, calendar: item.id })
        case 'documents':
          return item.anonymous_url || '#'
        default:
          return '#'
      }
    } catch (e) {
      console.error('Error generating URL:', e)
      return '#'
    }
  }

  const trackSearchInteraction = (action: string, data: Record<string, any>) => {
    if (typeof window !== 'undefined' && (window as any).posthog) {
      (window as any).posthog.capture(`search_${action}`, {
        ...data,
        timestamp: new Date().toISOString()
      })
    }
    
    try {
      const searches = JSON.parse(localStorage.getItem('search_analytics') || '[]')
      searches.push({ action, ...data, timestamp: Date.now() })
      if (searches.length > 100) {
        searches.splice(0, searches.length - 100)
      }
      localStorage.setItem('search_analytics', JSON.stringify(searches))
    } catch (e) {
      // Ignore localStorage errors
    }
  }

  const navigateToItem = (item: SearchItem) => {
    trackSearchInteraction('result_navigate', {
      item_type: item.type,
      item_id: item.id
    })
    
    router.visit(getItemUrl(item))
  }

  // Shared utility functions for search components
  const getIconComponent = (type: string) => {
    switch (type) {
      case 'news': return IconNews
      case 'pages': return IconPage
      case 'documents': return IconDocument
      case 'calendar': return IconCalendar
      default: return IconPage
    }
  }

  const formatDate = (dateValue: string | number | undefined) => {
    if (!dateValue) return ''
    
    try {
      let date: Date
      
      if (typeof dateValue === 'number') {
        const timestamp = dateValue < 10000000000 ? dateValue * 1000 : dateValue
        date = new Date(timestamp)
      } else {
        date = new Date(dateValue)
      }
      
      if (isNaN(date.getTime())) {
        return String(dateValue)
      }
      
      return format(date, 'MMM dd, yyyy')
    } catch {
      return String(dateValue)
    }
  }

  const stripHtml = (html: string): string => {
    if (!html) return ''
    const tmp = document.createElement('div')
    tmp.innerHTML = html
    return tmp.textContent || tmp.innerText || ''
  }

  const getItemDate = (item: any) => {
    switch (item.type) {
      case 'news': return item.publish_time
      case 'documents': return item.document_date
      case 'calendar': return item.date
      default: return item.created_at
    }
  }

  const getItemContent = (item: any) => {
    let content = ''
    switch (item.type) {
      case 'news': 
        content = item.short || item.summary || item.content
        break
      case 'documents': 
        content = item.summary || item.content
        break
      case 'pages': 
        content = item.content || item.summary
        break
      case 'calendar': 
        content = item.description || item.summary
        break
      default: 
        content = item.summary || item.content || item.description
    }
    
    // Expand content length for better information display
    if (content && content.length > 200) {
      return content.slice(0, 200) + '...'
    }
    return content
  }

  // Shared styling classes
  const getIconClasses = () => {
    return 'w-6 h-6 rounded flex items-center justify-center bg-red-100 dark:bg-red-900/50 text-vusa-red dark:text-red-400'
  }

  const getSectionHeaderClasses = () => {
    return 'px-3 py-2 text-xs font-medium text-muted-foreground border-l-2 bg-red-50 dark:bg-red-950/30 border-vusa-red'
  }

  // Unified item classes for both section and unified results
  const getItemClasses = () => {
    return 'group mx-1 mb-2 p-3 rounded-lg border cursor-pointer hover:shadow-md transition-all duration-200 bg-white dark:bg-zinc-900 focus:outline-none focus:ring-2 focus:ring-vusa-red border-zinc-200 dark:border-zinc-700 hover:border-vusa-red/50 dark:hover:border-vusa-red/50 hover:bg-red-50/30 dark:hover:bg-red-950/10'
  }

  const getTypeBadgeClasses = () => {
    return 'text-xs border bg-red-100 text-vusa-red border-red-200 dark:bg-red-900/30 dark:text-red-300 dark:border-red-800'
  }

  const getLoadMoreClasses = () => {
    return 'text-vusa-red hover:text-vusa-red-secondary hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/30'
  }

  return {
    trackSearchInteraction,
    navigateToItem,
    getIconComponent,
    formatDate,
    stripHtml,
    getItemDate,
    getItemContent,
    getIconClasses,
    getSectionHeaderClasses,
    getItemClasses,
    getTypeBadgeClasses,
    getLoadMoreClasses
  }
}