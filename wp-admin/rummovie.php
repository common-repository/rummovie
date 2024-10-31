<?php

require_once('admin.php');

if ($user_level < 1)
{
	die('No rights for this');
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title><?php _e('IMDB Movie search'); ?></title>
<link rel="stylesheet" href="wp-admin.css" type="text/css" />
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<base target="_self"> 
<script language="javascript" type="text/javascript">
	var closeOnLoad = false;
	function Movie()
	{
		this.content;
		this.headline;
	}

	function Init()
	{
		if (closeOnLoad)
		{
			self.close();
		}
	}
</script>
</head>
<body onload="Init();">
	<form method="get">
	<div class="wrap">
		<h2><?php _e('Search for movie'); ?></h2>
		<div><input type="text" name="word" value="<?php print $_GET['word']; ?>" style="width: 95%;" /></div>
		<p class="submit"><input name="sub" id="sub" type="submit" value="<?php _e('Search'); ?>"/></p>
	</div>
	</form>
	<?php
		flush();
		if (isset($_GET['sub']))
		{
			$search = new imdb_search($_GET['word']);
			?>
			<div class="wrap">
				<h2><?php _e('IMDB Results'); ?></h2>
				<?php
				if (count($search->results) > 0)
				{
					$i = 0;
					print '<table width="95%" cellspacing="0" cellpadding="4">';
					foreach($search->results as $result)
					{
						?>
							<tr class="<? print ($i % 2 == 0) ? 'alternate' : ''; ?>">
								<td><? print $result->year; ?></td>
								<td><a href="rummovie.php?id=<? print $result->id; ?>"><? print $result->title; ?></a></td>
								<td align="right">
									<a style="border: none;" href="http://imdb.com/title/<?php print $result->id; ?>/" target="_blank"><img src="../wp-images/imdb.gif" alt="<?php _e('See movie at imdb.com, opens in new window'); ?>" border="0"/></a>

								</td>
							</tr>
						<?
						$i++;
					}
					print '</table>';
				}
				else
				{
					_e('No results found for...');
				}
				?>
			</div>
			<?php
		}
		else if (isset($_GET['id']))
		{
			$movie = new imdb_movie($_GET['id']);
			/*
			print '<pre>';
			var_dump($movie);
			print '</pre>';
			*/
			rum_movie_picture($movie);
			?>
				<script language="javascript" type="text/javascript">
					var m = new Movie();
					m.content = '<? print rum_movie_escape(rum_movie_pattern($movie)); ?>';
					m.headline = '<? print rum_movie_escape($movie->title); ?>';
					if (typeof(window.opener) != 'undefined')
					{
						window.opener.document.getElementById('title').value = m.headline;
						if (typeof(window.opener.tinyMCE) != 'object')
						{
							window.opener.document.getElementById('content').value = m.content;
						}
						else
						{
							window.opener.tinyMCE.execCommand("mceInsertContent", true, m.content);
						}
						closeOnLoad = true;
					}
					else if (typeof(window.showModelDialog))
					{
						window.returnValue = m;
						closeOnLoad = true;
					}
				</script>
			<?
		}
	?>
</body>
</html>
