<?php

define('RUM_BUFFER', 2);

class RumHttpClient
{	
	var $content;
	var $bin;
	var $rawheadears;

	function RumHttpClient($url, $bin=false)
	{
		$this->bin = $bin;
		$this->connect($this->getUrlObj($url));
	}

	function getUrlObj($str)
	{
		$url = new RumHttpUrl;
		$url->raw = $str;
		$url->url = '/';
		$str = strtolower($str);
		$len = strlen($str);
		if (strpos($str, '/', 8) > -1)
		{
			$len -= ($len - strpos($str, '/', 8));
			$url->url = substr($str, strpos($str, '/', 8));
		}
		if (strpos($str, 'https://') > -1)
		{
			$len -= 8;
			$url->host = substr($str, 8, $len);
		}
		else if (strpos($str, 'http://') > -1)
		{
			$len -= 7;
			$url->host = substr($str, 7, $len);
		}
		else
		{
			return $this->getUrlObj('http://'.$str);
		}
		return $url;
	}

	function connect($url)
	{
		$str = '';
		if ($this->bin)
		{
			$str .= "GET " . $url->raw . " HTTP/1.1\r\n";
		}
		else
		{
			$str .= "GET " . $url->url . " HTTP/1.1\r\n";
		}
		$str .= "Host: " . $url->host . "\r\n";
		$str .= "Connection: Close\r\n\r\n";
                $socket = socket_create(AF_INET, SOCK_STREAM, getprotobyname('TCP'));
                if(!@socket_connect($socket, $url->host, $url->port))
                {
			die('Couldnt connect socket');
                }

		// send request...
		$offset = 0;
		$len = strlen($str);
                while ($res = socket_write($socket, substr($str, $offset, RUM_BUFFER), RUM_BUFFER))
		{
			if ($res === false) break;
			$offset += $res;
		}
		if ($offset < $len)
		{
			die('Couldnt send full request');
		}


		// receive response
		$buffer = '';
		while($buf = socket_read($socket, RUM_BUFFER))
		{
			if ($buf === false) break;
			$buffer .= $buf;
		}

		if ($this->bin)
		{
			list($headers, $data) = explode("\r\n\r\n", $buffer);
			$this->content = $data;
			$this->rawheaders = $headers;
		}
		else
		{
			$this->content = $buffer;
		}
		socket_close($socket);
	}

	function getContent()
	{
		return $this->content;
	}
}

class RumHttpUrl
{
	var $host;
	var $port = 80;
	var $url;
	var $raw;
}

?>
