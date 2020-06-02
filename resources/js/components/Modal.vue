<template>
  <portal to="modals">
    <div
      v-if="showModal"
      class="fixed inset-0 flex justify-center"
      :class="overflow ? 'overflow-auto' : 'items-center'"
    >
      <transition
        @before-leave="backdropLeaving = true"
        @after-leave="backdropLeaving = false"
        enter-active-class="transition-all transition-fast ease-out-quad"
        leave-active-class="transition-all transition-medium ease-in-quad"
        enter-class="opacity-0"
        enter-to-class="opacity-100"
        leave-class="opacity-100"
        leave-to-class="opacity-0"
        appear
      >
        <div v-if="showBackdrop">
          <div
            class="inset-0 bg-black opacity-25"
            :class="overflow ? 'fixed pointer-events-none' : 'absolute'"
            @click="close"
          ></div>
        </div>
      </transition>

      <transition
        @before-leave="cardLeaving = true"
        @after-leave="cardLeaving = false"
        enter-active-class="transition-all transition-fast ease-out-quad"
        leave-active-class="transition-all transition-medium ease-in-quad"
        enter-class="opacity-0 scale-70"
        enter-to-class="opacity-100 scale-100"
        leave-class="opacity-100 scale-100"
        leave-to-class="opacity-0 scale-70"
        appear
      >
        <div v-if="showContent" class="relative">
          <slot></slot>
        </div>
      </transition>
    </div>
  </portal>
</template>

<script>
export default {
  props: {
    open: {
      type: Boolean,
      required: true,
    },
    overflow: {
      type: Boolean,
      required: false,
      default: false,
    },
  },
  data() {
    return {
      showModal: false,
      showBackdrop: false,
      showContent: false,
      backdropLeaving: false,
      cardLeaving: false,
    }
  },
  created() {
    const onEscape = e => {
      if (this.open && e.keyCode === 27) {
        this.close()
      }
    }
    document.addEventListener('keydown', onEscape)
    this.$once('hook:destroyed', () => {
      document.removeEventListener('keydown', onEscape)
    })
  },
  watch: {
    open: {
      handler: function(newValue) {
        if (newValue) {
          this.show()
        } else {
          this.close()
        }
      },
      immediate: true,
    },
    leaving(newValue) {
      if (newValue === false) {
        this.showModal = false
        this.$emit('close')
      }
    },
  },
  computed: {
    leaving() {
      return this.backdropLeaving || this.cardLeaving
    },
  },
  methods: {
    show() {
      this.showModal = true
      this.showBackdrop = true
      this.showContent = true
    },
    close() {
      this.showBackdrop = false
      this.showContent = false
    },
  },
}
</script>

<style>
.origin-top-right {
  transform-origin: top right;
}
.transition-all {
  transition-property: all;
}
.transition-fastest {
  transition-duration: 50ms;
}
.transition-faster {
  transition-duration: 100ms;
}
.transition-fast {
  transition-duration: 150ms;
}
.transition-medium {
  transition-duration: 200ms;
}
.ease-out-quad {
  transition-timing-function: cubic-bezier(0.25, 0.46, 0.45, 0.94);
}
.ease-in-quad {
  transition-timing-function: cubic-bezier(0.55, 0.085, 0.68, 0.53);
}
.scale-70 {
  transform: scale(0.7);
}
.scale-100 {
  transform: scale(1);
}
</style>
