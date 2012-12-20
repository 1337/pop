<?php
    class AjaxField extends Model {

        public function make ($object, $property, $mode = 'text') {
            $k = $object->get_hash ('write');
            $i = $object->id;
            $t = $object->type;
            $v = htmlspecialchars ($object->$property, ENT_QUOTES);

            switch ($mode) {
                case 'option':
                    break;
                case 'textarea':
                    return "<textarea class='ajax' data-id='$i' data-type='$t' data-prop='$property' data-key='$k'>$v</textarea>";
                default:
                    // text, password, email, checkbox, hidden
                    return "<input class='ajax' data-id='$i' data-type='$t' data-prop='$property' data-key='$k' type='$mode' value='$v' />";
            }
        }
    }