<?php

class Sample extends Pop\Model {

    function handler_007() {
        $this->FirstName = "James";
        $this->LastName = "Bond";
        $this->render(null, [
            'content' => var_export($this, true)
        ]);
    }
}