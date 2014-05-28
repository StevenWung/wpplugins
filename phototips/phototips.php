<?php
/**
 * @package Phototips
 */
/*
Plugin Name: Phototips
Plugin URI: http://stevenwung.me/
Description: For Personal Use
Version: 1.0.0
Author: StevenWung
Author URI: http://stevenwung.me/
License: GPLv2 or later
Text Domain: akismet
*/

    /*
        news:
            /m/news/timestamp/count/new/
            /m/news/timestamp/count/old
        comments:
            /m/comment/timestamp/count/new/
            /m/comment/timestamp/count/old
     */
    require_once 'api_libs.php';

    global $post_routes;

    $post_routes = array(
        '(?P<ft>[a-z]+)/(?P<timestamp>[0-9]+)/(?P<count>[0-9]+)/(?P<order>[a-z]+)' => array(
            array( 'ft_get_posts',  1 )
        )
    );


function ft_get_posts($ft, $timestamp, $count, $order){
    $compare_timestamp =  date('Y-m-d H:i:s', $timestamp + 60) ;;

    $postList = array();
    $maxTimestamp = 0;
    $minTimestamp = 0;
    if( $order == 'new' ){
        posts("post_date > '$compare_timestamp'", $count);
    }else
    if( $order == 'old' ){
        posts("post_date < '$compare_timestamp'", $count);
    }else{
        die('parameter error');
    }


    while ( have_posts() ) :the_post();

        $post_id = get_the_ID();
        $timestamp = strtotime( get_the_date().' '.get_the_time());

        $header_picture_raw = get_the_post_thumbnail();
        $rt = preg_match('/src="([^"]+)" /i', $header_picture_raw, $match);
        if( $rt ){
            $header_picture = $match[1];
        }else{
            $header_picture = '';
        }

        $item = array();
        $item['title'] = get_the_title();
        $item['id'] = "".get_the_ID();
        $item['date'] = date('Y-m-d', $timestamp);
        $item['timestamp'] = "".$timestamp;
        $item['datetime'] = date('Y-m-d H:i:s', $timestamp);
        $item['summary'] = get_the_excerpt();
        $item['header_picture'] = $header_picture;
        $item['comments'] = get_comment_count($post_id)['approved'];

        $postList[] = $item;

        if( $maxTimestamp > $timestamp ){
            $maxTimestamp = $timestamp;
        }
        if( $minTimestamp < $timestamp ){
            $minTimestamp = $timestamp;
        }
    endwhile;

    $result = array();
    $result['name'] = 'news';
    $result['row'] = count($postList);
    $result['max_timestamp'] = $maxTimestamp;
    $result['min_timestamp'] = $minTimestamp;;
    $result['data'] = $postList;
    echo json_encode($result);
    wp_reset_query();
    die();
}

