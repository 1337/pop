<?php

    class View {
        //  View handles page templates (Views). put them inside TEMPLATE_PATH.
        var $contents;
        var $include_pattern, $forloop_pattern;

        function __construct ($special = '') {
            // if $special (file name) is specified, then that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->resolve_template_name ($special); // returns full path
            $this->contents = $this->get_parsed ($template);
            
            // constants
            $this->include_pattern = '/<!-- ?include ?"([^"]+)" ?-->/';
            $this->forloop_pattern = "/<!-- ?for ([a-zA-Z0-9-_]+), ?([a-zA-Z0-9-_]+) in ([a-zA-Z0-9-_]+) ?-->(.*)<!-- ?endfor ?-->/sU";
        }
        
        function __toString () {
            // GZ buffering is handled elsewhere.
            if (class_exists ("Compressor")) {
                return (Compressor::html_compress ($this->contents));
            } else {
                return ($this->contents);
            }
        }

        function get_parsed ($file) {
            ob_start();
            if (strpos ($file, TEMPLATE_PATH) === false) {
                $file = TEMPLATE_PATH . $file;
            }
            
            if (file_exists ($file)) {
                @include ($file);
            } else {
                // file not found
                debug ("File $file not found");
            }
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

        private function include_snippets () {
            /* replace tags that look like
               <!-- include "header_and_footer.php" -->
               with their actual contents.
               
               replace_tags help recurse this function.
            */
            $matches = array (); // preg_match_all gives you an array of &$matches.
            if (preg_match_all ($this->include_pattern, $this->contents, $matches) > 0) {
                if (sizeof ($matches) > 0 && sizeof ($matches[1]) > 0) {
                    foreach ($matches[1] as $index => $filename) { // [1] because [0] is full line
                        try {
                            $nv = $this->get_parsed ($filename);
                        } catch (Exception $e) { // include fail? fail.
                            $nv = '';
                        }
                        // replace tags in this contents with that contents
                        $this->contents = str_replace ($matches[0][$index], $nv, $this->contents);
                        unset ($nv); // free memory
                    }
                }
            }
        }

        private function expand_page_loops ($tags = array ()) {
            $regex = $this->forloop_pattern;
            // e.g. <!-- for i in objects --> bla bla bla <!-- endfor -->
            // i = case-insensitive, s = newlines included, U = non-greedy
            // defaults to case-sensitive.
            
            $matches = array ();
            preg_match_all ($regex, $this->contents, $matches);
            for ($i = 0; $i < sizeof ($matches[0]); $i++) { // each match
                $buffer = ''; // stuff to be printed
                // replace tags within the inner loop, n times
                if (array_key_exists ($matches[3][$i], $tags)) { // if such tag exists
                    $match_keys = array_keys ($tags[$matches[3][$i]]);
                    $match_vals = array_values ($tags[$matches[3][$i]]);
                    
                    // number of times the specific match is to be repeated
                    for ($lc = 0; $lc < sizeof ($tags[$matches[3][$i]]); $lc++) {
                        // now, replace the key and value
                        $buffer .= preg_replace (
                            array ( // search
                                "/<!-- ?" . preg_quote ($matches[1][$i], '/') . " ?-->/sU", // key
                                "/<!-- ?" . preg_quote ($matches[2][$i], '/') . " ?-->/sU"  // value
                            ), 
                            array ( // replace
                                (string) $match_keys[$lc],
                                (string) $match_vals[$lc]
                            ), 
                            $matches[4][$i] // loop content
                        );
                    }
                } // else: even if value doesn't exist, remove the tag.

                // str_replace is faster
                $this->contents = str_replace ($matches[0][$i], $buffer, $this->contents);
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
                    'base' => DOMAIN . '/' . SUBDIR,
                    'handler' => "$_era.$_ert",
                    'memory_usage' => filesize_natural (memory_get_peak_usage ())
                ), // "required" defaults
                $all_hooks, // how are you going to use these?
                vars (), // environmental variables
                $tags // custom tags
            );
            
            // build tags array; replace tags with object props
            foreach ($tags as $tag => $data) {
                $tags_processed[] = "/<!-- ?$tag ?-->/";
                $values_processed[] = (string) $data; // "abc", "true" or "array"
            }

            // replacing will stop when there are no more <!-- include "tags" -->.
            while (preg_match ($this->include_pattern, $this->contents) > 0 ||
                   preg_match ($this->forloop_pattern, $this->contents) > 0) {

                $this->include_snippets (); // recursively include files (resolves include tags)
                $this->expand_page_loops ($tags);
                
                // replace all variable tags
                $this->contents = preg_replace ($tags_processed, $values_processed, $this->contents);
            } // remember, replacement may generate new include tags
            unset ($tags_processed, $values_processed); // free ram
            
            // then hide unmatched var tags
            $this->contents = preg_replace ("/<!-- ?([a-z0-9-_])+ ?-->/", '', $this->contents);
            return $this; // chaining
        }
    }
?>