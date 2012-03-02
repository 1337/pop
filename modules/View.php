<?php

    class View {
        //  View handles page templates (Views). put them inside TEMPLATE_PATH.
        var $contents;
        var $include_pattern;

        function __construct ($special = '') {
            // if $special (file name) is specified, then that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->resolve_template_name ($special); // returns full path
            $this->contents = $this->get_parsed ($template);
            
            // constants
            $this->include_pattern = '/<!--\s*include\s+"([^"]+)"\s*-->/';
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
            /* replace tags that look like
               <!-- include "header_and_footer.php" -->
               with their actual contents.
               
               replace_tags help recurse this function.
            */

            $matches = array (); // preg_match_all gives you an array of &$matches.
            if (preg_match_all ($this->include_pattern, $this->contents, $matches) > 0) {
                if (sizeof ($matches) > 0 && sizeof ($matches[1]) > 0) {
                    foreach ($matches[1] as $filename) { // [1] because [0] is full line
                        try {
                            $nv = new_object ($filename, 'View');
                            // replace tags in this contents with that contents
                            $this->contents = preg_replace ('/<!--\s*include\s+"' . addslashes ($filename) . '"\s*-->/', $nv->contents, $this->contents);
                            unset ($nv); // free memory
                        } catch (Exception $e) {
                            // include fail? fail.
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
                        // TODO
                    }
                }
            } else {
                // TODO
            }
        }
        
        public function replace_tags ($tags = array ()) {
            global $all_hooks;
            
            // populate tags
            list ($_era, $_ert) = get_handler_by_url ($_SERVER['REQUEST_URI'], true);
            $tags = array_merge (
                        array (
                            'title' => '',
                            'styles' => '',
                            'content' => '',
                            'root' => DOMAIN,
                            'subdir' => SUBDIR,
                            'handler' => "$_era.$_ert",
                            'memory_get_usage' => filesize_natural (memory_get_peak_usage ())
                        ), // "required" defaults
                        $all_hooks,
                        vars (), // environmental variables
                        $tags // custom tags
                    );
            
            // replacing will stop when there are no more <!-- include "tags" -->.
            do {
                $this->build_page (); // recursively include files (resolves include tags)
                
                // build tags array; replace tags with object props
                foreach ($tags as $tag => $data) {
                    $tags_processed[] = "/<!-- ?$tag ?-->/";
                    // data could have weird stuff like "true" or "array"
                    $values_processed[] = (string) $data;
                }
                
                // replace ALL the tags EXCEPT quoted ones (inherits),
                $this->contents = preg_replace ($tags_processed, $values_processed, $this->contents);
                unset ($tags_processed, $values_processed);
            } while (preg_match ($this->include_pattern, $this->contents) > 0);
            
            // then hide unmatched var tags
            $this->contents = preg_replace ("/<!-- ?([a-z0-9-_])+ ?-->/", '', $this->contents);
        }
        
        public function output () {
            // GZ buffering is handled elsewhere.
            if (class_exists ("Compressor")) {
                echo (Compressor::html_compress ($this->contents));
            } else {
                echo ($this->contents);
            }
        }
    }
?>
