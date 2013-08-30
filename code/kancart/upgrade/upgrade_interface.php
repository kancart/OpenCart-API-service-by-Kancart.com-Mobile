<?php
$cartType = strtolower('opencart');
if (isset($_REQUEST['user_language'])) {
    $userLanguage = strtolower($_REQUEST['user_language']);
} else if (isset($_REQUEST['language'])) {
    $userLanguage = strtolower($_REQUEST['language']);
} else {
    $userLanguage = 'en';
}

if ($userLanguage == 'en') {
    $lang_upgrade_now = 'Upgrade Now';
    $lang_upgrade_success = 'Congratulations! You have successfully upgraded kancart plugin. ';
    $lang_read_more = 'You can see detail log here:';
    $lang_upgrade_failed = 'Sorry, upgrade failed.  ';
    $lang_img_url = "http://www.kancart.com/images/en/upgrade_loader_bar.gif";
} else {
    $lang_upgrade_now = '立刻升级';
    $lang_upgrade_success = '您成功地升级了kancart插件！ ';
    $lang_read_more = '您可以在这里查看详细日志：';
    $lang_upgrade_failed = '很抱歉，更新失败了。 ';
    $lang_img_url = "http://www.kancart.com/images/zh-c/upgrade_loader_bar.gif";
}

$param['v'] = '1.1';
$param['app_key'] = KANCART_APP_KEY;
$param['method'] = 'KanCart.Plugin.Upgrade';
$param['format'] = 'JSON';
$param['sign_method'] = 'md5';
$param['language'] = 'EN';
$param["timestamp"] = date("Y-m-d H:i:s", time());
$param['client'] = 'cart';
$param['do_upgrade'] = TRUE;

function createSign(array $param, $secret) {
    unset($param["sign"]);
    ksort($param);
    reset($param);
    $tempStr = "";
    foreach ($param as $key => $value) {
        $tempStr = $tempStr . $key . $value;
    }
    $tempStr = $tempStr . $secret;
    return strtoupper(md5($tempStr));
}

$param['sign'] = createSign($param, KANCART_APP_SECRET);
$param['app_key'] = CryptoUtil::Crypto($param["app_key"], 'AES-256', KANCART_APP_SECRET, true);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html;  charset=utf-8" />
        <title><?PHP echo ucwords($cartType) . ' Auto Upgrade' ?></title>
        <script src="http://www.kancart.com/js/jquery-1.4.1.min.js" type="text/javascript"></script>   
        <style type="text/css">
            .upgrade_now {
                display: block; 
                height: 36px; 
                width: 155px;
                background: url(http://www.kancart.com/images/en/upgrade_now_btn.png) no-repeat 0px 0px; 
                color: #d84700;
                border-style: none;
            }
            .upgrade_now:hover {
                background: url(http://www.kancart.com/images/en/upgrade_now_btn.png) no-repeat 0px -36px;
                border-style: none;
            }
        </style>
    </head>
    <body>
        <script>
            function general_upgrade(){
                $.ajax({
                    url: '',
                    dataType:'json',
                    type:'post',
                    data: <?php echo json_encode($param); ?>,
                    beforeSend: function(XMLHttpRequest){
                        $('#log_detail')[0].style.display = 'block';
                        $("#general_upgrade_btn")[0].innerHTML = '<img src="<?php echo $lang_img_url ?>"/>';
                    },
                    success: function(json){
                        if(json['result'] == 'success'){
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_success ?>";
                        }else{
                            $("#result_show")[0].innerHTML = "<?php echo $lang_upgrade_failed ?>";
                        }
                        $("#maincontain")[0].innerHTML =  $("#result_contain")[0].innerHTML;
                    },
                    complete: function(XMLHttpRequest, textStatus){
                    },
                    error: function(){
                    }
                });
            }
        </script>
        <div id="container" style="width:900px;margin:auto;">
            <div class="title" style="font-size: 30px;color:#93C905;height:60px;border-bottom: 1px solid #999;">
                <div id="logo" style="margin:20px;vertical-align: middle;display:table-cell;">
                    <img src="http://www.kancart.com/images/kancart_logo.gif"/>
                    <span style="margin-left:230px;margin-top:-35px;display:block"><?PHP echo ucfirst($cartType) . ' Plugin Auto Upgrade' ?></span>
                </div>
            </div>
            <div id="maincontain" style="width:100%;margin-top:20px;">
                <div id="general_upgrade_btn" style="width: 155px; text-align: center; margin: 10px auto;">
                    <input type="button" class="upgrade_now" onfocus="this.blur()" onclick="general_upgrade();"/> 
                </div>
                <div style="clear:both;"></div>
            </div>
            <div id="log_detail" style="font-size:12px;display:none;height:400px;overflow-y:auto;padding-top:15px;">                   
            </div>
            <div id="result_contain" style="display:none;">
                <div style="padding-top:5px;"><span id="result_show" style="font-size:20px;color:#93C905;"></span></div>
                <div style="clear:both;"></div>
            </div>
        </div>
    </body>
</html>