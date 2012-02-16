<?php
    class User extends MySQLModel {
        public static $urls = array (
            "user/log/?" => "user_tracker", // /pop/user/log
            "user/log/view/?" => "user_viewer" // /pop/user/log/view
        );
        
        function user_tracker () {
            // generate a UUID for the user if he/she doesn't have one yet.
            if (array_key_exists ('_uid', $_COOKIE)) {
                $uid = $_COOKIE['_uid'];
            } else {
                // defaults to month-long expiry.
                $uid = uniqid ('');
                setcookie ('_uid', $uid, time() + 86400 * 30);                
            }
            $user = new_object (null, 'User'); // create user using perm ID.
            
            // stuff you want to record.
            $session_data = array (
                'Referrer'         => @$_SERVER['HTTP_REFERER'],
                'User agent'       => @$_SERVER['HTTP_USER_AGENT'],
                'IP'               => @$_SERVER['REMOTE_ADDR'],
                'URL'              => @$_SERVER['REQUEST_URI'],
            );
            
            $user->id = $uid;
            $user->sessions_info = array_merge ((array) $user->sessions_info, array ($session_data));
            $user->put();
        }
    }
?>