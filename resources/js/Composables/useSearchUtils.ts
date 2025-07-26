import { format } from 'date-fns'
import { usePage, router } from '@inertiajs/vue3'
import { trans as $t } from 'laravel-vue-i18n'

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

  const getPreviewContent = (item: SearchItem) => {
    let content = ''
    if (item.type === 'news' && item.short) {
      content = item.short
    } else if (item.summary) {
      content = item.summary
    } else if (item.description) {
      content = item.description
    } else if (item.content) {
      content = item.content.slice(0, 500) + (item.content.length > 500 ? '...' : '')
    }
    
    return content.replace(/<script[^>]*>.*?<\/script>/gi, '')
                  .replace(/<style[^>]*>.*?<\/style>/gi, '')
                  .replace(/<[^>]+>/g, '')
                  .trim()
  }

  const getTypeBadgeText = (type: string) => {
    switch (type) {
      case 'documents': return $t('Document')
      case 'pages': return $t('Page')
      case 'news': return $t('News')
      case 'calendar': return $t('Event')
      default: return type
    }
  }

  const getBadgeClasses = (type: string) => {
    switch (type) {
      case 'news': return 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 border-blue-200'
      case 'pages': return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300 border-green-200'
      case 'documents': return 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300 border-purple-200'
      case 'calendar': return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300 border-amber-200'
      default: return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'
    }
  }

  const getItemDate = (item: SearchItem) => {
    switch (item.type) {
      case 'news': return item.publish_time
      case 'documents': return item.document_date
      case 'calendar': return item.date
      default: return item.created_at
    }
  }

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

  return {
    formatDate,
    getPreviewContent,
    getTypeBadgeText,
    getBadgeClasses,
    getItemDate,
    getItemUrl,
    trackSearchInteraction,
    navigateToItem
  }
}