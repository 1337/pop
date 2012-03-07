<?php
    require_once (dirname (__FILE__) . '/Model.php');

    class Sample extends Model {
        
        function handler_007 () {
            $this->FirstName = "James";
            $this->LastName = "Bond";
            $this->render (null, array (
                'content' => var_export ($this, true)
            ));
        }
    }
?>
