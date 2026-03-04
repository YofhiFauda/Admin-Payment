<?php

namespace Tests\Unit;

use App\Services\IdGeneratorService;
use PHPUnit\Framework\TestCase;

class IdGeneratorServiceTest extends TestCase
{
    public function test_it_builds_correct_upload_id_format(): void
    {
        $date = '20260304';
        $sequence = 5;

        $uploadId = IdGeneratorService::buildUploadId($sequence, $date);

        // Expectation: UP-YYYYMMDD-00005
        $this->assertEquals('UP-20260304-00005', $uploadId);
    }

    public function test_it_builds_correct_invoice_number_format(): void
    {
        $date = '20260304';
        $sequence = 12;

        $invoiceNumber = IdGeneratorService::buildInvoiceNumber($sequence, $date);

        // Expectation: INV-YYYYMMDD-00012
        $this->assertEquals('INV-20260304-00012', $invoiceNumber);
    }
}
