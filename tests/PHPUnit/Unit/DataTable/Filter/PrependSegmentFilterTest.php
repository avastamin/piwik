<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Core\DataTable\Filter;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Tests\Framework\TestCase\UnitTestCase;

/**
 * @group PrependSegmentFilterTest
 * @group DataTable
 * @group Filter
 * @group Unit
 * @group Core
 */
class PrependSegmentFilterTest extends UnitTestCase
{
    private $filter = 'PrependSegmentFilter';

    /**
     * @var DataTable
     */
    private $table;

    public function setUp()
    {
        $this->table = new DataTable();
        $this->addRowWithMetadata(array('test' => '1'));
        $this->addRowWithMetadata(array('test' => '2', 'segmentFilter' => 'country=NZ'));
        $this->addRowWithMetadata(array('test' => '3'));
        $this->addRowWithMetadata(array('test' => '1', 'segmentFilter' => 'country=AU'));
        $this->addRowWithMetadata(array('test' => '4', 'segmentFilter' => ''));
    }

    private function addRowWithMetadata($metadata)
    {
        $row = new Row(array(Row::COLUMNS => array('label' => 'val1')));
        foreach ($metadata as $name => $value) {
            $row->setMetadata($name, $value);
        }
        $this->table->addRow($row);
    }

    public function test_filter_shouldRemoveAllMetadataEntriesHavingTheGivenName()
    {
        $prepend = 'city=test;';
        $this->table->filter($this->filter, array($prepend));

        $metadata = $this->table->getRowsMetadata('segmentFilter');
        $this->assertSame(array(
            false,
            $prepend . 'country=NZ',
            false,
            $prepend . 'country=AU',
            $prepend),
            $metadata);

        // should be still the same
        $metadata = $this->table->getRowsMetadata('test');
        $this->assertSame(array('1', '2', '3', '1', '4'), $metadata);
    }
}
