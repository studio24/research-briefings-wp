<?php
include_once('taxonomy.php');

add_action('admin_menu', 'research_briefings_wp_crosstagger_page');
function research_briefings_wp_crosstagger_page() {
	add_menu_page(
		'Research Briefings',
		'Research Briefings',
		'manage_options',
		'research-briefings',
		'research_briefings_wp_page_callback',
		plugins_url( '../icon.svg', __FILE__ ),
		'100'
	);
}

/**
 * Get custom CSS for admin
 */
function research_briefings_load_crosstagger_styles($hook) {
    // Load only on ?page=mypluginname
    if($hook != 'toplevel_page_research-briefings') {
        return;
    }
    wp_enqueue_script( 'custom_wp_admin_css', plugins_url('../public/js/drag-drop.js', __FILE__) );
    wp_enqueue_style( 'custom_wp_admin_css', plugins_url('../public/css/ingester.css', __FILE__) );
}
add_action( 'admin_enqueue_scripts', 'research_briefings_load_crosstagger_styles' );

function research_briefings_wp_page_callback() {
	if( isset($_POST['research-briefings-crosstag-update']) ) {
            update_option('research_briefings_crosstagged', $_POST['crosstagged-category']);
            $updated = true;
        }
        $currentSettings = get_option('research_briefings_crosstagged');
    ?>

    <div class="wrap">
        <h1>Ingester Information</h1>
        <p>The Ingester runs every hour. It will add any new items.</p>
        <div class="research-briefings-ingester">
            <?php if($updated): ?>
                <div class="notice notice-success"><p>Crosstags updated.</p></div>
            <?php endif; ?>
            <div class="research-briefings-column">
                <h2>Second Reading Categories</h2>
                <?php echo research_briefings_format_wp_categories($currentSettings); ?>
            </div>
            <div class="research-briefings-column">
                <h2>Unassigned Research Briefings Topics</h2>
                <ul class="research-briefings-sortable research-briefings-unassigned">
                    <?php echo research_briefings_format_rb_topics($currentSettings); ?>
                </ul>
            </div>
        </div>
        <form class="research-briefings-form" method="post" action="<?php echo $_SERVER['REQUEST_URI'] ?>">
            <input type="hidden" name="research-briefings-crosstag-update" value="1">
            <!-- preserve previously set data -->
            <?php
                foreach ($currentSettings as $key => $value) {
                    foreach ($currentSettings[$key] as $topKey => $topVal) {
                        echo '<input class="hidden" type="text" name="crosstagged-category['.$key.']['.$topKey.']" value="'.$topVal.'">';
                    }
                }
            ?>
            <input type="submit" class="button action" value="Save">
        </form>
    </div>
    <?php
}

// Needs writing
function research_briefings_format_wp_categories($currentSettings) {
    $categories = get_categories(array('hide_empty' => false));

    foreach ($categories as $category) {
        $alreadySet = false;
        $extraLi = '';
        if(array_key_exists($category->term_id, $currentSettings)) {
            $alreadySet = true;
            foreach ($currentSettings[$category->term_id] as $key => $value) {
                $extraLi = $extraLi . '<li data-topic-name="'.$value.'">'.$key.'</li>';
            }
        }

        $formatted = $formatted . '<ul class="research-briefings-sortable" data-category-id="'.$category->term_id.'"><h3>' . $category->name . '</h3>'.$extraLi.'</ul>';
    }
    return $formatted;
}

/**
 * Return RB API topics that have not been mapped to WordPress categories
 *
 * @param array $currentSettings Unserialized JSON array of current WP IDs => Research Briefing API topics
 * @return string List of items for use on crosstagging admin page
 */
function research_briefings_format_rb_topics($currentSettings) {

    // Get all posts in correct category
    $posts = get_posts(array(
        'post_status' => array('publish', 'private'),
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_type' => 'research-briefing'
    ));

    // Get all WP categories
    $categories = get_terms([
        'taxonomy' => 'category',
        'fields' => 'ids',
        'hide_empty' => false
    ]);

    $unmappedTerms = [];
    $errors = [];

    // Build array of term URLs already mapped to WP categories
    $alreadyTagged = [];
    foreach ($currentSettings as $wpCategoryId => $apiTerms) {

        // Skip if WP category no longer exists so we can retag
        if (!in_array($wpCategoryId, $categories)) {
            continue;
        }

        foreach ($apiTerms as $name => $url) {
            $alreadyTagged[] = $url;
        }
    }
    
    // Loop over each post
    foreach($posts as $p) {
        try {
            // Get topics (these are copied from the RB API)
            $topics = get_post_meta($p, 'topics', true);
            if (empty($topics)) {
                // Topics not set for this research briefing
                continue;
            }
            $topics = json_decode($topics, false, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $errors[] = sprintf('Error decoding API topics for post ID <a href="/wp-admin/post.php?post=%s&action=edit">%s</a>', $p, $p);
            continue;
        }

        /**
        $topics format (API term data):

         [0] => stdClass Object
        (
            [_about] => http://data.parliament.uk/terms/95492
            [prefLabel] => stdClass Object
                (
                    [_value] => Armed forces
                )

        )

        $currentSettings format (WP ID, API Terms):

        [46] => Array
        (
            [Economic policy] => http://data.parliament.uk/terms/95560
            [Economic situation] => http://data.parliament.uk/terms/95561
            [Financial institutions] => http://data.parliament.uk/terms/95602
            [Financial services] => http://data.parliament.uk/terms/95603
            [Loans] => http://data.parliament.uk/terms/95664
            [Regulation] => http://data.parliament.uk/terms/95733
            [Taxation] => http://data.parliament.uk/terms/95764
            [International development] => http://data.parliament.uk/terms/95646
            [Incomes and poverty] => http://data.parliament.uk/terms/95637
            [Public expenditure] => http://data.parliament.uk/terms/95724
            [Competition] => http://data.parliament.uk/terms/95531
            [Consumers] => http://data.parliament.uk/terms/95534
            [Insolvency] => http://data.parliament.uk/terms/95642
            [World economy] => http://data.parliament.uk/terms/95785
        )
        */

        if (is_array($topics)) {
            foreach ($topics as $topic) {
                if (!in_array($topic->_about, $alreadyTagged)) {
                    $unmappedTerms[$topic->_about] = ['title' => $topic->prefLabel->_value, 'id' => $topic->_about];
                }
            }

        }
    }

    $content = '';

    if (!empty($errors)) {
        $content .= '<p><em>' . implode(', ', $errors) . '</em></p>';
    }

    foreach ($unmappedTerms as $topic) {
        $content = $content . '<li data-topic-name="'.$topic['id'].'">'.$topic['title'].'</li>';
    }

    return $content;
}

$crossTagOptions = get_option('research_briefings_crosstagged');
if($crossTagOptions == false) {
    update_option('research_briefings_crosstagged', array());
}
