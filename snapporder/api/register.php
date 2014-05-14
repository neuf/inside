<?php 
/* 
 * Registers a user in the user table
 *
 * Request:
 *
 * $ curl /snapporder/api/register.php 
 * {
 *   "phone":12345678,
 *   "firstName": "Jon",
 *   "lastName": "Hansen",
 *   "email": "jon@uio.no"
 * }
 *
 * Response:
 *
 * {
 *   "phone":12345678,
 *   "memberStatus":1,
 *   "endDate": "2015-04-23" ,
 *   "membershipNumber": "dsadsa2333",
 *   "firstName": "Jon",
 *   "lastName": "Hansen",
 *   "email": "jon@uio.no",
 *   "registration_status": "partial" // "partial" or "full"
 * }
 *
 * TODO:
 * - register user with fromSnappOrder flag
 * - return result
 */

/* Pull in Inside */
set_include_path("../../inside/");
require_once("functions.php");
require_once("User.php");
set_include_path("../../includes/");
require_once("DB.php");

/* Our own */
require_once("../lib/CryptoHelper.php");
require_once("../lib/functions.php");
require_once("../config.php");

/* Validate request */
$body = file_get_contents('php://input');
if(strlen($body) === 0) {
    set_response_code(400);
    echo json_encode(array('error' => 'Invalid request'));
    die();
}

/* Decrypt body */
// TODO enable
//$crypt = new CryptoHelper(SNAP_IV, SNAP_KEY);
//$body = $crypt->decrypt($body);
$data = (array) json_decode($body);
if($data === NULL) {
    set_response_code(400);
    echo json_encode(array('error' => 'Can\'t decode body'));
    die();
}

/* Validate supplied data */
$required_keys = array('firstname', 'lastname', 'phone', 'email');

foreach($required_keys as $key) {
    if(!array_key_exists($key, $data)) {
        set_response_code(400);
        echo json_encode(array('error' => 'missing required field \''.$key.'\''));
        die();
    }
}
var_dump($data);

if( !valid_phonenumber($data['phone']) ) {
    set_response_code(400);
    echo json_encode(array('error' => 'Not a phone number:'.$data['phone']));
    die();
}
if( !valid_email($data['email']) ) {
    set_response_code(400);
    echo json_encode(array('error' => 'Not an email: '.$data['email']));
    die();
}
/* Existing user? */
if( getUseridFromEmail($data['email']) !== false) {
    set_response_code(409);
    echo json_encode(array('error' => 'Existing user with email: '.$data['email']));
    die();
}
if( getUseridFromPhone($data['phone']) !== false ) {
    set_response_code(409);
    echo json_encode(array('error' => 'Existing user with phone: '.$data['phone']));
    die();
}

/* Create user */
$user_id = add_user($data);

/* Get and format user */
$user = get_user($user_id);

/* Return encrypted user object */
//echo json_encode($user);
//echo $crypt->encrypt(json_encode($user));
 
?>
