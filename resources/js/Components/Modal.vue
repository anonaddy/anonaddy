<!-- This example requires Tailwind CSS v2.0+ -->
<template>
  <TransitionRoot as="template" :show="open">
    <Dialog as="div" class="relative z-10" @close="setIsOpen">
      <TransitionChild
        as="template"
        enter="ease-out duration-300"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="ease-in duration-200"
        leave-from="opacity-100"
        leave-to="opacity-0"
      >
        <div class="fixed inset-0 bg-grey-500 bg-opacity-75 transition-opacity" />
      </TransitionChild>

      <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex h-auto items-center justify-center min-h-full p-4 text-center sm:p-0">
          <TransitionChild
            as="template"
            enter="ease-out duration-300"
            enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            enter-to="opacity-100 translate-y-0 sm:scale-100"
            leave="ease-in duration-200"
            leave-from="opacity-100 translate-y-0 sm:scale-100"
            leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
          >
            <DialogPanel
              class="relative bg-white dark:bg-grey-900 rounded-lg px-4 pt-5 pb-4 text-left shadow-xl transform transition-all sm:my-8 w-full sm:p-6"
              :class="maxWidth ? maxWidth : 'sm:max-w-lg'"
            >
              <div class="mt-3 text-center sm:mt-0 sm:text-left">
                <DialogTitle
                  as="h2"
                  class="text-2xl leading-tight font-medium text-grey-900 dark:text-grey-100 border-b-2 border-grey-100 pb-4"
                >
                  <slot name="title"></slot>
                </DialogTitle>
                <div class="mt-2">
                  <slot name="content"></slot>
                </div>
              </div>
            </DialogPanel>
          </TransitionChild>
        </div>
      </div>
    </Dialog>
  </TransitionRoot>
</template>

<script setup>
import { Dialog, DialogPanel, DialogTitle, TransitionChild, TransitionRoot } from '@headlessui/vue'

defineProps(['open', 'maxWidth'])
const emit = defineEmits(['update:modelValue'])

function setIsOpen(value) {
  emit('update:modelValue', value)
}
</script>
