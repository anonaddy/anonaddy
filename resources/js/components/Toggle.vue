<template>
  <span
    class="relative outline-none cursor-pointer h-6 w-12 rounded-full"
    @click="toggle"
    role="checkbox"
    :aria-checked="value.toString()"
    tabindex="0"
    @keydown.space.prevent="toggle"
  >
    <span
      class="toggle-background inline-block rounded-full h-full w-full shadow-inner"
      :class="this.value ? 'bg-cyan-500' : 'bg-grey-200'"
    ></span>
    <span
      class="toggle-indicator absolute bg-white rounded-full shadow w-4 h-4"
      :style="indicatorStyles"
    ></span>
  </span>
</template>

<script>
export default {
  props: ['value'],
  methods: {
    toggle() {
      this.$emit('input', !this.value)
      this.value ? this.$emit('off') : this.$emit('on')
    },
  },
  computed: {
    indicatorStyles() {
      return { transform: this.value ? 'translateX(1.5rem)' : 'translateX(0)' }
    },
  },
}
</script>

<style>
.toggle-background {
  transition: background-color 0.2s ease;
}

.toggle-indicator {
  top: 0.25rem;
  left: 0.25rem;
  transition: transform 0.2s ease;
}
</style>
