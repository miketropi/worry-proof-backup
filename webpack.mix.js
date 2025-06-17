const mix = require('laravel-mix');

mix
  .js('src/main.js', 'wp-backup.bundle.js')
  .react()
  .setPublicPath(`./dist`);

mix.version();