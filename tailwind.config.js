const svgToDataUri = require('mini-svg-data-uri')

module.exports = {
  content: [
    'app/**/*.php',
    'resources/**/*.html',
    'resources/**/*.js',
    'resources/**/*.php',
    'resources/**/*.vue',
  ],
  safelist: ['bg-green-400', 'bg-red-400', 'bg-grey-400'],
  theme: {
    colors: {
      current: 'currentColor',
      white: '#FFF',
      black: '#000',
      transparent: 'transparent',
      indigo: {
        50: '#E0E8F9',
        100: '#BED0F7',
        200: '#98AEEB',
        300: '#7B93DB',
        400: '#647ACB',
        500: '#4C63B6',
        600: '#4055A8',
        700: '#35469C',
        800: '#2D3A8C',
        900: '#19216C',
      },
      cyan: {
        50: '#E1FCF8',
        100: '#C1FEF6',
        200: '#92FDF2',
        300: '#62F4EB',
        400: '#3AE7E1',
        500: '#1CD4D4',
        600: '#0FB5BA',
        700: '#099AA4',
        800: '#07818F',
        900: '#05606E',
      },
      grey: {
        50: '#F5F7FA',
        100: '#E4E7EB',
        200: '#CBD2D9',
        300: '#9AA5B1',
        400: '#7B8794',
        500: '#616E7C',
        600: '#52606D',
        700: '#3E4C59',
        800: '#323F4B',
        900: '#1F2933',
      },
      pink: {
        50: '#FFE3EC',
        100: '#FFB8D2',
        200: '#FF8CBA',
        300: '#F364A2',
        400: '#E8368F',
        500: '#DA127D',
        600: '#BC0A6F',
        700: '#A30664',
        800: '#870557',
        900: '#620042',
      },
      red: {
        50: '#FFE3E3',
        100: '#FFBDBD',
        200: '#FF9B9B',
        300: '#F86A6A',
        400: '#EF4E4E',
        500: '#E12D39',
        600: '#CF1124',
        700: '#AB091E',
        800: '#8A041A',
        900: '#610316',
      },
      yellow: {
        50: '#FFFBEA',
        100: '#FFF3C4',
        200: '#FCE588',
        300: '#FADB5F',
        400: '#F7C948',
        500: '#F0B429',
        600: '#DE911D',
        700: '#CB6E17',
        800: '#B44D12',
        900: '#8D2B0B',
      },
      green: {
        50: '#E3F9E5',
        100: '#C1F2C7',
        200: '#91E697',
        300: '#51CA58',
        400: '#31B237',
        500: '#18981D',
        600: '#0F8613',
        700: '#0E7817',
        800: '#07600E',
        900: '#014807',
      },
    },
    fontSize: {
      xs: '0.75rem',
      sm: '0.875rem',
      base: '1rem',
      lg: '1.125rem',
      xl: '1.25rem',
      '2xl': '1.5rem',
      '3xl': '1.875rem',
      '4xl': '2.25rem',
      '5xl': '3rem',
      '6xl': '4rem',
    },
    container: {
      center: true,
      padding: '1.5rem',
    },
    extend: {
      backgroundImage: theme => ({
        'multiselect-caret': `url("${svgToDataUri(
          `<svg viewBox="0 0 320 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M31.3 192h257.3c17.8 0 26.7 21.5 14.1 34.1L174.1 354.8c-7.8 7.8-20.5 7.8-28.3 0L17.2 226.1C4.6 213.5 13.5 192 31.3 192z"></path></svg>`
        )}")`,
        'multiselect-spinner': `url("${svgToDataUri(
          `<svg viewBox="0 0 512 512" fill="${theme(
            'colors.green.500'
          )}" xmlns="http://www.w3.org/2000/svg"><path d="M456.433 371.72l-27.79-16.045c-7.192-4.152-10.052-13.136-6.487-20.636 25.82-54.328 23.566-118.602-6.768-171.03-30.265-52.529-84.802-86.621-144.76-91.424C262.35 71.922 256 64.953 256 56.649V24.56c0-9.31 7.916-16.609 17.204-15.96 81.795 5.717 156.412 51.902 197.611 123.408 41.301 71.385 43.99 159.096 8.042 232.792-4.082 8.369-14.361 11.575-22.424 6.92z"></path></svg>`
        )}")`,
        'multiselect-remove': `url("${svgToDataUri(
          `<svg viewBox="0 0 320 512" fill="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M207.6 256l107.72-107.72c6.23-6.23 6.23-16.34 0-22.58l-25.03-25.03c-6.23-6.23-16.34-6.23-22.58 0L160 208.4 52.28 100.68c-6.23-6.23-16.34-6.23-22.58 0L4.68 125.7c-6.23 6.23-6.23 16.34 0 22.58L112.4 256 4.68 363.72c-6.23 6.23-6.23 16.34 0 22.58l25.03 25.03c6.23 6.23 16.34 6.23 22.58 0L160 303.6l107.72 107.72c6.23 6.23 16.34 6.23 22.58 0l25.03-25.03c6.23-6.23 6.23-16.34 0-22.58L207.6 256z"></path></svg>`
        )}")`,
      }),
    },
  },
  plugins: [
    // Some useful comment
  ],
}
