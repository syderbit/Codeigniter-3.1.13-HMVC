<?php

defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends MX_Controller {

    public function __construct() {
        parent::__construct();
    }

    function index() {
        //-->> This load page
        $this->ciparser->new_parse('Mainpage', 'modules-welcome', 'view_welcome');
    }
}
