<?php



function api($function_name, $params = false)
{
    static $c;

    if ($c == false) {
        if (!defined('MW_API_RAW')) {
            define('MW_API_RAW', true);
        }
        $c = new \mw\Controller();

    }
    $res = $c->api($function_name, $params);
    return $res;

}

function api_expose($function_name)
{
    static $index = ' ';
    if (is_bool($function_name)) {

        return $index;
    } else {
        $index .= ' ' . $function_name;
    }
}

function exec_action($api_function, $data = false)
{
    global $mw_action_hook_index;
    $hooks = $mw_action_hook_index;

    $return = array();
    if (isset($hooks[$api_function]) and is_array($hooks[$api_function]) and !empty($hooks[$api_function])) {

        foreach ($hooks[$api_function] as $hook_key => $hook_value) {

            if (function_exists($hook_value)) {
                if ($data != false) {
                    $return[$hook_value] = $hook_value($data);
                } else {

                    $return[$hook_value] = $hook_value();
                }
                unset($hooks[$api_function][$hook_key]);

            } else {


                try {
                    if ($data != false) {
                        $return[$hook_value] = call_user_func($hook_value, $data); // As of PHP 5.3.0
                    } else {
                        $return[$hook_value] = call_user_func($hook_value, false);
                    }
                } catch (Exception $e) {

                }

            }
        }
        if (!empty($return)) {
            return $return;
        }
    }
}

$mw_action_hook_index = array();
function action_hook($function_name, $next_function_name = false)
{
    global $mw_action_hook_index;

    if (is_bool($function_name)) {
        $mw_action_hook_index = ($mw_action_hook_index);
        return $mw_action_hook_index;
    } else {
        if (!isset($mw_action_hook_index[$function_name])) {
            $mw_action_hook_index[$function_name] = array();
        }

        $mw_action_hook_index[$function_name][] = $next_function_name;

        //  $index .= ' ' . $function_name;
    }
}

$mw_api_hooks = array();
function api_hook($function_name, $next_function_name = false)
{
    //static $index = array();
    global $mw_api_hooks;
    if (is_bool($function_name)) {
        $index = array_unique($mw_api_hooks);
        return $index;
    } else {
        //d($function_name);
        $function_name = trim($function_name);
        $mw_api_hooks[$function_name][] = $next_function_name;

        // $index .= ' ' . $function_name;
    }
}

function document_ready($function_name)
{
    static $index = ' ';
    if (is_bool($function_name)) {

        return $index;
    } else {
        $index .= ' ' . $function_name;
    }
}

function execute_document_ready($l)
{

    $document_ready_exposed = (document_ready(true));

    if ($document_ready_exposed != false) {
        $document_ready_exposed = explode(' ', $document_ready_exposed);
        $document_ready_exposed = array_unique($document_ready_exposed);
        $document_ready_exposed = array_trim($document_ready_exposed);

        foreach ($document_ready_exposed as $api_function) {
            if (function_exists($api_function)) {
                //                for ($index = 0; $index < 20000; $index++) {
                //                     $l = $api_function($l);
                //                }
                $l = $api_function($l);
            }
        }
    }
    //$l = parse_micrwober_tags($l, $options = false);

    return $l;
}

/* JS Usage:
 *
 * var source = new EventSource('<?php print site_url('api/event_stream')?>');
 *	source.onmessage = function (event) {
 *
 * 	mw.$('#mw-admin-manage-orders').html(event.data);
 *	};
 *
 *
 *  */
api_expose('event_stream');
function event_stream()
{

    header("Content-Type: text/event-stream\n\n");

    for ($i = 0; $i < 10; $i++) {

        echo 'data: ' . $i . rand() . rand() . rand() . rand() . rand() . rand() . "\n";

    }

    exit();
}

function array_to_module_params($params, $filter = false)
{
    $str = '';

    if (is_array($params)) {
        foreach ($params as $key => $value) {

            if ($filter == false) {
                $str .= $key . '="' . $value . '" ';
            } else if (is_array($filter) and !empty($filter)) {
                if (in_array($key, $filter)) {
                    $str .= $key . '="' . $value . '" ';
                }
            } else {
                if ($key == $filter) {
                    $str .= $key . '="' . $value . '" ';
                }
            }

        }
    }

    return $str;
}


function parse_params($params)
{


    $params2 = array();
    if (is_string($params)) {
        $params = parse_str($params, $params2);
        $params = $params2;
        unset($params2);
    }


    return $params;
}

$mw_var_storage = array();
function mw_vars_destroy()
{
    global $mw_var_storage;
    $mw_var_storage = array();
}

function mw_var($key, $new_val = false)
{
    global $mw_var_storage;
    $contstant = ($key);
    if ($new_val == false) {
        if (isset($mw_var_storage[$contstant]) != false) {
            return $mw_var_storage[$contstant];
        } else {
            return false;
        }
    } else {
        if (isset($mw_var_storage[$contstant]) == false) {
            $mw_var_storage[$contstant] = $new_val;
            return $new_val;
        }
    }
    return false;
}


action_hook('mw_cron', 'mw_cron');
api_expose('mw_cron');
function mw_cron()
{


    $file_loc = CACHEDIR_ROOT . "cron" . DS;
    $file_loc_hour = $file_loc . 'cron_lock' . '.php';

    $time = time();
    if (!is_file($file_loc_hour)) {
        @touch($file_loc_hour);
    } else {
        if ((filemtime($file_loc_hour)) > $time - 2) {
            touch($file_loc_hour);
            return true;
        }
    }


    // touch($file_loc_hour);
    $cron = new \mw\utils\Cron;

    $scheduler = new \mw\utils\Events();

    return $scheduler->registerShutdownEvent(array($cron, 'run'));


}

/**
 * Guess the cache group from a table name or a string
 *
 * @uses guess_table_name()
 * @param bool|string $for Your table name
 *
 *
 * @return string The cache group
 * @example
 * <code>
 * $cache_gr = guess_cache_group('content');
 * </code>
 *
 * @package Database
 * @subpackage Advanced
 */
function guess_cache_group($for = false)
{
    return guess_table_name($for, true);
}


/**
 * Get Relative table name from a string
 *
 * @package Database
 * @subpackage Advanced
 * @param string $for string Your table name
 *
 * @param bool $guess_cache_group If true, returns the cache group instead of the table name
 *
 * @return bool|string
 * @example
 * <code>
 * $table = guess_table_name('content');
 * </code>
 */
function guess_table_name($for, $guess_cache_group = false)
{

    if (stristr($for, 'table_') == false) {
        switch ($for) {
            case 'user' :
            case 'users' :
                $rel = 'users';
                break;

            case 'media' :
            case 'picture' :
            case 'video' :
            case 'file' :
                $rel = 'media';
                break;

            case 'comment' :
            case 'comments' :
                $rel = 'comments';
                break;

            case 'module' :
            case 'modules' :
            case 'modules' :
            case 'modul' :
                $rel = 'modules';
                break;

            case 'category' :
            case 'categories' :
            case 'cat' :
            case 'categories' :
            case 'tag' :
            case 'tags' :
                $rel = 'categories';
                break;

            case 'category_items' :
            case 'cat_items' :
            case 'tag_items' :
            case 'tags_items' :
                $rel = 'categories_items';
                break;

            case 'post' :
            case 'page' :
            case 'content' :

            default :
                $rel = $for;
                break;
        }
        $for = $rel;
    }
    if (defined('MW_TABLE_PREFIX') and MW_TABLE_PREFIX != '' and stristr($for, MW_TABLE_PREFIX) == false) {
        //$for = MW_TABLE_PREFIX.$for;
    } else {

    }
    if ($guess_cache_group != false) {

        $for = str_replace('table_', '', $for);
        $for = str_replace(MW_TABLE_PREFIX, '', $for);
    }

    return $for;
}


function db_get_table_name($assoc_name)
{

    $assoc_name = str_ireplace('table_', MW_TABLE_PREFIX, $assoc_name);
    return $assoc_name;
}

$_mw_db_get_assoc_table_names = array();
function db_get_assoc_table_name($assoc_name)
{

    global $_mw_db_get_assoc_table_names;

    if (isset($_mw_db_get_assoc_table_names[$assoc_name])) {

        return $_mw_db_get_assoc_table_names[$assoc_name];
    }


    $assoc_name_o = $assoc_name;
    $assoc_name = str_ireplace(MW_TABLE_PREFIX, 'table_', $assoc_name);
    $assoc_name = str_ireplace('table_', '', $assoc_name);

    $is_assoc = substr($assoc_name, 0, 5);
    if ($is_assoc != 'table_') {
        //	$assoc_name = 'table_' . $assoc_name;
    }


    $assoc_name = str_replace('table_table_', 'table_', $assoc_name);
    //	d($is_assoc);
    $_mw_db_get_assoc_table_names[$assoc_name_o] = $assoc_name;
    return $assoc_name;
}

$_mw_db_get_real_table_names = array();
function db_get_real_table_name($assoc_name)
{
    global $_mw_db_get_real_table_names;

    if (isset($_mw_db_get_real_table_names[$assoc_name])) {

        return $_mw_db_get_real_table_names[$assoc_name];
    }


    $assoc_name_new = str_ireplace('table_', MW_TABLE_PREFIX, $assoc_name);
    if (defined('MW_TABLE_PREFIX') and MW_TABLE_PREFIX != '' and stristr($assoc_name_new, MW_TABLE_PREFIX) == false) {
        $assoc_name_new = MW_TABLE_PREFIX . $assoc_name_new;
    }
    $_mw_db_get_real_table_names[$assoc_name] = $assoc_name_new;
    return $assoc_name_new;
}


/**
 * Escapes a string from sql injection
 *
 * @param string $value to escape
 *
 * @return mixed
 * @example
 * <code>
 * //escape sql string
 *  $results = db_escape_string($_POST['email']);
 * </code>
 *
 *
 *
 * @package Database
 * @subpackage Advanced
 */
$mw_escaped_strings = array();
function db_escape_string($value)
{
    global $mw_escaped_strings;
    if(isset($mw_escaped_strings[$value])){
        return $mw_escaped_strings[$value];
    }

    $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
    $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");
    $new = str_replace($search, $replace, $value);
    $mw_escaped_strings[$value] = $new;
    return $new;
}