<?php
class PoP_ServerSide_ResourceLoader {

	// The values here will be populated from resourceloader-config.js,
	// on a domain by domain basis
	public $config, $blockId;

	// Keep a list of all loading resources
	// private $loading;
	// private $errorLoading;
	// private $loadingURLs;

	// Keep a list of all loaded resources. All resources are called always the same among different domains,
	// so one list here listing all of them works
	public $loaded;
	public $loadedInBody;
	// Loaded bundles and bundleGroups depend on their domains, since their names change among domains
	// private $loadedByDomain;

	function __construct() {

		PoP_ServerSide_Libraries_Factory::set_resourceloader_instance($this);
		
		// Initialize internal variables
		$this->config = array();
		// $this->loading = array(
		// 	'resources' => array(),
		// );
		// $this->errorLoading = array(
		// 	'resources' => array(),
		// );
		// $this->loadingURLs = array();
		$this->loaded = array();
		$this->loadedInBody = array();
		// $this->loadedByDomain = array();
	}

	//-------------------------------------------------
	// PUBLIC but NOT EXPOSED functions
	//-------------------------------------------------

	protected function includeResource($resource) {

		// Include the script/style link
		if (PoP_Frontend_ServerUtils::include_resources_in_body()) {

			global $pop_resourceloaderprocessor_manager;
			$config = $this->getConfigByDomain($this->domain);
			$blockId = $this->blockId;
			// $resource_id = PoP_ResourceLoaderProcessorUtils::get_noconflict_resource_name($resource);
			$resource_id = $pop_resourceloaderprocessor_manager->get_handle($resource);
			$include_type = PoP_Frontend_ServerUtils::get_templateresources_include_type();

			// For both 'body' and 'body-inline', include the style/script file when the pageSectionPage is destroyed
			$script = '';
			$source = $config['sources'][$resource];
			$fn = '<script type="text/javascript">jQuery(document).ready( function($) { popResourceLoader.onRemoveLoadResource("%s", "%s", "%s"); });</script>';
			if (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_CSS])) {

				$script = sprintf(
					$fn,
					$blockId,
					POP_RESOURCELOADER_RESOURCETYPE_CSS,
					$source
				);
			}
			elseif (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_JS])) {

				$script = sprintf(
					$fn,
					$blockId,
					POP_RESOURCELOADER_RESOURCETYPE_JS,
					$source
				);
			}

			if ($include_type == 'body') {

				// If destroying the pageSectionPage, the corresponding 'in-body' styles will also be deleted, and other pages using those styles will be affected.
				// Then, simply load again those removed resources (scripts and styles)
				if (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_CSS])) {

					$tag = sprintf(
						'<link id="%s" rel="stylesheet" href="%s">',
						$resource_id,
						$source
					);
					return $script.$tag;
				}
				// else if ($type == POP_RESOURCELOADER_RESOURCETYPE_JS) {
				elseif (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_JS])) {

					$tag = sprintf(
						'<script id="%s" type="text/javascript" src="%s"></script>',
						$resource_id,
						$source
					);
					return $script.$tag;
				}
			}
			// Include the content of the file
			elseif ($include_type == 'body-inline') {

				global $pop_resourceloaderprocessor_manager;
				$file = $pop_resourceloaderprocessor_manager->get_file_path($resource);
	            $file_contents = file_get_contents($file);

				if (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_CSS])) {

					$inline = sprintf(
						'<style id="%s" type="text/css">%s</style>',
						$resource_id,
						$file_contents
					);
					return $script.$inline;
				}
				// else if ($type == POP_RESOURCELOADER_RESOURCETYPE_JS) {
				elseif (in_array($resource, $config['types'][POP_RESOURCELOADER_RESOURCETYPE_JS])) {

					$inline = sprintf(
						'<script id="%s" type="text/javascript">%s</script>',
						$resource_id,
						$file_contents
					);
					return $script.$inline;
				}
			}		
		}

		return '';
	}

	function includeResources($domain, $blockId, $resources, $ignoreAlreadyIncluded) {

		if (!$resources) {

			return '';
		}

		// Only

		$body_resources = array();

		// Remove the resources that have been included already
		if ($ignoreAlreadyIncluded) {

			// Comment Leo 23/11/2017: if a component is lazy-loaded, and inside has a CSS file that is printed in the body,
			// then we must check if that resource has been added to the body. (It will be already marked as "loaded" by the website,
			// but it never was actually because of the lazy-loading)
			// if (PoP_Frontend_ServerUtils::include_resources_in_body()) {
				
			$body_resources = array_diff(
				$resources,
				$this->loadedInBody
			);
			// }
			$resources = array_diff(
				$resources,
				$this->loaded
			);
		}

		// Mark the resources as already included
		$this->loaded = array_merge(
			$this->loaded,
			$resources
		);
		// if (PoP_Frontend_ServerUtils::include_resources_in_body()) {

		$this->loadedInBody = array_merge(
			$this->loadedInBody,
			$body_resources
		);
		$resources = $body_resources;
		// }

		// Map the resources to their tags. First set the domain so it can be accessed in that function
		$this->domain = $domain;
		$this->blockId = $blockId;
		$tags = array_map(array($this, 'includeResource'), $resources);

		return implode('', $tags);
	}

	function getConfigByDomain($domain) {

		return $this->config[$domain];

		// // Check we have a config for this domain
		// $config = $this->config[$domain];
		// if (!$config && $domain != get_site_url()) {

		// 	// If we don't have a config, and the domain is not local, then try the local domain
		// 	// (This is needed for if the external resourceloader-config.js file has not been loaded yet. 
		// 	// This may happen often, as loading this file is asynchronous, so needing to check the URL path
		// 	// will happen before the script is loaded)
		// 	$config = $this->config[get_site_url()];
		// }
		
		// return $config ?? array();
	}

	// function getConfig($url) {

	// 	$domain = get_domain($url);
	// 	return $this->getConfigByDomain($domain);
	// }
}

/**---------------------------------------------------------------------------------------------------------------
 * Initialization
 * ---------------------------------------------------------------------------------------------------------------*/
new PoP_ServerSide_ResourceLoader();
