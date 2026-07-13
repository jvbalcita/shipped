<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
  defaultValue?: string | number
  modelValue?: string | number
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
  <textarea
    v-model="modelValue"
    data-slot="textarea"
    :class="cn('border-input placeholder:text-muted-foreground flex field-sizing-content min-h-28 w-full border bg-transparent px-3 py-2 font-mono text-sm outline-none focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-ring aria-invalid:outline-destructive aria-invalid:border-destructive disabled:cursor-not-allowed disabled:opacity-50', props.class)"
  />
</template>
