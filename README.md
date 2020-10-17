# WP Vercel Deploy Hooks

A WordPress plugin to deploy a static site to [Vercel](https://vercel.com/) when you publish a new WordPress post, update a WordPress post or deploy on command from the WordPress admin menu or admin bar.

Based on the excellent WordPress Plugin [WP Netlify Webhook Deploy](https://github.com/lukethacoder/wp-netlify-webhook-deploy).

## ✅ Features

- 🚗 &nbsp;&nbsp;Deploy your Vercel project when publishing / updating a WordPress post
- 👉 &nbsp;&nbsp;Manually deploy your Vercel Project with the push of a button

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

### Settings

After you've created your deploy hook, navigate to `Deploy -> Settings` in the WordPress admin menu and paste your Vercel Deploy hook URL. On the settings page you can also activate deploys when you publish or update a post (disabled by default).

## 🤔 To Do

- Add support for deploy and build statusses and updates through the [Vercel API](https://vercel.com/docs/api)
