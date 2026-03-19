<?php
/**
 * Register TMDB settings using the WordPress Settings API.
 *
 * This stores the TMDB API key in the wp_options table using a
 * namespaced option name to avoid conflicts with other plugins
 * or themes.
 */
function marzio_register_tmdb_settings() {

    register_setting(
        'marzio_tmdb_settings_group',
        'marzio_tmdb_api_key'
    );

}
add_action('admin_init', 'marzio_register_tmdb_settings');


/**
 * Add the TMDB configuration page to the WordPress admin menu.
 */
function marzio_add_tmdb_settings_page() {

    add_options_page(
        'TMDB API Settings',
        'TMDB API',
        'manage_options',
        'marzio-tmdb-settings',
        'marzio_render_tmdb_settings_page'
    );

}
add_action('admin_menu', 'marzio_add_tmdb_settings_page');


/**
 * Render the TMDB settings page inside the WordPress admin area.
 */
function marzio_render_tmdb_settings_page() {
?>

<div class="wrap">
<h1>TMDB API Settings</h1>

<form method="post" action="options.php">

<?php settings_fields('marzio_tmdb_settings_group'); ?>

<table class="form-table">

<tr>
<th scope="row">TMDB API Key</th>

<td>
<input
type="text"
name="marzio_tmdb_api_key"
value="<?php echo esc_attr(get_option('marzio_tmdb_api_key')); ?>"
size="50"
>
</td>

</tr>
</table>
<?php submit_button(); ?>
</form>
</div>
<?php
}

/**
 * Restrict REST API access to authenticated users only.
 *
 * WordPress Application Password authentication occurs before
 * this filter runs, therefore stateless API access remains
 * possible while anonymous requests are rejected.
 */
function marzio_block_anonymous_rest_requests($result) {

    if (!empty($result)) {
        return $result;
    }

    if (!is_user_logged_in()) {

        return new WP_Error(
            'rest_forbidden',
            'REST API access is restricted to authenticated users.',
            array('status' => 401)
        );

    }

    return $result;

}
add_filter('rest_authentication_errors', 'marzio_block_anonymous_rest_requests');


/**
 * Remove REST API discovery links from the HTML document head.
 *
 * This prevents the automatic exposure of the REST endpoint
 * through HTML metadata.
 */
function marzio_remove_rest_discovery_links() {

    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('template_redirect', 'rest_output_link_header', 11);

}
add_action('init', 'marzio_remove_rest_discovery_links');

/**
 * Retrieve trending movies from the TMDB API.
 *
 * The dataset is cached using WordPress transients to reduce
 * external API requests and improve performance.
 */
function marzio_fetch_tmdb_movies() {

    $cached_movies = get_transient('marzio_tmdb_trending_movies');

    if ($cached_movies !== false) {
        return $cached_movies;
    }

    $tmdb_api_key = get_option('marzio_tmdb_api_key');

    if (empty($tmdb_api_key)) {

        return new WP_Error(
            'tmdb_missing_key',
            'TMDB API key not configured.'
        );

    }

    $tmdb_request_url = add_query_arg(
        array(
            'api_key' => $tmdb_api_key
        ),
        'https://api.themoviedb.org/3/trending/movie/day'
    );

    $http_response = wp_remote_get($tmdb_request_url);

    if (is_wp_error($http_response)) {
        return $http_response;
    }

    $response_body = json_decode(
        wp_remote_retrieve_body($http_response),
        true
    );

    if (!isset($response_body['results'])) {
        return array();
    }

    $movies_dataset = $response_body['results'];

    /**
     * Cache movie dataset for 30 minutes.
     */
    set_transient(
        'marzio_tmdb_trending_movies',
        $movies_dataset,
        30 * MINUTE_IN_SECONDS
    );

    return $movies_dataset;

}

/**
 * Select a random movie from the cached TMDB dataset.
 */
function marzio_select_random_movie() {

    $movies_dataset = marzio_fetch_tmdb_movies();

    if (is_wp_error($movies_dataset)) {
        return $movies_dataset;
    }

    if (empty($movies_dataset)) {
        return null;
    }

    $random_movie_index = array_rand($movies_dataset);

    return $movies_dataset[$random_movie_index];

}

/**
 * Register the custom REST API endpoint.
 *
 * This endpoint returns information about one randomly
 * selected movie retrieved from TMDB.
 */
function marzio_register_random_movie_endpoint() {

    register_rest_route(

        'marzio/v1',

        '/random-movie',

        array(

            'methods'  => 'GET',

            'callback' => 'marzio_random_movie_endpoint_callback',

            'permission_callback' => function () {
                return is_user_logged_in();
            }

        )

    );

}
add_action('rest_api_init', 'marzio_register_random_movie_endpoint');

/**
 * Handle requests to the random movie REST endpoint.
 *
 * Returns normalized movie data extracted from the
 * TMDB dataset.
 */
function marzio_random_movie_endpoint_callback() {

    $movie = marzio_select_random_movie();

    if (is_wp_error($movie)) {
        return $movie;
    }

    if (!$movie) {

        return new WP_Error(
            'no_movie',
            'No movie available.'
        );

    }

    return array(

        'title'        => $movie['title'],
        'overview'     => $movie['overview'],
        'release_date' => $movie['release_date'],
        'rating'       => $movie['vote_average'],
        'poster_path'  => $movie['poster_path']

    );

}

