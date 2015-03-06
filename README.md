# Wordpress GitHub

Lightweight Wordpress plugin providing acess to GitHub through shortcodes. The plugin is aimed to provide functionality to display repositories, users, commits and issues.

**Work in Progress.**

## Installation

In `wp-content/plugins`, create a new folder `github` and put all files within this repository in this folder. In the backend, go to "Plugins" -> "Installed Plugins" and activate "Wordpress GitHub".

## Shortcodes

* [Commits](#commits)

### Commits

Use the following shortcodefor displaying commits from several repositories:

    [github-commits repositories="davidstutz/wordpress-github,davidstutz/wordpress-user-biography" limit="5"]

This will show the 5 most recent commits made to either `davidstutz/wordpress-github` or `davidstutz/wordpress-user-biography`.

## License

Copyright (C) 2015 David Stutz

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.

See [http://www.gnu.org/licenses/](http://www.gnu.org/licenses/).