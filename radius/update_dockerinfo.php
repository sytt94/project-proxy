<?php
include_once 'userinfo.php';
date_default_timezone_set('Asia/Ho_Chi_Minh');
$connection = mysqli_connect($sql_host, $sql_user, $sql_pass,$db);
// Hàm lấy danh sách các container, địa chỉ IP và trạng thái
function getDockerContainers() {
    // Chạy lệnh Docker để lấy thông tin container, địa chỉ IP và trạng thái
    $command = "docker inspect -f '{{ .Name }} {{range .NetworkSettings.Networks}}{{.IPAddress}}{{end}} {{.State.Status}}' $(docker ps -q)";
    $output = [];
    exec($command, $output);

    // Xử lý kết quả để hiển thị danh sách IP, tên container và trạng thái
    $containers = [];
    foreach ($output as $line) {
        list($name, $ip, $status) = explode(' ', $line, 3);
        $containers[] = ['name' => trim($name, '/'), 'ip' => $ip, 'status' => $status];
    }

    return $containers;
}

// Hàm lấy địa chỉ IP của server
function getServerIP() {
    // Chạy lệnh để lấy địa chỉ IP của cổng ens160
    $ip = shell_exec("ip -o -4 addr list ens160 | awk '{print $4}' | cut -d/ -f1");
    return trim($ip); // Loại bỏ khoảng trắng
}
// CREATE TABLE dockerinfo (
//     ip_server VARCHAR(45),
//     ip_container VARCHAR(45),
//     name_container VARCHAR(255),
//     status_container VARCHAR(45),
//     PRIMARY KEY (ip_server, ip_container)
// );
// Hiển thị danh sách container, IP và trạng thái
if (!$connection) {
    echo "Error Connection CSDL".mysqli_error($connection);
    exec('curl -d chat_id='.$tele_svcode_id.' -d text="[MYSQL-update-warehouse]-CONNECTION_SQL '.$argv[1].'" '.$tele_svcode_link.''); 
} else {
    $containers = getDockerContainers();
    $serverIP = getServerIP();
    #echo "Kết nối thành công đến database local";";
    mysqli_set_charset($connection,"utf8");
    //lam thong so import
    $temp = '';
    $temp_delete = '';
    foreach ($containers as $container) {
        $temp = $temp.'("'.$serverIP.'","'.$container['ip'].'","'.$container['name'].'","'.$container['status'].'"),';
        $temp_delete = $temp_delete.'("'.$serverIP.'","'.$container['ip'].'"),';
    }
    $value_import = rtrim($temp,',');
    $value_delete = rtrim($temp_delete,',');
    $sql = "INSERT INTO dockerinfo (ip_server, ip_container, name_container, status_container) VALUES ".$value_import." ON DUPLICATE KEY UPDATE  name_container = VALUES(name_container), status_container = VALUES(status_container)";
    if (mysqli_query($connection, $sql)) {
        echo "New record created successfully";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($connection);
    }
    $sql_delete =  "DELETE FROM dockerinfo WHERE (ip_server,ip_container) NOT IN (".$value_delete.") AND ip_server = '".$serverIP."'";
    if (mysqli_query($connection, $sql_delete)) {
        echo "DEL record successfully";
    } else {
        echo "Error: " . $sql_delete . "<br>" . mysqli_error($connection);
    }
    mysqli_close($connection);
}

?>
