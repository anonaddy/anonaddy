import './bootstrap'
import '../css/app.css'

import dayjs from 'dayjs'
import advancedFormat from 'dayjs/plugin/advancedFormat'
import relativeTime from 'dayjs/plugin/relativeTime'
import utc from 'dayjs/plugin/utc'

dayjs.extend(advancedFormat)
dayjs.extend(relativeTime)
dayjs.extend(utc)

window.dayjs = dayjs

import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from '../../vendor/tightenco/ziggy/dist/vue.m'
import Notifications from '@kyvg/vue3-notification'

// Styles
import 'tippy.js/dist/svg-arrow.css'
import 'tippy.js/dist/tippy.css'
import '@vueform/multiselect/themes/default.css'

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'addy.io'

import AppLayout from './Layouts/AppLayout.vue'

// Global components
import Icon from './Components/Icon.vue'
import Loader from './Components/Loader.vue'

createInertiaApp({
  progress: {
    color: '#3AE7E1',
    delay: 50,
  },
  resolve: name => {
    const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
    let page = pages[`./Pages/${name}.vue`]
    page.default.layout = page.default.layout || AppLayout
    return page
  },
  title: title => `${title} - ${appName}`,
  setup({ el, App, props, plugin }) {
    const addy = createApp({
      render: () => h(App, props),
    })

    addy.use(plugin)
    addy.use(ZiggyVue)
    addy.use(Notifications)

    addy.component('Icon', Icon)
    addy.component('Loader', Loader)

    addy.config.globalProperties.$filters = {
      formatDate(value) {
        return dayjs.utc(value).local().format('Do MMM YYYY')
      },
      formatDateTime(value) {
        return dayjs.utc(value).local().format('Do MMM YYYY h:mm A')
      },
      timeAgo(value) {
        return dayjs.utc(value).fromNow()
      },
      dateTimeNow() {
        return dayjs.utc().format()
      },
      truncate(value, length) {
        if (length >= value.length) {
          return value
        }
        return value.substring(0, length) + '...'
      },
    }

    return addy.mount(el)
  },
})
