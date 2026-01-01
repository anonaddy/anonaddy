<template>
  <div>
    <Head :title="$page.component.replace('/', ' | ')" />
    <h1 id="primary-heading" class="sr-only">
      {{ $page.component.replace('/', ' | ') }}
    </h1>

    <div class="sm:flex sm:items-center mb-6">
      <div class="sm:flex-auto">
        <h1 class="text-2xl font-semibold text-grey-900 dark:text-white">Settings</h1>
        <p class="mt-2 text-sm text-grey-700 dark:text-grey-100">Make changes to your account</p>
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
                    class="mt-1 block w-full rounded-md border-grey-300 py-2 pl-3 pr-10 text-base focus:border-purple-500 focus:outline-none focus:ring-purple-500 sm:text-base dark:text-white dark:bg-white/5"
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
  </div>
</template>

<script setup>
import { Link, Head, router } from '@inertiajs/vue3'
import { ref } from 'vue'

const tabs = [
  { name: 'General', href: route('settings.show'), current: route().current() === 'settings.show' },
  {
    name: 'Security',
    href: route('settings.security'),
    current: route().current() === 'settings.security',
  },
  { name: 'API Keys', href: route('settings.api'), current: route().current() === 'settings.api' },
  {
    name: 'Account Data',
    href: route('settings.data'),
    current: route().current() === 'settings.data',
  },
  {
    name: 'Delete Account',
    href: route('settings.account'),
    current: route().current() === 'settings.account',
  },
]

const selectedTabName = ref(_.find(tabs, ['current', true]).name)

const visitTab = () => {
  router.visit(_.find(tabs, ['name', selectedTabName.value]).href)
}
</script>
