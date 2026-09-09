<?php

namespace Dcw\AvalaraAvatax\Block\Checkout;

use Avalara\Avatax\Block\Checkout\CertificatesLayoutProcessor as OriginalProcessor;

class CertificatesLayoutProcessorDcw extends  \Avalara\AvaTax\Block\Checkout\CertificatesLayoutProcessor
{
    
    public function process($jsLayout)
    {
        $config = [
            'documentManagementEnabled' => false,
			'certificatesAutoValidationDisabled' => false

        ];

        if ($this->config->isModuleEnabled() && $this->documentManagementConfig->isEnabled()) {
            $hasCerts = false;
            /** @var CustomerInterface $customer */
            $customer = $this->customerSession->getCustomer();

            if ($customer->getId() !== null) {
                try {
                    $hasCerts = \count($this->certificateHelper->getCertificates($customer->getId())) > 0;
                } catch (\Avalara\AvaTax\Exception\AvataxConnectionException $e) {
                    // We will just assume there are no certificates
                }
            }

            $newCertText = $hasCerts ? __(
                $this->documentManagementConfig->getCheckoutLinkTextNewCertCertsExist()
            ) : __($this->documentManagementConfig->getCheckoutLinkTextNewCertNoCertsExist());

            $config = [
                'certificatesLink' => $this->urlBuilder->getUrl('avatax/certificates'),
                'newCertText' => ($newCertText=='Upload your first certificate')?'Upload Tax Exempt Certificate':$newCertText,//$newCertText,
                'manageCertsText' => __($this->documentManagementConfig->getCheckoutLinkTextManageExistingCert()),
                'enabledCountries' => $this->documentManagementConfig->getEnabledCountries(),
                'documentManagementEnabled' => true,
				'certificatesAutoValidationDisabled' => $this->documentManagementConfig->isCertificatesAutoValidationDisabled()
            ];
        }

        // Set config for payments area
        $jsLayout["components"]["checkout"]["children"]["steps"]["children"]["billing-step"]["children"]["payment"]["children"]["payments-list"]["config"] = array_merge(
            $jsLayout["components"]["checkout"]["children"]["steps"]["children"]["billing-step"]["children"]["payment"]["children"]["payments-list"]["config"],
            $config
        );

        // Set config for tax summary area
        $jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]["summary"]["children"]["totals"]["children"]["tax"]["config"] = array_merge(
            $jsLayout["components"]["checkout"]["children"]["sidebar"]["children"]["summary"]["children"]["totals"]["children"]["tax"]["config"],
            $config
        );

        return $jsLayout;
    }
}
