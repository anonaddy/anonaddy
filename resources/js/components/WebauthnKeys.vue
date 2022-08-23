<template>
  <div>
    <div class="mt-6">
      <h3 class="font-bold text-xl">Device Authentication (WebAuthn)</h3>

      <div class="my-4 w-24 border-b-2 border-grey-200"></div>

      <p class="my-6">
        Hardware security keys you have registered for 2nd factor authentication. To remove a key
        simply click the delete button next to it. Disabling all keys will turn off 2FA on your
        account.
      </p>

      <div>
        <p class="mb-0" v-if="keys.length === 0">You have not registered any hardware keys.</p>

        <div class="table w-full text-sm md:text-base" v-if="keys.length > 0">
          <div class="table-row">
            <div class="table-cell p-1 md:p-4 font-semibold">Name</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Created</div>
            <div class="table-cell p-1 md:p-4 font-semibold">Enabled</div>
            <div class="table-cell p-1 md:p-4 text-right">
              <a href="/webauthn/keys/create" class="text-indigo-700">Add New Key</a>
            </div>
          </div>
          <div v-for="key in keys" :key="key.id" class="table-row even:bg-grey-50 odd:bg-white">
            <div class="table-cell p-1 md:p-4">{{ key.name }}</div>
            <div class="table-cell p-1 md:p-4">{{ $filters.timeAgo(key.created_at) }}</div>
            <div class="table-cell p-1 md:p-4">
              <Toggle v-model="key.enabled" @on="enableKey(key.id)" @off="disableKey(key.id)" />
            </div>
            <div class="table-cell p-1 md:p-4 text-right">
              <a
                class="text-red-500 font-bold cursor-pointer focus:outline-none"
                @click="showRemoveModal(key)"
              >
                Delete
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <Modal :open="deleteKeyModalOpen" @close="closeDeleteKeyModal">
      <template v-slot:title> Remove Hardware Key </template>
      <template v-slot:content>
        <p v-if="keys.length === 1" class="my-4 text-grey-700">
          Once this key is removed, <b>Two-Factor Authentication</b> will be disabled on your
          account.
        </p>
        <p v-else class="my-4 text-grey-700">
          Once this key is removed, <b>Two-Factor Authentication</b> will still be enabled as you
          have other hardware keys associated with your account.
        </p>
        <div class="mt-6">
          <button
            @click="remove"
            class="bg-red-500 hover:bg-red-600 text-white font-bold py-3 px-4 rounded focus:outline-none"
            :class="removeKeyLoading ? 'cursor-not-allowed' : ''"
            :disabled="removeKeyLoading"
          >
            Remove
            <loader v-if="removeKeyLoading" />
          </button>
          <button
            @click="closeDeleteKeyModal"
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
import Modal from './Modal.vue'
import Toggle from './../components/Toggle.vue'

export default {
  components: {
    Modal,
    Toggle,
  },
  data() {
    return {
      deleteKeyModalOpen: false,
      keys: [],
      keyToRemove: null,
      loading: false,
      removeKeyLoading: false,
    }
  },
  mounted() {
    this.getWebauthnKeys()
  },
  methods: {
    getWebauthnKeys() {
      axios.get('/webauthn/keys').then(response => {
        this.keys = response.data
      })
    },
    showRemoveModal(token) {
      this.keyToRemove = token
      this.deleteKeyModalOpen = true
    },
    remove() {
      this.removeKeyLoading = true

      axios.delete(`/webauthn/keys/${this.keyToRemove.id}`).then(response => {
        this.removeKeyLoading = false
        this.deleteKeyModalOpen = false
        this.keyToRemove = null

        if (this.keys.length === 1) {
          location.reload()
        } else {
          this.getWebauthnKeys()
        }
      })
    },
    enableKey(id) {
      axios
        .post(
          `/webauthn/enabled-keys`,
          JSON.stringify({
            id: id,
          }),
          {
            headers: { 'Content-Type': 'application/json' },
          }
        )
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    disableKey(id) {
      axios
        .delete(`/webauthn/enabled-keys/${id}`)
        .then(response => {
          //
        })
        .catch(error => {
          if (error.response !== undefined) {
            this.error(error.response.data)
          } else {
            this.error()
          }
        })
    },
    closeDeleteKeyModal() {
      this.deleteKeyModalOpen = false
    },
  },
}
</script>
