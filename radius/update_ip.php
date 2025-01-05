<?php 
include('/home/ubuntu/userinfo.php');
date_default_timezone_set('Asia/Ho_Chi_Minh');
$connection = mysqli_connect($sql_host, $sql_user, $sql_pass,$db);
// Lấy tất cả các file có tên bắt đầu bằng abc và kết thúc bằng .log
$files = glob("/var/log/3proxy/3proxy*.log");

// Mảng để lưu tất cả các dòng
$lines = [];

// Duyệt qua các file
foreach ($files as $file) {
    // Lấy tất cả các dòng từ file
    $file_lines = file($file, FILE_IGNORE_NEW_LINES);
    
    // Thêm các dòng vào mảng
    $lines = array_merge($lines, $file_lines);
}

// Lấy 1000 dòng mới nhất
$latest_lines = array_slice($lines, -10);
// In ra các dòng mới nhất
foreach ($latest_lines as $line) {
    $temp = explode(' ',$line);
    $temp1 = explode(':',$temp[5]);
    $datetime = $temp[0].' '.$temp[1];
    $date = DateTime::createFromFormat('H:i:s d-m-Y', $datetime, new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Ho_Chi_Minh'));
    $formatted_datetime = $date->format('Y-m-d H:i:s');
    $result[str_replace('=','',$temp[3])][$temp1[0]][str_replace(']','',str_replace('[','',$temp[2]))] = $formatted_datetime;
}
//lam thong so import
#    $temp = '';
#    foreach ($result as $key => $value) {
#        foreach ($value as $key1 => $value1) {
#            foreach ($value1 as $key2 => $value2) {
#                $temp = $temp.'("'.$key.'","'.$key1.'","'.$key2.'","'.date("Y-m-d H:i:s").'"),';
#            }
#        }
#    }
#    $value_import = rtrim($temp,',');
if (!$connection) {
    echo "Error Connection CSDL".mysqli_error($connection);
    exec('curl -d chat_id='.$tele_svcode_id.' -d text="[MYSQL-update-warehouse]-CONNECTION_SQL '.$argv[1].'" '.$tele_svcode_link.''); 
} else {
    global $connection;
    #echo "Kết nối thành công đến database local";";
    mysqli_set_charset($connection,"utf8");
    //lam thong so import
    $temp = '';
    foreach ($result as $key => $value) {
      foreach ($value as $key1 => $value1) {
        foreach ($value1 as $key2 => $value2) {
          $temp = $temp.'("'.$key.'","'.$key1.'","'.$key2.'","'.$value2.'"),';
        }
      }
    }
    $value_import = rtrim($temp,',');
    $sql = "INSERT INTO radIPlogin ( username, ip_client, nas, lastupdate) VALUES ".$value_import." ON DUPLICATE KEY UPDATE lastupdate = VALUES(lastupdate)";
    echo $sql;
    if (mysqli_query($connection, $sql)) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($connection);
        exec('curl -d chat_id='.$tele_svcode_id.' -d text="[MYSQL-update-warehouse]-MYSQL_QUERY '.$argv[1].'" '.$tele_svcode_link.''); 
    }
    mysqli_close($connection);
}




?>
