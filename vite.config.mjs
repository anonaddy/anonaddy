import { defineConfig, loadEnv } from 'vite'
import laravel from 'laravel-vite-plugin'
import vue from '@vitejs/plugin-vue'
import fs from 'fs'
import { resolve } from 'path'

export default defineConfig(({ command, mode }) => {
  const env = loadEnv(mode, process.cwd(), 'APP_URL')
  const host = env.APP_URL.replace(/https?:\/\//, '')
  return {
    server: {
      host: host,
      hmr: {
        host: host,
      },
      https: {
        key:
          process.env.NODE_ENV === 'production'
            ? null
            : fs.readFileSync(`/home/vagrant/${host}.key`), // copy from /etc/ssl/certs in homestead so vagrant user has permissions
        cert:
          process.env.NODE_ENV === 'production'
            ? null
            : fs.readFileSync(`/home/vagrant/${host}.crt`),
      },
      watch: {
        usePolling: true,
        ignored: ['**/vendor/**', '**/postfix/**', '**/storage/**'],
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
    resolve: {
      alias: {
          'ziggy-js': resolve('vendor/tightenco/ziggy'),
      },
    },
  }
})

