<?php
/*
  Plugin Name: Additional Email for order By Category
  Plugin URI:  http://www.mxtechweb.com
  Description: Send additional Email for each order by Wocommerce category.
  Version: 2.0.0
  Author:Meldin Xavier
  Author URI: http://mxtechweb.com
  License: GPLv2
 */

class AdditionalEmailForOrder {

    var $db_version = '1.0.0';
    var $db_table = 'woocommerce_order_additional_email_by_category';
    var $preifx = 'aefo_';

    public function __construct() {
        register_activation_hook(__FILE__, array($this, 'plugin_install'));
        if (is_admin()) {
            add_action('admin_menu', array($this, 'add_plugin_menu'));
        }

        add_action('woocommerce_checkout_order_processed', array($this, 'Additional_Email_Sendmail'), 10, 1);
    }

    function plugin_install() {
        global $wpdb;
        $table_name = $wpdb->prefix . $this->db_table;
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name ( 
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		assigned_time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		category_id bigint NOT NULL,
		email_template varchar(200) NOT NULL,
		email_address varchar(200)  NOT NULL,
		PRIMARY KEY  (id),
                UNIQUE KEY `category_id` (`category_id`)
	) $charset_collate;";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta($sql);
        add_option($this->preifx . 'version', $this->db_version);
    }

    public function add_plugin_menu() {
        add_menu_page('Additional Email', 'Additional Email', 'manage_options', 'aefo_list', array($this, 'aefo_list'));
        add_submenu_page('aefo_list', 'Additional Email Add', 'Add Template', 'manage_options', 'aefo_add', array($this, 'aefo_add'));
        add_submenu_page('Additional Email Edit', 'Additional Email Edit', 'Add Template', 'manage_options', 'aefo_edit', array($this, 'aefo_edit'));
    }

    public function aefo_add() {
        global $wpdb;
        if ($_POST['submit']) {
            $data = $_POST;
            unset($data['submit']);
            $data['assigned_time'] = date('Y-m-d H:i:s');
            $wpdb->insert($wpdb->prefix . $this->db_table, $data);
            $message = '<div class="updated below-h2" id="message"><p>Item Added </p></div>';
            if ($wpdb->dbh->errno == '1062') {
                $message = '<div class="error below-h2" id="message"><p>Email already assigned to this category</p></div>';
            }
        }
        $taxonomy = 'product_cat';
        $orderby = 'name';
        $show_count = 0;
        $pad_counts = 0;
        $hierarchical = 1;
        $title = '';
        $empty = 0;
        $args = array(
            'taxonomy' => $taxonomy,
            'orderby' => $orderby,
            'show_count' => $show_count,
            'pad_counts' => $pad_counts,
            'hierarchical' => $hierarchical,
            'title_li' => $title,
            'hide_empty' => $empty
        );
        $all_categories = get_categories($args);
        $select = '<select name="category_id"  id="category_id"  required="true"><option value="">Select</option>';
        foreach ($all_categories as $cat) {
            if ($cat->category_parent == 0) {
                $category_id = $cat->term_id;
                $select .= '<option value="' . $category_id . '" >  ' . $cat->name . '</option>';
                $args2 = array(
                    'taxonomy' => $taxonomy,
                    'child_of' => 0,
                    'parent' => $category_id,
                    'orderby' => $orderby,
                    'show_count' => $show_count,
                    'pad_counts' => $pad_counts,
                    'hierarchical' => $hierarchical,
                    'title_li' => $title,
                    'hide_empty' => $empty
                );
                $sub_cats = get_categories($args2);
                if ($sub_cats) {
                    foreach ($sub_cats as $sub_category) {
                        $select .= '<option value="' . $sub_category->term_id . '" >  ' . $sub_category->name . '</option>';
                    }
                }
            }
        }
        $select .= '</select>';
        $mailer = WC()->mailer();
        $email_templates = $mailer->get_emails();
        ?>
        <div class="wrap">
            <h2><?php _e('Add new Email'); ?> <a class="button-primary" href="admin.php?page=aefo_list"><?php _e('Back'); ?></a></h2>
        <?php echo $message; ?>
            <form method="post" >
                <table class="form-table">
                    <tr>
                        <th><?php _e('Category'); ?></th>
                        <td><?php echo $select; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email Template')?></th>
                        <td><select class="for-select" name="email_template" id="email_template" required="true">
                                <option value="">Select</option>
        <?php foreach ($email_templates as $email_key => $email) { ?>
                                    <option value="<?php echo $email_key; ?>"><?php echo $email->get_title(); ?></option>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email Address'); ?></th>
                        <td>
                            <input type="email" name="email_address" id="email_address" required="true" >
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><?php submit_button('Save'); ?></td>
                    </tr>
                </table>
            </form>
        </div><?php
                    }

                    public function aefo_edit() {
                        global $wpdb;

                        if ($_POST['submit']) {
                            $data = $_POST;
                            unset($data['submit']);
                            $data['assigned_time'] = date('Y-m-d H:i:s');
                            $wpdb->update($wpdb->prefix . $this->db_table, $data, array('id' => intval($_GET['aefo_item'])));
                            $message = '<div class="updated below-h2" id="message"><p>Item updated </p></div>';
                            if ($wpdb->dbh->errno == '1062') {
                                $message = '<div class="error below-h2" id="message"><p>Email already assigned to this category</p></div>';
                            }
                        }
                        $data = $wpdb->get_row('select * from ' . $wpdb->prefix . $this->db_table . ' where id=' . intval($_GET['aefo_item']));
                        $taxonomy = 'product_cat';
                        $orderby = 'name';
                        $show_count = 0;
                        $pad_counts = 0;
                        $hierarchical = 1;
                        $title = '';
                        $empty = 0;
                        $args = array(
                            'taxonomy' => $taxonomy,
                            'orderby' => $orderby,
                            'show_count' => $show_count,
                            'pad_counts' => $pad_counts,
                            'hierarchical' => $hierarchical,
                            'title_li' => $title,
                            'hide_empty' => $empty
                        );
                        $all_categories = get_categories($args);
                        $select = '<select name="category_id"  id="category_id"  required="true"><option value="">Select</option>';
                        foreach ($all_categories as $cat) {
                            if ($cat->category_parent == 0) {
                                $category_id = $cat->term_id;
                                $select .= '<option value="' . $category_id . '" ';
                                $select .= ($data->category_id == $category_id) ? ' selected="selected" ' : ' ';
                                $select .='>  ' . $cat->name . '</option>';
                                $args2 = array(
                                    'taxonomy' => $taxonomy,
                                    'child_of' => 0,
                                    'parent' => $category_id,
                                    'orderby' => $orderby,
                                    'show_count' => $show_count,
                                    'pad_counts' => $pad_counts,
                                    'hierarchical' => $hierarchical,
                                    'title_li' => $title,
                                    'hide_empty' => $empty
                                );
                                $sub_cats = get_categories($args2);
                                if ($sub_cats) {
                                    foreach ($sub_cats as $sub_category) {
                                        $select .= '<option value="' . $sub_category->term_id . '" ';
                                        $select .= ($data->category_id == $sub_category->term_id) ? ' selected="selected" ' : ' ';
                                        $select .='>  ' . $sub_category->name . '</option>';
                                    }
                                }
                            }
                        }
                        $select .= '</select>';
                        $mailer = WC()->mailer();
                        $email_templates = $mailer->get_emails();
                                ?>
        <div class="wrap">
            <h2><?php _e('Edit Email'); ?> <a class="button-primary" href="admin.php?page=aefo_list"><?php _e('Back');?></a></h2>
        <?php echo $message; ?>
            <form method="post" >
                <table class="form-table">
                    <tr>
                        <th><?php _e('Category'); ?></th>
                        <td><?php echo $select; ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email Template'); ?></th>
                        <td><select class="for-select" name="email_template" id="email_template" required="true">
                                <option value="">Select</option>
        <?php foreach ($email_templates as $email_key => $email) { ?>
                                    <option value="<?php echo $email_key; ?>"
                                    <?php echo ($data->email_template == $email_key) ? ' selected="selected" ' : ' '; ?>
                                            ><?php echo $email->get_title(); ?></option>
                                <?php } ?>
                            </select></td>
                    </tr>
                    <tr>
                        <th><?php _e('Email Address'); ?></th>
                        <td>
                            <input type="email" name="email_address" id="email_address" required="true" value="<?php echo $data->email_address; ?>" >
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                        <td><?php submit_button('Save'); ?></td>
                    </tr>
                </table>
            </form>
        </div><?php
                    }

                    function Additional_Email_Sendmail($order_id) {
                        global $wpdb;
                        $email = WC()->mailer();
                        $order = new WC_Order($order_id);
                        $items = $order->get_items();
                        foreach ($items as $item) {
                            $terms = get_the_terms($item['product_id'], 'product_cat');
                            foreach ($terms as $term) {
                                $product_cat_id = $term->term_id;
                                $item = $wpdb->get_row('select * from ' . $wpdb->prefix . $this->db_table . ' where category_id=' . $product_cat_id);
                                if ($item) {
                                    $emailtemplate = $email->emails[$item->email_template];
                                    $emailtemplate->object = $order;
                                    $emailtemplate->find['order-number'] = '{order_number}';
                                    $emailtemplate->find['order-date'] = '{order_date}';
                                    $emailtemplate->replace['order-number'] = $order_id;
                                    $emailtemplate->replace['order-date'] = date_i18n(wc_date_format(), strtotime($order->order_date));
                                    $subject = str_replace($emailtemplate->find, $emailtemplate->replace, $emailtemplate->get_subject());
                                    $message = $emailtemplate->get_content();
                                    $email->send($item->email_address, $subject, $message);
                                    unset($item);
                                    unset($emailtemplate);
                                }
                            }
                        }
                        
                    }

                    function aefo_list() {
                        include_once 'class.php';
                        $list = new AEFO_List($this->db_table);
                        $list->prepare_items();
                                ?>
        <div class="wrap">
            <div id="icon-users" class="icon32"><br/></div>
            <h2><?php _e('Email List');?> <a class="button-primary" href="admin.php?page=aefo_add"><?php _e('Add');?></a></h2>
        <?php $list->message(); ?>
            <form id="email-filter" method="get">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>" />
        <?php $list->display() ?>
            </form>
        </div>
        <?php
    }

}

new AdditionalEmailForOrder();

