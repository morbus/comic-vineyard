
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Creating your List
 * Installation and Usage
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


CREATING YOUR LIST
------------------

The first step to using Comic Vineyard is to login to Comic Vine and create
a new "list". This list will contain all the comic book volumes you own,
and each volume's "comment" will indicate all the issues you own of that
volume. The easiest way to see how this works is with an example:

  http://www.comicvine.com/myvine/morbus/my-comic-vineyard-collection/75-20545/

To add an item to your list, find the comic book volume on the site. The
"volume" is the master page of all the issues, NOT an issue itself. For
example, if you own a bunch of Batman issues, you'd go to:

  http://www.comicvine.com/batman/49-796/

At the top of the screen, you'll see a green button that says "Add to a list".
When you click to add it to your new list, you'll be given a chance to set
an optional note - this is where you define what issues of the comic book you
have. The following formats are all acceptable:

  24-30
  24, 25, 26, 27, 28, 29, 30
  1-5,7,11-15, 13

The first is a range of issues (from issue #24 to issue #30), the second is
a longer way to say the same thing, and the third uses both formats (which
also indicates that you own two issue #13s, one specified in the range, and
one specified individually).

This approach was taken to speed data entry: with most comic book trackers,
you must manually visit each issue's page before adding it to your collection.
Using the above formatting, it's much faster to update the entire Comic Vine
list simply by typing new issue numbers every Wednesday (and/or adding the
occasional new volume).

In addition to the issues you own, you can OPTIONALLY specify additional
information in a "key: value" format separated by semi-colons. These
additional key values are passed to the Comic Vineyard theme and may be
used as part of the display. For example, to specify the five issues you
own and their location, you'd use:

  1,4,5-7; location: box 3

The following key values are "known" and in-use by themes (but, again,
you can use any key values you want - themes will silently ignore ones
it doesn't understand):

  location


INSTALLATION AND USAGE
----------------------

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

  php comicvineyard.php [your-api-key] [your-collection-url] [theme]

The final result will be saved in the renders/ subdirectory.


CREATING YOUR OWN THEMES
------------------------

@todo
