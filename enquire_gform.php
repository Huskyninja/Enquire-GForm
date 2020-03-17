<?php
/*
Plugin Name: Enquire Gravity Forms
Description: Send form data to the Enquire CRM using Gravity Form's Add-on Framework
version: 0.4.1
Author: Sage Age
Author URI: https://www.sageagestrategies.com/
License: GPLv3 or later
Text Domain: enquire-gform
Domain Path: /languages

---------------------------------------------------------------------

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*/

define( 'ENQUIRE_GFORM_VERSION', '0.4.1' );
 
add_action( 'gform_loaded', array( 'Enquire_Gform_Bootstrap', 'load' ), 5 );
 
class Enquire_Gform_Bootstrap {
 
    public static function load() {
 
        if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
            return;
        }
 
        require_once( 'class-enquire-gform.php' );
 
        GFAddOn::register( 'EnquireGform' );
    }
 
}
 
function enquire_gform() {
    return EnquireGform::get_instance();
}
