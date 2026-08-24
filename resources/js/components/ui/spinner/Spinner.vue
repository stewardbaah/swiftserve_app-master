<script setup lang="ts">
import { RiLoader4Line } from '@remixicon/vue';

import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()
</script>

<template>
  <RiLoader4Line role="status" aria-label="Loading" :class="cn('size-4 animate-spin', props.class)" />
</template>
