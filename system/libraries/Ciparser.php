<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

require_once dirname(realpath(SELF)) . "/application/libraries/JavaScriptPacker.php";

class CI_Ciparser {

    private $_LOG = FALSE;

    public function new_parse($template, $key, $embed_template, $data = '', $jsEncrypted = TRUE) {
        // this is to make $this php variable works
        // with this, then you can call standard codeigniter tutorials, such as $this->load->view() from other file
        $CI = & get_instance();
        foreach (get_object_vars($CI) as $_ci_key => $_ci_var) {
            if (!isset($this->$_ci_key)) {
                $this->$_ci_key = & $CI->$_ci_key;
            }
        }
        // to make load view is work, then we add '' on third params on view() below
        $template = $CI->load->view($template, $data, true);
        $this->_new_parse($template, $data, $key, $embed_template, $jsEncrypted);
    }

    // ready to parsing a tag
    private function _new_parse($template, $data, $key, $embed_template, $jsEncrypted = TRUE) {
        $keys = explode('-', $key);
        $module_place = $keys[0];
        $module_name = $keys[1];
        $this->_new_parse_single($data, $module_place, $module_name, $embed_template, $template, $jsEncrypted);
    }

    // now parsing a tag
    private function _new_parse_single($data, $module_place, $module_name, $embed_template, $template, $jsEncrypted = TRUE) {
        // find ci:doc tag on view
        $regex = '#<ci:doc type( =|=|= )("|\')(' . $module_place . ')("|\')( \/|\/)>#';
        // replace ci:doc tag with view file of selected module
        //print_r(APPPATH . $module_place . '/' . $module_name . '/views/' . $embed_template . '.php');return;

        $module = realpath(APPPATH . $module_place . '/' . $module_name . '/views/' . $embed_template . '.php');
        $module = file_get_contents($module);
        if (is_array($data)) {
            foreach ($data as $getkey => $info) {
                $getkey = $data[$getkey];
            }
        }
        #$template = $this->packerJS($template);

        ob_start();
        echo eval("?>" . $module);
        $module = ob_get_contents();
        if ($jsEncrypted) {
            $module = $this->packerJS($module);
        }
        ob_end_clean();
         
        
        ob_start();
        echo eval("?>" . preg_replace($regex, $module, $template));
        global $OUT;
        $OUT->append_output(ob_get_contents());

        @ob_end_clean();
        @ob_flush();
    }

    private function packerJS($htmlContent = '') {
        if (trim($htmlContent) === '') {
            return '';
        }
        $content_ = preg_split("/((\r?\n)|(\r\n?))/", $htmlContent);
        $num = 0;
        $start = 0;
        foreach ($content_ as $line) {
            $line_ = filter_var(trim($line), FILTER_SANITIZE_MAGIC_QUOTES);
            //print_r($line_); 
            if (strpos($line_, 'script language') !== FALSE) {
                $start = $num;
            } else if (strpos($line_, "/script") !== FALSE) {
                if ($start !== 0) {
                    $this->_LOG ? log_message('library ciparser', "Start decoding JS. Start Line : $start") : '';

                    $jsInline = '';
                    $lastline_ = 0;

                    $length = (intval($num + 1) - intval($start));
                    for ($x = 0; $x <= $length - 1; $x++) {
                        $line_ = intval($start) + $x;
                        if (strpos($content_[$line_], '//') === FALSE) {
                            $jsInline .= $content_[$line_] . "\r\n";
                        }

                        if ($x !== 0) {
                            $this->_LOG ? log_message('library ciparser', "Unset Line : $line_") : '';
                            unset($content_[$line_]);
                        } else {
                            $this->_LOG ? log_message('library ciparser', "Leaving unset Line : $line_") : '';
                        }
                    }

                    $this->_LOG ? log_message('library ciparser', "Repacking JS ... ") : '';
                    $jsInline = str_replace('<script language="javascript">', '', $jsInline);
                    $jsInline = str_replace('</script>', '', $jsInline);
                    //$jsInline = $this->minify_js($jsInline);
                    //$jsInline = new JavaScriptPacker($jsInline, 0); //-->> Only Minify
                    $jsInline = new JavaScriptPacker($jsInline, 95);
                    $jsInline = utf8_encode($jsInline->pack());

                    $this->_LOG ? log_message('library ciparser', "Appending JS in line " . (intval($start) - 1)) : '';
                    $content_[intval($start)] = "<script>$jsInline</script>";
                    $start = 0;
                }
            }
            $num += 1;
        }
        $content_ = implode("\r\n", $content_);
        return $content_;
    }

    private function minify_js($js) {
        $js = str_replace("\t", " ", $js);
        $js = preg_replace('/\n(\s+)?\/\/[^\n]*/', "", $js);
        $js = preg_replace("!/\*[^*]*\*+([^/][^*]*\*+)*/!", "", $js);
        $js = preg_replace("/\/\*[^\/]*\*\//", "", $js);
        $js = preg_replace("/\/\*\*((\r\n|\n) \*[^\n]*)+(\r\n|\n) \*\//", "", $js);
        $js = str_replace("\r", "", $js);
        $js = preg_replace('!\s+!', ' ', preg_replace("/[\r\n]*/", "", $js));
        $js = preg_replace("/\s+\n/", "\n", $js);
        $js = preg_replace("/\n\s+/", "\n ", $js);
        $js = preg_replace("/ +/", " ", $js);
        return $js;
    }

}
