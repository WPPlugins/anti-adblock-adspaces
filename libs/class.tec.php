<?php
class TEC {
    // conf.
    private $GET_CONTENT_TIMEOUT = 9999; // sets the "file_get_contants" timeout
    private $BASE_URL = false;
    private $URL_SOURCECODE = false;
    
    
    /**
     * A set of functions (mostly) for private use         
     */
    
    private function url_correct(){
        
        $t_urlStart     = substr($this->BASE_URL, 0, 7);
        
        if (stristr($t_urlStart, "http://")===FALSE){
            $this->BASE_URL =  "http://".$this->BASE_URL;
        }
        $url_lastChar = substr($this->BASE_URL, -1);
        $url_5lastChar = substr($this->BASE_URL, -5);
        
        $this->BASE_URL = str_replace(Array("\r", "\n"), "", $this->BASE_URL );
        
        return true;            
    }
    public function uri_parts($uri){
        $a_ret = explode("/", $uri);
        return $a_ret;
    }           
    
    
    /**
     * Function to set the url
     */
    public function uri_set($uri){
        $this->BASE_URL  = $uri;
    }
    
    
    /**
     * Set the source from which to scrap
     */
    public function source_set($sr){
        $this->URL_SOURCECODE = $sr;
    }
    /**
     * Check if SOURCECODE is set
     */         
    public function source_check(){
        $ret = false;
        if (isset($this->URL_SOURCECODE) && !empty($this->URL_SOURCECODE)) $ret = true;
        return $ret;            
    }
    
    /**
     * Get uri content and store the result
     */
    public function source_get($uri=false){
        if (!empty($uri)) $this->BASE_URL  = $uri;
        
        $ret = false;                   
        if ($this->BASE_URL !== false && $this->BASE_URL != ""){
            $this->url_correct(); // correct possible bad url
            
            $ctx = stream_context_create(array(
                'http' => array(
                    'timeout' => $this->GET_CONTENT_TIMEOUT,
                    'max_redirects' => '5'
                )
            )
                                         );
            $this->URL_SOURCECODE = @file_get_contents  (       
                                                         $this->BASE_URL,       
                                                         0, 
                                                         $ctx
                                                         );
            $ret = $this->URL_SOURCECODE;
        }
        return $ret;
    }
    

    
    
    public function links_get($filters=false){
        $array_return = false;
        if ($this->URL_SOURCECODE !== false){
            $array_return = Array();
            $txt = str_replace("'",'"',$this->URL_SOURCECODE);
            preg_match_all('/href="([^"]+)"/', $txt, $salida);
            $matches = $salida[0];
            foreach ($matches as $aK => $aV){
                $is_valid = true;
                // clean the url
                
                $link_d = str_replace(Array(
                    "href=",
                    "\\",
                    "'",
                    "\"",
                    "<br />",
                    "<br/>",
                    "<br>",
                    "\n",
                    "\r"), "", $aV);
                
                $link_d = trim($link_d);
                $protocol = substr($link_d, 0, 4);
                $two_chars = substr($link_d, 0, 2);

                
                // if path relative
                if ($protocol != "http" && $two_chars != "//"){

                    // check for mailto < make a more generic stuff to check more keywords
                    $mailto = substr($link_d, 0, 7);
                    $javascript = substr($link_d, 0, 11);
                    
                    if ($mailto != "mailto:" && $javascript != "javascript:"){
                        
                        $link_first_char = substr($link_d, 0, 1);
                        
                        if ($link_first_char == "/"){
                            $link_d = substr($link_d, 1, strlen($link_d));

                        }else if($link_d == "#"){
                            continue;                            
                        }
                        $then_separator = "";
                        $right_firstChar = substr($link_d, 0, 1);
                        $left_lastChar = substr($this->BASE_URL, -1);
                        if ($right_firstChar != "/" && $left_lastChar != "/"){
                            $then_separator = "/";
                        }
                        $s_host = $this->link_host($this->BASE_URL);
                        $s_proto = $this->link_host($this->BASE_URL, "protocol");
                        $link_d = $s_proto."://".$s_host."/".$then_separator.$link_d;                                            

                    }
                    
                    
                }

                
                // check the filters
                if ($filters !== false){
                    // get first character of the filter
                    $first_char_filter = substr($filters, 0, 1);
                    $REAL_FILTER = $filters; // real filters
                    if ($first_char_filter=="-" || $first_char_filter=="+"){
                        $REAL_FILTER = substr($filters, 1, strlen($filters));
                    }
                    
                    $FILTER_TO_USE = "+";
                    if ($first_char_filter=="-") $FILTER_TO_USE = $first_char_filter;
                    
                    // check filters
                    $is_valid_result = stristr($link_d, $REAL_FILTER);
                    if ($FILTER_TO_USE=="-"){
                        if ($is_valid_result != false) $is_valid = false; // founded, but if (-) not useful
                    }elseif($FILTER_TO_USE=="+"){
                        if ($is_valid_result === false) $is_valid = false; // innecessary                                                       
                    }

                }
                if ($is_valid) $array_return[] = $link_d;
                
                
            }
        }
        return $array_return;
    }
    
    public function link_is_external($link, $url){
        $ret = true;

        $compare_to = $this->link_host( $url );
        
        if(stristr($link, $compare_to) != FALSE) {
            $ret = false;
        }

        return $ret;
    }
    public function link_host($uri, $part = "host"){

        $a_url = parse_url($uri);
        $s_ret = "";

        if ($part == "host"){
            if (!isset($a_url["host"])){
                $s_ret = $a_url["path"];
            }else{
                $s_ret = $a_url["host"];
            }
        }else if($part == "protocol"){
            if (isset($a_url["scheme"])) $s_ret = $a_url["scheme"];            
        }
        return $s_ret;
        
    }

    public function images_get(){
        
        $array_return = Array();
        
        $txt = str_replace("'",'"',$this->URL_SOURCECODE);
        
        preg_match_all('/src="([^"]+)"/', $txt, $salida);
        $matches = $salida[0];

        foreach ($matches as $aK => $aV){
            $image_link = str_replace('src="','',$aV);
            $image_link = substr($image_link, 0, strlen($image_link)-1);
            $link_extension = substr($image_link,-3);
            if (        
                $link_extension=="jpg" || 
                $link_extension=="png" || 
                $link_extension=="gif"
                ){

                $link_d = str_replace("src=", "", $aV);
                $link_d = str_replace("\"", "", $link_d);
                $link_d = str_replace("'", "", $link_d);
                //echo "a:".$link_d."<br />";
                $protocol = substr($link_d, 0, 4);
                // if path relative
                if ($protocol != "http"){
                    $link_first_char = substr($link_d, 0, 1);
                    if ($link_first_char == "/"){
                        $link_d = substr($link_d, 1, strlen($link_d));
                    }
                    $then_separator = "";
                    $right_firstChar = substr($link_d, 0, 1);
                    $left_lastChar = substr($this->BASE_URL, -1);
                    if ($right_firstChar != "/" && $left_lastChar != "/"){
                        $then_separator = "/";
                    }
                    $link_d = $this->BASE_URL.$then_separator.$link_d;
                }
                $array_return[] = $link_d;
            }
            
            
        }
        return $array_return;
    }

    /**
      * Get param src=
      */
    public function src_get(){
        
        $array_return = Array();
        
        $txt = str_replace("'",'"',$this->URL_SOURCECODE);
        
        preg_match_all('/src="([^"]+)"/', $txt, $salida);
        $matches = $salida[0];

        foreach ($matches as $aK => $aV){
            $image_link = str_replace('src="','',$aV);
            $image_link = substr($image_link, 0, strlen($image_link)-1);
            
            
            $link_d = str_replace("src=", "", $aV);
            $link_d = str_replace("\"", "", $link_d);
            $link_d = str_replace("'", "", $link_d);
            //echo "a:".$link_d."<br />";
            $protocol = substr($link_d, 0, 4);
            // if path relative
            if ($protocol != "http"){
                $link_first_char = substr($link_d, 0, 1);
                if ($link_first_char == "/"){
                    $link_d = substr($link_d, 1, strlen($link_d));
                }
                $then_separator = "";
                $right_firstChar = substr($link_d, 0, 1);
                $left_lastChar = substr($this->BASE_URL, -1);
                if ($right_firstChar != "/" && $left_lastChar != "/"){
                    $then_separator = "/";
                }
                $link_d = $this->BASE_URL.$then_separator.$link_d;
            }
            $array_return[] = $link_d;

            
            
        }
        return $array_return;
    }
    
   
    /**
     * Get IP address: ipv4 , ipv6
     */
    public function ip_get(){
        if (!$this->source_check()) return false;
        $ret = Array();                 
        $ipv4_pattern = '/\b(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/'; 
        $ipv6_pattern = '/\b\s*((([0-9A-Fa-f]{1,4}:){7}([0-9A-Fa-f]{1,4}|:))|(([0-9A-Fa-f]{1,4}:){6}(:[0-9A-Fa-f]{1,4}|((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3})|:))|(([0-9A-Fa-f]{1,4}:){5}(((:[0-9A-Fa-f]{1,4}){1,2})|:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3})|:))|(([0-9A-Fa-f]{1,4}:){4}(((:[0-9A-Fa-f]{1,4}){1,3})|((:[0-9A-Fa-f]{1,4})?:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}))|:))|(([0-9A-Fa-f]{1,4}:){3}(((:[0-9A-Fa-f]{1,4}){1,4})|((:[0-9A-Fa-f]{1,4}){0,2}:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}))|:))|(([0-9A-Fa-f]{1,4}:){2}(((:[0-9A-Fa-f]{1,4}){1,5})|((:[0-9A-Fa-f]{1,4}){0,3}:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}))|:))|(([0-9A-Fa-f]{1,4}:){1}(((:[0-9A-Fa-f]{1,4}){1,6})|((:[0-9A-Fa-f]{1,4}){0,4}:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}))|:))|(:(((:[0-9A-Fa-f]{1,4}){1,7})|((:[0-9A-Fa-f]{1,4}){0,5}:((25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])(\.(25[0-5]|2[0-4][0-9]|1[0-9][0-9]|[1-9]?[0-9])){3}))|:)))(%.+)?\s*\b/';
        if (preg_match_all($ipv4_pattern, $this->URL_SOURCECODE, $matches, PREG_OFFSET_CAPTURE)) {
            

        }elseif (preg_match_all($ipv6_pattern, $this->URL_SOURCECODE, $matches, PREG_OFFSET_CAPTURE)) {
        }
        if (isset($matches[0])){
            foreach ($matches[0] as $aV){
                $ret[] = $aV[0];                                
            }
        }
        return $ret;
    }

    /**
     * Get an ordered array of most important keywords of the a text, size 1 and size 2
     */
    public function keywords_text($t, $size=1){
        
        $a_total_words = Array();
        $a_total_words_two_c = Array();
        
        
        $content = html_entity_decode($t);
        //$content = utf8_encode($content);
        $content = strip_tags($content);
        $a_content = explode(" ", $content);
        
        
        
        foreach ($a_content as $aK => $aW){
            
            $aW = strtolower($aW);
            $aW = str_replace( Array(",", ".", ":"), "", $aW);
            $aW = trim($aW);
            $key_len = strlen($aW);                                     
            
            $a_total_words_two_c[] = $aW; // save for the two keys
            if ($key_len > 2){
                
                
                if (!isset($a_total_words[$aW])){ 
                    $a_total_words[$aW] = 1;
                }else{
                    $a_total_words[$aW]++;
                }
                
                
            }
            
        }
        
        
        asort($a_total_words);
        $a_total_words = array_reverse($a_total_words);
        
        
        
        // return result
        if ($size==2){
            $a_final_two_phrases = Array();
            
            foreach ($a_total_words_two_c as $aKKK => $aVVV){
                if (isset($a_total_words_two_c[$aKKK+1])){
                    $str_two = $a_total_words_two_c[$aKKK]." ".$a_total_words_two_c[$aKKK+1];
                    if (str_replace(" ", "", $str_two) !== ""){
                        if (!isset($a_final_two_phrases[$str_two])){
                            $a_final_two_phrases[$str_two] = 1;
                        }else{
                            $a_final_two_phrases[$str_two]++;
                        }
                    }                           
                }
            }
            
            asort($a_final_two_phrases);
            $a_final_two_phrases = array_reverse($a_final_two_phrases);                 
            
            return $a_final_two_phrases;
        }else if ($size==1){
            return $a_total_words;
        }
    }
}    
?>
