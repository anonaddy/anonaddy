<template>
  <div class="relative flex justify-end items-center" @keydown.escape="isOpen = false">
    <button
      ref="openOptions"
      @click="isOpen = !isOpen"
      :aria-expanded="isOpen"
      id="project-options-menu-0"
      aria-has-popup="true"
      type="button"
      class="w-8 h-8 bg-white inline-flex items-center justify-center text-grey-400 rounded-full hover:text-grey-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500"
    >
      <span class="sr-only">Open options</span>

      <icon
        name="more"
        class="block w-6 h-6 text-grey-300 fill-current cursor-pointer outline-none"
        aria-hidden="true"
      />
    </button>

    <transition
      enter-active-class="transition ease-out duration-100"
      enter-class="opacity-0 scale-95"
      enter-to-class="opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-class="opacity-100 scale-100"
      leave-to-class="opacity-0 scale-95"
    >
      <div
        v-show="isOpen"
        class="mx-3 origin-top-right absolute right-7 top-0 w-48 mt-1 rounded-md shadow-lg z-10 bg-white ring-1 ring-black ring-opacity-5 divide-y divide-grey-200"
        role="menu"
        aria-orientation="vertical"
        aria-labelledby="project-options-menu-0"
      >
        <slot></slot>
      </div>
    </transition>
  </div>
</template>

<script>
export default {
  data() {
    return {
      isOpen: false,
    }
  },
  created() {
    window.addEventListener('click', this.close)
  },

  beforeDestroy() {
    window.removeEventListener('click', this.close)
  },

  methods: {
    close(e) {
      if (!this.$refs.openOptions.contains(e.target)) {
        this.isOpen = false
      }
    },
  },
}
</script>
