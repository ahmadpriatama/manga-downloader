<?php
ini_set('max_execution_time', 0);

require "simple_html_dom.php";

$animes = array('naruto', 'bleach', 'one-piece'); 
$anime_keys = array(93, 94, 103); 
$useproxy = false;

foreach($animes as $keys => $anime) {

	$curl = curl_init();
	curl_setopt($curl, CURLOPT_URL, "http://www.mangareader.net/".$anime_keys[$keys]."/".$anime.".html");
	if ($useproxy) curl_setopt($curl, CURLOPT_PROXY, "localhost:8888");
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
	$raw = curl_exec($curl);

	$html = str_get_html($raw);
	$arr = $html->find('#latestchapters li');
	$url = $arr[0]->children(1)->href;

	$atom = explode('/', $url);
	$volume = $atom[2];

	curl_setopt($curl, CURLOPT_URL, "http://www.mangareader.net$url");
	$raw = curl_exec($curl);

	$html->clear();
	$html = str_get_html($raw);
	$arr = $html->find('#selectpage option');

	$mh = curl_multi_init();
	$ch = array();

	foreach($arr as $key => $item) {
		$ch[$key] = curl_init("http://www.mangareader.net" . $item->value);
		curl_setopt($ch[$key], CURLOPT_RETURNTRANSFER, 1);
		if ($useproxy) curl_setopt($ch[$key], CURLOPT_PROXY, "localhost:8888");
		curl_multi_add_handle($mh,$ch[$key]);
	}

	$running=null;
	do {
		curl_multi_exec($mh,$running);
	} while($running > 0);

	curl_multi_close($mh);
	$mh = curl_multi_init();
	$ch2 = array();

	if (!is_dir("manga/$anime/$volume/")) {
		mkdir("manga/$anime/");
		mkdir("manga/$anime/$volume");
	}
	
	copy('reader.php', "manga/$anime/$volume/.reader.php");
	
	foreach ($ch as $key => $item) {
		$raw = curl_multi_getcontent($item);
		$html->clear();
		$html = str_get_html($raw);
		$img = $html->find('#img',0);
		
		$ch2[$key] = curl_init($img->src);
		$fh = fopen("manga/$anime/$volume/".str_pad($key, 2, "0", STR_PAD_LEFT).".jpg", "w"); 
		curl_setopt($ch2[$key], CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch2[$key], CURLOPT_FILE, $fh); 
		if ($useproxy) curl_setopt($ch2[$key], CURLOPT_PROXY, "localhost:8888");
		curl_multi_add_handle($mh,$ch2[$key]);
	}

	$running=null;
	do {
		curl_multi_exec($mh,$running);
	} while($running > 0);
	
	foreach($ch2 as $key => $value) {
		curl_close($ch2[$key]);
	}
	curl_multi_close($mh);
}
?>