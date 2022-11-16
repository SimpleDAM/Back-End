# Back End
The files required to set up a back-end SimpleDAM API server using Linux, Apache, MySQL and PHP (LAMP).

**Developers should note that the back-end provided here is simply *one* implementation that delivers the necessary responses to fulfil the SimpleDAM protocol; providing you stick to the protocol and serve JSON back to clients, you are free to use whatever technology you choose.**

For more information, please read [An Introduction to the SimpleDAM API Protocol](https://digitalassetmanagementnews.org/features/an-introduction-to-the-simpledam-api-protocol/) on the DAM News website.
## Requirements
- Linux type server running Apache (at least version 2)
- PHP (7.4 or above)
- MySQL or MariaDB
- GD and Imagick PHP Extensions (for preview and thumbnail creation)
## Installation (Apache, PHP, MySQL)
1. Set up a new MySQL database named 'simpledam', along with a database user that has sufficient privileges
2. Using phpMyAdmin or similar, import the `simpledam.sql` file to set up the necessary database tables.  This will also create two default SimpleDAM users (a normal user: *mail@example.com* and an administrator: *admin@example.com*), along with a default set of assets.
3. Modify the `api.settings.php` file within `/includes/classes` to reflect your server configuration and database credentials
4. Upload everything in the `src` folder to `/var/www/simpledam`  on your server (contents only, do not upload the `src` folder itself)
5. Ensure that you have set up the necessary Apache configuration files (also called virtual host files) so that your server responds to requests and points them to the correct directory (`/var/www/simpledam`). This is out of scope for these instructions, but further information on setting up Apache virtual host files can be found [here](https://httpd.apache.org/docs/2.4/vhosts/examples.html).
### File Permissions
Ensure that `/var/www/simpledam` and its contents are owned by group: www-data and user: www-data by running the following command in the terminal console:

`chown -R www-data:www-data /var/www/simpledam`

Apply the following folder permissions:

- assets: 755
- htdocs: 755
- import: 755
- includes: 755
- logs: 775
- preview: 755
- thumbnail: 755

## Front End Installation
If you also wish to install a front-end for SimpleDAM, go to the [Front End](https://github.com/SimpleDAM/Front-End) repository, download the source files and upload everything within the `src` folder to your `htdocs` folder (again, do not include the `src` folder itself). The `htdocs` folder should already exist at the location below if you have followed the above instructions:

`/var/www/simpledam/htdocs`

## URL Rewrites with .htaccess
The API entry point expects URLs in a particular format:

`/api/[entity]/[action]/?request_parameters`

In order to achieve this, SimpleDAM uses Apache's mod_rewrite within the webroot's .htaccess file to redirect requests.  A typical URL might look like the following:

`/api/asset/list/?start=0&limit=10&sort=assetid&dir=asc`

When redirected to the entry point, the translated URL would appear as follows:

`/api/index.php?entity=asset&action=list&start=0&limit=10&sort=assetid&dir=asc`

Depending upon the server's operating system, developers may choose to implement these so-called pretty URLs using a different approach.  The above information is provided solely to demonstrate what the API entry point is expecting when using Linux, Apache and PHP.
