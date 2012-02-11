<?php
    class DOM extends Model {
        var $id; // prevent writing object into DB
        
        // this thing generates HTML tags.
        public function __toString ($tag_name, $tag_contents = null) {
            if (is_null ($tag_contents)) {
                $tag_contents = $this->contents;
            }
            $str = "<$tag_name"; // $this->id/name cannot be used because...
            foreach ($this->properties () as $property_name) {
                $property_value = htmlentities ($this->{$property_name});
                $str .= " $property_name=\"$property_value\"";
            }
            foreach ($this->properties['dataset'] as $dataset_name) {
                $dataset_value = htmlentities ($this->properties['dataset'][$dataset_name]);
                $str .= " data-$dataset_name=\"$dataset_value\"";
            }
            $str .= ">$tag_contents</$tag_name>";
            return $str;
        }
    }
?>