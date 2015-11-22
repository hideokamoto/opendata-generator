<?php
/**
 * @package ODG_Config::DISPNAME
 * @version 3.0alpha
 */
/*
Plugin Name: ODG_Config::DISPNAME
Plugin URI: https://github.com/hideokamoto/ODG_Config::NAME/
Description: This Plugin can make jsonld for Linked Open Data.Using Advanced CustomField Plugin.
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
  		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ) );
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
  		add_action( 'init'          , array( $this, 'register_post_type' ) );
    	add_action( 'admin_menu'    , array( $this, 'admin_menu' ) );
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
        'supports'        => array( 'title', 'page-attributes' ),
        'show_in_menu'    => false,
      );
  		register_post_type( ODG_Config::NAME, $args );
  	}

  	/**
  	 * 管理画面にメニューを追加
  	 */
  	public function admin_menu() {
      add_menu_page(
          __(ODG_Config::DISPNAME, ODG_Config::NAME),
          __(ODG_Config::DISPNAME, ODG_Config::NAME),
          'administrator',
          'odg-admin-menu',
          array( $this, 'odg_admin_menu' ),
    			false,
    			'81'
      );
      add_submenu_page(
          'odg-admin-menu',
          __('Schema Settings', 'make_json_ld'),
          __('Schema Settings', 'make_json_ld'),
          'administrator',
          'odg-schema',
          array( $this, 'odg_schema' )
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

    public function odg_admin_menu(){
      echo "string";
    }

    public function odg_schema(){
      echo "string";
    }

}
new Opendata_generator();
