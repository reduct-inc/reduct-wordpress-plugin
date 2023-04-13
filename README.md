# reduct-wordpress-plugin
Embed Reduct Share URLs in Wordpress sites

Get our latest release of our plugin here.
[Embed Reduct Reels - Wordpress Plugin v1.0](https://github.com/reduct-inc/reduct-wordpress-plugin/releases/download/pre-release/reduct-video-plugin.zip).

## Requirements:
- php 7+
- node 16

## Setup 
- download [local app](https://localwp.com/)
- install it and open
- on first page "Create a new site"
- select "Create a new site"
- complete the process with default config ( remember username and password )
- open terminal and cd into "$HOME/Local\ Sites/[site-name]/app/public/wp-content/plugins/
- clone the repo
- cd into the `reduct-wordpress-plugin` directory
- run `yarn install`


## Development 

- cd into "$HOME/Local\ Sites/[site-name]/app/public/wp-content/plugins/reduct-wordpress-plugin
- run `yarn start`
- open local app and start the site server
- click on "WP Admin" to open the admin panel. Use previously set credentials to log in.
- from side panel, go to plugins
- active plugin `reduct-wordpress-plugin`
- open terminal
- from side panel, go to post
- pick any available post
- add plugin `reduct-wordpress-plugin`

## Deployment / bundling

- cd into "$HOME/Local\ Sites/[site-name]/app/public/wp-content/plugins/reduct-wordpress-plugin
- run `yarn bundle`. A zip will appear on the root directory of the project. This zip can be imported as wordpress plugin.
