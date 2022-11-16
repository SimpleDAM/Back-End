# Back End
 The files required to set up a back-end SimpleDAM API server using Linux, Apache, MySQL and PHP (LAMP).
## Requirements
- Linux type server running Apache (at least version 2)
- PHP (7.4 or above)
- MySQL or MariaDB
- GD and Imagick PHP Extensions (for preview and thumbnail creation)
## URL Rewrites with .htaccess
The API entry point expects URLs in a particular format:

`/api/[entity]/[action]/?request_parameters`

In order to achieve this, SimpleDAM uses Apache's mod_rewrite within the webroot's .htaccess file to redirect requests.  A typical URL might look like the following:

`/api/asset/list/?start=0&limit=10&sort=assetid&dir=asc`

When redirected to the entry point, the translated URL would appear as follows:

`/api/index.php?entity=asset&action=list&start=0&limit=10&sort=assetid&dir=asc`

Depending upon the server's operating system, developers may choose to implement these so-called pretty URLs using a different approach.  The above information is provided solely to demonstrate what the entry point is expecting when using Apache and PHP.
