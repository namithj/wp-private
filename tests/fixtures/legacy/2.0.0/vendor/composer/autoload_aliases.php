<?php

// Functions and constants

namespace {

}


namespace WpPrivate\Vendor {

    use BrianHenryIE\Strauss\Types\AutoloadAliasInterface;

    /**
     * @see AutoloadAliasInterface
     *
     * @phpstan-type ClassAliasArray array{'type':'class',isabstract:bool,classname:string,namespace?:string,extends:string,implements:array<string>}
     * @phpstan-type InterfaceAliasArray array{'type':'interface',interfacename:string,namespace?:string,extends:array<string>}
     * @phpstan-type TraitAliasArray array{'type':'trait',traitname:string,namespace?:string,use:array<string>}
     * @phpstan-type EnumAliasArray array{'type':'enum',enumname:string,namespace?:string,concrete:string}
     * @phpstan-type AutoloadAliasArray array<string,ClassAliasArray|InterfaceAliasArray|TraitAliasArray|EnumAliasArray>
     */
    class AliasAutoloader
    {
        private string $includeFilePath;

        /**
         * @var AutoloadAliasArray
         */
        private array $autoloadAliases = array (
  'PedalCMS\\CassetteCMFBlocks\\Binding\\Binding_Source_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Binding_Source_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Binding',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Binding\\Binding_Source_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Binding\\Meta_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Meta_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Binding',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Binding\\Meta_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Binding\\Settings_Binding_Source' => 
  array (
    'type' => 'class',
    'classname' => 'Settings_Binding_Source',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Binding',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Binding\\Settings_Binding_Source',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\CassetteCMFBlocks' => 
  array (
    'type' => 'class',
    'classname' => 'CassetteCMFBlocks',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\CassetteCMFBlocks',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Compat\\Cmf_Bridge' => 
  array (
    'type' => 'class',
    'classname' => 'Cmf_Bridge',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Compat',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Compat\\Cmf_Bridge',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Compat\\Requirements' => 
  array (
    'type' => 'class',
    'classname' => 'Requirements',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Compat',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Compat\\Requirements',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Asset_Loader' => 
  array (
    'type' => 'class',
    'classname' => 'Asset_Loader',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Asset_Loader',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Block_Definition' => 
  array (
    'type' => 'class',
    'classname' => 'Block_Definition',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Block_Definition',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Block_Manager' => 
  array (
    'type' => 'class',
    'classname' => 'Block_Manager',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Block_Manager',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Block_Registry' => 
  array (
    'type' => 'class',
    'classname' => 'Block_Registry',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Block_Registry',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Category_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Category_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Category_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Document_Scope_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Document_Scope_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Document_Scope_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Editor_Payload' => 
  array (
    'type' => 'class',
    'classname' => 'Editor_Payload',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Editor_Payload',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Editor_Scope_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Editor_Scope_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Editor_Scope_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Field_Collection' => 
  array (
    'type' => 'class',
    'classname' => 'Field_Collection',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Field_Collection',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Identity' => 
  array (
    'type' => 'class',
    'classname' => 'Identity',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Identity',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Pattern_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Pattern_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Pattern_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Core\\Variation_Registrar' => 
  array (
    'type' => 'class',
    'classname' => 'Variation_Registrar',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Core\\Variation_Registrar',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Json\\Block_Schema_Validator' => 
  array (
    'type' => 'class',
    'classname' => 'Block_Schema_Validator',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Json',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Json\\Block_Schema_Validator',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Attribute_Sanitizer' => 
  array (
    'type' => 'class',
    'classname' => 'Attribute_Sanitizer',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Attribute_Sanitizer',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Block_Renderer' => 
  array (
    'type' => 'class',
    'classname' => 'Block_Renderer',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Block_Renderer',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Conditional_Evaluator' => 
  array (
    'type' => 'class',
    'classname' => 'Conditional_Evaluator',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Conditional_Evaluator',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Deprecation_Builder' => 
  array (
    'type' => 'class',
    'classname' => 'Deprecation_Builder',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Deprecation_Builder',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Deprecation_Migrator' => 
  array (
    'type' => 'class',
    'classname' => 'Deprecation_Migrator',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Deprecation_Migrator',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Expression' => 
  array (
    'type' => 'class',
    'classname' => 'Expression',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Expression',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Markup_Renderer' => 
  array (
    'type' => 'class',
    'classname' => 'Markup_Renderer',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Markup_Renderer',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Render\\Unique_Checker' => 
  array (
    'type' => 'class',
    'classname' => 'Unique_Checker',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Render',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Render\\Unique_Checker',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Rest\\Config_Controller' => 
  array (
    'type' => 'class',
    'classname' => 'Config_Controller',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Rest',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Rest\\Config_Controller',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Rest\\Transform_Controller' => 
  array (
    'type' => 'class',
    'classname' => 'Transform_Controller',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Rest',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Rest\\Transform_Controller',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Ancestor_Options' => 
  array (
    'type' => 'class',
    'classname' => 'Ancestor_Options',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Ancestor_Options',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Area_Resolver' => 
  array (
    'type' => 'class',
    'classname' => 'Area_Resolver',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Area_Resolver',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Attribute_Schema_Mapper' => 
  array (
    'type' => 'class',
    'classname' => 'Attribute_Schema_Mapper',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Attribute_Schema_Mapper',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Control_Catalog' => 
  array (
    'type' => 'class',
    'classname' => 'Control_Catalog',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Control_Catalog',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Control_Mapper' => 
  array (
    'type' => 'class',
    'classname' => 'Control_Mapper',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Control_Mapper',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Meta_Schema_Mapper' => 
  array (
    'type' => 'class',
    'classname' => 'Meta_Schema_Mapper',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Meta_Schema_Mapper',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Supports_Normalizer' => 
  array (
    'type' => 'class',
    'classname' => 'Supports_Normalizer',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Supports_Normalizer',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMFBlocks\\Schema\\Transform_Config' => 
  array (
    'type' => 'class',
    'classname' => 'Transform_Config',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMFBlocks\\Schema',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMFBlocks\\Schema\\Transform_Config',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\CassetteCMF' => 
  array (
    'type' => 'class',
    'classname' => 'CassetteCMF',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\CassetteCMF',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\Abstract_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'Abstract_Handler',
    'isabstract' => true,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\Abstract_Handler',
    'implements' => 
    array (
      0 => 'PedalCMS\\CassetteCMF\\Core\\Handlers\\Handler_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Post_Type_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'Existing_Post_Type_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Post_Type_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Settings_Page_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'Existing_Settings_Page_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Settings_Page_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Taxonomy_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'Existing_Taxonomy_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\Existing_Taxonomy_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Post_Type_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'New_Post_Type_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Post_Type_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Settings_Page_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'New_Settings_Page_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Settings_Page_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Taxonomy_Handler' => 
  array (
    'type' => 'class',
    'classname' => 'New_Taxonomy_Handler',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\New_Taxonomy_Handler',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Manager' => 
  array (
    'type' => 'class',
    'classname' => 'Manager',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Core',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Manager',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\CPT\\Custom_Post_Type' => 
  array (
    'type' => 'class',
    'classname' => 'Custom_Post_Type',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\CPT',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\CPT\\Custom_Post_Type',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Abstract_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Abstract_Field',
    'isabstract' => true,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Abstract_Field',
    'implements' => 
    array (
      0 => 'PedalCMS\\CassetteCMF\\Field\\Field_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Field_Factory' => 
  array (
    'type' => 'class',
    'classname' => 'Field_Factory',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Field_Factory',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Checkbox_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Checkbox_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Checkbox_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Color_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Color_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Color_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Custom_HTML_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Custom_HTML_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Custom_HTML_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Date_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Date_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Date_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Email_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Email_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Email_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Group_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Group_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Group_Field',
    'implements' => 
    array (
      0 => 'PedalCMS\\CassetteCMF\\Field\\Container_Field_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Metabox_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Metabox_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Metabox_Field',
    'implements' => 
    array (
      0 => 'PedalCMS\\CassetteCMF\\Field\\Container_Field_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Number_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Number_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Number_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Password_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Password_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Password_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Radio_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Radio_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Radio_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Repeater_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Repeater_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Repeater_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Secret_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Secret_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Secret_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Select_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Select_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Select_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Tabs_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Tabs_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Tabs_Field',
    'implements' => 
    array (
      0 => 'PedalCMS\\CassetteCMF\\Field\\Container_Field_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Text_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Text_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Text_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Textarea_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Textarea_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Textarea_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Upload_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Upload_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Upload_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\URL_Field' => 
  array (
    'type' => 'class',
    'classname' => 'URL_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\URL_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Fields\\Wysiwyg_Field' => 
  array (
    'type' => 'class',
    'classname' => 'Wysiwyg_Field',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Field\\Fields',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Fields\\Wysiwyg_Field',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Json\\Schema_Validator' => 
  array (
    'type' => 'class',
    'classname' => 'Schema_Validator',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Json',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Json\\Schema_Validator',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Rest\\Settings_Controller' => 
  array (
    'type' => 'class',
    'classname' => 'Settings_Controller',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Rest',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Rest\\Settings_Controller',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Rest\\Settings_Schema' => 
  array (
    'type' => 'class',
    'classname' => 'Settings_Schema',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Rest',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Rest\\Settings_Schema',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Settings\\React_Renderer' => 
  array (
    'type' => 'class',
    'classname' => 'React_Renderer',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Settings',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Settings\\React_Renderer',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Settings\\Settings_Page' => 
  array (
    'type' => 'class',
    'classname' => 'Settings_Page',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Settings',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Settings\\Settings_Page',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Settings\\Settings_Upgrader' => 
  array (
    'type' => 'class',
    'classname' => 'Settings_Upgrader',
    'isabstract' => false,
    'namespace' => 'PedalCMS\\CassetteCMF\\Settings',
    'extends' => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Settings\\Settings_Upgrader',
    'implements' => 
    array (
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Registration_Trait' => 
  array (
    'type' => 'trait',
    'traitname' => 'Field_Registration_Trait',
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Traits',
    'use' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Registration_Trait',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Rendering_Trait' => 
  array (
    'type' => 'trait',
    'traitname' => 'Field_Rendering_Trait',
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Traits',
    'use' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Rendering_Trait',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Saving_Trait' => 
  array (
    'type' => 'trait',
    'traitname' => 'Field_Saving_Trait',
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Traits',
    'use' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Traits\\Field_Saving_Trait',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Core\\Handlers\\Handler_Interface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Handler_Interface',
    'namespace' => 'PedalCMS\\CassetteCMF\\Core\\Handlers',
    'extends' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Core\\Handlers\\Handler_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Container_Field_Interface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Container_Field_Interface',
    'namespace' => 'PedalCMS\\CassetteCMF\\Field',
    'extends' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Container_Field_Interface',
    ),
  ),
  'PedalCMS\\CassetteCMF\\Field\\Field_Interface' => 
  array (
    'type' => 'interface',
    'interfacename' => 'Field_Interface',
    'namespace' => 'PedalCMS\\CassetteCMF\\Field',
    'extends' => 
    array (
      0 => 'WpPrivate\\Vendor\\PedalCMS\\CassetteCMF\\Field\\Field_Interface',
    ),
  ),
);

        public function __construct()
        {
            $this->includeFilePath = __DIR__ . '/autoload_alias.php';
        }

        /**
         * @param string $class
         */
        public function autoload($class): void
        {
            if (!isset($this->autoloadAliases[$class])) {
                return;
            }
            switch ($this->autoloadAliases[$class]['type']) {
                case 'class':
                        $this->load(
                            $this->classTemplate(
                                $this->autoloadAliases[$class]
                            )
                        );
                    break;
                case 'interface':
                    $this->load(
                        $this->interfaceTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'trait':
                    $this->load(
                        $this->traitTemplate(
                            $this->autoloadAliases[$class]
                        )
                    );
                    break;
                case 'enum':
                    // Enums are final so cannot be aliased with an `extends` shim like classes are; `class_alias()`
                    // makes the original name a true alias of the renamed enum, preserving case identity (`===`),
                    // `match` arms, `::cases()`, `::from()` and `instanceof` the enum itself. Unlike the class shim,
                    // the alias does not implement the enum's original interface names, so `instanceof` against an
                    // original interface name is false. Enums require PHP 8.1, but this only executes when an enum
                    // is autoloaded by its original name, i.e. on a runtime already using enums.
                    \class_alias(
                        $this->autoloadAliases[$class]['concrete'],
                        $class
                    );
                    break;
                default:
                    // Never.
                    break;
            }
        }

        private function load(string $includeFile): void
        {
            file_put_contents($this->includeFilePath, $includeFile);
            include $this->includeFilePath;
            file_exists($this->includeFilePath) && unlink($this->includeFilePath);
        }

        /**
         * @param ClassAliasArray $class
         */
        private function classTemplate(array $class): string
        {
            $abstract = $class['isabstract'] ? 'abstract ' : '';
            $classname = $class['classname'];
            if (isset($class['namespace'])) {
                $namespace = "namespace {$class['namespace']};";
                $extends = '\\' . $class['extends'];
                $implements = empty($class['implements']) ? ''
                : ' implements \\' . implode(', \\', $class['implements']);
            } else {
                $namespace = '';
                $extends = $class['extends'];
                $implements = !empty($class['implements']) ? ''
                : ' implements ' . implode(', ', $class['implements']);
            }
            return <<<EOD
                <?php
                $namespace
                $abstract class $classname extends $extends $implements {}
                EOD;
        }

        /**
         * @param InterfaceAliasArray $interface
         */
        private function interfaceTemplate(array $interface): string
        {
            $interfacename = $interface['interfacename'];
            $namespace = isset($interface['namespace'])
            ? "namespace {$interface['namespace']};" : '';
            $extends = isset($interface['namespace'])
            ? '\\' . implode('\\ ,', $interface['extends'])
            : implode(', ', $interface['extends']);
            return <<<EOD
                <?php
                $namespace
                interface $interfacename extends $extends {}
                EOD;
        }

        /**
         * @param TraitAliasArray $trait
         */
        private function traitTemplate(array $trait): string
        {
            $traitname = $trait['traitname'];
            $namespace = isset($trait['namespace'])
            ? "namespace {$trait['namespace']};" : '';
            $uses = isset($trait['namespace'])
            ? '\\' . implode(';' . PHP_EOL . '    use \\', $trait['use'])
            : implode(';' . PHP_EOL . '    use ', $trait['use']);
            return <<<EOD
                <?php
                $namespace
                trait $traitname { 
                    use $uses; 
                }
                EOD;
        }
    }

    spl_autoload_register([ new AliasAutoloader(), 'autoload' ]);
}
