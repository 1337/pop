<?php
    class Sample extends Model {
        public static $urls = array (
            "/?" => "handler_007",
        );
        
        function handler_007 () {
            $this->FirstName = "James";
            $this->LastName = "Bond";
            $this->render ();
        }
    }
?>