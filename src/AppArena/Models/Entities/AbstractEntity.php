<?php
/**
 * Created by PhpStorm.
 * User: s.buckpesch
 * Date: 07.04.2017
 * Time: 15:30
 */

namespace AppArena\Models\Entities;


use AppArena\Models\Api;
use AppArena\Models\Entities\EntityInterface;

abstract class AbstractEntity implements EntityInterface {


	/** @var  Api */
	protected $api;
	protected $configs;
	protected $id;
	protected $infos;
	protected $lang;
	protected $languages;
	protected $name;
	protected $translations;
	protected $type;

	const VALID_LANGUAGES = [
		//"aa_DJ" => ["language" => "Afar", "country" => "Djibouti"],
		//"aa_ER" => ["language" => "Afar", "country" => "Eritrea"],
		//"aa_ET" => ["language" => "Afar", "country" => "Ethiopia"],
		//"af_ZA" => ["language" => "Afrikaans", "country" => "South Africa"],
		//"ak_GH" => ["language" => "Akan", "country" => "Ghana"],
		//"am_ET" => ["language" => "Amharic", "country" => "Ethiopia"],
		//"an_ES" => ["language" => "Aragonese", "country" => "Spain"],
		"ar_AE" => [ "language" => "Arabic", "country" => "United Arab Emirates" ],
		/*"ar_BH" => ["language" => "Arabic", "country" => "Bahrain"],
		"ar_DZ" => ["language" => "Arabic", "country" => "Algeria"],
		"ar_EG" => ["language" => "Arabic", "country" => "Egypt"],
		"ar_IN" => ["language" => "Arabic", "country" => "India"],
		"ar_IQ" => ["language" => "Arabic", "country" => "Iraq"],
		"ar_JO" => ["language" => "Arabic", "country" => "Jordan"],
		"ar_KW" => ["language" => "Arabic", "country" => "Kuwait"],
		"ar_LB" => ["language" => "Arabic", "country" => "Lebanon"],
		"ar_LY" => ["language" => "Arabic", "country" => "Libyan Arab Jamahiriya"],
		"ar_MA" => ["language" => "Arabic", "country" => "Morocco"],
		"ar_OM" => ["language" => "Arabic", "country" => "Oman"],
		"ar_QA" => ["language" => "Arabic", "country" => "Qatar"],
		"ar_SA" => ["language" => "Arabic", "country" => "Saudi Arabia"],
		"ar_SD" => ["language" => "Arabic", "country" => "Sudan"],
		"ar_SS" => ["language" => "Arabic", "country" => "South Soudan"],
		"ar_SY" => ["language" => "Arabic", "country" => "Syrian Arab Republic"],
		"ar_TN" => ["language" => "Arabic", "country" => "Tunisia"],
		"ar_YE" => ["language" => "Arabic", "country" => "Yemen"],
		"as_IN" => ["language" => "Assamese", "country" => "India"],
		"az_AZ" => ["language" => "Azerbaijani", "country" => "Azerbaijan"],
		"be_BY" => ["language" => "Belarusian", "country" => "Belarus"],*/
		"bg_BG" => [ "language" => "Bulgarian", "country" => "Bulgaria" ],
		/*"bn_BD" => ["language" => "Bengali", "country" => "Bangladesh"],
		"bn_IN" => ["language" => "Bengali", "country" => "India"],
		"bo_CN" => ["language" => "Tibetan", "country" => "China"],
		"bo_IN" => ["language" => "Tibetan", "country" => "India"],
		"br_FR" => ["language" => "Breton", "country" => "France"],
		"bs_BA" => ["language" => "Bosnian", "country" => "Bosnia And Herzegovina"],
		"ca_AD" => ["language" => "Catalan", "country" => "Andorra"],
		"ca_ES" => ["language" => "Catalan", "country" => "Spain"],
		"ca_FR" => ["language" => "Catalan", "country" => "France"],
		"ca_IT" => ["language" => "Catalan", "country" => "Italy"],
		"ce_RU" => ["language" => "Chechen", "country" => "Russian Federation"],*/
		"cs_CZ" => [ "language" => "Czech", "country" => "Czech Republic" ],
		/*"cv_RU" => ["language" => "Chuvash", "country" => "Russian Federation"],
		"cy_GB" => ["language" => "Welsh", "country" => "United Kingdom"],*/
		"da_DK" => [ "language" => "Danish", "country" => "Denmark" ],
		/*"de_AT" => ["language" => "German", "country" => "Austria"],
		"de_BE" => ["language" => "German", "country" => "Belgium"],
		"de_CH" => ["language" => "German", "country" => "Switzerland"],*/
		"de_DE" => [ "language" => "German", "country" => "Germany" ],
		/*"de_IT" => ["language" => "German", "country" => "Italy"],
		"de_LU" => ["language" => "German", "country" => "Luxembourg"],
		"en_AG" => ["language" => "English", "country" => "Antigua And Barbuda"],
		"en_AU" => ["language" => "English", "country" => "Australia"],
		"en_BW" => ["language" => "English", "country" => "Botswana"],
		"en_CA" => ["language" => "English", "country" => "Canada"],
		"en_DK" => ["language" => "English", "country" => "Denmark"],
		"en_GB" => ["language" => "English", "country" => "United Kingdom"],
		"en_IE" => ["language" => "English", "country" => "Ireland"],
		"en_IL" => ["language" => "English", "country" => "Israel"],
		"en_IN" => ["language" => "English", "country" => "India"],
		"en_NG" => ["language" => "English", "country" => "Nigeria"],
		"en_NZ" => ["language" => "English", "country" => "New Zealand"],
		"en_PH" => ["language" => "English", "country" => "Philippines"],
		"en_SG" => ["language" => "English", "country" => "Singapore"],*/
		"en_US" => [ "language" => "English", "country" => "United States" ],
		/*"en_ZA" => ["language" => "English", "country" => "South Africa"],
		"en_ZM" => ["language" => "English", "country" => "Zambia"],
		"en_ZW" => ["language" => "English", "country" => "Zimbabwe"],
		"es_AR" => ["language" => "Spanish", "country" => "Argentina"],
		"es_BO" => ["language" => "Spanish", "country" => "Bolivia, Plurinational State Of"],
		"es_CL" => ["language" => "Spanish", "country" => "Chile"],
		"es_CO" => ["language" => "Spanish", "country" => "Colombia"],
		"es_CR" => ["language" => "Spanish", "country" => "Costa Rica"],
		"es_CU" => ["language" => "Spanish", "country" => "Cuba"],
		"es_DO" => ["language" => "Spanish", "country" => "Dominican Republic"],
		"es_EC" => ["language" => "Spanish", "country" => "Ecuador"],*/
		"es_ES" => [ "language" => "Spanish", "country" => "Spain" ],
		/*"es_GT" => ["language" => "Spanish", "country" => "Guatemala"],
		"es_HN" => ["language" => "Spanish", "country" => "Honduras"],
		"es_MX" => ["language" => "Spanish", "country" => "Mexico"],
		"es_NI" => ["language" => "Spanish", "country" => "Nicaragua"],
		"es_PA" => ["language" => "Spanish", "country" => "Panama"],
		"es_PE" => ["language" => "Spanish", "country" => "Peru"],
		"es_PR" => ["language" => "Spanish", "country" => "Puerto Rico"],
		"es_PY" => ["language" => "Spanish", "country" => "Paraguay"],
		"es_SV" => ["language" => "Spanish", "country" => "El Salvador"],
		"es_US" => ["language" => "Spanish", "country" => "United States"],
		"es_UY" => ["language" => "Spanish", "country" => "Uruguay"],
		"es_VE" => ["language" => "Spanish", "country" => "Venezuela, Bolivarian Republic Of"],
		"et_EE" => ["language" => "Estonian", "country" => "Estonia"],
		"eu_ES" => ["language" => "Basque", "country" => "Spain"],
		"fa_IR" => ["language" => "Persian", "country" => "Iran, Islamic Republic Of"],*/
		"fi_FI" => [ "language" => "Finnish", "country" => "Finland" ],
		/*"fr_BE" => ["language" => "French", "country" => "Belgium"],
		"fr_CA" => ["language" => "French", "country" => "Canada"],
		"fr_CH" => ["language" => "French", "country" => "Switzerland"],*/
		"fr_FR" => [ "language" => "French", "country" => "France" ],
		/*"fr_LU" => ["language" => "French", "country" => "Luxembourg"],
		"he_IL" => ["language" => "Hebrew", "country" => "Israel"],
		"hi_IN" => ["language" => "Hindi", "country" => "India"],
		"hr_HR" => ["language" => "Croatian", "country" => "Croatia"],
		"ht_HT" => ["language" => "Haitian", "country" => "Haiti"],*/
		"hu_HU" => [ "language" => "Hungarian", "country" => "Hungary" ],
		/*"hy_AM" => ["language" => "Armenian", "country" => "Armenia"],
		"id_ID" => ["language" => "Indonesian", "country" => "Indonesia"],
		"is_IS" => ["language" => "Icelandic", "country" => "Iceland"],
		"it_CH" => ["language" => "Italian", "country" => "Switzerland"],*/
		"it_IT" => [ "language" => "Italian", "country" => "Italy" ],
		"ja_JP" => [ "language" => "Japanese", "country" => "Japan" ],
		/*"ka_GE" => ["language" => "Georgian", "country" => "Georgia"],
		"kk_KZ" => ["language" => "Kazakh", "country" => "Kazakhstan"],
		"ko_KR" => ["language" => "Korean", "country" => "Korea, Republic Of"],
		"ky_KG" => ["language" => "Kirghiz", "country" => "Kyrgyzstan"],
		"lt_LT" => ["language" => "Lithuanian", "country" => "Lithuania"],
		"lv_LV" => ["language" => "Latvian", "country" => "Latvia"],
		"mk_MK" => ["language" => "Macedonian", "country" => "Macedonia, The Former Yugoslav Republic Of"],
		"mn_MN" => ["language" => "Mongolian", "country" => "Mongolia"],
		"mt_MT" => ["language" => "Maltese", "country" => "Malta"],
		"my_MM" => ["language" => "Burmese", "country" => "Myanmar"],
		"ne_NP" => ["language" => "Nepali", "country" => "Nepal"],
		"nl_AW" => ["language" => "Dutch", "country" => "Aruba"],
		"nl_BE" => ["language" => "Dutch", "country" => "Belgium"],*/
		"nl_NL" => [ "language" => "Dutch", "country" => "Netherlands" ],
		"no_NO" => [ "language" => "Norwegian", "country" => "Norway" ],
		/*"om_ET" => ["language" => "Oromo", "country" => "Ethiopia"],
		"om_KE" => ["language" => "Oromo", "country" => "Kenya"],
		"pa_IN" => ["language" => "Panjabi", "country" => "India"],
		"pa_PK" => ["language" => "Panjabi", "country" => "Pakistan"],*/
		"pl_PL" => [ "language" => "Polish", "country" => "Poland" ],
		/*"ps_AF" => ["language" => "Pushto", "country" => "Afghanistan"],
		"pt_BR" => ["language" => "Portuguese", "country" => "Brazil"],*/
		"pt_PT" => [ "language" => "Portuguese", "country" => "Portugal" ],
		"ro_RO" => [ "language" => "Romanian", "country" => "Romania" ],
		"ru_RU" => [ "language" => "Russian", "country" => "Russian Federation" ],
		/*"ru_UA" => ["language" => "Russian", "country" => "Ukraine"],
		"rw_RW" => ["language" => "Kinyarwanda", "country" => "Rwanda"],
		"sc_IT" => ["language" => "Sardinian", "country" => "Italy"],
		"sd_IN" => ["language" => "Sindhi", "country" => "India"],
		"si_LK" => ["language" => "Sinhala", "country" => "Sri Lanka"],*/
		"sk_SK" => [ "language" => "Slovak", "country" => "Slovakia" ],
		"sl_SI" => [ "language" => "Slovenian", "country" => "Slovenia" ],
		/*"so_DJ" => ["language" => "Somali", "country" => "Djibouti"],
		"so_ET" => ["language" => "Somali", "country" => "Ethiopia"],
		"so_KE" => ["language" => "Somali", "country" => "Kenya"],
		"so_SO" => ["language" => "Somali", "country" => "Somalia"],
		"sq_AL" => ["language" => "Albanian", "country" => "Albania"],
		"sq_MK" => ["language" => "Albanian", "country" => "Macedonia, The Former Yugoslav Republic Of"],
		"sr_ME" => ["language" => "Serbian", "country" => "Montenegro"],
		"sr_RS" => ["language" => "Serbian", "country" => "Serbia"],
		"sv_FI" => ["language" => "Swedish", "country" => "Finland"],*/
		"sv_SE" => [ "language" => "Swedish", "country" => "Sweden" ],
		/*"ta_IN" => ["language" => "Tamil", "country" => "India"],
		"ta_LK" => ["language" => "Tamil", "country" => "Sri Lanka"],
		"tk_TM" => ["language" => "Turkmen", "country" => "Turkmenistan"],
		"tl_PH" => ["language" => "Tagalog", "country" => "Philippines"],
		"tn_ZA" => ["language" => "Tswana", "country" => "South Africa"],
		"tr_CY" => ["language" => "Turkish", "country" => "Cyprus"],*/
		"tr_TR" => [ "language" => "Turkish", "country" => "Turkey" ],
		"uk_UA" => [ "language" => "Ukrainian", "country" => "Ukraine" ],
		/*"vi_VN" => ["language" => "Vietnamese", "country" => "Viet Nam"],
		"yo_NG" => ["language" => "Yoruba", "country" => "Nigeria"],*/
		"zh_CN" => [ "language" => "Chinese", "country" => "China" ],
		/*"zh_HK" => ["language" => "Chinese", "country" => "Hong Kong"],
		"zh_SG" => ["language" => "Chinese", "country" => "Singapore"],
		"zh_TW" => ["language" => "Chinese", "country" => "Taiwan"],*/
	];

	/**
	 * Initialize entity related information
	 *
	 * @param int $id ID of the entity
	 */
	public function __construct( $id ) {
		$this->id = $id;
	}

	/**
	 * @return Api
	 */
	protected function getApi() {
		return $this->api;
	}

	/**
	 * @param Api $api
	 */
	public function setApi( $api ) {
		$this->api = $api;
	}

	/**
	 * @inheritdoc
	 */
	public function getChannels() {
		return [];
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
		if ( $this->getLang() ) {
			$this->api->setLang( $this->getLang() );
		}

		// App infos is a merged array of basic app information and additional app meta data
		$key  = $this->getEntityType() . 's/' . $this->id;
		$info = $this->api->get( $key );
		$meta = $this->api->get( $key . '/infos' );

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
		if ( $this->getLang() ) {
			$this->api->setLang( $this->getLang() );
		}
		$response = $this->api->get( $this->getEntityType() . "s/$this->id/configs" );

		if ( $response === false ) {
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

		// Update the language for the current request
		if ( $this->getLang() ) {
			$this->api->setLang( $this->getLang() );
		}

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
		if ( ! $this->lang ) {
			// Try to recover language from Request
			$lang = false;
			if ( isset( $_GET['lang'] ) ) {
				$lang = $_GET['lang'];
			} else {
				if ( isset( $_GET['locale'] ) ) {
					$lang = $_GET['locale'];
				} else {
					if ( isset( $_COOKIE[ 'aa_' . $this->id . '_lang' ] ) ) {
						$lang = $_COOKIE[ 'aa_' . $this->id . '_lang' ];
					}
				}
			}

			if ( $lang ) {
				$this->setLang( $lang );
			}
		}

		return $this->lang;
	}

	/**
	 * @param string $lang
	 */
	public function setLang( $lang ) {
		// Validate language code
		$languages = self::VALID_LANGUAGES;
		if ( ! isset( $languages[ $lang ] ) ) {
			throw new \InvalidArgumentException( $lang . ' is not a valid language code' );
		}

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