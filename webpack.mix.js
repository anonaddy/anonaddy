let mix = require('laravel-mix')

mix
  .js('resources/js/app.js', 'public/js')
  .js('resources/js/webauthn.js', 'public/js')
  .postCss('resources/css/app.css', 'public/css', [require('tailwindcss')])

if (mix.inProduction()) {
  mix.version()
}
