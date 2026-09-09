<?php

namespace Dcw\DesignerTool\Helper;

class Mobile
{
    /**
     * @var array
     */
    protected $_haTerms = [
        'midp',
        'wml',
        'vnd.rim',
        'vnd.wap',
        'j2me',
    ];

    /**
     * User Agent Signatures
     *
     * @var array
     */
    protected $_uaSignatures = [
        'iphone',
        'ipod',
        'ipad',
        'android',
        'blackberry',
        'opera mini',
        'opera mobi',
        'palm',
        'palmos',
        'elaine',
        'windows ce',
        'icab',
        '_mms',
        'ahong',
        'archos',
        'armv',
        'astel',
        'avantgo',
        'benq',
        'blazer',
        'brew',
        'com2',
        'compal',
        'danger',
        'pocket',
        'docomo',
        'epoc',
        'ericsson',
        'eudoraweb',
        'hiptop',
        'htc-',
        'htc_',
        'iemobile',
        'iris',
        'j-phone',
        'kddi',
        'kindle',
        'lg ',
        'lg-',
        'lg/',
        'lg;lx',
        'lge vx',
        'lge',
        'lge-',
        'lge-cx',
        'lge-lx',
        'lge-mx',
        'linux armv',
        'maemo',
        'midp',
        'mini 9.5',
        'minimo',
        'mob-x',
        'mobi',
        'mobile',
        'mobilephone',
        'mot 24',
        'mot-',
        'motorola',
        'n410',
        'netfront',
        'nintendo wii',
        'nintendo',
        'nitro',
        'nokia',
        'novarra-vision',
        'nuvifone',
        'openweb',
        'opwv',
        'palmsource',
        'pdxgw',
        'phone',
        'playstation',
        'polaris',
        'portalmmm',
        'qt embedded',
        'reqwirelessweb',
        'sagem',
        'sam-r',
        'samsu',
        'samsung',
        'sec-',
        'sec-sgh',
        'semc-browser',
        'series60',
        'series70',
        'series80',
        'series90',
        'sharp',
        'sie-m',
        'sie-s',
        'smartphone',
        'sony cmd',
        'sonyericsson',
        'sprint',
        'spv',
        'symbian os',
        'symbian',
        'symbianos',
        'telco',
        'teleca',
        'treo',
        'up.browser',
        'up.link',
        'vodafone',
        'vodaphone',
        'webos',
        'wml',
        'windows phone os 7',
        'wireless',
        'wm5 pie',
        'wms pie',
        'xiino',
        'wap',
        'up/',
        'psion',
        'j2me',
        'klondike',
        'kbrowser'
    ];

    /**
     * first 4 letters of mobile User Agent chains
     *
     * @var array
     */
    protected $_uaBegin = [
        'w3c ',
        'acs-',
        'alav',
        'alca',
        'amoi',
        'audi',
        'avan',
        'benq',
        'bird',
        'blac',
        'blaz',
        'brew',
        'cell',
        'cldc',
        'cmd-',
        'dang',
        'doco',
        'eric',
        'hipt',
        'inno',
        'ipaq',
        'java',
        'jigs',
        'kddi',
        'keji',
        'leno',
        'lg-c',
        'lg-d',
        'lg-g',
        'lge-',
        'maui',
        'maxo',
        'midp',
        'mits',
        'mmef',
        'mobi',
        'mot-',
        'moto',
        'mwbp',
        'nec-',
        'newt',
        'noki',
        'palm',
        'pana',
        'pant',
        'phil',
        'play',
        'port',
        'prox',
        'qwap',
        'sage',
        'sams',
        'sany',
        'sch-',
        'sec-',
        'send',
        'seri',
        'sgh-',
        'shar',
        'sie-',
        'siem',
        'smal',
        'smar',
        'sony',
        'sph-',
        'symb',
        't-mo',
        'teli',
        'tim-',
        'tosh',
        'tsm-',
        'upg1',
        'upsi',
        'vk-v',
        'voda',
        'wap-',
        'wapa',
        'wapi',
        'wapp',
        'wapr',
        'webc',
        'winw',
        'winw',
        'xda',
        'xda-',
    ];

    /**
     * Comparison of the UserAgent chain and User Agent signatures
     *
     * @param  string $userAgent User Agent chain
     * @param  array $server $_SERVER like param
     * @return bool
     */
    public function match($userAgent, $server)
    {
        //  To have a quick identification, try light-weight tests first
        if (isset($server['all_http'])) {
            if (strpos(strtolower(str_replace(' ', '', $server['all_http'])), 'operam') !== false) {
                // Opera Mini or Opera Mobi
                return true;
            }
        }

        if (isset($server['http_x_wap_profile']) || isset($server['http_profile'])) {
            return true;
        }

        if (isset($server['http_accept'])) {
            if ($this->matchAgentAgainstSignatures($server['http_accept'], $this->_haTerms)) {
                return true;
            }
        }

        if ($this->userAgentStart($userAgent)) {
            return true;
        }

        if ($this->matchAgentAgainstSignatures($userAgent, $this->_uaSignatures)) {
            return true;
        }

        return false;
    }

    /**
     * Match a user agent string against a list of signatures
     *
     * @param  string $userAgent
     * @param  array $signatures
     * @return bool
     */
    protected function matchAgentAgainstSignatures($userAgent, $signatures)
    {
        $userAgent = strtolower($userAgent);
        foreach ($signatures as $signature) {
            if (!empty($signature)) {
                if (strpos($userAgent, $signature) !== false) {
                    // Browser signature was found in user agent string
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Retrieve beginning clause of user agent
     *
     * @param  string $userAgent
     * @return string
     */
    public function userAgentStart($userAgent)
    {

        $mobile_ua = strtolower(substr($userAgent, 0, 4));

        return (in_array($mobile_ua, $this->_uaBegin));
    }
}
