<?php

declare(strict_types=1);

namespace Dcw\TaxCertificate\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Certificate extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('tax_certificates_list', 'id');
    }

    public function fetchByCustomerAndCertificateId($customerId, $certificateId)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('customer_id = ?', $customerId)
            ->where('certificate_id = ?', $certificateId);

        return $connection->fetchRow($select);
    }

    public function updateFlag($id, $flag)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            ['email_flag' => $flag],
            ['id = ?' => $id]
        );
    }

    public function insertRecord($certificate)
    {
        $connection = $this->getConnection();
        $connection->insert($this->getMainTable(), [
            'customer_id' => $certificate['customer_id'],
            'certificate_id' => $certificate['certificate_id'],
            'email_flag' => $certificate['email_flag'],
            'status' => $certificate['status'],
        ]);
    }

    public function updateRecord($certificate)
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            [
                'email_flag' => $certificate['email_flag'],
                'status' => $certificate['status']
            ],
            [
                'id = ?' => $certificate['id']
            ]
        );
    }
}
