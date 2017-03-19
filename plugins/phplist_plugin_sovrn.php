<?php

/**
 * phpList statistics for Sovrn
 *
 * Once installed, enter the partner ID in the settings. That's all.
 *
 */

class phplist_plugin_sovrn extends phplistPlugin {
	public $name = "Sovrn tracking";
	public $coderoot = '';
	public $version = "0.1";
	public $authors = 'Michiel Dethmers';
	public $enabled = 1;
	public $description = 'Add tracking codes to emails and subscribe pages';
    public $settings = array(
        'Sovrn_PartnerID' => array(
        'value' => '',
        'description' => 'Sovrn Partner ID',
        'type' => 'text',
        'allowempty' => 0,
        'category' => 'statistics',
    ));
    private $emails = array(); // remember emails we've looked up
    
	public function dependencyCheck() {
		global $plugins;
		return array(
			'phpList version 3.2.4 or later' => version_compare(VERSION, '3.2.3') > 0,
		);
	}

	public function sovrnPixelURL($email) {
        $partnerID = sprintf('%d',getConfig('Sovrn_PartnerID'));
        if (empty($partnerID)) return '';

        //http://ap.lijit.com/merge?pid=5175&3pid=<lc_md5>,<uc_md5>,<lc_sha1>,<uc_sha1>,<lc_sha256>,<uc_sha256>,<lc_domain>
        $url = 'http://ap.lijit.com/merge?pid='.$partnerID;

//        partner_id - Partner ID (provided by sovrn)
//        lc_md5 - Lower-case MD5 email address hash
//        uc_md5 - Upper-case MD5 email address hash
//        lc_sha1 - Lower-case SHA1 email address hash
//        uc_sha1 - Upper-case SHA1 email address hash
//        lc_sha256 - Lower-case SHA256 email address hash
//        uc_sha256 - Upper-case SHA256 email address hash
//        lc_domain - Lower-case SHA1 email domain hash

        list($subscriber,$domain) = explode('@',$email);

        $url .= '&3pid=';
        $url .= md5(strtolower($email)).',';
        $url .= md5(strtoupper($email)).',';
        $url .= hash('sha1',strtolower($email)).',';
        $url .= hash('sha1',strtoupper($email)).',';
        $url .= hash('sha256',strtolower($email)).',';
        $url .= hash('sha256',strtoupper($email)).',';
        $url .= hash('sha1',strtolower($domain));

        return $url;
    }

    private function sovrnPixelByID($subscriberID) {
        $subscriberEmail = $this->subscriberEmail($subscriberID);
        return $this->sovrnPixelByEmail($subscriberEmail);
    }

    private function sovrnPixelByEmail($subscriberEmail) {
        if (empty($subscriberEmail)) {
            return '';
        } else {
            $url = $this->sovrnPixelURL($subscriberEmail);
            if (!empty($url)) {
                return '<img src="' . $this->sovrnPixelURL($subscriberEmail) . '" />';
            }
        }
    }

    private function subscriberEmail($subscriberID) {
        if (isset($this->emails[$subscriberID])) {
            return $this->emails[$subscriberID];
        }
        $subscriberEmail = Sql_Fetch_Row_Query(sprintf('select email from %s where id = %d', $GLOBALS['tables']['subscriber'],$subscriberID));
        $this->emails[$subscriberID] = $subscriberEmail[0];
        return $this->emails[$subscriberID];
    }

    public function parseThankyou($pageid = 0, $subscriberid = 0, $text = '')
    {
        return $text.$this->sovrnPixelByID($subscriberid);
    }

    public function displaySubscriptionChoice($pageData, $subscriberID = 0)
    {
        return $this->sovrnPixelByID($subscriberID);
    }

    public function parseOutgoingHTMLMessage($messageid, $content, $destination, $subscriberdata = null)
    {
        return $content.$this->sovrnPixelByEmail($destination);
    }

}
