# How to Manually Update the WordPress Site URL via Database (phpMyAdmin)

If you can't access the WordPress admin dashboard, you can update the Site URL directly in the database using phpMyAdmin. Follow these steps:

1. Log in to your web hosting control panel and open **phpMyAdmin**.
2. Select your WordPress database from the list on the left.
3. Find and click the **wp_options** table (the prefix may be different, e.g., `wp_`).
4. Locate the rows named:
   - `siteurl`
   - `home`
5. Click **Edit** next to each row and change the `option_value` to your new URL (e.g., https://yournewdomain.com).
6. Click **Go** to save your changes.

**Tip:** After updating, clear your browser cache and any site caching plugins to see the changes take effect.
