<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Comic Vineyard</title>
  <link rel="stylesheet" href="../misc/default.css" type="text/css" />
</head>
<body>

<div id="header">
  <a href="http://www.comicvine.com/"><img id="logo" src="http://media.comicvine.com/media/vine/img/white/layout/logo.png" /></a>
  <div id="header-statistics">&nbsp;<!-- nothing to see here yet. maybe one day it'll include a list of statistics? -->&nbsp;</div>
  <div id="header-explanation"><a href="http://www.disobey.com/d/code/comic-vineyard/">Comic Vineyard</a> allows you to track your comic book collection using <a href="http://www.comicvine.com/">Comic Vine</a>. <div style="float: right;">Comic Vineyard was created by <a href="http://www.comicvine.com/myvine/morbus/">Morbus Iff</a>.</div></div>
</div>

<div id="wrapper" class="clearfix collection">

<?php
  foreach ($data as $volume_id => $volume) {
    print '<div class="volume clearfix">';
    print   '<div class="volume-header">';
    print     '<h2>' . $volume['name'] . '</h2>';
    print     '<div class="volume-data">';
    print     isset($volume['location']) ? 'Location: ' . $volume['location'] : '';
    print     '</div>';
    print    '</div>';

    krsort($volume['issue numbers']); // new first.
    foreach ($volume['issue numbers'] as $issue) { // we'll try to cheaply transform the individual publication entries into a Comic Vine-like date string.
      $published = $issue['data']['publish_year'] . '-' . $issue['data']['publish_month'] . (isset($issue['data']['publish_day']) ? '-' . $issue['data']['publish_day'] : '');

      print   '<div class="issue">';
      print     '<a href="'. $issue['data']['site_detail_url'] .' "><img src="' . $issue['data']['image']->thumb_url . '" /></a>';
      print     '<div class="issue-number"><a href="'. $issue['data']['site_detail_url'] .' ">Issue #' . $issue['data']['issue_number'] . '</a></div>'; 
      print     '<div class="issue-publication">' . date('M. j, Y', strtotime($published)) . '</div>'; 
      print   '</div>';
    }
    print "</div>";
  }
?>

</div>

</body>
</html>
