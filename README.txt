
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * How to use Comic Vineyard
 * Installing your own Comic Vineyard
 * Creating Your Own Themes


INTRODUCTION
------------

Current Maintainer: Morbus Iff <morbus@disobey.com>

Comic Vineyard is a way of tracking your comic book collection using Comic
Vine's functionality and data. You maintain your public collection of comics
in a Comic Vine "list" and then use Comic Vineyard to take that data and
turn it into a display of your choice.

Comic Vineyard exists to address two of the most troubling aspects of
tracking your comic book collection: getting all your data in, and getting
better looking data out in a way you'd like.

  Comic Vine:                 http://www.comicvine.com/
  Comic Vineyard Online:      http://www.disobey.com/d/code/comic-vineyard/
  Comic Vineyard source code: https://github.com/morbus/comic-vineyard/


HOW TO USE COMIC VINEYARD
-------------------------

  1. Login to Comic Vine and create a new list.
  2. Search the site for your comic book volumes (not issues).
  3. Add your comic book volumes to your Comic Vine list.
  4. In the volume's comments, indicate the issues you own (see below).
  5. Enter your Comic Vine list URL into Comic Vineyard and "render" it.
  6. Changed your Comic Vine list? "Render" it again with Comic Vineyard.

The easiest way to see how this works is with an actual list:

  http://www.comicvine.com/lists/75-20545/

The following comment and issue formats are acceptable:

  24-30
  24, 25, 26, 27, 28, 29, 30
  1-5,7,11-15, 13
  17-143,176; location: 4; variants: 100C

This approach was taken to speed data entry: with most comic book
trackers, you must manually visit each issue's "page" before adding it to
your collection. Using the above formatting, it's much faster to update
the entire Comic Vine list simply by typing new issue numbers every Wednesday
(and/or adding new volumes).

In addition to the issues you own, you can OPTIONALLY specify additional
information in a "key: value" format separated by semi-colons (the fourth
example above). These additional key values are passed to the Comic Vineyard
theme and may be used as part of the display. The following key values are
"known" and in-use by themes (but, again, you can use any key values you want -
themes will silently ignore ones it doesn't understand):

  location


INSTALLING YOUR OWN COMIC VINEYARD
----------------------------------

There are two ways to run Comic Vineyard:

 * Through someone else's installation
 * Through your own installation

The quickest approach is through the official web installation at:

  http://www.disobey.com/d/code/comic-vineyard/

If you'd like to customize the display of your data with a new theme, or
else tweak Comic Vineyard's code, you'll need your own installation with:

 * PHP 5.2+ with HTTP_Request2 from PEAR.
 * A Comic Vine API key from http://api.comicvine.com/
 * A web server (optional; command line use is available).

To run Comic Vineyard from the command line, use:

  php comicvineyard.php 
    --api_key [your-api-key]
    --url [your-collection-url]

If you want to run Comic Vineyard through your web site, you'll need to find
some way to pass your Comic Vine API key to the script in a secure manner. You
can either manually force the API key in the source file, or you can set it
in the Apache web server like so:

  1. Create an .htaccess file in the root of the Comic Vineyard install.
  2. Add "SetEnv COMIC_VINE_API_KEY [your-api-key]" to it and save.

Whether this will work with your particular Apache installation is a matter
too variable for a README.txt, but don't hesitate to email morbus@disobey.com
and I'll try to help out where I can.


CREATING YOUR OWN THEMES
------------------------

@todo Themes are still being solidified.
