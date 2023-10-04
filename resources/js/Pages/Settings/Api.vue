<template>
  <SettingsLayout>
    <div class="divide-y divide-grey-200">
      <div class="pt-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900">Manage your API Access Keys</h3>
          <p class="text-base text-grey-700">
            Your API access keys can be used with the
            <a
              href="https://github.com/anonaddy/browser-extension"
              target="_blank"
              rel="nofollow noopener noreferrer"
              class="text-indigo-700"
              >open-source</a
            >
            browser extension on
            <a
              href="https://addons.mozilla.org/en-GB/firefox/addon/addy_io/"
              target="_blank"
              rel="nofollow noopener noreferrer"
              class="text-indigo-700"
              >Firefox</a
            >
            or
            <a
              href="https://chrome.google.com/webstore/detail/addyio-anonymous-email-fo/iadbdpnoknmbdeolbapdackdcogdmjpe"
              target="_blank"
              rel="nofollow noopener noreferrer"
              class="text-indigo-700"
              >Chrome / Brave</a
            >
            to create new aliases. They can also be used with the mobile apps. Simply paste a key
            you've created into the browser extension or mobile apps to get started. Your API access
            keys <b>are secret and should be treated like your password</b>. For more information
            please see the <a href="/docs" class="text-indigo-700">API documentation</a>.
          </p>
        </div>
        <div class="mt-4">
          <button
            @click="openCreateTokenModal"
            class="bg-cyan-400 w-full hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
          >
            Create New API Key
          </button>

          <div class="mt-6">
            <h3 class="text-lg font-medium leading-6 text-grey-900">Personal Access Keys</h3>

            <div class="my-4 w-24 border-b-2 border-grey-200"></div>

            <p class="my-6 text-base text-grey-700">
              Keys you have created that can be used to access the API. To revoke an access key
              simply click the delete button next to it.
            </p>

            <div>
              <p class="mb-0 text-base text-grey-700" v-if="tokens.length === 0">
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
        </div>
      </div>
    </div>

    <Modal :open="createTokenModalOpen" @close="closeCreateTokenModal">
      <template v-if="!accessToken" v-slot:title> Create New API Key </template>
      <template v-else v-slot:title> Personal Access Key </template>
      <template v-slot:content>
        <div v-show="!accessToken">
          <p class="mt-4 text-grey-700">
            What's this API key going to be used for? Give it a short name so that you remember
            later. You can also select an expiry date for the key if you wish.
          </p>
          <div class="mt-6">
            <div v-if="isObject(form.errors)" class="mb-3 text-red-500">
              <ul>
                <li v-for="error in form.errors" :key="error[0]">
                  {{ error[0] }}
                </li>
              </ul>
            </div>
            <label
              for="create-token-name"
              class="block text-sm my-2 font-medium leading-6 text-grey-600"
              >Name</label
            >
            <input
              v-model="form.name"
              type="text"
              id="create-token-name"
              class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
              :class="form.errors.name ? 'ring-red-500' : ''"
              placeholder="e.g. Firefox extension"
              required
              autofocus
            />
            <label
              for="create-token-expiration"
              class="block font-medium leading-6 text-grey-600 text-sm my-2"
            >
              Expiration
            </label>
            <div class="block relative">
              <select
                v-model="form.expiration"
                id="create-token-expiration"
                class="relative block w-full rounded border-0 bg-transparent py-2 text-grey-900 ring-1 ring-inset focus:z-10 focus:ring-2 focus:ring-inset sm:text-base sm:leading-6"
                :class="form.errors.expiration ? 'ring-red-500' : ''"
              >
                <option value="day">1 day</option>
                <option value="week">1 week</option>
                <option value="month">1 month</option>
                <option value="year">1 year</option>
                <option :value="null">No expiration</option>
              </select>
            </div>
            <label
              for="create-token-name"
              class="block text-sm my-2 font-medium leading-6 text-grey-600"
              >Confirm Password</label
            >
            <input
              v-model="form.password"
              type="password"
              id="create-token-password"
              class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 mb-6"
              :class="form.errors.password ? 'ring-red-500' : ''"
              placeholder="********"
              required
            />
            <button
              @click="store"
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
              :class="loading ? 'cursor-not-allowed' : ''"
              :disabled="loading"
            >
              Create API Key
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
            This is your new personal access key. This is the only time the key will ever be
            displayed, so please make a note of it in a safe place (e.g. password manager)!
          </p>
          <textarea
            v-model="accessToken"
            @click="selectTokenTextArea"
            id="token-text-area"
            class="w-full appearance-none bg-grey-100 border border-transparent text-grey-700 focus:outline-none rounded p-3 text-md break-all"
            rows="2"
            readonly
          >
          </textarea>
          <div class="text-center">
            <img :src="qrCode" class="inline-block" alt="QR Code" />
            <p class="text-left text-sm text-grey-700">
              You can scan this QR code to automatically login to the addy.io mobile app by Stjin.
            </p>
          </div>
          <div class="mt-6">
            <button
              class="bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus:outline-none"
              @click="clipboard(accessToken)"
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
      <template v-slot:title> Revoke API Access Key </template>
      <template v-slot:content>
        <p class="my-4 text-grey-700">
          Any browser extension, application or script using this API access key will no longer be
          able to access the API. This action cannot be undone.
        </p>
        <div class="mt-6">
          <button
            @click="revoke"
            class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded focus:outline-none"
            :class="revokeTokenLoading ? 'cursor-not-allowed' : ''"
            :disabled="revokeTokenLoading"
          >
            Revoke API Key
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
  </SettingsLayout>
</template>

<script setup>
import { ref } from 'vue'
import SettingsLayout from './../../Layouts/SettingsLayout.vue'
import { notify } from '@kyvg/vue3-notification'
import Modal from '../../Components/Modal.vue'

const props = defineProps({
  initialTokens: {
    type: Object,
    required: true,
  },
})

const tokens = ref(props.initialTokens.data)

const accessToken = ref(null)
const qrCode = ref(null)
const createTokenModalOpen = ref(false)
const revokeTokenModalOpen = ref(false)
const tokenToRevoke = ref(null)
const loading = ref(false)
const revokeTokenLoading = ref(false)
const form = ref({
  name: '',
  expiration: null,
  password: '',
  errors: {},
})

const store = () => {
  form.value.errors = {}

  if (!form.value.name.length) {
    return (form.value.errors.name = ['The name field is required.'])
  }

  if (!['day', 'week', 'month', 'year', null].includes(form.value.expiration)) {
    return (form.value.errors.expiration = ['Invalid expiration given.'])
  }

  if (!form.value.password.length) {
    return (form.value.errors.password = ['The password field is required.'])
  }

  loading.value = true
  accessToken.value = null
  qrCode.value = null

  axios
    .post('/settings/personal-access-tokens', form.value)
    .then(response => {
      loading.value = false
      form.value.name = ''
      form.value.password = ''
      form.value.expiration = null
      form.value.errors = {}

      tokens.value.push(response.data.token)
      accessToken.value = response.data.accessToken
      qrCode.value = response.data.qrCode
    })
    .catch(error => {
      loading.value = false
      if (isObject(error.response.data)) {
        form.value.errors = error.response.data.errors
      } else {
        errorMessage()
      }
    })
}

const showRevokeModal = token => {
  tokenToRevoke.value = token
  revokeTokenModalOpen.value = true
}

const revoke = () => {
  revokeTokenLoading.value = true

  axios
    .delete(`/settings/personal-access-tokens/${tokenToRevoke.value.id}`)
    .then(response => {
      revokeTokenLoading.value = false
      revokeTokenModalOpen.value = false
      tokens.value = _.reject(tokens.value, token => token.id === tokenToRevoke.value.id)
      tokenToRevoke.value = null
    })
    .catch(error => {
      revokeTokenLoading.value = false
      revokeTokenModalOpen.value = false
      errorMessage()
    })
}

const openCreateTokenModal = () => {
  accessToken.value = null
  qrCode.value = null
  createTokenModalOpen.value = true
}

const closeCreateTokenModal = () => {
  createTokenModalOpen.value = false
}

const closeRevokeTokenModal = () => {
  revokeTokenModalOpen.value = false
}

const selectTokenTextArea = () => {
  let textArea = document.getElementById('token-text-area')
  textArea.focus()
  textArea.select()
}

const isObject = val => {
  return _.isObject(val) && !_.isEmpty(val)
}

const clipboard = (str, success, error) => {
  // Needed as v-clipboard doesn't work inside modals!
  navigator.clipboard.writeText(str).then(
    () => {
      successMessage('Copied to clipboard')
    },
    () => {
      errorMessage('Could not copy to clipboard')
    },
  )
}

const successMessage = (text = '') => {
  notify({
    title: 'Success',
    text: text,
    type: 'success',
  })
}

const errorMessage = (text = 'An error has occurred, please try again later') => {
  notify({
    title: 'Error',
    text: text,
    type: 'error',
  })
}
</script>
