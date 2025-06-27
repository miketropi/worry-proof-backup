# 🚀 How to Manually Update Your WordPress Site URL (phpMyAdmin Edition)

> **Heads up!**
> This guide is for when you can't access your WordPress dashboard. Let's fix your site URL directly in the database—quick and easy! 💻✨

---

## 🛠️ Step-by-Step: Update via phpMyAdmin

1. **Login to your hosting control panel** and open **phpMyAdmin** 🗄️
2. **Pick your WordPress database** from the sidebar 📂
3. **Find the table:** `wp_options` (your prefix might be different, like `mywp_options`) 🔍
4. **Look for these rows:**
   - `siteurl`
   - `home`
5. **Edit** each row:
   - Click **Edit** ✏️
   - Change the `option_value` to your new URL (e.g., `https://yournewdomain.com`) 🌐
   - Hit **Go** to save ✅

---

### 💡 Pro Tip
After updating, clear your browser cache and any site caching plugins to see the changes! 🔄🧹

---

✨ You did it! Your WordPress site URL is now updated. If you run into issues, double-check your changes or ask your host for help. 👩‍💻👨‍💻
