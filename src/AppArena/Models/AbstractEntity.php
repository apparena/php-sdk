<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 07.04.2017
 * Time: 15:30
 */

namespace AppArena\Models;


abstract class AbstractEntity implements EntityInterface {


	/** @var  Api */
	protected $api;
	protected $configs;
	protected $id;
	protected $infos;
	protected $lang = 'de_DE';
	protected $languages;
	protected $name;
	protected $translations;
	protected $type;

	/**
	 * Initialize entity related information
	 *
	 * @param int $id ID of the entity
	 * @param Api $api
	 */
	public function __construct( $id, Api $api ) {

		// Initialize the API object
		$this->api = $api;

	}

	/**
	 * @return Api
	 */
	protected function getApi() {
		return $this->api;
	}

	/**
	 * @return String
	 */
	public function getEntityType() {
		return $this->type;
	}

	/**
	 * @inheritdoc
	 */
	public function getInfo( $key ) {
		$infos = $this->getInfos();

		if ( isset( $infos[ $key ] ) ) {
			return $infos[ $key ];
		}

		return false;
	}

	/**
	 * @inheritdoc
	 */
	public function getInfos() {
		// Return array from Memory if already available
		if ( $this->infos ) {
			return $this->infos;
		}

		// Update the language for the current request
		$this->api->setLang( null );

		// App infos is a merged array of basic app information and additional app meta data
		$info = $this->api->get( $this->getEntityType() . 's/' . $this->id );
		$meta = $this->api->get( $this->getEntityType() . 's/' . $this->id . '/infos' );

		if ( isset( $info['_embedded']['data'] ) && is_array( $info['_embedded']['data'] ) ) {
			$this->infos = $info['_embedded']['data'];
		} else {
			return false;
		}

		if ( isset( $meta['_embedded']['data'] ) && is_array( $meta['_embedded']['data'] ) ) {
			$values = array_map( function ( $item ) {
				return $item['value'];
			}, $meta['_embedded']['data'] );

			$this->infos = array_merge( $values, $this->infos );
		}
		ksort( $this->infos );

		if ( ! $this->infos ) {
			return false;
		}

		return $this->infos;
	}

	/**
	 * @inheritdoc
	 */
	public function getConfig( $config_id, $attr = 'value' ) {
		// Validate Input
		if ( ! is_string( $config_id ) || ! $config_id ) {
			return null;
		}

		// Get all config values
		$config = $this->getConfigs();

		// Return the value as string
		if ( is_string( $attr ) && isset( $config[ $config_id ][ $attr ] ) ) {
			return $config[ $config_id ][ $attr ];
		}

		// Return certain attributes of a config value
		if ( is_array( $attr ) && isset( $config[ $config_id ] ) ) {
			$result = [];
			// Initialize all required attributes with null
			foreach ( $attr as $attribute ) {
				$result[ $attribute ] = null;
			}

			// Add attributes from config value
			foreach ( $config[ $config_id ] as $attribute => $value ) {
				if ( in_array( $attribute, $attr ) ) {
					$result[ $attribute ] = $value;
				}
			}

			return $result;
		}

		return null;
	}

	/**
	 * @inheritdoc
	 */
	public function getConfigs() {
		// Return array from Memory if already available
		if ( $this->configs ) {
			return $this->configs;
		}

		// Update the language for the current request
		$this->api->setLang( $this->getLang() );
		$response = $this->api->get( $this->getEntityType() . "s/$this->id/configs" );

		if ( $response == false ) {
			return false;
		}
		$config        = $response['_embedded']['data'];
		$this->configs = $config;

		return $this->configs;
	}

	/**
	 * @inheritdoc
	 */
	public function getLanguages() {
		// Return array from Memory if already available
		if ( $this->languages ) {
			return $this->languages;
		}

		// Update the language for the current request
		$this->api->setLang( $this->getLang() );
		$response = $this->api->get( $this->getEntityType() . "s/$this->id/languages" );

		if ( $response == false ) {
			return false;
		}

		$this->languages = $response;

		return $this->languages;
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslation( $translationKey, array $args = [] ) {
		// Validate Input
		if ( ! is_string( $translationKey ) || ( ! is_array( $args ) && ! is_string( $args ) ) ) {
			return '';
		}

		if ( is_string( $args ) ) {
			$args = [ $args ];
		}

		$translate = $this->getTranslations();

		// No replacements necessary, so just return the translation like it is
		if ( count( $args ) == 0 && isset( $translate[ $translationKey ] ) ) {
			if ( is_array( $translate[ $translationKey ] ) && isset( $translate[ $translationKey ]['translation'] ) ) {
				return $translate[ $translationKey ]['translation'];
			}

			return $translate[ $translationKey ];
		}

		// Replace arguments in string
		if ( isset( $translate[ $translationKey ] ) ) {
			if ( is_array( $translate[ $translationKey ] ) && isset( $translate[ $translationKey ]['translation'] ) ) {
				$translate[ $translationKey ]['translation'] = vsprintf( $translate[ $translationKey ]['translation'], $args );

				return $translate[ $translationKey ]['translation'];
			}

			return vsprintf( $translate[ $translationKey ], $args );
		}

		return $translationKey;
	}

	/**
	 * @inheritdoc
	 */
	public function getTranslations() {
		// Return array from Memory if already available
		if ( $this->translations ) {
			return $this->translations;
		}

		$lang = $this->getLang();
		$this->api->setLang( $lang );
		$response = $this->api->get( $this->getEntityType() . "s/$this->id/translations" );
		if ( $response == false ) {
			return false;
		}

		$this->translations = $response['_embedded']['data'];

		return $this->translations;
	}

	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}


	/**
	 * @return string
	 */
	public function getLang() {
		return $this->lang;
	}

	/**
	 * @param string $lang
	 */
	public function setLang( $lang ) {
		$this->lang = $lang;
	}

	/**
	 * @return mixed
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName( $name ) {
		$this->name = $name;
	}


}