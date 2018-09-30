
<?php

date_default_timezone_set("Asia/Shanghai");

/*
$num = 8;
echo sprintf("%03d", $num);
*/
function period_format($num) {
	return sprintf("%03d", $num);
}

$lottery_time = array();
$lottery_time[0] = 0;
for ( $i = 1; $i <= 23; $i++ ){
	$lottery_time[$i] = 5*$i;
}
for ( $i = 24; $i <=96; $i++ ){
	$lottery_time[$i] = 10*60+($i-24)*10;
}
for ( $i = 97; $i <=120; $i++ ){
	$lottery_time[$i] = 22*60+($i-96)*5;
}

$date = date("ymd");

$hour = (int)date("H");
$minute = (int)date("i");

if ($hour>=2 and $hour<10){
	exit();
}

$num = $hour*60 + $minute;

/*
notice num == 0 
*/
$period;
if ( $num == 0 ){
	$date = date("ymd", strtotime("yesterday"));
	$period = "120";
} else {
	for ( $i = 1; $i < count($lottery_time); $i++ ){
		if ( $lottery_time[$i] < $num && $lottery_time[$i+1] > $num ){
			$period = period_format($i);
			break;
		}
	}	
};

global $m;
global $db;

try
{
    $m = new Mongo("mongodb://127.0.0.1:27018"); // connect
    // $m = new Mongo("mongodb://211.151.209.101:27018");
    $db = $m->selectDB("zy_base");
}
catch ( MongoConnectionException $e )
{	
    $result = array( "result"=>-1, "desc"=>"database error." );
    exit();
}

$lottery = $db->zy_lottery;
//whether query exists
$query = array("date"=>$date, "period"=>$date.$period);
$cursor = $lottery->find($query);
foreach ($cursor as $doc) {
	if(isset($doc)){
		exit();
 	} 
}

$request_url = "http://caipiao.163.com/award/getAwardNumberInfo.html?gameEn=ssc&period=".$date.$period;
$item = file_get_contents( "compress.zlib://".$request_url );
$arr = json_decode( $item, true );

if ( $arr['status'] == 0 ){	
	$lottery_num = $arr["awardNumberInfoList"][0]["winningNumber"];
}

if (!isset($lottery_num)) {
	exit();
}


//insert
$insert = array( "date"=>$date, "period"=>$date.$period, "lottery"=>str_replace(' ','',$lottery_num) );

$cursor = $lottery->insert($insert);
//1 success, 0 fail
?>
