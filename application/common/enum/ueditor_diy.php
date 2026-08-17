<?php
namespace app\common\enum;
use think\Db;

class ueditor_diy
{
    protected $root_path='';
    protected $icon_ext=array('png','gif','jpg','jpeg');
    protected $icon_default_ext='png';
    protected $icon_background='./Public/img/empty_background.png';
    protected $font_ttf='./Public/font/zhongchangsongtigbk.TTF';//字体文件
    protected $css_path='';
    protected $js_path='';
    protected $js_label_path='';
    protected $third_class=array();
    protected $ueditor_diy_button_list=array();
    protected $diy_button_source=array('Contract');
    protected $error='';
    public function __construct()
    {
        $this->root_path='/static/ueditor';
        $this->css_path = $this->root_path.'/themes/default/css/ueditor_diy_button.css';
        $this->js_path = $this->root_path.'/ueditor_diy_button.js';
        $this->js_label_path = $this->root_path.'/ueditor_diy_button_labelMap.js';
    }
    public function getError(){
        return $this->error;
    }
    public function set_button_source($button_source=''){
        if(!empty($button_source)){
            if(is_string($button_source)){
                $this->diy_button_source[]=$button_source;
            }elseif(is_array($button_source)){
                $this->diy_button_source=$button_source;
            }
        }
        return true;
    }
    public function get_third_class($class_name='',$namesplace=''){
        if(!isset($this->third_class[$class_name])){
            $third_class_list=array('Project','Contract');
            $model_list=array('Project','Contract');
            if(in_array($class_name,$third_class_list)){
                if(in_array($class_name,$model_list)){
                    $namesplace=!empty($namesplace)&&is_string($namesplace)?$namesplace:'Lxkus';
                    $this->third_class[$class_name]=D($namesplace.'/'.$class_name) ;
                }
            }
        }
        return $this->third_class[$class_name];
    }
    public function get_ueditor_diy_button_list($is_new=false){  //获取数据列表  -文本name列表
        $is_new = $is_new===true;
        if(empty($this->ueditor_diy_button_list)||$is_new){
            $ueditor_diy_button_list=   array(); 
            $default_contract_list=   Db::name('pact')->select(); 
            array_map(function($v)use(&$ueditor_diy_button_list){
                $temp=array();
                $temp['type']='contract';
                $temp['name']='wse_contract_'.$v['id'];
                $temp['title_en']=trim($v['name_en']);
                $temp['title']=trim($v['name']);
                $ueditor_diy_button_list[]=$temp;
            },$default_contract_list);
            $this->ueditor_diy_button_list=array_reverse($ueditor_diy_button_list);  //原数组元素0-1倒序整理
        }
        return $this->ueditor_diy_button_list;
    }
    public function replace_content($id=0,$title='',$type=1){
        $type=1;
        if($type==1){
            $pre_name='wse_contract_';
        }
        $list= Db::name('pact')->select(); //查询 列表
        $data=array();
        foreach($list as $val){
            $temp=array();
            $temp['id']=$val['id'];
            $temp['content']=replace_ueditor_content($val['content'],$pre_name.$id,$title);
            $temp['edittime']=time();
            $data[]=$temp;
        }
        if(!empty($data)){
            $status=model('pact')->allowField(true)->saveAll($data);
                        //  改为保存方法  全局替换 
            if($status===false){
                $this->error='数据更新失败：';
                return false;
            }
        }
        return true;
    }
    public function check_file_exist(){
        $file_error=0;
        $css_path=get_filepath($this->css_path);
        if(!file_exists($css_path)){
            $file_error+=1;
        }
        clearstatcache(true,$css_path);
        $css_size=@filesize($css_path);
        if(!$css_size){
            $file_error+=2;
        }
        $js_path=get_filepath($this->js_path);
        if(!file_exists($js_path)){
            $file_error+=4;
        }
        clearstatcache(true,$js_path);
        $css_size=@filesize($js_path);
        if(!$css_size){
            $file_error+=8;
        }
        $js_label_path=get_filepath($this->js_label_path);
        if(!file_exists($js_label_path)){
            $file_error+=16;
        }
        clearstatcache(true,$js_label_path);
        $css_size=@filesize($js_label_path);
        if(!$css_size){
            $file_error+=32;
        }
        return $file_error;
    }
    public function diy_button(){
      
        $this->rebuild_js();
        $this->rebuild_css();
        $this->rebuild_js_label();
        return true;
    }
    public function rebuild_js(){
        $js_path=$this->js_path;
        $file = fopen(get_filepath($js_path), "w");
        if($file===false){
            $this->error='Unable to open js file';
            return false;
        }
        $js_header=<<<EOF
var ueditor_diy_button=[

EOF;
        fwrite($file,$js_header);
        $ueditor_diy_button_list=$this->get_ueditor_diy_button_list();
        array_map(function($v)use($file){
            /*这里开头加空行以防止与前面的js叠于同一行*/
            $js_content=<<<EOF
    '
EOF;
            $js_content.=$v['name'];
            $js_content.=<<<EOF
',

EOF;
            fwrite($file, $js_content);
        },$ueditor_diy_button_list);

        $js_end=<<<EOF

]
EOF;
        fwrite($file, $js_end);
        fclose($file);
        return true;
    }
    public function rebuild_css(){
        $css_path=$this->css_path;
        $file = fopen(get_filepath($css_path), "w");
        if($file===false){
            $this->error='Unable to open css file';
            return false;
        }

        /*写入css*/
        $css_header=<<<EOF
body .edui-default .edui-toolbar .edui-button.edui-default .edui-icon{

}

EOF;
        fwrite($file,$css_header);
        $ueditor_diy_button_list=$this->get_ueditor_diy_button_list();
        array_map(function($v)use($file){
            /*这里开头加空行以防止与前面的css叠于同一行*/
            $css_content=<<<EOF

body .edui-default .edui-button.edui-default.edui-for-
EOF;
            $css_content.=$v['name'].'  ';
            $css_content.=<<<EOF
.edui-icon
{
    /*background: url(../images/
EOF;
            $css_content.=$v['name'];
            /*这里结尾加空行以防止与后面可能有的css叠于同一行*/
            $css_content.=<<<EOF
.png) no-repeat center;
    background-size: contain;
    min-width: 10px !important;
    height: 10px !important;
    width: 150px !important;*/
    background:none !important;
    min-width: 10px !important;
    width:auto !important;
    overflow:visible;
}

EOF;
            $css_content.=<<<EOF
body .edui-toolbar .edui-for-
EOF;
            $css_content.=$v['name'].'  ';
            $css_content.=<<<EOF
.edui-button-wrap .edui-button-body .edui-icon:before
{
    content:"
EOF;
            $css_content.=$v['title'];
            /*这里结尾加空行以防止与后面可能有的css叠于同一行*/
            $css_content.=<<<EOF
";
    font-size:12px;
    line-height:20px;
    padding-left:24px;
    width:160px !important;
    white-space:nowrap;
    font-weight:bold;
}

EOF;
            fwrite($file,$css_content);
        },$ueditor_diy_button_list);
        fclose($file);
        return true;
    }
    public function rebuild_js_label(){
        $js_path=$this->js_label_path;
        $file = fopen(get_filepath($js_path), "w");
        if($file===false){
            $this->error='Unable to open js labelMap file';
            return false;
        }

        $js_label_header=<<<EOF
var ueditor_diy_button_labelMap= {

EOF;
        fwrite($file,$js_label_header);

        $ueditor_diy_button_list=$this->get_ueditor_diy_button_list();
        array_map(function($v)use($file){
            /*这里开头加空行以防止与前面的js叠于同一行*/
            $js_label_content=<<<EOF
    '
EOF;
            $js_label_content.=$v['name'];
            $js_label_content.=<<<EOF
':'
EOF;
            $js_label_content.=$v['title_en'];
            $js_label_content.=<<<EOF
',

EOF;
            fwrite($file,$js_label_content);
        },$ueditor_diy_button_list);

        $js_label_end=<<<EOF

}
EOF;
        fwrite($file,$js_label_end);

        fclose($file);
        return true;
    }
    function image_cut($src_img = "./Public/img/empty_background.png",$path=false,$dst_w = 300,$dst_h = 200){
        $path=!empty($path)&&is_string($path)?$path:false;
        list($src_w,$src_h)=getimagesize($src_img);  // 获取原图尺寸
        $dst_scale = $dst_h/$dst_w; //目标图像长宽比
        $src_scale = $src_h/$src_w; // 原图长宽比
        if ($src_scale>=$dst_scale){  // 过高
            $w = intval($src_w);
            $h = intval($dst_scale*$w);
            $x = 0;
            $y = ($src_h - $h)/3;
        } else { // 过宽
            $h = intval($src_h);
            $w = intval($h/$dst_scale);
            $x = ($src_w - $w)/2;
            $y = 0;
        }
        // 剪裁
        $source=imagecreatefromjpeg($src_img);
        $croped=imagecreatetruecolor($w, $h);
        imagecopy($croped, $source, 0, 0, $x, $y, $src_w, $src_h);
        // 缩放
        $scale = $dst_w / $w;
        $target = imagecreatetruecolor($dst_w, $dst_h);
        $final_w = intval($w * $scale);
        $final_h = intval($h * $scale);
        imagecopyresampled($target, $croped, 0, 0, 0, 0, $final_w,$final_h, $w, $h);
        // 保存
        if(!$path){
            header ("Content-type: image/*");
        }
        imagejpeg($target, $path);
        imagedestroy($target);
        return $path?$path:true;
    }
    function image_undamaged_cut($image='./Public/img/empty_background.png',$path=false,$xx = 140,$yy = 200){
        $path=!empty($path)&&is_string($path)?$path:false;
        if (empty($image) || !file_exists($image)) {
            $this->error='文件不存在';
            return false;
        }
        $imgstream = file_get_contents($image);
        $im = imagecreatefromstring($imgstream);
        $x = imagesx($im);//获取图片的宽
        $y = imagesy($im);//获取图片的高
        if($x>$y){
            //图片宽大于高
            $sx = abs(($y-$x)/2);
            $sy = 0;
            $thumbw = $y;
            $thumbh = $y;
        } else {
            //图片高大于等于宽
            $sy = abs(($x-$y)/2.5);
            $sx = 0;
            $thumbw = $x;
            $thumbh = $x;
        }
        if(function_exists("imagecreatetruecolor")) {
            $dim = imagecreatetruecolor($yy, $xx); // 创建目标图gd2
        } else {
            $dim = imagecreate($yy, $xx); // 创建目标图gd1
        }
        imageCopyreSampled ($dim,$im,0,0,$sx,$sy,$yy,$xx,$thumbw,$thumbh);
        if(!$path){
            header ("Content-type: image/*");
        }
        imagejpeg ($dim, $path, 100);
        return $path?$path:true;
    }
    function imageWaterMark($path,$groundImage, $waterPos = 0, $waterImage = "", $waterText = "", $textFont = 5, $textColor = "#FF0000") {
        $path=get_filepath($path);
        $groundImage=get_filepath($groundImage);
        $waterImage=get_filepath($waterImage);
        $isWaterImage = false;
        $formatMsg = "暂不支持该文件格式，请用图片处理软件将图片转换为GIF、JPG、PNG格式。";
        $font_ttf="./Public/font/zhongchangsongtigbk.TTF";
        //读取水印文件
        if (!empty($waterImage) && file_exists($waterImage)) {
            $isWaterImage = true;
            $water_info = getimagesize($waterImage);
            $water_w = $water_info[0]; //取得水印图片的宽
            $water_h = $water_info[1]; //取得水印图片的高
            switch ($water_info[2]) {//取得水印图片的格式
                case 1:$water_im = imagecreatefromgif($waterImage);
                    break;
                case 2:$water_im = imagecreatefromjpeg($waterImage);
                    break;
                case 3:$water_im = imagecreatefrompng($waterImage);
                    break;
                default:
                    $this->error='111'.$formatMsg;
                    return false;
            }
        }
        //读取背景图片
        $groundImage = !empty($groundImage)?$groundImage:'./Public/img/empty_background.png';
        if (!empty($groundImage) && file_exists($groundImage)) {
            $status=copy($groundImage,$path);
            if(!$status){
                $this->error="背景图片复制失败";
                return false;
            }
            $ground_info = getimagesize($path);
            $ground_w = $ground_info[0]; //取得背景图片的宽
            $ground_h = $ground_info[1]; //取得背景图片的高
        } else {
            $this->error="需要加水印的图片不存在！";
            return false;
        }
        //水印位置
        if ($isWaterImage) {//图片水印
            $w = $water_w;
            $h = $water_h;
            $label = "图片的";
        } else {//文字水印
            $temp = imagettfbbox(ceil($textFont * 5), 0, $font_ttf , $waterText); //取得使用 TrueType 字体的文本的范围
            $w = $temp[4] - $temp[6];
            $h = $temp[3] - $temp[7];
            //unset($temp);
            $label = "文字区域";
        }
        if (($ground_w < $w) || ($ground_h < $h)) {
            $this->error="需要加水印的图片的长度或宽度比水印" . $label . "还小，无法生成水印！";
            return false;
        }
        $scal=$ground_w/$w;
        switch ($waterPos) {
            case 1://1为顶端居左
                $posX = 0;
                $posY = 0;
                break;
            case 2://2为顶端居中
                $posX = ($ground_w - $w) / 2;
                $posY = 0;
                break;
            case 3://3为顶端居右
                $posX = $ground_w - $w;
                $posY = 0;
                break;
            case 4://4为中部居左
                $posX = 0;
                $posY = ($ground_h - $h) / 2;
                break;
            case 5://5为中部居中
                $posX = ($ground_w - $w) / 2;
                $posY = ($ground_h - $h) / 2;
                break;
            case 6://6为中部居右
                $posX = $ground_w - $w;
                $posY = ($ground_h - $h) / 2;
                break;
            case 7://7为底端居左
                $posX = 0;
                $posY = $ground_h - $h;
                break;
            case 8://8为底端居中
                $posX = ($ground_w - $w) / 2;
                $posY = $ground_h - $h;
                break;
            case 9://9为底端居右
                $posX = $ground_w - $w - 10;   // -10 是距离右侧10px 可以自己调节
                $posY = $ground_h - $h - 10;   // -10 是距离底部10px 可以自己调节
                break;
            case 10://撑满
                $posX = 0;
                $posY = 0;
                $w=$ground_w;
                $h=floor($h*$scal);
                break;
            default://随机
                $posX = rand(0, ($ground_w - $w));
                $posY = rand(0, ($ground_h - $h));
                break;
        }
        $status=$this->image_undamaged_cut($path,$path,$w,$h);
        if(!$status){
            $this->error="背景图无损裁剪失败：".get_last_error();
            return false;
        }
        $ground_info = getimagesize($path);
        switch ($ground_info[2]) {//取得背景图片的格式
            case 1:$ground_im = imagecreatefromgif($path);
                break;
            case 2:$ground_im = imagecreatefromjpeg($path);
                break;
            case 3:$ground_im = imagecreatefrompng($path);
                break;
            default:
                $this->error='222'.$formatMsg;
                return false;
        }
        //设定图像的混色模式
        imagealphablending($ground_im, true);
        if ($isWaterImage) {//图片水印
            imagecopy($ground_im, $water_im, $posX, $posY, 0, 0, $w, $h); //拷贝水印到目标文件
        } else {//文字水印
            if (!empty($textColor) && (strlen($textColor) == 7)) {
                $R = hexdec(substr($textColor, 1, 2));
                $G = hexdec(substr($textColor, 3, 2));
                $B = hexdec(substr($textColor, 5));
            } else {
                $this->error="水印文字颜色格式不正确！";
                return false;
            }
//        imagestring($ground_im, $textFont, $posX, $posY, $waterText, imagecolorallocate($ground_im, $R, $G, $B));
            imagettftext ( $ground_im, ceil($textFont * 5), 0, $posX, $posY, imagecolorallocate ( $ground_im, $R, $G, $B ), $font_ttf, $waterText );
            //iconv("UTF-8","GB2312",$waterText)
        }
        //生成水印后的图片
        @unlink($path);
        switch ($ground_info[2]) {//取得背景图片的格式
            case 1:imagegif($ground_im, $path);
                break;
            case 2:imagejpeg($ground_im, $path);
                break;
            case 3:imagepng($ground_im, $path);
                break;
            default:
                $this->error='333'.$errorMsg;
                return false;
        }
        //释放内存
        if (isset($water_info)){
            unset($water_info);
        }
        if (isset($water_im)){
            imagedestroy($water_im);
        }
        unset($ground_info);
        imagedestroy($ground_im);

        return substr($path,1);
    }
}