<template>
  <div>
    <h3 class="font-bold text-xl">{{ token ? 'Rotate' : 'Generate' }} API Token</h3>

    <div class="my-4 w-24 border-b-2 border-grey-200"></div>

    <p v-if="token" class="my-6">
      To rotate your current API token simply click the button below.
    </p>
    <p v-else class="my-6">
      To enable the use of the API simply click the button below to generate an API token.
    </p>

    <button
      @click="rotate"
      class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
      :class="loading ? 'cursor-not-allowed' : ''"
      :disabled="loading"
    >
      {{ token ? 'Rotate' : 'Generate' }} Token
      <loader v-if="loading" />
    </button>

    <div class="mt-6" v-if="token">
      <h3 class="font-bold text-xl">
        Revoke API Token
      </h3>

      <div class="my-4 w-24 border-b-2 border-grey-200"></div>

      <p class="my-6">
        To revoke the current API token simply click the button below.
      </p>

      <button
        @click="revoke"
        class="text-red-500 font-bold focus:outline-none"
        :class="revokeLoading ? 'cursor-not-allowed' : ''"
        :disabled="revokeLoading"
      >
        Revoke Token
      </button>
    </div>

    <Modal :open="modalOpen" @close="closeModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          API Token
        </h2>
        <p class="my-4 text-grey-700">
          This is your new API token. This is the only time the token will ever be displayed, so
          please make a note of it in a safe place (e.g. password manager)! You may revoke or rotate
          the token at any time from your API settings.
        </p>
        <pre class="flex p-3 text-grey-900 bg-white border rounded">
            <code class="break-all whitespace-normal">{{ token }}</code>
        </pre>
        <div class="mt-6">
          <button
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            v-clipboard="() => token"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
          >
            Copy To Clipboard
          </button>
          <button
            @click="closeModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script>
import Modal from './../components/Modal.vue'

export default {
  props: {
    initialToken: {
      type: String,
      required: true,
    },
  },
  components: {
    Modal,
  },
  data() {
    return {
      loading: false,
      revokeLoading: false,
      modalOpen: false,
      token: this.initialToken,
    }
  },
  methods: {
    rotate() {
      this.loading = true

      axios
        .post('/settings/api-token', {
          headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
          this.modalOpen = true
          this.loading = false
          this.token = response.data.token
        })
        .catch(error => {
          this.loading = false
          this.error()
        })
    },
    revoke() {
      this.revokeLoading = true

      axios
        .delete('/settings/api-token', {
          headers: { 'Content-Type': 'application/json' },
        })
        .then(response => {
          this.revokeLoading = false
          this.token = ''
          this.success('Token Revoked Successfully!')
        })
        .catch(error => {
          this.revokeLoading = false
          this.error()
        })
    },
    closeModal() {
      this.modalOpen = false
    },
    clipboardSuccess() {
      this.success('Copied to clipboard')
    },
    clipboardError() {
      this.error('Could not copy to clipboard')
    },
    success(text = '') {
      this.$notify({
        title: 'Success',
        text: text,
        type: 'success',
      })
    },
    error(text = 'An error has occurred, please try again later') {
      this.$notify({
        title: 'Error',
        text: text,
        type: 'error',
      })
    },
  },
}
</script>
