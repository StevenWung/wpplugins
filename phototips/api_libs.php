<?php

/*
 *  reg entrance
 */
function ft_api_url_write_rules() {
    add_rewrite_rule( '^m/(.*)/?','index.php?ft=$matches[1]','top' );
    global $wp;
    $wp->add_query_var('ft');
}
add_action( 'init', 'ft_api_url_write_rules' );

/*
 *  flush
 */
function ft_api_maybe_flush_rewrites() {
    flush_rewrite_rules();
}
add_action( 'init', 'ft_api_maybe_flush_rewrites', 999 );

/*
 *
 */
function ft_posts_register_routes($routes){
    global $post_routes;
    //$post_routes =
    /*
    $post_routes = array(
        '(?P<ft>[a-z]+)/(?P<timestamp>[0-9]+)/(?P<count>[0-9]+)/(?P<order>[a-z]+)' => array(
            array( 'ft_get_posts',  1 )
        )
    );
    */
    return array_merge( $routes, $post_routes );
}
function ft_api_default_filters($server) {
    add_filter( 'ft_endpoints', 'ft_posts_register_routes', 0 );
}
add_action('ft_serve_action', 'ft_api_default_filters', 10, 1 );

function ft_api_loaded(){
    $rq_method = $_SERVER['REQUEST_METHOD'];
    $path = $GLOBALS['wp']->query_vars['ft'];
    do_action('ft_serve_action');
    $params = apply_filters('ft_endpoints', array());


    foreach( $params as $route => $handlers ){
        foreach($handlers as $handler){
            $callback = $handler[0];
            $method = $handler[1];
            $match = preg_match("@".$route."$@i", $path, $args );

            if( $match ){
                if( $rq_method == $method ){
                    if ( !is_callable( $callback ) ){
                        continue;
                    }
                    $params = sort_callback_params($callback, $args);

                    if( is_array($params) ){
                        call_user_func_array( $callback, $params );
                    }
                }
            }

        }
    }

}
add_action( 'template_redirect', 'ft_api_loaded', -100 );



function sort_callback_params( $callback, $provided ) {
    if ( is_array( $callback ) )
        $ref_func = new ReflectionMethod( $callback[0], $callback[1] );
    else
        $ref_func = new ReflectionFunction( $callback );

    $wanted = $ref_func->getParameters();
    //var_dump($wanted);
    $ordered_parameters = array();

    foreach ( $wanted as $param ) {
        if ( isset( $provided[ $param->getName() ] ) ) {
            // We have this parameters in the list to choose from
            $ordered_parameters[] = $provided[ $param->getName() ];
        }
        elseif ( $param->isDefaultValueAvailable() ) {
            // We don't have this parameter, but it's optional
            $ordered_parameters[] = $param->getDefaultValue();
        }
        else {
            // We don't have this parameter and it wasn't optional, abort!
            return new WP_Error( 'json_missing_callback_param', sprintf( __( 'Missing parameter %s' ), $param->getName() ), array( 'status' => 400 ) );
        }
    }

    return $ordered_parameters;
}



function filter_where($where = '') {
    global $gwhere;
    $where .= " AND $gwhere";
    return $where;
}

function posts($where, $count){
    global $gwhere;
    $gwhere = $where;
    add_filter('posts_where', 'filter_where');
    query_posts("showposts=$count&orderby=post_date");
    remove_filter('posts_where', 'filter_where');
}
function comments($where, $count){

}
function check_token(){
    $timezone = date_default_timezone_get();
    date_default_timezone_set('PRC');
    $timestamp = time();
    $ymd = date('YmdH', $timestamp);
    $i = intval(intval(date("i", $timestamp)) / 3);
    date_default_timezone_set($timezone);
    $temp = "$ymd$i";
    $server_tok = md5($temp);
    $remote_tok = $_POST['token'];
    if( $remote_tok == $server_tok ){
        return true;
    }
    //return false;
    return true;
}
function post_error($code, $msg){
    $result = array();
    $result['success'] = 'false';
    $result['code'] = $code;
    $result['message'] = $msg;
    $tmp = json_encode($result);
    echo $tmp;
    die();
}