<?php
set_include_path("../includes/".PATH_SEPARATOR.get_include_path());
require_once("../includes/DB.php");
require_once("../inside/credentials.php");


/* Set HTTP response code */
if( !function_exists('set_response_code') ) {
    function set_response_code($code)
    {
        if (!is_int($code)) {
            return false;
        }
        header('X-Ignore-This: something', true, $code);
    }
}


function return_json_response($data, $response_code=200) {
    if($response_code != 200) {
        set_response_code($response_code);
    }
    die(json_encode($data));
}


function is_valid_utf8($text) {
    return mb_check_encoding($text, 'utf-8');
}


function get_db_connection($fetch_mode=NULL, $options=NULL) {
    if($options == NULL) {
        $options = array(
            'debug'       => 2,
            'portability' => DB_PORTABILITY_ALL,
        );
    }

    $conn = DB::connect(getDSN(), $options);

    if(DB :: isError($conn)) {
        echo $conn->toString();
    } else {
        if($fetch_mode != NULL) {
            $conn->setFetchMode($fetch_mode);
        }
    }

    return $conn;
}


if( !function_exists('clean_phonenumber') ) {
    function clean_phonenumber($pn) {
        $pn = preg_replace('/[^0-9\+]/', '', $pn); // remove everything except valid chars
        $pn = preg_replace('/^00/', '+', $pn); // replace starting 00 with +
        // norwegian phone numbers
        if (strlen($pn) === 8 && ($pn[0] === "4" || $pn[0] === "9")) {
            $pn = "+47" . $pn;
        }
        // ...without +
        if (strlen($pn) === 10 && $pn[0] === "4" && $pn[1] === "7" && ($pn[2] === "4" || $pn[2] === "9")) {
            $pn = "+" . $pn;
        }

        return $pn;
    }
}


if( !function_exists('valid_phonenumber') ) {
    // E.164
    function valid_phonenumber($phone) {
        return preg_match('/^\+?\d{8,15}$/i', $phone);
    }
}


function get_user_data($ids, $conn) {
    if( !is_string($ids) ) {
        new Exception('Param ids should be a comma separated string of user ids');
    }
    $ACTIVE_GROUP_ID = "2";
    $conn->setFetchMode(DB_FETCHMODE_ASSOC);

    /* Get data */
    $sql_is_member = "u.expires > NOW() OR u.expires IS NULL AS is_member";
    $CARDNO_LEGACY_MAX = 100000000;
    $sql_card_is_legacy = "u.cardno<$CARDNO_LEGACY_MAX AS card_is_legacy";
    $sql_groups = "GROUP_CONCAT(DISTINCT g.id,';',g.name SEPARATOR ',') AS groups";
    $data_sql = "SELECT u.id,u.username,u.firstname,u.lastname,u.email,up.number,u.expires,u.cardno,$sql_card_is_legacy,$sql_groups,$sql_is_member
    FROM din_user as u
    LEFT JOIN din_userphonenumber as up ON up.user_id=u.id
    LEFT JOIN din_usergrouprelationship AS ug ON u.id=ug.user_id
    LEFT JOIN din_group AS g ON g.id=ug.group_id
    WHERE u.id IN ($ids)
    GROUP BY u.id
    ORDER BY u.firstname, u.lastname ASC";

    $res = $conn->getAll($data_sql);

    if( DB::isError($res) ) {
        new Exception($res->getMessage());
    }
    $results = array();

    /* Encode and output */
    foreach($res as $result) {
        $is_active = "0";
        foreach($result as $key => $value) {
            if($key == "groups") {
                if($value === "") {
                    continue; // no groups
                }
                $u_gs = array();
                $u_groups = explode(",",$value);
                foreach($u_groups as $g) {
                    list($id,$name) = explode(";", $g);
                    if( !is_valid_utf8($name) ) {
                        $name = utf8_encode($name);
                    }
                    if($id == $ACTIVE_GROUP_ID) {
                        $is_active = "1";
                    }

                    $u_gs[] = array(
                        'id' => $id,
                        'name' => $name
                    );
                }
                $result[$key] = $u_gs;
            }
            /* Encoding issues? oh yes, utf-8 please */
            elseif( !is_valid_utf8($value) ) {
                $result[$key] = utf8_encode($value);
            }
        }
        $result['is_active'] = $is_active;
        $results[] = $result;
    }
    return $results;
}

function update_card($user_id, $card_number) {
    /* Update card number on user */

}
function get_user_id_by_card_number($card_number) {
    $conn = get_db_connection(DB_FETCHMODE_ORDERED);

    $card_number = $conn->quoteSmart($card_number);
    $sql = "SELECT mc.userId FROM din_membercard AS mc
      LEFT JOIN din_user AS u ON mc.userId=u.id
      WHERE mc.id=$card_number";

    $res = $conn->getAll($sql);

    if( DB::isError($res) ) {
        new Exception($res->getMessage());
    }
    if( count($res) === 0 ) {
        return NULL;
    }
    return $res[0][0];
}