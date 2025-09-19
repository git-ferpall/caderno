<?php
function http_post_form($url, array $data, array $headers = []) {
  $baseHeaders = [
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
    'User-Agent: caderno/1.0'
  ];
  $headers = array_merge($baseHeaders, $headers);

  if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
      CURLOPT_POST           => true,
      CURLOPT_POSTFIELDS     => http_build_query($data),
      CURLOPT_HTTPHEADER     => $headers,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_HEADER         => true,
      CURLOPT_TIMEOUT        => 15,
      CURLOPT_CONNECTTIMEOUT => 5,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_MAXREDIRS      => 3,
    ]);
    $raw     = curl_exec($ch);
    $errno   = curl_errno($ch);
    $status  = curl_getinfo($ch, CURLINFO_HTTP_CODE) ?: 0;
    $hdrSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE) ?: 0;
    $body    = substr($raw ?: '', $hdrSize);
    curl_close($ch);
    return ['status' => ($errno ? 0 : $status), 'body' => ($errno ? '' : $body)];
  }

  // Fallback sem cURL
  $ctx = stream_context_create([
    'http' => [
      'method'        => 'POST',
      'header'        => implode("\r\n", $headers),
      'content'       => http_build_query($data),
      'timeout'       => 15,
      'ignore_errors' => true, // ler corpo mesmo com 4xx/5xx
    ]
  ]);
  $resp = @file_get_contents($url, false, $ctx);
  $status = 0;
  if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
    $status = (int)$m[1];
  }
  return ['status' => $status, 'body' => $resp ?: ''];
}
