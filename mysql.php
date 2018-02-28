<?php 
header("content-type:text/html;charset=utf-8"); 
$dbhost='176.122.139.131:3306';
$dbuser='root';
$dbpass='a3492585';
$conn=mysqli_connect($dbhost,$dbuser,$dbpass);
if(!$conn){
	die('数据库连接失败'.mysqli_error());
}
mysqli_query($conn,'SET NAMES UTF8');
mysqli_select_db($conn,'oc');
mysqli_query($conn,"SET AUTOCOMMIT=0");
//查询测试
// $sql="select * from oc_product order by product_id";
// $result=mysqli_query($conn,$sql);
// if(!$result){
// 	die("无法读取数据:".mysqli_error($conn));
// }
// echo '<table>';
// while($row=mysqli_fetch_array($result,MYSQL_ASSOC)){
// 	echo "<tr>
//           <td>{$row['product_id']}</td>
//           <td>{$row['model']}</td>
// 	</tr>";
// }
// echo '</table>';

/**
 * 插入sql语句，通过事务操作，避免部分语句插入成功，部分插入失败             
 * @param  [type] $sql [description]
 * @return [type]      [description]
 */
function insertsql($sql){
	$dbhost='176.122.139.131:3306';
    $dbuser='root';
    $dbpass='a3492585';
    $conn=mysqli_connect($dbhost,$dbuser,$dbpass);
    if(!$conn){
	   die('数据库连接失败'.mysqli_error());
    }
    mysqli_query($conn,'SET NAMES UTF8');
    mysqli_select_db($conn,'oc');
    // mysqli_query($conn,"SET AUTOCOMMIT=0");
    // mysqli_begin_transaction($conn);
   foreach($sql as $k=>$v){
     if(!mysqli_query($conn,$v)){
     	// mysqli_query($conn,"ROLLBACK");
     	die("插入数据失败:".mysqli_error($conn));
     }
   }
   // mysqli_commit($conn);
   return "1";
}
/**
 * 查询最新的语句
 * @param  [type] $key [description]
 * @param  [type] $table [description]
 * @return [type]      [description]
 */
function selectsql(){
	$dbhost='176.122.139.131:3306';
    $dbuser='root';
    $dbpass='a3492585';
    $conn=mysqli_connect($dbhost,$dbuser,$dbpass);
    if(!$conn){
	die('数据库连接失败'.mysqli_error());
    }
   mysqli_query($conn,'SET NAMES UTF8');
   mysqli_select_db($conn,'oc');
   $sql['product_id']="select product_id from oc_product order by product_id desc limit 1";
   $sql['product_image_id']="select product_image_id from oc_product_image order by product_image_id desc limit 1";
   $sql['product_option_id']="select product_option_id from oc_product_option order by product_option_id desc limit 1";
   $sql['product_option_value_id']="select product_option_value_id from oc_product_option_value order by product_option_value_id desc limit 1";
   foreach($sql as $k=>$v){
   	 $value=mysqli_query($conn,$v);
   	 $result=mysqli_fetch_array($value,MYSQLI_ASSOC);
   	 $row[$k]=$result[$k];
   }
   	return $row;


}

 function getIdModel(){
    $dbhost='176.122.139.131:3306';
    $dbuser='root';
    $dbpass='a3492585';
    $conn=mysqli_connect($dbhost,$dbuser,$dbpass);
    if(!$conn){
  die('数据库连接失败'.mysqli_error());
    }
   mysqli_query($conn,'SET NAMES UTF8');
   mysqli_select_db($conn,'oc');
   $sql="select image from oc_product where product_id >= 51;";
   $value=mysqli_query($conn,$sql);
   $result=mysqli_fetch_all($value);  
   return $result;
 }

 function dealArray(){
  $IdModel=getIdModel();
  foreach($IdModel as $k=>$v){
    $row=explode('/',$v[0])[2];
    $id=substr($row,0,strpos($row,'+'));
    $model=substr($row,-(strlen($row)-strpos($row,'+')-1));
    $result[$id]=$model;
  }
    return $result;
 }

 function checkstatus($k){
   $id=$k;
   $url="http://hws.m.taobao.com/cache/wdetail/5.0/?id=".$id;
   $ch=curl_init();
   $timeout=5;
   $UserAgent = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)';
   curl_setopt($ch,CURLOPT_URL,$url);
   curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
   curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
    curl_setopt( $ch, CURLOPT_USERAGENT, $UserAgent); 
   $file_contents=curl_exec($ch);
   curl_close($ch);
   $content_arr=json_decode($file_contents,true);
   $success_sym=$content_arr['ret']['0'];
   if($success_sym == "SUCCESS::调用成功"){
     return "√";
   }elseif($success_sym =="ERRCODE_QUERY_DETAIL_FAIL::宝贝不存在"){
    return "×";
   }else{
    return "网速较慢,请稍后重试";
   }
 }
 // $k=562232750740;
 // $data=checkstatus($k);
 // var_dump($data);
?>