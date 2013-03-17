<?php
    class View {
        //  View handles page templates (Views). put them inside VIEWS_PATH.
        protected $contents;
        protected static $ot, $ct, $vf, $include_pattern, $forloop_pattern,
            $if_pattern, $listcmp_pattern, $field_pattern, $variable_pattern;

        function __construct($special_filename = '') {
            // if $special_filename (without file path) is specified, then
            // that template will be used instead.
            // note that user pref take precedence over those in page, post, etc.

            $template = $this->resolve_template_name($special_filename); // returns full path
            $this->contents = $this->get_parsed($template);

            // constants default to case-sensitive
            $ot = self::$ot = '({[{%])'; // opening tag
            $ct = self::$ct = '([}%]})'; // close tag
            $vf = self::$vf = '([a-zA-Z0-9_\.]+)'; // variable format

            // these will only be evaluated once - speed is not much concern.
            self::$include_pattern = "/$ot ?include ?\"([^\"]+)\" ?$ct/U";
            self::$forloop_pattern = "/$ot ?for $vf, ?$vf in $vf ?$ct(.*)$ot ?endfor ?$ct/sU";
            self::$if_pattern = "/$ot ?if $vf ?$ct(.*)(($ot ?elseif $vf ?$ct(.*))*)($ot ?else ?$ct(.*))*$ot ?endif ?$ct/sU";
            self::$listcmp_pattern = "/$ot ?$vf ?in ?$vf ?$ct/sU";
            self::$field_pattern = "/$ot ?field $vf +$vf +$vf ?$ct/sU";
            self::$variable_pattern = "/$ot ?$vf ?$ct/sU";
        }

        function __toString() {
            // GZ buffering is handled elsewhere.
            if (class_exists('Compressor') && TEMPLATE_COMPRESS === true) {
                return (Compressor::html_compress($this->contents));
            } else {
                return ($this->contents);
            }
        }

        function get_parsed($file) {
            if (strpos($file, VIEWS_PATH) === false) {
                $file = VIEWS_PATH . $file;
            }

            if (TEMPLATE_SAFE_MODE === false) { // PHP tags don't work in safe mode.
                ob_start();
                // open_basedir
                if (@is_file($file)) {
                    @include ($file);
                } else {
                    // file not found
                    Pop::debug('File %s not found', $file);
                }
                $buffer = ob_get_contents();
                ob_end_clean();
            } else {
                try {
                    $buffer = @file_get_contents($file);
                } catch (Exception $e) {
                    $buffer = '';
                }
            }
            return $buffer;
        }

        function resolve_template_name($special_filename = '') {
            // successive attempts to get an existing template.
            if (is_file(VIEWS_PATH . $special_filename)) {
                return VIEWS_PATH . $special_filename;
            }
            if (is_file(VIEWS_PATH . SITE_TEMPLATE)) {
                return VIEWS_PATH . SITE_TEMPLATE;
            }
            if (is_file(VIEWS_PATH . DEFAULT_TEMPLATE)) {
                return VIEWS_PATH . DEFAULT_TEMPLATE;
            }
            throw new Exception ('Template file cannot be found to render this page.');
        }

        private function include_snippets(&$contents) {
            /* replace tags that look like
               {% include "header_and_footer.html" %}
               with their actual contents.

               replace_tags help recurse this function.
            */
            $matches = array (); // preg_match_all gives you an array of &$matches.
            if (preg_match_all(self::$include_pattern,
                               $contents,
                               $matches) > 0
            ) {
                if (sizeof($matches) > 0 && sizeof($matches[2]) > 0) {
                    foreach ($matches[2] as $index => $filename) { // [1] because [0] is full line
                        try {
                            $nv = $this->get_parsed($filename);
                        } catch (Exception $e) { // include fail? fail.
                            $nv = '';
                        }
                        // replace tags in this contents with that contents
                        $contents = str_replace($matches[0][$index], $nv, $contents);
                        unset ($nv); // free memory
                    }
                }
            }
        }

        private function create_field_tags(&$contents) {
            /* replace tags that look like
               {% field [id] [type] [prop] %} (without the square brackets)
               with an AJAX html tag. requires jQuery Transmission on the same page.

               replace_tags help recurse this function.
            */
            global $modules;
            if (in_array('AjaxField', $modules) !== true) {
                // don't try to create a AjaxField class if it is not loaded
                return;
            }
            $af = Pop::obj('AjaxField', null);

            $matches = array (); // preg_match_all gives you an array of &$matches.
            if (preg_match_all(self::$field_pattern, $contents, $matches) <= 0) {
                return;
            }
            if (sizeof($matches) > 0 && sizeof($matches[2]) > 0) {
                foreach ($matches[2] as $index => $id) { // [1] because [0] is full line
                    $type = $matches[3][$index];
                    $prop = $matches[4][$index];
                    $obj = Pop::obj($type, $id);
                    if ($obj) {
                        // replace tags in this contents with that contents
                        $contents = str_replace(
                            $matches[0][$index],
                            $af->make($obj, $matches[4][$index]),
                            $contents
                        );
                    }
                }
            }
        }

        private function expand_list_comprehension(&$contents) {
            // e.g. {% object in objects %}
            $contents = preg_replace(
                self::$listcmp_pattern,
                '{% for _lop,_$2 in $3 %}{{ _$2 }}{% endfor %}',
                $contents);
        }

        private function expand_page_loops(&$contents, $tags = array ()) {
            $ot = self::$ot;
            $ct = self::$ct;
            $regex = self::$forloop_pattern;
            // e.g. {% for i in objects %} bla bla bla {% endfor %}

            $matches = array ();
            preg_match_all($regex, $contents, $matches);
            $len = sizeof($matches[0]);
            for ($i = 0; $i < $len; ++$i) { // each match
                $buffer = ''; // stuff to be printed
                // replace tags within the inner loop, n times
                if (isset ($tags[$matches[4][$i]]) &&
                    is_array($tags[$matches[4][$i]])
                ) { // if such tag exists
                    $match_keys = array_keys($tags[$matches[4][$i]]);
                    $match_vals = array_values($tags[$matches[4][$i]]);

                    // number of times the specific match is to be repeated
                    for ($lc = 0; $lc < sizeof($tags[$matches[4][$i]]); ++$lc) {
                        // now, replace the key and value
                        $buffer .= preg_replace(
                            array ( // search
                                    '/' . $ot . ' ?' . preg_quote($matches[2][$i], '/') . ' ?' . $ct . '/sU',
                                    // key
                                    '/' . $ot . ' ?' . preg_quote($matches[3][$i], '/') . ' ?' . $ct . '/sU'
                                    // value
                            ),
                            array ( // replace
                                    (string)$match_keys[$lc],
                                    (string)$match_vals[$lc]
                            ),
                            $matches[6][$i] // loop content
                        );
                    }
                } // else: even if value doesn't exist, remove the tag.

                // str_replace is faster
                $contents = str_replace($matches[0][$i], $buffer, $contents);
            }
        }

        private function resolve_if_conditionals(&$contents,
                                                 $tags = array ()) {
            $regex = self::$if_pattern;
            // e.g. {% if a %} b
            //      {% elseif c %} d
            //      {% elseif e %} f
            //      {% else g %} h
            //      {% endif %}

            $matches = array ();
            preg_match_all($regex, $contents, $matches);
            for ($i = 0; $i < sizeof($matches[0]); ++$i) { // each match

                if (isset($tags[$matches[2][$i]])
                    && $tags[$matches[2][$i]]
                ) { // if {% if ? %} evals to true
                    // replace whole thing with the true part:
                    $this->contents = str_replace($matches[0][$i], // search
                                                  $matches[4][$i], // replace
                                                  $contents); // subject

                    // expand here when ready to do multiple elseif statements //

                } else if (isset($tags[$matches[8][$i]])
                    && strlen($matches[8][$i]) > 0 // if no {% elseif ? %}, this is empty
                    && $tags[$matches[8][$i]]
                ) { // if {% elseif ? %} evals to true
                    // replace whole thing with the true part:
                    $contents = str_replace($matches[0][$i], // search
                                            $matches[10][$i], // replace
                                            $contents); // subject
                } else if (isset($matches[14][$i])
                    && strlen($matches[14][$i]) > 0
                ) { // if no {% else %}?, this is empty
                    // since this is else, replace whole thing with the true part:
                    $contents = str_replace($matches[0][$i], // search
                                            $matches[14][$i], // replace
                                            $contents); // subject
                } else { // you hit this if nothing is true and no {% else %} available
                    $contents = str_replace($matches[0][$i], // search
                                            '', // replace
                                            $contents); // subject
                }
            }
        }

        public function replace_tags($tags = array ()) {
            $ot = self::$ot;
            $ct = self::$ct;
            $vf = self::$vf;

            list($_era, $_ert) = Pop::url( /* defaults to REQUEST_URI */);
            $tags = array_merge(
                array ( // defaults
                        '__cacheable' => false,
                        'title' => '',
                        'styles' => '',
                        'content' => '',
                        'root' => DOMAIN,
                        'subdir' => SUBDIR,
                        'base' => DOMAIN . SUBDIR, // so, pop dir
                        'handler' => $_era ? "$_era.$_ert" : '',
                        'memory_usage' => filesize_natural(memory_get_peak_usage()),
                        'exec_time' => (time() - $_SERVER['REQUEST_TIME']) . ' s',
                        'year' => date('Y'),
                ), // "required" defaults
                vars(), // environmental variables
                $tags // custom tags
            );

            // build tags array; replace tags with object props
            foreach ($tags as $tag => $data) {
                $tags_processed[] = '/' . $ot . ' ?' . $tag . ' ?' . $ct . '/U';
                $values_processed[] = (string)$data; // "abc", "true" or "array"
            }

            // replacing will stop when there are no more {% include "tags" %}.
            do {
                $this->include_snippets($this->contents); // recursively include files (resolves include tags)
                $this->expand_list_comprehension($this->contents);
                $this->expand_page_loops($this->contents, $tags);
                $this->resolve_if_conditionals($this->contents, $tags);
                $this->create_field_tags($this->contents);

                // replace all variable tags
                // remember, replacement may generate new include tags
                $this->contents = preg_replace(
                    $tags_processed,
                    $values_processed,
                    $this->contents);
            } while (preg_match_multi(array(self::$include_pattern,
                                            self::$forloop_pattern,
                                            self::$if_pattern,
                                            self::$listcmp_pattern,
                                            self::$field_pattern,
                                            self::$variable_pattern),
                                      $this->contents));
            unset ($tags_processed, $values_processed); // free ram

            // then hide unmatched var tags
            $this->contents = preg_replace(
                '/' . $ot . ' ?' . $vf . ' ?' . $ct . '/U', '',
                $this->contents
            );
            return $this; // chaining
        }
    }

    if (!function_exists('render')) {
        function render($options = array (), $template = '') {
            static $has_rendered;
            if ($has_rendered === true && $options === array ()) {
                // mistyping is a sign for shutdown function to be triggered
                return;
            }

            // that's why you ob_start at the beginning of Things.
            $content = ob_get_contents();
            ob_end_clean();

            $pj = Pop::obj('Model');
            $pj->render($template,
                        array_merge($options,
                                    array ('content' => $content)));
            $has_rendered = true;
        }
    }
