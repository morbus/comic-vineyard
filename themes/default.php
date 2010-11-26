<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>My Comic Vineyard</title>
  <style type="text/css">
    body { background: #ccc; font: 12px sans-serif; margin: 0; }
    #header { background: #458C40; height: 99px; }
    #header #logo { float: left; }
    #header-statistics { background: #000; height: 70px; }
    #header-explanation { color: #fff; font-size: 11px; font-weight: bold; margin: 9px; }
    #header-explanation a { color: #fff; }
    #collection { background: #fff; border-radius: 5px; clear: both; margin: auto; padding: 10px; width: 90%; }
    .volume { margin-bottom: 20px; }
    .volume-header { margin-bottom: 10px; }
    .volume-header h2 { background: #e5ede5; border-top-left-radius: 5px; border-top-right-radius: 5px; margin: 0; padding: 10px; }
    .volume-data { background: #f3f3f3; border-bottom-left-radius: 5px; border-bottom-right-radius: 5px; margin-top: 0; padding: 5px 10px; }
    .issue { float: left; font-weight: bold; margin: 2px; text-align: center; }
    .issue img { border: 1px solid #e5ede5; padding: 2px; }
    .issue a { color: #093; text-decoration: none; }
    .issue-publication { color: #666; font-size: 11px; font-weight: normal; }

    .clearfix:after { content: "."; display: block; height: 0; clear: both; visibility: hidden; }
    * html .clearfix { height: 1%; } *:first-child + html .clearfix { min-height: 1%; }
  </style>
</head>
<body>

<div id="header">
  <a href="http://www.comicvine.com/"><img id="logo" src="http://media.comicvine.com/media/vine/img/white/layout/logo.png" /></a>
  <div id="header-statistics">&nbsp;<!-- nothing to see here yet. maybe one day it'll include a list of statistics? -->&nbsp;</div>
  <div id="header-explanation"><a href="http://www.disobey.com/d/code/comic_vineyard/">Comic Vineyard</a> allows you to track your comic book collection using <a href="http://www.comicvine.com/">Comic Vine</a>. <div style="float: right;">Comic Vineyard was created by <a href="http://www.comicvine.com/myvine/morbus/">Morbus Iff</a>.</div></div>
</div>

<div id="collection" class="clearfix">

<?php
  foreach ($collection as $volume_id => $volume) {
    print '<div class="volume clearfix">';
    print   '<div class="volume-header">';
    print     '<h2>' . $volume['name'] . '</h2>';
    print     '<div class="volume-data">';
    print       'Location: ' . $volume['location'];
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
