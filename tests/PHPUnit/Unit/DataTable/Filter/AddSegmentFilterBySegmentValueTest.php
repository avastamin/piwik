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
use Piwik\Plugins\Actions\Reports\GetOutlinks;
use Piwik\Plugins\UserCountry\Reports\GetCity;
use Piwik\Plugins\UserCountry\Reports\GetCountry;
use Piwik\Plugins\VisitsSummary\Reports\Get;
use Piwik\Tests\Framework\TestCase\UnitTestCase;

/**
 * @group AddSegmentFilterBySegmentValueTest
 * @group DataTable
 * @group Filter
 * @group Unit
 * @group Core
 */
class AddSegmentFilterBySegmentValueTest extends UnitTestCase
{
    private $filter = 'AddSegmentFilterBySegmentValue';

    /**
     * @var DataTable
     */
    private $table;

    private $report;

    public function setUp()
    {
        $this->report = new GetCity();
        $this->table = new DataTable();
        $this->addRowWithMetadata(array('test' => '1'));
        $this->addRowWithMetadata(array('test' => '2', 'segmentValue' => 'teeest'));
        $this->addRowWithMetadata(array('test' => '3', 'segmentValue' => 'existing', 'segmentFilter' => 'city==mytest'));
        $this->addRowWithMetadata(array('test' => '1', 'segmentValue' => 'test/test2.r'));
        $this->addRowWithMetadata(array('test' => '4'));
    }

    private function addRowWithMetadata($metadata)
    {
        $row = new Row(array(Row::COLUMNS => array('label' => 'val1')));
        foreach ($metadata as $name => $value) {
            $row->setMetadata($name, $value);
        }
        $this->table->addRow($row);

        return $row;
    }

    public function test_filter_shouldGenerateASegmentFilterIfSegmentValueIsPresent()
    {
        $segmentValue = 'existing';
        $expectedSegmentFilter = 'city==existing';
        $this->assertSegmentFilterForSegmentValueAndReport($this->report, $segmentValue, $expectedSegmentFilter);
    }

    public function test_filter_shouldUrlEncodeTheValue()
    {
        $segmentValue = 'existing täs/ts';
        $expectedSegmentFilter = 'city==existing+t%C3%A4s%2Fts';
        $this->assertSegmentFilterForSegmentValueAndReport($this->report, $segmentValue, $expectedSegmentFilter);
    }

    public function test_filter_shouldNotOverwriteAnExistingSegmentValue()
    {
        $row = $this->addRowWithMetadata(array('segmentValue' => 'existing', 'segmentFilter' => 'city==mytest'));

        $this->table->filter($this->filter, array($this->report));

        $this->assertSegmentFilter('city==mytest', $row);
    }

    public function test_filter_shouldUseTheFirstSegment_IfAReportHasMultiple()
    {
        $report = new GetCountry();
        $this->assertCount(2, $report->getDimension()->getSegments());

        $this->assertSegmentFilterForSegmentValueAndReport($report, $segmentValue = 'existing', 'countryCode==existing');
    }

    public function test_filter_shouldNotGenerateASegmentFilter_IfReportHasNoDimension()
    {
        $report = new Get(); // VisitsSummary.get has no dimension
        $this->assertNull($report->getDimension());

        $this->assertSegmentFilterForSegmentValueAndReport($report, $segmentValue = 'existing', false);
    }

    public function test_filter_shouldNotGenerateASegmentFilter_IfDimensionHasNoSegmentFilter()
    {
        // outlinks currently has a dimensions but no segments, we have to use another report once it has segments
        $report = new GetOutlinks();
        $this->assertEmpty($report->getDimension()->getSegments());

        $this->assertSegmentFilterForSegmentValueAndReport($report, $segmentValue = 'existing', false);
    }

    public function test_filter_shouldNotFail_IfNoReportGiven()
    {
        $this->assertSegmentFilterForSegmentValueAndReport($report = null, $segmentValue = 'existing', false);
    }

    public function test_filter_shouldNotFail_IfDataTableHasNoRows()
    {
        $table = new DataTable();
        $table->filter($this->filter, array($this->report));
        $this->assertSame(0, $table->getRowsCount());
    }

    private function assertSegmentFilterForSegmentValueAndReport($report, $segmentValue, $expectedSegmentFilter)
    {
        $row = $this->addRowWithMetadata(array('segmentValue' => $segmentValue));

        $this->table->filter($this->filter, array($report));

        $this->assertSegmentFilter($expectedSegmentFilter, $row);
    }

    private function assertSegmentFilter($expected, Row $row)
    {
        $segmentFilter = $row->getMetadata('segmentFilter');
        $this->assertSame($expected, $segmentFilter);
    }

}
