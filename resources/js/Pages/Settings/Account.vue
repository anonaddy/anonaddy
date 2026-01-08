<template>
  <SettingsLayout>
    <div class="divide-y divide-grey-200">
      <div class="py-10">
        <div class="space-y-1">
          <h3 class="text-lg font-medium leading-6 text-grey-900 dark:text-white">Danger Zone</h3>
          <p class="text-base text-grey-700 dark:text-grey-200">
            Once you delete your account, there is no going back.
            <b>This username will not be able to be used again</b>. Please make sure you are
            certain. Before deleting your account, please export any alias data or information that
            you wish to retain. For more information on what happens when you delete your account
            please see this
            <a
              href="https://addy.io/faq/#what-happens-when-i-delete-my-account"
              rel="nofollow noopener noreferrer"
              target="_blank"
              class="text-indigo-700 cursor-pointer dark:text-indigo-400"
              >FAQ item</a
            >.
          </p>
        </div>
        <div class="mt-4">
          <form @submit.prevent="confirmDeleteAccount()">
            <div class="grid grid-cols-1 mb-6">
              <div>
                <label
                  for="current-password-delete"
                  class="block text-sm font-medium leading-6 text-grey-600 dark:text-white"
                  >Enter your password to confirm</label
                >
                <div class="relative mt-2">
                  <input
                    v-model="deleteAccountForm.password"
                    type="password"
                    name="password"
                    id="current-password-delete"
                    class="block w-full rounded-md border-0 py-2 pr-10 ring-1 ring-inset focus:ring-2 focus:ring-inset sm:text-base sm:leading-6 dark:bg-white/5 dark:text-white"
                    :class="
                      deleteAccountForm.errors.password
                        ? 'text-red-900 ring-red-300 placeholder:text-red-300 focus:ring-red-500'
                        : 'text-grey-900 ring-grey-300 placeholder:text-grey-400 focus:ring-indigo-600'
                    "
                    placeholder="********"
                    :aria-invalid="deleteAccountForm.errors.password ? 'true' : undefined"
                    :aria-describedby="
                      deleteAccountForm.errors.password
                        ? 'current-password-delete-error'
                        : undefined
                    "
                  />
                  <div
                    v-if="deleteAccountForm.errors.password"
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3"
                  >
                    <ExclamationCircleIcon class="h-5 w-5 text-red-500" aria-hidden="true" />
                  </div>
                </div>
                <p
                  v-if="deleteAccountForm.errors.password"
                  class="mt-2 text-sm text-red-600"
                  id="current-password-delete-error"
                >
                  {{ deleteAccountForm.errors.password }}
                </p>
              </div>
            </div>

            <button
              type="submit"
              class="text-white font-bold bg-red-500 hover:bg-red-600 w-full py-3 px-4 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Delete Account
            </button>
          </form>
        </div>
      </div>
    </div>

    <Modal :open="deleteAccountModalOpen" @close="deleteAccountModalOpen = false">
      <template v-slot:title> Delete Account </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to <b>permanently</b> delete your account and any aliases you've
          created?
        </p>
        <div class="mt-6 flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4">
          <button
            type="button"
            @click="submitDeleteAccountForm"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="deleteAccountForm.processing"
          >
            Delete Account
            <loader v-if="deleteAccountForm.processing" />
          </button>
          <button
            @click="deleteAccountModalOpen = false"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>
  </SettingsLayout>
</template>

<script setup>
import SettingsLayout from './../../Layouts/SettingsLayout.vue'
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'
import { ExclamationCircleIcon } from '@heroicons/vue/20/solid'

const deleteAccountModalOpen = ref(false)

const deleteAccountForm = useForm({
  password: '',
})

const confirmDeleteAccount = () => {
  if (!deleteAccountForm.password) {
    deleteAccountForm.setError('password', 'The password field is required.')
  } else {
    deleteAccountModalOpen.value = true
  }
}

const submitDeleteAccountForm = () => {
  deleteAccountForm.post(route('account.destroy'), {
    preserveScroll: true,
    onSuccess: () => deleteAccountForm.reset(),
  })
  deleteAccountModalOpen.value = false
}
</script>
