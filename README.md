# Wordpress GitHub

Lightweight Wordpress plugin providing acess to GitHub through shortcodes. The plugin is aimed to provide functionality to display repositories, users, commits and issues.

**Note:** After authenticating as described in [Settings](#settings), login and password are stored as plain text in the database.

![Settings for Authentication.](screenshot.png?raw=true 'Settings for Authentication.')

## Index

* [Installation](#installation)
* [Settings](#settings)
* [Shortcodes](#shortcodes)
    * [Commits](#commits)
    * [Issues](#issues)
    * [Releases](#releases)

## Installation

In `wp-content/plugins`, create a new folder `github` and put all files within this repository in this folder. In the backend, go to "Plugins" -> "Installed Plugins" and activate "Wordpress GitHub".

## Settings

In order to increase the rate limit, that is the number of allowed requests (see [https://developer.github.com/v3/#rate-limiting](https://developer.github.com/v3/#rate-limiting)), you can authenticate by setting your login and password in "Settings" > "GitHub". The form looks as shown above and displays how many requests have already been used after successfully authenticating.

## Shortcodes

The following list details the usage of all provided shortcodes.

### Commits

Use the following shortcode for displaying commits from several repositories:

    [github-commits repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" format="m/d/Y" template="default"]

This will show the 5 most recent commits made to either `davidstutz/wordpress-github` or `davidstutz/wordpress-user-biography`. The generated HTML looks as follows:

    <ul class="wp-github-commits">
        <li class="wp-github-commit">
            <b><a href="https://github.com/:repository" target="_blank">:repository</a>
            @<a href=":url" target="_blank"><code class="wp-github-commit-sha">:sha</code></a></b>: 
            :message <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :date
        </li>
    </ul>

Additional templates can be added by adapting `Github::$templates`.

### Issues

Use the following shortcode for displaying issues from several repositories and all users:

    [github-issues repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5" format="m/d/Y" template="default"]

This will show the 5 most recent issues made to either `davidstutz/wordpress-github` or `davidstutz/wordpress-user-biography`. Additional templates can be implemented by adapting 'Github::$templates'. The generated HTML looks as follows:

    <ul class="wp-github-issues">
        <li class="wp-github-issue">
            <b><a href="https://github.com/:repository" target="_blank">:repository</a>
            #<a href=":url" target="_blank"><code class="wp-github-issue-number">:number</code></a></b>: 
            :title <a href=":user_url" target="_blank"><img style="display:inline;" height="20" width="20" class="wp-github-commit-avatar" src=":user_avatar" /> :user_login</a>, :state, :date
        </li>
    </ul>

Additional templates can be added by adapting `Github::$templates`.

### Releases

Use the following shortcode for displaying releases of one or several repositories:

    [github-releases repositories="davidstutz/wordpress-github" limit="3" format="m/d/Y" template="default"]

This will show the 3 most recent releases of `davidstutz/wordpress-github` (note that this repository currently has no releases, try `davidstutz/bootstrap-multiselect` instead). The generated HTML looks as follows:

    <ul class="wp-github-releases">
        <li class="wp-github-release">
            <b><a href="https://github.com/:repository" target="_blank">:repository</a>
            /<a href=":url">:tag_name</a></b>: 
            :name <a href=":tar_url" class="wp-github-release-tar">tar</a>, <a href=":zip_url" class="wp-github-release-zip">zip</a>, :date
        </li>
    </ul>

Additional templates can be added by adapting `Github::$templates`.

## License

Copyright (C) 2015 David Stutz

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).