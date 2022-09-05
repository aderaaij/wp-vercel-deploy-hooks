=== Plugin Name ===
Contributors: Arden012
Tags: vercel, deploy, hooks
Requires at least: 5.0
Tested up to: 6.0.1
Stable tag: 1.4.2
Requires PHP: 7.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A WordPress plugin to deploy a static site to Vercel when you publish a new WordPress post, update a WordPress post or deploy on command from the WordPress admin menu or admin bar.

== Description ==

A WordPress plugin to deploy a static site to [Vercel](https://vercel.com/) when you publish a new WordPress post, update a WordPress post or deploy on command from the WordPress admin menu or admin bar.

Based on the excellent WordPress Plugin [WP Netlify Webhook Deploy](https://github.com/lukethacoder/wp-netlify-webhook-deploy).

== Frequently Asked Questions ==

= Does this plugin support scheduling? =

Yes it does, please see the [Plugin Documentation on Github](https://github.com/aderaaij/wp-vercel-deploy-hooks/) for more information. 

= Does this plugin show me if the deploy has been succesful? =

Unfortunately this plugin will not (yet) show build / deploy updates as it is not connecter with the [Vercel API](https://vercel.com/docs/api). Any contributions on getting this to work would be most welcome. 

== Screenshots ==

1. Vercel Deploy Hooks documentation

== Changelog ==

= 1.4.2 =
* Update versioning and readme

= 1.4.1 =
* Add support for environment endpoints in wp-config. Credits to [elliottpost](https://github.com/elliottpost)

= 1.4.0 =
* Add support for triggering build on trashing or restoring of posts. Credits to [mjenczmyktsh](https://github.com/mjenczmyktsh)

= 1.3.0 =
* Add support for custom user roles. Credits to [Morlin1129](https://github.com/Morlin1129/wp-vercel-deploy-hooks)

= 1.0 =
* First release

