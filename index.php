<?php
$dbconn = pg_connect("host=localhost dbname=tweetlead user=postgres password=Fishface93");
//  or die('Could not connect: ' . pg_last_error());


// Function which will automatically do the queries
// dbconn: the connection (global variables are not visible in functions)
// query: string, psql query
function query($query){
  global $dbconn;
  $result = pg_query($dbconn, $query)
    or die('Query execution failed: ' . $query);
  return $result;
} 


// Function which returns a single result from a query
// query : string, psql query
function getSingle($query){
  global $dbconn;
  $result = query($query);
  $row = pg_fetch_row($result);
  return $row[0];
} 


// Function which returns uid
function getUid(){
  $ip = $_SERVER['REMOTE_ADDR'];
  $uid = getSingle("select uid from users where ip = '" . $ip . "';");

  // If the user does not exist, insert into table users the user
  if(!$uid){
    query($dbconn, "insert into users(ip) values ('" . $ip . "');");
  } 
  $uid = getSingle("select uid from users where ip = '" . $ip . "';");
  return $uid;
}


// Function to output tweets
// tweets: array of tweets
function printTweets($tweets){
  global $dbconn;
  $user = getUid();
  print "<table border=1>";
  foreach($tweets as $row){
    $uid = $row['uid'];
    $post = $row['post'];
    $date = $row['date'];

    // If a certain user has already followed another one
    if (!getSingle("select follower from follows where uid=$user and follower=$uid"))
    $follow = <<<EOF
    <a href=index.php?follow=$uid>Follow</a>
EOF;
    else {
      $follow = "<a href=index.php?unfollow=$uid>Unfollow</a>";
    }
    print <<<EOF
  <tr><TD>$uid</td><td>$post</td><td>$date</td><td>$follow</td></tr>
EOF;
  }
  print "</table>";
}


// Handle FOLLOW requests
if($_REQUEST['follow']){
  $follow = $_REQUEST['follow'];
  $uid = getUid();
  // Insert pair (uid, follower) into table follows
  query("insert into follows(uid, follower) values (". $uid . "," . $follow . ");");
}


// Handle UNFOLLOW request
if($_REQUEST['unfollow']){
  $unfollow = $_REQUEST['unfollow'];
  $uid = getUid();
  // Delete pair (uid, follower) from table follow
  query("delete from follows where uid = $uid and follower = '$unfollow';");
}


// Handle TWEET request
if($_REQUEST['tweet']){
  $tweet = $_REQUEST['tweet'];
  $ip = $_SERVER['REMOTE_ADDR'];
  $uid = getSingle("select uid from users where ip = '" . $ip . "';");

  // If the user does not exist, insert into table users the user
  if(!$uid){
    query("insert into users(ip) values ('" . $ip . "');");
  } 

  // Insert the tweet into the tweets table
  $date = Date("Y-m-d H:i:s");
  query("insert into tweets(uid, post, date)
         values(" . $uid . ", '" . $tweet . "', '" . $date . "');");
} 


// Print the whole table
print <<<EOF

<html><head>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
<script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>

<style>
body{
  padding:20px;
}
</style>
<title>TweetDemo</title>
</head>
<body>

<h2>Twitter Prototype Demo</h2>

<form action=index.php method=post>
<textarea name=tweet></textarea>
<input type=submit value="Tweet">
</form>

EOF;

print "<h4>Latest Tweets</h4>";
$tweets = array();
$result = query("select * from tweets order by date desc;");
while($row = pg_fetch_assoc($result)){
  $tweets[] = $row;
}
printTweets($tweets);


print "<br>";

// Print only the tweets for the followed users
print "<h4>Followed users</h4>";
$user = getUid();
$tweets = array();
$result = query("select * from tweets where uid in 
                 (select follower from follows where uid = $user)
                 order by date desc limit 100;");
while($row = pg_fetch_assoc($result)){
  $tweets[] = $row;
}
printTweets($tweets);


/* Comments for the whole program:
 * 1. print <<<EOF 
 *     some text/html
 *     EOF;
 *  This is a way to incorporate html into
 *  a PHP file.
 * 2.  */
?>
