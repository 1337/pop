<?php
    require_once (MODULE_PATH . 'Model.php');

    class User extends Model {
        
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
            $payload = array (
                'Referrer'      => isset ($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '',
                'User agent'    => isset ($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
                'IP'            => isset ($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '',
                'URL'           => isset ($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '',
                'Action'        => vars ('action', 'PageLoad')
            );
            
            $user->id = $uid;
            /* $user->sessions_info = array_merge (
                (array) $user->sessions_info, 
                 array ($payload)
            ); */
            $user->sessions_info = (array) $user->sessions_info + array ($payload);
        }
        
        function user_viewer () {
            // generate a UUID for the user if he/she doesn't have one yet.
            $this->id = vars ('id', $_COOKIE['_uid']);
            
            $urls = array ();
            foreach ((array) $this->sessions_info as $idx => $session) {
                $urls[] = $session['Referrer'];
            }
            $this->render(array (
                'title' => 'Your browsing history',
                'sessions' => $urls ,
                'content' => '<!-- include "templates/view_user.html" -->'
            ));
        }
        
        function clear_user_log () {
            if (isset ($_COOKIE['_uid'])) {
                $uid = $_COOKIE['_uid'];
            } else {
                // defaults to month-long expiry.
                $uid = uniqid ('');
                setcookie ('_uid', $uid, time() + 86400 * 30);
            }
            $user = new_object ($uid, 'User'); // create user using perm ID.
            $user->sessions_info = array ();
            
            if (isset ($_SERVER['HTTP_REFERER'])) {
                header ('location: ' . $_SERVER['HTTP_REFERER']);
            }
        }
    }
?>
