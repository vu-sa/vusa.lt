import { usePage, router } from '@inertiajs/vue3'

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

  return {
    trackSearchInteraction,
    navigateToItem
  }
}