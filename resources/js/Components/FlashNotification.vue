<template>
  <div v-show="show" class="fixed inset-x-0 bottom-0 pb-2 sm:pb-5">
    <div class="mx-auto max-w-7xl px-2 sm:px-6 lg:px-8">
      <div class="rounded-lg bg-green-100 p-2 shadow-lg sm:p-3">
        <div class="flex flex-wrap items-center justify-between">
          <div class="flex w-0 flex-1 items-center">
            <span class="flex rounded-lg bg-green-800 p-2">
              <slot name="icon"></slot>
            </span>
            <p class="ml-3 truncate font-medium text-green-800">
              <span><slot name="message"></slot></span>
            </p>
          </div>
          <div class="order-2 flex-shrink-0 sm:order-3 sm:ml-2">
            <button
              type="button"
              @click="show = false"
              class="-mr-1 flex rounded-md p-2 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-white"
            >
              <span class="sr-only">Dismiss</span>
              <XMarkIcon class="h-6 w-6 text-green-800" aria-hidden="true" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { XMarkIcon } from '@heroicons/vue/24/outline'

const show = ref(true)

watch(
  () => usePage().props.flash,
  function () {
    show.value = true
  },
  { deep: true },
)
</script>
