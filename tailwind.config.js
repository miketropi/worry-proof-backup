module.exports = {
  content: [
    "./*.php",
    "./templates/**/*.php",
    "./src/**/*.js",
    "./src/**/*.css"
  ],
  purge: [],
  darkMode: false, // or 'media' or 'class'
  theme: {
    extend: {
      fontFamily: {
        'space-mono': ['Space Mono', 'monospace'],
      },
    }, 
  },
  variants: {
    extend: {},
  },
  plugins: [],
}
