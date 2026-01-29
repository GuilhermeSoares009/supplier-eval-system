<script setup>
import { cn } from '@/lib/utils'

const props = defineProps({
  class: { type: String, default: null },
})
</script>

<template>
  <div :class="cn('p-6 pt-0', props.class)">
    <slot />
  </div>
</template>
