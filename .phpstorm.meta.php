<?php # -*- coding: utf-8 -*-

namespace PHPSTORM_META {

	$STATIC_METHOD_TYPES = [
		// STATIC call key to make static (1) & dynamic (2) calls work "KEY" instanceof Class maps KEY to Class
		\Inpsyde\MultilingualPress\MultilingualPress::resolve( '' ) => [
			'' == '@',

			'multilingualpress.assets' instanceof \Mlp_Assets_Interface,
			'multilingualpress.content_relations' instanceof \Mlp_Content_Relations_Interface,
			'multilingualpress.languages' instanceof \Mlp_Language_Api_Interface,
			'multilingualpress.post_types' instanceof \ArrayObject,
			'multilingualpress.properties' instanceof \Inpsyde\MultilingualPress\Core\Properties,
		],
		// NEW INSTANCE is to make ArrayAccess (3) style factory work
		new \Inpsyde\MultilingualPress\Service\Container            => [
			'' == '@',

			'multilingualpress.assets' instanceof \Mlp_Assets_Interface,
			'multilingualpress.blogs_duplicator' instanceof \Mlp_Duplicate_Blogs,
			'multilingualpress.content_relations' instanceof \Mlp_Content_Relations_Interface,
			'multilingualpress.custom_columns' instanceof \Mlp_Custom_Columns,
			'multilingualpress.dashboard_widget' instanceof \Mlp_Dashboard_Widget,
			'multilingualpress.global_switcher_post' instanceof \Mlp_Global_Switcher,
			'multilingualpress.language_db_access' instanceof \Mlp_Data_Access,
			'multilingualpress.languages' instanceof \Mlp_Language_Api_Interface,
			'multilingualpress.locations' instanceof \Mlp_Locations_Interface,
			'multilingualpress.module_manager' instanceof \Mlp_Module_Manager_Interface,
			'multilingualpress.module.advanced_translator' instanceof \Mlp_Advanced_Translator,
			'multilingualpress.module.admin_bar_customizer' instanceof \Mlp_Admin_Bar_Customizer,
			'multilingualpress.module.post_type_support' instanceof \Mlp_Cpt_Translator,
			'multilingualpress.module.quicklinks' instanceof \Mlp_Quicklink,
			'multilingualpress.module.redirect' instanceof \Mlp_Redirect,
			'multilingualpress.module.trasher' instanceof \Mlp_Trasher,
			'multilingualpress.module.user_admin_language' instanceof \Mlp_User_Backend_Language,
			'multilingualpress.nav_menu_controller' instanceof \Mlp_Nav_Menu_Controller,
			'multilingualpress.post_types' instanceof \ArrayObject,
			'multilingualpress.properties' instanceof \Inpsyde\MultilingualPress\Core\Properties,
			'multilingualpress.relationship_changer' instanceof \Mlp_Relationship_Changer,
			'multilingualpress.relationship_control' instanceof \Mlp_Relationship_Control,
			'multilingualpress.site_manager' instanceof \Mlp_Module_Manager_Interface,
			'multilingualpress.site_relations' instanceof \Mlp_Site_Relations_Interface,
			'multilingualpress.table_duplicator' instanceof \Mlp_Table_Duplicator_Interface,
			'multilingualpress.table_list' instanceof \Mlp_Db_Table_List_Interface,
			'multilingualpress.term_translation_controller' instanceof \Mlp_Term_Translation_Controller,
			'multilingualpress.translatable_post_data' instanceof \Mlp_Translatable_Post_Data_Interface,
			'multilingualpress.translation_metabox' instanceof \Mlp_Translation_Metabox,
		],
	];
}
