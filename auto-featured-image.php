<?php
/*
Plugin Name: Auto Featured Image Generator
Description: Genera automáticamente imágenes destacadas a partir del título de las publicaciones.
Version: 1.0
Author: Oscar Mangut Dev
*/

if (!defined('ABSPATH')) {
    exit; // Evita el acceso directo
}

// Hooks para generar imagen al guardar el post
add_action('save_post', 'afi_generate_featured_image');
add_action('admin_menu', 'afi_add_admin_menu');

function afi_generate_featured_image($post_id) {
    // Evita bucles infinitos
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Verifica que el post no tenga ya una imagen destacada
    if (has_post_thumbnail($post_id)) return;

    // Obtén el título del post y elige una plantilla
    $title = get_the_title($post_id);
    $template = plugin_dir_path(__FILE__) . 'templates/default_template.png';

    // Genera la imagen con GD o Imagick (GD en este caso)
    $image_path = afi_create_image_from_template($title, $template);

    // Establece la imagen como imagen destacada
    if ($image_path) {
        afi_set_featured_image($image_path, $post_id);
    }
}

function afi_create_image_from_template($title, $template) {
    $image = imagecreatefrompng($template);

    // Colores y fuente para el texto
    $color = imagecolorallocate($image, 255, 255, 255);
    $font = plugin_dir_path(__FILE__) . 'fonts/Roboto-Regular.ttf';

    // Agrega el título a la imagen
    imagettftext($image, 20, 0, 20, 60, $color, $font, $title);

    // Guarda la imagen en uploads
    $upload_dir = wp_upload_dir();
    $output_file = $upload_dir['path'] . '/featured_' . uniqid() . '.png';
    imagepng($image, $output_file);
    imagedestroy($image);

    return $output_file;
}

function afi_set_featured_image($image_path, $post_id) {
    $wp_filetype = wp_check_filetype(basename($image_path), null);
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title'     => sanitize_file_name(basename($image_path)),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );

    $attach_id = wp_insert_attachment($attachment, $image_path, $post_id);
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    $attach_data = wp_generate_attachment_metadata($attach_id, $image_path);
    wp_update_attachment_metadata($attach_id, $attach_data);

    set_post_thumbnail($post_id, $attach_id);
}

// Añade el menú de configuración para opciones de plantilla
function afi_add_admin_menu() {
    add_options_page(
        'Auto Featured Image Settings',
        'Auto Featured Image',
        'manage_options',
        'auto-featured-image',
        'afi_options_page'
    );
}

function afi_options_page() {
    ?>
    <div class="wrap">
        <h1>Auto Featured Image Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('afi_settings_group');
            do_settings_sections('afi_settings_group');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Choose a Template:</th>
                    <td>
                        <input type="text" name="afi_template" value="<?php echo esc_attr(get_option('afi_template')); ?>" />
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
?>
