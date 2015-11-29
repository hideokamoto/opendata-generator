<?php
/**
 * @package Opendata Generator
 * @version 3.0.1alpha
 */
/*
Plugin Name: Opendata Generator
Plugin URI: https://github.com/hideokamoto/opendata-generator/
Description: This Plugin can make jsonld for Linked Open Data. Using CustomField Plugin.
Author: Hidetaka Okamoto
Version: 3.0alpha
Author URI: http://wp-kyoto.net/
*/

class Opendata_generator {

    /**
  	 * __construct
  	 */
  	public function __construct() {
    		require_once plugin_dir_path( __FILE__ ) . 'classes/class.config.php';
      	require_once plugin_dir_path( __FILE__ ) . 'classes/admin/class.admin.panels.php';
      	require_once plugin_dir_path( __FILE__ ) . 'classes/admin/class.admin.schema.php';
        require_once plugin_dir_path( __FILE__ ) . 'classes/ep/class.ep.mapping.php';
        require_once plugin_dir_path( __FILE__ ) . 'classes/ep/class.ep.schema.php';
        require_once plugin_dir_path( __FILE__ ) . 'classes/class.jsonld.content.php';
    		add_action( 'plugins_loaded', array( $this , 'plugins_loaded' ) );
        register_activation_hook( __FILE__ , array( $this , 'activation_callback' ) );
    		//register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );
  	}
  	/**
  	 * 翻訳ファイルの読み込み
  	 */
  	public function plugins_loaded() {
        load_plugin_textdomain (
      			ODG_Config::NAME,
      			false,
      			dirname( plugin_basename( __FILE__ ) ) . '/languages'
    		);
    		add_action( 'after_setup_theme', array( $this, 'after_setup_theme' ) );
  	}

  	/**
  	 * 各クラスの読み込み
  	 */
  	public function after_setup_theme() {
    		add_action( 'init'             , array( $this, 'register_post_type' ) );
        add_action( 'init'             , array( $this, 'add_endpoint' ) );
      	add_action( 'admin_menu'       , array( $this, 'admin_menu' ) );
        add_action( 'admin_init'       , array( $this, 'admin_init' ) );
        add_action( 'template_redirect', array( $this, 'odg_redirect' ));
  	}

    /**
     * 有効化時の処理
     */
     private function activation_callback() {
          $this->add_endpoint();
          flush_rewrite_rules();
     }

     /**
      * エンドポイントの追加
      */
     private function add_endpoint() {
          add_rewrite_endpoint('odg-jsonld',EP_ROOT);
          add_rewrite_endpoint('odg-jsonld-context', EP_ROOT);
     }

  	/**
  	 * カスタム投稿タイプの登録。メニュー表示は別メソッドで実行
  	 */
  	public function register_post_type() {
    		$labels = array(
      			'name'               => __( ODG_Config::DISPNAME, ODG_Config::NAME ),
      			'menu_name'          => __( ODG_Config::DISPNAME, ODG_Config::NAME ),
      			'name_admin_bar'     => __( ODG_Config::DISPNAME, ODG_Config::NAME ),
      			'add_new'            => __( 'Add New', ODG_Config::NAME ),
      			'add_new_item'       => __( 'Add New', ODG_Config::NAME ),
      			'new_item'           => __( 'New Field', ODG_Config::NAME ),
      			'edit_item'          => __( 'Edit Field', ODG_Config::NAME ),
      			'view_item'          => __( 'View Field', ODG_Config::NAME ),
      			'all_items'          => __( 'All Fields', ODG_Config::NAME ),
      			'search_items'       => __( 'Search Fields', ODG_Config::NAME ),
      			'parent_item_colon'  => __( 'Parent Fields:', ODG_Config::NAME ),
      			'not_found'          => __( 'No Fields found.', ODG_Config::NAME ),
      			'not_found_in_trash' => __( 'No Fields found in Trash.', ODG_Config::NAME )
    		);
        $args = array(
            'label'           => ODG_Config::DISPNAME,
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'capability_type' => 'page',
            'supports'        => array( 'title', 'custom-fields','page-attributes' ),
            'show_in_menu'    => false,
        );
    		register_post_type( ODG_Config::NAME, $args );
  	}

  	/**
  	 * 管理画面にメニューを追加
  	 */
  	public function admin_menu() {
        $ODG_Admin_Panels = new ODG_Admin_Panels();
        add_menu_page(
            __(ODG_Config::DISPNAME, ODG_Config::NAME),
            __(ODG_Config::DISPNAME, ODG_Config::NAME),
            'administrator',
            'odg-admin-menu',
            array( $ODG_Admin_Panels , 'odg_admin_menu' ),
      			false,
      			'81'
        );
        add_submenu_page(
            'odg-admin-menu',
            __('Schema Settings', ODG_Config::NAME),
            __('Schema Settings', ODG_Config::NAME),
            'administrator',
            'odg-schema',
            array( $ODG_Admin_Panels, 'odg_admin_schema' )
        );
    		add_submenu_page(
          'odg-admin-menu',
          __("Mapping List", ODG_Config::NAME),
          __("Mapping List", ODG_Config::NAME),
          'administrator',
    			'edit.php?post_type=' . ODG_Config::NAME
    		);
    		add_submenu_page(
    			'odg-admin-menu',
          __("Create Mapping", ODG_Config::NAME),
          __("Create Mapping", ODG_Config::NAME),
          'administrator',
    			'post-new.php?post_type=' . ODG_Config::NAME
    		);
  	}

    /**
     * 各種設定の保存処理
     */
    public function admin_init () {
        if( isset ( $_POST['odg-schema'] ) && $_POST['odg-schema'] ){
            $Schema = new ODG_Admin_Schema();
            $Schema->save_schema();
        }
    }

    /**
     * データの表示処理
     */
    public function odg_redirect() {
        header("Access-Control-Allow-Origin: *");

        global $wp_query;
        if( in_array( 'odg-jsonld' , $wp_query->query ) || isset( $wp_query->query['odg-jsonld'] ) ) {
            $this->get_content();
        } elseif ( in_array( 'odg-jsonld-context' , $wp_query->query ) || isset( $wp_query->query['odg-jsonld-context'] ) ) {
            $this->get_context();
        }
    }

    public function get_content() {

        //Get Defined Mapping Data
        $Map = new ODG_Ep_Mapping();
        $mapped_schema_array = $Map->get_Schema();

        //Set Content Type
        header('Content-type: application/ld+json; charset=UTF-8');

        //Get Content(JSON-LD)
        $JSONLD_Cont = new ODG_JSONLD_Content();
        $jsonld_content = $JSONLD_Cont->get_content( $mapped_schema_array );

        if ( !$jsonld_content ) {
            global $wp_query;
            $wp_query->set_404();
            status_header(404);
            exit;
        }

        //Convert JSON-LD
        $jsonld = json_encode($jsonld_content , JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        //Show JSON-LD
        echo $jsonld;
        exit;
    }

    public function get_context(){
        //Get Defined Mapping Data
        $Schema = new ODG_Ep_Schema();
        //Set Content Type
        header('Content-type: application/ld+json; charset=UTF-8');
        //Show JSON-LD
        echo $Schema->get_context();
        exit;
    }

}

new Opendata_generator();
