<?php

define('RUM_IMDB_DOMAIN', 'us.imdb.com');
define('RUM_IMDB_SEARCH_URL', 'http://'.RUM_IMDB_DOMAIN.'/find?q=#QUERY#;tt=on;mx=20');
define('RUM_IMDB_TITLE_URL', 'http://'.RUM_IMDB_DOMAIN.'/title/#QUERY#/');
define('RUM_IMDB_SEARCH_RESULTS', 10);
// define('RUM_IMDB_SEARCH_URL', 'http://'.RUM_IMDB_DOMAIN.'/List?words=#QUERY#');
define('RUM_SUCCESS', 0);
define('RUM_E_CONFAILED', -1);
define('RUM_HTTPCLIENT', 0);

class imdb
{
	function getContent($keyword, &$error)
	{
		$content = false;
		if (!ini_get('allow_url_fopen') || RUM_HTTPCLIENT)
		{
			if (class_exists('RumHttpClient'))
			{
				$client = new RumHttpClient($this->getUrl($keyword));
				$content = $client->getContent();
				if ($content === false)
				{
					die('Couldnt get page "'.$this->getUrl($keyword).'" through RumHttpClient');
				}
			}
			else
			{
				die('Couldnt find class RumHttpClient');
			}
		}
		else
		{
			$content = @file_get_contents($this->getUrl($keyword));
		}

		if ($content !== false)
		{
			$error = RUM_SUCCESS;
			return $content;
		}
		else
		{
			$error = RUM_E_CONFAILED;
			return '';
		}
	}

	function getUrl($keyword)
	{
		return str_replace('#QUERY#', urlencode($keyword), $this->url);
	}
}

class imdb_movie extends imdb
{
	var $url = RUM_IMDB_TITLE_URL;
	
	var $id;
	var $title;
	var $year;
	var $picture;
	var $directors = array();
	var $genres = array();
	var $tagline;
	var $plot;
	var $rating;
	var $votes;
	var $actors = array();

	function imdb_movie($id)
	{
		$content = $this->getContent($id, $error);
		if ($error == RUM_SUCCESS)
		{
			$this->id = $id;
			$this->_parse($content);
		}
		else
		{
			die('Couldnt connect to imdb search webserver');
		}

	}

	function _parse($content)
	{
		// get title and year
		$ptn = "/<title>(.*?) \(([0-9]{2,4})/is";
		if (preg_match($ptn, $content, $match))
		{
			$this->title = $match[1];
			$this->year = $match[2];
			
			// find cover...
			$ptn =  "/alt=\"cover\" src=\"(.*?)\"/is";
			if (preg_match($ptn, $content, $match))
			{
				$this->picture = $match[1];
			}

			// find directors
			$ptn = "/<b class=\"blackcatheader\">Directed by<\/b><br>\n(.*?)\n<br>/is";
			if (preg_match($ptn, $content, $match))
			{
				$ptn = "/<a href=\"\/name\/(.*?)\/\">(.*?)<\/a>/is";
				if (preg_match_all($ptn, $match[1], $matches, PREG_SET_ORDER))
				{
					foreach ($matches as $match)
					{
						$name = new imdb_name;
						$name->id = $match[1];
						$name->name = $match[2];
						$this->directors[] = $name;
					}
				}
			}

			// find genres
			$ptn = "/<b class=\"ch\">Genre:<\/b>\n(.*?)\n/is";
			if (preg_match($ptn, $content, $match))
			{
				$ptn = "/<a href=\"\/Sections\/Genres\/(.*?)\/\">(.*?)<\/a>/is";
				if (preg_match_all($ptn, $match[1], $matches, PREG_SET_ORDER))
				{
					foreach ($matches as $match)
					{
						$this->genres[] = $match[1];
					}
				}
			}

			// find tagline
			$ptn = "/<b class=\"ch\">Tagline:<\/b> (.*?)[<|\n]/i";
			if (preg_match($ptn, $content, $match))
			{
				$this->tagline = $match[1];
			}

			// find plot
			$ptn = "/<b class=\"ch\">Plot Outline:<\/b> (.*?)[<|\n]/i";
			if (preg_match($ptn, $content, $match))
			{
				$this->plot = $match[1];
			}

			// find rating and votes
			$ptn = "/<b>([0-9\.]+?)\/10<\/b> \(([0-9,]+?) votes\)/is";
			if (preg_match($ptn, $content, $match))
			{
				$this->rating = $match[1];
				$this->votes = $match[2];
			}

			// find actors
			$ptn = "/<tr><td valign=\"top\"><a href=\"\/name\/(.*?)\/\">(.*?)<\/a><\/td><td valign=\"top\" nowrap=\"1\"> \.\.\.\. <\/td><td valign=\"top\">(.*?)<\/td><\/tr>/is";
			if (preg_match_all($ptn, $content, $matches, PREG_SET_ORDER))
			{

				foreach ($matches as $match)
				{
					$name = new imdb_name;
					$name->id = $match[1];
					$name->name = $match[2];
					$name->role = $match[3];
					$this->actors[] = $name;
				}
			}
		}
	}

}

class imdb_search extends imdb
{
	var $results = array();
	var $url = RUM_IMDB_SEARCH_URL;

	function imdb_search($keyword)
	{
		$content = $this->getContent($keyword, $error);
		if ($error == RUM_SUCCESS)
		{
			$this->_parse($content);
		}
		else
		{
			die('Couldnt connect to imdb search webserver');
		}
	}

	function _parse($content)
	{
		$this->_clearResults();
		// $ptn = '/<title>IMDb title search for (.*?)<\/title>/i';
		$ptn = '/<title>IMDb Title/i';
		if (preg_match($ptn, $content, $match))
		{
			$ptn = "/<ol>(.*?)<\/ol>/is";
			$count = 0;
			if (preg_match_all($ptn, $content, $matches, PREG_SET_ORDER))
			{	
				$ptn = "/<a href=\"\/title\/(.*?)\/\" onclick=\".*?[^\"]\">(.*?[^<])<\/a> \(([0-9]+)/is";
				foreach ($matches as $match)
				{
					if (preg_match_all($ptn, $match[1], $movies, PREG_SET_ORDER))
					{
						foreach ($movies as $movie)
						{
							$result = new imdb_search_result;
							$result->id = $movie[1];
							$result->title = $movie[2];
							$result->year = $movie[3];
							$this->results[] = $result;
							$count++;
							if ($count >= RUM_IMDB_SEARCH_RESULTS)
							{
								return;
							}
						}
					}
				}
			}
		}
		else
		{
			$ptn = "/<a href=\"\/rg\/title-tease\/rating-vote\/title\/(.*?)\/ratings\">/";
			if (preg_match($ptn, $content, $match))
			{
				// get title and year
				$result = new imdb_search_result;
				$result->id = $match[1];
				$ptn = "/<title>(.*?) \(([0-9]{2,4})/is";
				if (preg_match($ptn, $content, $match))
				{
					$result->title = $match[1];
					$result->year = $match[2];
					$this->results[] = $result;
				}
			}
		}
	}

	function _clearResults()
	{
		$this->results = array();
	}
}

class imdb_search_result
{
	var $title;
	var $year;
	var $id;
}

class imdb_name
{
	var $id;
	var $name;
	var $role;
}

/*
print '<pre>';
$search = new imdb_search('bugs life');
$search = new imdb_search('gladiator');
var_dump($search->results);

$movie = new imdb_movie('tt0120623');
var_dump($movie->directors);
var_dump($movie->genres);
var_dump($movie->actors);

print '</pre>';
*/

?>
