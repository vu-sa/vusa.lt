import { fn } from 'storybook/test';

/**
 * Centralized route mock for Storybook
 * Provides realistic route generation for all Laravel routes used in the application
 */

// Mock route function that returns realistic URLs
export const route = fn((name: string, params: any = {}, absolute = true) => {
  const baseUrl = absolute ? 'http://localhost:8000' : '';
  
  // Comprehensive Laravel routes used in the application
  const routeMap: Record<string, string> = {
    // Admin routes
    'documents.index': '/mano/documents',
    'documents.show': '/mano/documents/:id',
    'documents.create': '/mano/documents/create',
    'documents.edit': '/mano/documents/:id/edit',
    'documents.store': '/mano/documents',
    'documents.update': '/mano/documents/:id',
    'documents.destroy': '/mano/documents/:id',
    
    // Meeting routes
    'meetings.index': '/mano/meetings',
    'meetings.show': '/mano/meetings/:id',
    'meetings.create': '/mano/meetings/create',
    'meetings.edit': '/mano/meetings/:id/edit',
    'meetings.store': '/mano/meetings',
    'meetings.update': '/mano/meetings/:id',
    'meetings.destroy': '/mano/meetings/:id',
    
    // Institution routes
    'institutions.index': '/mano/institutions',
    'institutions.show': '/mano/institutions/:id',
    'institutions.create': '/mano/institutions/create',
    'institutions.edit': '/mano/institutions/:id/edit',
    
    // Public routes
    'public.documents': '/dokumentai',
    'public.document': '/dokumentai/:id',
    'home': '/',
    'login': '/login',
    'register': '/register',
    
    // API routes
    'api.documents.search': '/api/documents/search',
    'api.documents.facets': '/api/documents/facets',
    'api.types': '/api/types',
    'api.institutions': '/api/institutions',
    
    // User routes
    'profile.edit': '/profile',
    'profile.update': '/profile',
    'profile.destroy': '/profile',
  };
  
  let url = routeMap[name] || `/mock-route/${name}`;
  
  // Replace route parameters
  if (params && typeof params === 'object') {
    Object.keys(params).forEach(key => {
      url = url.replace(`:${key}`, params[key]);
    });
  }
  
  return baseUrl + url;
});

// Mock Ziggy global object
export const Ziggy = {
  url: 'http://localhost:8000',
  port: 8000,
  defaults: {},
  routes: {
    'documents.index': {
      uri: 'mano/documents',
      methods: ['GET', 'HEAD']
    },
    'public.documents': {
      uri: 'dokumentai',
      methods: ['GET', 'HEAD']
    },
    'meetings.index': {
      uri: 'mano/meetings',
      methods: ['GET', 'HEAD']
    }
  }
};

// Export as default
export default { route, Ziggy };