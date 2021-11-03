# WP Vercel Deploy Hooks

A WordPress plugin to deploy a static site to [Vercel](https://vercel.com/) when you publish a new WordPress post/page, update a WordPress post/page or deploy on command from the WordPress admin menu and admin bar.

Based on the excellent WordPress Plugin [WP Netlify Webhook Deploy](https://github.com/lukethacoder/wp-netlify-webhook-deploy).

## ✅ Features

- 🚗 &nbsp;&nbsp;Deploy your Vercel project when publishing / updating a WordPress post
- 👉 &nbsp;&nbsp;Manually deploy your Vercel Project with the push of a button
- ⏲ &nbsp;&nbsp;Schedule deploys on a daily or weekly basis (mileage may vary).

## 🛠 Installation

You can install WP Vercel Deploy Hooks manually or through Composer

### 🤙 Manual Install

1. Download the plugin as a `.zip` file [from the repository](https://github.com/aderaaij/wp-vercel-deploy-hooks/archive/main.zip)
2. Login to your WordPress site and go to `Plugins -> Add new -> Upload plugin`
3. Locate the `.zip` file on your machine, upload and activate

### 🎼 Composer

Composer allows you to install pacakges from a GitHub repository. This repository includes a `composer.json` file which declares the package as a WordPress plugin. Include it in your project's `composer.json` as following:

```json
...
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/aderaaij/wp-vercel-deploy-hooks.git"
    }
  ],
  "require": {
    "aderaaij/wp-vercel-deploy-hooks": "main"
  }
...
```

Now the package will be included in the plugins folder when you use `composer install/update`.

## ⚙️ Settings / Configuration

To enable the plugin, you will need to create a [Deploy Hook for your Vercel Project](https://vercel.com/docs/more/deploy-hooks).

### 🎚 Settings

After you've created your deploy hook, navigate to `Deploy -> Settings` in the WordPress admin menu and paste your Vercel Deploy hook URL. On the settings page you can also activate deploys when you publish or update a post (disabled by default).

### ⏲ Scheduling

When you enable scheduling it calls [the `cron_schedules` hook](https://developer.wordpress.org/reference/hooks/cron_schedules/) which depends on your site having visitors to be triggered. To make sure your schedule is triggered timely, you could schedule a CRON job in your hosting panel which calls `wp-cron.php`. Please check out the [Webhook Netlify Deploy scheduling documentation](https://github.com/lukethacoder/wp-webhook-netlify-deploy#scheduling-netlify-builds) for more information.

## 👯 Contributors & Credits

This plugin was based on the excellent WordPress Plugin [WP Netlify Webhook Deploy](https://github.com/lukethacoder/wp-netlify-webhook-deploy)

<a href="https://github.com/aderaaij/wp-vercel-deploy-hooks/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=aderaaij/wp-vercel-deploy-hooks" />
</a>

Made with [contrib.rocks](https://contrib.rocks).

## 🤔 To Do

- Add support for deploy and build statusses and updates through the [Vercel API](https://vercel.com/docs/api)
- Add support for Netlify Builds and Deploys
- Add support for multiple Vercel deploy endpoints
