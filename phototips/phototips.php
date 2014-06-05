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
    define("FT_COMMENT_AUTHOR_EMAIL", "comment@localhosttestserver.com");
    define("FT_COMMENT_AUTHOR_NAME", "foto_commentor");
    define("POST", 'POST');
    define('GET', 'GET');

    $header = "<style type='text/css'>img{width: 100%;}</style>";
    $header.= "<script>function loaded(){window.location = 'loaded://'; }</script><body onload='loaded();'>";

    require_once 'api_libs.php';

    global $post_routes;

    $post_routes = array(
        'posts/(?P<timestamp>[0-9]+)/(?P<count>[0-9]+)/(?P<order>[a-z]+)' => array(
            array( 'ft_get_posts',  GET )
        ),
        'post/(?P<id>[0-9]+)/?' => array(
            array( 'ft_get_post',  GET )
        ),
        'comment/(?P<post_id>[0-9]+)/?' => array(
            array( 'ft_post_comments',  POST )
        ),
        'comment/(?P<post_id>[0-9]+)/(?P<timestamp>[0-9]+)/(?P<count>[0-9]+)/?' => array(
            array( 'ft_get_comments',  GET)
        )
    );

    function ft_get_comments( $post_id, $timestamp , $count ){
        $real_count = 0;
        $comments = array();
        $raw_comments = get_comments( array(
            'post_id' => $post_id,
            'author_email' => FT_COMMENT_AUTHOR_EMAIL,
            'number' => $count,
            'offset' => $timestamp,
        ));
        foreach ($raw_comments as $raw_comment){
            $timestr = $raw_comment->comment_date;
            $content = $raw_comment->comment_content;
            
            $comment = array();
            $comment['id'] = $raw_comment->comment_ID;;
            $comment['timestamp'] = strval(strtotime($timestr));
            $comment['author'] = substr($content, 0, strpos($content, ':'));
            $comment['comment'] = substr($content, strpos($content, ':') + 1 );
            $comments[] = $comment;
        }
        $result = array();
        $result['name'] = 'comments';
        $result['row'] = count($comments);
        $result['max_timestamp'] = $timestamp + count($raw_comments);
        $result['data'] = $comments;

        echo json_encode($result);
        die();
    }
    function ft_post_comments($post_id){
        global $header;
        $comment = $_POST['comment'];
        $author = $_POST['author'];
        if( $author == '' ){
            $author = 'anonymous';
        }
        if($comment == ''){
            return;
        }

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST['comment_post_ID'] = $post_id;;
        $_POST['author'] = FT_COMMENT_AUTHOR_NAME;
        $_POST['email'] = FT_COMMENT_AUTHOR_EMAIL;
        $_POST['url'] = "";
        $_POST['comment'] = $author.":".$comment;
        $_POST['parent'] = "";
        $_POST['redirect_to'] = './';
        include ABSPATH . 'wp-comments-post.php';
    }
    function ft_get_post($id){
        global $header;
        query_posts("p=$id");
        the_post();
        echo $header . get_the_content();
        die();
    }

    function ft_get_posts($timestamp, $count, $order){
        $compare_timestamp =  date('Y-m-d H:i:s', $timestamp + 60) ;;

        $postList = array();
        $maxTimestamp = 0;
        $minTimestamp = time() + 100000;;
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
            $comment = get_comment_count($post_id);
            $item = array();
            $item['title'] = get_the_title();
            $item['id'] = "".get_the_ID();
            $item['date'] = date('Y-m-d', $timestamp);
            $item['timestamp'] = "".$timestamp;
            $item['datetime'] = date('Y-m-d H:i:s', $timestamp);
            $item['summary'] = get_the_excerpt();
            $item['header_picture'] = $header_picture;
            $item['comments'] = "".$comment['approved'];

            $postList[] = $item;

            if( $timestamp > $maxTimestamp ){
                $maxTimestamp = $timestamp;
            }
            if( $timestamp < $minTimestamp  ){
                $minTimestamp = $timestamp;
            }
        endwhile;

        $result = array();
        $result['name'] = 'posts';
        $result['row'] = count($postList);
        $result['max_timestamp'] = $maxTimestamp;
        $result['min_timestamp'] = $minTimestamp;;
        $result['data'] = $postList;
        echo json_encode($result);
        wp_reset_query();
        die();
    }

