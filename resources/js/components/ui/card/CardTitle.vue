<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
  class: { type: String, default: null },
  as: { type: String, default: 'h3' },
})
</script>

<template>
  <component :as="as" :class="cn('text-2xl font-semibold leading-none tracking-tight', props.class)">
    <slot />
  </component>
</template>
