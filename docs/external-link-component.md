# External Link Component

The `ExternalLink` component provides a standardized way to handle external links with proper SEO attributes, security features, and accessibility support.

## Features

### SEO Optimization
- **Automatic external link detection**: Automatically detects if a URL is external to your domain
- **Proper `rel` attributes**: Adds `rel="noopener noreferrer"` for security
- **Target attributes**: Opens external links in new tabs with `target="_blank"`
- **Accessibility**: Includes proper `aria-label` attributes for screen readers

### Security
- **`noopener`**: Prevents the opened page from accessing the `window.opener` property
- **`noreferrer`**: Prevents passing the referrer information to the new page

### Accessibility
- **Screen reader support**: Automatic generation of descriptive `aria-label` attributes
- **Icon accessibility**: External link icons are marked with `aria-hidden="true"`
- **Keyboard navigation**: Fully accessible via keyboard navigation

## Usage

### Basic Usage

```vue
<template>
  <ExternalLink href="https://example.com" label="Visit Example.com" />
</template>

<script setup>
import ExternalLink from '@/components/ExternalLink.vue'
</script>
```

### Advanced Usage

```vue
<template>
  <ExternalLink
    href="https://github.com"
    label="GitHub Repository"
    :show-icon="true"
    icon-size="md"
    class-name="text-blue-600 hover:text-blue-800 font-medium"
    icon-class-name="text-gray-500"
    aria-label="Visit our GitHub repository (opens in new tab)"
  />
</template>
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `href` | `string` | **required** | The URL to link to |
| `label` | `string` | `undefined` | Display text for the link |
| `showIcon` | `boolean` | `true` | Whether to show the external link icon |
| `iconSize` | `'sm' \| 'md' \| 'lg'` | `'sm'` | Size of the external link icon |
| `className` | `string` | See default styles | CSS classes for the link element |
| `iconClassName` | `string` | `'text-gray-400 dark:text-gray-500'` | CSS classes for the icon |
| `ariaLabel` | `string` | `undefined` | Custom aria-label (auto-generated if not provided) |
| `forceExternal` | `boolean` | `false` | Force external link behavior even for same-domain URLs |
| `referrerParam` | `string` | `undefined` | Custom referrer parameter (e.g., "uptimekita_docs") |
| `referrerSource` | `string` | `undefined` | Source for referrer parameter (e.g., "public_monitors") |
| `referrerCampaign` | `string` | `undefined` | Campaign for referrer parameter (e.g., "github_links") |
| `autoReferrer` | `boolean` | `true` | Automatically generate referrer parameter from current page |

## Default Styles

The component comes with sensible default styles that work well with Tailwind CSS:

```css
/* Default link styles */
text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 
underline decoration-blue-300 dark:decoration-blue-600 underline-offset-2 
transition-colors duration-200

/* Default icon styles */
text-gray-400 dark:text-gray-500
```

## Icon Sizes

| Size | Classes | Description |
|------|---------|-------------|
| `sm` | `w-3 h-3` | Small icon (12px) |
| `md` | `w-4 h-4` | Medium icon (16px) |
| `lg` | `w-5 h-5` | Large icon (20px) |

## Examples

### Different Use Cases

```vue
<!-- Basic external link -->
<ExternalLink href="https://example.com" label="Example Site" />

<!-- Link without icon -->
<ExternalLink 
  href="https://example.com" 
  label="Example Site" 
  :show-icon="false" 
/>

<!-- Custom styled link -->
<ExternalLink
  href="https://github.com"
  label="GitHub"
  class-name="text-green-600 hover:text-green-800 font-bold"
  icon-class-name="text-green-500"
/>

<!-- Large icon -->
<ExternalLink
  href="https://laravel.com"
  label="Laravel"
  icon-size="lg"
  class-name="text-orange-600 hover:text-orange-800"
/>

<!-- Forced external behavior -->
<ExternalLink
  href="/dashboard"
  label="Dashboard"
  :force-external="true"
  class-name="text-purple-600 hover:text-purple-800"
/>

<!-- With referrer tracking -->
<ExternalLink
  href="https://github.com"
  label="GitHub"
  referrer-source="docs"
  referrer-campaign="github_links"
  class-name="text-gray-800 hover:text-gray-900"
/>

<!-- Custom referrer parameter -->
<ExternalLink
  href="https://laravel.com"
  label="Laravel"
  referrer-param="uptimekita_docs_laravel"
  class-name="text-red-600 hover:text-red-800"
/>
```

### MonitorLink Component

For monitor-specific links, use the `MonitorLink` component:

```vue
<template>
  <MonitorLink
    :monitor="monitor"
    :show-favicon="true"
    :show-status="false"
    class-name="mb-2"
    link-class-name="text-lg font-semibold hover:text-blue-600"
  />
</template>

<script setup>
import MonitorLink from '@/components/MonitorLink.vue'
</script>
```

## Referrer Tracking

### Automatic Referrer Parameters

The component automatically adds referrer parameters to external links for better analytics and SEO tracking:

```javascript
// Automatic referrer generation
getCurrentPageReferrerParam() // "uptimekita_public_monitors"
generateReferrerParam('docs', 'github') // "uptimekita_docs_github"
```

### Referrer Parameter Examples

- **Automatic**: `https://example.com?ref=uptimekita_public_monitors`
- **Custom**: `https://github.com?ref=uptimekita_docs_github`
- **Source + Campaign**: `https://laravel.com?ref=uptimekita_docs_laravel`

### Benefits

- **Analytics**: Track which pages generate the most external link clicks
- **SEO**: Help external sites understand traffic sources
- **Marketing**: Measure campaign effectiveness
- **User Journey**: Understand user navigation patterns

## SEO Benefits

### Automatic External Link Detection

The component automatically detects external links and applies appropriate attributes:

```javascript
// Automatically detects external URLs
isExternalUrl('https://example.com') // true
isExternalUrl('/dashboard') // false
isExternalUrl('https://yourdomain.com/page') // false (same domain)
```

### Generated Attributes

For external links, the component automatically generates:

```html
<a 
  href="https://example.com"
  target="_blank"
  rel="noopener noreferrer"
  aria-label="Visit Example.com (opens in new tab)"
>
  Example.com
  <icon aria-hidden="true" />
</a>
```

## Utility Functions

The component uses utility functions from `@/lib/link-utils.ts`:

### `isExternalUrl(url: string): boolean`
Checks if a URL is external to the current domain.

### `getDomainFromUrl(url: string): string`
Extracts the domain from a URL.

### `getExternalLinkAttributes(url: string, customAriaLabel?: string)`
Generates SEO-friendly attributes for external links.

### `generateLinkMetaDescription(url: string, title?: string): string`
Generates meta descriptions for external links.

### `generateReferrerParam(source?: string, campaign?: string): string`
Generates referrer parameters for external links.

### `getCurrentPageReferrerParam(): string`
Gets the current page referrer parameter automatically.

## Best Practices

1. **Always provide meaningful labels**: Use descriptive text instead of generic "Click here"
2. **Customize aria-labels for context**: Provide specific context for screen readers
3. **Use appropriate icon sizes**: Choose icon size based on the link's importance
4. **Maintain consistent styling**: Use consistent colors and styles across your application
5. **Test accessibility**: Ensure links work properly with keyboard navigation and screen readers

## Browser Support

The component works in all modern browsers and includes fallbacks for older browsers:

- **Modern browsers**: Full support for all features
- **Older browsers**: Graceful degradation with basic link functionality
- **Screen readers**: Full accessibility support with proper ARIA attributes

## Performance

The component is lightweight and optimized:

- **No external dependencies**: Uses only Vue 3 and Lucide icons
- **Efficient rendering**: Minimal re-renders with proper computed properties
- **Small bundle size**: Contributes minimal size to your application bundle
