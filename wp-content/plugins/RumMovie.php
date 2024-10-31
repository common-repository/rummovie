<?php
/*
Plugin Name: RumMovie
Plugin URI: http://www.rummanddan.dk/plugins/
Description: Post IMDB fetch integration... 
Version: 0.05
Author: Dan Thrue
Author URI: http://www.rummanddan.dk
*/ 

require_once(ABSPATH.'wp-content/plugins/RumLib/imdb.php');
require_once(ABSPATH.'wp-content/plugins/RumLib/httpclient.php');

$RUM_MOVIE = '<!-- IMDB #ID# --><img src="#PICTURE#" align="right" alt="#TITLE# (#YEAR#)" style="margin: 4px; padding: 3px; border: 1px solid black;" />

<p>#PLOT#</p>

<p><b>Directed by</b><br/> #DIRECTORS#</p>

<p><b>Genres </b><br/> #GENRES#</p>

<p><b>Cast</b><br/>#ACTORS#</p>
';

$RUM_ACTOR = '#NAME#';
$RUM_GENRE_JOIN = ', ';
$RUM_DIRECTOR_JOIN = ', ';
$RUM_ACTOR_JOIN = ', ';
$RUM_DIRECTOR = '#NAME#';

$RUM_LOCAL_PICTURES = is_writeable(ABSPATH.'wp-content/rummovie/');
$SCRIPT_NAME = $_SERVER['SCRIPT_NAME'];
if (isset($_SERVER['SCRIPT_URL']))
{
	$SCRIPT_NAME = $_SERVER['SCRIPT_URL'];
}

function rum_movie_footer()
{
	if (basename($GLOBALS['SCRIPT_NAME']) == 'post.php')
	{
?>
<script language="javascript" type="text/javascript">

	function HandleMovie(movie)
	{
		document.getElementById('title').value = movie.headline;
		// check for rich editor
		if (typeof(tinyMCE) != 'object')
		{
			document.getElementById('content').value = movie.content;
		}
		else
		{
			tinyMCE.execCommand("mceInsertContent", true, movie.content);
		}
	}

	function OpenMovieSearch()
	{
		if (typeof(window.showModalDialog) != 'undefined')
		{
			var result = window.showModalDialog("rummovie.php", window, "resizable:no;scroll:yes;status:yes;center:yes;help:no;dialogWidth:500px;dialogHeight:500px;");
			HandleMovie(result);
		}
		else
		{
			var win = window.open("rummovie.php", "RumPop", "scrollbars=yes,dialog=yes,minimizable=no,modal=yes,width=400,height=500,resizable=no");
		}
	}
	
	// check for rich editor
	if (typeof(tinyMCE) != 'object')
	{
		// load the oldschool way...
		var obj;
		if (obj = document.getElementById('ed_toolbar'))
		{
			obj.innerHTML += '<input type="button" class="ed_button" value="IMDB" onclick="OpenMovieSearch();"/>';
		}
	}
	else
	{
		// load into rich editor toolbar..
	}
	

</script>
<?php
	}
}

function rum_movie_install()
{
	$path = ABSPATH.'wp-content/';
	if (is_writable($path))
	{
		$path .= 'rummovie';
		if (!is_dir($path))
		{
			if (mkdir($path, 0777))
			{
				return;
			}
		}
	}
}

function rum_movie_pattern($movie)
{
	// do actors...
	$actors = array();
	foreach ($movie->actors as $actor)
	{
		$actors[] = str_replace(array('#NAME#', '#ID#', '#ROLE#'), array($actor->name, $actor->id, $actor->role), $GLOBALS['RUM_ACTOR']);
	}
	$actors = implode($GLOBALS['RUM_ACTOR_JOIN'], $actors);

	// do directors...
	$directors = array();
	foreach ($movie->directors as $director)
	{
		if (isset($GLOBALS['RUM_DIRECTOR']))
		{
			$directors[] = str_replace(array('#NAME#', '#ID#'), array($director->name, $director->id), $GLOBALS['RUM_DIRECTOR']);
		}
		else
		{
			$directors[] = $director->name;
		}
	}
	$directors = implode($GLOBALS['RUM_DIRECTOR_JOIN'], $directors);
	$str = str_replace(
		array('#ID#', '#TITLE#', '#YEAR#', '#PLOT#', '#VOTES#', '#RATING#', '#PICTURE#', '#TAGLINE#', '#GENRES#', '#ACTORS#', '#DIRECTORS#'),
		array($movie->id, $movie->title, $movie->year, $movie->plot, $movie->votes, $movie->rating, $movie->picture, $movie->tagline, implode($GLOBALS['RUM_GENRE_JOIN'], $movie->genres), $actors, $directors),
		$GLOBALS['RUM_MOVIE']
	);
	return $str;
}

function rum_movie_picture(&$movie)
{
	if ($GLOBALS['RUM_LOCAL_PICTURES'])
	{
		if (strlen($movie->picture) > 0)
		{
			$ext = substr($movie->picture, strrpos($movie->picture, '.'));
			$path = 'wp-content/rummovie/' . $movie->id . $ext;
			$target = ABSPATH . $path;
			$data = '';
			if (RUM_HTTPCLIENT || !ini_get('allow_url_fopen'))
			{
				print "BLAH";
				$client = new RumHttpClient($movie->picture, true);
				$data = $client->getContent();
			}
			else
			{
				$data = file_get_contents($movie->picture);
			}
			if ($fp = fopen($target, 'wb'))
			{
				if (fwrite($fp, $data) === false)
				{
					die('Couldnt write picture file, check permissions for ' . $target);
				}
				fclose($fp);
				chmod($target, 0777);
				$movie->picture = get_option('siteurl') . '/' . $path;
			}
			else
			{
				die('Couldnt open file for binary writing, check permissions ' . $target);
			}
		}
	}
	else
	{
		die('Local thumbnail directory isnt writable: "'.ABSPATH.'wp-content/rummovie"');
	}
}

// Can reuse this to both button and plugins attachment..
function rum_movie_richattach($arr)
{
	$arr[] = 'rummovie';
	return $arr;
}


function rum_movie_escape($str)
{
	return str_replace(array("\n", "'"), array('\n',"\\'"), $str);
}

add_action('admin_footer', 'rum_movie_footer');
add_filter('mce_plugins', 'rum_movie_richattach');
add_filter('mce_buttons', 'rum_movie_richattach');

if (basename($SCRIPT_NAME) == 'plugins.php' && isset($_GET['activate']))
{
	rum_movie_install();
}

?>
