require('./bootstrap')

import dayjs from 'dayjs'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'
import utc from 'dayjs/plugin/utc'

dayjs.extend(advancedFormat)
dayjs.extend(relativeTime)
dayjs.extend(utc)

import { createApp } from 'vue'

import Clipboard from 'v-clipboard'
import Notifications from '@kyvg/vue3-notification'
import VueGoodTablePlugin from 'vue-good-table-next'

const app = createApp({
  data() {
    return {
      mobileNavActive: false,
    }
  },
})

app.use(Clipboard)
app.use(Notifications)
app.use(VueGoodTablePlugin)

app.component('loader', require('./components/Loader.vue').default)
app.component('dropdown', require('./components/DropdownNav.vue').default)
app.component('icon', require('./components/Icon.vue').default)

app.component('aliases', require('./pages/Aliases.vue').default)
app.component('recipients', require('./pages/Recipients.vue').default)
app.component('domains', require('./pages/Domains.vue').default)
app.component('usernames', require('./pages/Usernames.vue').default)
app.component('rules', require('./pages/Rules.vue').default)
app.component('failed-deliveries', require('./pages/FailedDeliveries.vue').default)

app.component(
  'personal-access-tokens',
  require('./components/sanctum/PersonalAccessTokens.vue').default
)
app.component('webauthn-keys', require('./components/WebauthnKeys.vue').default)

// Global filters
app.config.globalProperties.$filters = {
  formatDate(value) {
    return dayjs.utc(value).local().format('Do MMM YYYY')
  },
  formatDateTime(value) {
    return dayjs.utc(value).local().format('Do MMM YYYY h:mm A')
  },
  timeAgo(value) {
    return dayjs.utc(value).fromNow()
  },
  truncate(value, length) {
    if (length >= value.length) {
      return value
    }
    return value.substring(0, length) + '...'
  },
}

app.mount('#app')
