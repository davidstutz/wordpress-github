# Wordpress GitHub

Lightweight Wordpress plugin providing acess to GitHub through shortcodes. The plugin is aimed to provide functionality to display repositories, users, commits and issues.

**Work in Progress.**

![Settings for Authentication.](screenshot.png?raw=true 'Settings for Authentication.')

## Index

* [Installation](#installation)
* [Settings](#settings)
* [Shortcodes](#shortcodes)
    * [Commits](#commits)
    * [Issues](#issues)
    * [User](#user)
        * [Number of Repositories](#number-of-repositories)
        * [Number of Commits](#number-of-commits)

## Installation

In `wp-content/plugins`, create a new folder `github` and put all files within this repository in this folder. In the backend, go to "Plugins" -> "Installed Plugins" and activate "Wordpress GitHub".

## Settings

In order to increase the rate limit, that is the number of allowed requests (see [https://developer.github.com/v3/#rate-limiting](https://developer.github.com/v3/#rate-limiting)), you can authenticate by setting your login and password in "Settings" > "GitHub". The form looks as shown above and displays how many requests have already been used after successfully authenticating.

## Shortcodes

The following list details the usage of all provided shortcodes.

### Commits

Use the following shortcode for displaying commits from several repositories:

    [github-commits repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" template="default"]

This will show the 5 most recent commits made to either `davidstutz/wordpress-github` or `davidstutz/wordpress-user-biography`. Additional templates can be implemented by adapting 'Github::$templates'.

### Issues

Use the following shortcode for displaying issues from several repositories and all users:

    [github-issues repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" template="default"]

This will show the 5 most recent issues made to either `davidstutz/wordpress-github` or `davidstutz/wordpress-user-biography`. Additional templates can be implemented by adapting 'Github::$templates'.

### User

#### Number of Repositories

To get the number of public repositories of a specific user, use:

    [github-user-num-repos user="davidstutz"]

#### Numberof Commits

To get the number of commits from a specific user (made to one of his public repositories), use:

    [github-user-num-commits user="davidstutz"]

## License

Copyright (C) 2015 David Stutz

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).