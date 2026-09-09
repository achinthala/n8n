<?php
 
namespace Dcw\Custom\Newsletter\Model;
 
use Magento\Newsletter\Model\Subscriber as SubscriberModel;
 
class Subscriber
{
    /**
     * @param SubscriberModel $subject
     * @param callable $proceed
     */
    public function aroundSendConfirmationRequestEmail(SubscriberModel $subject, callable $proceed) {}
 
    /**
     * @param SubscriberModel $subject
     * @param callable $proceed
     */
    public function aroundSendConfirmationSuccessEmail(SubscriberModel $subject, callable $proceed) {}
 
    /**
     * @param SubscriberModel $subject
     * @param callable $proceed
     */
    public function aroundSendUnsubscriptionEmail(SubscriberModel $subject, callable $proceed) {}
}