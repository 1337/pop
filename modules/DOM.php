<?php
    class DOM extends Model {
        var $id; // prevent writing object into DB
        
        // this thing generates HTML tags.
        public function __toString () {
            $tag_name = $this->tagname;
            $tag_contents = $this->contents;
            $str = "<$tag_name"; // $this->id/name cannot be used because...
            $properties = array_merge ($this->properties (), array ('id'));
            foreach ($properties as $property_name) {
                switch (strtolower ($property_name)) {
                    case 'dataset':
                        $dataset_value = htmlentities ($this->properties['dataset'][$dataset_name]);
                        $str .= " data-$dataset_name=\"$dataset_value\"";
                        break;
                    case 'tag_name':
                    case 'contents':
                        break; // do nothing.
                    default:
                        $property_value = htmlentities ($this->{$property_name});
                        $str .= " $property_name=\"$property_value\"";
                }
            }
            $str .= ">$tag_contents</$tag_name>";
            return $str;
        }
    }
?>