<?php

/**
 * @file
 * Comic Vineyard: use ComicVine.com for comic book collection tracking.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

require_once 'HTTP/Request2.php';
date_default_timezone_set('GMT');
set_time_limit(0); // FOREVEVRE!

/* ************************************************************************** */
/* Step 1: determine where we're being run and how, then setup progress bar.  */
/* ************************************************************************** */
// browser-run. be as responsive as possible.
if (isset($_REQUEST['urls'])) {
  disable_output_buffering(); // send output to the browser as fast as possible.
  print progress_surroundings('header'); // footer will show up at script end.
  $api_key  = isset($_REQUEST['api_key']) ? $_REQUEST['api_key'] : $_SERVER['COMIC_VINE_API_KEY'];
  if (isset($_REQUEST['urls'])) { // fetch everything sent to us yummy yummy yummy
    foreach (explode("\n", trim($_REQUEST['urls'])) as $list_url) {
      $list_urls[] = $list_url; // loop through em later.
    }
  }
}
else { // treat it like a command-line run.
  while ($param = array_shift($_SERVER['argv'])) {
    switch ($param) {
      case '--api_key': $api_key  = array_shift($_SERVER['argv']); break;
      case '--url':     $list_urls[] = array_shift($_SERVER['argv']); break;
    }
  }
}

progress("<p>Comic Vineyard is now rendering your comic book collection. Be patient, bub.</p>\n");
$theme_fields = 'issue_number,publish_year,publish_month,publish_day,image,site_detail_url';
$issue_numbers = cache_load('issue-numbers.db');

/* ************************************************************************** */
/* Step 2: get our collection of volumes, and parse the comments for issues.  */
/* ************************************************************************** */
progress("<ul>\n"); // make it purdy.
foreach ($list_urls as $list_url) {
  if (!preg_match('!http://(www.)?comicvine.com/myvine/(.*?)/(.*?)/75-(\d+)!', $list_url)) {
    progress("<br />[ERROR] $list_url is not a list.\n"); // NOT ONE OF US. NOT ONE OF US. NOT ONE OF...
    continue; // at least, not one we've ever seen ...US. NOT ONE OF US. NOT ONE OF US. NOT ONE OF US...
  }

  // take off existing ?page params and force a start on page 1.
  $list_url = preg_replace('/\?page=(.*)/', '', trim($list_url));
  progress("<li><span class=\"message\">Fetching list data from $list_url...</span> ");
  $list_url .= "?page=1"; // pretty ugly looking code in the nearby 10 or so lines.
  $total_pages = 1; // lists always have at least 1 total pages.

  // load up every page of the list.
  for ($i = 1; $i <= $total_pages; $i++) {
    $list_url = preg_replace('!\?page=(\d+)!', "?page=$i", $list_url);
    progress('<span class="heartbeat">.</span> '); // BADUMP. BADUMP. BADUMP.
    $list_data = http_request($list_url); // IM IN YER HTTP GITTING YER DATA.
    $pages_xml = $list_data->xpath("//li[@class='results']"); // divide bah 50, foo.
    $total_pages = floor(str_replace(' results', '', $pages_xml[0]) / 50 + 1);

    // for every item in this list...
    foreach ($list_data->xpath("//tr") as $row) {
      $volume_id = preg_replace("!/.*?/\d*-(\d*)/!", "$1", $row->td[1]->a["href"]);
      $collection[$volume_id]['name'] = preg_replace("!^\d+\. (.*?)!", "$1", $row->td[1]->a);
      $collection[$volume_id]['unparsed'] = (string) $row->td[1]->p;
      $collection[$volume_id]['issue numbers'] = array();
      $found_issue_numbers = array(); // transient.

      // try to parse out the comments into something logical, in
      // the form of "1,2,4-10; location: box 1" (; and : delimiters).
      // array_filter() will remove any empty bits of the array.
      $comment_parts = array_filter(explode(";", $collection[$volume_id]['unparsed']));
      foreach ($comment_parts as $comment_part) {
        $key_values = explode(":", $comment_part);

        // if we have an unlabeled comment part, we treat it as
        // a list of issue numbers which must be further parsed.
        if (isset($key_values[0]) && !isset($key_values[1])) {
          $issue_number_parts = explode(",", $key_values[0]);
          foreach ($issue_number_parts as $issue_number_part) {
            $issue_number_part = trim($issue_number_part);

            // ISSUE RANGE ex.: [ 1-3, 4-1, 1 - 24 ]
            if (strpos($issue_number_part, '-') !== FALSE) {
              preg_match("/(\d+)\s*-\s*(\d+)/", $issue_number_part, $matches);
              // sanity check. if the second number is LOWER than the first number,
              // someone has done a "8-3" type of range, and we need to swap values.
              if ($matches[2] < $matches[1]) { // damn fools makin' infinite loops!
                list($matches[1], $matches[2]) = array($matches[2], $matches[1]);
              }

              for ($matches[1]; $matches[1] <= $matches[2]; $matches[1]++) {
                $found_issue_numbers[$matches[1]]++;
              }
            }

            // ISSUE WITH COUNT ex.: [ 24(2), 100 (5) ]
            elseif (preg_match('/(\d+) ?(\(\d+\))/', $issue_number_part, $matches)) {
              $found_issue_numbers[$matches[1]] += trim($matches[2], '( )');
            }

            // SINGLE ISSUE ex.: erm. really? sigh: [ 4 ]
            elseif (preg_match('/(\d+)/', $issue_number_part, $matches)) {
              $found_issue_numbers[$matches[1]]++;
            }
          }
        }
        // otherwise, it's a normal set of key:value pairs.
        elseif (isset($key_values[0]) && isset($key_values[1])) {
          $collection[$volume_id][strtolower(trim($key_values[0]))] = trim($key_values[1]);
        }
      }

      // move all our found issues into our master collection.
      foreach ($found_issue_numbers as $found_issue_number => $count) {
        $collection[$volume_id]['issue numbers'][$found_issue_number] = array('count' => $count);
      }

      // if we've found no issues, we should complain a bit.
      if (count($collection[$volume_id]['issue numbers']) == 0) {
        progress("<br />[ERROR] Comic Vineyard doesn't think you specified any issues for " . $collection[$volume_id]['name'] . ".\n");
        unset($collection[$volume_id]); // remove it from the collection so that it's not themed empty.
      }
    }
  } progress("</li>\n");
} progress("</ul>");

if (!isset($collection)) { // after all these URLs, I got nothing? How depressing.
  progress("<br />[ERROR] Comic Vineyard was unable to find a collection at any the submitted URLs.\n");
  exit; // FOR YOU, NOT ME. I AM TEH ALL POWERFUL SCRIPT PUNY HUMAN YOU ARE BAG OF MOSTLY WATER.
}

/* ************************************************************************** */
/* Step 3: from our collected volumes, load up information about the issues.  */
/* ************************************************************************** */
progress("<ul>\n"); // make it purdy.
foreach ($collection as $volume_id => $volume) {
  progress("<li><span class=\"message\">Fetching issue data for $volume[name]...</span> "); // start the heartbeat.
  $volume_issues = http_request("http://api.comicvine.com/volume/$volume_id/?api_key=$api_key&field_list=issues&format=json", "json");

  foreach ($volume_issues->results->issues as $volume_issue) {
    // the current Comic Vine API doesn't return the issue number in the
    // volume issues field list so we have to scan each one to find it.
    // we cache the values in a local file for faster reruns.
    if (!isset($issue_numbers[$volume_issue->id])) {
      $issue_details = http_request("http://api.comicvine.com/issue/" . $volume_issue->id . "/?api_key=$api_key&field_list=issue_number&format=json", "json");
      $issue_numbers[$volume_issue->id] = (int) $issue_details->results->issue_number; // just a simple lookup hash to convert ids to numbers. yawn.
      progress('<span class="heartbeat">.</span> '); // heartbeat for long-lived volumes with lots of issues.
      $new_cache_items = TRUE;
    }

    // if this issue is in our collection, fetch all.
    $issue_number = $issue_numbers[$volume_issue->id];
    if (isset($collection[$volume_id]['issue numbers'][$issue_number])) {
      progress('<span class="heartbeat">.</span> '); // heartbeat for long-lived collections with lots of issues.
      $issue_details = http_request("http://api.comicvine.com/issue/" . $volume_issue->id . "/?api_key=$api_key&field_list=$theme_fields&format=json", "json");
      $collection[$volume_id]['issue numbers'][$issue_number]['data'] = (array) $issue_details->results; // put the whole blasted thing in there.

      // massage some of the data to get it into a more "print and go" form.
      $collection[$volume_id]['issue numbers'][$issue_number]['data']['issue_number'] = (int) $collection[$volume_id]['issue numbers'][$issue_number]['data']['issue_number'];
    }
  }

  // see if there are any user-defined issues we couldn't find.
  foreach ($collection[$volume_id]['issue numbers'] as $issue_number => $issue) {
    if (!isset($issue['data'])) { // no matching issue number from API's volume call.
      progress("<br />[ERROR] Comic Vineyard was unable to find data for issue #$issue_number.\n");
      unset($collection[$volume_id]['issue numbers'][$issue_number]); // no theming of emptiness.
    }
  } progress("</li>\n");
} progress("</ul>");

if (isset($new_cache_items)) {
  cache_save($issue_numbers, 'issue-numbers.db');
}


/* ************************************************************************** */
/* Step 4: our collection is full of data so we can print it as we'd like.    */
/* ************************************************************************** */
$output   = theme_render($collection);
foreach ($list_urls as $list_url) {
  $user = preg_replace('!.*/myvine/(.*?)/.*!', '\1', $list_url);
  $render_id += preg_replace("!.*/myvine/$user/.*?/75-(\d+).*!", '\1', $list_url);
} // if someone renders ten lists at once, add the IDs together to create a uniq.
$rendered_path = "renders/$user-$render_id-default.html";
file_put_contents($rendered_path, $output);

progress("\n<p>Your Comic Vineyard is complete!</p>\n");
progress("  <ul><li><a href=\"$rendered_path\">$rendered_path</a></li></ul>\n");
progress_surroundings('footer');

/* ************************************************************************** */
/* FUNCTIONES SOMNICULOSUS. NOTHING WICKED THIS WAY COMES WEIRDO ... CALL ME? */
/* ************************************************************************** */
/**
 * Disable output buffering in Apache, PHP, and certain browsers.
 */
function disable_output_buffering() {
  @apache_setenv('no-gzip', 1);
  @ini_set('zlib.output_compression', 0);
  @ini_set('implicit_flush', 1);
  ob_end_flush(); ob_implicit_flush(1);

  // Safari and IE need a payload of a certain number of bytes to start.
  // Safari might need to see the rise and fall of an HTML element.
  print str_pad('', 1024); // print '<span class="buffering" />';
}

/**
 * Request a resource over HTTP and return it in various ways.
 *
 * @param $url
 *   The full URL to retrieve (GET parameters are OK).
 * @param $type
 *   Defaults to 'xml'; one of 'json', 'xml', or 'string'.
 *
 * @return
 *   Either a SimpleXML object, an array from JSON, or the raw string.
 */
function http_request($url, $type = 'xml') {
 $request = new HTTP_Request2($url, HTTP_Request2::METHOD_GET);
  try {
    $response = $request->send();
    if ($response->getStatus() == 200) {
      if ($type == 'xml') {
        $dom = new DOMDocument();
        @$dom->loadHTML($response->getBody());
        return simplexml_import_dom($dom);
      }
      elseif ($type == 'json') {
        return json_decode($response->getBody());
      }
      else {
        return $response->getBody();
      }
    }
    else {
      progress("<br />[ERROR] $url: " .
        $response->getStatus() . ' ' .
        $response->getReasonPhrase() . "\n");
      return NULL;
    }
  }
  catch (HTTP_Request2_Exception $e) {
    progress("<br />[ERROR] $url: " . $e->getMessage() . "\n");
    return NULL;
  }
}

/**
 * Loads a two-column CSV file into a key/values array.
 *
 * @param $path
 *   A relative or absolute path of a two column CSV file.
 *
 * @return
 *   An array of key values.
 */
function cache_load($path) {
  if (file_exists($path)) {
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) { // this is to speed reruns.
      list($key, $value) = explode(",", $line);
      $cache[$key] = $value;
    }
  }

  return $cache;
}

/**
 * Saves a key/values array to a CSV file.
 *
 * @param $data
 *   The array of key values. Note that the data is saved as is -
 *   no escaping or quoting or massaging occurs. It'll be easier
 *   from PHP 5.3 is everywhere and we get fputcsv(), etc.
 * @param $path
 *    A relative or absolute path to save the data to.
 */
function cache_save($data, $path) {
  $lines = NULL; // I SHALL SPIT YOU.
  foreach ($data as $key => $value) {
    $lines .= "$key,$value\n";
  }

  file_put_contents($path, $lines);
}

/**
 * Show any surrounding elements necessary for a progress report.
 *
 * This function is typically for when we're run through a browser.
 *
 * @param $type
 *   One of 'header' or 'footer'.
 * @return
 *   A string representing the type of HTML requested.
 */
function progress_surroundings($type) {
  // YES, AS A MATTER OF FACT, I DO HATE HEREDOCS.
  // I APPRECIATE YOUR ASKING. MINE MINE CONCATENATION!
  $header  = '<!DOCTYPE html>';
  $header .= '<html lang="en">';
  $header .= '<head>';
  $header .=   '<meta charset="utf-8" />';
  $header .=   '<title>My Comic Vineyard</title>';
  $header .=   '<link rel="stylesheet" href="misc/default.css" type="text/css" />';
  $header .= '</head>';
  $header .= '<body>';
  $header .= '<div id="header">';
  $header .=   '<a href="http://www.comicvine.com/"><img id="logo" src="http://media.comicvine.com/media/vine/img/white/layout/logo.png" /></a>';
  $header .=   '<div id="header-statistics">&nbsp;<!-- nothing to see here yet. maybe one day it\'ll include a list of statistics? -->&nbsp;</div>';
  $header .=   '<div id="header-explanation"><a href="http://www.disobey.com/d/code/comic-vineyard/">Comic Vineyard</a> allows you to track your comic book collection using <a href="http://www.comicvine.com/">Comic Vine</a>. <div style="float: right;">Comic Vineyard was created by <a href="http://www.comicvine.com/myvine/morbus/">Morbus Iff</a>.</div></div>';
  $header .= '</div>';
  $header .= '<div id="wrapper" class="clearfix progress">';
  $footer =  '</div>';
  $footer .= '</body>';
  $footer .= '</html>';

  return ($type == 'header') ? $header : $footer;
}

/**
 * Show that the script is "working" by displaying progress messages.
 *
 * This function strips HTML tags if it detects we're on the command line.
 *
 * @param $message
 *   The message you want displayed to the user.
 */
function progress($message) {
  print isset($_REQUEST['urls']) ? $message : rtrim(strip_tags($message), " ");
}

/**
 * Pass some data off to a theme include for rendering.
 *
 * @param $data
 *   The data to be passed to the theme for rendering.
 * @param $theme
 *   The name of the theme file to use for rendering.
 * @return
 *   A rendered string.
 */
function theme_render($data, $theme = 'default') {
  extract($data, EXTR_SKIP);
  ob_start(); // no printing, mmkay?
  include "./themes/$theme.php";
  $contents = ob_get_contents();
  ob_end_clean(); // ok, fine.
  return $contents;
}
