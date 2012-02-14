<?php

    class View {
        //  View handles page templates (Views). put them inside TEMPLATE_PATH.
        var $contents;

        function __construct ($special = '') {
            // if $special (file name) is specified, then that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->resolve_template_name ($special); // returns full path
            $this->contents = $this->get_parsed ($template);
        }

        function get_parsed ($file) {
            ob_start();
            include ($file);
            $buffer = ob_get_contents();
            ob_end_clean();
            return $buffer;
        }

        function resolve_template_name ($special = '') {
            if (is_file (TEMPLATE_PATH . $special)) {
                return TEMPLATE_PATH . $special;
            } elseif (is_file (TEMPLATE_PATH . SITE_TEMPLATE)) {
                return TEMPLATE_PATH . SITE_TEMPLATE;
            } elseif (is_file (TEMPLATE_PATH . DEFAULT_TEMPLATE)) {
                return TEMPLATE_PATH . DEFAULT_TEMPLATE;
            } else {
                throw new Exception ('nope');
            }
        }

        public function build_page () {
            // recursively replace tags that look like
            // <!--inherit file="header_and_footer.php" -->
            // with their actual contents.
            $tag_pattern = '/<!--\s*inherit\s+file\s*=\s*"([^"]+)"\s*-->/';

            $matches = array ();

            // as long as there is still a tag left...
            if (preg_match_all ($tag_pattern, $this->contents, $matches) > 0) {
                if (sizeof ($matches) > 0 && sizeof ($matches[1]) > 0) {
                    foreach ($matches[1] as $filename) { // [1] because [0] is full line
                        if (is_file (TEMPLATE_PATH . $filename)) { // "file exists"
                            $nv = new_object ($filename, 'View');
                            $nv->build_page (); // call build_page on IT

                            // replace tags in this contents with that contents
                            $this->contents = preg_replace (
                                '/<!--\s*inherit\s+file\s*=\s*"' . addslashes ($filename) . '"\s*-->/', 
                                $nv->contents,
                                $this->contents
                            );
                            unset ($nv);
                        }
                    }
                }
                $matches = array (); // reset matches for next preg_match
            }
        }

        public function expand_page_loops ($tags = array ()) {
            $regex = "/<!-- ?for ([a-z0-9-_]+) in self.([a-z0-9-_]+) ?-->(.*)<!-- ?endfor ?-->/isU";
            // e.g. <!-- for i in self.objects --> bla bla bla <!-- endfor -->
            // i = case-insensitive, s = newlines included, U = non-greedy
            
            /* Array (
                [0] => Array (
                    [0] => <!-- for i in self.objects -->Hello<!-- endfor -->
                    [1] => <!-- for i2 in self.objects2 -->Hello2<!-- endfor -->
                )
            [1] => Array (
                    [0] => i
                    [1] => i2
                )
            [2] => Array (
                    [0] => objects
                    [1] => objects2
                )
            [3] => Array (
                    [0] =>Hello
                    [1] =>Hello2
                )
            ) */
            $matches = array ();
            if (preg_match_all ($regex, $this->contents, $matches)) {
                // foreach loop found...
                for ($i = 0; $i < sizeof ($matches[2]); $i++) {
                     // $len = sizeof objects2; number of times to loop
                    $len = sizeof ($tags[$matches[2][$i]]);
                    for ($j = 0; $j < $len; $j++) {
                        
                    }
                }
            } else {
                
            }
        }
        
        public function replace_tags ($tags = array ()) {
            $this->build_page (); // recursively include files
            if (sizeof ($tags) > 0) {
                // replace special tags (e.g. tags that must exist)
                $tags = array_merge (array (
                        'title' => '',
                        'content' => ''
                    ), $tags);
                
                // replace tags with object props
                foreach ($tags as $tag => $data) {
                    $data = (string) $data;
                    $data = (file_exists($data))     //decides on
                          ? $this->get_parsed ($data) //file replacement or
                          : $data;                   //string replacement.
                       
                    $this->contents = preg_replace ("/<!-- ?self\." . $tag . " ?-->/i", $data, $this->contents);
                }
                
                foreach (vars () as $tag => $data) { // replace dynamic vars ($_GET, $_POST, ...)
                    $data = (string) $data;
                    $this->contents = preg_replace ("/<!-- ?var\." . $tag . " ?-->/i", $data, $this->contents);
                }
                // hide unmatched var tags
                $this->contents = preg_replace ("/<!-- ?var\.([a-z0-9-_])+ ?-->/i", '', $this->contents);
                
                // $this->contents = str_ireplace("<!--root-->", DOMAIN, $this->contents);
                $this->contents = preg_replace ("/<!-- ?root ?-->/i", DOMAIN, $this->contents);
            }
        }
        
        public function output () {
            echo (html_compress ($this->contents));
        }
    }
?>
