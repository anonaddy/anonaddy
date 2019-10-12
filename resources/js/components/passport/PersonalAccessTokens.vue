<template>
  <div>
    <h3 class="font-bold text-xl">
      Information
    </h3>

    <div class="mt-4 w-24 border-b-2 border-grey-200"></div>

    <p class="my-6">
      Your API access tokens can be used with the
      <a
        href="https://github.com/anonaddy/browser-extension"
        target="_blank"
        rel="nofollow noopener noreferrer"
        class="text-indigo-700"
        >open-source</a
      >
      AnonAddy browser extension on
      <a
        href="https://addons.mozilla.org/en-GB/firefox/addon/anonaddy/"
        target="_blank"
        rel="nofollow noopener noreferrer"
        class="text-indigo-700"
        >Firefox</a
      >
      or
      <a
        href="https://chrome.google.com/webstore/detail/anonaddy/iadbdpnoknmbdeolbapdackdcogdmjpe"
        target="_blank"
        rel="nofollow noopener noreferrer"
        class="text-indigo-700"
        >Chrome / Brave</a
      >
      to generate UUID aliases. Simply paste the token generated below into the browser extension to
      get started. Your API Access tokens are secret and should be treated like your password.
    </p>

    <button
      @click="openCreateTokenModal"
      class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
    >
      Generate New Token
    </button>

    <div class="mt-6">
      <h3 class="font-bold text-xl">
        Personal Access Tokens
      </h3>

      <div class="my-4 w-24 border-b-2 border-grey-200"></div>

      <p class="my-6">
        Tokens you have generated that can be used to access the AnonAddy API. To revoke an access
        token simply click the delete button next to it.
      </p>

      <div>
        <p class="mb-0" v-if="tokens.length === 0">
          You have not created any personal access tokens.
        </p>

        <div class="table w-full text-sm md:text-base" v-if="tokens.length > 0">
          <div class="table-row">
            <div class="table-cell p-1 md:p-4 font-semibold">Name</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Created</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Expires</div>
            <div class="table-cell p-1 md:p-4"></div>
          </div>
          <div
            v-for="token in tokens"
            :key="token.id"
            class="table-row even:bg-grey-50 odd:bg-white"
          >
            <div class="table-cell p-1 md:p-4">{{ token.name }}</div>
            <div class="table-cell p-1 md:p-4">{{ token.created_at | timeAgo }}</div>
            <div class="table-cell p-1 md:p-4">{{ token.expires_at | timeAgo }}</div>
            <div class="table-cell p-1 md:p-4 text-right">
              <a
                class="text-red-500 font-bold cursor-pointer focus:outline-none"
                @click="showRevokeModal(token)"
              >
                Delete
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal :open="createTokenModalOpen" @close="closeCreateTokenModal">
      <div v-if="!accessToken" class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Create New Token
        </h2>
        <p class="mt-4 text-grey-700">
          What's this token going to be used for? Give it a short name so that you remember later.
        </p>
        <div class="mt-6">
          <div v-if="form.errors.length > 0" class="mb-3 text-red-500">
            <ul>
              <li v-for="error in form.errors" :key="error">
                {{ error }}
              </li>
            </ul>
          </div>
          <label for="create-token-name" class="block text-grey-700 text-sm my-2">
            Name:
          </label>
          <input
            v-model="form.name"
            type="text"
            id="create-token-name"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-6"
            :class="form.errors.length > 0 ? 'border-red-500' : ''"
            placeholder="e.g. Browser extension"
            autofocus
          />
          <button
            @click="store"
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            :class="loading ? 'cursor-not-allowed' : ''"
            :disabled="loading"
          >
            Generate Token
            <loader v-if="loading" />
          </button>
          <button
            @click="closeCreateTokenModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </div>
      <div v-else class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Personal Access Token
        </h2>
        <p class="my-4 text-grey-700">
          This is your new personal access token. This is the only time the token will ever be
          displayed, so please make a note of it in a safe place (e.g. password manager)!
        </p>
        <textarea
          v-model="accessToken"
          class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 text-sm"
          rows="10"
          disabled
        >
        </textarea>
        <div class="mt-6">
          <button
            class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
            v-clipboard="() => accessToken"
            v-clipboard:success="clipboardSuccess"
            v-clipboard:error="clipboardError"
          >
            Copy To Clipboard
          </button>
          <button
            @click="closeCreateTokenModal"
            class="ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 border border-grey-100 rounded focus:outline-none"
          >
            Close
          </button>
        </div>
      </div>
    </Modal>

    <Modal :open="revokeTokenModalOpen" @close="closeRevokeTokenModal">
      <div class="max-w-lg w-full bg-white rounded-lg shadow-2xl px-6 py-6">
        <h2
          class="font-semibold text-grey-900 text-2xl leading-tight border-b-2 border-grey-100 pb-4"
        >
          Revoke API Access Token
        </h2>
        <p class="my-4 text-grey-700">
          Any browser extension, application or script using this API access token will no longer be
          able to access the AnonAddy API. This action cannot be undone.
        </p>
        <div class="mt-6">
          <button
            @click="revoke"
            class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded focus:outline-none"
            :class="revokeTokenLoading ? 'cursor-not-allowed' : ''"
            :disabled="revokeTokenLoading"
          >
            Revoke Token
            <loader v-if="revokeTokenLoading" />
          </button>
          <button
            @click="closeRevokeTokenModal"
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
import Modal from './../Modal.vue'

export default {
  components: {
    Modal,
  },
  data() {
    return {
      accessToken: null,
      createTokenModalOpen: false,
      revokeTokenModalOpen: false,
      tokens: [],
      tokenToRevoke: null,
      form: {
        name: '',
        errors: [],
      },
      loading: false,
      revokeTokenLoading: false,
    }
  },
  mounted() {
    this.getTokens()
  },

  methods: {
    getTokens() {
      axios.get('/oauth/personal-access-tokens').then(response => {
        this.tokens = response.data
      })
    },
    store() {
      this.loading = true
      this.accessToken = null
      this.form.errors = []

      axios
        .post('/oauth/personal-access-tokens', this.form)
        .then(response => {
          this.loading = false
          this.form.name = ''
          this.form.errors = []

          this.tokens.push(response.data.token)
          this.accessToken = response.data.accessToken
        })
        .catch(error => {
          this.loading = false
          if (typeof error.response.data === 'object') {
            this.form.errors = _.flatten(_.toArray(error.response.data.errors))
          } else {
            this.error()
          }
        })
    },
    showRevokeModal(token) {
      this.tokenToRevoke = token
      this.revokeTokenModalOpen = true
    },
    revoke() {
      this.revokeTokenLoading = true

      axios.delete(`/oauth/personal-access-tokens/${this.tokenToRevoke.id}`).then(response => {
        this.revokeTokenLoading = false
        this.revokeTokenModalOpen = false
        this.tokenToRevoke = null
        this.getTokens()
      })
    },
    openCreateTokenModal() {
      this.accessToken = null
      this.createTokenModalOpen = true
    },
    closeCreateTokenModal() {
      this.createTokenModalOpen = false
    },
    closeRevokeTokenModal() {
      this.revokeTokenModalOpen = false
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
