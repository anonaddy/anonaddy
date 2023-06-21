<template>
  <div>
    <h3 class="font-bold text-xl">Information</h3>

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
      browser extension on
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
      to create new aliases. They can also be used with the mobile apps. Simply paste a token you've
      created into the browser extension or mobile apps to get started. Your API access tokens are
      secret and should be treated like your password. For more information please see the
      <a
        href="https://app.anonaddy.com/docs"
        target="_blank"
        rel="nofollow noopener noreferrer"
        class="text-indigo-700"
        >API documentation</a
      >.
    </p>

    <button
      @click="openCreateTokenModal"
      class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
    >
      Create New Token
    </button>

    <div class="mt-6">
      <h3 class="font-bold text-xl">Personal Access Tokens</h3>

      <div class="my-4 w-24 border-b-2 border-grey-200"></div>

      <p class="my-6">
        Tokens you have created that can be used to access the API. To revoke an access token simply
        click the delete button next to it.
      </p>

      <div>
        <p class="mb-0" v-if="tokens.length === 0">
          You have not created any personal access tokens.
        </p>

        <div class="table w-full text-sm md:text-base" v-if="tokens.length > 0">
          <div class="table-row">
            <div class="table-cell p-1 md:p-4 font-semibold">Name</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Created</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Last Used</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Expires At</div>
            <div class="table-cell p-1 md:p-4"></div>
          </div>
          <div
            v-for="token in tokens"
            :key="token.id"
            class="table-row even:bg-grey-50 odd:bg-white"
          >
            <div class="table-cell p-1 md:p-4">{{ token.name }}</div>
            <div class="table-cell p-1 md:p-4">{{ $filters.timeAgo(token.created_at) }}</div>
            <div v-if="token.last_used_at" class="table-cell p-1 md:p-4">
              {{ $filters.timeAgo(token.last_used_at) }}
            </div>
            <div v-else class="table-cell p-1 md:p-4">Not used yet</div>
            <div v-if="token.expires_at" class="table-cell p-1 md:p-4">
              {{ $filters.formatDate(token.expires_at) }}
            </div>
            <div v-else class="table-cell p-1 md:p-4">Does not expire</div>
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
      <template v-if="!accessToken" v-slot:title> Create New Token </template>
      <template v-else v-slot:title> Personal Access Token </template>
      <template v-slot:content>
        <div v-show="!accessToken">
          <p class="mt-4 text-grey-700">
            What's this token going to be used for? Give it a short name so that you remember later.
            You can also select an expiry date for the token if you wish.
          </p>
          <div class="mt-6">
            <div v-if="isObject(form.errors)" class="mb-3 text-red-500">
              <ul>
                <li v-for="error in form.errors" :key="error[0]">
                  {{ error[0] }}
                </li>
              </ul>
            </div>
            <label for="create-token-name" class="block text-grey-700 text-sm my-2"> Name: </label>
            <input
              v-model="form.name"
              type="text"
              id="create-token-name"
              class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 mb-4"
              :class="form.errors.name ? 'border-red-500' : ''"
              placeholder="e.g. Firefox extension"
              autofocus
            />
            <label for="create-token-name" class="block text-grey-700 text-sm my-2">
              Expiration:
            </label>
            <div class="block relative mb-6">
              <select
                v-model="form.expiration"
                class="block appearance-none w-full text-grey-700 bg-grey-100 p-3 pr-8 rounded shadow focus:ring"
                :class="form.errors.expiration ? 'border border-red-500' : ''"
              >
                <option value="day">1 day</option>
                <option value="week">1 week</option>
                <option value="month">1 month</option>
                <option value="year">1 year</option>
                <option :value="null">No expiration</option>
              </select>
              <div
                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700"
              >
                <svg
                  class="fill-current h-4 w-4"
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 20 20"
                >
                  <path
                    d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"
                  />
                </svg>
              </div>
            </div>
            <button
              @click="store"
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
              :class="loading ? 'cursor-not-allowed' : ''"
              :disabled="loading"
            >
              Create Token
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
        <div v-show="accessToken">
          <p class="my-4 text-grey-700">
            This is your new personal access token. This is the only time the token will ever be
            displayed, so please make a note of it in a safe place (e.g. password manager)!
          </p>
          <textarea
            v-model="accessToken"
            @click="selectTokenTextArea"
            id="token-text-area"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 text-md break-all"
            rows="1"
            readonly
          >
          </textarea>
          <div class="text-center">
            <img :src="qrCode" class="inline-block" alt="QR Code" />
            <p class="text-left text-sm text-grey-700">
              You can scan this QR code to automatically login to the AnonAddy for Android mobile
              app.
            </p>
          </div>
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
      </template>
    </Modal>

    <Modal :open="revokeTokenModalOpen" @close="closeRevokeTokenModal">
      <template v-slot:title> Revoke API Access Token </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Any browser extension, application or script using this API access token will no longer be
          able to access the API. This action cannot be undone.
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
      </template>
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
      qrCode: null,
      createTokenModalOpen: false,
      revokeTokenModalOpen: false,
      tokens: [],
      tokenToRevoke: null,
      form: {
        name: '',
        expiration: null,
        errors: {},
      },
      loading: false,
      revokeTokenLoading: false,
    }
  },
  mounted() {
    this.getTokens()
  },
  watch: {
    'form.expiration'() {
      delete this.form.errors.expiration
    },
  },
  methods: {
    getTokens() {
      axios.get('/settings/personal-access-tokens').then(response => {
        this.tokens = response.data.data
      })
    },
    store() {
      this.loading = true
      this.accessToken = null
      this.qrCode = null
      this.form.errors = {}

      axios
        .post('/settings/personal-access-tokens', this.form)
        .then(response => {
          this.loading = false
          this.form.name = ''
          this.form.expiration = null
          this.form.errors = {}

          this.tokens.push(response.data.token)
          this.accessToken = response.data.accessToken
          this.qrCode = response.data.qrCode
        })
        .catch(error => {
          this.loading = false
          if (this.isObject(error.response.data)) {
            this.form.errors = error.response.data.errors
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

      axios
        .delete(`/settings/personal-access-tokens/${this.tokenToRevoke.id}`)
        .then(response => {
          this.revokeTokenLoading = false
          this.revokeTokenModalOpen = false
          this.tokenToRevoke = null
          this.getTokens()
        })
        .catch(error => {
          this.revokeTokenLoading = false
          this.revokeTokenModalOpen = false
          this.error()
        })
    },
    openCreateTokenModal() {
      this.accessToken = null
      this.qrCode = null
      this.createTokenModalOpen = true
    },
    closeCreateTokenModal() {
      this.createTokenModalOpen = false
    },
    closeRevokeTokenModal() {
      this.revokeTokenModalOpen = false
    },
    selectTokenTextArea() {
      let textArea = document.getElementById('token-text-area')
      textArea.focus()
      textArea.select()
    },
    isObject(val) {
      return _.isObject(val) && !_.isEmpty(val)
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
