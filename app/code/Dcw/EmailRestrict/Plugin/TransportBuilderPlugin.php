<?php
namespace Dcw\EmailRestrict\Plugin;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Mail\TransportInterface as CoreTransportInterface;

/**
 * Class TransportBuilderPlugin
 * @package Dcw\EmailRestrict\Plugin
 */
class TransportBuilderPlugin
{
    const XML_PATH_ENABLED = 'emailrestrict/general/enabled';
    const XML_PATH_ALLOWED = 'emailrestrict/general/allowed_domains';
    const XML_PATH_ACTION = 'emailrestrict/general/action';

    // add this const to your plugin class (or use the actual path used by your SMTP extension)
    const XML_PATH_SMTP_DISABLE = 'system/smtp/disable'; // adjust if your SMTP module uses a different path
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * TransportBuilderPlugin constructor.
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * After-plugin for getTransport
     *
     * @param \Magento\Framework\Mail\Template\TransportBuilder $subject
     * @param TransportInterface $transport
     * @return TransportInterface
     * @throws LocalizedException
     */
    public function afterGetTransport(\Magento\Framework\Mail\Template\TransportBuilder $subject, TransportInterface $transport)
    {
	    // Respect global SMTP disable (if set by other modules)
        $smtpDisabled = $this->scopeConfig->isSetFlag(self::XML_PATH_SMTP_DISABLE, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
        if ($smtpDisabled) {
            // Do nothing and return transport as-is so other logic that blocks sending can run
            return $transport;
        }

        // If disabled globally -> do nothing
        $isEnabled = $this->scopeConfig->isSetFlag(self::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE);
        if (!$isEnabled) {
            return $transport;
        }

        // Load config
        $allowedRaw = (string)$this->scopeConfig->getValue(self::XML_PATH_ALLOWED, ScopeInterface::SCOPE_STORE);
        $allowed = $this->parseAllowedDomains($allowedRaw);

        try {
            // Try to extract message and recipients
            if (method_exists($transport, 'getMessage')) {
                $message = $transport->getMessage();
                $recipients = $this->extractRecipients($message);
            } else {
                // Unknown transport structure - be safe and block
                $recipients = [];
            }
        } catch (\Exception $e) {
            // Avoid letting email process break the system unexpectedly; rethrow if needed
            throw new LocalizedException(__('Email Guard: unable to inspect recipients. %1', $e->getMessage()));
        }

        // If no recipients return as-is
        if (empty($recipients)) {
            return $transport;
        }

        // Determine allowed recipients and disallowed ones
        $allowedRecipients = [];
        $disallowedRecipients = [];
        foreach ($recipients as $address => $name) {
            $domain = $this->getDomainFromEmail($address);
            if ($this->isDomainAllowed($domain, $allowed)) {
                $allowedRecipients[$address] = $name;
            } else {
                $disallowedRecipients[$address] = $name;
            }
        }

        // If nothing disallowed -> return
        if (empty($disallowedRecipients)) {
            return $transport;
        }

        // Remove disallowed recipients leaving only allowed ones
        if (!empty($allowedRecipients)) {
            $this->setRecipientsOnMessage($transport->getMessage(), $allowedRecipients);
            return $transport;
        } else {
            // No allowed left - block or fallback
            throw new LocalizedException(__('Email Guard: All recipients are disallowed.'));
        }

        // Default: block
        throw new LocalizedException(__('Email Guard: Email contains recipients outside allowed domains and sending is blocked.'));
    }

    /**
     * Parse allowed domains from textarea/csv
     *
     * @param string $raw
     * @return array
     */
    private function parseAllowedDomains($raw)
    {
        $arr = preg_split("/[\r\n,]+/", trim($raw));
        $clean = [];
        foreach ($arr as $d) {
            $d = trim(strtolower($d));
            if ($d !== '') {
                $clean[] = $d;
            }
        }
        return array_values($clean);
    }

    /**
     * Extract recipients from message; supports Laminas/Zend or simple array
     *
     * @param $message
     * @return array [email => name|null]
     */
    private function extractRecipients($message)
    {
        $result = [];

        if (!method_exists($message, 'getTo')) {
            return $result;
        }

        $to = $message->getTo();

        // 1) String: "a@b.com, c@d.com"
        if (is_string($to)) {
            $items = array_filter(array_map('trim', explode(',', $to)));
            foreach ($items as $addr) {
                if ($addr !== '') {
                    $result[$addr] = null;
                }
            }
            return $result;
        }

        // 2) Plain array: [ 'a@b.com', 'b@c.com' ] OR [ 'a@b.com' => 'Name' ]
        if (is_array($to)) {
            foreach ($to as $k => $v) {
                if (is_int($k)) {
                    // indexed array: $v may be a string email, an Address object, or an array
                    if (is_string($v) && filter_var($v, FILTER_VALIDATE_EMAIL)) {
                        $result[$v] = null;
                    } elseif ($v instanceof \Magento\Framework\Mail\Address) {
                        $result[$v->getEmail()] = $v->getName();
                    } elseif (is_array($v)) {
                        // common shapes: ['email'=>'a@b.com','name'=>'X'] or [0=>'a@b.com',1=>'Name']
                        if (!empty($v['email'])) {
                            $result[$v['email']] = isset($v['name']) ? $v['name'] : null;
                        } elseif (isset($v[0]) && filter_var($v[0], FILTER_VALIDATE_EMAIL)) {
                            $result[$v[0]] = isset($v[1]) ? $v[1] : null;
                        }
                    } elseif (is_object($v) && method_exists($v, 'getEmail')) {
                        $result[$v->getEmail()] = method_exists($v, 'getName') ? $v->getName() : null;
                    } else {
                        // fallback: log unknown type (helps debugging)
                    }
                } else {
                    // associative: key is email (string)
                    if (is_string($k) && filter_var($k, FILTER_VALIDATE_EMAIL)) {
                        $result[$k] = is_string($v) ? $v : null;
                    } else {
                        // fallback: log unexpected key
                    }
                }
            }
            return $result;
        }

        // 4) Laminas/Zend AddressList
        if ($to instanceof \Laminas\Mail\AddressList || $to instanceof \Zend\Mail\AddressList) {
            foreach ($to as $addr) {
                if (method_exists($addr, 'getEmail')) {
                    $result[$addr->getEmail()] = method_exists($addr, 'getName') ? $addr->getName() : null;
                }
            }
            return $result;
        }

        // 5) Traversable of Address objects or strings
        if ($to instanceof \Traversable) {
            foreach ($to as $item) {
                if ($item instanceof \Magento\Framework\Mail\Address) {
                    $result[$item->getEmail()] = $item->getName();
                } elseif (is_string($item)) {
                    $result[$item] = null;
                } elseif (is_array($item)) {
                    // fallback: [email => name] style
                    foreach ($item as $ek => $ev) {
                        if (is_int($ek)) {
                            $result[$ev] = null;
                        } else {
                            $result[$ek] = $ev;
                        }
                    }
                }
            }
            return $result;
        }
        // fallback - log and return empty
        return $result;
    }

    /**
     * Set recipients on message (replace To header)
     *
     * @param $message
     * @param array $recipients [email => name|null]
     */
    private function setRecipientsOnMessage($message, array $recipients)
    {
        // Clear existing To header then add allowed recipients
        if (method_exists($message, 'clearRecipients')) {
            try {
                $message->clearRecipients();
            } catch (\Exception $e) {
                // ignore if not available
            }
        }

        // Many message implementations provide setTo or addTo
        if (method_exists($message, 'setTo')) {
            $message->setTo(array_keys($recipients));
            return;
        }

        if (method_exists($message, 'addTo')) {
            foreach ($recipients as $email => $name) {
                if ($name) {
                    $message->addTo($email, $name);
                } else {
                    $message->addTo($email);
                }
            }
            return;
        }

        // If Laminas AddressList
        if (method_exists($message, 'getTo') && ($message->getTo() instanceof \Laminas\Mail\AddressList || $message->getTo() instanceof \Zend\Mail\AddressList)) {
            $list = $message->getTo();
            // clear and add
            foreach ($list as $addr) {
                // no direct clear, create new list - fallback: can't reliably modify
            }
            // brute-force: set header with string
            $toString = implode(', ', array_keys($recipients));
            if (method_exists($message, 'setTo')) {
                $message->setTo($toString);
            }
        }
    }

    /**
     * Extract domain from email
     *
     * @param string $email
     * @return string
     */
    private function getDomainFromEmail($email)
    {
        $email = strtolower(trim($email));
        if (preg_match('/@(.+)$/', $email, $m)) {
            return $m[1];
        }
        return '';
    }

    /**
     * Check domain against allowed list; supports wildcard *.example.com
     *
     * @param string $domain
     * @param array $allowed
     * @return bool
     */
    private function isDomainAllowed($domain, array $allowed)
    {
        $domain = strtolower($domain);
        foreach ($allowed as $a) {
            if ($a === $domain) {
                return true;
            }
            if (strpos($a, '*.') === 0) {
                $base = substr($a, 2);
                if (substr($domain, -strlen($base)) === $base) {
                    return true;
                }
            }
            // allow wildcard at left e.g. *.sub.example.com
            if (strpos($a, '*') !== false) {
                // convert to regex
                $regex = '/^' . str_replace('\*', '.*', preg_quote($a, '/')) . '$/i';
                if (preg_match($regex, $domain)) {
                    return true;
                }
            }
        }
        return false;
    }
}

