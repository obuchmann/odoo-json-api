<?php

declare(strict_types=1);

namespace Obuchmann\OdooJsonApi\Tests\Integration;

use Obuchmann\OdooJsonApi\Domain;

class OdooIntegrationTest extends TestCase
{
    private ?int $createdPartnerId = null;

    protected function tearDown(): void
    {
        if ($this->createdPartnerId !== null) {
            try {
                $this->odoo->unlink('res.partner', [$this->createdPartnerId]);
            } catch (\Throwable) {
                // Best effort cleanup
            }
        }

        parent::tearDown();
    }

    public function testSearchRead(): void
    {
        $partners = $this->odoo->searchRead(
            'res.partner',
            fields: ['name', 'email'],
            limit: 5,
        );

        $this->assertIsArray($partners);
        $this->assertLessThanOrEqual(5, count($partners));

        if (count($partners) > 0) {
            $this->assertArrayHasKey('id', $partners[0]);
            $this->assertArrayHasKey('name', $partners[0]);
        }
    }

    public function testSearch(): void
    {
        $ids = $this->odoo->search('res.partner', limit: 3);

        $this->assertIsArray($ids);
        $this->assertLessThanOrEqual(3, count($ids));
    }

    public function testCount(): void
    {
        $count = $this->odoo->count('res.partner');

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testCrudCycle(): void
    {
        // Create
        $id = $this->odoo->create('res.partner', [
            'name' => 'OdooJsonApi Test Partner',
            'email' => 'test@odoo-json-api.example.com',
        ]);
        $this->createdPartnerId = $id;

        $this->assertIsInt($id);
        $this->assertGreaterThan(0, $id);

        // Read
        $records = $this->odoo->read('res.partner', [$id], fields: ['name', 'email']);
        $this->assertCount(1, $records);
        $this->assertSame('OdooJsonApi Test Partner', $records[0]['name']);

        // Write
        $result = $this->odoo->write('res.partner', [$id], ['name' => 'Updated Test Partner']);
        $this->assertTrue($result);

        // Verify update
        $records = $this->odoo->read('res.partner', [$id], fields: ['name']);
        $this->assertSame('Updated Test Partner', $records[0]['name']);

        // Delete
        $result = $this->odoo->unlink('res.partner', [$id]);
        $this->assertTrue($result);
        $this->createdPartnerId = null;
    }

    public function testSearchReadWithDomain(): void
    {
        $domain = new Domain();
        $domain->where('is_company', '=', true);

        $companies = $this->odoo->searchRead(
            'res.partner',
            $domain,
            fields: ['name', 'is_company'],
            limit: 5,
        );

        $this->assertIsArray($companies);
        foreach ($companies as $company) {
            $this->assertTrue($company['is_company']);
        }
    }

    public function testFieldsGet(): void
    {
        $fields = $this->odoo->fieldsGet('res.partner', ['string', 'type']);

        $this->assertIsArray($fields);
        $this->assertArrayHasKey('name', $fields);
    }

    public function testRequestBuilder(): void
    {
        $partners = $this->odoo->model('res.partner')
            ->fields(['name', 'email'])
            ->limit(3)
            ->orderBy('name asc')
            ->get();

        $this->assertIsArray($partners);
        $this->assertLessThanOrEqual(3, count($partners));
    }

    public function testRequestBuilderCount(): void
    {
        $count = $this->odoo->model('res.partner')->count();

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }
}
