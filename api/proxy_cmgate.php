<?php 
header("Content-type: text/json");
header("Access-Control-Allow-Origin: *");
include_once 'userinfo.php';
require_once 'function.php';
error_reporting(1);
if (isset($_POST)) {
    $content = trim(file_get_contents("php://input"));
    $post = json_decode($content,true);
    $command = $post['command'];
    switch ($command) {
        case 'showListUser':
            $owner = trim($post['owner']);
            $result = showuserall($sql_host,$sql_user,$sql_pass,$sql_db,$owner);
            if ($result == 1) {
                $resultcmd = json_encode(array('RESULT' => 2, 'MESSAGE' => 'Có lỗi trong quá trình truy xuất dữ liệu' ));
            }elseif($result == 3) {
                $resultcmd = json_encode(array('RESULT' => 3, 'MESSAGE' => 'User '.$owner.' không sở hữu proxy-account !' ));
            }elseif($result == 0) {
                $resultcmd = json_encode(array('RESULT' => 0, 'MESSAGE' => 'Không thể kết nối tới database!!!' ));
            }else{
                $resultcmd = json_encode(array('RESULT' => 1, 'MESSAGE' => json_encode($result)));
            }
            break;
        
        default:
            # code...
            break;
    }
}
print_r($resultcmd);
?>
