import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import fs from 'fs'

export default defineConfig({
  server: {
    hmr: {
      host: 'addy-sh.test',
    },
    host: 'addy-sh.test',
    https: {
      key:
        process.env.NODE_ENV === 'production'
          ? null
          : fs.readFileSync('/home/vagrant/addy-sh.test.key'), // copy from /etc/ssl/certs in homestead so vagrant user has permissions
      cert:
        process.env.NODE_ENV === 'production'
          ? null
          : fs.readFileSync('/home/vagrant/addy-sh.test.crt'),
    },
    watch: {
      usePolling: true,
    },
  },
  plugins: [
    laravel({
      input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/js/webauthn/authenticate.js',
        'resources/js/webauthn/register.js',
      ],
      refresh: true,
    }),
    vue({
      template: {
        transformAssetUrls: {
          base: null,
          includeAbsolute: false,
        },
      },
    }),
  ],
  base: '',
})
