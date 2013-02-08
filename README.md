CSS_Customizer
==============

Drop in class for implementing a custom CSS editor either as a standalone settings page or a meta box.

Use in Plugins
--------------

1. Simply clone the repository to a convenient location, like `/plugin-name/includes/css-customizer`
1. Include the `TenUp_CSS_Customizer.php` class in your plugin
1. Instantiate the class for use either as a standalone settings page or as a meta box

Instantiation
-------------

The `TenUp_CSS_Customizer` class takes several options opon instantiation:

1. `$handle` - The first parameter is the unique string used to identify the instance.  This string will be used in rewrites, option names, and permalinks, so it should be URL and database safe. Defaults to 'tenup_css'.
1. `$scheme` - Either 'page' when using the class in a settings page or 'meta' when using the page as a meta box. Defaults to 'page.'
1. `$settings` - Associative array of translated labels and post types for use with the meta box.

### Settings Array

- 'settings-section-label' - Label used as a section heading. (Settings page mode)
- 'settings-field-label' - Label used to identify the custom CSS textarea. (Settings page mode)
- 'options-page-label' - Label used as a heading on the settings page. (Settings page mode)
- 'metabox-label' - Label used on the custom meta box. (Meta box mode)
- 'metabox-post-types' - Array of post type slugs to which you want the meta box added. (Meta box mode)

Actions/Filters
-------

### Instance Filter

The 'tenup_css_customizer_instances' filter will return an associative array of all instances.  For example, if the class is instantiated with default options:

    new TenUp_CSS_Customizer();

The filter will return:

    array(
        'tenup_css' => object of type TenUp_CSS_Customizer
    )

If multiple instances exist, they will *all* be returned by the filter.

### Rewrite Registration

The class uses a set of custom permalinks to fetch and return the custom CSS document.  However, these permalinks will not work unless rewrite rules are flushed after they're added.

To make life easier, you can add a `do_action()` call to your plugin activation hook to force the rewrite rules into the system.

    new TenUp_CSS_Customizer();

    function my_plugin_activation() {
        do_action( 'tenup_css_register_rewrites' );
        flush_rewrite_rules();
    }
    register_activation_hook( __FILE__, 'my_plugin_activation' );