<template>
  <div>
    <Head :title="$page.component.replace('/', ' | ')" />
    <h1 id="primary-heading" class="sr-only">
      {{ $page.component.replace('/', ' | ') }}
    </h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Settings</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-100">
          Make changes to your account
          <button
            type="button"
            @click="moreInfoOpen = !moreInfoOpen"
            class="inline-flex items-center gap-1 ml-1 font-medium text-indigo-700 dark:text-indigo-200 hover:text-indigo-300 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            More info
            <InformationCircleIcon class="h-5 w-5" aria-hidden="true" />
          </button>
        </p>
      </div>
    </div>

    <div class="bg-white rounded-lg shadow p-4 dark:bg-grey-900">
      <main class="flex-1">
        <div class="relative">
          <div>
            <div class="px-4 sm:px-6 md:px-0">
              <div>
                <!-- Tabs -->
                <div class="lg:hidden">
                  <label for="selected-tab" class="sr-only">Select a tab</label>
                  <select
                    id="selected-tab"
                    name="selected-tab"
                    v-model="selectedTabName"
                    @change="visitTab()"
                    class="mt-1 block w-full rounded-md border-grey-300 py-2 pl-3 pr-10 text-base focus:border-purple-500 focus:outline-hidden focus:ring-purple-500 sm:text-base dark:text-white dark:bg-white/5"
                  >
                    <option
                      v-for="tab in tabs"
                      :key="tab.name"
                      :selected="tab.current"
                      class="dark:bg-grey-900"
                    >
                      {{ tab.name }}
                    </option>
                  </select>
                </div>
                <div class="hidden lg:block">
                  <div class="border-b border-grey-200">
                    <nav class="-mb-px flex space-x-8">
                      <Link
                        v-for="tab in tabs"
                        as="button"
                        type="button"
                        :key="tab.name"
                        :href="tab.href"
                        :class="[
                          tab.current
                            ? 'border-grey-900 text-grey-900 dark:border-white dark:text-white'
                            : 'border-transparent text-grey-500 hover:border-grey-300 hover:text-grey-700 dark:text-grey-300  dark:hover:text-grey-500',
                          'whitespace-nowrap py-4 px-1 border-b-2 font-medium text-base',
                        ]"
                        >{{ tab.name }}</Link
                      >
                    </nav>
                  </div>
                </div>

                <div>
                  <slot />
                </div>
              </div>
            </div>
          </div>
        </div>
      </main>
    </div>

    <Modal :open="moreInfoOpen" @close="moreInfoOpen = false">
      <template v-slot:title> More information </template>
      <template v-slot:content>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          Settings is where you manage account-wide preferences. Use the tabs to move between
          sections:
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          <b>General</b> - account email, default alias domain and format, and other preferences.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          <b>Security</b> - password, active sessions, two-factor authentication and passkeys.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          <b>API Keys</b> - create and manage tokens for the browser extension, mobile apps and
          other integrations.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          <b>Account Data</b> - export aliases and account data, or import aliases for a custom
          domain.
        </p>
        <p class="mt-4 text-grey-700 dark:text-grey-200">
          <b>Delete Account</b> - permanently delete your addy.io account and associated data.
        </p>

        <div class="mt-6 flex flex-col sm:flex-row">
          <a
            href="https://addy.io/help/category/accounts-and-settings/"
            target="_blank"
            rel="nofollow noreferrer noopener"
            class="inline-flex items-center justify-center bg-cyan-400 hover:bg-cyan-300 text-cyan-900 font-bold py-3 px-4 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            View settings help
            <ArrowTopRightOnSquareIcon class="h-4 w-4 ml-2" aria-hidden="true" />
          </a>
          <button
            @click="moreInfoOpen = false"
            class="mt-3 sm:mt-0 sm:ml-4 px-4 py-3 text-grey-800 font-semibold bg-white hover:bg-grey-50 dark:text-grey-100 dark:hover:bg-grey-700 dark:bg-grey-600 dark:border-grey-700 border border-grey-100 rounded focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
          >
            Close
          </button>
        </div>
      </template>
    </Modal>
  </div>
</template>

<script setup>
import { Link, Head, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import Modal from '../Components/Modal.vue'
import { InformationCircleIcon } from '@heroicons/vue/24/outline'
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/20/solid'

const page = usePage()

const allTabs = [
  {
    name: 'General',
    href: route('settings.show'),
    current: route().current() === 'settings.show',
    enabled: true,
  },
  {
    name: 'Security',
    href: route('settings.security'),
    current: route().current() === 'settings.security',
    enabled: !page.props.usesExternalAuthentication,
  },
  {
    name: 'API Keys',
    href: route('settings.api'),
    current: route().current() === 'settings.api',
    enabled: true,
  },
  {
    name: 'Account Data',
    href: route('settings.data'),
    current: route().current() === 'settings.data',
    enabled: true,
  },
  {
    name: 'Delete Account',
    href: route('settings.account'),
    current: route().current() === 'settings.account',
    enabled: !page.props.usesExternalAuthentication,
  },
]

const tabs = computed(() => allTabs.filter(tab => tab.enabled))

const selectedTabName = ref(_.find(allTabs, ['current', true]).name)
const moreInfoOpen = ref(false)

const visitTab = () => {
  router.visit(_.find(allTabs, ['name', selectedTabName.value]).href)
}
</script>
