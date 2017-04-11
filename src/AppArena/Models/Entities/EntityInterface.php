<?php

namespace AppArena\Models\Entities;

/**
 * Interface for project, template and app
 * Interface EntityInterface
 * @package AppArena\Models
 */
interface EntityInterface {

	/**
	 * Returns a list of channels the current entity is been published on (applies only to apps at the moment)
	 * @return array List of channels the current entity has been published on
	 */
	public function getChannels( );

	/**
	 * Returns the value of one config value
	 *
	 * @param String       $configKey Config identifier to get the data for
	 * @param String|array $attr      Attribute or Attributes which should be returned
	 *
	 * @return String|array Requested config value as String or an
	 */
	public function getConfig( $configKey, $attr = 'value' );

	/**
	 * Returns an array of all available config values
	 *
	 * @return array|bool Array of all config values
	 */
	public function getConfigs();

	/**
	 * Returns the ID of the entity
	 * @return mixed
	 */
	public function getId();

	/**
	 * Returns the value of a single Info field
	 *
	 * @param String $infoKey Info key to request the value for
	 *
	 * @return String|array Returns the value of the requested info key as string or array
	 */
	public function getInfo( $infoKey );

	/**
	 * Returns an array of all info fields
	 * @return array Array of all info fields
	 */
	public function getInfos();

	/**
	 * Returns all available and activated languages
	 * @return array Array of all languages incl. their status
	 */
	public function getLanguages();

	/**
	 * Returns the translation of the current language for the submitted ID
	 *
	 * @param String       $translationKey Translation key to get the translation for
	 * @param String|array $args           Array of values to replace in the translation (@see
	 *                                     http://php.net/manual/de/function.vsprintf.php)
	 *
	 * @return String Translated value
	 */
	public function getTranslation( $translationKey, array $args = [] );

	/**
	 * Returns the entity type (version, template or app)
	 * @return mixed
	 */
	public function getEntityType();

	/**
	 * Returns a list of all available translations
	 * @return array Array of all translations for the current language
	 */
	public function getTranslations();


}