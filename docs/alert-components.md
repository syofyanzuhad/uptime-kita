# Alert Components

A comprehensive set of alert components for displaying various types of messages including validation errors, error messages, warnings, and other notifications.

## Components

### Alert
The main alert component with different variants and optional dismissible functionality.

### AlertTitle
Component for the alert title/heading.

### AlertDescription
Component for the alert description/content.

### ValidationAlert
Specialized component for handling Laravel validation errors.

## Usage

### Basic Alert

```vue
<script setup>
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'
</script>

<template>
  <Alert variant="info">
    <AlertTitle>Information</AlertTitle>
    <AlertDescription>
      This is an informational message.
    </AlertDescription>
  </Alert>
</template>
```

### Alert Variants

The Alert component supports the following variants:

- `default` - Default styling
- `destructive` - For errors and critical messages (red)
- `warning` - For warnings (yellow/orange)
- `success` - For success messages (green)
- `info` - For informational messages (blue)

### Dismissible Alert

```vue
<script setup>
import { ref } from 'vue'
import { Alert, AlertTitle, AlertDescription } from '@/components/ui/alert'

const showAlert = ref(true)

const dismissAlert = () => {
  showAlert.value = false
}
</script>

<template>
  <Alert 
    v-if="showAlert" 
    variant="warning" 
    dismissible 
    @dismiss="dismissAlert"
  >
    <AlertTitle>Warning</AlertTitle>
    <AlertDescription>
      This alert can be dismissed.
    </AlertDescription>
  </Alert>
</template>
```

### Validation Errors

For handling Laravel validation errors, use the `ValidationAlert` component:

```vue
<script setup>
import ValidationAlert from '@/components/ValidationAlert.vue'

// Example validation errors from Laravel
const errors = {
  email: ['The email field is required.', 'The email must be a valid email address.'],
  password: ['The password must be at least 8 characters.'],
  name: ['The name field is required.']
}
</script>

<template>
  <ValidationAlert :errors="errors" />
</template>
```

### Custom Styling

You can apply custom classes to any alert component:

```vue
<template>
  <Alert 
    variant="warning" 
    class="border-2 border-yellow-400 bg-yellow-50"
  >
    <AlertTitle class="text-yellow-800">Custom Styled</AlertTitle>
    <AlertDescription class="text-yellow-700">
      This alert has custom styling.
    </AlertDescription>
  </Alert>
</template>
```

## Props

### Alert Props

- `variant` - Alert variant: `default`, `destructive`, `warning`, `success`, `info`
- `dismissible` - Whether the alert can be dismissed (boolean)
- `onDismiss` - Callback function when alert is dismissed
- `class` - Additional CSS classes

### ValidationAlert Props

- `errors` - Object containing validation errors (field name -> array of error messages)
- `title` - Custom title for the validation alert
- `show` - Whether to show the alert (boolean)
- `class` - Additional CSS classes

## Examples

### Error Message
```vue
<Alert variant="destructive">
  <AlertTitle>Error</AlertTitle>
  <AlertDescription>
    Failed to save changes. Please try again.
  </AlertDescription>
</Alert>
```

### Success Message
```vue
<Alert variant="success">
  <AlertTitle>Success</AlertTitle>
  <AlertDescription>
    Your changes have been saved successfully.
  </AlertDescription>
</Alert>
```

### Warning Message
```vue
<Alert variant="warning">
  <AlertTitle>Warning</AlertTitle>
  <AlertDescription>
    Your session will expire in 5 minutes.
  </AlertDescription>
</Alert>
```

### Info Message
```vue
<Alert variant="info">
  <AlertTitle>Information</AlertTitle>
  <AlertDescription>
    New features are available. Check them out!
  </AlertDescription>
</Alert>
```

### Simple Alert (without title)
```vue
<Alert variant="default">
  <AlertDescription>
    This is a simple alert without a title.
  </AlertDescription>
</Alert>
```

## Integration with Laravel

The alert components work seamlessly with Laravel's validation system. You can pass validation errors from your Laravel backend:

```vue
<script setup>
import { usePage } from '@inertiajs/vue3'
import ValidationAlert from '@/components/ValidationAlert.vue'

const page = usePage()
const errors = computed(() => (page.props as any).errors || {})
</script>

<template>
  <ValidationAlert :errors="errors" />
</template>
```

## Accessibility

All alert components include proper ARIA attributes:
- `role="alert"` for screen readers
- Proper focus management for dismissible alerts
- Semantic HTML structure with titles and descriptions

## Icons

The alert components automatically include appropriate icons based on the variant:
- `destructive` - Alert circle icon
- `warning` - Alert triangle icon  
- `success` - Check circle icon
- `info` - Info icon
- `default` - Info icon 
