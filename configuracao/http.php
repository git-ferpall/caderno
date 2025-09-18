<?php
function http_post_form($url, array $data, array $headers = []) {
  $opts = ['http' => [
    'method'  => 'POST',
    'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                 implode("\r\n", $headers),
    'content' => http_build_query($data),
    'timeout' => 10,
  ]];
  return file_get_contents($url, false, stream_context_create($opts));
}
