<?php
header("content-type:text/html;charset=utf-8");
require('mysql.php');
/**
 * Created by PhpStorm.
 * User: leif
 * Date: 16/1/26
 * Time: 10:17
 * email: leiflyy@outlook.com
 */

/**
 *  实现传入宝贝的id,返回宝贝的链接,支持淘宝
 * @param $id 宝贝的id
 * @return string 返回的宝贝的链接
 */
function getTbLink($id){
    $url="https://item.taobao.com/item.htm?spm=a1z10.4-c.w5003-12641268955.30.0lDnKZ&id=".$id."&scene=taobao_shop";
    // $url="https://detail.tmall.com/item.htm?spm=a220m.1000858.1000725.5.3accd4e4IOQeAD&id=".$id;   //天猫
    return $url;
}


/**
 * 实现传入宝贝的id,获取宝贝的商品名,支持淘宝和天猫
 * @param $id  宝贝的id
 * @return mixed  宝贝的商品名
 */
function getNameById($id){
    // $url="http://hws.m.taobao.com/cache/wdetail/5.0/?id=".$id;
    // // $url="https://detail.m.tmall.com/item.htm?id=".$id;   //天猫
    // $content=file_get_contents($url);
    // $content_ori=strip_tags($content);
    $memcache=MemcacheConnect();
    if($memcache->get($id) != ''){
    $content_arr=$memcache->get($id);
  }else{
    $content_arr=getUrlData($id);
  }
    $name=$content_arr['data']['itemInfoModel']['title'];

    return $name;
}

/**
 * 实现传入宝贝id,获取宝贝价格,支持淘宝和天猫
 * @param $id   宝贝的id
 * @return mixed 返回的宝贝的价格或价格区间
 */
function getPriceById($id){
    $content_arr=getUrlData($id);
    $pro_detail=json_decode($content_arr['data']['apiStack']['0']['value'],true);
    $success_sym=$pro_detail['ret']['0'];//成功则返回"SUCCESS::调用成功";
    if($success_sym=="SUCCESS::调用成功"){
        $pro_price=$pro_detail['data']['itemInfoModel']['priceUnits']['0']['price'];
        return $pro_price;
    }else{
        exit("获取价格失败");
    }
}

/**
 * 获取商品的id
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
function getTitlelid($id){
  $urldata=getUrlData($id);
  $title=$urldata['data']['itemInfoModel']['title'];
  $modelid=substr($title,-7);
  return $modelid;
}

/**
 *  实现传入宝贝id,获取宝贝的收藏人数(人气),支持淘宝
 * @param $id  宝贝id
 * @return mixed   返回的宝贝的收藏人数(人气)
 */
function getPopById($id){
    $url=getTbLink($id);
    $urlinfo = parse_url($url);
    parse_str($urlinfo['query'], $query);
    $id = $query['id'];
    $data = file_get_contents($url);
    $start = strpos($data, 'counterApi');
    $start = strpos($data, ": ", $start);
    $end = strpos($data, "',", $start);
    $api = 'https:' . substr($data, $start + 3, $end - $start - 3) . '&callback=jsonp107';
    $response = file_get_contents($api);
    $response = substr($response, 9, -2);
    $arr = json_decode($response, true);
    $popularity=$arr['ICCP_1_'.$id];
    return $popularity;
}

/**   实现传入宝贝id，获取宝贝图片url
 * @param $id    宝贝id
 * @return mixd   宝贝图片url
 */
function getPicById($id){
    $urldata=getUrlData($id);
    $picsPath=$urldata['data']['itemInfoModel']['picsPath'];
    $memcache=MemcacheConnect();
    if($memcache->get($id.'-picsPath') == ""){
      $memcache->set($id.'-picsPath',$picsPath);
      return $memcache->get($id.'-picsPath');
    }else{
      return $memcache->get($id.'-picsPath');
    }

}

 function getUrlData($id){
    $memcache=MemcacheConnect();
    if($memcache->get($id) == ''){
        $url="http://hws.m.taobao.com/cache/wdetail/5.0/?id=".$id;
        $data=file_get_contents($url);
        $content_ori=strip_tags($data);
        $content_arr=json_decode($content_ori,true);
        $success_sym=$content_arr['ret']['0'];
        if($success_sym=="SUCCESS::调用成功"){
          if($memcache->set($id,$content_arr)){
            return $memcache->get($id);
          }else{
            return "memcache 写入失败";
          }
        }else{
        getUrlData($id);
      }
        // return $memcache->get($id);
   }else{
      return $memcache->get($id);
   }

 }
  /**
   * 根据获取的内容进行翻译
   * @param  [type] $content [description]
   * @return [type]          [description]
   */
  function getTranslate($content){
    $appid='20180125000118625';
    $code='J6KL5y0buGww8_eluKNJ';
    $url='http://api.fanyi.baidu.com/api/trans/vip/translate?q=';
    $rand=rand(1,10000);
    //生成sign
    $sign=md5($appid.$content.$rand.$code);
    //拼接url
    $url=$url.$content."&from="."auto"."&to="."en"."&appid=".$appid."&salt=".$rand."&sign=".$sign;
    // 发送url请求，获取内容
    $data=file_get_contents($url);
    $content_ori=strip_tags($data);
    $content_arr=json_decode($content_ori,true);
    $detail=$content_arr['trans_result']['0']['dst'];
    return $detail;
  }

  /**
   * 获取产品的中文或中英文标题
   * @param  [type] $id   [description]
   * @param  [type] $type [默认为false 只有中文，true为中英文]
   * @return [type]       [description]
   */
 function getTitle($id,$type="false"){
   if($type == "true"){
     $title_cn=getNameById($id);
     $title_en=getTranslate($title_cn);
     $data['tit_cn']=$title_cn;
     $data['tit_en']=$title_en;
     return $data;
   }else{
      $title_cn=getNameById($id);
      $data['tit_cn']=$title_cn;
      return $data;

   }
 }
  /**
   * 获取某个产品的各款式id和中文名称
   * @param  [type] $id [description]
   * @return [type]     [description]
   */
  function getColor($id){
    $data=getUrlData($id);
    $propId=$data['data']['skuModel']['skuProps'][0]['propId'];
    $skuValue=$data['data']['skuModel']['skuProps'][0]['values'];
    $ppathIdmap=$data['data']['skuModel']['ppathIdmap'];
    foreach($skuValue as $k=>$v){
       $skuValue[$k]['valueId']=$propId.":".$v['valueId'];
       unset($skuValue[$k]['imgUrl']);
       $data=$ppathIdmap[$propId.":".$v['valueId']];
       if($data != ''){
        $skuValue[$k]['valueId']=$data;
       }
       
    }

   return $skuValue;
  }

  /**
   * 获取所有颜色属性对应的价格，数量
   * @param  [type] $id   [description]
   * @return [type]       [description]
   */
   function getQuantity($id){
   $Urldata=getUrlData($id);
   $skuValue=getColor($id);
   $data['quantity']=array();
   $dataValue=json_decode($Urldata['data']['apiStack'][0]['value'],true);
   $totalquantity=$dataValue['data']['itemInfoModel']['quantity'];
   $skus=$dataValue['data']['skuModel']['skus'];
   // $num=$skus[$skuValue[0]['valueId']]['quantity'];
   foreach($skuValue as $k=>$v){
       $try=$skus[$skuValue[$k]['valueId']]['quantity'];
       $skuValue[$k]['quantity']=$try;
       $num=$skus[$skuValue[$k]['valueId']]['priceUnits'][0]['price'];
       $skuValue[$k]['price']=$num;
   }
   $data['quantity']=$skuValue;
   $data['quantity']['totalquantity']=$totalquantity;
   return $data;

}

function getOptionNum($id){
	$urldata=getUrlData($id);
	// $data=$urldata['data']['skuModel']['skuProps'][0]['values'];
	$num=count($urldata['data']['skuModel']['skuProps'][0]['values']);
	return $num;
}

 /**
  * 获取某个产品所有的属性
  * @param  [type]  $id   [description]
  * @return $return 返回一维关联数组 [description]
  */
 function getPropsArr($id){
    $urldata=getUrlData($id);
    $props=$urldata['data']['props'];
    $propsarr=array();
    foreach($props as $k=>$v){
       $name=$props[$k]['name'];
       $value=$props[$k]['value'];
       $propsarr[$name]=$value;
    }
    return $propsarr;
   
 }

 /**
  * 获取某个产品的所需的6个属性的中英文
  * @param  [type]  $id   [description]
  * @param  boolean $type [description]
  * @return [type]        [description]
  */
 function getAttribute($id){
    $memcache=MemcacheConnect();
    if($memcache->get($id.'-attribute') == ''){
     $attarr=array('大小'=>'','背包方式'=>'','提拎部件类型'=>'','箱包外袋种类'=>'','箱包硬度'=>'','形状'=>'','质地'=>'','闭合方式'=>'','图案'=>'');
     $propsarr=getPropsArr($id);
     $result=array_intersect_key($propsarr, $attarr);
     foreach($result as $k=>$v){
      $k1=getTranslate($k);
      $v1=getTranslate($v);
      $result[$k1]=$v1;
    }
       $memcache->set($id.'-attribute',$result);
       return $memcache->get($id.'-attribute');
    }else{
       return $memcache->get($id.'-attribute');
    }
    
 }
 
 /**
  * 
  * 获取某个商品的详情图片url
  * @param  [type] $id [description]
  * @return [type]     [description]
  */
 function getbriefDescUrl($id){
    $urldata=getUrlData($id);
    $briefDescUrl=$urldata['data']['descInfo']['briefDescUrl'];
    $data=file_get_contents($briefDescUrl);
    $content_ori=strip_tags($data);
    $content_arr=json_decode($content_ori,true);
    $success_sym=$content_arr['ret']['0'];
    if($success_sym=="SUCCESS::接口调用成功"){
        return $content_arr['data']['images'];
    }else{
        exit("获取详情图片链接失败");
    }
    
 }

/**
 * 构造网店的图片路径并写入数组
 * @param  [type] $id [description]
 * @return [type]     [description]
 */
 function getDescImage($id){
       $memcache=MemcacheConnect();
       if($memcache->get($id.'-Descimg')==""){
          $descimgurl=getbriefDescUrl($id);
          $webimgpath="http://www.ngbag.com/image/catalog/Product/".$id."+".getTitlelid($id);
          //构造店铺图片url
           foreach($descimgurl as $k=>$v){
            $filename=pathinfo($v,PATHINFO_BASENAME);
            $data['Descimg'][$k]=$webimgpath."/".$filename;
            }
            if($data['Descimg'] !=""){
            $memcache->set($id.'-Descimg',$data['Descimg']);
            return $memcache->get($id.'-Descimg');
           }
    }else{
          return $memcache->get($id.'-Descimg');
       }
      
 }
  
/**
 * 获取所有图片的下载链接并保存
 * @param  [type] $id   [产品id]
 * @param  [type] $path [保存路径]
 * @return [type]       [description]
 */
 function getDownImage($id,$path){
   // $memcache=MemcacheConnect();
   // if($memcache->get($id."-Descimg")==""){
      $descimgurl=getbriefDescUrl($id);
      $mainimgurl=getPicById($id);
      $imgurl=array_merge($descimgurl,$mainimgurl);
      !is_dir($path)?mkdir($path,0775):"";
      $dir=$path.$id."+".getTitlelid($id)."\\";
      !is_dir($dir)?mkdir($dir,0775):"";
      $txt=$dir.$id."+".getTitlelid($id).".txt";
      $downfp=@fopen($txt,'w');
      //先将详情图的下载链接逐条写入文件夹中
      foreach($imgurl as $k=>$v){
         $urlresult=fwrite($downfp,$v."\r\n");
      }
      if($urlresult !=""){
      	return $urlresult;
      }else{
      	exit("图片下载链接写入失败");
      }
      
  }


/**
 * 获取主图图片并写入数组中
 * @param  [type] $id   [description]
 * @param  string $path [description]
 * @return [type]       [description]
 */
 function getMainimg($id){
       $memcache=MemcacheConnect();
       if($memcache->get($id.'-Mainimg')==""){
          $mainimgurl=getPicById($id);
          $mainimgpath="catalog/Product/".$id."+".getTitlelid($id);
         foreach($mainimgurl as $k=>$v){
           $filename=pathinfo($v,PATHINFO_BASENAME);
           $data['Mainimg'][$k]=$mainimgpath."/".$filename;
           }
          if($data['Mainimg'] !=""){
            $memcache->set($id.'-Mainimg',$data['Mainimg']);
            return $memcache->get($id.'-Mainimg');
          }       
    }else{
          return $memcache->get($id.'-Mainimg');
       }

 }

  /**
   * 构造执行数组
   * @param  [type] $id   [description]
   * @param  [type] $type [description]
   * @param  [type] $path [description]
   * @return [type]       [description]
   */
 function getArrTogether($id,$type,$path){
   $memcache=MemcacheConnect();
   if($memcache->get($id.'-total') == ""){
     //获取标题数组
     $title=getTitle($id,$type);
     $data["title"]=$title;
   //获取价格
     $price=getPriceById($id);
     $data['totalprice']=$price;
   // 获取库存
     $quantity=getQuantity($id);
     $data['colorquantity']=$quantity;
   //获取属性
     $props=getAttribute($id);
     $data['Attribute']=$props;
   // //获取详情图片
    $descimg=getDescImage($id,$path);
     $data['Descimg']=$descimg;
   // //获取主图图片
     $mainimg=getMainimg($id,$path);
     $data['Mainimg']=$mainimg;
   //写入图片下载链接文件
     getDownImage($id,$path);
     // return $data;
     $memcache->set($id.'-total',$data);
     return $memcache->get($id.'-total');
    }else{
    return $memcache->get($id.'-total');
    }

 }

/**
 * 连接memcache,返回memcache实例
 */
 function MemcacheConnect(){
    $memcache=new Memcache;
    $memcache->connect('localhost','11211') or die('连接memcache失败');
    // $memcache->set('key','test');
    // $get_value=$memcache->get('key');
    // echo $get_value;
    return $memcache;
 }

//memcache value test
 function test($id){
    $memcache=MemcacheConnect();
    $value=$memcache->get($id);
    var_dump($value);
 }

 function deletevalue($id){
  $memcache=MemcacheConnect();
  $result=$memcache->delete($id);
  return $result;
 }

// ----以下为构造sql-----

 function getmodelId($id){
    $memcache=MemcacheConnect();
    $productdata=$memcache->get($id.'-total');
    $modelId=substr($productdata['title']['tit_cn'],-7);
    return $modelId;
 }
 /**
  * 构造数据库属性sql语句
  * @param [type] $id         [description]
  * @param [type] $product_id [description]
  */
 function Attrsql($product_id){
    // $memcache=MemcacheConnect();
    // $productdata=$memcache->get($id.'-total');
    for($i=12;$i<=20;$i++){
    $attrsql="insert into oc_product_attribute (product_id,attribute_id,language_id,text) values ('$product_id','$i','1','')#\r\n";
    $getattrsql .=$attrsql;
    }
    return $getattrsql;
  }
   /**
    * 写入详情描述sql
    * @param [type] $id         [description]
    * @param [type] $product_id [description]
    */
  function Descsql($id,$product_id){
    $memcache=MemcacheConnect();
    $productdata=$memcache->get($id.'-total');
    $title=$productdata['title']['tit_en'];
    $Descimg=$productdata['Descimg'];
    $front="&lt;p&gt;&lt;img 
style=&quot;width: 790px;&quot; src=&quot;";
    $rear="&quot;&gt;&lt;br&gt;&lt;/p&gt;\r\n";
    foreach($Descimg as $k=>$v){
      $detail[$k]=$front.$v.$rear;
    }
    $getdetail=implode("\r",$detail);
    // $fp=fopen("C:\\Users\\Administrator\\Desktop\\new.txt",w);
    // fwrite($fp,$getdetail);
    $descsql="insert into oc_product_description (product_id,language_id,name,description,tag,meta_title,meta_description,meta_keyword) values ";
    $descsql.="('$product_id','1','$title','$getdetail','$title','$title','$title','$title')#\r\n";
    return $descsql;

  }

  function Mainpicsql($id,$product_id,$imgid){
    $memcache=MemcacheConnect();
    $productdata=$memcache->get($id.'-total');
    $mainimg=$productdata['Mainimg'];
    for($i=$imgid,$j=1;$i<=$imgid+2,$j<=4;$i++,$j++){
    $sql="insert into oc_product_image (product_image_id,product_id,image,sort_order) values ('$i','$product_id','$mainimg[$j]','0')#\r\n";
    $getsql .=$sql;
    }

    return $getsql;

  }
/**
 * 写入产品的option id内容
 * @param [type] $id       [description]
 * @param [type] $product  [description]
 * @param [type] $optionid [description]
 */
  function Optionsql($id,$product_id,$optionid,$product_option_id){
     //增加product_option
     $memcache=MemcacheConnect();
     $productdata=$memcache->get($id.'-total');
     $quantity=$productdata['colorquantity']['quantity'];
     $count=count($quantity)-2;
     for($i=$optionid+1,$j=$product_option_id+1,$z=0;$i<=$optionid+1+$count,$j<=$product_option_id+1+$count,$z<=$count;$i++,$j++,$z++){
      $product_option_sql="insert into oc_product_option (product_option_id,product_id,option_id,value,required)values('$optionid','$product_id','1','','1')#\r\n";
      $quan=$quantity[$z]['quantity'];
      $option_value_sql="insert into oc_product_option_value (product_option_value_id,product_option_id,product_id,option_id,option_value_id,quantity,subtract,price,price_prefix,points,points_prefix,weight,weight_prefix)values('$j','$optionid','$product_id','1','52','$quan','1','0','+','0','+','0','+')#\r\n";
      $get_value_sql.=$option_value_sql;
     }
     $result=$product_option_sql.$get_value_sql;
    // $fp=fopen("C:\\Users\\Administrator\\Desktop\\new.txt",w);
    //  fwrite($fp,$result);
     return $result;
  }

  function othersql($product_id){
    //insert to category
    $category="insert into oc_product_to_category (product_id,category_id)values('$product_id','61')#\r\n";
    //insert to layout
    $layout="insert into oc_product_to_layout (product_id,store_id,layout_id)values('$product_id','0','0')#\r\n";
    //insert to store
    $store="insert into oc_product_to_store (product_id,store_id)values('$product_id','0')";
    $result=$category.$layout.$store;
    return $result;
  }

/**
 * 从memcache中获取产品信息，拼出所需的sql语句
 * @param  [type] $id [description]
 * @return [type] $product_id  [description]
 */

 function writesql($id,$product_id,$imgid,$optionid,$product_option_id){
  $memcache=MemcacheConnect();
  if($memcache->get($id.'-sql') == ""){
  $productdata=$memcache->get($id.'-total');
  $quantity=$productdata['colorquantity']['quantity']['totalquantity'];
  $mainpic=$productdata['Mainimg'][0];
  $price=$productdata['totalprice'];
  $modelId=getmodelId($id);
  //oc_product 表单
  $oc_product_sql="insert into oc_product (product_id,model,quantity,stock_status_id,image,manufacturer_id,shipping,price,points,tax_class_id,date_available,weight,weight_class_id,length,width,height,length_class_id,subtract,minimum,sort_order,status,viewed,date_added)values ";
  $oc_product_sql.="('$product_id','$modelId','$quantity','5','$mainpic','0','1','$price','0','0','2018-01-21','1','1','1','1','1','1','1','1','1','1','1','2018-01-23 22:00:00')#\r\n";
  $oc_product_sql.=Attrsql($product_id);
  $oc_product_sql.=Descsql($id,$product_id);
  $oc_product_sql.=Mainpicsql($id,$product_id,$imgid);
  $oc_product_sql.=Optionsql($id,$product_id,$optionid,$product_option_id);
  $oc_product_sql.=othersql($product_id);
  // $fp=fopen("C:\\Users\\Administrator\\Desktop\\new.txt",a);//追加写用a,覆盖写用w
  // fwrite($fp,$oc_product_sql);
  $totalsql=explode('#',$oc_product_sql);
  $memcache->set($id.'-sql',$totalsql);
  return $memcache->get($id.'-sql');
}else{
    return $memcache->get($id.'-sql');
}

  
 }

//接受传送过来的id值
// $data=$_POST['data'];
// $idarray=explode(',',$data);
$data="561708598849,561707114779,561637333913";
$idarray=explode(',',$data);



function run($idarray){
	//初始化各id值
	$row=selectsql();
   $product_id=$row['product_id'];
  $path="C:\\Users\\Administrator\\Desktop\\image\\".date("Y-m-d")."\\";
  $type=true;
   $imgid=$row['product_image_id'];
   $optionid=$row['product_option_id'];
   $product_option_id=$row['product_option_value_id'];
  foreach($idarray as $k=>$v){
  	$result1=getArrTogether($v,$type,$path);
    $product_id=$product_id+1;
  	$imgid=$imgid+4;
  	$optionid=$optionid+1;
  	$product_option_id=$product_option_id+getOptionNum($v)+1;
  	$result2=writesql($v,$product_id,$imgid,$optionid,$product_option_id);
    print_r($result2);
  	$result3=insertsql($result2);

  }
  // if($result1 && $result2 && $result3!=""){
  // 	$info=array('msg'=>"图片链接和数据库语句准备好");
  // 	echo json_encode($info);
  // }else{
  // 	$info=array('msg'=>"操作失败");
  // 	echo json_encode($info);
  // }  
    
}
run($idarray);

function runtest($idarray){
   $info=array('msg'=>"图片成功");
   echo json_encode($info);
}
// runtest($idarray);

 
// $id='562872788998';
$id="561637333913";
$id1="111";
$product_id='65';//+1
// $id='563117479546';
$date=date("Y-m-d");
$path="C:\\Users\\Administrator\\Desktop\\image\\".$date."\\";
$type=true;
$imgid='2470';//+4
$optionid='255';//+1
$product_option_id='82';//根据情况
$key='product_id';
$table='oc_product';
//数组集合
// $data=getArrTogether($id,$type,$path);
// var_dump($data);
// 写sql
// $data=writesql($id,$product_id,$imgid,$optionid,$product_option_id);
//   var_dump($data);
//获取属性
// $data=getAttribute($id);
// var_dump($data);
// 获取主图
 // $data=getMainimg($id,$path);
 // var_dump($data);
//memcache id test
// $data=test($id."-sql");
// var_dump($data);
// desc的sql语句
// $data=getDescImage($id,$path);
// var_dump($data);
// 删除缓存数据
// $data=deletevalue($id."-sql");
// var_dump($data);
// 获取主图图片
// $data=getMainimg($id,$path);
// var_dump($data);
// 获取数据
// $data=getUrlData($id);
// var_dump($data);
// 获取颜色
// $data=getColor($id);
// var_dump($data);
// 获取id
// $data=getNameById($id);
// var_dump($data);
// 获取翻译
// $data=getTitle($id,$type);
// var_dump($data);
// 创建下载链接文件
 // $data=getDownImage($id,$path);
 // var_dump($data);
   // option的数量
   // $data=getOptionNum($id);
   // var_dump($data);
//执行查询命令
// $data=selectsql();
// var_dump($data);
?>