<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes["class"]
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number): void
}>()

const modelValue = useVModel(props, "modelValue", emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
      'file:text-foreground placeholder:text-muted-foreground selection:bg-primary selection:text-primary-foreground border-input h-10 w-full min-w-0 border bg-transparent px-3 py-1 font-mono text-sm outline-none file:inline-flex file:h-7 file:border-0 file:bg-transparent file:font-mono file:text-xs file:font-semibold file:uppercase disabled:pointer-events-none disabled:cursor-not-allowed disabled:opacity-50',
      'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring',
      'aria-invalid:outline-destructive aria-invalid:border-destructive',
      props.class,
    )"
  >
</template>
