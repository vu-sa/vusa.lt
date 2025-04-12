# GitHub Copilot Instructions for vusa.lt

This document provides context and guidelines for GitHub Copilot when working with the VU Students' Representation (VU SA) website project.

## Application Purpose

vusa.lt serves as a dual-purpose platform:
1. **Public Website**: The public-facing website for VU SA, providing information about the organization, student events, news, and services.
2. **Internal System**: A comprehensive student representation management platform (vusa.lt/mano) that enables:
   - Managing and reporting on meetings with university administration
   - Tracking student representation activities across university committees
   - Creating and managing institutional goals and related tasks
   - Monitoring student opinion collection and representation outcomes
   - Maintaining institutional memory for future student representatives

The core focus is facilitating effective student representation while providing tools for the organization's day-to-day operations.

## Project Overview

vusa.lt is a student representation website for Vilnius University Students' Representation. The project serves many organizational needs while maintaining high code quality standards.

### Key Technologies

- **Backend**: Laravel 12+ (PHP 8.3+)
- **Frontend**: Vue 3 with Inertia.js
- **Styling**: Tailwind CSS 4
- **UI Components**: Shadcn Vue (transitioning from Naive UI)
- **Database**: MySQL
- **Testing**: Pest/PHPUnit for PHP, Vitest for JavaScript
- **Internationalization**: Laravel-vue-i18n with multi-language support (lt/en)
- **Documentation**: VitePress (stored in the docs directory)
- **Rich Content**: Tiptap editor with custom extensions

### Project Structure

- `app/` - Laravel application code
- `resources/js/` - Vue components and frontend code
- `resources/css/` - CSS files (Tailwind)
- `lang/` - Localization files
- `docs/` - User documentation and guides
- `tests/` - Test files for the application

## Development Principles

1. **Code Quality**: Prioritize clean, maintainable code over quick fixes.
2. **Simplicity**: Keep implementations simple and straightforward when possible.
3. **Sustainability**: Consider long-term maintainability, as the project has limited developer resources.
4. **Documentation**: Add inline documentation for complex logic or patterns.
5. **Testing**: Ensure proper test coverage for new features.

## Laravel-Specific Guidelines

1. Use Laravel's built-in features and conventions where possible.
2. Follow Laravel naming conventions for controllers, models, and other components.
3. Utilize the Spatie packages already integrated (permissions, media library, translatable, etc.).
4. Leverage Laravel's middleware for authentication and authorization.
5. Use Laravel's Eloquent ORM for database interactions.

## Vue/Frontend Guidelines

1. Use Vue 3 Composition API for new components.
2. Implement responsive designs using Tailwind CSS.
3. Work within the Inertia.js pattern for page components.
4. Follow accessibility (a11y) best practices.
5. Maintain consistent component structure and naming patterns.
6. Use Shadcn Vue components for consistent UI elements, following their implementation patterns.
7. Transition existing Naive UI components to Shadcn Vue where feasible.
8. Use defineAsyncComponent for code splitting where appropriate, especially for larger components.

## UI Component Guidelines

1. **Shadcn Vue**: The primary UI component library for the project.
   - Use the existing utility functions from `@/Utils/Shadcn/utils`
   - Maintain the established design system with consistent styling
   - Follow the component structure in `@/Components/ui`
   - Transition existing components from Naive UI to Shadcn Vue incrementally.

## Rich Content System

1. Follow the established content type pattern when creating new rich content types.
2. Use the RichContentParser and existing type registration pattern for new content components.
3. Ensure each content type has both editor and display components.
4. Maintain internationalization support in all rich content types.

## Internationalization Guidelines

1. All user-facing content must support both Lithuanian (lt) and English (en) languages.
2. Use the `trans as $t` and `transChoice as $tChoice` functions from laravel-vue-i18n for translations.
3. For form inputs with localized content, use components like `MultiLocaleInput` or `MultiLocaleTiptapFormItem`.
4. Models with translatable attributes use the `HasTranslations` trait; check the `$translatable` property.
5. Some content (like Pages and News) has separate records for each language with relationships between them.
6. Avoid direct conditionals (`if ($page.props.app.locale === 'lt')`) in favor of translation functions.

## Microsoft Integration

1. Follow existing patterns for Microsoft Graph API authentication and calls.
2. Ensure proper error handling for API responses.
3. Consider caching strategies to minimize API requests.

## Performance Considerations

1. Optimize database queries to avoid N+1 problems.
2. Use eager loading for relationships appropriately.
3. Implement caching for frequently accessed data.
4. Minimize JavaScript bundle size through code splitting with defineAsyncComponent.
5. Lazy load components and assets, especially for the public-facing pages.
6. Use image optimization and appropriate loading strategies (lazy loading).

## Accessibility and SEO

1. Ensure proper semantic HTML structure.
2. Include appropriate alt text for images.
3. Implement proper meta tags for SEO.
4. Ensure keyboard navigability for all interactive elements.
5. Follow WCAG 2.1 AA standards for all user interfaces.

## Sustainability Practices

1. **Component Reusability**: Create and use shared components rather than duplicating code.
2. **Progressive Enhancement**: Design features to work with minimal JavaScript when possible.
3. **Efficient State Management**: Use Vue's Composition API reactivity system effectively.
4. **Modular Architecture**: Follow established patterns for new features to maintain consistency.
5. **Documentation**: Document complex components or business logic for future maintainers.

## Growth Considerations

1. The platform continuously evolves to support new representation needs and organizational workflows.
2. Design features with flexibility to accommodate changing university structures and representation processes.
3. Consider data export/import capabilities for interoperability with other university systems.
4. Plan for scalability as the number of representatives and institutional memory grows over time.

## Remember

This is a student-run project with limited developer resources. Prioritize maintainability and clarity over complex solutions. The codebase should remain approachable for future student contributors.

When suggesting new packages or dependencies, consider:
1. Long-term maintenance status
2. Impact on application size and performance
3. Learning curve for future contributors