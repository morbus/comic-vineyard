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

$api_key  = isset($_ENV['COMIC_VINE_API_KEY']) ? $_ENV['COMIC_VINE_API_KEY'] : $_SERVER['argv'][1];
$list_url = isset($_REQUEST['list_url']) ? $_REQUEST['list_url'] : $_SERVER['argv'][2];
$theme    = isset($_REQUEST['theme']) ? $_REQUEST['theme'] : $_SERVER['argv'][3];
$theme    = isset($theme) ? $theme : 'default'; // theme is kludgey at the moment.
$theme_fields = 'issue_number,publish_year,publish_month,publish_day,image,site_detail_url';
$user = preg_replace('!.*/myvine/(.*?)/.*!', '\1', $list_url);
if (isset($_REQUEST['list_url'])) {
  header('Content-type: text/plain');
}

$issues_cache = issues_cache_load();

/* ************************************************************************** */
/* Step 1: get our collection of volumes, and parse the comments for issues.  */
/* ************************************************************************** */
$list_data = http_request($list_url);
foreach ($list_data->xpath("//tr") as $row) {
  $volume_id = preg_replace("!/.*?/\d*-(\d*)/!", "$1", $row->td[1]->a["href"]);
  $collection[$volume_id]['name'] = preg_replace("!^\d+\. (.*?)!", "$1", $row->td[1]->a);
  $collection[$volume_id]['unparsed'] = (string) $row->td[1]->p;

  // try to parse out the comments into something logical, in
  // the form of "1,2,4-10; location: box 1" (; and : delimiters).
  $comment_parts = explode(";", $collection[$volume_id]['unparsed']);
  foreach ($comment_parts as $comment_part) {
    $key_values = explode(":", $comment_part);

    // if we have an unlabeled comment part, we treat it as
    // a list of issue numbers which must be further parsed.
    if (isset($key_values[0]) && !isset($key_values[1])) {
      $issue_number_parts = explode(",", $key_values[0]);
      foreach ($issue_number_parts as $issue_number_part) {

        // if it's a range, find the min/max and fill.
        if (strpos($issue_number_part, '-') !== FALSE) {
          preg_match("/(\d+)\s*-\s*(\d+)/", $issue_number_part, $matches);
          for ($matches[1]; $matches[1] <= $matches[2]; $matches[1]++) {
            $collection[$volume_id]['issue numbers'][$matches[1]]['count']++;
          }
        }
        else { // just a single number so fill it.
          $collection[$volume_id]['issue numbers'][trim($issue_number_part)]['count'] += 1;
        }
      }
    }
    // otherwise, it's a normal set of key:value pairs.
    elseif (isset($key_values[0]) && isset($key_values[1])) {
      $collection[$volume_id][trim($key_values[0])] = trim($key_values[1]);
    }
  }
}

/* ************************************************************************** */
/* Step 2: from our collected volumes, load up information about the issues.  */
/* ************************************************************************** */
foreach ($collection as $volume_id => $volume) {
  print "Fetching data for $volume[name]..."; // start the heartbeat.
  $volume_issues = http_request("http://api.comicvine.com/volume/$volume_id/?api_key=$api_key&field_list=issues&format=json", "json");

  foreach ($volume_issues->results->issues as $volume_issue) {
    // the current Comic Vine API doesn't return the issue number in the
    // volume issues field list so we have to scan each one to find it.
    // we cache the values in a local file for faster reruns.
    if (!isset($issues_cache[$volume_issue->id])) {
      $issue_details = http_request("http://api.comicvine.com/issue/" . $volume_issue->id . "/?api_key=$api_key&field_list=issue_number&format=json", "json");
      $issues_cache[$volume_issue->id] = (int) $issue_details->results->issue_number; // just a simple lookup hash to convert ids to numbers. yawn.
      print "."; ob_flush(); flush(); // heartbeat for long-lived volumes with lots of issues.
      $new_cache_items = TRUE;
    }

    // if this issue is in our collection, fetch all.
    $issue_number = $issues_cache[$volume_issue->id];
    if (isset($collection[$volume_id]['issue numbers'][$issue_number])) {
      print "."; ob_flush(); flush(); // heartbeat for long-lived collections with lots of issues.
      $issue_details = http_request("http://api.comicvine.com/issue/" . $volume_issue->id . "/?api_key=$api_key&field_list=$theme_fields&format=json", "json");
      $collection[$volume_id]['issue numbers'][$issue_number]['data'] = (array) $issue_details->results; // put the whole blasted thing in there.

      // massage some of the data to get it into a more "print and go" form.
      $collection[$volume_id]['issue numbers'][$issue_number]['data']['issue_number'] = (int) $collection[$volume_id]['issue numbers'][$issue_number]['data']['issue_number'];
    }
  }
  print "\n";
}

if ($new_cache_items) {
  issues_cache_save($issues_cache);
}

/* ************************************************************************** */
/* Step 3: our collection is full of data so we can print it as we'd like.    */
/* ************************************************************************** */
$output = theme_render($collection, $theme);
file_put_contents("renders/$user-$theme.html", $output);

/* ************************************************************************** */
/* Addendum: boring helper monkey functions. NOTHING WICKED THIS WAY COMES.   */
/* ************************************************************************** */
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
      print "Error [$url]: " . $response->getStatus() . ' ' . $response->getReasonPhrase() . "\n";
      return NULL;
    }
  }
  catch (HTTP_Request2_Exception $e) {
    print "Error [$url]: " . $e->getMessage() . "\n";
    return NULL;
  }
}

function issues_cache_load($filename = 'issues_cache.db') {
  if (file_exists($filename)) {
    $lines = file($filename, FILE_IGNORE_NEW_LINES);
    foreach ($lines as $line) { // this is to speed reruns.
      list($issue_id, $issue_number) = explode(",", $line);
      $issues_cache[$issue_id] = $issue_number;
    }
  }

  return $issues_cache;
}

function issues_cache_save($issues_cache, $filename = 'issues_cache.db') {
  foreach ($issues_cache as $issue_id => $issue_number) {
    $lines .= "$issue_id,$issue_number\n";
  }

  file_put_contents($filename, $lines);
}

function theme_render($collection, $name = 'default') {
  extract($collection, EXTR_SKIP);
  ob_start(); // no printing, mmkay?
  include "./themes/$name.php";
  $contents = ob_get_contents();
  ob_end_clean(); // ok, fine.
  return $contents;
}