<template>
  <div>
    <Head :title="pageTitle" />
    <h1 id="primary-heading" class="sr-only">{{ pageTitle }}</h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">{{ pageTitle }}</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-200">
          {{ pageIntro }}
        </p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 dark:bg-grey-900">
      <div class="divide-y divide-grey-200 dark:divide-grey-400">
        <div class="pb-8">
          <h2 class="text-lg font-medium text-grey-900 dark:text-white">This email</h2>
          <dl class="mt-4 space-y-3 text-base text-grey-700 dark:text-grey-200">
            <div>
              <dt class="font-medium text-grey-900 dark:text-white">Alias</dt>
              <dd class="mt-1 break-words">{{ alias.email }}</dd>
            </div>
            <div v-if="alias.description">
              <dt class="font-medium text-grey-900 dark:text-white">Description</dt>
              <dd class="mt-1 break-words">{{ alias.description }}</dd>
            </div>
            <div v-if="senderEmail">
              <dt class="font-medium text-grey-900 dark:text-white">Sender</dt>
              <dd class="mt-1 break-words">{{ senderEmail }}</dd>
            </div>
            <div v-if="senderDomain && action !== 'block_email'">
              <dt class="font-medium text-grey-900 dark:text-white">Sender domain</dt>
              <dd class="mt-1 break-words">{{ senderDomain }}</dd>
            </div>
          </dl>
          <Link
            :href="route('aliases.edit', alias.id)"
            class="inline-block mt-4 text-indigo-600 dark:text-indigo-400 hover:text-indigo-500 font-medium"
          >
            Edit this alias
          </Link>
        </div>

        <div v-if="action === 'block_email' && senderEmail" class="pt-6">
          <p class="text-base text-grey-700 dark:text-grey-200">
            Are you sure you want to block <b class="break-words">{{ senderEmail }}</b
            >? Mail from this address will not reach your aliases.
          </p>
          <p v-if="blockEmailForm.errors.email" class="mt-2 text-sm text-red-600">
            {{ blockEmailForm.errors.email }}
          </p>
          <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <button
              type="button"
              class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="blockEmailForm.processing"
              @click="submitBlockEmail"
            >
              Block sender
              <loader v-if="blockEmailForm.processing" />
            </button>
            <Link
              :href="route('aliases.edit', alias.id)"
              class="px-4 py-3 text-center text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Cancel
            </Link>
          </div>
        </div>

        <div v-else-if="action === 'block_domain' && senderDomain && canBlockDomain" class="pt-6">
          <p class="text-base text-grey-700 dark:text-grey-200">
            Are you sure you want to block the whole domain
            <b class="break-words">{{ senderDomain }}</b
            >? This can stop a lot of mail, for example if the domain is a shared provider such as
            gmail.com.
          </p>
          <p v-if="blockDomainForm.errors.domain" class="mt-2 text-sm text-red-600">
            {{ blockDomainForm.errors.domain }}
          </p>
          <div class="mt-6 flex flex-col sm:flex-row gap-4">
            <button
              type="button"
              class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
              :disabled="blockDomainForm.processing"
              @click="submitBlockDomain"
            >
              Block domain
              <loader v-if="blockDomainForm.processing" />
            </button>
            <Link
              :href="route('aliases.edit', alias.id)"
              class="px-4 py-3 text-center text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            >
              Cancel
            </Link>
          </div>
        </div>

        <div v-else-if="action === 'block_email' && !senderEmail" class="pt-6">
          <p class="text-base text-grey-700 dark:text-grey-200">
            This link has no sender email, so you cannot block a sender from here.
          </p>
        </div>

        <div v-else-if="action === 'block_domain'" class="pt-6">
          <p class="text-base text-grey-700 dark:text-grey-200">You cannot block this domain.</p>
        </div>

        <div v-else class="pt-6">
          <h2 class="text-lg font-medium text-grey-900 dark:text-white">Block this sender</h2>
          <p class="mt-2 text-base text-grey-700 dark:text-grey-200">
            Stop mail from this sender email, or from every address on the sender domain.
          </p>

          <template v-if="senderEmail">
            <button
              type="button"
              class="mt-4 bg-cyan-400 w-full sm:w-auto hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              @click="blockEmailModalOpen = true"
            >
              Block {{ senderEmail }}
            </button>
            <p v-if="blockEmailForm.errors.email" class="mt-2 text-sm text-red-600">
              {{ blockEmailForm.errors.email }}
            </p>
          </template>
          <p v-else class="mt-2 text-base text-grey-700 dark:text-grey-200">
            This link has no sender email, so you cannot block a sender from here.
          </p>

          <template v-if="senderDomain && canBlockDomain">
            <button
              type="button"
              class="mt-4 bg-white w-full sm:w-auto hover:bg-grey-50 text-grey-800 font-bold py-3 px-4 rounded border border-grey-200 dark:bg-grey-800 dark:text-grey-100 dark:hover:bg-grey-700 dark:border-grey-600 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
              @click="blockDomainModalOpen = true"
            >
              Block {{ senderDomain }}
            </button>
            <p v-if="blockDomainForm.errors.domain" class="mt-2 text-sm text-red-600">
              {{ blockDomainForm.errors.domain }}
            </p>
          </template>
        </div>
      </div>
    </div>

    <Modal :open="blockEmailModalOpen" @close="blockEmailModalOpen = false">
      <template v-slot:title>Block sender email</template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to block <b class="break-words">{{ senderEmail }}</b
          >? Mail from this address will not reach your aliases.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-4">
          <button
            type="button"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="blockEmailForm.processing"
            @click="submitBlockEmail"
          >
            Block sender
            <loader v-if="blockEmailForm.processing" />
          </button>
          <button
            type="button"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            @click="blockEmailModalOpen = false"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>

    <Modal :open="blockDomainModalOpen" @close="blockDomainModalOpen = false">
      <template v-slot:title>Block sender domain</template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Are you sure you want to block the whole domain
          <b class="break-words">{{ senderDomain }}</b
          >? This can stop a lot of mail, for example if the domain is a shared provider such as
          gmail.com.
        </p>
        <div class="mt-6 flex flex-col sm:flex-row gap-4">
          <button
            type="button"
            class="px-4 py-3 text-white font-semibold bg-red-500 hover:bg-red-600 border border-transparent rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:cursor-not-allowed"
            :disabled="blockDomainForm.processing"
            @click="submitBlockDomain"
          >
            Block domain
            <loader v-if="blockDomainForm.processing" />
          </button>
          <button
            type="button"
            class="px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
            @click="blockDomainModalOpen = false"
          >
            Cancel
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue'
import { Head, Link, useForm } from '@inertiajs/vue3'
import Modal from '../../Components/Modal.vue'

const props = defineProps({
  alias: {
    type: Object,
    required: true,
  },
  senderEmail: {
    type: String,
    default: null,
  },
  senderDomain: {
    type: String,
    default: null,
  },
  action: {
    type: String,
    default: null,
  },
  canBlockDomain: {
    type: Boolean,
    default: false,
  },
})

const pageTitle = computed(() => {
  if (props.action === 'block_email') {
    return 'Block sender email'
  }

  if (props.action === 'block_domain') {
    return 'Block sender domain'
  }

  return 'Alias Actions'
})

const pageIntro = computed(() => {
  if (props.action === 'block_email') {
    return 'Confirm that you want to block this sender email.'
  }

  if (props.action === 'block_domain') {
    return 'Confirm that you want to block this sender domain.'
  }

  return 'Choose what to do with this sender.'
})

const blockEmailModalOpen = ref(false)
const blockDomainModalOpen = ref(false)

const blockEmailForm = useForm({
  email: props.senderEmail,
})
const blockDomainForm = useForm({
  domain: props.senderDomain,
})

const submitBlockEmail = () => {
  blockEmailForm.post(route('aliases.banner_actions.block_email', props.alias.id))
}

const submitBlockDomain = () => {
  blockDomainForm.post(route('aliases.banner_actions.block_domain', props.alias.id))
}
</script>
