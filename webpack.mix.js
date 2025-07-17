const mix = require('laravel-mix');

mix
  .js('src/main.js', 'worry-proof-backup.bundle.js')
  .react()
  .setPublicPath(`./dist`);

mix.version();