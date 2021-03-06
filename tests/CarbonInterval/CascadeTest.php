<?php

namespace Tests\CarbonInterval;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Tests\AbstractTestCase;

class CascadeTest extends AbstractTestCase
{
    /**
     * @dataProvider provideIntervalSpecs
     */
    public function testCascadesOverflowedValues($spec, $expected)
    {
        $this->assertSame(
            $expected,
            CarbonInterval::fromString($spec)->cascade()->spec()
        );
    }

    public function provideIntervalSpecs()
    {
        return [
            ['3600s',                        'PT1H'],
            ['10000s',                       'PT2H46M40S'],
            ['1276d',                        'P3Y9M16D'],
            ['47d 14h',                      'P1M19DT14H'],
            ['2y 123mo 5w 6d 47h 160m 217s', 'P12Y4M15DT1H43M37S'],
        ];
    }

    public function testCascadesWithMicroseconds()
    {
        $interval = CarbonInterval::fromString('1040ms 3012µs')->cascade();

        $this->assertSame('PT1S', $interval->spec());
        $this->assertSame(43, $interval->milliseconds);
        $this->assertSame(43012, $interval->microseconds);
    }

    /**
     * @dataProvider provideCustomIntervalSpecs
     */
    public function testCustomCascadesOverflowedValues($spec, $expected)
    {
        $cascades = CarbonInterval::getCascadeFactors();
        CarbonInterval::setCascadeFactors([
            'minutes' => [Carbon::SECONDS_PER_MINUTE, 'seconds'],
            'hours' => [Carbon::MINUTES_PER_HOUR, 'minutes'],
            'dayz' => [8, 'hours'],
            'weeks' => [5, 'dayz'],
        ]);
        $actual = CarbonInterval::fromString($spec)->cascade()->forHumans(true);
        CarbonInterval::setCascadeFactors($cascades);

        $this->assertSame($expected, $actual);
    }

    public function provideCustomIntervalSpecs()
    {
        return [
            ['3600s',                        '1h'],
            ['10000s',                       '2h 46m 40s'],
            ['1276d',                        '255w 1d'],
            ['47d 14h',                      '9w 3d 6h'],
            ['2y 123mo 5w 6d 47h 160m 217s', '2yrs 123mos 7w 2d 1h 43m 37s'],
        ];
    }

    /**
     * @dataProvider provideCustomIntervalSpecsLongFormat
     */
    public function testCustomCascadesOverflowedValuesLongFormat($spec, $expected)
    {
        $cascades = CarbonInterval::getCascadeFactors();
        CarbonInterval::setCascadeFactors([
            'minutes' => [Carbon::SECONDS_PER_MINUTE, 'seconds'],
            'hours' => [Carbon::MINUTES_PER_HOUR, 'minutes'],
            'dayz' => [8, 'hours'],
            'weeks' => [5, 'dayz'],
        ]);
        $actual = CarbonInterval::fromString($spec)->cascade()->forHumans(false);
        CarbonInterval::setCascadeFactors($cascades);

        $this->assertSame($expected, $actual);
    }

    public function provideCustomIntervalSpecsLongFormat()
    {
        return [
            ['3600s',                        '1 hour'],
            ['10000s',                       '2 hours 46 minutes 40 seconds'],
            ['1276d',                        '255 weeks 1 day'],
            ['47d 14h',                      '9 weeks 3 days 6 hours'],
            ['2y 123mo 5w 6d 47h 160m 217s', '2 years 123 months 7 weeks 2 days 1 hour 43 minutes 37 seconds'],
        ];
    }

    public function testMultipleAdd()
    {
        $cascades = CarbonInterval::getCascadeFactors();
        CarbonInterval::setCascadeFactors([
            'minutes' => [Carbon::SECONDS_PER_MINUTE, 'seconds'],
            'hours' => [Carbon::MINUTES_PER_HOUR, 'minutes'],
            'days' => [8, 'hours'],
            'weeks' => [5, 'days'],
        ]);
        $actual = CarbonInterval::fromString('3d')
            ->add(CarbonInterval::fromString('1d 5h'))
            ->add(CarbonInterval::fromString('7h'))
            ->cascade()
            ->forHumans(true);
        CarbonInterval::setCascadeFactors($cascades);
        $this->assertSame('1w 4h', $actual);
    }

    public function testFactorsGroups()
    {
        $cascades = CarbonInterval::getCascadeFactors();
        CarbonInterval::setCascadeFactors([
            'hours' => [Carbon::MINUTES_PER_HOUR, 'minutes'],
            'weeks' => [5, 'days'],
        ]);
        $actual = CarbonInterval::fromString('3d 50m')
            ->add(CarbonInterval::fromString('1d 5h 30m'))
            ->add(CarbonInterval::fromString('7h 45m'))
            ->add(CarbonInterval::fromString('1w 15m'))
            ->cascade()
            ->forHumans(true);
        CarbonInterval::setCascadeFactors($cascades);
        $this->assertSame('1w 4d 14h 20m', $actual);
    }

    public function testGetFactor()
    {
        $this->assertSame(28, CarbonInterval::getFactor('day', 'months'));
        $this->assertSame(28, CarbonInterval::getFactor('day', 'month'));
        $this->assertSame(28, CarbonInterval::getFactor('days', 'month'));
        $this->assertSame(28, CarbonInterval::getFactor('day', 'month'));
        $this->assertSame(28, CarbonInterval::getFactor('dayz', 'months'));
    }
}
