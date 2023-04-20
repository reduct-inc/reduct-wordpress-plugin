## How to Embed Reduct Reels on WordPress 
- Download our latest release of our plugin here. [Embed Reduct Reels - Wordpress Plugin v1.0.](https://github.com/reduct-inc/reduct-wordpress-plugin/releases/download/pre-release/reduct-video-plugin.zip)
- Log in to your WordPress site and access the dashboard. This is where you'll manage your site and all of its features.
- Navigate to the plugins section. Once you're in the dashboard, navigate to the plugins section on the left-hand menu. Here, you'll see a list of all the plugins you have installed on your site.
- Click on add new at the top of the page. And then click on the upload plugin and choose the download zipped file.
- Click on the install button.
- Activate the plugin. The plugin will now be listed in your list of installed plugins.
- Embed the shared reels. Open the page where you want to embed the video and hit the plus icon towards the top left. Click on the reduct video plugin. Paste the shared reel URL in the text box and hit embed.


## For Developers

### Setting up the project locally.

Requirements:

- php 7+
- node 16

Steps

- Download the [local app](https://localwp.com/) (Wordpress development tool).
- Install it and then open.
- On the first page, "Create a new site"
- Select "Create a new site"
- Complete the process with the default config (make sure you remember the username and password).
- Open your terminal and type 
  ‘cd $HOME/Local\Sites/[site-name]/app/public/wp-content/plugins/’
- Clone the repo
- Go inside the reduct-wordpress-plugin directory
  ‘cd reduct-wordpress-plugin’
- run yarn install


### Development
- cd into "$HOME/Local\ Sites/[site-name]/app/public/wp-content/plugins/reduct-wordpress-plugin
- run yarn start
- open the local app and start the site server.
- Click on the "WP Admin" to open the admin panel. Use previously set credentials to log in.
- From the side panel, go to plugins
- Activate the plugin reduct-wordpress-plugin
- Open your terminal
- From the side panel, go to post
- Pick one of the available posts
- Add the plugin reduct-wordpress-plugin


### Deployment / bundling

- cd into "$HOME/Local\ Sites/[site-name]/app/public/wp-content/plugins/reduct-wordpress-plugin
- run yarn bundle. A zip will appear on the root directory of the project. This zip file can be imported as a wordpress plugin.

