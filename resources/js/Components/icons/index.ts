// Main barrel export for all icons
// This allows clean imports from @/Components/icons

// =============================================================================
// TREE-SHAKABLE EXPORTS - Use these for best performance
// =============================================================================
// Re-export all named icon exports (perfect tree-shaking)
export * from './model-icons';
export * from './form-icons';
export * from './other-icons';

// =============================================================================
// DYNAMIC HELPERS - WARNING: These import ALL icons in their category
// =============================================================================
// Only use these when you need dynamic icon selection at runtime
// Otherwise prefer direct named imports above for optimal bundle size

/**
 * Get model icon dynamically (imports ALL model icons)
 * @warning This will bundle all model icons, use direct imports when possible
 */
export { getModelIcon } from './model-icons';
