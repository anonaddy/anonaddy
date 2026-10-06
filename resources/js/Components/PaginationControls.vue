<template>
  <div
    v-if="pagination.data.length"
    class="mt-4 rounded-lg shadow flex items-center justify-between bg-white px-4 py-3 sm:px-6 overflow-x-auto horizontal-scroll dark:bg-grey-900"
  >
    <div class="flex flex-1 justify-between items-center md:hidden gap-x-3">
      <Link
        v-if="pagination.prev_page_url"
        :href="pagination.prev_page_url"
        as="button"
        class="relative inline-flex items-center rounded-md border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 hover:bg-grey-50 dark:bg-grey-950 dark:hover:bg-grey-900 dark:text-grey-200"
      >
        Previous
      </Link>
      <span
        v-else
        class="relative inline-flex h-min items-center rounded-md border border-grey-300 px-4 py-2 text-sm font-medium text-grey-700 bg-grey-100 dark:bg-grey-800 dark:text-grey-200"
        >Previous</span
      >
      <div class="flex flex-col items-center justify-center gap-y-2">
        <p class="text-sm text-grey-700 text-center dark:text-grey-200">
          Showing
          {{ ' ' }}
          <span class="font-medium">{{ pagination.from.toLocaleString() }}</span>
          {{ ' ' }}
          to
          {{ ' ' }}
          <span class="font-medium">{{ pagination.to.toLocaleString() }}</span>
          {{ ' ' }}
          of
          {{ ' ' }}
          <span class="font-medium">{{ pagination.total.toLocaleString() }}</span>
          {{ ' ' }}
          {{ pagination.total === 1 ? 'result' : 'results' }}
        </p>
        <select
          :value="pageSize"
          @change="handlePageSizeChange"
          :disabled="pageSizeLoading"
          class="relative rounded border-0 bg-transparent py-1 pr-8 text-grey-900 text-sm ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset ring-grey-300 focus:ring-indigo-600 disabled:cursor-not-allowed dark:text-grey-200"
        >
          <option
            class="dark:bg-grey-900"
            v-for="size in pageSizeOptions"
            :key="size"
            :value="size"
          >
            {{ size }}
          </option>
        </select>
      </div>
      <Link
        v-if="pagination.next_page_url"
        :href="pagination.next_page_url"
        as="button"
        class="relative inline-flex h-min items-center rounded-md border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 dark:bg-grey-950 dark:hover:bg-grey-900 dark:text-grey-200 hover:bg-grey-50"
      >
        Next
      </Link>
      <span
        v-else
        class="relative inline-flex items-center rounded-md border border-grey-300 px-4 py-2 text-sm font-medium text-grey-700 dark:text-grey-200 bg-grey-100 dark:bg-grey-800"
        >Next</span
      >
    </div>
    <div class="hidden md:flex md:flex-1 md:items-center md:justify-between md:gap-x-2">
      <div class="flex items-center gap-x-2">
        <p class="text-sm text-grey-700 dark:text-grey-200">
          Showing
          {{ ' ' }}
          <span class="font-medium">{{ pagination.from.toLocaleString() }}</span>
          {{ ' ' }}
          to
          {{ ' ' }}
          <span class="font-medium">{{ pagination.to.toLocaleString() }}</span>
          {{ ' ' }}
          of
          {{ ' ' }}
          <span class="font-medium">{{ pagination.total.toLocaleString() }}</span>
          {{ ' ' }}
          {{ pagination.total === 1 ? 'result' : 'results' }}
        </p>
        <select
          :value="pageSize"
          @change="handlePageSizeChange"
          :disabled="pageSizeLoading"
          class="relative rounded border-0 bg-transparent py-1 pr-8 text-grey-900 text-sm ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset ring-grey-300 focus:ring-indigo-600 disabled:cursor-not-allowed dark:text-grey-200"
        >
          <option
            class="dark:bg-grey-900"
            v-for="size in pageSizeOptions"
            :key="size"
            :value="size"
          >
            {{ size }}
          </option>
        </select>
      </div>

      <nav class="isolate inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
        <Link
          v-if="pagination.prev_page_url"
          :href="pagination.prev_page_url"
          class="relative inline-flex items-center rounded-l-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20 dark:bg-grey-900 dark:hover:bg-grey-950 dark:border-grey-500 dark:text-grey-200"
        >
          <span class="sr-only">Previous</span>
          <ChevronLeftIcon class="pointer-events-none h-5 w-5" aria-hidden="true" />
        </Link>
        <span
          v-else
          class="disabled cursor-not-allowed relative inline-flex items-center rounded-l-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 focus:z-20 dark:bg-grey-800 dark:border-grey-500 dark:text-grey-200"
        >
          <span class="sr-only">Previous</span>
          <ChevronLeftIcon class="pointer-events-none h-5 w-5" aria-hidden="true" />
        </span>

        <template v-for="(link, index) in pageLinks" :key="`${index}-${link.label}`">
          <Link
            v-if="link.url"
            :href="link.url"
            :aria-current="link.active ? 'page' : undefined"
            class="relative inline-flex items-center border px-4 py-2 text-sm font-medium focus:z-20"
            :class="
              link.active
                ? 'z-10 border-indigo-500 bg-indigo-50 text-indigo-600 dark:bg-grey-950 dark:text-grey-100 dark:border-grey-500'
                : 'border-grey-300 bg-white text-grey-500 hover:bg-grey-50 dark:bg-grey-900 dark:hover:bg-grey-950 dark:text-grey-200 dark:border-grey-500'
            "
          >
            <span class="pointer-events-none">{{ link.label }}</span>
          </Link>
          <span
            v-else
            class="relative inline-flex items-center border border-grey-300 bg-white px-4 py-2 text-sm font-medium text-grey-700 dark:bg-grey-900 dark:text-grey-200 dark:border-grey-500"
            >...</span
          >
        </template>

        <Link
          v-if="pagination.next_page_url"
          :href="pagination.next_page_url"
          class="relative inline-flex items-center rounded-r-md border border-grey-300 bg-white p-2 text-sm font-medium text-grey-500 hover:bg-grey-50 focus:z-20 dark:bg-grey-900 dark:hover:bg-grey-950 dark:text-grey-200 dark:border-grey-500"
        >
          <span class="sr-only">Next</span>
          <ChevronRightIcon class="pointer-events-none h-5 w-5" aria-hidden="true" />
        </Link>
        <span
          v-else
          class="disabled cursor-not-allowed relative inline-flex items-center rounded-r-md border border-grey-300 bg-white px-2 py-2 text-sm font-medium text-grey-500 focus:z-20 dark:bg-grey-800 dark:text-grey-200 dark:border-grey-500"
        >
          <span class="sr-only">Next</span>
          <ChevronRightIcon class="pointer-events-none h-5 w-5" aria-hidden="true" />
        </span>
      </nav>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Link } from '@inertiajs/vue3'
import { ChevronLeftIcon, ChevronRightIcon } from '@heroicons/vue/24/outline'

const props = defineProps({
  pagination: {
    type: Object,
    required: true,
  },
  pageSize: {
    type: Number,
    required: true,
  },
  pageSizeOptions: {
    type: Array,
    required: true,
  },
  pageSizeLoading: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(['update:pageSize', 'page-size-change'])

const pageLinks = computed(() => props.pagination?.links?.slice(1, -1) ?? [])

const handlePageSizeChange = event => {
  const nextPageSize = Number(event.target.value)
  emit('update:pageSize', nextPageSize)
  emit('page-size-change', nextPageSize)
}
</script>
