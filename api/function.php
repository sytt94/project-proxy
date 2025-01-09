<?php 
header("Content-type: text/json");
header("Access-Control-Allow-Origin: *");
include_once 'userinfo.php';
include("routeros_api2.class.php");
//ghi nhan src/dst NAT
function showmik($sql_host,$sql_user,$sql_pass,$sql_db,$host,$mik_user,$mik_pass){
    $API = new RouterosAPI();
    $API->debug = false;
    if ($API->connect($host, $mik_user, $mik_pass)) {
        $ip = $API->comm("/ip/address/print");
        foreach ($ip as $key => $value) {
            $temp = explode('/',$value['address']);
            $ip[] = $temp[0];
        }
        $ip_mik = $API->comm("/ip/firewall/nat/print");
        foreach ($ip_mik as $key => $value) {
            if ($value['chain'] == 'srcnat' && $value['action'] == 'src-nat') {
                if (in_array($value['to-addresses'],$ip)) {
                    $result[$value['to-addresses']][$value['src-address']] = $host;
                }
            }
        }
        $connection = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
        if (!$connection) {
            return 0;
            exit;
        } else {
            mysqli_set_charset($connection,"utf8");
            $temp = '';
            $temp_delete = '';
            foreach ($result as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    $temp = $temp.'("'.$key.'","'.$key1.'","'.$value1.'"),';
                    $temp_delete = $temp_delete.'("'.$key.'"),';
                }                
            }
            $value_import = rtrim($temp,',');
            $value_delete = rtrim($temp_delete,',');
            $sql = "INSERT INTO srcnat (externalIP, localIP, nas) VALUES ".$value_import." ON DUPLICATE KEY UPDATE  externalIP = VALUES(externalIP), nas = VALUES(nas)";
            if (mysqli_query($connection, $sql)) {
                echo "New record created successfully";
            } else {
                echo "Error: " . $sql . "<br>" . mysqli_error($connection);
            }
            $sql_delete =  "DELETE FROM srcnat WHERE (externalIP) NOT IN (".$value_delete.") AND nas = '".$host."'"; 
            if (mysqli_query($connection, $sql_delete)) {
                echo "DEL record successfully";
            } else {
                echo "Error: " . $sql_delete . "<br>" . mysqli_error($connection);
            }
        }
        mysqli_close($connection); 
    } else {
        echo "Lỗi kết nối Mik: ".$host."";
    }
    
}

function addlistuser ($sql_host,$sql_user,$sql_pass,$sql_db,$username,$pass,$owner,$count){
    $connection = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
    if (!$connection) {
        return 0;
        exit;
    } else {
        mysqli_set_charset($connection,"utf8");
        $sql = "SELECT * FROM srcnat";
        $query = mysqli_query($connection, $sql);
        if ($query) {
            if ($query->num_rows < $count) {
                return 5; // khong đủ IP cấp
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    $arr_bng_host[] = $data['externalIP'];  
                }
                shuffle($arr_bng_host);
                $creationdate = date('Y-m-d H:i:s');
                $sql = "SELECT * FROM radcheck WHERE username LIKE '%".$username."-%'";
                $query = mysqli_query($connection, $sql);
                if ($query) {
                    if ($query->num_rows == 0) {
                        # username chua co tao lần nào sub-index bắt đầu từ 001
                        $user_arr = [];
                        for ($i = 1; $i <= $count; $i++){
                            $user_arr[] = sprintf("".$username."-%03d", $i);
                        }
                        $temp = '';
                        $temp_info = '';
                        foreach ($user_arr as $key => $value) {
                            $temp = $temp.'("'.$value.'","Cleartext-Password",":=","'.$pass.'"),';
                            $temp_info = $temp_info.'("'.$value.'","'.$username.'","'.$owner.'","'.$creationdate.'","'.$arr_bng_host[$key].'","'.$owner.'","'.$creationdate.'"),';
                        }
                        $value_import = rtrim($temp,',');
                        $sql = "INSERT INTO radcheck (username,attribute,op,value) VALUES ".$value_import."";
                        $query = mysqli_query($connection, $sql);
                        if ($query === true) {
                            $value_import_info = rtrim($temp_info,',');
                            $sql = "INSERT INTO userinfo (username,firstname,company,creationdate,notes,creationby,updatedate) VALUES ".$value_import_info."";
                            $query = mysqli_query($connection, $sql);
                            if ($query === true) {
                                return 1; //add thành công
                            } else {
                                if (mysqli_errno($connection) == 1062) { // Lỗi trùng key
                                    return 3; // Lỗi trùng key
                                } else {
                                    return 2; // lỗi sql_query
                                }
                            } 
                        } else {
                            if (mysqli_errno($connection) == 1062) { // Lỗi trùng key
                                return 3; // Lỗi trùng key
                            } else {
                                return 2; // lỗi sql_query
                            }
                        }
                        
                    } else {
                        return 4; //account đã tồn tại
                    }
                    
                } else {
                    return 2; // loi sql
                }
                }
            
        } else {
            return 2; // loi sql
        }        
    }
    mysqli_close($connection); 
}

function exportlistuser($sql_host,$sql_user,$sql_pass,$sql_db,$username) {
    $connection = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
    if (!$connection) {
        return 0;
        exit;
    } else {
        mysqli_set_charset($connection,"utf8");
        $sql = "SELECT userinfo.username, radcheck.value, userinfo.notes FROM userinfo JOIN radcheck ON userinfo.username = radcheck.username WHERE userinfo.username LIKE '".$username."-%'";
        $query = mysqli_query($connection, $sql);
        if ($query) {
            $result = '';
            while ($data = mysqli_fetch_array($query)) {
                $result = $result."\n".$data['notes'].':10012:'.$data['username'].':'.$data['value'];  
            }
            echo $result;
        } else {
            return 2; // loi sql
        }
        
    }
    mysqli_close($connection); 
}

function showuserall ($sql_host,$sql_user,$sql_pass,$sql_db,$owner){
    $connection = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
    if (!$connection) {
        return 0;
        exit;
    } else {
        mysqli_set_charset($connection,"utf8");
        $sql = "SELECT userinfo.username, radcheck.value, userinfo.notes, (SELECT reason FROM radpostauth WHERE radpostauth.username = userinfo.username ORDER BY authdate DESC LIMIT 1) AS lastlog , (SELECT reply FROM radpostauth WHERE radpostauth.username = userinfo.username ORDER BY authdate DESC LIMIT 1) AS reply,userinfo.creationdate, userinfo.updatedate FROM userinfo JOIN radcheck ON userinfo.username = radcheck.username WHERE userinfo.company LIKE '".$owner."'";
        $query = mysqli_query($connection, $sql);
        if ($query) {
            if ($query->num_rows == 0) {
                # chua so huu proxy-user
                return 3; // chua so huu proxy
            } else {
                while ($data = mysqli_fetch_array($query)) {
                    $date = new DateTime($data['updatedate']);
                    $date->modify('+30 days');
                    $expireday = $date->format('d-m-Y');
                    $createdate = new DateTime($data['creationdate']);
                    $createdate = $createdate->format('H:i:s d-m-Y');
                    $result[$data['username']]['password'] = $data['value'];
                    $result[$data['username']]['url'] = $data['notes'].':10012';
                    if ($data['reply'] == 'Access-Accept') {
                        $result[$data['username']]['reply'] = 1;
                    } else {
                        $result[$data['username']]['reply'] = 0;
                    }
                    $result[$data['username']]['lastlog'] = $data['lastlog'];
                    $result[$data['username']]['creationdate'] = $createdate;
                    $result[$data['username']]['expire'] = $expireday;
                }
                return $result;
            }
        } else {
            return 2; // loi sql
        }
        
    }
    mysqli_close($connection); 
}
########tạo list user##############
// $username = 'toancr';
// $pass = 'toancr123';
// $owner = 'toancr';
// $count = 120;
// $result = addlistuser ($sql_host,$sql_user,$sql_pass,$sql_db,$username,$pass,$owner,$count);
#########export list user###########
//$username = 'sytt';
//$result = exportlistuser ($sql_host,$sql_user,$sql_pass,$sql_db,$username);
#########show list user###########
// $owner = 'toancr';
// $result = showuserall ($sql_host,$sql_user,$sql_pass,$sql_db,$owner);
// print_r($result);

##########update IP PUBLIC##############
#$host = '172.31.1.1';
#$result = showmik($sql_host,$sql_user,$sql_pass,$sql_db,$host, $mik_user, $mik_pass);
#print_r($result);

?>
