<?php
    if (!function_exists ('map_reduce')) {
        function map_reduce ($array, $callback,
                              $drop_results = false,
                              $map_url = false, 
                              $reduce_url = false) {
            /*  maps the array an the operation to different PHP processes/threads
                known as shards, then reduces them back to one array or value.
                function referenced by callback just be present in all threads.
                $drop_results = true issues calls to the map_url, but does not
                wait for these functions to return.
            */
        }
        
        // function connects to an array of URLS at the same time
        // and returns an array of results.

        function multi_http ($urlArr) {
            $sockets = $urlInfo = $retDone = $retData = $errno = $errstr = array ();
            for ($x = 0; $x < count ($urlArr); $x++) {
                $urlInfo[$x] = parse_url($urlArr[$x]);
                $urlInfo[$x][port] = ($urlInfo[$x][port]) ? $urlInfo[$x][port] : 80;
                $urlInfo[$x][path] = ($urlInfo[$x][path]) ? $urlInfo[$x][path] : "/";
                $sockets[$x] = fsockopen ($urlInfo[$x][host], $urlInfo[$x][port], $errno[$x], $errstr[$x], 30);
                socket_set_blocking ($sockets[$x], false);
                $query = ($urlInfo[$x][query]) ? "?" . $urlInfo[$x][query] : "";
                fputs ($sockets[$x],"GET " . $urlInfo[$x][path] . "$query HTTP/1.0\r\nHost: " . $urlInfo[$x][host] . "\r\n\r\n");
            }
            // ok read the data from each one
            $done = false;
            while (!$done) {
                for ($x = 0; $x < count ($urlArr); $x++) {
                    if (!feof ($sockets[$x])) {
                        if ($retData[$x]) {
                            $retData[$x] .= fgets ($sockets[$x], 128);
                        } else {
                            $retData[$x] = fgets ($sockets[$x], 128);
                        }
                    } else {
                        $retDone[$x] = 1;
                    }
                }
                $done = (array_sum ($retDone) == count ($urlArr));
            }
            return $retData;
        }
    }
?>