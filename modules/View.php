<?php
    @include_once (dirname (__FILE__) . '/Compressor.php');

    class View {
        //  View handles page templates (Views). put them inside VIEWS_PATH.
        var $contents, $ot, $ct, $vf;
        var $include_pattern, $forloop_pattern, $if_pattern, $listcmp_pattern;

        function __construct ($special = '') {
            // if $special (file name) is specified, then that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->resolve_template_name ($special); // returns full path
            $this->contents = $this->get_parsed ($template);
            
            // constants default to case-sensitive
            if (defined ('EXTRA_TEMPLATE_TAG_FORMATS') && EXTRA_TEMPLATE_TAG_FORMATS === true) {
                $ot = $this->ot = '(<!--|{[{%])';       // opening tag
                $ct = $this->ct = '([}%]}|-->)';        // close tag
            } else {
                // limit to just html comment tags: slightly faster
                $ot = $this->ot = '(<!--)';       // opening tag
                $ct = $this->ct = '(-->)';        // close tag
            }
            $vf = $this->vf = '([_a-zA-Z0-9\.]+)'; // variable format

            $this->include_pattern = "/$ot ?include ?\"([^\"]+)\" ?$ct/U";
            $this->forloop_pattern = "/$ot ?for $vf, ?$vf in $vf ?$ct(.*)$ot ?endfor ?$ct/sU";
            $this->if_pattern      = "/$ot ?if $vf ?$ct(.*)(($ot ?elseif $vf ?$ct(.*))*)($ot ?else ?$ct(.*))*$ot ?endif ?$ct/sU";
            $this->listcmp_pattern = "/$ot ?$vf ?in ?$vf ?$ct/sU";
        }
        
        function __toString () {
            // GZ buffering is handled elsewhere.
            if (class_exists ("Compressor") && TEMPLATE_COMPRESS === true) {
                return (Compressor::html_compress ($this->contents));
            } else {
                return ($this->contents);
            }
        }

        function get_parsed ($file) {
            if (strpos ($file, VIEWS_PATH) === false) {
                $file = VIEWS_PATH . $file;
            }
            
            if (TEMPLATE_SAFE_MODE === false) { // PHP tags don't work in safe mode.
                ob_start();
                // open_basedir
                if (@is_file ($file)) {
                    @include ($file);
                } else {
                    // file not found
                    debug ("File $file not found");
                }
                $buffer = ob_get_contents();
                ob_end_clean();
            } else {
                try {
                    $buffer = @file_get_contents ($file);
                } catch (Exception $e) {
                    $buffer = '';
                }
            }
            return $buffer;
        }

        function resolve_template_name ($special = '') {
            if (is_file (VIEWS_PATH . $special)) {
                return VIEWS_PATH . $special;
            } elseif (is_file (VIEWS_PATH . SITE_TEMPLATE)) {
                return VIEWS_PATH . SITE_TEMPLATE;
            } elseif (is_file (VIEWS_PATH . DEFAULT_TEMPLATE)) {
                return VIEWS_PATH . DEFAULT_TEMPLATE;
            } else {
                throw new Exception ('nope');
            }
        }

        private function include_snippets () {
            /* replace tags that look like
               <!-- include "header_and_footer.html" -->
               with their actual contents.
               
               replace_tags help recurse this function.
            */
            $matches = array (); // preg_match_all gives you an array of &$matches.
            if (preg_match_all ($this->include_pattern, $this->contents, $matches) > 0) {
                if (sizeof ($matches) > 0 && sizeof ($matches[2]) > 0) {
                    foreach ($matches[2] as $index => $filename) { // [1] because [0] is full line
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

        private function expand_list_comprehension () {
            // e.g. <!-- object in objects -->
            $this->contents = preg_replace ($this->listcmp_pattern, '<!-- for _lop,_$2 in $3 --><!-- _$2 --><!-- endfor -->', $this->contents);
        }

        private function expand_page_loops ($tags = array ()) {
            $ot = $this->ot;
            $ct = $this->ct;
            $regex = $this->forloop_pattern;
            // e.g. <!-- for i in objects --> bla bla bla <!-- endfor -->
            
            $matches = array ();
            preg_match_all ($regex, $this->contents, $matches);
            for ($i = 0; $i < sizeof ($matches[0]); $i++) { // each match
                $buffer = ''; // stuff to be printed
                // replace tags within the inner loop, n times
                if (array_key_exists ($matches[4][$i], $tags) && 
                    is_array ($tags[$matches[4][$i]])) { // if such tag exists
                    $match_keys = array_keys ($tags[$matches[4][$i]]);
                    $match_vals = array_values ($tags[$matches[4][$i]]);
                    
                    // number of times the specific match is to be repeated
                    for ($lc = 0; $lc < sizeof ($tags[$matches[4][$i]]); $lc++) {
                        // now, replace the key and value
                        $buffer .= preg_replace (
                            array ( // search
                                "/$ot ?" . preg_quote ($matches[2][$i], '/') . " ?$ct/sU", // key
                                "/$ot ?" . preg_quote ($matches[3][$i], '/') . " ?$ct/sU"  // value
                            ), 
                            array ( // replace
                                (string) $match_keys[$lc],
                                (string) $match_vals[$lc]
                            ), 
                            $matches[6][$i] // loop content
                        );
                    }
                } // else: even if value doesn't exist, remove the tag.

                // str_replace is faster
                $this->contents = str_replace ($matches[0][$i], $buffer, $this->contents);
            }
        }
        
        private function resolve_if_conditionals ($tags = array ()) {
            $regex = $this->if_pattern;
            // e.g. <!-- if a -->b<!-- elseif c -->d<!-- elseif e -->f<!-- else g -->h<!-- endif -->
            
            $matches = array ();
            preg_match_all ($regex, $this->contents, $matches);
            for ($i = 0; $i < sizeof ($matches[0]); $i++) { // each match
                
                if (isset ($tags[$matches[2][$i]]) && 
                    $tags[$matches[2][$i]]) { // if <!-- if ? --> evals to true
                    // replace whole thing with the true part:
                    $this->contents = str_replace (
                        $matches[0][$i], // search
                        $matches[4][$i], // replace
                        $this->contents  // subject
                    );
                
                
                // expand here when ready to do multiple elseif statements //
                
                
                } elseif (isset ($tags[$matches[8][$i]]) && 
                           strlen ($matches[8][$i]) > 0 && // if no <!-- elseif ? -->, this is empty
                           $tags[$matches[8][$i]]) { // if <!-- elseif ? --> evals to true
                    // replace whole thing with the true part:
                    $this->contents = str_replace (
                        $matches[0][$i],  // search
                        $matches[10][$i], // replace
                        $this->contents   // subject
                    );
                } elseif (isset ($matches[14][$i]) && 
                           strlen ($matches[14][$i]) > 0) {// if no <!-- else -->?, this is empty
                    // since this is else, replace whole thing with the true part:
                    $this->contents = str_replace (
                        $matches[0][$i],  // search
                        $matches[14][$i], // replace
                        $this->contents   // subject
                    );
                } else { // you hit this if nothing is true and no <!-- else --> available
                    $this->contents = str_replace (
                        $matches[0][$i],  // search
                        '',               // replace
                        $this->contents   // subject
                    );
                }
            }
        }
        
        public function replace_tags ($tags = array ()) {
            global $all_hooks;
            $ot = $this->ot;
            $ct = $this->ct;
            $vf = $this->vf;
            
            // populate tags
            list ($_era, $_ert) = get_handler_by_url ($_SERVER['REQUEST_URI'], false);
            $tags = array_merge (
                array (
                    '__cacheable' => false,
                    'title' => '',
                    'styles' => '',
                    'content' => '',
                    'root' => DOMAIN,
                    'subdir' => SUBDIR,
                    'base' => DOMAIN . SUBDIR, // so, pop dir
                    'handler' => $_era ? "$_era.$_ert" : '',
                    'memory_usage' => filesize_natural (memory_get_peak_usage ()),
                    'exec_time' => round (microtime(true) - EXEC_START_TIME, 4)*1000 . 'ms',
                    'year' => date ("Y"),
                ), // "required" defaults
                vars (), // environmental variables
                $tags // custom tags
            );
            
            // build tags array; replace tags with object props
            foreach ($tags as $tag => $data) {
                $tags_processed[] = "/$ot ?$tag ?$ct/U";
                $values_processed[] = (string) $data; // "abc", "true" or "array"
            }

            // replacing will stop when there are no more <!-- include "tags" -->.
            while (preg_match_multi (
                        array ($this->include_pattern, 
                               $this->forloop_pattern,
                               $this->if_pattern,
                               $this->listcmp_pattern), 
                        $this->contents)) {
                $this->include_snippets (); // recursively include files (resolves include tags)
                $this->expand_list_comprehension ();
                $this->expand_page_loops ($tags);
                $this->resolve_if_conditionals ($tags);
                
                // replace all variable tags
                // remember, replacement may generate new include tags
                $this->contents = preg_replace ($tags_processed, $values_processed, $this->contents);
            }
            unset ($tags_processed, $values_processed); // free ram
            
            // then hide unmatched var tags
            $this->contents = preg_replace ("/$ot ?$vf ?$ct/U", '', $this->contents);
            return $this; // chaining
        }
    }
    
    if (!function_exists ('render')) {
        function render ($options = array (), $template = '') {
            // that's why you ob_start at the beginning of Things.
            $content = ob_get_contents (); ob_end_clean ();
            
            // $etag = create_etag ($_SERVER['REQUEST_URI']);
            // @file_put_contents (CACHE_PATH . $etag, $content);

            $pj = new_object ('Model');
            $pj->render (
                $template, 
                array_merge (
                    $options, 
                    array ('content' => $content)
                )
            );
        }
    }
?>