require('./bootstrap')

import dayjs from 'dayjs'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'

dayjs.extend(advancedFormat)
dayjs.extend(relativeTime)

window.Vue = require('vue')

import PortalVue from 'portal-vue'
import Clipboard from 'v-clipboard'
import Notifications from 'vue-notification'

Vue.use(PortalVue)
Vue.use(Clipboard)
Vue.use(Notifications)

Vue.component('dropdown', require('./components/DropdownNav.vue').default)
Vue.component('icon', require('./components/Icon.vue').default)
Vue.component('aliases', require('./pages/Aliases.vue').default)
Vue.component('recipients', require('./pages/Recipients.vue').default)
Vue.component('domains', require('./pages/Domains.vue').default)
Vue.component('usernames', require('./pages/Usernames.vue').default)

Vue.filter('formatDate', value => {
  return dayjs(value).format('Do MMM YYYY')
})

Vue.filter('timeAgo', value => {
  return dayjs(value).fromNow()
})

Vue.filter('truncate', (string, value) => {
  if (value >= string.length) {
    return string
  }
  return string.substring(0, value) + '...'
})

const app = new Vue({
  el: '#app',
  data() {
    return {
      mobileNavActive: false,
    }
  },
})
