<?php

class AEFO_List extends WP_List_Table {
    var $email_templates;
    var $itemtable= '';
    var $message= '';
    function __construct($itemtable) {
        $this->itemtable = $itemtable;
        
        parent::__construct(array(
            'singular' => 'aefo_item',
            'plural' => 'aefo_items',
            'ajax' => false
        ));
        $mailer = WC()->mailer();
        $this->email_templates = $mailer->get_emails();
    }
    function message(){
        if($this->message){
       echo  '<div class="updated below-h2" id="message"><p>'.$this->message.'</p></div>';
        }
    }
            function prepare_items() {
       global $wpdb;
        $table = $wpdb->prefix . $this->itemtable;
        $per_page = 100;

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();


        $this->_column_headers = array($columns, $hidden, $sortable);


        $this->process_bulk_action();


        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table");


        $paged = isset($_REQUEST['paged']) ? max(0, intval($_REQUEST['paged']) - 1) : 0;
        $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'category_id';
        $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';


        $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged));
        echo $wpdb->last_error;

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page)
        ));
    }
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'category_id' => 'Category',
            'email_template' => 'Template',
            'email_address' => 'Email Address'
        );
        return $columns;
    }
     function column_cb($item){
        return sprintf(
            '<input type="checkbox" name="%1$s[]" value="%2$s" />',
            /*$1%s*/ $this->_args['singular'],  
            /*$2%s*/ $item->id                
        );
    }
//     function get_sortable_columns() {
//        $sortable_columns = array(
//            'email_template'    => array('Template',false),
//            'email_address'  => array('Email',false)
//        );
//        return $sortable_columns;
//    }
    function column_default($item, $column_name) {
        return $item->$column_name;
    }
    function get_bulk_actions() {
        $actions = array(
            'delete'    => 'Delete'
        );
        return $actions;
    }
      function process_bulk_action() {
        global $wpdb;
        $table = $wpdb->prefix . $this->itemtable;

        if ('delete' === $this->current_action()) {
            $ids = isset($_REQUEST['aefo_item']) ? $_REQUEST['aefo_item'] : array();
            if (is_array($ids))
                $ids = implode(',', $ids);
            if (!empty($ids)) {
                $wpdb->query("DELETE FROM $table WHERE  id IN($ids)");
                 $this->message =  'Items deleted';
            }
         

        }
    }

    function column_category_id($item) {
        if ($term = get_term_by('id', $item->category_id, 'product_cat')) {
            $title = $term->name;
        }
          //Build row actions
        $actions = array(
            'edit'      => sprintf('<a href="?page=%s&action=%s&aefo_item=%s">Edit</a>','aefo_edit','edit',$item->id),
            'delete'    => sprintf('<a href="?page=%s&action=%s&aefo_item=%s">Delete</a>',$_REQUEST['page'],'delete',$item->id),
        );
        
        //Return the title contents
        return sprintf('%1$s %3$s',
            /*$1%s*/ $title,
            /*$2%s*/ $item->id,
            /*$3%s*/ $this->row_actions($actions)
        );
        
    }
    function column_email_template($item) {
        return $this->email_templates[$item->email_template]->get_title();
    }
    

}
