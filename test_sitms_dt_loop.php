<?php
// sitms_probe_full.php
$BASE = 'https://sitms.ptsi.co.id';
$URL_DT = $BASE.'/admin/api/employees_list';
$URL_API = $BASE.'/admin/api/employees';

// === CONFIG dari kamu ===
$COOKIEFULL = '_ga_21GWG326YK=GS2.1.s1759204384$o1$g0$t1759204384$j60$l0$h0; _ga=GA1.1.1565227122.1759204384; csrf_sitms=8f3a08c167429ebdcc9d1caec924455b; itms_session_live=4bd9b0bc34539e6e8f6b8b6411e8140cac1b92b6';
$APIKEY     = 'V1HoPfqlsfBQyD8EuLOGLytcSaP2YZyM';
$UA         = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36';
$REFERER    = $BASE.'/admin/';
$ORIGIN     = $BASE;
$AL         = 'en-US,en;q=0.9,id;q=0.8';

function show($mode, $info, $hdr, $body, $start=null) {
  $ct = $info['content_type'] ?? '';
  echo "[$mode] CT=$ct".($start!==null?" start=$start":"")."\n";
  if (stripos($ct,'application/json')!==false) {
    $j = json_decode($body, true);
    if (is_array($j)) {
      $rows = $j['data'] ?? $j['items'] ?? $j['rows'] ?? [];
      $count = is_array($rows) ? count($rows) : 0;
      $total = $j['employeesCountAll'] ?? ($j['data']['employeesCountAll'] ?? ($j['recordsTotal'] ?? null));
      echo "      rows=$count total=".($total ?? 'null')."\n";
    } else {
      echo "      (JSON parse fail)\n";
    }
  }
}

function dt_get($start,$length,$draw,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL){
  $qs = http_build_query([
    'draw'=>$draw,'start'=>$start,'length'=>$length,
    'search[value]'=>'','search[regex]'=>'false',
  ]);
  $u = $URL_DT.'?'.$qs;
  $ch = curl_init();
  curl_setopt_array($ch,[
    CURLOPT_URL=>$u, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>60,
    CURLOPT_HTTPHEADER=>[
      'X-Requested-With: XMLHttpRequest',
      'Accept: application/json,text/javascript,*/*;q=0.01',
      'Accept-Language: '.$AL,
      'Referer: '.$REFERER,
      'Origin: '.$ORIGIN,
      'User-Agent: '.$UA,
      'Cookie: '.$COOKIEFULL,
    ],
  ]);
  $resp=curl_exec($ch); $info=curl_getinfo($ch); curl_close($ch);
  [$hdr,$body]=explode("\r\n\r\n",$resp,2);
  show('DT_GET ',$info,$hdr,$body,$start);
}

function dt_post($start,$length,$draw,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL){
  preg_match('~csrf_sitms=([^;]+)~',$COOKIEFULL,$m); $csrf=$m[1]??'';
  $payload=http_build_query([
    'csrf_sitms'=>$csrf,'draw'=>$draw,'start'=>$start,'length'=>$length,
    'search[value]'=>'','search[regex]'=>'false',
  ]);
  $ch=curl_init();
  curl_setopt_array($ch,[
    CURLOPT_URL=>$URL_DT, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>60,
    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload,
    CURLOPT_HTTPHEADER=>[
      'Content-Type: application/x-www-form-urlencoded; charset=UTF-8',
      'Accept: application/json,text/javascript,*/*;q=0.01',
      'Accept-Language: '.$AL,
      'X-Requested-With: XMLHttpRequest',
      'Referer: '.$REFERER,
      'Origin: '.$ORIGIN,
      'User-Agent: '.$UA,
      'Cookie: '.$COOKIEFULL,
    ],
  ]);
  $resp=curl_exec($ch); $info=curl_getinfo($ch); curl_close($ch);
  [$hdr,$body]=explode("\r\n\r\n",$resp,2);
  show('DT_POST',$info,$hdr,$body,$start);
}

function api_post($page,$perPage,$URL_API,$COOKIEFULL,$APIKEY,$UA,$REFERER,$ORIGIN,$AL){
  preg_match('~csrf_sitms=([^;]+)~',$COOKIEFULL,$m); $csrf=$m[1]??'';
  $json=json_encode(['apikey'=>$APIKEY,'page'=>$page,'per_page'=>$perPage],JSON_UNESCAPED_SLASHES);
  $ch=curl_init();
  curl_setopt_array($ch,[
    CURLOPT_URL=>$URL_API, CURLOPT_RETURNTRANSFER=>true, CURLOPT_HEADER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_TIMEOUT=>60,
    CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$json,
    CURLOPT_HTTPHEADER=>[
      'Content-Type: application/json',
      'Accept: application/json',
      'Accept-Language: '.$AL,
      'X-CSRF-TOKEN: '.$csrf,
      'X-Requested-With: XMLHttpRequest',
      'Referer: '.$REFERER,
      'Origin: '.$ORIGIN,
      'User-Agent: '.$UA,
      'Cookie: '.$COOKIEFULL,
    ],
  ]);
  $resp=curl_exec($ch); $info=curl_getinfo($ch); curl_close($ch);
  [$hdr,$body]=explode("\r\n\r\n",$resp,2);
  show('API_POST',$info,$hdr,$body,null);
}

echo "=== DT_GET ===\n";
dt_get(0,100,1,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_get(900,100,10,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_get(1000,100,11,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_get(1100,100,12,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);

echo "\n=== DT_POST ===\n";
dt_post(0,100,1,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_post(900,100,10,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_post(1000,100,11,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);
dt_post(1100,100,12,$URL_DT,$COOKIEFULL,$UA,$REFERER,$ORIGIN,$AL);

echo "\n=== API_POST (apikey) ===\n";
api_post(1,100,$URL_API,$COOKIEFULL,$APIKEY,$UA,$REFERER,$ORIGIN,$AL);
