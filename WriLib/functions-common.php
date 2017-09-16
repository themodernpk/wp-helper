<?php

require_once "Zebra_Pagination.php";

//---------------------------------------------------------
function wri_get_custom_posts($post_type, $per_page = 1, $options=null)
{
    //https://codex.wordpress.org/Template_Tags/get_posts
    $inputs = $_REQUEST;
    $offset = 0;
    if(isset($inputs['page']))
    {
        if($inputs['page'] > 1)
        {
            $offset = $inputs['page']-1;
        }
    }
    $args = [
        'post_type' => $post_type,
        'posts_per_page' => $per_page,
        'offset'           => $offset,
        'fields' => 'ids,post_type'
    ];

    if(is_array($options))
    {
        $args = array_merge($args, $options);
    }

    $list = get_posts($args);
    if(!$list)
    {
        return null;
    }

    $i = 0;
    $result = [];
    foreach ($list as $item)
    {
        $result['post'][$i] = wri_get_post($item->ID);
        $i++;
    }

    $total = wri_count_posts($post_type);
    $result['paginate'] = wri_get_pagination($total, $per_page, $offset);

    return $result;
}
//---------------------------------------------------------
function wri_get_post($post_id)
{
    $item = get_post($post_id);
    $item->custom_fields = wri_get_custom_fields($item->ID);
    $item->taxonomies = wri_get_taxonomy_list($item->ID);
    return $item;
}
//---------------------------------------------------------
function wri_get_pagination($total, $per_page, $current_page=1)
{
    // instantiate the pagination object
    $pagination = new Zebra_Pagination();
    // the number of total records is the number of records in the array
    $pagination->records($total);
    // records per page
    $pagination->records_per_page($per_page);
    return $pagination->render();

}
//---------------------------------------------------------
function wri_get_custom_fields($post_id)
{
    $keys = wri_get_custom_field_keys($post_id);

    if(!$keys)
    {
        return null;
    }
    $result = [];
    foreach ($keys as $key)
    {
        $result[$key] = wri_get_custom_field($post_id, $key);
    }

    return $result;
}
//---------------------------------------------------------
function wri_get_custom_field_keys($post_id)
{
    $keys = get_post_custom_keys($post_id);

    return $keys;
}
//---------------------------------------------------------
function wri_get_custom_field($post_id, $field_key)
{
    $item = get_post_meta($post_id, $field_key, true);
    return $item;
}
//---------------------------------------------------------
function wri_count_posts($post_type, $post_status='publish')
{
    $count = wp_count_posts($post_type);

    if(!is_null($post_status))
    {
        if(isset($count->$post_status))
        {
            return $count->$post_status;
        } else
        {
            return 0;
        }
    }
    $total = 0;
    foreach ($count as $key => $value)
    {
        $total = $total+$value;
    }

    return $total;
}
//---------------------------------------------------------
function wri_get_taxonomy_list($post_id)
{
    $list = get_post_taxonomies( $post_id);

    if(!$list)
    {
        return null;
    }
    $result = [];
    foreach ($list as $item)
    {
        $result[$item] = wri_get_the_taxonomy($post_id, $item);
    }

    return $result;
}
//---------------------------------------------------------
function wri_get_the_taxonomy($post_id, $taxonomy, $args = null)
{
    // https://codex.wordpress.org/Function_Reference/wp_get_post_terms
    if($args)
    {
        $terms = wp_get_post_terms( $post_id, $taxonomy, $args );
    } else
    {
        $terms = wp_get_post_terms( $post_id, $taxonomy);
    }

    if(count($terms) < 1)
    {
        $terms = null;
    }

    return $terms;
}
//---------------------------------------------------------
function wri_get_posts_from_custom_field($post_type, $key, $value)
{
    $meta_query_args = array(
        array(
            'key' => $key,
            'value' => $value,
            'compare' => '='
        )
    );

    $reviewArgs = array(
        'post_type' => $post_type,
        'meta_query' => $meta_query_args,
    );

    $review_query = new WP_Query( $reviewArgs );

    if(!$review_query->posts)
    {
        return false;
    }

    return $review_query->posts;

}
//---------------------------------------------------------
function wri_get_theme($theme_name=null)
{

    if(!$theme_name)
    {
        $theme = wp_get_theme();
    } else
    {
        $theme = wp_get_theme($theme_name);
        if ( !$theme->exists() )
        {
            $response['status'] = 'failed';
            $response['errors'][]= "Theme not exist";
            return $response;
        }
    }

    return $theme;
}
//---------------------------------------------------------
function wri_get_theme_version($theme_name=null)
{
    $theme = wri_get_theme($theme_name);
    if(isset($theme['status']) && $theme['status'] == 'failed')
    {
        return $theme;
    }

    return $theme->get( 'Version' );
}
//---------------------------------------------------------
function wri_get_theme_details($theme_name=null, $key)
{
    $theme = wri_get_theme($theme_name);
    if(isset($theme['status']) && $theme['status'] == 'failed')
    {
        return $theme;
    }

    return $theme->get( $key );
}
//---------------------------------------------------------
//---------------------------------------------------------